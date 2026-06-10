<template>
    <base-card class="staff-card" variant="list" :interactive="true" @click="handleClick">
        <view class="staff-card__content">
            <view class="staff-card__header">
                <image
                    class="staff-card__avatar"
                    :src="staff.avatar"
                    mode="aspectFill"
                    lazy-load
                />
                <view class="staff-card__info">
                    <view class="staff-card__name-row">
                        <text class="staff-card__name">{{ staff.name }}</text>
                        <view class="staff-card__rating">
                            <tn-icon name="star-fill" size="24" color="#C8A45D" />
                            <text class="staff-card__rating-text">{{ staff.rating }}</text>
                            <text class="staff-card__review-count">({{ staff.reviewCount }})</text>
                        </view>
                    </view>
                    <text class="staff-card__category">{{ staff.category }}</text>
                    <view class="staff-card__price-row">
                        <template
                            v-if="
                                staff.has_price !== false &&
                                staff.price !== null &&
                                staff.price !== undefined
                            "
                        >
                            <text class="staff-card__price"
                                >¥{{ staff.price_text ?? staff.price }}</text
                            >
                            <text class="staff-card__price-unit">起</text>
                        </template>
                        <text v-else class="staff-card__price staff-card__price--negotiable"
                            >面议</text
                        >
                    </view>
                </view>
            </view>

            <view v-if="staff.tags && staff.tags.length" class="staff-card__tags">
                <view
                    v-for="(tag, index) in staff.tags.slice(0, 3)"
                    :key="index"
                    class="staff-card__tag"
                >
                    {{ tag }}
                </view>
            </view>

            <view v-if="showFavorite" class="staff-card__favorite" @click.stop="handleFavorite">
                <tn-icon
                    :name="staff.isFavorite ? 'heart-fill' : 'heart'"
                    size="40"
                    :color="staff.isFavorite ? '#0B0B0B' : '#9A9388'"
                />
            </view>
        </view>
    </base-card>
</template>

<script setup lang="ts">
import BaseCard from '@/components/base/BaseCard.vue'

interface StaffData {
    id: number
    name: string
    avatar: string
    category: string
    rating: number
    reviewCount: number
    price?: number | null
    has_price?: boolean
    price_text?: string
    tags?: string[]
    isFavorite?: boolean
}

interface Props {
    staff: StaffData
    showFavorite?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    showFavorite: true
})

const emit = defineEmits<{
    (event: 'click', staff: StaffData): void
    (event: 'favorite', staff: StaffData): void
}>()

const handleClick = () => {
    emit('click', props.staff)
}

const handleFavorite = () => {
    emit('favorite', props.staff)
}
</script>

<script lang="ts">
export default {
    name: 'StaffCard',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.staff-card {
    position: relative;

    &__content {
        position: relative;
    }

    &__header {
        display: flex;
        gap: 18rpx;
    }

    &__avatar {
        width: 96rpx;
        height: 96rpx;
        border-radius: var(--wm-radius-card-soft, 20rpx);
        flex-shrink: 0;
        border: 2rpx solid rgba(255, 255, 255, 0.92);
        box-shadow: 0 6rpx 14rpx rgba(17, 17, 17, 0.1);
    }

    &__info {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
        min-width: 0;
        padding-right: 54rpx;
    }

    &__name-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8rpx;
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: 32rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #111111);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__rating {
        display: flex;
        align-items: center;
        gap: 4rpx;
    }

    &__rating-text {
        font-size: 26rpx;
        font-weight: 500;
        color: var(--wm-text-primary, #111111);
    }

    &__review-count {
        font-size: 24rpx;
        color: var(--wm-text-tertiary, #9a9388);
    }

    &__category {
        font-size: 26rpx;
        color: var(--wm-text-secondary, #56524a);
    }

    &__price-row {
        display: flex;
        align-items: baseline;
        gap: 4rpx;
    }

    &__price {
        font-size: 32rpx;
        font-weight: 800;
        color: var(--wm-color-price, var(--wm-color-primary, #0b0b0b));

        &--negotiable {
            color: var(--wm-text-tertiary, #9a9388);
        }
    }

    &__price-unit {
        font-size: 24rpx;
        color: var(--wm-color-secondary, #c8a45d);
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8rpx;
        margin-top: 12rpx;
    }

    &__tag {
        padding: 6rpx 14rpx;
        background: var(--wm-color-secondary-soft, #f8f1e1);
        color: var(--wm-color-primary, #0b0b0b);
        font-size: 22rpx;
        border: 1rpx solid rgba(200, 164, 93, 0.28);
        border-radius: var(--wm-radius-pill, 999rpx);
    }

    &__favorite {
        position: absolute;
        top: 0;
        right: 0;
        width: 58rpx;
        height: 58rpx;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999rpx;
        background: rgba(255, 255, 255, 0.92);
        border: 1rpx solid rgba(232, 224, 210, 0.88);
        transition: transform var(--wm-motion-base, 220ms) ease;

        &:active {
            transform: scale(1.2);
        }
    }
}
</style>
