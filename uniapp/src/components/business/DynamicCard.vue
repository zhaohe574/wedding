<template>
    <view class="dynamic-card" :class="cardClass" :style="cardStyle" @click="handleCardClick">
        <template v-if="isEditorial">
            <view class="dynamic-card__editorial-head">
                <view class="dynamic-card__editorial-author">
                    <view class="dynamic-card__avatar-wrap" @click.stop="handleUserClick">
                        <image class="dynamic-card__avatar" :src="avatarSrc" mode="aspectFill" />
                    </view>
                    <view class="dynamic-card__editorial-author-main">
                        <view class="dynamic-card__name-row">
                            <text class="dynamic-card__name">{{ dynamic.user.nickname }}</text>
                            <view
                                v-if="dynamic.user.roleLabel"
                                class="dynamic-card__role-badge"
                                :class="{
                                    'dynamic-card__role-badge--staff':
                                        dynamic.user.roleLabel === '服务人员',
                                    'dynamic-card__role-badge--official':
                                        dynamic.user.roleLabel === '官方'
                                }"
                            >
                                {{ dynamic.user.roleLabel }}
                            </view>
                        </view>
                        <text class="dynamic-card__editorial-meta">{{ editorialMeta }}</text>
                    </view>
                </view>
            </view>

            <view
                v-if="editorialCover"
                class="dynamic-card__editorial-cover-wrap"
                @click.stop="handleMediaClick(0)"
            >
                <image
                    class="dynamic-card__editorial-cover"
                    :src="editorialCover"
                    mode="aspectFill"
                />
                <view
                    v-if="dynamic.dynamicType === 2"
                    class="dynamic-card__video-badge dynamic-card__video-badge--editorial"
                >
                    <tn-icon name="play-fill" size="24" color="#FFFFFF" />
                    <text>播放</text>
                </view>
            </view>

            <text v-if="dynamic.content" class="dynamic-card__editorial-content">
                {{ truncatedContent }}
            </text>

            <view class="dynamic-card__editorial-stats">
                <view
                    class="dynamic-card__editorial-stat dynamic-card__editorial-stat--like"
                    :class="{ 'is-active': dynamic.isLiked }"
                    @click.stop="handleLike"
                >
                    <text class="dynamic-card__editorial-stat-text">
                        赞 {{ formatCount(dynamic.likeCount) }}
                    </text>
                </view>
                <view class="dynamic-card__editorial-stat" @click.stop="handleComment">
                    <text class="dynamic-card__editorial-stat-text">
                        评论 {{ formatCount(dynamic.commentCount) }}
                    </text>
                </view>
                <view class="dynamic-card__editorial-stat">
                    <text class="dynamic-card__editorial-stat-text">
                        浏览 {{ formatCount(dynamic.viewCount) }}
                    </text>
                </view>
            </view>
        </template>

        <template v-else>
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
                                    'dynamic-card__role-badge--staff':
                                        dynamic.user.roleLabel === '服务人员',
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
                                    <tn-icon name="location" size="20" color="#9A9388" />
                                    <text class="dynamic-card__meta-text">{{
                                        dynamic.location.name
                                    }}</text>
                                </view>
                            </template>
                        </view>
                    </view>
                </view>

                <favorite-button
                    v-if="showFavoriteButton"
                    :is-favorited="dynamic.user.isFavorite"
                    size="sm"
                    @click="handleFavorite"
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
                    @click.stop="handleMediaClick(index)"
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
                        <tn-icon name="eye" size="22" color="#9A9388" />
                        <text>{{ formatCount(dynamic.viewCount) }} 浏览</text>
                    </view>
                    <view class="dynamic-card__stat">
                        <tn-icon name="chat" size="22" color="#9A9388" />
                        <text>{{ formatCount(dynamic.commentCount) }} 评论</text>
                    </view>
                    <view class="dynamic-card__stat" :class="{ 'is-active': dynamic.isLiked }">
                        <tn-icon
                            :name="dynamic.isLiked ? 'like-fill' : 'like'"
                            size="22"
                            :color="dynamic.isLiked ? themeStore.primaryColor : '#9A9388'"
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
                                    ? 'var(--color-primary, #0B0B0B)'
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
                        <tn-icon name="share" size="24" color="#5F5A50" />
                    </view>
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { DynamicCardData } from '@/utils/dynamic'
import FavoriteButton from './FavoriteButton.vue'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'

interface Props {
    dynamic: DynamicCardData
    variant?: 'default' | 'plaza-unified' | 'editorial'
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
    'favorite',
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
const isEditorial = computed(() => props.variant === 'editorial')
const cardClass = computed(() => ({
    'dynamic-card--plaza-unified': isPlazaUnified.value,
    'dynamic-card--editorial': isEditorial.value
}))

const displayTopics = computed(() => props.dynamic.topics?.slice(0, 4) || [])

const showTypeBadge = computed(() => !isEditorial.value && props.dynamic.dynamicType !== 1)

const showFavoriteButton = computed(() => !isEditorial.value && props.dynamic.user.canFavorite)

const editorialMeta = computed(() => {
    const parts = [formatTime(props.dynamic.createTime)]
    if (props.dynamic.location?.name) {
        parts.push(props.dynamic.location.name)
    }
    return parts.join(' · ')
})

const truncatedContent = computed(() => {
    const content = props.dynamic.content || ''
    const maxLength = isEditorial.value ? 44 : 220
    if (content.length > maxLength) {
        return `${content.slice(0, maxLength)}...`
    }
    return content
})

const displayedImages = computed(() =>
    isEditorial.value
        ? props.dynamic.images?.slice(0, 1) || []
        : props.dynamic.images?.slice(0, 4) || []
)

const editorialCover = computed(() => displayedImages.value[0] || '')

const hiddenImageCount = computed(() => Math.max(0, (props.dynamic.images?.length || 0) - 4))

const mediaGridClass = computed(() => {
    const count = Math.max(1, Math.min(displayedImages.value.length, 4))
    return `dynamic-card__media--${count}`
})

const primaryColor = computed(() => themeStore.primaryColor || '#0B0B0B')
const primarySoftColor = computed(() => alphaColor(primaryColor.value, 0.1))
const primarySoftBorderColor = computed(() => alphaColor(primaryColor.value, 0.28))
const primaryShadowColor = computed(() =>
    alphaColor(primaryColor.value, isPlazaUnified.value ? 0.24 : 0.18)
)

const cardStyle = computed(() => ({
    boxShadow: isEditorial.value
        ? 'var(--dynamic-editorial-card-shadow, 0 12rpx 24rpx rgba(17, 17, 17, 0.16))'
        : isPlazaUnified.value
        ? '0 8rpx 22rpx rgba(17, 17, 17, 0.08)'
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

const handleFavorite = () => {
    if (props.dynamic.user.staffId > 0) {
        emit('favorite', props.dynamic.user.staffId)
    }
}

const handleTopicClick = (topic: { id: number; name: string }) => {
    emit('topicClick', topic)
}

const handleMediaClick = (index: number) => {
    emit('imageClick', index)
    handleCardClick()
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
@import '../../styles/dynamic.scss';

.dynamic-card {
    background: var(--wm-color-bg-card, rgba(255, 255, 255, 0.9));
    border-radius: var(--wm-radius-card-glass, 26rpx);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    overflow: hidden;
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(17, 17, 17, 0.16));
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(-2rpx);
    }

    &__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--wm-space-section-gap-lg, 16rpx);
        padding: var(--wm-space-card-padding-lg, 24rpx) var(--wm-space-card-padding-lg, 24rpx) 0;
    }

    &__author {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--wm-space-section-gap-lg, 16rpx);
    }

    &__avatar {
        width: 88rpx;
        height: 88rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.82);
        border: 2rpx solid #ffffff;
        box-shadow: 0 8rpx 18rpx rgba(17, 17, 17, 0.12);
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
        gap: 8rpx;
    }

    &__name {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 30rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #111111);
    }

    &__role-badge {
        flex-shrink: 0;
        padding: 6rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(11, 11, 11, 0.08);
        border: 1rpx solid rgba(11, 11, 11, 0.16);
        font-size: 22rpx;
        font-weight: 600;
        color: var(--wm-color-primary, #0b0b0b);

        &--staff {
            color: var(--wm-color-secondary, #c8a45d);
            background: rgba(11, 11, 11, 0.1);
            border-color: rgba(11, 11, 11, 0.08);
        }

        &--official {
            background: var(--wm-color-secondary-soft, #f7f0df);
            border-color: rgba(200, 164, 93, 0.2);
            color: var(--wm-color-secondary, #c8a45d);
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
        color: var(--wm-text-secondary, #5f5a50);
        line-height: 1.4;
    }

    &__meta-dot {
        color: #d8d3c7;
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
        gap: var(--wm-space-section-gap-sm, 12rpx);
        padding: var(--wm-space-section-gap-lg, 16rpx) var(--wm-space-card-padding-lg, 24rpx) 0;
    }

    &__tag {
        max-width: 100%;
        padding: 8rpx 16rpx;
        border-radius: 999rpx;
        background: rgba(11, 11, 11, 0.08);
        border: 1rpx solid rgba(11, 11, 11, 0.16);
        font-size: 24rpx;
        font-weight: 500;
        color: var(--wm-color-primary, #0b0b0b);
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;

        &--type {
            background: rgba(255, 255, 255, 0.82);
            border-color: var(--wm-color-border, #e7e2d6);
            color: var(--wm-text-secondary, #5f5a50);
            font-weight: 600;
        }
    }

    &__content {
        display: block;
        padding: var(--wm-space-section-gap-lg, 16rpx) var(--wm-space-card-padding-lg, 24rpx) 0;
        font-size: 28rpx;
        line-height: 1.7;
        color: var(--wm-text-secondary, #5f5a50);
        word-break: break-all;
    }

    &__media {
        display: grid;
        gap: var(--wm-space-section-gap-sm, 12rpx);
        padding: var(--wm-space-card-padding, 20rpx) var(--wm-space-card-padding-lg, 24rpx) 0;

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
        border-radius: var(--wm-radius-card-soft, 20rpx);
        background: rgba(255, 255, 255, 0.86);
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
            var(--wm-color-primary, #0b0b0b) 0%,
            var(--wm-color-secondary, #c8a45d) 100%
        );
        color: #ffffff;
        font-size: 22rpx;
        font-weight: 600;
    }

    &__media-mask {
        position: absolute;
        inset: 0;
        background: rgba(17, 17, 17, 0.5);
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
        gap: var(--wm-space-section-gap-lg, 16rpx);
        flex-wrap: wrap;
        margin-top: var(--wm-space-card-padding-lg, 24rpx);
        padding: var(--wm-space-card-padding, 20rpx) var(--wm-space-card-padding-lg, 24rpx);
        background: linear-gradient(
            180deg,
            rgba(255, 255, 255, 0.96) 0%,
            rgba(248, 247, 242, 0.92) 100%
        );
        border-top: 1rpx solid var(--wm-color-border, #e7e2d6);
    }

    &__stats {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--wm-space-section-gap-lg, 16rpx);
        flex-wrap: wrap;
    }

    &__stat {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        font-size: 24rpx;
        color: var(--wm-text-secondary, #5f5a50);

        &.is-active {
            color: var(--wm-color-primary, #0b0b0b);
            font-weight: 600;
        }
    }

    &__actions {
        display: inline-flex;
        align-items: center;
        gap: var(--wm-space-section-gap-sm, 12rpx);
        margin-left: auto;
    }

    &__action {
        height: 76rpx;
        border-radius: 999rpx;
        padding: 0 24rpx;
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        background: rgba(255, 255, 255, 0.9);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
        font-size: 24rpx;
        font-weight: 600;
        color: var(--wm-text-primary, #111111);
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
        }

        &--ghost {
            background: rgba(255, 255, 255, 0.9);
        }

        &--primary {
            border-color: transparent;
            background: linear-gradient(
                135deg,
                var(--wm-color-primary, #0b0b0b) 0%,
                var(--wm-color-secondary, #c8a45d) 100%
            );
            color: var(--color-btn-text, #ffffff);
        }

        &--active {
            border-color: rgba(11, 11, 11, 0.16);
            background: rgba(11, 11, 11, 0.08);
            color: var(--wm-color-primary, #0b0b0b);
        }
    }

    &__icon-action {
        width: 76rpx;
        height: 76rpx;
        border-radius: 50%;
        border: 1rpx solid var(--wm-color-border, #e7e2d6);
        background: rgba(255, 255, 255, 0.9);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
            background: rgba(247, 240, 223, 0.72);
        }
    }
}

.dynamic-card--editorial {
    border-radius: var(--dynamic-editorial-card-radius, 26rpx);
    border-color: $dynamic-border;
    background: var(--dynamic-editorial-card-bg, rgba(255, 255, 255, 0.84));
    backdrop-filter: blur(var(--dynamic-editorial-card-blur, 14rpx));
    -webkit-backdrop-filter: blur(var(--dynamic-editorial-card-blur, 14rpx));

    .dynamic-card__editorial-head {
        padding: var(--dynamic-editorial-head-padding, 12rpx 12rpx 0);
    }

    .dynamic-card__editorial-author {
        display: flex;
        align-items: center;
        gap: var(--dynamic-editorial-author-gap, 10rpx);
    }

    .dynamic-card__editorial-author-main {
        flex: 1;
        min-width: 0;
    }

    .dynamic-card__avatar {
        width: var(--dynamic-editorial-avatar-size, 42rpx);
        height: var(--dynamic-editorial-avatar-size, 42rpx);
        border: 2rpx solid rgba(255, 255, 255, 0.92);
        box-shadow: 0 6rpx 14rpx rgba(17, 17, 17, 0.14);
    }

    .dynamic-card__name {
        font-size: var(--dynamic-editorial-name-size, 24rpx);
        color: $dynamic-text;
    }

    .dynamic-card__role-badge {
        padding: 4rpx 10rpx;
        font-size: 18rpx;
        color: #9F7A2E;
        background: rgba(11, 11, 11, 0.08);
        border-color: rgba(11, 11, 11, 0.12);
    }

    .dynamic-card__role-badge--staff {
        color: #9F7A2E;
        background: rgba(11, 11, 11, 0.08);
        border-color: rgba(11, 11, 11, 0.12);
    }

    .dynamic-card__role-badge--official {
        color: #9F7A2E;
        background: rgba(247, 240, 223, 0.3);
        border-color: rgba(159, 122, 46, 0.12);
    }

    .dynamic-card__editorial-meta {
        display: block;
        margin-top: var(--dynamic-editorial-meta-margin-top, 4rpx);
        font-size: var(--dynamic-editorial-meta-size, 20rpx);
        line-height: 1.4;
        color: $dynamic-text-muted;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dynamic-card__editorial-cover-wrap {
        position: relative;
        margin: var(--dynamic-editorial-cover-margin, 10rpx 12rpx 0);
        border-radius: var(--dynamic-editorial-cover-radius, 20rpx);
        overflow: hidden;
        background: $dynamic-soft;
    }

    .dynamic-card__editorial-cover {
        width: 100%;
        height: var(--dynamic-editorial-cover-height, 304rpx);
        display: block;
    }

    .dynamic-card__video-badge--editorial {
        left: var(--dynamic-editorial-video-badge-offset, 12rpx);
        bottom: var(--dynamic-editorial-video-badge-offset, 12rpx);
        gap: 4rpx;
        padding: var(--dynamic-editorial-video-badge-padding, 6rpx 12rpx);
        border-radius: $dynamic-radius-pill;
        background: rgba(11, 11, 11, 0.44);
        box-shadow: none;

        text {
            color: #ffffff;
            font-size: 18rpx;
            font-weight: 600;
            line-height: 1;
        }
    }

    .dynamic-card__editorial-content {
        display: block;
        padding: var(--dynamic-editorial-content-padding, 10rpx 12rpx 0);
        font-size: var(--dynamic-editorial-content-size, 28rpx);
        line-height: var(--dynamic-editorial-content-line-height, 1.45);
        font-weight: 600;
        color: $dynamic-text;
        word-break: break-word;
        overflow: hidden;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: var(--dynamic-editorial-content-clamp, 2);
    }

    .dynamic-card__editorial-stats {
        display: flex;
        align-items: center;
        gap: var(--dynamic-editorial-stats-gap, 8rpx);
        padding: var(--dynamic-editorial-stats-padding, 2rpx 12rpx 12rpx);
        flex-wrap: wrap;
    }

    .dynamic-card__editorial-stat {
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        flex-shrink: 0;
        min-width: 0;
        padding: 0;
        border: none;
        background: transparent;
        box-shadow: none;
        color: $dynamic-text-muted;

        &.is-active {
            color: $dynamic-accent;
            font-weight: 600;
        }
    }

    .dynamic-card__editorial-stat--like {
        color: inherit;
    }

    .dynamic-card__editorial-stat-text {
        font-size: var(--dynamic-editorial-stat-size, 20rpx);
        font-weight: 500;
        line-height: 1;
        white-space: nowrap;
    }
}

.dynamic-card--plaza-unified {
    .dynamic-card__header {
        padding: var(--wm-space-card-padding-lg, 24rpx) var(--wm-space-card-padding-lg, 24rpx) 0;
    }

    .dynamic-card__author {
        gap: var(--wm-space-section-gap-lg, 16rpx);
    }

    .dynamic-card__name {
        font-size: 32rpx;
    }

    .dynamic-card__meta-row {
        margin-top: 12rpx;
    }

    .dynamic-card__tag-row {
        padding: var(--wm-space-section-gap-lg, 16rpx) var(--wm-space-card-padding-lg, 24rpx) 0;
    }

    .dynamic-card__tag {
        background: var(--dynamic-card-primary-soft);
        border-color: var(--dynamic-card-primary-soft-border);
        color: var(--wm-color-primary, #0b0b0b);
        font-weight: 500;

        &--type {
            background: #F8F7F2;
            border-color: #E7E2D6;
            color: #5f5a50;
            font-weight: 600;
        }
    }

    .dynamic-card__content {
        padding: var(--wm-space-section-gap-lg, 16rpx) var(--wm-space-card-padding-lg, 24rpx) 0;
        font-size: 26rpx;
        line-height: 1.62;
        color: #5F5A50;
    }

    .dynamic-card__media {
        padding: var(--wm-space-card-padding, 20rpx) var(--wm-space-card-padding-lg, 24rpx) 0;
    }

    .dynamic-card__footer {
        gap: var(--wm-space-section-gap-lg, 16rpx);
        padding: var(--wm-space-card-padding, 20rpx) var(--wm-space-card-padding-lg, 24rpx);
        background: linear-gradient(
            180deg,
            rgba(255, 255, 255, 0.96) 0%,
            rgba(248, 247, 242, 0.92) 100%
        );
        border-top: 1rpx solid var(--wm-color-border, #e7e2d6);
    }

    .dynamic-card__stats {
        gap: var(--wm-space-section-gap-lg, 16rpx);
    }

    .dynamic-card__action {
        height: 80rpx;
        padding: 0 var(--wm-space-card-padding-lg, 24rpx);
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
