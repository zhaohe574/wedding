<template>
    <page-shell :title="isEdit ? '编辑作品' : '新增作品'" bg="page" :show-back="true" :bottom-bar="guardAllowed">
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
                <base-skeleton :rows="4" variant="card" />
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

            <view v-else class="we">
            <!-- 灰度关闭：表单可浏览，但提交/删除都不可用（入口已隐藏，这是直达路径的兜底） -->
            <view v-if="!canWrite" class="gate-note">
                <tn-icon name="tips" :size="24" class="gate-note__icon" />
                <text class="gate-note__text">{{ STAFF_WRITE_GATE_TIP }}</text>
            </view>

            <!-- 编辑态提示 -->
            <view v-if="isEdit" class="edit-note">
                <tn-icon name="tips" :size="24" class="edit-note__icon" />
                <text class="edit-note__text">重新提交后将进入审核，审核通过前前台仍展示当前内容</text>
            </view>

            <!-- 封面与图集 -->
            <base-card>
                <section-header title="作品图片" flush />
                <form-field
                    label="封面图"
                    type="upload"
                    layout="stack"
                    :max="1"
                    :required="true"
                    :error="errors.cover"
                    :model-value="coverList"
                    @update:model-value="(v: string[]) => (coverList = v)"
                />
                <form-field
                    label="作品图集"
                    type="upload"
                    layout="stack"
                    :max="9"
                    :model-value="form.images"
                    @update:model-value="(v: string[]) => (form.images = v)"
                />
            </base-card>

            <!-- 基本信息 -->
            <base-card>
                <section-header title="基本信息" flush />
                <form-field
                    label="标题"
                    type="input"
                    :required="true"
                    :maxlength="50"
                    placeholder="如：草坪婚礼 · 全程主持"
                    :error="errors.title"
                    :model-value="form.title"
                    @update:model-value="(v: string) => (form.title = v)"
                />
                <form-field label="拍摄日期">
                    <view class="picker-row">
                        <picker
                            class="picker-row__picker"
                            mode="date"
                            :value="form.shoot_date || today"
                            start="2020-01-01"
                            :end="today"
                            @change="onPickDate"
                        >
                            <view class="picker-row__main">
                                <text
                                    :class="
                                        form.shoot_date
                                            ? 'picker-row__text t-num'
                                            : 'picker-row__ph'
                                    "
                                >
                                    {{ form.shoot_date || '选择拍摄 / 服务日期' }}
                                </text>
                                <tn-icon name="right" :size="28" />
                            </view>
                        </picker>
                        <view
                            v-if="form.shoot_date"
                            class="picker-row__clear hit-88"
                            hover-class="is-pressed"
                            @tap.stop="clearShootDate"
                        >
                            <text class="picker-row__clear-text">清空</text>
                        </view>
                    </view>
                </form-field>
                <form-field
                    label="地点"
                    type="input"
                    :maxlength="50"
                    placeholder="选填，如：杭州 · 西子宾馆"
                    :model-value="form.location"
                    @update:model-value="(v: string) => (form.location = v)"
                />
                <form-field
                    label="排序"
                    type="input"
                    :maxlength="4"
                    placeholder="数字越小越靠前，默认 0"
                    :error="errors.sort"
                    :model-value="form.sort"
                    @update:model-value="(v: string) => (form.sort = v)"
                />
            </base-card>

            <!-- 说明与视频 -->
            <base-card>
                <section-header title="说明与视频" flush />
                <form-field
                    label="作品说明"
                    type="textarea"
                    layout="stack"
                    :maxlength="1000"
                    count
                    placeholder="介绍一下这场婚礼的亮点与你的工作内容"
                    :model-value="form.description"
                    @update:model-value="(v: string) => (form.description = v)"
                />
                <view class="video-block">
                    <view class="video-block__head">
                        <text class="video-block__label">视频</text>
                        <text class="video-block__status t-num">{{
                            videoUploading
                                ? `上传中 ${videoProgress}%`
                                : videoUrl
                                  ? '已上传'
                                  : '选填'
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
                        :aria-label="videoUrl ? '重新上传视频' : '点击上传视频'"
                        @tap="pickVideo"
                    >
                        <view class="video-block__badge">
                            <tn-icon :name="videoUploading ? 'loading' : 'upload'" :size="36" />
                        </view>
                        <text class="video-block__pick-title t-num">{{
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
            </base-card>

            <!-- 展示开关 -->
            <base-card>
                <view class="switch-row">
                    <view class="switch-row__body">
                        <text class="switch-row__label">在主页展示该作品</text>
                    </view>
                    <!-- 弹性开关：轨道消费 --c-action-*，明暗两态无需字面色值；
                         change 直接给 boolean，组件内部已发 light 触觉，页面只回写状态 -->
                    <elastic-switch
                        :model-value="form.is_show"
                        aria-label="在主页展示该作品"
                        @change="onShowChange"
                    />
                </view>
            </base-card>

            <!-- 编辑态删除：非吸底区，与提交栏隔离；灰度关闭时一并隐藏 -->
            <view v-if="isEdit && canWrite" class="danger-zone">
                <base-button
                    text="删除作品"
                    variant="danger"
                    size="md"
                    :loading="deleting"
                    haptic-level="heavy"
                    @click="onDelete"
                />
            </view>
        </view>
        </template>

        <template #bottom>
            <bottom-bar v-if="guardAllowed">
                <text v-if="!canWrite" class="bar__note">{{ STAFF_WRITE_GATE_TIP }}</text>
                <template #actions>
                    <base-button
                        v-if="canWrite"
                        :text="submitting ? '提交中…' : isEdit ? '重新提交' : '提交审核'"
                        variant="ink"
                        size="lg"
                        :block="false"
                        :loading="submitting"
                        :disabled="submitting"
                        haptic-level="medium"
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
 * 服务人员：新增 / 编辑作品（P0 写能力）。
 *
 * 硬口径：
 * 1. cover 单图必填、title 必填，提交前手算校验 + toast.warning 第一条；
 * 2. shoot_date 用 tn-calendar（v-model:show 绑 show，与 tn-popup 的 v-model 写法不同，别混）；
 * 3. 提交后 audit_status=0 待审，toast 文案是「已提交，等待审核」/「已重新提交，进入审核」；
 * 4. 编辑态删除按钮放非吸底区（danger + showModal 二次确认）。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    getStaffWorkDetail,
    staffWorkAdd,
    staffWorkEdit,
    staffWorkDelete
} from '@/api/staff-center'
import { uploadVideo } from '@/api/app'
import { chooseVideo } from '@/composables/useVideoPick'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { useStaffWriteGate, STAFF_WRITE_GATE_TIP } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'
import { schemeColor } from '@/utils/scheme-color'

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

/**
 * 封面单图也用 string[] 承载：form-field type=upload 的 modelValue 契约是数组，
 * 传 string 的话组件的 uploadList 恒为空 —— 回填的封面缩略图不会显示（已踩过，别改回去）。
 * 取值/回填都走 coverList.value[0]。
 */
const coverList = ref<string[]>([])
const videoUrl = ref('')

const form = reactive({
    title: '',
    images: [] as string[],
    description: '',
    shoot_date: '',
    location: '',
    sort: '',
    is_show: true
})

const errors = reactive({
    title: '',
    cover: '',
    sort: ''
})

const submitting = ref(false)
const deleting = ref(false)

/* ---------------- 拍摄日期 ---------------- */

const now = new Date()
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(
    now.getDate()
).padStart(2, '0')}`

function onPickDate(e: any) {
    const val = String(e?.detail?.value || '').trim()
    if (val) {
        form.shoot_date = val.slice(0, 10)
    }
}

function clearShootDate() {
    haptic('light', 'work-edit-clear-date')
    form.shoot_date = ''
}

/* ---------------- 视频上传 ---------------- */

const videoUploading = ref(false)
const videoProgress = ref(0)

async function pickVideo() {
    if (videoUploading.value) return
    haptic('light', 'work-edit-video-pick')
    const picked = await chooseVideo({ maxDuration: 60, maxSizeMB: 100 })
    if (!picked) return
    videoUploading.value = true
    videoProgress.value = 0
    try {
        const up = await uploadVideo(picked.tempFilePath, (p) => (videoProgress.value = p))
        const url = (up as any)?.uri || (up as any)?.url
        videoUrl.value = typeof url === 'string' && url ? url : picked.tempFilePath
    } catch {
        // 请求层已提示；保留旧视频
    } finally {
        videoUploading.value = false
    }
}

function removeVideo() {
    haptic('light', 'work-edit-video-remove')
    videoUrl.value = ''
    videoProgress.value = 0
}

function onShowChange(val: boolean) {
    form.is_show = !!val
}

/* ---------------- 校验与提交 ---------------- */

function validate(): boolean {
    errors.title = form.title.trim() ? '' : '请输入标题'
    errors.cover = coverList.value.length ? '' : '请上传封面图'
    const sort = form.sort.trim()
    errors.sort = sort && !/^\d{1,4}$/.test(sort) ? '排序请填数字' : ''

    const first = errors.title || errors.cover || errors.sort
    if (first) {
        toast.warning(first)
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
            title: form.title.trim(),
            cover: coverList.value[0] || '',
            images: form.images.length ? form.images : undefined,
            video: videoUrl.value || undefined,
            description: form.description.trim() || undefined,
            shoot_date: form.shoot_date || undefined,
            location: form.location.trim() || undefined,
            sort: form.sort.trim() ? Number(form.sort.trim()) : undefined,
            is_show: form.is_show ? 1 : 0
        }

        if (isEdit.value) {
            payload.id = editId.value
            await staffWorkEdit(payload as any)
            toast.success('已重新提交，进入审核')
        } else {
            await staffWorkAdd(payload as any)
            toast.success('已提交，等待审核')
        }
        // work-list 页 onShow 重新拉取
        setTimeout(() => uni.navigateBack(), 600)
    } catch (e: any) {
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
        title: '删除作品',
        content: '删除后不可恢复，确认删除这个作品？',
        confirmText: '删除',
        cancelText: '取消',
        // 删除属不可逆操作，uni.showModal 居中确认弹窗保持原生居中形态
        confirmColor: schemeColor('danger'),
        success: async (r) => {
            if (!r.confirm) return
            deleting.value = true
            try {
                await staffWorkDelete({ id: editId.value })
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
            const res: any = await getStaffWorkDetail({ id: editId.value })
            const d = res?.work || res?.detail || res || {}
            form.title = String(d.title || '')
            coverList.value = d.cover ? [String(d.cover)] : []
            form.images = Array.isArray(d.images) ? d.images.map(String) : []
            videoUrl.value = String(d.video || '')
            form.description = String(d.description || '')
            form.shoot_date = String(d.shoot_date || '').slice(0, 10)
            form.location = String(d.location || '')
            form.sort = Number(d.sort) > 0 ? String(Number(d.sort)) : '0'
            form.is_show = Number(d.is_show) !== 0
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

.we {
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
.edit-note {
    display: flex;
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

/* 视频上传 */
.video-block {
    margin-top: var(--sp-3);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
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
        margin-top: var(--sp-2);
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
        margin-top: var(--sp-2);
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
        justify-content: center;
        gap: var(--sp-1);
        /* 相邻小按钮不叠加 .hit-88（伪元素会互相覆盖），直接把盒高做到 88rpx */
        min-height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        color: var(--c-ink-2);
        transition: transform var(--dur-press) var(--ease-out);

        &.is-pressed {
            transform: scale(0.96);
        }

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

/* 开关行 */
.switch-row {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    /* 整行 88rpx：与 elastic-switch 自身热区对齐，视觉基线不塌 */
    min-height: 88rpx;

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
}

/* 删除区 */
.danger-zone {
    margin-top: var(--sp-2);
}

.bar__note {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

.picker-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    width: 100%;

    &__picker {
        flex: 1;
        min-width: 0;
    }

    &__main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        color: var(--c-ink-3);
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__ph {
        font-size: var(--fs-md);
        color: var(--c-ink-3);
    }

    &__clear {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 56rpx;
        padding: 0 var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        transition: transform var(--dur-press) var(--ease-out);

        &.is-pressed {
            transform: scale(0.94);
        }
    }

    &__clear-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

/* v10 §3.13：减弱动效下按压不缩放 */
@media (prefers-reduced-motion: reduce) {
    .video-block__action,
    .picker-row__clear {
        transition: none;
    }

    .video-block__action.is-pressed,
    .picker-row__clear.is-pressed {
        transform: none;
    }
}
</style>
