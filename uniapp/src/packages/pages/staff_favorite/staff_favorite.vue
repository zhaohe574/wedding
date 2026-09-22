<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" tone="workspace">
        <BaseNavbar
            title="我的收藏"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <z-paging
            ref="paging"
            v-model="favoriteList"
            :fixed="false"
            height="100%"
            use-page-scroll
            :hide-empty-view="true"
            @query="queryList"
        >
            <view class="staff-favorite wm-page-content">
                <!-- Loading State -->
                <BaseCard
                    v-if="loading && !hasLoaded"
                    class="staff-favorite__state"
                    variant="quiet"
                    padding="42rpx 28rpx"
                    border-radius="32rpx"
                >
                    <LoadingState text="正在同步收藏团队..." tone="wedding" compact />
                </BaseCard>

                <!-- Favorite List Content -->
                <view v-else-if="favoriteList.length" class="staff-favorite__content wm-page-stack">
                    <!-- Header Summary Bar -->
                    <view class="staff-favorite__header-bar">
                        <view class="staff-favorite__summary-left">
                            <view class="staff-favorite__summary-kicker">
                                <BaseIcon name="heart-fill" size="20" color="#D9BE82" />
                                <text>我的收藏</text>
                            </view>
                            <text class="staff-favorite__summary-text">
                                已收藏 <text class="staff-favorite__summary-num">{{ favoriteList.length }}</text> 位
                            </text>
                        </view>
                        <view class="staff-favorite__summary-tip">
                            <text>向左滑动卡片可快速取消</text>
                        </view>
                    </view>

                    <!-- Staff Cards -->
                    <u-swipe-action
                        v-for="(item, index) in favoriteList"
                        :key="item.id"
                        class="staff-favorite__swipe"
                        :show="item.show"
                        :index="index"
                        :options="swipeOptions"
                        btn-width="156"
                        bg-color="transparent"
                        @click="handleCancelFavorite"
                    >
                        <BaseCard
                            class="staff-favorite-card"
                            variant="list"
                            padding="24rpx"
                            border-radius="32rpx"
                            border="1rpx solid rgba(216, 201, 173, 0.78)"
                            box-shadow="0 14rpx 32rpx rgba(74, 43, 24, 0.07)"
                            interactive
                            @click="goToDetail(item.id)"
                        >
                            <view class="staff-favorite-card__layout">
                                <!-- Avatar -->
                                <view class="staff-favorite-card__avatar-wrap">
                                    <image
                                        class="staff-favorite-card__avatar"
                                        :src="getStaffAvatar(item)"
                                        mode="aspectFill"
                                        lazy-load
                                    />
                                    <view class="staff-favorite-card__shade"></view>
                                    <view class="staff-favorite-card__badge">
                                        <BaseIcon name="heart-fill" size="18" color="#D9BE82" />
                                        <text>已收藏</text>
                                    </view>
                                </view>

                                <!-- Body -->
                                <view class="staff-favorite-card__body">
                                    <view class="staff-favorite-card__head">
                                        <view class="staff-favorite-card__identity">
                                            <view class="staff-favorite-card__title-row">
                                                <text class="staff-favorite-card__name text-ellipsis">
                                                    {{ item.name || '未命名手艺人' }}
                                                </text>
                                                <text
                                                    v-if="item.category_name"
                                                    class="staff-favorite-card__role"
                                                >
                                                    {{ item.category_name }}
                                                </text>
                                            </view>
                                        </view>

                                        <view
                                            class="staff-favorite-card__heart-btn"
                                            @click.stop="handleCancelFavorite(index)"
                                        >
                                            <BaseIcon name="heart-fill" size="26" color="#D9BE82" />
                                        </view>
                                    </view>

                                    <!-- Metrics Row -->
                                    <view class="staff-favorite-card__metrics">
                                        <view class="staff-favorite-card__metric">
                                            <BaseIcon name="star-fill" size="20" color="#B8954A" />
                                            <text class="staff-favorite-card__metric-score">
                                                {{ formatRating(item) }}
                                            </text>
                                        </view>

                                        <view class="staff-favorite-card__metric">
                                            <BaseIcon name="calendar" size="20" color="#9A9388" />
                                            <text class="staff-favorite-card__metric-text">
                                                {{ item.order_count || 0 }} 场履约
                                            </text>
                                        </view>

                                        <view class="staff-favorite-card__metric staff-favorite-card__metric--price">
                                            <text class="staff-favorite-card__price">
                                                {{ formatPrice(item) }}
                                            </text>
                                        </view>
                                    </view>

                                    <!-- Tags -->
                                    <view
                                        v-if="getDisplayTags(item).length"
                                        class="staff-favorite-card__tags"
                                    >
                                        <text
                                            v-for="tag in getDisplayTags(item)"
                                            :key="`${item.id}-${tag}`"
                                            class="staff-favorite-card__tag"
                                        >
                                            {{ tag }}
                                        </text>
                                    </view>
                                </view>

                                <!-- Bottom Action -->
                                <view class="staff-favorite-card__actions">
                                    <BaseButton
                                        label="查看主页与档期"
                                        variant="dark"
                                        size="mini"
                                        height="60rpx"
                                        font-size="23rpx"
                                        icon="right"
                                        icon-position="right"
                                        @click.stop="goToDetail(item.id)"
                                    />
                                </view>
                            </view>
                        </BaseCard>
                    </u-swipe-action>
                </view>

                <!-- Empty State -->
                <EmptyState
                    v-else
                    class="staff-favorite__empty"
                    title="还没有收藏"
                    description="探索不同风格的摄影师、造型师与司仪，收藏心仪人员以便对比档期"
                    action-text="去挑选"
                    icon="heart"
                    compact
                    @action="goToScheduleQuery"
                />
            </view>
        </z-paging>
    </PageShell>
</template>

<script lang="ts" setup>
import { onShow } from '@dcloudio/uni-app'
import { reactive, ref, shallowRef } from 'vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getMyFavoriteStaff, toggleStaffFavorite } from '@/api/staff'
import { useThemeStore } from '@/stores/theme'
import { showError, showSuccess } from '@/utils/feedback'

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
    show?: boolean
}

interface SwipeActionPayload {
    index?: number
}

const DEFAULT_AVATAR = '/static/images/user/default_avatar.png'

const $theme = useThemeStore()
const paging = shallowRef()
const favoriteList = ref<FavoriteStaff[]>([])
const loading = ref(true)
const hasLoaded = ref(false)
const swipeOptions = reactive([
    {
        text: '取消收藏',
        style: {
            color: '#FFFDF8',
            backgroundColor: '#7D4C35',
            fontSize: '24rpx',
            fontWeight: '700'
        }
    }
])

const normalizeFavoriteList = (data: unknown): FavoriteStaff[] => {
    if (!Array.isArray(data)) {
        return []
    }

    return data
        .filter((item) => Number(item?.id || 0) > 0)
        .map((item) => ({
            ...item,
            show: false
        }))
}

const queryList = async (pageNo: number, pageSize: number) => {
    void pageSize
    if (pageNo > 1) {
        paging.value?.complete([])
        return
    }

    loading.value = true
    try {
        const data = await getMyFavoriteStaff()
        paging.value?.complete(normalizeFavoriteList(data))
    } catch (error) {
        showError(error)
        paging.value?.complete(false)
    } finally {
        loading.value = false
        hasLoaded.value = true
    }
}

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

const getDisplayTags = (item: FavoriteStaff, limit = 3) => {
    const sourceTags = normalizeTags(item.tags_arr).length
        ? normalizeTags(item.tags_arr)
        : normalizeTags(item.tags)
    return sourceTags.slice(0, limit)
}

const getStaffAvatar = (item: FavoriteStaff) => item.avatar || DEFAULT_AVATAR

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

    return `¥${item.price_text || item.price} 起`
}

const getSwipeIndex = (payload: number | SwipeActionPayload) => {
    if (typeof payload === 'number') {
        return payload
    }

    return Number(payload?.index ?? -1)
}

const handleCancelFavorite = async (payload: number | SwipeActionPayload): Promise<void> => {
    const index = getSwipeIndex(payload)
    const target = favoriteList.value[index]
    const staffId = Number(target?.id || 0)

    if (!staffId) {
        showError('服务人员信息错误')
        return
    }

    try {
        await toggleStaffFavorite({ id: staffId })
        favoriteList.value = favoriteList.value.filter((item) => Number(item.id) !== staffId)
        showSuccess('已取消收藏')
    } catch (error) {
        showError(error)
    }
}

const goToDetail = (id: number) => {
    if (!id) {
        showError('服务人员信息错误')
        return
    }

    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${id}`
    })
}

const goToScheduleQuery = () => {
    uni.navigateTo({
        url: '/pages/schedule_query/schedule_query'
    })
}

onShow(() => {
    if (hasLoaded.value) {
        paging.value?.reload()
    }
})
</script>

<style scoped lang="scss">
.staff-favorite {
    display: flex;
    flex-direction: column;
    padding: 24rpx var(--wm-space-page-x, 28rpx) calc(48rpx + env(safe-area-inset-bottom));
    background: transparent;
    box-sizing: border-box;
}

.staff-favorite__content {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

/* Header Summary Bar */
.staff-favorite__header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16rpx 20rpx;
    border-radius: 24rpx;
    background: rgba(255, 253, 248, 0.7);
    border: 1rpx solid rgba(216, 201, 173, 0.5);
}

.staff-favorite__summary-left {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.staff-favorite__summary-kicker {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    padding: 4rpx 14rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(217, 190, 130, 0.16);
    border: 1rpx solid rgba(217, 190, 130, 0.5);
    font-size: 20rpx;
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

.staff-favorite__summary-text {
    font-size: 23rpx;
    color: var(--wm-text-secondary, #5f5a50);
}

.staff-favorite__summary-num {
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.staff-favorite__summary-tip {
    font-size: 20rpx;
    color: var(--wm-text-tertiary, #9a9388);
}

.staff-favorite__state,
.staff-favorite__swipe,
.staff-favorite-card {
    display: block;
}

/* Artisan Card Layout */
.staff-favorite-card__layout {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 168rpx minmax(0, 1fr);
    grid-template-rows: minmax(154rpx, auto) 60rpx;
    column-gap: 20rpx;
    row-gap: 16rpx;
    min-height: 234rpx;
}

.staff-favorite-card__avatar-wrap {
    position: relative;
    grid-row: 1 / 2;
    grid-column: 1 / 2;
    width: 168rpx;
    height: 168rpx;
    overflow: hidden;
    border-radius: 24rpx;
    border: 2rpx solid rgba(217, 190, 130, 0.6);
    background: linear-gradient(145deg, #fff7ec 0%, #d9be82 58%, #b8954a 100%);
    box-shadow: 0 8rpx 20rpx rgba(74, 43, 24, 0.08);
    align-self: start;
}

.staff-favorite-card__avatar {
    display: block;
    width: 100%;
    height: 100%;
}

.staff-favorite-card__shade {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(25, 23, 19, 0) 40%, rgba(25, 23, 19, 0.45) 100%);
    pointer-events: none;
}

.staff-favorite-card__badge {
    position: absolute;
    left: 8rpx;
    bottom: 8rpx;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 4rpx;
    padding: 2rpx 10rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(25, 23, 19, 0.75);
    border: 1rpx solid rgba(217, 190, 130, 0.5);
    font-size: 18rpx;
    font-weight: 800;
    color: var(--wm-color-champagne, #d9be82);
}

.staff-favorite-card__body {
    grid-row: 1 / 2;
    grid-column: 2 / 3;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 12rpx;
}

.staff-favorite-card__head {
    min-width: 0;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12rpx;
}

.staff-favorite-card__identity {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.staff-favorite-card__title-row {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-wrap: wrap;
}

.staff-favorite-card__name {
    font-size: 32rpx;
    font-weight: 900;
    line-height: 1.3;
    color: var(--wm-text-primary, #191713);
}

.staff-favorite-card__role {
    display: inline-flex;
    align-items: center;
    padding: 2rpx 12rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(217, 190, 130, 0.16);
    border: 1rpx solid rgba(217, 190, 130, 0.45);
    font-size: 20rpx;
    font-weight: 800;
    color: var(--wm-color-gold, #b8954a);
}

.staff-favorite-card__heart-btn {
    flex-shrink: 0;
    width: 54rpx;
    height: 54rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(25, 23, 19, 0.9);
    border: 1rpx solid rgba(217, 190, 130, 0.7);
    box-shadow: 0 6rpx 16rpx rgba(74, 43, 24, 0.1);
    transition: transform 0.15s ease;
}

.staff-favorite-card__heart-btn:active {
    transform: scale(0.9);
}

.staff-favorite-card__metrics {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-wrap: wrap;
}

.staff-favorite-card__metric {
    min-width: 0;
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    height: 38rpx;
    padding: 0 12rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(248, 242, 228, 0.85);
    border: 1rpx solid rgba(216, 201, 173, 0.5);
    box-sizing: border-box;
}

.staff-favorite-card__metric-score {
    font-size: 21rpx;
    font-weight: 900;
    color: var(--wm-color-gold, #b8954a);
}

.staff-favorite-card__metric-text {
    font-size: 20rpx;
    font-weight: 700;
    color: var(--wm-text-secondary, #665e52);
}

.staff-favorite-card__metric--price {
    background: rgba(217, 190, 130, 0.12);
    border-color: rgba(217, 190, 130, 0.4);
}

.staff-favorite-card__price {
    font-size: 21rpx;
    font-weight: 900;
    color: var(--wm-color-gold, #b8954a);
}

.staff-favorite-card__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8rpx;
}

.staff-favorite-card__tag {
    max-width: 140rpx;
    height: 36rpx;
    padding: 0 12rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(25, 23, 19, 0.04);
    border: 1rpx solid rgba(25, 23, 19, 0.08);
    font-size: 19rpx;
    font-weight: 700;
    line-height: 36rpx;
    color: var(--wm-text-secondary, #665e52);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    box-sizing: border-box;
}

.staff-favorite-card__actions {
    grid-row: 2 / 3;
    grid-column: 1 / 3;
    display: flex;
    justify-content: stretch;
    align-items: stretch;
}

.staff-favorite-card__actions :deep(.base-button) {
    width: 100%;
}

.staff-favorite__empty {
    margin-top: 16rpx;
}

@media screen and (max-width: 360px) {
    .staff-favorite-card__layout {
        grid-template-columns: 150rpx minmax(0, 1fr);
    }

    .staff-favorite-card__avatar-wrap {
        width: 150rpx;
        height: 150rpx;
    }

    .staff-favorite-card__name {
        font-size: 28rpx;
    }
}
</style>
