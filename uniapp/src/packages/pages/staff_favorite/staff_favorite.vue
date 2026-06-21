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
                <BaseCard
                    v-if="loading && !hasLoaded"
                    class="staff-favorite__state"
                    variant="quiet"
                    padding="42rpx 28rpx"
                    border-radius="32rpx"
                >
                    <LoadingState text="正在同步收藏..." compact />
                </BaseCard>

                <view v-else-if="favoriteList.length" class="staff-favorite__content wm-page-stack">
                    <u-swipe-action
                        v-for="(item, index) in favoriteList"
                        :key="item.id"
                        class="staff-favorite__swipe"
                        :show="item.show"
                        :index="index"
                        :options="swipeOptions"
                        btn-width="148"
                        bg-color="transparent"
                        @click="handleCancelFavorite"
                    >
                        <BaseCard
                            class="staff-favorite-card"
                            variant="list"
                            padding="20rpx"
                            border-radius="32rpx"
                            border="1rpx solid rgba(216, 201, 173, 0.92)"
                            box-shadow="0 14rpx 32rpx rgba(74, 43, 24, 0.07)"
                        >
                            <view class="staff-favorite-card__layout">
                                <view class="staff-favorite-card__avatar-wrap">
                                    <image
                                        class="staff-favorite-card__avatar"
                                        :src="getStaffAvatar(item)"
                                        mode="aspectFill"
                                        lazy-load
                                    />
                                    <view class="staff-favorite-card__shade"></view>
                                    <StatusBadge
                                        class="staff-favorite-card__badge"
                                        tone="primary"
                                        size="xs"
                                    >
                                        已收藏
                                    </StatusBadge>
                                </view>

                                <view class="staff-favorite-card__body">
                                    <view class="staff-favorite-card__head">
                                        <view class="staff-favorite-card__identity">
                                            <text class="staff-favorite-card__name text-ellipsis">
                                                {{ item.name || '未命名人员' }}
                                            </text>
                                            <text class="staff-favorite-card__role text-ellipsis">
                                                {{ item.category_name || '服务人员' }}
                                            </text>
                                        </view>

                                        <view
                                            class="staff-favorite-card__cancel"
                                            @click.stop="handleCancelFavorite(index)"
                                        >
                                            <BaseIcon name="heart-fill" size="26" color="#D9BE82" />
                                        </view>
                                    </view>

                                    <view class="staff-favorite-card__metrics">
                                        <view class="staff-favorite-card__metric">
                                            <BaseIcon name="star-fill" size="22" color="#B8954A" />
                                            <text class="staff-favorite-card__metric-text">
                                                {{ formatRating(item) }}
                                            </text>
                                        </view>

                                        <view class="staff-favorite-card__metric">
                                            <BaseIcon name="order" size="22" color="#9A9388" />
                                            <text class="staff-favorite-card__metric-text">
                                                {{ item.order_count || 0 }} 场
                                            </text>
                                        </view>

                                        <view class="staff-favorite-card__metric">
                                            <BaseIcon name="funds" size="22" color="#9A9388" />
                                            <text class="staff-favorite-card__metric-text">
                                                {{ formatPrice(item) }}
                                            </text>
                                        </view>
                                    </view>

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

                                <view class="staff-favorite-card__actions">
                                    <BaseButton
                                        label="查看详情"
                                        variant="dark"
                                        size="mini"
                                        height="58rpx"
                                        font-size="22rpx"
                                        icon="right"
                                        icon-position="right"
                                        @click.stop="goToDetail(item.id)"
                                    />
                                </view>
                            </view>
                        </BaseCard>
                    </u-swipe-action>
                </view>

                <EmptyState
                    v-else
                    class="staff-favorite__empty"
                    title="还没有收藏服务人员"
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
import StatusBadge from '@/components/base/StatusBadge.vue'
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
            color: '#FFFFFF',
            backgroundColor: '#7D4C35'
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

    return `¥${item.price_text || item.price}`
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
    padding-top: 18rpx;
    padding-bottom: calc(36rpx + env(safe-area-inset-bottom));
}

.staff-favorite__content {
    gap: 18rpx;
}

.staff-favorite__state,
.staff-favorite__swipe,
.staff-favorite-card {
    display: block;
}

.staff-favorite-card__layout {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 176rpx minmax(0, 1fr);
    grid-template-rows: minmax(148rpx, auto) 58rpx;
    column-gap: 20rpx;
    row-gap: 14rpx;
    min-height: 228rpx;
}

.staff-favorite-card__avatar-wrap {
    position: relative;
    grid-row: 1 / 2;
    grid-column: 1 / 2;
    width: 176rpx;
    height: 176rpx;
    overflow: hidden;
    border-radius: 24rpx;
    background: linear-gradient(145deg, #fff7ec 0%, #d9be82 58%, #b8954a 100%);
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
    background: linear-gradient(180deg, rgba(25, 23, 19, 0.02) 0%, rgba(25, 23, 19, 0.34) 100%);
    pointer-events: none;
}

.staff-favorite-card__badge {
    position: absolute;
    left: 10rpx;
    top: 10rpx;
    z-index: 1;
}

.staff-favorite-card__body {
    grid-row: 1 / 2;
    grid-column: 2 / 3;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 10rpx;
    padding-top: 2rpx;
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

.staff-favorite-card__name {
    display: block;
    font-size: 31rpx;
    font-weight: 900;
    line-height: 1.25;
    color: var(--wm-text-primary, #191713);
}

.staff-favorite-card__role {
    display: block;
    font-size: 23rpx;
    font-weight: 700;
    line-height: 1.28;
    color: var(--wm-text-secondary, #665e52);
}

.staff-favorite-card__cancel {
    position: absolute;
    right: 4rpx;
    top: 78rpx;
    z-index: 2;
    flex-shrink: 0;
    width: 56rpx;
    height: 56rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: var(--wm-color-primary, #191713);
    border: 1rpx solid var(--wm-color-champagne, #d9be82);
    box-shadow: 0 10rpx 22rpx rgba(74, 43, 24, 0.12);
}

.staff-favorite-card__metrics {
    display: flex;
    align-items: center;
    gap: 8rpx;
    flex-wrap: wrap;
    padding-right: 58rpx;
}

.staff-favorite-card__metric {
    min-width: 0;
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    height: 36rpx;
    max-width: 100%;
    padding: 0 11rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(248, 242, 228, 0.82);
    border: 1rpx solid rgba(216, 201, 173, 0.56);
    box-sizing: border-box;
}

.staff-favorite-card__metric-text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 19rpx;
    font-weight: 800;
    line-height: 1;
    color: var(--wm-text-tertiary, #8a806f);
}

.staff-favorite-card__tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8rpx;
}

.staff-favorite-card__tag {
    max-width: 148rpx;
    height: 38rpx;
    padding: 0 12rpx;
    border-radius: var(--wm-radius-pill, 999rpx);
    background: rgba(217, 190, 130, 0.18);
    border: 1rpx solid rgba(217, 190, 130, 0.46);
    font-size: 20rpx;
    font-weight: 800;
    line-height: 38rpx;
    color: var(--wm-color-gold, #b8954a);
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
    margin-top: 8rpx;
}

@media screen and (max-width: 360px) {
    .staff-favorite-card {
        padding: 18rpx !important;
    }

    .staff-favorite-card__layout {
        grid-template-columns: 156rpx minmax(0, 1fr);
        grid-template-rows: minmax(138rpx, auto) auto;
        column-gap: 16rpx;
        row-gap: 12rpx;
        min-height: 214rpx;
    }

    .staff-favorite-card__avatar-wrap {
        width: 156rpx;
        height: 156rpx;
    }

    .staff-favorite-card__name {
        font-size: 28rpx;
    }

    .staff-favorite-card__cancel {
        top: 72rpx;
        right: 2rpx;
    }

    .staff-favorite-card__metrics {
        padding-right: 52rpx;
    }
}
</style>
