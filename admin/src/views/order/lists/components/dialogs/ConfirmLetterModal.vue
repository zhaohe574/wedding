<template>
    <el-dialog v-model="dialogVisible" title="档期确认海报" width="980px" destroy-on-close>
        <div class="confirm-letter-panel">
            <div class="confirm-letter-panel__order">
                <div class="confirm-letter-panel__order-main">
                    <span class="confirm-letter-panel__order-sn">
                        {{ orderData?.order_sn || '未获取到订单号' }}
                    </span>
                    <el-tag v-if="orderStatusDesc" :type="orderStatusTagType" size="small" effect="plain">
                        {{ orderStatusDesc }}
                    </el-tag>
                </div>
                <div class="confirm-letter-panel__order-meta">
                    <span>服务日期 {{ orderData?.service_date || '-' }}</span>
                    <span class="confirm-letter-panel__dot">·</span>
                    <span>{{ staffMode ? '服务人员自助生成，不会推送给客户' : '后台可代服务人员生成朋友圈档期确认海报，不会推送给客户' }}</span>
                </div>
            </div>

            <el-alert
                v-if="statusBlockedHint"
                type="warning"
                :closable="false"
                show-icon
                :title="statusBlockedHint"
            />

            <div class="confirm-letter-panel__toolbar">
                <div class="confirm-letter-panel__actions">
                    <template v-if="!staffMode && staffOptions.length">
                        <el-select
                            v-model="formData.staff_id"
                            class="confirm-letter-panel__select"
                            placeholder="选择服务人员"
                            :disabled="staffOptions.length <= 1"
                            @change="handleStaffChange"
                        >
                            <el-option
                                v-for="staff in staffOptions"
                                :key="staff.staff_id"
                                :label="`${staff.staff_name}｜${staff.service_name || staff.item_type_desc || '服务项'}`"
                                :value="staff.staff_id"
                            />
                        </el-select>
                    </template>
                    <span v-else-if="staffMode && currentStaffName" class="confirm-letter-panel__staff">
                        服务人员：{{ currentStaffName }}
                    </span>
                    <el-select
                        v-model="formData.config_id"
                        class="confirm-letter-panel__select"
                        placeholder="选择海报模板"
                        :disabled="!templateOptions.length"
                    >
                        <el-option
                            v-for="config in templateOptions"
                            :key="config.config_id"
                            :label="`${config.template_name || '未命名模板'}${Number(config.is_default || 0) === 1 ? '（默认）' : ''}`"
                            :value="config.config_id"
                        />
                    </el-select>
                    <el-button
                        type="primary"
                        :loading="generating"
                        :disabled="!canGenerate || !!statusBlockedHint"
                        @click="submitGenerate"
                    >
                        生成海报
                    </el-button>
                </div>
                <el-button v-if="staffMode" link type="primary" @click="goMyTemplates">
                    我的海报模板
                </el-button>
                <el-button v-else link type="primary" @click="goPosterSettings">
                    二维码与字体检修
                </el-button>
            </div>

            <el-empty
                v-if="!staffOptions.length && !staffMode"
                description="当前订单暂无可生成海报的服务人员"
                :image-size="80"
            />
            <div v-else class="confirm-letter-panel__content">
                <div class="confirm-letter-panel__preview">
                    <div class="confirm-letter-panel__section-title">当前海报</div>
                    <div v-if="currentPoster" class="confirm-letter-preview">
                        <div class="confirm-letter-preview__meta">
                            <el-tag type="success">第 {{ currentPoster.version }} 版</el-tag>
                            <el-tag type="info">{{ currentPoster.config_name || '历史配置' }}</el-tag>
                            <el-tag v-if="Number(currentPoster.is_outdated || 0) === 1" type="warning">
                                已失效 · 建议重生成
                            </el-tag>
                            <span>{{ currentPoster.confirm_date || '-' }}</span>
                        </div>
                        <el-image
                            v-if="currentPoster.full_image_url"
                            :src="currentPoster.full_image_url"
                            fit="contain"
                            class="confirm-letter-preview__image"
                            :preview-src-list="[currentPoster.full_image_url]"
                            preview-teleported
                        />
                        <div v-else class="confirm-letter-preview__empty">
                            已生成记录，但图片暂未落盘，请尝试重建图片。
                        </div>
                        <div class="confirm-letter-preview__buttons">
                            <el-button
                                v-if="currentPoster.full_image_url"
                                type="primary"
                                link
                                @click="downloadImage(currentPoster.full_image_url)"
                            >
                                下载原图
                            </el-button>
                            <el-button
                                v-if="currentPoster.full_image_url"
                                type="primary"
                                link
                                @click="openImage(currentPoster.full_image_url)"
                            >
                                新窗口打开
                            </el-button>
                            <el-button
                                type="warning"
                                link
                                :loading="assetSaving"
                                @click="regenerateAssets(currentPoster)"
                            >
                                重建图片
                            </el-button>
                        </div>
                    </div>
                    <div v-else class="confirm-letter-preview__empty">
                        {{ emptyPreviewHint }}
                    </div>
                </div>
                <div class="confirm-letter-panel__history">
                    <div class="confirm-letter-panel__section-title">历史版本</div>
                    <el-table :data="historyRows" size="small" border>
                        <el-table-column label="版本" width="70">
                            <template #default="{ row }">第 {{ row.version }} 版</template>
                        </el-table-column>
                        <el-table-column label="模板" min-width="110">
                            <template #default="{ row }">{{ row.config_name || '历史配置' }}</template>
                        </el-table-column>
                        <el-table-column label="状态" width="76">
                            <template #default="{ row }">
                                <el-tag size="small" :type="Number(row.is_current || 0) === 1 ? 'success' : 'info'">
                                    {{ Number(row.is_current || 0) === 1 ? '当前' : '历史' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="生成时间" width="150">
                            <template #default="{ row }">
                                {{ row.create_time || row.confirm_date || '-' }}
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="76">
                            <template #default="{ row }">
                                <el-button type="primary" link @click="loadDetail(row)">查看</el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>
            </div>
        </div>
    </el-dialog>
</template>

<script lang="ts" setup name="ConfirmLetterModal">
/**
 * 档期确认海报弹窗：运营订单与按需的服务人员自助流程共用。
 *
 * staffMode=false：运营接口 `/ops.order/confirmLetter*`，历史用 id + staff_id；
 * staffMode=true ：服务人员接口 `/ops.staff/myScheduleConfirmLetter*`，历史/生成用 order_id，
 * 详情/重建用 letter_id（不带 staff_id），避免参数结构混用。
 */
import {
    orderConfirmLetterAssets,
    orderConfirmLetterDetail,
    orderConfirmLetterGenerate,
    orderConfirmLetterHistory
} from '@/api/order'
import { myScheduleConfirmLetterConfig } from '@/api/staff-center'
import { getOrderStatusTagType, getOrderStatusText } from '@/enums/orderEnums'
import { getMenuPath, getRoutePath } from '@/router'
import feedback from '@/utils/feedback'

type ApiFn = (_params: any) => Promise<any>

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        orderData: any
        fetchHistoryApi?: ApiFn
        generateApi?: ApiFn
        fetchDetailApi?: ApiFn
        regenerateAssetsApi?: ApiFn
        staffMode?: boolean
    }>(),
    {
        staffMode: false,
        fetchHistoryApi: orderConfirmLetterHistory,
        generateApi: orderConfirmLetterGenerate,
        fetchDetailApi: orderConfirmLetterDetail,
        regenerateAssetsApi: orderConfirmLetterAssets
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'changed'): void
}>()

const dialogVisible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const formData = reactive({
    staff_id: undefined as number | undefined,
    config_id: undefined as number | undefined
})

const currentPoster = ref<any>(null)
const historyRows = ref<any[]>([])
const generating = ref(false)
const assetSaving = ref(false)
const fallbackVersions = ref<any[]>([])
const fallbackConfigId = ref(0)

const router = useRouter()

const orderId = computed(() => Number(props.orderData?.id || props.orderData?.order_id || 0))

const orderStatusDesc = computed(
    () => props.orderData?.order_status_desc || getOrderStatusText(props.orderData?.order_status, '')
)

const orderStatusTagType = computed(() => getOrderStatusTagType(props.orderData?.order_status))

const statusBlockedHint = computed(() => {
    const status = Number(props.orderData?.order_status ?? -1)
    if (status === 7) {
        return '订单已暂停，暂停期间无法生成档期确认函；请先恢复订单后再试。'
    }
    if ([6, 8, 9].includes(status)) {
        return '订单已取消、退款或删除，无法生成档期确认函。'
    }
    if ([0, 1].includes(status)) {
        return '订单尚未确认或支付锁档，暂不能生成已预定档期确认函。'
    }
    return ''
})

const staffOptions = computed<any[]>(() => {
    const candidates = props.orderData?.schedule_confirm_letter?.candidates
    if (Array.isArray(candidates) && candidates.length) {
        return candidates
    }
    if (props.staffMode) {
        return [
            {
                staff_id: 0,
                staff_name: props.orderData?.staff_name || '当前服务人员',
                service_name: props.orderData?.service_name || '本人档期',
                versions: fallbackVersions.value,
                default_config_id: fallbackConfigId.value
            }
        ]
    }
    return []
})

const selectedStaff = computed<any>(() => {
    if (props.staffMode) {
        return staffOptions.value[0] || null
    }
    return (
        staffOptions.value.find(
            (item: any) => Number(item.staff_id || 0) === Number(formData.staff_id || 0)
        ) || null
    )
})

const currentStaffName = computed(() => selectedStaff.value?.staff_name || '')

const templateOptions = computed<any[]>(() => {
    const versions = selectedStaff.value?.versions
    if (Array.isArray(versions) && versions.length) {
        return versions
    }
    return props.staffMode ? fallbackVersions.value : []
})

const canGenerate = computed(() => {
    if (!orderId.value || Number(formData.config_id || 0) <= 0) {
        return false
    }
    return props.staffMode || Number(formData.staff_id || 0) > 0
})

const emptyPreviewHint = computed(() => {
    if (statusBlockedHint.value) {
        return statusBlockedHint.value
    }
    if (!templateOptions.value.length) {
        return props.staffMode
            ? '当前账号尚未启用海报模板，请先在服务人员工作台配置模板。'
            : '所选服务人员尚未启用海报模板，请先在设计模板中保存并启用。'
    }
    return '选择服务人员和模板后，点击生成海报。'
})

const resolveDefaultConfigId = (staff: any) => {
    if (Number(staff?.default_config_id || 0) > 0) {
        return Number(staff.default_config_id)
    }
    const versions = Array.isArray(staff?.versions) ? staff.versions : []
    const defaultConfig = versions.find((item: any) => Number(item.is_default || 0) === 1)
    return Number(defaultConfig?.config_id || versions[0]?.config_id || 0)
}

const resetState = () => {
    formData.staff_id = undefined
    formData.config_id = undefined
    currentPoster.value = null
    historyRows.value = []
}

/** 服务人员订单详情不返回 candidates，需回落到我的模板配置。 */
const loadStaffTemplateFallback = async () => {
    if (!props.staffMode) {
        return
    }
    if (staffOptions.value.some((item: any) => Array.isArray(item.versions) && item.versions.length)) {
        return
    }
    try {
        const data: any = await myScheduleConfirmLetterConfig()
        fallbackVersions.value = Array.isArray(data?.versions) ? data.versions : []
        fallbackConfigId.value = Number(data?.config_id || 0)
        if (!formData.config_id) {
            formData.config_id = fallbackConfigId.value || Number(fallbackVersions.value[0]?.config_id || 0) || undefined
        }
    } catch {
        fallbackVersions.value = []
    }
}

const buildHistoryParams = () => {
    const staffId = Number(formData.staff_id || 0)
    return props.staffMode ? { order_id: orderId.value } : { id: orderId.value, staff_id: staffId }
}

const buildGenerateParams = () => {
    const configId = Number(formData.config_id || 0)
    return props.staffMode
        ? { order_id: orderId.value, config_id: configId }
        : { id: orderId.value, staff_id: Number(formData.staff_id || 0), config_id: configId }
}

const buildDetailParams = (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    return props.staffMode
        ? { letter_id: letterId }
        : { letter_id: letterId, staff_id: Number(row?.staff_id || formData.staff_id || 0) }
}

const buildAssetsParams = (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    const snapshotHash = String(row?.snapshot_hash || '')
    return props.staffMode
        ? { letter_id: letterId, snapshot_hash: snapshotHash }
        : {
              letter_id: letterId,
              staff_id: Number(row?.staff_id || formData.staff_id || 0),
              snapshot_hash: snapshotHash
          }
}

const loadHistory = async () => {
    if (!orderId.value) {
        historyRows.value = []
        currentPoster.value = null
        return
    }
    if (!props.staffMode && !Number(formData.staff_id || 0)) {
        historyRows.value = []
        currentPoster.value = null
        return
    }
    const rows = await props.fetchHistoryApi(buildHistoryParams())
    historyRows.value = Array.isArray(rows) ? rows : []
    const current =
        historyRows.value.find((row: any) => Number(row.is_current || 0) === 1) ||
        historyRows.value[0] ||
        null
    if (current?.letter_id) {
        await loadDetail(current)
    } else {
        currentPoster.value = null
    }
}

const initState = async () => {
    resetState()
    await loadStaffTemplateFallback()
    const candidates = staffOptions.value
    if (!candidates.length) {
        return
    }
    if (props.staffMode) {
        formData.staff_id = Number(candidates[0]?.staff_id || 0) || undefined
        formData.config_id =
            Number(formData.config_id || 0) ||
            resolveDefaultConfigId(candidates[0]) ||
            fallbackConfigId.value ||
            undefined
        await loadHistory()
        return
    }
    const defaultStaffId = Number(
        props.orderData?.schedule_confirm_letter?.default_staff_id || candidates[0]?.staff_id || 0
    )
    const staff =
        candidates.find((item: any) => Number(item.staff_id || 0) === defaultStaffId) || candidates[0]
    formData.staff_id = Number(staff?.staff_id || 0) || undefined
    formData.config_id = resolveDefaultConfigId(staff) || undefined
    await loadHistory()
}

watch(
    () => props.modelValue,
    (value) => {
        if (value) {
            initState().catch(() => {
                // 加载失败已由 request 层提示，弹窗保持空态可重试
            })
        }
    }
)

const handleStaffChange = async () => {
    currentPoster.value = null
    historyRows.value = []
    formData.config_id = resolveDefaultConfigId(selectedStaff.value) || undefined
    await loadHistory()
}

const submitGenerate = async () => {
    if (!canGenerate.value) {
        feedback.msgError('请选择服务人员和海报模板')
        return
    }
    generating.value = true
    try {
        const data = await props.generateApi(buildGenerateParams())
        currentPoster.value = data
        await loadHistory()
        feedback.msgSuccess('档期确认海报已生成')
        emit('changed')
    } finally {
        generating.value = false
    }
}

const loadDetail = async (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    if (!letterId) {
        return
    }
    currentPoster.value = await props.fetchDetailApi(buildDetailParams(row))
}

const regenerateAssets = async (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    if (!letterId) {
        return
    }
    assetSaving.value = true
    try {
        await props.regenerateAssetsApi(buildAssetsParams(row))
        await loadDetail(row)
        await loadHistory()
        feedback.msgSuccess('海报图片已重新生成')
        emit('changed')
    } finally {
        assetSaving.value = false
    }
}

const openImage = (url: string) => {
    if (url) {
        window.open(url, '_blank')
    }
}

const downloadImage = (url: string) => {
    if (url) {
        window.open(url, '_blank')
    }
}

const goPosterSettings = () => {
    router.push({
        path: getMenuPath('page.setting.website.hub') || '/setting/brand-site',
        query: { tab: 'poster' }
    })
}

/** 服务人员工作台没有二维码菜单，跳转到自己的海报模板版本页。 */
const myTemplatesPath =
    getMenuPath('ops.staff/myScheduleConfirmLetterConfig') ||
    getRoutePath('ops.staff/myScheduleConfirmLetterConfig') ||
    '/staff-center/schedule-confirm-letter'

const goMyTemplates = () => {
    router.push({ path: myTemplatesPath })
}
</script>

<style lang="scss" scoped>
.confirm-letter-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;

    &__order {
        padding: 12px 14px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-fill-color-lighter);
    }

    &__order-main {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    &__order-sn {
        font-size: 15px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__order-meta {
        margin-top: 4px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        font-size: 12px;
        line-height: 1.6;
        color: var(--el-text-color-secondary);
    }

    &__dot {
        opacity: 0.6;
    }

    &__toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    &__actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    &__select {
        width: 200px;
    }

    &__staff {
        font-size: 13px;
        color: var(--el-text-color-regular);
    }

    &__content {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
        gap: 16px;
    }

    &__preview,
    &__history {
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        padding: 16px;
    }

    &__section-title {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 12px;
        color: var(--el-text-color-primary);
    }
}

.confirm-letter-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;

    &__meta {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }

    &__image {
        width: 100%;
        max-height: 420px;
        border-radius: 6px;
        border: 1px solid var(--el-border-color-light);
    }

    &__buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    &__empty {
        padding: 32px 16px;
        text-align: center;
        color: var(--el-text-color-secondary);
        font-size: 13px;
        line-height: 1.7;
    }
}

@media (max-width: 960px) {
    .confirm-letter-panel__content {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
