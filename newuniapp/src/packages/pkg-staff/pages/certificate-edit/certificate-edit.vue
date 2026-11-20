<template>
    <page-shell :title="isEdit ? '编辑证书' : '新增证书'" bg="page" :show-back="true" :bottom-bar="true">
        <!-- 传参异常兜底（编辑态 query 字段全空时触发）：只给返回，重试无意义 -->
        <base-empty
            v-if="error"
            status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title"
            :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed
            @primary="onPrimary"
        />

        <view v-else class="ce">
            <!-- 灰度关闭：表单可浏览，但提交/删除都不可用（入口已隐藏，这是直达路径的兜底） -->
            <view v-if="!canWrite" class="gate-note">
                <tn-icon name="tips" :size="24" class="gate-note__icon" />
                <text class="gate-note__text">{{ STAFF_WRITE_GATE_TIP }}</text>
            </view>

            <!-- 编辑态提示：证书编辑回填来自列表页传参（契约无 certificateDetail 端点，不私造接口） -->
            <view v-if="isEdit" class="edit-note">
                <tn-icon name="tips" :size="24" class="edit-note__icon" />
                <text class="edit-note__text">重新提交后将进入审核，审核通过前前台仍展示当前内容</text>
            </view>

            <!-- 证书信息 -->
            <base-card>
                <section-header title="证书信息" flush />
                <form-field
                    label="证书名称"
                    type="input"
                    :required="true"
                    :maxlength="50"
                    placeholder="如：婚礼主持人职业资格证书"
                    :error="errors.name"
                    :model-value="form.name"
                    @update:model-value="(v: string) => (form.name = v)"
                />
                <form-field
                    label="证书类型"
                    type="input"
                    :maxlength="50"
                    placeholder="选填，如：职业资格"
                    :model-value="form.type"
                    @update:model-value="(v: string) => (form.type = v)"
                />
                <form-field
                    label="证书编号"
                    type="input"
                    :maxlength="100"
                    placeholder="选填，证书上的编号"
                    :model-value="form.sn"
                    @update:model-value="(v: string) => (form.sn = v)"
                />
                <form-field
                    label="发证机构"
                    type="input"
                    :maxlength="50"
                    placeholder="选填，如：人力资源和社会保障部"
                    :model-value="form.issue_org"
                    @update:model-value="(v: string) => (form.issue_org = v)"
                />
            </base-card>

            <!-- 照片与日期 -->
            <base-card>
                <section-header title="照片与日期" flush />
                <form-field
                    label="证书照片"
                    type="upload"
                    layout="stack"
                    :max="1"
                    hint="选填，建议上传证书全页照片"
                    :model-value="imageList"
                    @update:model-value="(v: string[]) => (imageList = v)"
                />
                <form-field
                    label="发证日期"
                    type="picker"
                    placeholder="选择发证日期"
                    :model-value="form.issue_date"
                    @tap="openCalendar('issue')"
                />
                <form-field
                    label="有效期至"
                    type="picker"
                    placeholder="选择有效期截止日期"
                    hint="长期有效的证书可不填"
                    :model-value="form.expire_date"
                    @tap="openCalendar('expire')"
                />
            </base-card>

            <!-- 编辑态删除：非吸底区，与提交栏隔离；灰度关闭时一并隐藏 -->
            <view v-if="isEdit && canWrite" class="danger-zone">
                <base-button
                    text="删除证书"
                    variant="danger"
                    size="md"
                    :loading="deleting"
                    @click="onDelete"
                />
            </view>
        </view>

        <template #bottom>
            <bottom-bar>
                <text class="bar__note">{{
                    canWrite ? '提交后进入审核，审核通过才展示在主页' : STAFF_WRITE_GATE_TIP
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

    <!-- 日期选择：单实例日历，target 区分发证/到期（两个 tn-calendar 不能同时开） -->
    <tn-calendar
        v-model:show="calendarShow"
        mode="single"
        :min-date="minDate"
        @confirm="onPickDate"
    />
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 服务人员：新增 / 编辑证书（P0 写能力）。
 *
 * 两条硬口径：
 * 1. 编辑回填不走详情接口：契约只开放 certificateAdd/Edit/Delete（无 certificateDetail），
 *    不私造端点 —— 编辑态数据由 certificate-list 跳转时经 query 传入，
 *    编码由 navigate 的 buildQuery 统一做一层，本页 safeDecode 解回；
 * 2. 提交后 verify_status=0 待审，toast 文案固定「已提交，等待审核」，不说「保存成功」。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    staffCertificateAdd,
    staffCertificateEdit,
    staffCertificateDelete
} from '@/api/staff-center'
import { useStaffWriteGate, STAFF_WRITE_GATE_TIP } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'

const editId = ref(0)
const isEdit = computed(() => editId.value > 0)

const { canWrite } = useStaffWriteGate()

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))
const error = computed(() => !!rawError.value)

function onPrimary() {
    // 编辑态传参异常的兜底动作只有「返回列表重进」；重试拿不到新数据
    uni.navigateBack()
}

/**
 * 证书照片用 string[] 承载：form-field type=upload 的 modelValue 契约是数组，
 * 传 string 的话组件的 uploadList 恒为空 —— 回填的照片缩略图不会显示。
 * 取值/回填都走 imageList.value[0]。
 */
const imageList = ref<string[]>([])

const form = reactive({
    name: '',
    type: '',
    sn: '',
    issue_org: '',
    issue_date: '',
    expire_date: ''
})

const errors = reactive({
    name: ''
})

const submitting = ref(false)
const deleting = ref(false)

/* ---------------- 日期选择 ---------------- */

const calendarShow = ref(false)
/** target：区分当前在选发证日期还是有效期（单实例日历，同时只能开一个） */
const calendarTarget = ref<'issue' | 'expire'>('issue')
/** 2000-01-01：发证日期可能是多年前 */
const minDate = new Date(2000, 0, 1).getTime()

function openCalendar(target: 'issue' | 'expire') {
    calendarTarget.value = target
    calendarShow.value = true
}

function onPickDate(e: any) {
    const v = e?.date ?? e
    let picked = ''
    if (Array.isArray(v)) {
        picked = String(v[0] || '')
    } else if (typeof v === 'number') {
        const d = new Date(v)
        picked = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(
            d.getDate()
        ).padStart(2, '0')}`
    } else if (typeof v === 'string') {
        picked = v.slice(0, 10)
    }
    if (calendarTarget.value === 'issue') form.issue_date = picked
    else form.expire_date = picked
    calendarShow.value = false
}

/* ---------------- 校验与提交 ---------------- */

function validate(): boolean {
    errors.name = form.name.trim() ? '' : '请输入证书名称'
    if (errors.name) {
        toast.warning(errors.name)
        return false
    }
    // 都填了才校验先后：有效期早于发证日期明显是填错
    if (form.issue_date && form.expire_date && form.expire_date < form.issue_date) {
        toast.warning('有效期不能早于发证日期，请检查两个日期')
        return false
    }
    return true
}

function submit() {
    if (submitting.value) return
    // 灰度兜底：入口已隐藏，这里是直达路径的最后闸门（后端也会拒绝）
    if (!canWrite.value) {
        toast.warning(STAFF_WRITE_GATE_TIP)
        return
    }
    if (!validate()) return
    doSubmit()
}

async function doSubmit() {
    submitting.value = true
    try {
        const payload: Record<string, any> = {
            name: form.name.trim(),
            type: form.type.trim() || undefined,
            sn: form.sn.trim() || undefined,
            image: imageList.value[0] || undefined,
            issue_org: form.issue_org.trim() || undefined,
            issue_date: form.issue_date || undefined,
            expire_date: form.expire_date || undefined
        }

        if (isEdit.value) {
            payload.id = editId.value
            await staffCertificateEdit(payload as any)
        } else {
            await staffCertificateAdd(payload as any)
        }
        toast.success('已提交，等待审核')
        // certificate-list 页 onShow 重新拉取
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
    uni.showModal({
        title: '删除证书',
        content: '删除后不可恢复，确认删除这个证书？',
        confirmText: '删除',
        cancelText: '取消',
        confirmColor: '#D8584A',
        success: async (r) => {
            if (!r.confirm) return
            deleting.value = true
            try {
                await staffCertificateDelete({ id: editId.value })
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

/* ---------------- 编辑态：列表页经 query 传入回填 ---------------- */

function safeDecode(v: unknown) {
    try {
        return decodeURIComponent(String(v ?? ''))
    } catch {
        return String(v ?? '')
    }
}

onLoad((options: any) => {
    editId.value = Number(options?.id || 0) || 0
    if (!isEdit.value) return
    // 契约无 certificateDetail 端点，编辑回填由列表页传参承载；
    // 个别字段缺失就留空，用户补填后整体重新提交
    form.name = safeDecode(options?.name)
    form.type = safeDecode(options?.type)
    form.sn = safeDecode(options?.sn)
    imageList.value = safeDecode(options?.image) ? [safeDecode(options?.image)] : []
    form.issue_org = safeDecode(options?.issue_org)
    form.issue_date = safeDecode(options?.issue_date).slice(0, 10)
    form.expire_date = safeDecode(options?.expire_date).slice(0, 10)
    // 传参异常（字段全空）按业务错误走空态兜底，不给用户一张空表单猜
    if (!form.name && !form.type && !form.sn && !imageList.value.length) {
        rawError.value = { code: -1, message: '证书数据加载不完整，请返回列表重新进入' }
    }
})
</script>

<style lang="scss" scoped>
.ce {
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

/* 删除区 */
.danger-zone {
    margin-top: var(--sp-2);
}

.bar__note {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}
</style>
