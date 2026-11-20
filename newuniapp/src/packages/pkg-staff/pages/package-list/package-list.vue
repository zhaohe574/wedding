<template>
    <page-shell title="我的套餐" bg="page" :show-back="true">
        <view class="page-wrap pkg">
            <view class="tabs page-pad">
                <chip-group
                    :items="TABS.map((t) => ({ label: t.label, value: t.key }))"
                    :model-value="active"
                    @change="switchTab"
                />
            </view>

            <view v-if="phase === 'loading' || phase === 'idle'" class="page-pad">
                <base-skeleton :rows="3" />
            </view>

            <base-empty
                v-else-if="phase === 'error'"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="reload"
            />

            <base-empty
                v-else-if="phase === 'empty'"
                status="empty"
                empty-text="还没有配置套餐"
                desc="套餐由后台配置，上架后家长在下单页就能选到"
                fixed
            />

            <view v-else class="page-pad list">
                <base-card v-for="item in list" :key="item.id" compact>
                    <view class="item">
                        <view class="item__body">
                            <view class="item__line">
                                <text class="item__name ellipsis">{{
                                    item.name || '未命名套餐'
                                }}</text>
                                <status-tag
                                    :label="item.is_show ? '已上架' : '已下架'"
                                    :tone="item.is_show ? 'success' : 'neutral'"
                                    size="sm"
                                    :dot="false"
                                />
                            </view>
                            <text v-if="item.description" class="item__desc ellipsis-2">
                                {{ item.description }}
                            </text>
                            <view class="item__foot">
                                <price
                                    size="md"
                                    :content="item.price"
                                    color="var(--c-brand-text)"
                                />
                                <text v-if="item.original_price" class="item__origin">
                                    原价 ¥{{ item.original_price }}
                                </text>
                                <text v-if="item.duration" class="item__duration">
                                    {{ item.duration }} 分钟
                                </text>
                            </view>
                        </view>
                    </view>
                </base-card>

                <load-more :status="loadMoreStatus" @retry="loadMore" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/** 服务人员：我的套餐（只读）。价格与上下架由后台控制，避免线上改价。 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom } from '@dcloudio/uni-app'
import { getStaffPackageList } from '@/api/staff-center'
import { usePagedList } from '@/composables/usePagedList'
import { resolveErrorView } from '@/utils/resolveErrorView'

const TABS = [
    { key: '', label: '全部' },
    { key: 1, label: '已上架' },
    { key: 0, label: '已下架' }
]

const active = ref<string | number>('')
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const query = computed(() => ({
    is_show: active.value === '' ? '' : Number(active.value)
}))

const { list, phase, loadMoreStatus, reload, loadMore, refresh } = usePagedList<any>({
    fetcher: (q) => getStaffPackageList(q as any),
    query,
    onError: (e) => {
        rawError.value = e
    }
})

function switchTab(key: string | number) {
    active.value = key
    reload()
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
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

onLoad(() => reload())

onReachBottom(() => loadMore())

onPullDownRefresh(async () => {
    try {
        await refresh()
    } finally {
        uni.stopPullDownRefresh()
    }
})
</script>

<style lang="scss" scoped>
.pkg {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.tabs {
    padding-top: var(--sp-2);
    padding-bottom: var(--sp-2);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.item {
    &__line {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__foot {
        display: flex;
        align-items: baseline;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }

    &__origin {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-decoration: line-through;
    }

    &__duration {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
