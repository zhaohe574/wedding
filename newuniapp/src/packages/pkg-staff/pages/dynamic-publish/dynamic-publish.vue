<template>
    <page-shell :title="isEdit ? '编辑动态' : '发布动态'" bg="page" :show-back="true" :bottom-bar="guardAllowed">
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            :skeleton-rows="5"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <template v-else>
            <view v-if="loading && isEdit" class="pad">
                <base-skeleton :rows="4" />
            </view>

            <base-empty
                v-else-if="error && isEdit && !hasLoaded"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="() => load()"
            />

            <view v-else class="dp">
            <!-- 灰度关闭：表单可浏览，但提交/删除都不可用（入口已隐藏，这是直达路径的兜底） -->
            <view v-if="!canWrite" class="gate-note">
                <tn-icon name="tips" :size="24" class="gate-note__icon" />
                <text class="gate-note__text">{{ STAFF_WRITE_GATE_TIP }}</text>
            </view>

            <!-- 编辑态提示：重新提交 = 重新排队审核，不能让用户以为改完即生效 -->
            <view v-if="isEdit" class="edit-note">
                <tn-icon name="tips" :size="24" class="edit-note__icon" />
                <text class="edit-note__text">重新提交后将进入审核，审核通过前前台仍展示当前内容</text>
            </view>

            <!-- 动态类型 -->
            <base-card>
                <section-header title="动态类型" flush />
                <view class="type-row">
                    <chip-group
                        :items="[
                            { label: '图文', value: 1 },
                            { label: '视频', value: 2 }
                        ]"
                        :model-value="form.dynamic_type"
                        @change="(v: string | number) => (form.dynamic_type = Number(v))"
                    />
                </view>
            </base-card>

            <!-- 内容 -->
            <base-card>
                <section-header title="动态内容" flush />
                <form-field
                    label="标题"
                    type="input"
                    :required="true"
                    :maxlength="100"
                    placeholder="一句话说清这条动态"
                    :error="errors.title"
                    :model-value="form.title"
                    @update:model-value="(v: string) => (form.title = v)"
                />
                <form-field
                    label="正文"
                    type="textarea"
                    layout="stack"
                    :required="true"
                    placeholder="分享服务花絮、婚礼现场、幕后故事…"
                    :error="errors.content"
                    :model-value="form.content"
                    @update:model-value="(v: string) => (form.content = v)"
                />
            </base-card>

            <!-- 图文图片 -->
            <base-card v-if="form.dynamic_type === 1">
                <form-field
                    label="图片"
                    type="upload"
                    layout="stack"
                    :max="9"
                    hint="最多 9 张，第一张将作为封面"
                    :error="errors.images"
                    :model-value="form.images"
                    @update:model-value="(v: string[]) => (form.images = v)"
                />
            </base-card>

            <!-- 视频 -->
            <base-card v-if="form.dynamic_type === 2">
                <section-header title="视频" flush />
                <view class="video-block">
                    <view class="video-block__head">
                        <text class="video-block__label">视频文件</text>
                        <text class="video-block__status">{{
                            videoUploading
                                ? `上传中 ${videoProgress}%`
                                : videoUrl
                                  ? '已上传'
                                  : '必填'
                        }}</text>
                    </view>
                    <view v-if="videoUrl && !videoUploading" class="video-block__frame">
                        <video
                            class="video-block__player"
                            :src="videoUrl"
                            controls
                            object-fit="contain"
                        />
                    </view>
                    <view
                        v-else
                        hover-class="is-pressed"
                        class="video-block__pick"
                        :class="{ 'is-busy': videoUploading }"
                        :aria-label="videoUrl ? '重新选择视频' : '点击上传视频'"
                        @tap="pickVideo"
                    >
                        <view class="video-block__badge">
                            <tn-icon :name="videoUploading ? 'loading' : 'upload'" :size="36" />
                        </view>
                        <text class="video-block__pick-title">{{
                            videoUploading
                                ? `视频上传中 ${videoProgress}%`
                                : '点击上传视频'
                        }}</text>
                        <text class="video-block__pick-sub">MP4 / MOV · 不超过 100MB、60 秒</text>
                        <view v-if="videoUploading" class="video-block__progress">
                            <view
                                class="video-block__progress-fill"
                                :style="{ width: `${videoProgress}%` }"
                            />
                        </view>
                    </view>
                    <view v-if="videoUrl && !videoUploading" class="video-block__actions">
                        <view
                            hover-class="is-pressed"
                            class="video-block__action"
                            aria-label="重新上传视频"
                            @tap="pickVideo"
                        >
                            <tn-icon name="upload" :size="24" />
                            <text class="video-block__action-text">重新上传</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            class="video-block__action video-block__action--danger"
                            aria-label="移除视频"
                            @tap="removeVideo"
                        >
                            <tn-icon name="delete" :size="24" />
                            <text class="video-block__action-text">移除视频</text>
                        </view>
                    </view>
                </view>
                <text class="video-limit">视频不超过 100MB、60 秒</text>

                <form-field
                    label="视频封面"
                    type="upload"
                    layout="stack"
                    :max="1"
                    hint="选填，不传时由平台截取"
                    :model-value="videoCoverList"
                    @update:model-value="(v: string[]) => (videoCoverList = v)"
                />
            </base-card>

            <!-- 更多设置 -->
            <base-card>
                <section-header title="更多设置" flush />
                <form-field
                    label="标签"
                    type="input"
                    :maxlength="50"
                    placeholder="多个标签用逗号分隔，如：中式,户外"
                    :model-value="form.tags"
                    @update:model-value="(v: string) => (form.tags = v)"
                />
                <form-field
                    label="地点"
                    type="input"
                    :maxlength="50"
                    placeholder="选填，如：杭州 · 西子宾馆"
                    :model-value="form.location"
                    @update:model-value="(v: string) => (form.location = v)"
                />
                <view class="switch-row">
                    <view class="switch-row__body">
                        <text class="switch-row__label">允许评论</text>
                        <text class="switch-row__hint">关闭后客户不能在这条动态下评论</text>
                    </view>
                    <!-- switch 组件不支持 var() 色值，取品牌金定值（全站既有口径） -->
                    <switch
                        :checked="form.allow_comment"
                        color="#C6A15B"
                        @change="onAllowCommentChange"
                    />
                </view>
            </base-card>

            <!-- 编辑态删除：放在非吸底区，避免与提交栏误触；灰度关闭时一并隐藏 -->
            <view v-if="isEdit && canWrite" class="danger-zone">
                <base-button
                    text="删除动态"
                    variant="danger"
                    size="md"
                    :loading="deleting"
                    @click="onDelete"
                />
            </view>
        </view>
        </template>

        <template #bottom>
            <bottom-bar v-if="guardAllowed">
                <text class="bar__note">{{
                    canWrite ? '提交后进入审核，审核通过才对外展示' : STAFF_WRITE_GATE_TIP
                }}</text>
                <template #actions>
                    <base-button
                        v-if="canWrite"
                        :text="submitting ? '提交中…' : isEdit ? '重新提交' : '提交审核'"
                        variant="ink"
                        size="lg"
                        :block="false"
                        :loading="submitting"
                        :disabled="submitting"
                        @click="submit"
                    />
                </template>
            </bottom-bar>
        </template>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 服务人员：发布 / 编辑动态（P0 写能力）。
 *
 * 三条硬口径：
 * 1. 提交后进入审核（status=0 待审），toast 文案必须是「已提交，等待审核」，
 *    编辑态是「已重新提交，进入审核」—— 不说「发布成功」；
 * 2. 视频上传前端限 100MB / 60s（chooseVideo 统一校验），封面单图选填；
 * 3. 编辑态删除按钮放页面非吸底区（danger + showModal 二次确认），
 *    与吸底提交栏物理隔离，防误触。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    getStaffDynamicDetail,
    staffDynamicAdd,
    staffDynamicEdit,
    staffDynamicDelete
} from '@/api/staff-center'
import { uploadVideo } from '@/api/app'
import { chooseVideo } from '@/composables/useVideoPick'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { useStaffWriteGate, STAFF_WRITE_GATE_TIP } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'

const editId = ref(0)
const isEdit = computed(() => editId.value > 0)

const { canWrite } = useStaffWriteGate()
const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const loading = ref(false)
const hasLoaded = ref(false)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))
const error = computed(() => !!rawError.value)

const form = reactive({
    dynamic_type: 1,
    title: '',
    content: '',
    images: [] as string[],
    tags: '',
    location: '',
    allow_comment: true
})

/**
 * 视频封面用 string[] 承载：form-field type=upload 的 modelValue 契约是数组，
 * 传 string 的话组件的 uploadList 恒为空 —— 回填的封面缩略图不会显示。
 * 取值/回填都走 videoCoverList.value[0]。
 */
const videoCoverList = ref<string[]>([])

const errors = reactive({
    title: '',
    content: '',
    images: ''
})

const submitting = ref(false)
const deleting = ref(false)

/* ---------------- 视频上传 ---------------- */

const videoUrl = ref('')
const videoUploading = ref(false)
const videoProgress = ref(0)

async function pickVideo() {
    if (videoUploading.value) return
    // 与页面提示文案一致：100MB / 60s
    const picked = await chooseVideo({ maxDuration: 60, maxSizeMB: 100 })
    if (!picked) return
    videoUploading.value = true
    videoProgress.value = 0
    try {
        const up = await uploadVideo(picked.tempFilePath, (p) => (videoProgress.value = p))
        const url = (up as any)?.uri || (up as any)?.url
        videoUrl.value = typeof url === 'string' && url ? url : picked.tempFilePath
    } catch {
        // 请求层已提示；保留旧视频，用户可重试
    } finally {
        videoUploading.value = false
    }
}

function removeVideo() {
    videoUrl.value = ''
    videoProgress.value = 0
}

function onAllowCommentChange(e: any) {
    form.allow_comment = !!e?.detail?.value
}

/* ---------------- 校验与提交 ---------------- */

function validate(): boolean {
    errors.title = form.title.trim() ? '' : '请输入标题'
    errors.content = form.content.trim() ? '' : '请输入正文'
    errors.images = ''
    if (form.dynamic_type === 1 && !form.images.length) {
        errors.images = '请至少上传一张图片'
    }

    const first = errors.title || errors.content || errors.images
    if (first) {
        toast.warning(first)
        return false
    }
    // 视频模式：视频本体是硬条件（不在 errors 里，无对应字段位）
    if (form.dynamic_type === 2 && !videoUrl.value) {
        toast.warning('请上传视频')
        return false
    }
    return true
}

function submit() {
    if (!guardAllowed.value || submitting.value) return
    // 灰度兜底：入口已隐藏，这里是直达路径的最后闸门（后端也会拒绝）
    if (!canWrite.value) {
        toast.warning(STAFF_WRITE_GATE_TIP)
        return
    }
    if (!validate()) return
    doSubmit()
}

async function doSubmit() {
    if (!guardAllowed.value) return
    submitting.value = true
    try {
        const payload: Record<string, any> = {
            dynamic_type: form.dynamic_type,
            title: form.title.trim(),
            content: form.content.trim(),
            tags: form.tags.trim() || undefined,
            location: form.location.trim() || undefined,
            allow_comment: form.allow_comment ? 1 : 0
        }
        if (form.dynamic_type === 1) {
            payload.images = form.images
        } else {
            payload.video_url = videoUrl.value
            payload.video_cover = videoCoverList.value[0] || undefined
        }

        if (isEdit.value) {
            payload.id = editId.value
            await staffDynamicEdit(payload as any)
            toast.success('已重新提交，进入审核')
        } else {
            await staffDynamicAdd(payload as any)
            toast.success('已提交，等待审核')
        }
        // dynamic-list 页 onShow 重新拉取
        setTimeout(() => uni.navigateBack(), 600)
    } catch (e: any) {
        // 写封装是 silent:true，错误反馈由页面承担：
        // 灰度关闭 / 内容安全命中（含违规词括号提示）/ 其他业务拒绝 → 后端 msg 直接展示
        toast.error(String(e?.message || '提交失败，请稍后重试'))
    } finally {
        submitting.value = false
    }
}

/* ---------------- 删除（编辑态） ---------------- */

function onDelete() {
    if (!guardAllowed.value) return
    uni.showModal({
        title: '删除动态',
        content: '删除后不可恢复，确认删除这条动态？',
        confirmText: '删除',
        cancelText: '取消',
        confirmColor: '#D8584A',
        success: async (r) => {
            if (!r.confirm) return
            deleting.value = true
            try {
                await staffDynamicDelete({ id: editId.value })
                toast.success('已删除')
                setTimeout(() => uni.navigateBack(), 600)
            } catch (e: any) {
                // 灰度关闭 / 内容安全命中 / 其他业务拒绝：后端 msg 直接展示（写封装 silent:true）
                toast.error(String(e?.message || '删除失败，请稍后重试'))
            } finally {
                deleting.value = false
            }
        }
    })
}

/* ---------------- 加载回填 ---------------- */

async function load() {
    if (loading.value) return
    loading.value = true
    if (isEdit.value && !hasLoaded.value) rawError.value = null
    try {
        if (isEdit.value) {
            const res: any = await getStaffDynamicDetail({ id: editId.value })
            const d = res?.dynamic || res?.detail || res || {}
            form.dynamic_type = Number(d.dynamic_type) === 2 ? 2 : 1
            form.title = String(d.title || '')
            form.content = String(d.content || '')
            form.images = Array.isArray(d.images) ? d.images.map(String) : []
            videoUrl.value = String(d.video_url || '')
            videoCoverList.value = d.video_cover ? [String(d.video_cover)] : []
            form.tags = String(d.tags || '')
            form.location = String(d.location || '')
            form.allow_comment = Number(d.allow_comment) !== 0
            hasLoaded.value = true
        }
    } catch (e) {
        if (hasLoaded.value) {
            toast.error('刷新失败，请稍后重试')
        } else {
            rawError.value = e
        }
    } finally {
        loading.value = false
    }
}

async function init(forceGuard = false) {
    if (!(await verify(forceGuard))) return
    if (isEdit.value) await load()
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        void init()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

onLoad((options: any) => {
    editId.value = Number(options?.id || 0) || 0
    void init()
})
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-3) var(--sp-4);
}

.dp {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4) var(--sp-4);
}

/* 灰度关闭提示条 */
.gate-note {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);

    &__icon {
        flex-shrink: 0;
        margin-top: 4rpx;
        color: var(--c-ink-3);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

/* 编辑态提示 */
.edit-note {    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-warning-bg);

    &__icon {
        flex-shrink: 0;
        margin-top: 4rpx;
        color: var(--c-warning);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

.type-row {
    margin-top: var(--sp-3);
}

/* 视频上传 */
.video-block {
    margin-top: var(--sp-3);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__label {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__status {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__frame {
        border-radius: var(--r-md);
        border: 1rpx solid var(--c-line);
        overflow: hidden;
        background: var(--c-surface-2);
    }

    &__player {
        width: 100%;
        height: 340rpx;
        display: block;
        background: var(--c-surface-2);
    }

    &__pick {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);
        width: 100%;
        height: 300rpx;
        padding: var(--sp-3);
        border: 1rpx dashed var(--c-control-border);
        border-radius: var(--r-md);
        background: var(--c-surface-2);
        box-sizing: border-box;

        &.is-busy {
            pointer-events: none;
            opacity: 0.72;
        }
    }

    &__badge {
        width: 80rpx;
        height: 80rpx;
        margin-bottom: var(--sp-1);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        border: 1rpx solid var(--c-line);
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__pick-title {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__pick-sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__progress {
        width: 60%;
        height: 8rpx;
        margin-top: var(--sp-1);
        border-radius: var(--r-pill);
        background: var(--c-line);
        overflow: hidden;
    }

    &__progress-fill {
        height: 100%;
        border-radius: var(--r-pill);
        background: var(--c-brand);
    }

    &__actions {
        margin-top: var(--sp-2);
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-2);
    }

    &__action {
        display: inline-flex;
        align-items: center;
        gap: var(--sp-1);
        padding: 8rpx var(--sp-3);
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-line);
        background: var(--c-surface);
        color: var(--c-ink-2);

        &--danger {
            border-color: var(--c-danger-bg);
            background: var(--c-danger-bg);
            color: var(--c-danger);
        }
    }

    &__action-text {
        font-size: var(--fs-xs);
        color: inherit;
    }
}

.video-limit {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

/* 开关行 */
.switch-row {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    margin-top: var(--sp-3);
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-control-border);

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__label {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__hint {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

/* 删除区 */
.danger-zone {
    margin-top: var(--sp-2);
}

.bar__note {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}
</style>
