<template>
    <view class="panel">
        <view v-if="reviewCount" class="rsum">
            <text class="rsum__num">{{ scoreText }}</text>
            <view class="rsum__mid">
                <view class="stars" :aria-label="`评分 ${scoreText} 分，满分 5 分`">
                    <tn-icon
                        v-for="n in 5"
                        :key="n"
                        name="star-fill"
                        :size="22"
                        :class="['stars__i', { 'is-off': n > starFilled }]"
                    />
                </view>
                <text class="rsum__sub">{{ reviewSubText }}</text>
            </view>
            <view v-if="reviewTags.length" class="rtags">
                <text v-for="t in reviewTags" :key="t" class="rtags__i">{{ t }}</text>
            </view>
        </view>

        <view v-if="reviews.length" class="reviews">
            <view v-for="r in reviews" :key="r.id" class="review">
                <view class="review__head">
                    <text class="review__avatar">{{ firstChar(r.user?.nickname) }}</text>
                    <text class="review__name ellipsis">{{ r.user?.nickname || '匿名用户' }}</text>
                    <text v-if="r.create_time" class="review__date">{{
                        formatDay(r.create_time)
                    }}</text>
                    <view class="stars stars--sm" :aria-label="`${r.score} 分`">
                        <tn-icon
                            v-for="n in 5"
                            :key="n"
                            name="star-fill"
                            :size="18"
                            :class="['stars__i', { 'is-off': n > Number(r.score || 0) }]"
                        />
                    </view>
                </view>
                <text v-if="r.content" class="review__text">{{ r.content }}</text>
            </view>
            <load-more :status="loadStatus" @retry="emit('loadMore')" />
        </view>
        <base-empty
            v-else
            status="empty"
            empty-text="还没有评价"
            desc="这位主持人刚加入，先看看作品"
        />
    </view>
</template>

<script setup lang="ts">
import { formatDate } from '@shared/utils/format'

defineProps<{
    reviews: any[]
    reviewCount: number
    scoreText: string
    starFilled: number
    reviewSubText: string
    reviewTags: string[]
    loadStatus: 'idle' | 'loading' | 'nomore' | 'error'
}>()

const emit = defineEmits<{
    (e: 'loadMore'): void
}>()

function firstChar(name?: string) {
    const s = String(name || '').trim()
    return s ? Array.from(s)[0] : '匿'
}

function formatDay(v: any) {
    return formatDate(v, 'YYYY.MM.DD', '')
}
</script>

<style lang="scss" scoped>
.panel {
    padding: 34rpx 42rpx 0;
}

.rsum {
    display: flex;
    align-items: center;
    gap: var(--sp-4);
    padding-bottom: 29rpx;
    border-bottom: 1rpx solid var(--c-line);

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-63);
        font-weight: var(--fw-medium);
        line-height: var(--lh-none);
        color: var(--c-ink);
    }

    &__mid {
        flex: 1;
        min-width: 0;
    }

    &__sub {
        display: block;
        margin-top: 12rpx;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.stars {
    display: flex;
    align-items: center;
    gap: 4rpx;

    &__i {
        /* G1 ⑧：已选星原 --c-brand(#c6a15b) 对白底 2.39:1，与评分控件真源
           form-field.vue 的 --c-warning 对齐（一个语义一个值，不用两套） */
        color: var(--c-warning);

        &.is-off {
            /* G1 ⑨：未选星原 --c-line-strong 仅 1.60:1（看不出有几颗星）→ --c-ink-3 5.67:1 */
            color: var(--c-ink-3);
        }
    }

    &--sm {
        margin-left: auto;
    }
}

.rtags {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    justify-content: flex-end;
    max-width: 280rpx;

    &__i {
        height: 38rpx;
        padding: 0 14rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-size: var(--fs-18);
        line-height: 36rpx;
        white-space: nowrap;
    }
}

.reviews {
    display: flex;
    flex-direction: column;
}

.review {
    padding: 25rpx 0;
    border-bottom: 1rpx solid var(--c-line);

    &:last-child {
        border-bottom: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: 14rpx;
    }

    &__avatar {
        flex: none;
        width: 46rpx;
        height: 46rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-primary-border);
        background: var(--c-brand-bg);
        color: var(--c-brand-text);
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        text-align: center;
        line-height: 44rpx;
    }

    &__name {
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        max-width: 220rpx;
    }

    &__date {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__text {
        display: block;
        margin-top: 16rpx;
        font-size: var(--fs-22);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }
}

@media screen and (max-width: 359px) {
    .panel {
        padding-left: var(--sp-3);
        padding-right: var(--sp-3);
    }
}
</style>
