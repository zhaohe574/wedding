<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="editorial">
        <BaseNavbar title="我的收藏" />

        <view class="staff-favorite wm-page-content">
            <view v-if="loading" class="loading-container">
                <tn-loading mode="circle" />
            </view>

            <view v-else-if="!favoriteList.length" class="empty-shell">
                <EmptyState
                    title="还没有心动人选"
                    description="先去挑选服务人员，收藏后会在这里集中对比。"
                    action-text="去挑选"
                    @action="goToList"
                />
            </view>

            <view v-else class="favorite-showcase">
                <BaseCard
                    v-if="leadStaff"
                    variant="media"
                    scene="consumer"
                    class="lead-card"
                    interactive
                    @click="goToDetail(leadStaff.id)"
                >
                    <view class="lead-card__media">
                        <image
                            class="lead-card__image"
                            :src="getStaffAvatar(leadStaff)"
                            mode="aspectFill"
                            lazy-load
                        />

                        <view class="lead-card__shade" />

                        <view class="lead-card__topline">
                            <text class="lead-card__badge">{{
                                leadStaff.category_name || '服务人员'
                            }}</text>

                            <view
                                class="lead-card__favorite"
                                @click.stop="handleCancelFavorite(leadStaff)"
                            >
                                <tn-icon name="star-fill" size="34" color="#C8A45D" />
                            </view>
                        </view>

                        <view class="lead-card__identity">
                            <text class="lead-card__name">{{
                                leadStaff.name || '未命名人员'
                            }}</text>
                            <text class="lead-card__meta">{{ buildStaffMeta(leadStaff) }}</text>
                        </view>
                    </view>

                    <view class="lead-card__content">
                        <view class="lead-card__metric-row">
                            <view class="lead-card__metric">
                                <text class="lead-card__metric-value">{{
                                    formatRating(leadStaff)
                                }}</text>
                                <text class="lead-card__metric-label">综合评分</text>
                            </view>

                            <view class="lead-card__metric">
                                <text class="lead-card__metric-value">{{
                                    leadStaff.order_count || 0
                                }}</text>
                                <text class="lead-card__metric-label">服务场次</text>
                            </view>

                            <view class="lead-card__metric">
                                <text class="lead-card__metric-value">{{
                                    formatPrice(leadStaff)
                                }}</text>
                                <text class="lead-card__metric-label">参考价格</text>
                            </view>
                        </view>

                        <view v-if="getDisplayTags(leadStaff).length" class="lead-card__tags">
                            <text
                                v-for="tag in getDisplayTags(leadStaff)"
                                :key="`lead-${leadStaff.id}-${tag}`"
                                class="lead-card__tag"
                            >
                                {{ tag }}
                            </text>
                        </view>

                        <view class="lead-card__actions">
                            <view
                                class="lead-card__ghost-btn"
                                @click.stop="goToDetail(leadStaff.id)"
                            >
                                <text class="lead-card__ghost-text">查看详情</text>
                            </view>

                            <view
                                class="lead-card__primary-btn"
                                @click.stop="handleBooking(leadStaff)"
                            >
                                <text class="lead-card__primary-text">立即预约</text>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <view v-if="galleryStaff.length" class="gallery-section">
                    <view class="gallery-section__head">
                        <text class="gallery-section__title">全部收藏</text>
                        <text class="gallery-section__meta">已保留的备选人员</text>
                    </view>

                    <view class="gallery-grid">
                        <BaseCard
                            v-for="item in galleryStaff"
                            :key="item.id"
                            variant="media"
                            scene="consumer"
                            class="gallery-card"
                            interactive
                            @click="goToDetail(item.id)"
                        >
                            <view class="gallery-card__media">
                                <image
                                    class="gallery-card__image"
                                    :src="getStaffAvatar(item)"
                                    mode="aspectFill"
                                    lazy-load
                                />

                                <view
                                    class="gallery-card__favorite"
                                    @click.stop="handleCancelFavorite(item)"
                                >
                                    <tn-icon name="star-fill" size="28" color="#C8A45D" />
                                </view>
                            </view>

                            <view class="gallery-card__body">
                                <text class="gallery-card__name">{{
                                    item.name || '未命名人员'
                                }}</text>
                                <text class="gallery-card__role">{{
                                    item.category_name || '服务人员'
                                }}</text>

                                <view class="gallery-card__footer">
                                    <view class="gallery-card__score">
                                        <tn-icon name="star-fill" size="18" color="#C8A45D" />
                                        <text class="gallery-card__score-text">{{
                                            formatRating(item)
                                        }}</text>
                                    </view>

                                    <text class="gallery-card__price">{{ formatPrice(item) }}</text>
                                </view>

                                <view class="gallery-card__actions">
                                    <view
                                        class="gallery-card__action gallery-card__action--ghost"
                                        @click.stop="goToDetail(item.id)"
                                    >
                                        <text class="gallery-card__action-text">详情</text>
                                    </view>

                                    <view
                                        class="gallery-card__action gallery-card__action--primary"
                                        @click.stop="handleBooking(item)"
                                    >
                                        <text
                                            class="gallery-card__action-text gallery-card__action-text--primary"
                                        >
                                            预约
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </BaseCard>
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getMyFavoriteStaff, toggleStaffFavorite } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'

interface FavoriteStaff {
    id: number
    sn?: string
    name?: string
    avatar?: string
    category_id?: number
    category_name?: string
    rating?: number | string | null
    order_count?: number | string | null
    price?: number | string | null
    price_text?: string | null
    has_price?: boolean
    tags?: string[] | string | null
    tags_arr?: string[] | string | null
}

const DEFAULT_AVATAR = '/static/images/user/default_avatar.png'

const $theme = useThemeStore()

const loading = ref(true)
const favoriteList = ref<FavoriteStaff[]>([])
const leadStaff = computed(() => favoriteList.value[0] || null)
const galleryStaff = computed(() => favoriteList.value.slice(1))

const getStaffAvatar = (item: FavoriteStaff) => item.avatar || DEFAULT_AVATAR

const normalizeTags = (tags: FavoriteStaff['tags'] | FavoriteStaff['tags_arr']) => {
    if (Array.isArray(tags)) {
        return tags.map((tag) => String(tag || '').trim()).filter(Boolean)
    }

    if (typeof tags === 'string') {
        return tags
            .split(/[、,]/)
            .map((tag) => tag.trim())
            .filter(Boolean)
    }

    return []
}

const getDisplayTags = (item: FavoriteStaff, limit = 4) => {
    const sourceTags = normalizeTags(item.tags_arr).length
        ? normalizeTags(item.tags_arr)
        : normalizeTags(item.tags)
    return sourceTags.slice(0, limit)
}

const formatRating = (item: FavoriteStaff) => {
    const rating = Number(item.rating || 0)
    return Number.isFinite(rating) ? rating.toFixed(1) : '0.0'
}

const hasStaffPrice = (item: FavoriteStaff) =>
    !(item.has_price === false || item.price === null || item.price === undefined)

const formatPrice = (item: FavoriteStaff) => {
    if (!hasStaffPrice(item)) {
        return '面议'
    }

    return `¥${item.price_text || item.price}`
}

const buildStaffMeta = (item: FavoriteStaff) => {
    const parts = [item.category_name || '服务人员', `${item.order_count || 0} 场服务`]
    return parts.join(' · ')
}

const getFavorites = async () => {
    loading.value = true
    try {
        const data = await getMyFavoriteStaff()
        favoriteList.value = Array.isArray(data) ? data : []
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

const handleCancelFavorite = async (item: FavoriteStaff) => {
    uni.showModal({
        title: '提示',
        content: `确定取消收藏 ${item.name || '该服务人员'} 吗？`,
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

const handleBooking = (item: FavoriteStaff) => {
    if (!item.id) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })
        return
    }

    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${item.id}&open_date_picker=1`
    })
}

const goToList = () => {
    uni.switchTab({
        url: '/pages/index/index'
    })
}

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
    padding: 220rpx 0;
}

.empty-shell {
    min-height: calc(100vh - 220rpx);
    display: flex;
    align-items: center;
    justify-content: center;
}

.favorite-showcase {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding: 0 0 calc(8rpx + env(safe-area-inset-bottom));
    animation: favorite-showcase-enter 260ms ease both;
}

.lead-card {
    animation: favorite-card-enter 280ms ease 60ms both;

    &__media {
        position: relative;
        height: 560rpx;
        overflow: hidden;
        background: linear-gradient(135deg, #f7f0df 0%, #d8c28a 100%);
    }

    &__image {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__shade {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        bottom: 0;
        background: linear-gradient(
            180deg,
            rgba(11, 11, 11, 0.14) 0%,
            rgba(11, 11, 11, 0.06) 44%,
            rgba(11, 11, 11, 0.72) 100%
        );
    }

    &__topline {
        position: absolute;
        top: 24rpx;
        left: 24rpx;
        right: 24rpx;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18rpx;
    }

    &__badge {
        max-width: 390rpx;
        min-height: 54rpx;
        padding: 0 20rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        background: rgba(255, 255, 255, 0.88);
        display: inline-flex;
        align-items: center;
        font-size: 22rpx;
        line-height: 54rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #0b0b0b);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        box-sizing: border-box;
    }

    &__favorite {
        width: 66rpx;
        height: 66rpx;
        border-radius: 50%;
        background: rgba(11, 11, 11, 0.56);
        border: 1rpx solid rgba(255, 255, 255, 0.26);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;

        &:active {
            transform: scale(0.96);
        }
    }

    &__identity {
        position: absolute;
        left: 30rpx;
        right: 30rpx;
        bottom: 32rpx;
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__name {
        display: block;
        font-size: 46rpx;
        line-height: 1.18;
        font-weight: 800;
        color: #ffffff;
    }

    &__meta {
        display: block;
        font-size: 24rpx;
        line-height: 1.4;
        color: rgba(255, 255, 255, 0.78);
    }

    &__content {
        padding: 30rpx;
        display: flex;
        flex-direction: column;
        gap: 24rpx;
    }

    &__metric-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14rpx;
    }

    &__metric {
        min-width: 0;
        padding: 20rpx 16rpx;
        border-radius: var(--wm-radius-card-soft, 14rpx);
        background: var(--wm-color-bg-soft, #f7f7f7);
        border: 1rpx solid var(--wm-color-border, #e5e5e5);
        box-sizing: border-box;
    }

    &__metric-value {
        display: block;
        min-width: 0;
        font-size: 28rpx;
        line-height: 1.2;
        font-weight: 800;
        color: var(--wm-text-primary, #111111);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__metric-label {
        display: block;
        margin-top: 8rpx;
        font-size: 20rpx;
        line-height: 1.2;
        color: var(--wm-text-secondary, #4a4a4a);
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10rpx;
    }

    &__tag {
        min-height: 48rpx;
        padding: 0 18rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        background: var(--wm-color-secondary-soft, #f8f3e7);
        border: 1rpx solid rgba(200, 164, 93, 0.42);
        font-size: 22rpx;
        line-height: 48rpx;
        font-weight: 700;
        color: var(--wm-color-primary, #0b0b0b);
    }

    &__actions {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.2fr);
        gap: 16rpx;
    }

    &__ghost-btn,
    &__primary-btn {
        min-height: 82rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;

        &:active {
            transform: scale(0.99);
        }
    }

    &__ghost-btn {
        background: #ffffff;
        border: 1rpx solid var(--wm-color-border, #e5e5e5);
    }

    &__primary-btn {
        background: var(--wm-color-primary, #0b0b0b);
        box-shadow: 0 12rpx 24rpx rgba(11, 11, 11, 0.16);
    }

    &__ghost-text,
    &__primary-text {
        font-size: 26rpx;
        line-height: 1;
        font-weight: 800;
    }

    &__ghost-text {
        color: var(--wm-text-primary, #111111);
    }

    &__primary-text {
        color: #ffffff;
    }
}

.gallery-section {
    display: flex;
    flex-direction: column;
    gap: 18rpx;

    &__head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20rpx;
    }

    &__title {
        font-size: 32rpx;
        line-height: 1.25;
        font-weight: 800;
        color: var(--wm-text-primary, #111111);
    }

    &__meta {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: 22rpx;
        line-height: 1.4;
        color: var(--wm-text-tertiary, #8a8a8a);
    }
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18rpx;
}

.gallery-card {
    min-width: 0;
    animation: favorite-card-enter 280ms ease both;

    &__media {
        position: relative;
        height: 284rpx;
        overflow: hidden;
        background: linear-gradient(135deg, #f7f0df 0%, #d8c28a 100%);
    }

    &__image {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__favorite {
        position: absolute;
        right: 16rpx;
        top: 16rpx;
        width: 54rpx;
        height: 54rpx;
        border-radius: 50%;
        background: rgba(11, 11, 11, 0.58);
        border: 1rpx solid rgba(255, 255, 255, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;

        &:active {
            transform: scale(0.96);
        }
    }

    &__body {
        padding: 18rpx;
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__name {
        display: block;
        min-width: 0;
        font-size: 28rpx;
        line-height: 1.3;
        font-weight: 800;
        color: var(--wm-text-primary, #111111);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__role {
        display: block;
        min-width: 0;
        font-size: 22rpx;
        line-height: 1.35;
        color: var(--wm-text-secondary, #4a4a4a);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8rpx;
        padding-top: 4rpx;
    }

    &__score {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 4rpx;
    }

    &__score-text {
        font-size: 21rpx;
        line-height: 1.2;
        font-weight: 800;
        color: var(--wm-color-warning, #9f7a2e);
    }

    &__price {
        min-width: 0;
        font-size: 22rpx;
        line-height: 1.2;
        font-weight: 800;
        color: var(--wm-text-primary, #111111);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10rpx;
        padding-top: 6rpx;
    }

    &__action {
        min-width: 0;
        min-height: 56rpx;
        border-radius: var(--wm-radius-pill, 999rpx);
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;

        &:active {
            transform: scale(0.98);
        }

        &--ghost {
            background: #ffffff;
            border: 1rpx solid var(--wm-color-border, #e5e5e5);
        }

        &--primary {
            background: var(--wm-color-primary, #0b0b0b);
        }
    }

    &__action-text {
        font-size: 22rpx;
        line-height: 1;
        font-weight: 800;
        color: var(--wm-text-primary, #111111);

        &--primary {
            color: #ffffff;
        }
    }
}

@keyframes favorite-showcase-enter {
    from {
        opacity: 0;
        transform: translateY(18rpx);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes favorite-card-enter {
    from {
        opacity: 0;
        transform: translateY(14rpx);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
