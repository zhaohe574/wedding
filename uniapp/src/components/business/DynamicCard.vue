<template>
    <view class="dynamic-card" :class="cardClass" :style="cardStyle" @click="handleCardClick">
        <view class="dynamic-card__header">
            <view class="dynamic-card__author">
                <view class="dynamic-card__avatar-wrap" @click.stop="handleUserClick">
                    <image class="dynamic-card__avatar" :src="avatarSrc" mode="aspectFill" />
                </view>
                <view class="dynamic-card__author-main">
                    <view class="dynamic-card__name-row">
                        <text class="dynamic-card__name">{{ dynamic.user.nickname }}</text>
                        <view
                            v-if="dynamic.user.roleLabel"
                            class="dynamic-card__role-badge"
                            :class="{
                                'dynamic-card__role-badge--official':
                                    dynamic.user.roleLabel === '官方'
                            }"
                        >
                            {{ dynamic.user.roleLabel }}
                        </view>
                    </view>
                    <view class="dynamic-card__meta-row">
                        <text class="dynamic-card__meta-text">{{
                            formatTime(dynamic.createTime)
                        }}</text>
                        <template v-if="dynamic.location?.name">
                            <text class="dynamic-card__meta-dot">·</text>
                            <view class="dynamic-card__location">
                                <tn-icon name="location" size="20" color="#98A2B3" />
                                <text class="dynamic-card__meta-text">{{
                                    dynamic.location.name
                                }}</text>
                            </view>
                        </template>
                    </view>
                </view>
            </view>

            <follow-button
                v-if="showFollowButton"
                :is-followed="dynamic.user.isFollowed"
                size="sm"
                @click="handleFollow"
            />
        </view>

        <view v-if="showTypeBadge || displayTopics.length" class="dynamic-card__tag-row">
            <view v-if="showTypeBadge" class="dynamic-card__tag dynamic-card__tag--type">
                {{ dynamic.dynamicTypeLabel }}
            </view>
            <view
                v-for="topic in displayTopics"
                :key="topic.id"
                class="dynamic-card__tag"
                @click="handleTopicClick(topic)"
            >
                #{{ topic.name }}
            </view>
        </view>

        <text v-if="dynamic.content" class="dynamic-card__content">{{ truncatedContent }}</text>

        <view v-if="displayedImages.length" class="dynamic-card__media" :class="mediaGridClass">
            <view
                v-for="(image, index) in displayedImages"
                :key="`${dynamic.id}-${index}`"
                class="dynamic-card__media-item"
                @click.stop="handleImagePreview(index)"
            >
                <image class="dynamic-card__media-image" :src="image" mode="aspectFill" />
                <view
                    v-if="index === 0 && dynamic.dynamicType === 2"
                    class="dynamic-card__video-badge"
                    :style="videoBadgeStyle"
                >
                    <tn-icon name="play-fill" size="24" color="#FFFFFF" />
                    <text>播放</text>
                </view>
                <view
                    v-if="hiddenImageCount > 0 && index === displayedImages.length - 1"
                    class="dynamic-card__media-mask"
                >
                    +{{ hiddenImageCount }}
                </view>
            </view>
        </view>

        <view class="dynamic-card__footer">
            <view class="dynamic-card__stats">
                <view class="dynamic-card__stat">
                    <tn-icon name="eye" size="22" color="#98A2B3" />
                    <text>{{ formatCount(dynamic.viewCount) }} 浏览</text>
                </view>
                <view class="dynamic-card__stat">
                    <tn-icon name="chat" size="22" color="#98A2B3" />
                    <text>{{ formatCount(dynamic.commentCount) }} 评论</text>
                </view>
                <view class="dynamic-card__stat" :class="{ 'is-active': dynamic.isLiked }">
                    <tn-icon
                        :name="dynamic.isLiked ? 'like-fill' : 'like'"
                        size="22"
                        :color="dynamic.isLiked ? themeStore.primaryColor : '#98A2B3'"
                    />
                    <text>{{ formatCount(dynamic.likeCount) }} 点赞</text>
                </view>
            </view>

            <view class="dynamic-card__actions">
                <view
                    class="dynamic-card__action dynamic-card__action--ghost"
                    @click.stop="handleComment"
                >
                    评论
                </view>
                <view
                    class="dynamic-card__action"
                    :class="
                        dynamic.isLiked
                            ? 'dynamic-card__action--active'
                            : 'dynamic-card__action--primary'
                    "
                    :style="dynamic.isLiked ? undefined : primaryActionStyle"
                    @click.stop="handleLike"
                >
                    <tn-icon
                        :name="dynamic.isLiked ? 'like-fill' : 'like'"
                        size="22"
                        :color="
                            dynamic.isLiked
                                ? 'var(--color-primary, #7C3AED)'
                                : 'var(--color-btn-text, #FFFFFF)'
                        "
                    />
                    <text>{{ dynamic.isLiked ? '已赞' : '点赞' }}</text>
                </view>
                <view
                    v-if="showShare"
                    class="dynamic-card__icon-action"
                    @click.stop="handleMore"
                >
                    <tn-icon name="share" size="24" color="#667085" />
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { DynamicCardData } from '@/utils/dynamic'
import FollowButton from './FollowButton.vue'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'

interface Props {
    dynamic: DynamicCardData
    variant?: 'default' | 'plaza-unified'
    showShare?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    showShare: true
})
const themeStore = useThemeStore()

const emit = defineEmits([
    'click',
    'userClick',
    'follow',
    'topicClick',
    'imageClick',
    'like',
    'comment',
    'share'
])

const avatarSrc = computed(
    () => props.dynamic.user.avatar || '/static/images/user/default_avatar.png'
)
const isPlazaUnified = computed(() => props.variant === 'plaza-unified')
const cardClass = computed(() => ({
    'dynamic-card--plaza-unified': isPlazaUnified.value
}))

const displayTopics = computed(() => props.dynamic.topics?.slice(0, 4) || [])

const showTypeBadge = computed(() => props.dynamic.dynamicType !== 1)

const showFollowButton = computed(() => props.dynamic.user.canFollow)

const truncatedContent = computed(() => {
    const content = props.dynamic.content || ''
    const maxLength = 220
    if (content.length > maxLength) {
        return `${content.slice(0, maxLength)}...`
    }
    return content
})

const displayedImages = computed(() => props.dynamic.images?.slice(0, 4) || [])

const hiddenImageCount = computed(() => Math.max(0, (props.dynamic.images?.length || 0) - 4))

const mediaGridClass = computed(() => {
    const count = Math.max(1, Math.min(displayedImages.value.length, 4))
    return `dynamic-card__media--${count}`
})

const primaryColor = computed(() => themeStore.primaryColor || '#7C3AED')
const primarySoftColor = computed(() => alphaColor(primaryColor.value, 0.1))
const primarySoftBorderColor = computed(() => alphaColor(primaryColor.value, 0.28))
const primaryShadowColor = computed(() =>
    alphaColor(primaryColor.value, isPlazaUnified.value ? 0.24 : 0.18)
)

const cardStyle = computed(() => ({
    boxShadow: isPlazaUnified.value
        ? '0 8rpx 22rpx rgba(15, 23, 42, 0.08)'
        : `0 10rpx 28rpx ${alphaColor(primaryColor.value, 0.08)}`,
    '--dynamic-card-primary-soft': primarySoftColor.value,
    '--dynamic-card-primary-soft-border': primarySoftBorderColor.value,
    '--dynamic-card-primary-shadow': primaryShadowColor.value
}))

const primaryActionStyle = computed(() => ({
    boxShadow: `0 8rpx 18rpx ${primaryShadowColor.value}`
}))

const videoBadgeStyle = computed(() => ({
    boxShadow: `0 6rpx 18rpx ${primaryShadowColor.value}`
}))

const formatTime = (time: string): string => {
    const value = String(time || '').trim()
    if (!value) {
        return '刚刚'
    }

    const iosCompatibleTime = value.includes('T') ? value : value.replace(' ', 'T')
    const createTime = new Date(iosCompatibleTime).getTime()
    if (Number.isNaN(createTime)) {
        return value.split(' ')[0] || value
    }

    const diff = Date.now() - createTime
    const minute = 60 * 1000
    const hour = 60 * minute
    const day = 24 * hour

    if (diff < minute) return '刚刚'
    if (diff < hour) return `${Math.floor(diff / minute)}分钟前`
    if (diff < day) return `${Math.floor(diff / hour)}小时前`
    if (diff < 7 * day) return `${Math.floor(diff / day)}天前`
    return value.split(' ')[0] || value
}

const formatCount = (count: number): string => {
    const value = Number(count || 0)
    if (value < 10000) {
        return `${value}`
    }
    const formatted = (value / 10000).toFixed(value >= 100000 ? 0 : 1)
    return `${formatted.replace(/\.0$/, '')}万`
}

const handleCardClick = () => {
    emit('click', props.dynamic)
}

const handleUserClick = () => {
    if (props.dynamic.user.id > 0) {
        emit('userClick', props.dynamic.user.id)
    }
}

const handleFollow = () => {
    if (props.dynamic.user.id > 0) {
        emit('follow', props.dynamic.user.id)
    }
}

const handleTopicClick = (topic: { id: number; name: string }) => {
    emit('topicClick', topic)
}

const handleImagePreview = (index: number) => {
    emit('imageClick', index)
    if (!props.dynamic.images?.length) {
        return
    }
    uni.previewImage({
        urls: props.dynamic.images,
        current: props.dynamic.images[index] || props.dynamic.images[0]
    })
}

const handleMore = () => {
    emit('share', props.dynamic)
}

const handleLike = () => {
    emit('like', props.dynamic)
}

const handleComment = () => {
    emit('comment', props.dynamic)
}
</script>

<script lang="ts">
export default {
    name: 'DynamicCard',
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.dynamic-card {
    background: #ffffff;
    border-radius: 24rpx;
    border: 1rpx solid #edf0f4;
    overflow: hidden;
    transition: all 0.2s ease;

    &:active {
        transform: translateY(-2rpx);
    }

    &__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
        padding: 24rpx 24rpx 0;
    }

    &__author {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__avatar {
        width: 88rpx;
        height: 88rpx;
        border-radius: 50%;
        background: #f3f4f6;
        border: 2rpx solid #ffffff;
        box-shadow: 0 8rpx 18rpx rgba(15, 23, 42, 0.12);
    }

    &__avatar-wrap {
        flex-shrink: 0;
    }

    &__author-main {
        flex: 1;
        min-width: 0;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__name {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 30rpx;
        font-weight: 700;
        color: #1f2937;
    }

    &__role-badge {
        flex-shrink: 0;
        padding: 6rpx 14rpx;
        border-radius: 999rpx;
        background: var(--color-primary-light-9, #faf5ff);
        border: 1rpx solid var(--color-primary-light-7, #ddd6fe);
        font-size: 22rpx;
        font-weight: 600;
        color: var(--color-primary, #7c3aed);

        &--official {
            background: #fff4e5;
            border-color: #ffd8a8;
            color: #d97706;
        }
    }

    &__meta-row {
        display: flex;
        align-items: center;
        gap: 8rpx;
        min-width: 0;
        margin-top: 10rpx;
    }

    &__meta-text {
        font-size: 24rpx;
        color: #98a2b3;
        line-height: 1.4;
    }

    &__meta-dot {
        color: #cbd5e1;
        font-size: 24rpx;
    }

    &__location {
        min-width: 0;
        display: inline-flex;
        align-items: center;
        gap: 6rpx;

        text {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    &__tag-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10rpx;
        padding: 18rpx 24rpx 0;
    }

    &__tag {
        max-width: 100%;
        padding: 8rpx 16rpx;
        border-radius: 999rpx;
        background: var(--color-primary-light-9, #faf5ff);
        border: 1rpx solid var(--color-primary-light-7, #ddd6fe);
        font-size: 24rpx;
        font-weight: 500;
        color: var(--color-primary, #7c3aed);
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;

        &--type {
            background: #f7f8fb;
            border-color: #e5e7eb;
            color: #4b5563;
            font-weight: 600;
        }
    }

    &__content {
        display: block;
        padding: 18rpx 24rpx 0;
        font-size: 28rpx;
        line-height: 1.7;
        color: #475467;
        word-break: break-all;
    }

    &__media {
        display: grid;
        gap: 12rpx;
        padding: 20rpx 24rpx 0;

        &--1 {
            grid-template-columns: 1fr;

            .dynamic-card__media-item {
                height: 360rpx;
            }
        }

        &--2,
        &--4 {
            grid-template-columns: repeat(2, minmax(0, 1fr));

            .dynamic-card__media-item {
                height: 210rpx;
            }
        }

        &--3 {
            grid-template-columns: minmax(0, 1.16fr) minmax(0, 1fr);
            grid-template-rows: repeat(2, 164rpx);

            .dynamic-card__media-item:first-child {
                grid-row: 1 / span 2;
                height: 340rpx;
            }

            .dynamic-card__media-item:not(:first-child) {
                height: 164rpx;
            }
        }
    }

    &__media-item {
        position: relative;
        overflow: hidden;
        border-radius: 20rpx;
        background: #f3f4f6;
    }

    &__media-image {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__video-badge {
        position: absolute;
        left: 16rpx;
        bottom: 16rpx;
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 10rpx 16rpx;
        border-radius: 999rpx;
        background: linear-gradient(
            135deg,
            var(--color-primary, #7c3aed) 0%,
            var(--color-primary-dark-2, #6d28d9) 100%
        );
        color: #ffffff;
        font-size: 22rpx;
        font-weight: 600;
    }

    &__media-mask {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.5);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32rpx;
        font-weight: 700;
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        flex-wrap: wrap;
        margin-top: 24rpx;
        padding: 22rpx 24rpx;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
        border-top: 1rpx solid #eef1f5;
    }

    &__stats {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 18rpx;
        flex-wrap: wrap;
    }

    &__stat {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        font-size: 24rpx;
        color: #98a2b3;

        &.is-active {
            color: var(--color-primary, #7c3aed);
            font-weight: 600;
        }
    }

    &__actions {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        margin-left: auto;
    }

    &__action {
        height: 76rpx;
        border-radius: 999rpx;
        padding: 0 24rpx;
        border: 1rpx solid #e5e7eb;
        background: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
        font-size: 24rpx;
        font-weight: 600;
        color: #4b5563;
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
        }

        &--ghost {
            background: #ffffff;
        }

        &--primary {
            border-color: transparent;
            background: linear-gradient(
                135deg,
                var(--color-primary, #7c3aed) 0%,
                var(--color-primary-dark-2, #6d28d9) 100%
            );
            color: var(--color-btn-text, #ffffff);
        }

        &--active {
            border-color: var(--color-primary-light-7, #ddd6fe);
            background: var(--color-primary-light-9, #faf5ff);
            color: var(--color-primary, #7c3aed);
        }
    }

    &__icon-action {
        width: 76rpx;
        height: 76rpx;
        border-radius: 50%;
        border: 1rpx solid #e5e7eb;
        background: #ffffff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
            background: #f8fafc;
        }
    }
}

.dynamic-card--plaza-unified {
    .dynamic-card__header {
        padding: 24rpx 24rpx 0;
    }

    .dynamic-card__author {
        gap: 20rpx;
    }

    .dynamic-card__name {
        font-size: 32rpx;
    }

    .dynamic-card__meta-row {
        margin-top: 12rpx;
    }

    .dynamic-card__tag-row {
        padding: 16rpx 24rpx 0;
    }

    .dynamic-card__tag {
        background: var(--dynamic-card-primary-soft);
        border-color: var(--dynamic-card-primary-soft-border);
        color: var(--color-primary, #7c3aed);
        font-weight: 500;

        &--type {
            background: #f7f8fb;
            border-color: #e7ebf1;
            color: #4b5563;
            font-weight: 600;
        }
    }

    .dynamic-card__content {
        padding: 16rpx 24rpx 0;
        font-size: 26rpx;
        line-height: 1.62;
        color: #667085;
    }

    .dynamic-card__media {
        padding: 20rpx 24rpx 0;
    }

    .dynamic-card__footer {
        gap: 18rpx;
        padding: 22rpx 24rpx;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
        border-top: 1rpx solid #eef1f5;
    }

    .dynamic-card__stats {
        gap: 20rpx;
    }

    .dynamic-card__action {
        height: 80rpx;
        padding: 0 26rpx;
    }

    .dynamic-card__action--active {
        border-color: var(--dynamic-card-primary-soft-border);
        background: var(--dynamic-card-primary-soft);
    }

    .dynamic-card__icon-action {
        width: 80rpx;
        height: 80rpx;
    }
}
</style>
