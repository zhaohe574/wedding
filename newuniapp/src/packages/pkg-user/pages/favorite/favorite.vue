<template>
    <view class="page-wrap fav-page">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="4" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <base-empty
            v-else-if="!list.length"
            status="empty" empty-icon="empty-favor"
            empty-text="还没有收藏的人员"
            action-text="去看看"
            :show-action="true"
            fixed
            @action="goList"
        />

        <view v-else class="page-pad cards">
            <view hover-class="is-pressed" v-for="item in list" :key="item.id" class="card" @tap="goDetail(item)">
                <image class="card__avatar" :src="imageUrl(item.avatar)" mode="aspectFill" />
                <view class="card__body">
                    <text class="card__name ellipsis">{{ item.name }}</text>
                    <view class="card__meta">
                        <text v-if="item.rating" class="card__rating">★ {{ item.rating }}</text>
                        <text v-if="item.category_name" class="card__muted">{{ item.category_name }}</text>
                    </view>
                </view>
                <view class="card__unfav" @tap.stop="remove(item)">
                    <u-icon name="star-fill" :size="40" color="var(--c-brand)" />
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/** 我的收藏（人员）。后端 myFavorites 无分页，直接返回数组。 */
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getMyFavorites, toggleStaffFavorite } from '@/api/catalog'
import { useAppStore } from '@/stores/app'
import { navigate } from '@/utils/link'

const appStore = useAppStore()

const list = ref<any[]>([])
const loading = ref(true)
const error = ref(false)

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getMyFavorites()
        list.value = Array.isArray(res) ? res : []
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
}

async function remove(item: any) {
    try {
        await toggleStaffFavorite({ id: item.id })
        list.value = list.value.filter((i) => i.id !== item.id)
        uni.showToast({ title: '已取消收藏', icon: 'none' })
    } catch {
        // 请求层已提示
    }
}

function goDetail(item: any) {
    navigate({ path: '/packages/pkg-catalog/pages/staff-detail/staff-detail', query: { id: item.id } })
}

function goList() {
    navigate({ path: '/packages/pkg-catalog/pages/staff-list/staff-list' })
}

onShow(() => load())
</script>

<style lang="scss" scoped>
.fav-page {
    background: var(--c-bg);
}

.cards {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.card {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    background: var(--c-surface);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-lg);
    padding: var(--sp-3);
    box-shadow: var(--shadow-card);

    &:active {
        opacity: 0.9;
    }

    &__avatar {
        width: 120rpx;
        height: 144rpx;
        border-radius: var(--r-md);
        background: var(--c-neutral-bg);
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-1) var(--sp-2);
        min-width: 0;
    }

    &__rating {
        font-size: var(--fs-xs);
        color: var(--c-warning);
        flex-shrink: 0;
    }

    &__muted {
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }

    &__unfav {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 88rpx;
        height: 88rpx;
        margin: calc(-1 * var(--sp-2));
        flex-shrink: 0;
    }
}
</style>
