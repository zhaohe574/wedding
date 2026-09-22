<template>
    <el-dialog v-model="dialogVisible" title="档期确认海报" width="980px" destroy-on-close>
        <div class="confirm-letter-panel">
            <div class="confirm-letter-panel__toolbar">
                <div>
                    <div class="confirm-letter-panel__title">
                        {{ orderData?.order_sn ? `订单：${orderData.order_sn}` : '选择订单后生成海报' }}
                    </div>
                    <div class="confirm-letter-panel__desc">
                        后台可代服务人员生成朋友圈档期确认海报，不会推送给客户。
                    </div>
                </div>
                <div class="confirm-letter-panel__actions">
                    <el-select
                        v-model="formData.staff_id"
                        class="confirm-letter-panel__select"
                        placeholder="选择服务人员"
                        :disabled="!staffOptions.length"
                        @change="handleStaffChange"
                    >
                        <el-option
                            v-for="staff in staffOptions"
                            :key="staff.staff_id"
                            :label="`${staff.staff_name}｜${staff.service_name || staff.item_type_desc || '服务项'}`"
                            :value="staff.staff_id"
                        />
                    </el-select>
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
                        :disabled="!canGenerate"
                        @click="submitGenerate"
                    >
                        生成海报
                    </el-button>
                </div>
            </div>
            <el-empty
                v-if="!staffOptions.length"
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
                            <span>{{ currentPoster.confirm_date || '-' }}</span>
                        </div>
                        <el-image
                            v-if="currentPoster.full_image_url"
                            :src="currentPoster.full_image_url"
                            fit="contain"
                            class="confirm-letter-preview__image"
                            :preview-src-list="[currentPoster.full_image_url]"
                        />
                        <div v-else class="service-project-empty">
                            已生成记录，但图片暂未落盘，请重新生成图片。
                        </div>
                        <div class="confirm-letter-preview__buttons">
                            <el-button
                                v-if="currentPoster.full_image_url"
                                type="primary"
                                link
                                @click="openImage(currentPoster.full_image_url)"
                            >
                                打开图片
                            </el-button>
                            <el-button
                                type="primary"
                                link
                                :loading="assetSaving"
                                @click="regenerateAssets(currentPoster)"
                            >
                                重新生成图片
                            </el-button>
                        </div>
                    </div>
                    <div v-else class="service-project-empty">
                        选择服务人员和模板后，点击生成海报。
                    </div>
                </div>
                <div class="confirm-letter-panel__history">
                    <div class="confirm-letter-panel__section-title">历史海报</div>
                    <el-table :data="historyRows" size="small" border>
                        <el-table-column label="版本" width="72">
                            <template #default="{ row }">第 {{ row.version }} 版</template>
                        </el-table-column>
                        <el-table-column label="模板" min-width="120">
                            <template #default="{ row }">{{ row.config_name || '历史配置' }}</template>
                        </el-table-column>
                        <el-table-column label="状态" width="80">
                            <template #default="{ row }">
                                <el-tag size="small" :type="Number(row.is_current || 0) === 1 ? 'success' : 'info'">
                                    {{ Number(row.is_current || 0) === 1 ? '当前' : '历史' }}
                                </el-tag>
                            </template>
                        </el-table-column>
                        <el-table-column label="操作" width="86">
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

<script lang="ts" setup>
import { computed, reactive, ref, watch } from 'vue'
import {
    orderConfirmLetterAssets,
    orderConfirmLetterDetail,
    orderConfirmLetterGenerate,
    orderConfirmLetterHistory
} from '@/api/order'
import feedback from '@/utils/feedback'

const props = defineProps<{
    modelValue: boolean
    orderData: any
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', val: boolean): void
}>()

const dialogVisible = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val)
})

const formData = reactive({
    staff_id: undefined as number | undefined,
    config_id: undefined as number | undefined
})

const currentPoster = ref<any>(null)
const historyRows = ref<any[]>([])
const generating = ref(false)
const assetSaving = ref(false)

const staffOptions = computed<any[]>(() => {
    const candidates = props.orderData?.schedule_confirm_letter?.candidates
    return Array.isArray(candidates) ? candidates : []
})

const selectedStaff = computed<any>(() =>
    staffOptions.value.find((item: any) => Number(item.staff_id || 0) === Number(formData.staff_id || 0)) || null
)

const templateOptions = computed<any[]>(() => {
    const versions = selectedStaff.value?.versions
    return Array.isArray(versions) ? versions : []
})

const canGenerate = computed(() =>
    !!props.orderData?.id &&
    Number(formData.staff_id || 0) > 0 &&
    Number(formData.config_id || 0) > 0
)

const resolveDefaultConfigId = (staff: any) => {
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

const loadHistory = async () => {
    const orderId = Number(props.orderData?.id || 0)
    const staffId = Number(formData.staff_id || 0)
    if (!orderId || !staffId) {
        historyRows.value = []
        currentPoster.value = null
        return
    }
    const rows = await orderConfirmLetterHistory({ id: orderId, staff_id: staffId })
    historyRows.value = Array.isArray(rows) ? rows : []
    const current = historyRows.value.find((row: any) => Number(row.is_current || 0) === 1) || historyRows.value[0] || null
    if (current?.letter_id) {
        await loadDetail(current)
    } else {
        currentPoster.value = null
    }
}

const initState = async () => {
    resetState()
    const candidates = staffOptions.value
    if (!candidates.length) return
    const defaultStaffId = Number(props.orderData?.schedule_confirm_letter?.default_staff_id || candidates[0]?.staff_id || 0)
    const staff = candidates.find((item: any) => Number(item.staff_id || 0) === defaultStaffId) || candidates[0]
    formData.staff_id = Number(staff?.staff_id || 0) || undefined
    formData.config_id = resolveDefaultConfigId(staff) || undefined
    await loadHistory()
}

watch(
    () => props.modelValue,
    (val) => {
        if (val && props.orderData) {
            initState()
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
        const data = await orderConfirmLetterGenerate({
            id: Number(props.orderData?.id || 0),
            staff_id: Number(formData.staff_id || 0),
            config_id: Number(formData.config_id || 0)
        })
        currentPoster.value = data
        await loadHistory()
        feedback.msgSuccess('档期确认海报已生成')
    } finally {
        generating.value = false
    }
}

const loadDetail = async (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    const staffId = Number(row?.staff_id || formData.staff_id || 0)
    if (!letterId || !staffId) return
    currentPoster.value = await orderConfirmLetterDetail({
        letter_id: letterId,
        staff_id: staffId
    })
}

const regenerateAssets = async (row: any) => {
    const letterId = Number(row?.letter_id || 0)
    const staffId = Number(row?.staff_id || formData.staff_id || 0)
    if (!letterId || !staffId) return
    assetSaving.value = true
    try {
        await orderConfirmLetterAssets({
            letter_id: letterId,
            staff_id: staffId,
            snapshot_hash: String(row?.snapshot_hash || '')
        })
        await loadDetail(row)
        await loadHistory()
        feedback.msgSuccess('海报图片已重新生成')
    } finally {
        assetSaving.value = false
    }
}

const openImage = (url: string) => {
    if (!url) return
    window.open(url, '_blank')
}
</script>

<style lang="scss" scoped>
.confirm-letter-panel {
    display: flex;
    flex-direction: column;
    gap: 16px;

    &__toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    &__title {
        font-size: 16px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__desc {
        margin-top: 4px;
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }

    &__actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    &__select {
        width: 200px;
    }

    &__content {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
        gap: 16px;
    }

    &__preview,
    &__history {
        border: 1px solid var(--el-border-color-lighter);
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
        border: 1px solid var(--el-border-color-extra-light);
    }

    &__buttons {
        display: flex;
        gap: 12px;
    }
}

.service-project-empty {
    padding: 32px 16px;
    text-align: center;
    color: var(--el-text-color-secondary);
    font-size: 13px;
}
</style>
