<template>
    <view class="com">
        <view class="com__head">
            <text class="com__title">评论</text>
            <text v-if="commentCount > 0" class="com__num t-num">{{ commentCount }}</text>
            <view class="com__sort">
                <view
                    class="com__sort-item"
                    :class="{ 'is-on': sort === 'hot' }"
                    hover-class="is-pressed"
                    aria-role="button"
                    @tap="onSwitchSort('hot')"
                >
                    <text class="com__sort-text">最热</text>
                </view>
                <view
                    class="com__sort-item"
                    :class="{ 'is-on': sort === 'new' }"
                    hover-class="is-pressed"
                    aria-role="button"
                    @tap="onSwitchSort('new')"
                >
                    <text class="com__sort-text">最新</text>
                </view>
            </view>
        </view>

        <view v-if="comments.length === 0 && !commentLoading" class="com__empty">
            <text class="com__empty-text">还没有评论</text>
        </view>

        <view v-for="c in comments" :key="c.id" class="cm">
            <image
                v-if="c.user_avatar"
                class="cm__av"
                :src="imgUrl(c.user_avatar)"
                mode="aspectFill"
            />
            <view v-else class="cm__av cm__av--txt">
                <text class="cm__av-text">{{ (c.user_nickname || '·').slice(0, 1) }}</text>
            </view>
            <view class="cm__body">
                <view class="cm__who">
                    <text class="cm__name">{{ c.user_nickname || '匿名' }}</text>
                    <text v-if="c.pending" class="cm__pending">待审核</text>
                </view>
                <text class="cm__p">{{ c.content }}</text>
                <view class="cm__foot">
                    <text class="cm__time">{{ c.create_time_text || '' }}</text>
                    <view
                        class="cm__like"
                        :class="{ 'is-on': c.is_liked }"
                        hover-class="is-pressed"
                        aria-role="button"
                        @tap="onLike(c)"
                    >
                        <tn-icon :name="c.is_liked ? 'like-fill' : 'like'" :size="22" />
                        <text class="cm__like-num t-num">{{ c.like_count || 0 }}</text>
                    </view>
                    <view
                        v-if="canComment"
                        class="cm__act"
                        hover-class="is-pressed"
                        aria-role="button"
                        @tap="onReply(c, c.id)"
                    >
                        <text class="cm__act-text">回复</text>
                    </view>
                </view>

                <!-- 二级回复：默认收起，展开时按 parent_id 拉取；新回复即时插到列表尾部 -->
                <view
                    v-if="Number(c.reply_count) > 0 || replyStates[c.id]?.expanded"
                    class="cm__replies"
                >
                    <view
                        class="cm__replies-toggle hit-88"
                        hover-class="is-pressed"
                        aria-role="button"
                        @tap="onToggleReplies(c)"
                    >
                        <text class="cm__replies-toggle-text">
                            {{
                                replyStates[c.id]?.expanded
                                    ? '收起回复'
                                    : `展开 ${replyStates[c.id]?.total || c.reply_count} 条回复`
                            }}
                        </text>
                    </view>

                    <view v-if="replyStates[c.id]?.expanded" class="cm__reply-list">
                        <view
                            v-for="r in replyStates[c.id]?.items || []"
                            :key="r.id"
                            class="cm__reply-item"
                        >
                            <image
                                v-if="r.user_avatar"
                                class="cm__reply-av"
                                :src="imgUrl(r.user_avatar)"
                                mode="aspectFill"
                            />
                            <view v-else class="cm__reply-av cm__reply-av--txt">
                                <text class="cm__reply-av-text">{{
                                    (r.user_nickname || '·').slice(0, 1)
                                }}</text>
                            </view>
                            <view class="cm__reply-body">
                                <view class="cm__reply-who">
                                    <text class="cm__reply-name">{{
                                        r.user_nickname || '匿名'
                                    }}</text>
                                    <text v-if="r.pending" class="cm__pending">待审核</text>
                                </view>
                                <text class="cm__reply-text">
                                    <text
                                        v-if="
                                            Number(r.reply_user_id) > 0 && r.reply_user_nickname
                                        "
                                        class="cm__reply-to"
                                        >回复 @{{ r.reply_user_nickname }}：</text
                                    >{{ r.content }}
                                </text>
                                <view class="cm__reply-foot">
                                    <text class="cm__time">{{ r.create_time_text || '' }}</text>
                                    <view
                                        class="cm__like"
                                        :class="{ 'is-on': r.is_liked }"
                                        hover-class="is-pressed"
                                        aria-role="button"
                                        @tap="onLike(r)"
                                    >
                                        <tn-icon
                                            :name="r.is_liked ? 'like-fill' : 'like'"
                                            :size="20"
                                        />
                                        <text class="cm__like-num t-num">{{
                                            r.like_count || 0
                                        }}</text>
                                    </view>
                                    <view
                                        v-if="canComment"
                                        class="cm__act"
                                        hover-class="is-pressed"
                                        aria-role="button"
                                        @tap="onReply(r, c.id)"
                                    >
                                        <text class="cm__act-text">回复</text>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view v-if="replyStates[c.id]?.loading" class="cm__reply-loading">
                            <tn-loading mode="circle" :size="28" :show="true" />
                        </view>
                        <view
                            v-else-if="replyStates[c.id]?.error"
                            class="cm__reply-tip hit-88"
                            hover-class="is-pressed"
                            @tap="onLoadReplies(c.id, true)"
                            >加载失败，点击重试</view
                        >
                        <view
                            v-else-if="replyStates[c.id]?.hasMore"
                            class="cm__reply-tip hit-88"
                            hover-class="is-pressed"
                            @tap="onLoadReplies(c.id, false)"
                            >查看更多回复</view
                        >
                    </view>
                </view>
            </view>
        </view>

        <load-more
            v-if="comments.length"
            :status="loadMoreStatus"
            @retry="emit('loadMore')"
        />
        <view v-else-if="commentLoading" class="com__loading">
            <tn-loading mode="circle" :size="32" :show="true" />
        </view>
    </view>
</template>

<script setup lang="ts">
import { useAppStore } from '@/stores/app'
import type { CommentRow, ReplyState } from '../useFeedComments'

defineProps<{
    comments: CommentRow[]
    commentCount: number
    commentLoading: boolean
    sort: 'hot' | 'new'
    loadMoreStatus: 'idle' | 'loading' | 'nomore' | 'error'
    canComment: boolean
    replyStates: Record<number, ReplyState>
}>()

const emit = defineEmits<{
    (e: 'switchSort', sort: 'hot' | 'new'): void
    (e: 'like', row: CommentRow): void
    (e: 'reply', target: CommentRow, rootId: number): void
    (e: 'toggleReplies', row: CommentRow): void
    (e: 'loadReplies', parentId: number, reset: boolean): void
    (e: 'loadMore'): void
}>()

const appStore = useAppStore()

function onSwitchSort(next: 'hot' | 'new') {
    emit('switchSort', next)
}

function onLike(row: CommentRow) {
    emit('like', row)
}

function onReply(target: CommentRow, rootId: number) {
    emit('reply', target, rootId)
}

function onToggleReplies(row: CommentRow) {
    emit('toggleReplies', row)
}

function onLoadReplies(parentId: number, reset: boolean) {
    emit('loadReplies', parentId, reset)
}

function imgUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}
</script>

<style lang="scss" scoped>
.com {
    margin-top: 34rpx;
    padding: 0 0 40rpx;

    &__head {
        display: flex;
        align-items: center;
        gap: 10rpx;
        padding: 0 42rpx 18rpx;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__num {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__sort {
        margin-left: auto;
        display: flex;
        gap: 20rpx;
    }

    /* 排序胶囊：视觉是文字，热区靠 min-size 撑到 88rpx（间距 20rpx，横向不重叠） */
    &__sort-item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 88rpx;
        min-height: 88rpx;
        margin: -26rpx -10rpx;
        padding: 0 10rpx;
        box-sizing: border-box;
    }

    &__sort-text {
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }

    &__sort-item.is-on &__sort-text {
        color: var(--c-ink);
        font-weight: var(--fw-medium);
    }

    &__empty {
        padding: 40rpx 42rpx;
        text-align: center;
    }

    &__empty-text {
        font-size: var(--fs-22);
        color: var(--c-ink-3);
    }

    &__loading {
        display: flex;
        justify-content: center;
        padding: 30rpx 0;
    }
}

/* 评论行：一级评论之间用发丝线分组，二级回复挂在同一条左侧引线里 */
.cm {
    display: flex;
    gap: 16rpx;
    padding: 22rpx 42rpx;
    border-bottom: 1rpx solid var(--c-line);

    &__av {
        width: 60rpx;
        height: 60rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex: none;

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-surface-2);
        }
    }

    &__av-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__who {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__name {
        font-size: var(--fs-23);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__p {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-25);
        line-height: var(--lh-loose);
        color: var(--c-ink);
    }

    &__pending {
        flex: none;
        padding: 2rpx 8rpx;
        border-radius: var(--r-xs);
        background: var(--c-warning-bg);
        color: var(--c-warning);
        border: 1rpx solid var(--c-warning-border);
        font-size: var(--fs-18);
        line-height: var(--lh-tight);
    }

    /* 点赞 / 回复：88rpx 热区，用负外边距抵消，不撑高评论区 */
    &__act {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        min-height: 88rpx;
        min-width: 88rpx;
        padding-left: var(--sp-3);
        box-sizing: border-box;
    }

    &__act-text {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }

    &__replies {
        margin-top: 10rpx;
    }

    &__replies-toggle {
        display: inline-flex;
        align-items: center;
        min-height: 88rpx;
        padding: 0 var(--sp-1);
    }

    &__replies-toggle-text {
        font-size: var(--fs-21);
        color: var(--c-brand-text);
    }

    &__reply-list {
        margin-top: 6rpx;
        padding-left: var(--sp-2);
        border-left: 2rpx solid var(--c-line);
    }

    &__reply-item {
        display: flex;
        gap: 12rpx;
        padding: 12rpx 0;
        border-bottom: 1rpx solid var(--c-line);

        &:last-child {
            border-bottom: 0;
        }
    }

    &__reply-av {
        width: 44rpx;
        height: 44rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex: none;

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-surface-2);
        }
    }

    &__reply-av-text {
        font-size: var(--fs-18);
        color: var(--c-ink-2);
    }

    &__reply-body {
        flex: 1;
        min-width: 0;
    }

    &__reply-who {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    &__reply-name {
        font-size: var(--fs-21);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__reply-text {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-23);
        line-height: var(--lh-body);
        color: var(--c-ink);
    }

    &__reply-to {
        color: var(--c-brand-text);
    }

    &__reply-foot {
        display: flex;
        align-items: center;
        gap: 20rpx;
        margin-top: 6rpx;
    }

    &__reply-loading {
        display: flex;
        justify-content: center;
        padding: 12rpx 0;
    }

    &__reply-tip {
        display: flex;
        align-items: center;
        min-height: 88rpx;
        font-size: var(--fs-21);
        color: var(--c-brand-text);
    }

    &__foot {
        display: flex;
        align-items: center;
        gap: 24rpx;
        margin-top: 10rpx;
    }

    &__time {
        font-size: var(--fs-19);
        color: var(--c-ink-3);
    }

    &__like {
        display: flex;
        align-items: center;
        gap: 6rpx;
        min-height: 88rpx;
        min-width: 88rpx;
        box-sizing: border-box;
        color: var(--c-ink-3);

        &.is-on {
            color: var(--c-danger);
        }
    }

    &__like-num {
        font-size: var(--fs-19);
        color: inherit;
    }
}
</style>
