<template>
    <el-drawer v-model="visible" title="档期确认函详情" size="640px" destroy-on-close>
        <div v-loading="loadingDetail" class="poster-record-detail">
            <div class="poster-record-detail__preview">
                <el-image
                    v-if="displayImageUrl"
                    :src="displayImageUrl"
                    fit="contain"
                    class="poster-record-detail__image"
                    :preview-src-list="[displayImageUrl]"
                    preview-teleported
                >
                    <template #error>
                        <div class="poster-record-detail__empty">海报图片暂未落盘</div>
                    </template>
                </el-image>
                <div v-else class="poster-record-detail__empty">暂无海报图片</div>

                <div class="poster-record-detail__buttons">
                    <el-button
                        v-if="displayImageUrl"
                        type="primary"
                        link
                        @click="openImage"
                    >
                        新窗口打开
                    </el-button>
                    <el-button
                        v-if="displayImageUrl"
                        type="primary"
                        link
                        @click="downloadImage"
                    >
                        下载原图
                    </el-button>
                    <el-tooltip
                        :disabled="canRebuild"
                        :content="rebuildTooltip"
                        placement="top"
                    >
                        <span>
                            <el-button
                                type="warning"
                                link
                                :disabled="!canRebuild"
                                :loading="rebuilding"
                                @click="handleRebuild"
                            >
                                重建图片
                            </el-button>
                        </span>
                    </el-tooltip>
                </div>
            </div>

            <el-descriptions class="mt-4" :column="2" border>
                <el-descriptions-item label="服务日期">
                    {{ displayRecord?.service_date_label || displayRecord?.confirm_date || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="脱敏客户">
                    {{ displayRecord?.customer_alias || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="服务项目">
                    {{ displayRecord?.service_name || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="服务城市">
                    {{ displayRecord?.city_label || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="服务人员">
                    {{ displayRecord?.staff_name || '-' }}
                </el-descriptions-item>
                <el-descriptions-item label="模板名称与版本">
                    {{ displayRecord?.config_name || '历史配置' }}
                    <el-tag class="ml-1" size="small" effect="plain" type="info">
                        V{{ displayRecord?.config_template_version || 1 }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="生成来源">
                    {{ generateSourceLabel }}
                </el-descriptions-item>
                <el-descriptions-item label="状态">
                    <el-tag
                        size="small"
                        effect="plain"
                        :type="Number(displayRecord?.is_outdated || 0) === 1 ? 'info' : 'success'"
                    >
                        {{ Number(displayRecord?.is_outdated || 0) === 1 ? '已失效' : '当前有效' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="业务来源">
                    {{
                        displayRecord?.source === 'manual'
                            ? `线下档期 #${displayRecord?.manual_schedule_id || '-'}`
                            : displayRecord?.order_sn || `平台订单 #${displayRecord?.order_id || '-'}`
                    }}
                </el-descriptions-item>
                <el-descriptions-item label="出图版本">
                    第 {{ displayRecord?.version || 1 }} 版
                </el-descriptions-item>
                <el-descriptions-item label="快照哈希" :span="2">
                    <span class="poster-record-detail__hash">{{ displayRecord?.snapshot_hash || '-' }}</span>
                </el-descriptions-item>
                <el-descriptions-item label="生成时间" :span="2">
                    {{ displayRecord?.create_time_text || displayRecord?.confirm_date || '-' }}
                </el-descriptions-item>
            </el-descriptions>
        </div>
    </el-drawer>
</template>

<script lang="ts" setup name="PosterRecordDetailDrawer">
/**
 * 出图记录详情抽屉：高清海报、快照变量与来源/哈希信息。
 *
 * 运营 Hub 传 record 即可（默认走运营重建接口，线下档期仅供审计）；
 * 服务人员工作台以 staffMode 打开，通过 fetchDetailApi 补全详情，
 * 重建走 myScheduleConfirmLetterAssets（letter_id + snapshot_hash，不带 staff_id）。
 */
import { orderConfirmLetterAssets } from '@/api/order'
import feedback from '@/utils/feedback'

type ApiFn = (_params: any) => Promise<any>

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        record: any
        staffMode?: boolean
        fetchDetailApi?: ApiFn
        regenerateAssetsApi?: ApiFn
    }>(),
    {
        staffMode: false,
        regenerateAssetsApi: orderConfirmLetterAssets
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'regenerated'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const rebuilding = ref(false)
const loadingDetail = ref(false)
const cacheKey = ref('')
const detailRecord = ref<any>(null)

/** 列表摘要字段优先：详情接口可能只返回快照子集，不能被它覆盖丢失。 */
const mergeRecord = (detail: any, summary: any) => {
    const merged: Record<string, any> = { ...(detail || {}) }
    Object.keys(summary || {}).forEach((key) => {
        const value = summary[key]
        if (value !== undefined && value !== null && value !== '') {
            merged[key] = value
        }
    })
    return merged
}

const displayRecord = computed(() => {
    const base = detailRecord.value
        ? mergeRecord(detailRecord.value, props.record)
        : { ...(props.record || {}) }
    const snapshot =
        base?.rendered_snapshot && typeof base.rendered_snapshot === 'object'
            ? base.rendered_snapshot
            : {}
    return {
        ...base,
        service_date_label: base?.service_date_label || snapshot.service_date_label || '',
        customer_alias: base?.customer_alias || snapshot.customer_alias || '',
        service_name: base?.service_name || snapshot.service_name || '',
        city_label: base?.city_label || snapshot.city_label || '',
        staff_name: base?.staff_name || snapshot.staff_name || ''
    }
})

const isPlatformRecord = computed(
    () =>
        displayRecord.value?.source === 'platform' &&
        Number(displayRecord.value?.order_id || 0) > 0
)

const canRebuild = computed(() =>
    props.staffMode
        ? Number(displayRecord.value?.letter_id || 0) > 0
        : isPlatformRecord.value
)

const rebuildTooltip = computed(() =>
    props.staffMode
        ? '暂无可用记录标识'
        : '线下档期确认函请在服务人员工作台重建，后台审计仅支持详情与下载'
)

const baseImageUrl = computed(
    () => displayRecord.value?.full_image_url || displayRecord.value?.thumb_image_url || ''
)

const displayImageUrl = computed(() => {
    if (!baseImageUrl.value) {
        return ''
    }
    if (!cacheKey.value) {
        return baseImageUrl.value
    }
    return `${baseImageUrl.value}${baseImageUrl.value.includes('?') ? '&' : '?'}t=${cacheKey.value}`
})

const generateSourceLabel = computed(() => {
    if (displayRecord.value?.generate_source === 'admin') {
        return '后台代生成'
    }
    if (displayRecord.value?.generate_source === 'staff') {
        return '服务人员'
    }
    return '未知来源'
})

const loadDetail = async () => {
    const api = props.fetchDetailApi
    const letterId = Number(props.record?.letter_id || 0)
    if (!props.modelValue || !api || letterId <= 0) {
        return
    }
    loadingDetail.value = true
    try {
        const detail = await api(
            props.staffMode
                ? { letter_id: letterId }
                : { letter_id: letterId, staff_id: Number(props.record?.staff_id || 0) }
        )
        detailRecord.value = detail || null
    } catch {
        detailRecord.value = null
    } finally {
        loadingDetail.value = false
    }
}

watch(
    () => [props.modelValue, props.record?.letter_id] as const,
    ([isOpen]) => {
        if (isOpen) {
            void loadDetail()
            return
        }
        detailRecord.value = null
        cacheKey.value = ''
    },
    { immediate: true }
)

const openImage = () => {
    if (baseImageUrl.value) {
        window.open(baseImageUrl.value, '_blank')
    }
}

const downloadImage = () => {
    if (baseImageUrl.value) {
        window.open(baseImageUrl.value, '_blank')
    }
}

const handleRebuild = async () => {
    if (!canRebuild.value) {
        return
    }
    const letterId = Number(displayRecord.value?.letter_id || 0)
    if (letterId <= 0) {
        return
    }
    rebuilding.value = true
    try {
        await props.regenerateAssetsApi(
            props.staffMode
                ? {
                      letter_id: letterId,
                      snapshot_hash: String(displayRecord.value?.snapshot_hash || '')
                  }
                : {
                      letter_id: letterId,
                      staff_id: Number(displayRecord.value?.staff_id || 0),
                      snapshot_hash: String(displayRecord.value?.snapshot_hash || '')
                  }
        )
        cacheKey.value = String(Date.now())
        if (props.fetchDetailApi) {
            await loadDetail()
        }
        feedback.msgSuccess('海报图片已重新生成')
        emit('regenerated')
    } finally {
        rebuilding.value = false
    }
}
</script>

<style scoped lang="scss">
.poster-record-detail {
    &__preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        padding: 16px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-fill-color-lighter);
    }

    &__image {
        max-width: 100%;
        max-height: 520px;
        border-radius: 6px;
        border: 1px solid var(--el-border-color-light);
    }

    &__empty {
        display: grid;
        place-items: center;
        width: 100%;
        height: 220px;
        color: var(--el-text-color-secondary);
        font-size: 13px;
    }

    &__buttons {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    &__hash {
        word-break: break-all;
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }
}
</style>
