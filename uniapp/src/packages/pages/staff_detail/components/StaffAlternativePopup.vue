<template>
    <view>
        <BaseOverlayMask
            :show="modelValue"
            :closeable="!querying"
            @close="closePopup"
        />

        <tn-popup
            :value="modelValue"
            open-direction="bottom"
            :overlay="false"
            :overlay-closeable="!querying"
            safe-area-inset-bottom
            :radius="28"
            @input="onPopupInput"
        >
            <view class="alternative-popup">
                <view class="alternative-popup__header">
                    <view class="alternative-popup__badge">
                        <text class="alternative-popup__badge-text">档期提醒</text>
                    </view>

                    <text class="alternative-popup__title">该日期暂不可预约</text>

                    <text class="alternative-popup__desc">
                        {{ reason || '当前档期暂不可预约' }}
                    </text>
                </view>

                <view v-if="loading" class="alternative-popup__loading">
                    <tn-loading mode="circle" />
                </view>

                <scroll-view
                    v-else-if="list.length"
                    scroll-y
                    class="alternative-popup__scroll"
                >
                    <view class="alternative-popup__list">
                        <view
                            v-for="item in list"
                            :key="item.id"
                            class="alternative-card"
                            @click="emit('select', item)"
                        >
                            <image
                                class="alternative-card__avatar"
                                :src="item.avatar || '/static/images/user/default_avatar.png'"
                                mode="aspectFill"
                                lazy-load
                            />

                            <view class="alternative-card__content">
                                <view class="alternative-card__head">
                                    <view class="alternative-card__name-group">
                                        <text class="alternative-card__name">
                                            {{ item.name || '未命名人员' }}
                                        </text>

                                        <text
                                            v-if="item.is_recommend"
                                            class="alternative-card__badge"
                                        >
                                            推荐
                                        </text>
                                    </view>

                                    <text class="alternative-card__price">
                                        {{ formatAlternativePrice(item) }}
                                    </text>
                                </view>

                                <text class="alternative-card__role">
                                    {{ formatAlternativeRoleLine(item) }}
                                </text>

                                <view
                                    v-if="getAlternativeStaffTags(item).length"
                                    class="alternative-card__tags"
                                >
                                    <text
                                        v-for="tag in getAlternativeStaffTags(item)"
                                        :key="`${item.id}-${tag}`"
                                        class="alternative-card__tag"
                                    >
                                        {{ tag }}
                                    </text>
                                </view>

                                <text v-else-if="item.profile" class="alternative-card__desc">
                                    {{ item.profile }}
                                </text>

                                <view class="alternative-card__footer">
                                    <view class="alternative-card__score">
                                        <BaseIcon name="star-fill" size="20" color="#C8A45D" />

                                        <text class="alternative-card__score-text">
                                            {{ formatAlternativeRating(item) }}
                                        </text>
                                    </view>

                                    <text class="alternative-card__orders">
                                        已服务{{ item.order_count || 0 }}对
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>

                <view v-else class="alternative-popup__empty">
                    <text class="alternative-popup__empty-title">暂无可替代人员</text>

                    <text class="alternative-popup__empty-desc">
                        当前日期下暂无同类可预约人员。
                    </text>
                </view>

                <view class="alternative-popup__actions">
                    <view
                        class="alternative-popup__btn alternative-popup__btn--ghost"
                        @click="emit('pick-date')"
                    >
                        <text class="alternative-popup__btn-text">重新选日期</text>
                    </view>

                    <view
                        v-if="list.length"
                        class="alternative-popup__btn alternative-popup__btn--primary"
                        :style="primaryBtnStyle"
                        @click="closePopup"
                    >
                        <text class="alternative-popup__btn-text alternative-popup__btn-text--primary">
                            关闭
                        </text>
                    </view>

                    <view
                        v-else
                        class="alternative-popup__btn alternative-popup__btn--primary"
                        :style="primaryBtnStyle"
                        @click="emit('join-waitlist')"
                    >
                        <text class="alternative-popup__btn-text alternative-popup__btn-text--primary">
                            {{ querying ? '处理中...' : '加入候补' }}
                        </text>
                    </view>
                </view>
            </view>
        </tn-popup>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'

interface Props {
    modelValue: boolean
    loading?: boolean
    querying?: boolean
    reason?: string
    list?: any[]
    themeColor?: string
}

const props = withDefaults(defineProps<Props>(), {
    loading: false,
    querying: false,
    reason: '',
    list: () => [],
    themeColor: '#181614'
})

const emit = defineEmits<{
    (e: 'update:modelValue', val: boolean): void
    (e: 'select', item: any): void
    (e: 'pick-date'): void
    (e: 'join-waitlist'): void
}>()

const primaryBtnStyle = computed(() => ({
    background: props.themeColor || '#181614'
}))

const closePopup = () => {
    emit('update:modelValue', false)
}

const onPopupInput = (val: boolean) => {
    emit('update:modelValue', val)
}

const formatAlternativePrice = (item: any) => {
    if (item?.has_price === false) return '面议'
    const price = item?.price_text || item?.price
    if (price === null || price === undefined || price === '') return '面议'
    return `¥${price}/次起`
}

const formatAlternativeRoleLine = (item: any) => {
    const role = item?.category_name || item?.category?.name || '服务人员'
    const exp = Number(item?.experience_years || 0)
    return exp > 0 ? `${role} · ${exp}年经验` : role
}

const getAlternativeStaffTags = (item: any) => {
    if (Array.isArray(item?.tags)) {
        return item.tags.slice(0, 3)
    }
    return []
}

const formatAlternativeRating = (item: any) => {
    const rating = Number(item?.rating || 0)
    return rating > 0 ? rating.toFixed(1) : '5.0'
}
</script>

<style lang="scss" scoped>
.alternative-popup {
    padding: 36rpx 32rpx calc(36rpx + env(safe-area-inset-bottom));
    background: #FAF8F2;
    border-top-left-radius: 32rpx;
    border-top-right-radius: 32rpx;
    box-sizing: border-box;

    &__header {
        text-align: center;
        margin-bottom: 24rpx;
    }

    &__badge {
        display: inline-block;
        background: #FAF3E5;
        border-radius: 30rpx;
        padding: 6rpx 20rpx;
        margin-bottom: 12rpx;
    }

    &__badge-text {
        font-size: 22rpx;
        color: #8A6D3B;
        font-weight: 600;
    }

    &__title {
        font-size: 34rpx;
        font-weight: 700;
        color: #181614;
    }

    &__desc {
        font-size: 24rpx;
        color: #8E8880;
        margin-top: 8rpx;
    }

    &__loading {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60rpx 0;
    }

    &__scroll {
        max-height: 55vh;
    }

    &__list {
        display: flex;
        flex-direction: column;
        gap: 18rpx;
    }

    &__empty {
        text-align: center;
        padding: 60rpx 0;
    }

    &__empty-title {
        font-size: 28rpx;
        font-weight: 700;
        color: #181614;
    }

    &__empty-desc {
        font-size: 24rpx;
        color: #8E8880;
        margin-top: 8rpx;
    }

    &__actions {
        display: flex;
        gap: 16rpx;
        margin-top: 28rpx;
    }

    &__btn {
        flex: 1;
        height: 80rpx;
        border-radius: 40rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        &--ghost {
            background: #FFFFFF;
            border: 1rpx solid #D8D3C7;
        }

        &--primary {
            background: #181614;
        }
    }

    &__btn-text {
        font-size: 28rpx;
        font-weight: 600;
        color: #181614;

        &--primary {
            color: #FAF8F2;
        }
    }
}

.alternative-card {
    display: flex;
    gap: 20rpx;
    background: #FFFFFF;
    border-radius: 20rpx;
    padding: 22rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.2);

    &__avatar {
        width: 120rpx;
        height: 120rpx;
        border-radius: 16rpx;
        background: #F2EFE9;
        flex-shrink: 0;
    }

    &__content {
        flex: 1;
        min-width: 0;
    }

    &__head {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    &__name-group {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__name {
        font-size: 28rpx;
        font-weight: 700;
        color: #181614;
    }

    &__badge {
        font-size: 20rpx;
        background: #FAF3E5;
        color: #8A6D3B;
        padding: 2rpx 10rpx;
        border-radius: 6rpx;
    }

    &__price {
        font-size: 28rpx;
        font-weight: 700;
        color: #C6A15B;
    }

    &__role {
        font-size: 22rpx;
        color: #8E8880;
        margin-top: 6rpx;
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8rpx;
        margin-top: 10rpx;
    }

    &__tag {
        background: #FAF6EE;
        border-radius: 6rpx;
        padding: 4rpx 10rpx;
        font-size: 20rpx;
        color: #75561E;
    }

    &__desc {
        font-size: 22rpx;
        color: #8E8880;
        margin-top: 8rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 12rpx;
    }

    &__score {
        display: flex;
        align-items: center;
        gap: 6rpx;
    }

    &__score-text {
        font-size: 22rpx;
        font-weight: 700;
        color: #C6A15B;
    }

    &__orders {
        font-size: 22rpx;
        color: #9E9890;
    }
}
</style>
