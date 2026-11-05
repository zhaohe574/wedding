<template>
    <view class="detail-page">
        <view v-if="loading" class="page-pad detail-page__loading">
            <base-skeleton :rows="4" />
        </view>

        <base-empty v-else-if="error" status="error" error-kind="network" fixed @retry="load" />

        <template v-else>
            <!-- 头图 -->
            <swiper v-if="banners.length" class="banner" :circular="true" :indicator-dots="banners.length > 1">
                <swiper-item v-for="(b, i) in banners" :key="i">
                    <image class="banner__img" :src="imageUrl(b.image || b.url)" mode="aspectFill" />
                </swiper-item>
            </swiper>
            <view v-else class="banner banner--empty">
                <image class="banner__img" :src="imageUrl(staff.avatar)" mode="aspectFill" />
            </view>

            <view class="page-pad body">
                <!-- 资料 -->
                <base-card class="profile">
                    <view class="profile__head">
                        <image class="profile__avatar" :src="imageUrl(staff.avatar)" mode="aspectFill" />
                        <view class="profile__info">
                            <text class="profile__name">{{ staff.name }}</text>
                            <view class="profile__meta">
                                <text v-if="staff.rating" class="profile__rating">★ {{ staff.rating }}</text>
                                <text v-if="staff.order_count" class="profile__muted">{{ staff.order_count }} 单</text>
                                <text v-if="staff.experience_years" class="profile__muted">
                                    {{ staff.experience_years }} 年经验
                                </text>
                            </view>
                        </view>
                        <view class="profile__fav" @tap.stop="handleFavorite">
                            <u-icon
                                :name="isFavorite ? 'star-fill' : 'star'"
                                :size="40"
                                :color="isFavorite ? 'var(--c-brand)' : 'var(--c-ink-3)'"
                            />
                        </view>
                    </view>

                    <view v-if="tags.length" class="tags">
                        <text v-for="(t, i) in tags" :key="i" class="tags__item">{{ t }}</text>
                    </view>

                    <text v-if="staff.profile" class="profile__desc">{{ staff.profile }}</text>
                </base-card>

                <!-- 档期状态 -->
                <view class="avail" :class="available ? 'is-on' : 'is-off'">
                    <u-icon
                        :name="available ? 'checkmark-circle' : 'close-circle'"
                        :size="32"
                        :color="available ? 'var(--c-success)' : 'var(--c-danger)'"
                    />
                    <text class="avail__text">{{ availText }}</text>
                    <text v-if="date" class="avail__date">{{ date }}</text>
                </view>

                <!-- 套餐 -->
                <section-header title="服务套餐" :subtitle="packages.length ? `${packages.length} 个可选` : ''" />
                <view v-if="packages.length" class="pkg-list">
                    <base-card
                        v-for="p in packages"
                        :key="p.package_id || p.id"
                        clickable
                        :selected="selectedPackageId === (p.package_id || p.id)"
                        @tap="selectPackage(p)"
                    >
                        <view class="pkg">
                            <view class="pkg__body">
                                <text class="pkg__name ellipsis">{{ p.package?.name || p.name }}</text>
                                <text v-if="p.duration_desc" class="pkg__meta">{{ p.duration_desc }}</text>
                                <text v-if="p.package?.description" class="pkg__desc ellipsis-2">
                                    {{ p.package.description }}
                                </text>
                            </view>
                            <view class="pkg__right">
                                <price size="md" :content="p.price ?? p.package?.price ?? 0" />
                                <view class="pkg__check" :class="{ 'is-on': selectedPackageId === (p.package_id || p.id) }">
                                    <u-icon
                                        v-if="selectedPackageId === (p.package_id || p.id)"
                                        name="checkmark"
                                        :size="24"
                                        color="var(--c-on-brand)"
                                    />
                                </view>
                            </view>
                        </view>
                    </base-card>
                </view>
                <base-empty v-else status="empty" empty-text="暂无可预约套餐" />

                <!-- 作品 -->
                <template v-if="works.length">
                    <section-header title="作品案例" :subtitle="`${works.length} 组`" />
                    <scroll-view class="works" scroll-x :show-scrollbar="false">
                        <view class="works__row">
                            <image
                                v-for="w in works"
                                :key="w.id"
                                class="works__item"
                                :src="imageUrl(w.cover || w.image)"
                                mode="aspectFill"
                            />
                        </view>
                    </scroll-view>
                </template>

                <!-- 评价 -->
                <section-header title="用户评价" :subtitle="reviewTotal ? `${reviewTotal} 条` : ''" />
                <view v-if="reviews.length" class="reviews">
                    <view v-for="r in reviews" :key="r.id" class="review">
                        <view class="review__head">
                            <text class="review__user">{{ r.user?.nickname || '匿名用户' }}</text>
                            <text class="review__score">★ {{ r.score }}</text>
                        </view>
                        <text class="review__content ellipsis-2">{{ r.content }}</text>
                    </view>
                </view>
                <base-empty v-else status="empty" empty-text="还没有评价" />

                <view class="body__space" />
            </view>

            <!-- 底部操作 -->
            <view class="action-bar">
                <view class="action-bar__price">
                    <text class="action-bar__label">起价</text>
                    <price size="md" :content="staff.price ?? 0" />
                </view>
                <base-button
                    text="立即预约"
                    variant="brand"
                    size="md"
                    :block="false"
                    :disabled="!available"
                    @click="goBooking"
                />
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/**
 * 人员详情。
 *
 * /api/v2/staff/:id 未上线时降级为 3 个老接口并发（detail + works + reviews），
 * 不再像老端那样串行等待 6 个请求。
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getStaffDetail, getStaffDetailV2, getStaffWorks, toggleStaffFavorite } from '@/api/catalog'
import { getStaffReviews } from '@/api/content'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'

const appStore = useAppStore()
const userStore = useUserStore()

const staffId = ref(0)
const date = ref('')
const loading = ref(true)
const error = ref(false)

const staff = ref<any>({})
const banners = ref<any[]>([])
const tags = ref<string[]>([])
const packages = ref<any[]>([])
const works = ref<any[]>([])
const reviews = ref<any[]>([])
const reviewTotal = ref(0)
const isFavorite = ref(false)
const selectedPackageId = ref(0)

const available = computed(() => staff.value.schedule_available !== false)
const availText = computed(() => {
    if (staff.value.schedule_message) return String(staff.value.schedule_message)
    return available.value ? '该日期可预约' : '该日期已被预约'
})

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function selectPackage(p: any) {
    selectedPackageId.value = p.package_id || p.id
}

async function load() {
    loading.value = true
    error.value = false
    try {
        // 优先 v2 聚合（1 次请求）
        try {
            const v2 = await getStaffDetailV2(staffId.value, { date: date.value || undefined })
            applyV2(v2)
            return
        } catch {
            // v2 未上线或异常：降级到 3 个 v1 接口并发
        }

        const detail = await getStaffDetail({ id: staffId.value, date: date.value || undefined })
        if (!detail || !detail.id) throw new Error('empty')

        staff.value = detail
        banners.value = Array.isArray(detail.banners) ? detail.banners : []
        tags.value = Array.isArray(detail.tags) ? detail.tags : []
        packages.value = Array.isArray(detail.packages) ? detail.packages : []
        isFavorite.value = Number(detail.is_favorite) === 1
        if (packages.value.length) selectedPackageId.value = packages.value[0].package_id || packages.value[0].id

        const [worksRes, reviewRes] = await Promise.allSettled([
            getStaffWorks({ staff_id: staffId.value }),
            getStaffReviews({ staff_id: staffId.value, page: 1, page_size: 3 })
        ])

        if (worksRes.status === 'fulfilled') {
            const raw = worksRes.value as any
            works.value = Array.isArray(raw) ? raw : raw?.lists || raw?.data || []
        }
        if (reviewRes.status === 'fulfilled') {
            const raw = reviewRes.value as any
            reviews.value = raw?.lists || raw?.data || (Array.isArray(raw) ? raw : [])
            reviewTotal.value = Number(raw?.count ?? raw?.total ?? reviews.value.length)
        }
    } catch {
        error.value = true
        report('api_error', { endpoint: 'staff.detail', message: '人员详情加载失败' })
    } finally {
        loading.value = false
    }
}

/** v2 聚合结构 → 页面内部结构 */
function applyV2(v2: any) {
    const s = v2?.staff || {}
    staff.value = {
        ...s,
        schedule_available: v2?.availability?.is_available !== false,
        schedule_message: v2?.availability?.message || ''
    }
    banners.value = Array.isArray(v2?.banner?.list) ? v2.banner.list : []
    tags.value = Array.isArray(s.tags) ? s.tags : []
    packages.value = Array.isArray(v2?.packages) ? v2.packages : []
    works.value = Array.isArray(v2?.works?.list) ? v2.works.list : []
    reviews.value = Array.isArray(v2?.review?.list) ? v2.review.list : []
    reviewTotal.value = Number(v2?.review?.total ?? reviews.value.length)
    isFavorite.value = Number(s.is_favorite) === 1
    if (packages.value.length) {
        selectedPackageId.value = packages.value[0].package_id || packages.value[0].id
    }
}

async function handleFavorite() {
    if (!userStore.isLogin) {
        navigate({ path: '/pages/login/login' })
        return
    }
    try {
        const res = await toggleStaffFavorite({ id: staffId.value })
        isFavorite.value =
            res && typeof res.is_favorite !== 'undefined' ? Number(res.is_favorite) === 1 : !isFavorite.value
    } catch {
        // 请求层已提示
    }
}

function goBooking() {
    if (!available.value) {
        uni.showToast({ title: '该日期不可预约', icon: 'none' })
        return
    }
    navigate({
        path: '/packages/pkg-catalog/pages/booking/booking',
        query: {
            staff_id: staffId.value,
            package_id: selectedPackageId.value || '',
            date: date.value
        }
    })
}

onLoad((options: any) => {
    staffId.value = Number(options?.id || 0)
    date.value = options?.date ? String(options.date) : ''
    if (!staffId.value) {
        error.value = true
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.detail-page {
    min-height: 100vh;
    background: var(--c-bg);
    padding-bottom: 180rpx;

    &__loading {
        padding-top: var(--sp-5);
    }
}

.banner {
    height: 620rpx;

    &--empty {
        height: 480rpx;
    }

    &__img {
        width: 100%;
        height: 100%;
    }
}

.body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    margin-top: -60rpx;
    position: relative;
    z-index: 2;

    &__space {
        height: var(--sp-4);
    }
}

.profile {
    &__head {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
    }

    &__avatar {
        width: 120rpx;
        height: 120rpx;
        border-radius: var(--r-md);
        background: var(--c-neutral-bg);
    }

    &__info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-xl);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__meta {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__rating {
        font-size: var(--fs-xs);
        color: var(--c-warning);
    }

    &__muted {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__fav {
        width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin: calc(-1 * var(--sp-2));
    }

    &__desc {
        display: block;
        margin-top: var(--sp-3);
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.6;
    }
}

.tags {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-top: var(--sp-3);

    &__item {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        background: var(--c-brand-bg);
        padding: 6rpx 16rpx;
        border-radius: var(--r-pill);
    }
}

.avail {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    border-radius: var(--r-md);
    padding: var(--sp-3);
    margin-top: var(--sp-2);

    &.is-on {
        background: var(--c-success-bg);
    }

    &.is-off {
        background: var(--c-danger-bg);
    }

    &__text {
        flex: 1;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__date {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.pkg-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.pkg {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.5;
    }

    &__right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: var(--sp-2);
        flex-shrink: 0;
    }

    &__check {
        width: 32rpx;
        height: 32rpx;
        border-radius: 50%;
        border: 1rpx solid var(--c-line-strong);
        display: flex;
        align-items: center;
        justify-content: center;

        &.is-on {
            background: var(--c-brand);
            border-color: var(--c-brand);
        }
    }
}


.works {
    white-space: nowrap;

    &__row {
        display: inline-flex;
        gap: var(--sp-2);
    }

    &__item {
        width: 240rpx;
        height: 320rpx;
        border-radius: var(--r-md);
        background: var(--c-neutral-bg);
    }
}

.reviews {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.review {
    background: var(--c-surface);
    border-radius: var(--r-md);
    padding: var(--sp-3);
    display: flex;
    flex-direction: column;
    gap: 8rpx;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__user {
        font-size: var(--fs-xs);
        color: var(--c-ink);
    }

    &__score {
        font-size: var(--fs-xs);
        color: var(--c-warning);
    }

    &__content {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.5;
    }
}

.action-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4);
    padding-bottom: calc(var(--sp-3) + var(--safe-bottom));
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);

    &__price {
        display: flex;
        align-items: baseline;
        gap: 6rpx;
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
