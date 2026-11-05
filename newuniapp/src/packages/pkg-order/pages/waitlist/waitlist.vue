<template>
    <view class="page-wrap waitlist">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="3" />
        </view>

        <base-empty v-else-if="error" status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title" :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed @primary="onPrimary" @retry="load" />

        <base-empty
            v-else-if="!list.length"
            status="empty" empty-icon="empty-history"
            empty-text="还没有候补记录"
            action-text="去查档期"
            :show-action="true"
            fixed
            @action="goQuery"
        />

        <view v-else class="page-pad list">
            <base-card v-for="item in list" :key="item.id" compact>
                <view class="head">
                    <text class="head__name">{{ item.staff_name || '服务人员' }}</text>
                    <status-tag :label="notifyLabel(item)" :tone="notifyTone(item)" size="sm" />
                </view>
                <view class="kv">
                    <text class="kv__k">候补日期</text>
                    <text class="kv__v">{{ item.schedule_date || item.date || '-' }}</text>
                </view>
                <view class="kv">
                    <text class="kv__k">提交时间</text>
                    <text class="kv__v">{{ item.create_time }}</text>
                </view>
                <view v-if="canCancel(item)" class="foot">
                    <base-button text="取消候补" variant="ghost" size="sm" :block="false" @click="handleCancel(item)" />
                </view>
            </base-card>
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 我的候补：档期满了可以排队，有人退单会收到通知 */
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import {
    WAITLIST_NOTIFY_STATUS_NAME_MAP,
    WaitlistNotifyStatus,
    resolveStatusLabel
} from '@shared/contracts'
import type { Tone } from '@shared/contracts'
import { getMyWaitlist, cancelWaitlist } from '@/api/catalog'
import { navigate } from '@/utils/link'

const list = ref<any[]>([])
const loading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') { load(); return }
    if (kind === 'reselect' || kind === 'back') { uni.navigateBack(); return }
    if (kind === 'login') { uni.navigateTo({ url: '/pages/login/login' }); return }
    // contact / 其余：保持现有客服入口，不新增跳转
}

function notifyLabel(item: any) {
    const mapped = resolveStatusLabel(WAITLIST_NOTIFY_STATUS_NAME_MAP as any, Number(item.notify_status))
    return mapped.known ? mapped.label : mapped.label
}

function notifyTone(item: any): Tone {
    const s = Number(item.notify_status)
    if (s === WaitlistNotifyStatus.NOTIFIED) return 'primary'
    if (s === WaitlistNotifyStatus.ORDERED) return 'success'
    if (s === WaitlistNotifyStatus.EXPIRED) return 'neutral'
    return 'warning'
}

function canCancel(item: any) {
    const s = Number(item.notify_status)
    return s === WaitlistNotifyStatus.PENDING || s === WaitlistNotifyStatus.NOTIFIED
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getMyWaitlist()
        list.value = Array.isArray(res) ? res : (res as any)?.lists || []
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

async function handleCancel(item: any) {
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '取消候补',
            content: '取消后将不再收到该档期的通知',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    try {
        await cancelWaitlist({ id: item.id })
        list.value = list.value.filter((i) => i.id !== item.id)
        toast.info('已取消')
    } catch {
        // 请求层已提示
    }
}

function goQuery() {
    navigate({ path: '/packages/pkg-catalog/pages/query/query' })
}

onShow(() => load())
</script>

<style lang="scss" scoped>
.waitlist {
    background: var(--c-bg);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    padding-bottom: var(--sp-3);
    border-bottom: 1rpx solid var(--c-line);

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
}

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 64rpx;
    padding: var(--sp-2) 0 0;
    box-sizing: border-box;

    &__k {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: 1.5;
        color: var(--c-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }
}

.foot {
    display: flex;
    justify-content: flex-end;
    padding-top: var(--sp-3);
    border-top: 1rpx solid var(--c-line);
    margin-top: var(--sp-3);
}
</style>
