<template>
    <view class="page-wrap change-detail">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="4" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <template v-else>
            <view class="status-head">
                <view class="status-head__row">
                    <text class="status-head__title">{{ titleText }}</text>
                    <status-tag :label="statusLabel" :tone="statusTone" size="md" />
                </view>
                <text class="status-head__sub">{{ data.create_time }}</text>
            </view>

            <view class="page-pad body">
                <base-card>
                    <view class="kv">
                        <text class="kv__k">关联订单</text>
                        <text class="kv__v">{{ data.order_sn || data.order_id || '-' }}</text>
                    </view>
                    <view v-if="isPause" class="kv">
                        <text class="kv__k">暂停区间</text>
                        <text class="kv__v">{{ data.start_date }} ~ {{ data.end_date }}</text>
                    </view>
                    <view v-else-if="data.new_date" class="kv">
                        <text class="kv__k">新服务日期</text>
                        <text class="kv__v">{{ data.new_date }}</text>
                    </view>
                    <view v-else-if="data.service_date" class="kv">
                        <text class="kv__k">服务日期</text>
                        <text class="kv__v">{{ data.service_date }}</text>
                    </view>
                    <view v-if="data.add_price" class="kv">
                        <text class="kv__k">新增金额</text>
                        <text class="kv__v kv__v--brand">¥{{ money(data.add_price) }}</text>
                    </view>
                    <view v-if="data.audit_remark" class="kv">
                        <text class="kv__k">审核说明</text>
                        <text class="kv__v">{{ data.audit_remark }}</text>
                    </view>
                </base-card>

                <base-card v-if="data.reason">
                    <text class="block-title">申请原因</text>
                    <text class="block-text">{{ data.reason }}</text>
                </base-card>

                <base-card v-if="images.length">
                    <text class="block-title">附件</text>
                    <view class="images">
                        <image
                            v-for="(img, i) in images"
                            :key="i"
                            class="image__img"
                            :src="imageUrl(img)"
                            mode="aspectFill"
                            @tap="preview(i)"
                        />
                    </view>
                </base-card>

                <base-card v-if="logs.length">
                    <text class="block-title">处理记录</text>
                    <view v-for="(l, i) in logs" :key="i" class="log">
                        <view class="log__dot" />
                        <view class="log__body">
                            <text class="log__text">{{ l.content || l.remark || l.action_desc }}</text>
                            <text class="log__time">{{ l.create_time }}</text>
                        </view>
                    </view>
                </base-card>

                <view class="body__space" />
            </view>

            <view v-if="canCancel" class="action-bar">
                <base-button text="撤销申请" variant="danger" size="md" @click="handleCancel" />
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/** 变更/暂停 申请详情（两种记录共用一页，靠 type 区分） */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    ORDER_CHANGE_STATUS_NAME_MAP,
    ORDER_CHANGE_STATUS_TONE,
    ORDER_CHANGE_TYPE_NAME_MAP,
    ORDER_PAUSE_STATUS_NAME_MAP,
    ORDER_PAUSE_STATUS_TONE,
    ORDER_PAUSE_TYPE_NAME_MAP,
    OrderChangeStatus,
    OrderPauseStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getChangeDetail, getPauseDetail, cancelChange, cancelPause } from '@/api/change'
import { useAppStore } from '@/stores/app'
import { formatMoney } from '@shared/utils/format'

const appStore = useAppStore()

const id = ref(0)
const type = ref<'change' | 'pause'>('change')
const data = ref<any>({})
const loading = ref(true)
const error = ref(false)

const isPause = computed(() => type.value === 'pause')

const titleText = computed(() => {
    const map = isPause.value ? ORDER_PAUSE_TYPE_NAME_MAP : ORDER_CHANGE_TYPE_NAME_MAP
    const key = isPause.value ? data.value.pause_type : data.value.change_type
    return (map as Record<number, string>)[Number(key)] || (isPause.value ? '暂停申请' : '变更申请')
})

const statusLabel = computed(() => {
    const map = isPause.value ? ORDER_PAUSE_STATUS_NAME_MAP : ORDER_CHANGE_STATUS_NAME_MAP
    const mapped = resolveStatusLabel(map as any, Number(data.value.status))
    return mapped.known ? mapped.label : data.value.status_desc || mapped.label
})

const statusTone = computed(() =>
    isPause.value
        ? resolveTone(ORDER_PAUSE_STATUS_TONE as any, Number(data.value.status))
        : resolveTone(ORDER_CHANGE_STATUS_TONE as any, Number(data.value.status))
)

const images = computed<string[]>(() => {
    const raw = isPause.value ? data.value.proof_images : data.value.attach_images
    if (Array.isArray(raw)) return raw
    if (typeof raw === 'string' && raw) {
        try {
            const parsed = JSON.parse(raw)
            return Array.isArray(parsed) ? parsed : []
        } catch {
            return []
        }
    }
    return []
})

const logs = computed<any[]>(() => (Array.isArray(data.value.logs) ? data.value.logs : []))

const canCancel = computed(() => {
    const s = Number(data.value.status)
    return isPause.value ? s === OrderPauseStatus.PENDING : s === OrderChangeStatus.PENDING
})

function money(v: any) {
    return formatMoney(v as any)
}

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function preview(index: number) {
    uni.previewImage({ urls: images.value.map((u) => imageUrl(u)), current: index })
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = isPause.value ? await getPauseDetail({ id: id.value }) : await getChangeDetail({ id: id.value })
        if (!res) throw new Error('empty')
        data.value = res
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
}

async function handleCancel() {
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '撤销申请',
            content: '撤销后需要重新提交，确定吗？',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    try {
        if (isPause.value) await cancelPause({ id: id.value })
        else await cancelChange({ id: id.value })
        uni.showToast({ title: '已撤销', icon: 'none' })
        load()
    } catch {
        // 请求层已提示
    }
}

onLoad((options: any) => {
    id.value = Number(options?.id || 0)
    type.value = options?.type === 'pause' ? 'pause' : 'change'
    if (!id.value) {
        error.value = true
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.change-detail {
    background: var(--c-bg);
    padding-bottom: calc(164rpx + var(--safe-bottom));
}

.status-head {
    background: var(--c-ink);
    padding: var(--sp-5) var(--sp-4);

    &__row {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-3);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xl);
        font-weight: 500;
        line-height: 1.4;
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }

    &__sub {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: 1.5;
        color: var(--c-on-dark-2);
        overflow-wrap: anywhere;
    }
}

.body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    margin-top: -20rpx;
    position: relative;
    z-index: 2;
    padding-top: var(--sp-3);

    &__space {
        height: var(--sp-4);
    }
}

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 56rpx;
    padding: var(--sp-2) 0;
    box-sizing: border-box;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &__k {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: 1.6;
        color: var(--c-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }

    &__v--brand {
        color: var(--c-brand-text);
        font-weight: 500;
    }
}

.block-title {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-md);
    font-weight: 500;
    color: var(--c-ink);
}

.block-text {
    display: block;
    font-size: var(--fs-md);
    color: var(--c-ink-2);
    line-height: 1.7;
    overflow-wrap: anywhere;
}

.images {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.image__img {
    width: 160rpx;
    height: 160rpx;
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);
}

.log {
    display: flex;
    gap: var(--sp-2);
    padding: var(--sp-3) 0;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: 50%;
        background: var(--c-brand);
        margin-top: 12rpx;
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    &__time {
        font-size: var(--fs-xs);
        line-height: 1.5;
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.action-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);
    box-sizing: border-box;
}
</style>
