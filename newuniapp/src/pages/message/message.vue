<template>
    <view class="page-wrap msg-page">
        <view class="head">
            <view class="head__tabs">
                <view hover-class="is-pressed"
                    v-for="tab in TABS"
                    :key="tab.key"
                    class="head__tab"
                    :class="{ 'is-active': tab.key === activeKey }"
                    @tap="switchTab(tab.key)"
                >
                    <text class="head__tab-text">{{ tab.label }}</text>
                    <view v-if="tab.key === activeKey" class="head__tab-line" />
                </view>
            </view>
            <view v-if="unread.total > 0" class="head__action" @tap="handleReadAll">
                <text class="head__action-text">全部已读</text>
            </view>
        </view>

        <view v-if="firstLoading" class="page-pad">
            <base-skeleton :rows="4" />
        </view>

        <base-empty v-else-if="error" status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title" :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed @primary="onPrimary" @retry="reload" />

        <base-empty
            v-else-if="!list.length"
            status="empty" empty-icon="empty-message"
            empty-text="暂无消息"
            fixed
        />

        <view v-else class="page-pad list">
            <base-card
                v-for="item in list"
                :key="item.id"
                compact
                class="msg"
                :class="{ 'is-unread': item.is_read === 0 }"
                @tap="openItem(item)"
            >
                <view class="msg__dot" v-if="item.is_read === 0" aria-hidden="true" />
                <text v-if="item.is_read === 0" class="msg__unread">未读</text>
                <view class="msg__body">
                    <view class="msg__head">
                        <text class="msg__title ellipsis">{{ item.title }}</text>
                        <text class="msg__time">{{ item.create_time }}</text>
                    </view>
                    <text class="msg__content ellipsis-2">{{ item.content }}</text>
                </view>
            </base-card>

            <load-more :status="loadMoreStatus" @retry="fetchPage(false)" />
        </view>

        <app-tabbar current="/pages/message/message" />
        <app-toast />
    </view>
</template>

<script setup lang="ts">
/**
 * 消息中心。
 *
 * 点击消息后按 target_type 跳转，映射集中在 composables/useNotificationRouter。
 * 老端把 20+ 条映射硬编码在页面里，新增类型必漏。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import { getNotificationList, getUnreadCount, markRead, markAllRead, type NotificationItem } from '@/api/message'
import { normalizePage } from '@/request'
import { navigate } from '@/utils/link'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toast } from '@/composables/useToast'

const TABS = [
    { key: 'all', label: '全部', isRead: undefined as number | undefined },
    { key: 'unread', label: '未读', isRead: 0 }
]

const activeKey = ref('all')
const list = ref<NotificationItem[]>([])
const unread = ref({ total: 0, system: 0, order: 0, interact: 0 })
const page = ref(1)
const pageSize = 10
const hasMore = ref(true)
const loadingMore = ref(false)
const firstLoading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作 */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') { reload(); return }
    if (kind === 'reselect' || kind === 'back') { uni.navigateBack(); return }
    if (kind === 'login') { uni.navigateTo({ url: '/pages/login/login' }); return }
    // contact / 其余：保持现有客服入口，不新增跳转
}

const loadMoreFailed = ref(false)
const loadMoreStatus = computed<'idle' | 'loading' | 'nomore' | 'error'>(() => {
    if (loadMoreFailed.value) return 'error'
    if (loadingMore.value && list.value.length) return 'loading'
    if (!hasMore.value) return 'nomore'
    return 'idle'
})

const activeIsRead = () => TABS.find((t) => t.key === activeKey.value)?.isRead

async function fetchUnread() {
    try {
        const res = await getUnreadCount()
        unread.value = {
            total: Number((res as any)?.total ?? 0),
            system: Number((res as any)?.system ?? 0),
            order: Number((res as any)?.order ?? 0),
            interact: Number((res as any)?.interact ?? 0)
        }
    } catch {
        // 未读数失败不影响列表
    }
}

async function fetchPage(reset = false) {
    if (reset) {
        page.value = 1
        hasMore.value = true
        error.value = false
        loadMoreFailed.value = false
    }
    if (!hasMore.value && !reset) return

    loadingMore.value = true
    loadMoreFailed.value = false
    try {
        const res = await getNotificationList({
            page: page.value,
            page_size: pageSize,
            is_read: activeIsRead()
        })
        const paged = normalizePage<NotificationItem>(res, page.value, pageSize)
        list.value = reset ? paged.list : [...list.value, ...paged.list]
        hasMore.value = paged.hasMore
        if (hasMore.value) page.value += 1
    } catch (e) {
        rawError.value = e
        if (reset) error.value = true
        else loadMoreFailed.value = true
    } finally {
        loadingMore.value = false
        firstLoading.value = false
        uni.stopPullDownRefresh()
    }
}

function reload() {
    firstLoading.value = true
    fetchPage(true)
}

function switchTab(key: string) {
    if (key === activeKey.value) return
    activeKey.value = key
    list.value = []
    reload()
}

async function openItem(item: NotificationItem) {
    if (item.is_read === 0) {
        try {
            await markRead({ id: item.id })
            item.is_read = 1
            unread.value.total = Math.max(0, unread.value.total - 1)
        } catch {
            // 标记失败不阻断跳转
        }
    }
    // 先进详情看完整正文，详情页里再按 target_type 跳业务页
    navigate({ path: '/pages/message/detail', query: { id: item.id } })
}

async function handleReadAll() {
    try {
        await markAllRead()
        unread.value = { total: 0, system: 0, order: 0, interact: 0 }
        if (activeKey.value === 'unread') {
            await fetchPage(true)
        } else {
            list.value = list.value.map((i) => ({ ...i, is_read: 1 }))
        }
        toast.success('已全部标记为已读')
    } catch {
        // 失败提示由请求层统一处理
    }
}

onLoad(() => {
    fetchPage(true)
    fetchUnread()
})
onShow(() => fetchUnread())
onPullDownRefresh(() => {
    fetchPage(true)
    fetchUnread()
})
onReachBottom(() => fetchPage(false))
</script>

<style lang="scss" scoped>
.msg-page {
    padding-bottom: calc(100rpx + var(--safe-bottom) + var(--sp-4));
}

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--c-surface);
    padding: 0 var(--sp-4);
    position: sticky;
    top: 0;
    z-index: 10;

    &__tabs {
        display: flex;
        gap: var(--sp-5);
    }

    &__tab {
        padding: var(--sp-3) 0;
        position: relative;
    }

    &__tab-text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }

    &__tab.is-active &__tab-text {
        color: var(--c-ink);
        font-weight: 500;
    }

    &__tab-line {
        position: absolute;
        left: 50%;
        bottom: var(--sp-1);
        width: 40rpx;
        height: 4rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand);
        transform: translateX(-50%);
    }

    &__action {
        min-width: 88rpx;
        min-height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }

    &__action-text {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding-top: var(--sp-3);
}

.msg {
    display: flex;
    gap: var(--sp-2);

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: var(--r-pill);
        background: var(--c-danger);
        margin-top: 12rpx;
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-md);
        color: var(--c-ink);
        flex: 1;
        min-width: 0;
    }

    &__time {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        flex-shrink: 0;
    }

    &__content {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: var(--lh-body);
    }
}

.msg.is-unread .msg__title {
    font-weight: 500;
}

.loadmore {
    padding: var(--sp-4) 0 var(--sp-6);
    text-align: center;

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
.msg__unread {
    flex-shrink: 0;
    font-size: var(--fs-xs);
    font-weight: var(--fw-semibold);
    color: var(--c-info);
    line-height: var(--lh-none);
}
</style>
