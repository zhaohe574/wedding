<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="我的收藏" />

        <view class="staff-favorite wm-page-content">
            <view v-if="loading" class="loading-container">
                <tn-loading mode="circle" />
            </view>

            <EmptyState
                v-else-if="!favoriteList.length"
                title="还没有收藏任何服务人员"
                description="去看看可预约人员。"
                action-text="去看看"
                @action="goToList"
            />

            <view v-else class="favorite-list">
                <BaseCard variant="hero" scene="consumer" class="favorite-hero">
                    <view class="favorite-hero__copy">
                        <text class="favorite-hero__eyebrow">服务人员收藏夹</text>
                        <text class="favorite-hero__title">保留心动人选，方便继续比较</text>
                        <text class="favorite-hero__meta">{{ favoriteHeroMeta }}</text>
                    </view>

                    <view class="favorite-hero__stats">
                        <view class="favorite-hero__stat wm-soft-card">
                            <text class="favorite-hero__stat-label">已收藏</text>
                            <text class="favorite-hero__stat-value">{{ favoriteList.length }}</text>
                        </view>

                        <view class="favorite-hero__stat wm-soft-card">
                            <text class="favorite-hero__stat-label">可继续</text>
                            <text class="favorite-hero__stat-value">查看档期</text>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard
                    v-for="item in favoriteList"
                    :key="item.id"
                    variant="glass"
                    scene="consumer"
                    class="favorite-card"
                    interactive
                    @click="goToDetail(item.id)"
                >
                    <view class="favorite-card__content">
                        <view class="favorite-card__avatar-shell">
                            <image
                                class="favorite-card__avatar"
                                :src="item.avatar || '/static/images/user/default_avatar.png'"
                                mode="aspectFill"
                                lazy-load
                            />

                            <view v-if="item.is_vip" class="favorite-card__vip-badge">
                                <tn-icon name="vip-fill" size="24" color="#FFD700" />
                            </view>
                        </view>

                        <view class="favorite-card__body">
                            <view class="favorite-card__head">
                                <view class="favorite-card__copy">
                                    <view class="favorite-card__title-row">
                                        <text class="favorite-card__name">{{ item.name }}</text>
                                        <StatusBadge
                                            v-if="item.is_verified"
                                            tone="success"
                                            size="sm"
                                        >
                                            已认证
                                        </StatusBadge>
                                    </view>

                                    <view class="favorite-card__badge-row">
                                        <StatusBadge tone="info" size="sm">
                                            {{ item.category_name || '服务人员' }}
                                        </StatusBadge>

                                        <StatusBadge v-if="item.rating" tone="neutral" size="sm">
                                            评分 {{ item.rating }}
                                        </StatusBadge>
                                    </view>
                                </view>

                                <view
                                    class="favorite-card__favorite-btn"
                                    @click.stop="handleCancelFavorite(item)"
                                >
                                    <tn-icon
                                        name="star-fill"
                                        size="44"
                                        :color="$theme.secondaryColor"
                                    />
                                </view>
                            </view>

                            <view class="favorite-card__rating-row">
                                <view class="favorite-card__rating-stars">
                                    <tn-icon
                                        v-for="star in 5"
                                        :key="star"
                                        :name="
                                            star <= Math.floor(item.rating) ? 'star-fill' : 'star'
                                        "
                                        size="24"
                                        :color="
                                            star <= Math.floor(item.rating)
                                                ? $theme.accentColor
                                                : '#E5E7EB'
                                        "
                                    />
                                </view>
                                <text class="favorite-card__rating-score">
                                    {{ item.rating || '0.0' }}
                                </text>
                                <text class="favorite-card__rating-divider">·</text>
                                <text class="favorite-card__order-count">
                                    服务 {{ item.order_count || 0 }} 场
                                </text>
                            </view>

                            <view class="favorite-card__footer">
                                <view class="favorite-card__price-group">
                                    <template
                                        v-if="
                                            item.has_price !== false &&
                                            item.price !== null &&
                                            item.price !== undefined
                                        "
                                    >
                                        <text class="favorite-card__price-symbol">¥</text>
                                        <text class="favorite-card__price-value">{{
                                            item.price_text || item.price
                                        }}</text>
                                        <text class="favorite-card__price-unit">/次起</text>
                                    </template>
                                    <text v-else class="favorite-card__price-negotiable">面议</text>
                                </view>

                                <view class="favorite-card__actions">
                                    <view
                                        class="favorite-card__ghost-btn"
                                        @click.stop="goToDetail(item.id)"
                                    >
                                        <text class="favorite-card__ghost-btn-text">查看详情</text>
                                    </view>

                                    <view
                                        class="favorite-card__primary-btn"
                                        @click.stop="handleBooking(item)"
                                    >
                                        <text class="favorite-card__primary-btn-text">
                                            立即预约
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </BaseCard>
            </view>
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { getMyFavoriteStaff, toggleStaffFavorite } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const loading = ref(true)
const favoriteList = ref<any[]>([])
const favoriteHeroMeta = computed(() =>
    favoriteList.value.length > 1
        ? `当前共收藏 ${favoriteList.value.length} 位服务人员，可继续查看详情与预约信息。`
        : '已收藏的人选会保留在这里，方便继续查看详情与预约信息。'
)

// 获取收藏列表
const getFavorites = async () => {
    loading.value = true
    try {
        const data = await getMyFavoriteStaff()
        favoriteList.value = data || []
    } catch (e) {
        console.error(e)
        uni.showToast({
            title: '加载失败，请重试',
            icon: 'none'
        })
    } finally {
        loading.value = false
    }
}

// 取消收藏
const handleCancelFavorite = async (item: any) => {
    uni.showModal({
        title: '提示',
        content: `确定取消收藏 ${item.name} 吗？`,
        success: async (res) => {
            if (res.confirm) {
                try {
                    await toggleStaffFavorite({ id: item.id })
                    favoriteList.value = favoriteList.value.filter((i) => i.id !== item.id)
                    uni.showToast({
                        title: '已取消收藏',
                        icon: 'success'
                    })
                } catch (e: any) {
                    uni.showToast({
                        title: e.msg || '操作失败',
                        icon: 'none'
                    })
                }
            }
        }
    })
}

// 立即预约
const handleBooking = (item: any) => {
    if (!item.id) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })
        return
    }
    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${item.id}&open_date_picker=1`
    })
}

// 跳转列表
const goToList = () => {
    uni.switchTab({
        url: '/pages/index/index'
    })
}

// 跳转详情
const goToDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${id}`
    })
}

onShow(() => {
    getFavorites()
})
</script>

<style lang="scss" scoped>
.staff-favorite {
    min-height: 100vh;
    background: transparent;
}

.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 200rpx 0;
}

.favorite-list {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding: 24rpx;
}

.favorite-hero {
    &__copy {
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__eyebrow {
        font-size: 22rpx;
        font-weight: 600;
        color: var(--wm-color-primary, #e85a4f);
        letter-spacing: 4rpx;
    }

    &__title {
        font-size: 40rpx;
        line-height: 1.3;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }

    &__meta {
        font-size: 24rpx;
        line-height: 1.6;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16rpx;
        margin-top: 24rpx;
    }

    &__stat {
        display: flex;
        flex-direction: column;
        gap: 8rpx;
        padding: 20rpx 24rpx;
        border-radius: 28rpx;
    }

    &__stat-label {
        font-size: 22rpx;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__stat-value {
        font-size: 30rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }
}

.favorite-card {
    &__content {
        display: flex;
        gap: 24rpx;
    }

    &__avatar-shell {
        position: relative;
        flex-shrink: 0;
    }

    &__avatar {
        width: 168rpx;
        height: 200rpx;
        border-radius: 32rpx;
        background: rgba(255, 255, 255, 0.7);
    }

    &__vip-badge {
        position: absolute;
        top: 12rpx;
        right: 12rpx;
        width: 44rpx;
        height: 44rpx;
        border-radius: 50%;
        background: rgba(23, 23, 23, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 18rpx;
    }

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__title-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12rpx;
    }

    &__name {
        font-size: 34rpx;
        line-height: 1.3;
        font-weight: 700;
        color: var(--wm-text-primary, #1e2432);
    }

    &__badge-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10rpx;
    }

    &__favorite-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 72rpx;
        height: 72rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.66);
        border: 1rpx solid rgba(239, 230, 225, 0.96);
    }

    &__rating-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
        flex-wrap: wrap;
    }

    &__rating-stars {
        display: flex;
        align-items: center;
        gap: 4rpx;
    }

    &__rating-score {
        font-size: 26rpx;
        font-weight: 700;
        color: var(--wm-color-secondary, #c99b73);
    }

    &__rating-divider,
    &__order-count {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        margin-top: auto;
    }

    &__price-group {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 4rpx;
    }

    &__price-symbol {
        font-size: 24rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #e85a4f);
    }

    &__price-value {
        font-size: 40rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #e85a4f);
    }

    &__price-unit,
    &__price-negotiable {
        font-size: 24rpx;
        color: var(--wm-text-secondary, #7f7b78);
    }

    &__price-negotiable {
        font-size: 28rpx;
        font-weight: 700;
    }

    &__actions {
        display: flex;
        gap: 12rpx;
    }

    &__ghost-btn,
    &__primary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 128rpx;
        height: 72rpx;
        padding: 0 24rpx;
        border-radius: 999rpx;
        box-sizing: border-box;
    }

    &__ghost-btn {
        background: rgba(255, 255, 255, 0.7);
        border: 1rpx solid rgba(239, 230, 225, 0.96);
    }

    &__primary-btn {
        background: linear-gradient(135deg, #f07c70 0%, #e85a4f 100%);
        box-shadow: 0 14rpx 28rpx rgba(232, 90, 79, 0.18);
    }

    &__ghost-btn-text {
        font-size: 24rpx;
        font-weight: 600;
        color: var(--wm-text-primary, #1e2432);
    }

    &__primary-btn-text {
        font-size: 24rpx;
        font-weight: 700;
        color: #fff;
    }
}
</style>
