<template>
    <view class="dynamic-card" :class="cardClass" :style="cardStyle" @click="handleCardClick">
        <template v-if="isEditorial">
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
                    <BaseIcon name="play-fill" size="24" color="var(--wm-text-inverse, #FFFDF8)" />
                    <text>播放</text>
                </view>
            </view>

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

            <text v-if="dynamic.content" class="dynamic-card__editorial-content">
                {{ truncatedContent }}
            </text>

            <view class="dynamic-card__editorial-stats">
                <view
                    class="dynamic-card__editorial-stat dynamic-card__editorial-stat--like"
                    :class="{ 'is-active': dynamic.isLiked }"
                    @click.stop="handleLike"
                >
                    <BaseIcon
                        :name="dynamic.isLiked ? 'like-fill' : 'like'"
                        size="20"
                        :color="dynamic.isLiked ? '#C6A15B' : '#8C8273'"
                    />
                    <text class="dynamic-card__editorial-stat-text">
                        {{ formatCount(dynamic.likeCount) }}
                    </text>
                </view>
                <view
                    v-if="showComment"
                    class="dynamic-card__editorial-stat"
                    @click.stop="handleComment"
                >
                    <BaseIcon name="chat" size="20" color="#8C8273" />
                    <text class="dynamic-card__editorial-stat-text">
                        {{ formatCount(dynamic.commentCount) }}
                    </text>
                </view>
                <view class="dynamic-card__editorial-stat">
                    <BaseIcon name="eye" size="20" color="#8C8273" />
                    <text class="dynamic-card__editorial-stat-text">
                        {{ formatCount(dynamic.viewCount) }}
                    </text>
                </view>
            </view>
        </template>

        <template v-else>
            <!-- 头部作者信息 -->
            <view class="dynamic-card__header">
                <view class="dynamic-card__author">
                    <view class="dynamic-card__avatar-wrap" @click.stop="handleUserClick">
                        <image class="dynamic-card__avatar" :src="avatarSrc" mode="aspectFill" />
                        <view
                            v-if="dynamic.user.roleLabel === '官方'"
                            class="dynamic-card__avatar-crown"
                        >
                            <BaseIcon name="diamond" size="16" color="#D9BE82" />
                        </view>
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
                            <text class="dynamic-card__meta-text">
                                {{ formatTime(dynamic.createTime) }}
                            </text>
                            <template v-if="dynamic.location?.name">
                                <text class="dynamic-card__meta-dot">·</text>
                                <view class="dynamic-card__location">
                                    <BaseIcon name="location" size="20" color="#C6A15B" />
                                    <text class="dynamic-card__meta-text">{{
                                        dynamic.location.name
                                    }}</text>
                                </view>
                            </template>
                        </view>
                    </view>
                </view>

                <view class="dynamic-card__header-action">
                    <favorite-button
                        v-if="showFavoriteButton"
                        :is-favorited="dynamic.user.isFavorite"
                        size="sm"
                        @click="handleFavorite"
                    />
                    <view
                        v-else-if="showShare"
                        class="dynamic-card__quick-share"
                        @click.stop="handleMore"
                    >
                        <BaseIcon name="share" size="22" color="#8C8273" />
                    </view>
                </view>
            </view>

            <!-- 标签与分类行 -->
            <view v-if="showTypeBadge || displayTopics.length" class="dynamic-card__tag-row">
                <view v-if="showTypeBadge" class="dynamic-card__tag dynamic-card__tag--type">
                    <text class="dynamic-card__tag-icon">✦</text>
                    <text>{{ dynamic.dynamicTypeLabel }}</text>
                </view>
                <view
                    v-for="topic in displayTopics"
                    :key="topic.id"
                    class="dynamic-card__tag dynamic-card__tag--topic"
                    @click.stop="handleTopicClick(topic)"
                >
                    <text>#{{ topic.name }}</text>
                </view>
            </view>

            <!-- 正文叙事内容 -->
            <text v-if="dynamic.content" class="dynamic-card__content">{{ truncatedContent }}</text>

            <!-- 多媒体网格布局 (1, 2, 3, 4+) -->
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
                    >
                        <BaseIcon name="play-fill" size="22" color="#FFFDF8" />
                        <text>播放视频</text>
                    </view>
                    <view
                        v-if="hiddenImageCount > 0 && index === displayedImages.length - 1"
                        class="dynamic-card__media-mask"
                    >
                        <text class="dynamic-card__media-mask-plus">+</text>
                        <text class="dynamic-card__media-mask-count">{{ hiddenImageCount }}</text>
                    </view>
                </view>
            </view>

            <!-- 底栏数据与互动按纽 -->
            <view class="dynamic-card__footer">
                <view class="dynamic-card__stats">
                    <view class="dynamic-card__stat">
                        <BaseIcon name="eye" size="22" color="#8C8273" />
                        <text>{{ formatCount(dynamic.viewCount) }} 浏览</text>
                    </view>
                    <view
                        v-if="showComment"
                        class="dynamic-card__stat dynamic-card__stat--clickable"
                        @click.stop="handleComment"
                    >
                        <BaseIcon name="chat" size="22" color="#8C8273" />
                        <text>{{ formatCount(dynamic.commentCount) }} 评论</text>
                    </view>
                </view>

                <view class="dynamic-card__actions">
                    <view
                        v-if="showComment"
                        class="dynamic-card__action dynamic-card__action--comment"
                        @click.stop="handleComment"
                    >
                        <BaseIcon name="chat" size="22" color="#5E564B" />
                        <text>回复</text>
                    </view>
                    <view
                        class="dynamic-card__action dynamic-card__action--like"
                        :class="{ 'is-active': dynamic.isLiked }"
                        @click.stop="handleLike"
                    >
                        <BaseIcon
                            :name="dynamic.isLiked ? 'like-fill' : 'like'"
                            size="24"
                            :color="dynamic.isLiked ? '#C6A15B' : '#5E564B'"
                        />
                        <text>{{ dynamic.isLiked ? '已赞' : '点赞' }}</text>
                        <text class="dynamic-card__action-num">{{ formatCount(dynamic.likeCount) }}</text>
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
    variant?: 'default' | 'plaza-unified' | 'plaza-v2' | 'editorial'
    showShare?: boolean
    showComment?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    showShare: true,
    showComment: true
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
const isPlazaV2 = computed(() => props.variant === 'plaza-v2')
const isEditorial = computed(() => props.variant === 'editorial')
const hasEditorialCover = computed(() => isEditorial.value && Boolean(props.dynamic.images?.[0]))
const cardClass = computed(() => ({
    'dynamic-card--plaza-unified': isPlazaUnified.value,
    'dynamic-card--plaza-v2': isPlazaV2.value,
    'dynamic-card--editorial': isEditorial.value,
    'dynamic-card--editorial-no-cover': isEditorial.value && !hasEditorialCover.value
}))

const displayTopics = computed(() => props.dynamic.topics?.slice(0, 4) || [])

const showTypeBadge = computed(() => !isEditorial.value && props.dynamic.dynamicType !== 1)

const showFavoriteButton = computed(
    () => !isEditorial.value && !isPlazaV2.value && props.dynamic.user.canFavorite
)

const editorialMeta = computed(() => {
    const parts = [formatTime(props.dynamic.createTime)]
    if (props.dynamic.location?.name) {
        parts.push(props.dynamic.location.name)
    }
    return parts.join(' · ')
})

const truncatedContent = computed(() => {
    const content = props.dynamic.content || ''
    const maxLength = isEditorial.value ? (hasEditorialCover.value ? 64 : 120) : 220
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

const primaryColor = computed(() => themeStore.primaryColor || '#181614')

const cardStyle = computed(() => ({
    boxShadow: isEditorial.value
        ? 'var(--dynamic-editorial-card-shadow, 0 16rpx 36rpx rgba(74, 43, 24, 0.07))'
        : '0 18rpx 44rpx rgba(74, 43, 24, 0.08)'
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
    if (!props.showComment) {
        return
    }
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
    background: #FFFFFF;
    border-radius: var(--wm-radius-card, 32rpx);
    border: 1rpx solid rgba(231, 224, 211, 0.85);
    overflow: hidden;
    box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.06);
    transition: all 0.22s ease;
    box-sizing: border-box;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.98;
    }

    &__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;
        padding: 26rpx 28rpx 0;
    }

    &__author {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 18rpx;
    }

    &__avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }

    &__avatar {
        width: 80rpx;
        height: 80rpx;
        border-radius: 50%;
        background: #FAF6EE;
        border: 2rpx solid #D9BE82;
        box-shadow: 0 4rpx 14rpx rgba(74, 43, 24, 0.09);
        display: block;
    }

    &__avatar-crown {
        position: absolute;
        right: -4rpx;
        bottom: -2rpx;
        width: 30rpx;
        height: 30rpx;
        border-radius: 50%;
        background: #181614;
        border: 1.5rpx solid #D9BE82;
        display: flex;
        align-items: center;
        justify-content: center;
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
        color: #181614;
    }

    &__role-badge {
        flex-shrink: 0;
        padding: 4rpx 14rpx;
        border-radius: 999rpx;
        background: #F1E5C8;
        border: 1rpx solid rgba(217, 190, 130, 0.45);
        font-size: 20rpx;
        font-weight: 600;
        color: #9A6B35;
        line-height: 1.2;

        &--staff {
            color: #9A6B35;
            background: #F6EDE0;
            border-color: rgba(217, 190, 130, 0.6);
        }

        &--official {
            background: #181614;
            border-color: #D9BE82;
            color: #FFFDF8;
        }
    }

    &__meta-row {
        display: flex;
        align-items: center;
        gap: 8rpx;
        min-width: 0;
        margin-top: 6rpx;
    }

    &__meta-text {
        font-size: 22rpx;
        color: #8C8273;
        line-height: 1.3;
    }

    &__meta-dot {
        color: #D8C9AD;
        font-size: 20rpx;
    }

    &__location {
        min-width: 0;
        display: inline-flex;
        align-items: center;
        gap: 4rpx;

        text {
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    &__header-action {
        flex-shrink: 0;
    }

    &__quick-share {
        width: 56rpx;
        height: 56rpx;
        border-radius: 50%;
        background: #FAF6EE;
        border: 1rpx solid rgba(231, 224, 211, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;

        &:active {
            background: #F2ECE1;
            transform: scale(0.92);
        }
    }

    &__tag-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
        padding: 16rpx 28rpx 0;
    }

    &__tag {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        max-width: 100%;
        padding: 6rpx 18rpx;
        border-radius: 999rpx;
        font-size: 22rpx;
        line-height: 1.3;
        white-space: nowrap;
        box-sizing: border-box;
        transition: all 0.2s ease;

        &--type {
            background: #181614;
            border: 1rpx solid #D9BE82;
            color: #FFFDF8;
            font-weight: 600;

            .dynamic-card__tag-icon {
                color: #D9BE82;
                font-size: 20rpx;
            }
        }

        &--topic {
            background: #FAF6EE;
            border: 1rpx solid rgba(217, 190, 130, 0.5);
            color: #9A6B35;
            font-weight: 500;

            &:active {
                background: #F1E5C8;
            }
        }
    }

    &__content {
        display: block;
        padding: 16rpx 28rpx 0;
        font-size: 28rpx;
        line-height: 1.68;
        font-weight: 400;
        color: #2C261E;
        word-break: break-word;
    }

    &__media {
        display: grid;
        gap: 12rpx;
        padding: 20rpx 28rpx 0;

        &--1 {
            grid-template-columns: 1fr;

            .dynamic-card__media-item {
                height: 400rpx;
            }
        }

        &--2 {
            grid-template-columns: repeat(2, 1fr);

            .dynamic-card__media-item {
                height: 230rpx;
            }
        }

        &--3 {
            grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
            grid-template-rows: repeat(2, 168rpx);

            .dynamic-card__media-item:first-child {
                grid-row: 1 / span 2;
                height: 348rpx;
            }

            .dynamic-card__media-item:not(:first-child) {
                height: 168rpx;
            }
        }

        &--4 {
            grid-template-columns: repeat(2, 1fr);

            .dynamic-card__media-item {
                height: 216rpx;
            }
        }
    }

    &__media-item {
        position: relative;
        overflow: hidden;
        border-radius: 22rpx;
        background: #FAF6EE;
        border: 1rpx solid rgba(231, 224, 211, 0.75);
    }

    &__media-image {
        width: 100%;
        height: 100%;
        display: block;
        background: #F8F6F0;
    }

    &__video-badge {
        position: absolute;
        left: 16rpx;
        bottom: 16rpx;
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 10rpx 20rpx;
        border-radius: 999rpx;
        background: rgba(24, 22, 20, 0.72);
        border: 1rpx solid rgba(217, 190, 130, 0.4);
        color: #FFFDF8;
        font-size: 22rpx;
        font-weight: 600;
        backdrop-filter: blur(8rpx);
        -webkit-backdrop-filter: blur(8rpx);
    }

    &__media-mask {
        position: absolute;
        inset: 0;
        background: rgba(24, 22, 20, 0.58);
        color: #FFFDF8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        backdrop-filter: blur(6rpx);
        -webkit-backdrop-filter: blur(6rpx);

        &-plus {
            font-size: 28rpx;
            margin-right: 2rpx;
        }

        &-count {
            font-size: 38rpx;
        }
    }

    &__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        margin-top: 22rpx;
        padding: 20rpx 28rpx 24rpx;
        background: #FAF8F5;
        border-top: 1rpx solid rgba(231, 224, 211, 0.75);
    }

    &__stats {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 20rpx;
    }

    &__stat {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        font-size: 22rpx;
        color: #8C8273;

        &--clickable {
            cursor: pointer;
            &:active {
                color: #181614;
            }
        }
    }

    &__actions {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        margin-left: auto;
    }

    &__action {
        height: 64rpx;
        border-radius: 999rpx;
        padding: 0 22rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.9);
        background: #FFFFFF;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
        font-size: 23rpx;
        font-weight: 600;
        color: #5E564B;
        box-shadow: 0 4rpx 10rpx rgba(74, 43, 24, 0.04);
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.96);
            background: #FAF6EE;
        }

        &--like {
            &.is-active {
                border-color: rgba(217, 190, 130, 0.6);
                background: #FDF9F2;
                color: #C6A15B;

                :deep(.base-icon) {
                    @include dynamic-heart-pulse;
                }
            }
        }

        &-num {
            font-size: 21rpx;
            font-weight: 500;
        }
    }
}

/* Editorial variant adjustments */
.dynamic-card--editorial {
    border-radius: 26rpx;
    border-color: #E7E0D3;
    background: #FFFFFF;

    .dynamic-card__editorial-head {
        padding: 14rpx 16rpx 0;
    }

    .dynamic-card__editorial-author {
        display: flex;
        align-items: center;
        gap: 12rpx;
    }

    .dynamic-card__editorial-author-main {
        flex: 1;
        min-width: 0;
    }

    .dynamic-card__avatar {
        width: 56rpx;
        height: 56rpx;
        border: 2rpx solid #D9BE82;
    }

    .dynamic-card__name {
        font-size: 24rpx;
        color: #181614;
    }

    .dynamic-card__role-badge {
        padding: 4rpx 10rpx;
        font-size: 18rpx;
    }

    .dynamic-card__editorial-meta {
        display: block;
        margin-top: 4rpx;
        font-size: 20rpx;
        line-height: 1.4;
        color: #8C8273;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dynamic-card__editorial-cover-wrap {
        position: relative;
        margin: 12rpx 14rpx 0;
        border-radius: 20rpx;
        overflow: hidden;
        background: #FAF6EE;
    }

    .dynamic-card__editorial-cover {
        width: 100%;
        height: 320rpx;
        display: block;
    }

    .dynamic-card__video-badge--editorial {
        left: 12rpx;
        bottom: 12rpx;
        gap: 4rpx;
        padding: 6rpx 14rpx;
        border-radius: 999rpx;
        background: rgba(24, 22, 20, 0.5);

        text {
            color: #FFFDF8;
            font-size: 18rpx;
            font-weight: 600;
        }
    }

    .dynamic-card__editorial-content {
        display: block;
        padding: 12rpx 14rpx 0;
        font-size: 27rpx;
        line-height: 1.48;
        font-weight: 600;
        color: #181614;
        word-break: break-word;
        overflow: hidden;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .dynamic-card__editorial-stats {
        display: flex;
        align-items: center;
        gap: 14rpx;
        padding: 12rpx 14rpx 16rpx;
        flex-wrap: wrap;
    }

    .dynamic-card__editorial-stat {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        color: #8C8273;

        &.is-active {
            color: #C6A15B;
            font-weight: 700;
        }
    }

    .dynamic-card__editorial-stat-text {
        font-size: 21rpx;
        line-height: 1;
    }
}

.dynamic-card--editorial-no-cover {
    position: relative;

    &::before {
        content: '';
        position: absolute;
        left: 0;
        top: 24rpx;
        bottom: 24rpx;
        width: 6rpx;
        border-radius: 999rpx;
        background: #C6A15B;
    }

    .dynamic-card__editorial-head {
        padding-top: 22rpx;
    }

    .dynamic-card__editorial-content {
        padding-top: 20rpx;
        font-size: 30rpx;
        line-height: 1.56;
        -webkit-line-clamp: 4;
    }
}
</style>
