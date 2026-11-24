<template>
    <view class="panel">
        <view class="stephead">
            <text class="stephead__title">是否需要{{ role.role_label }}？</text>
            <text class="stephead__hint">不同人员价格不同，可进详情对比后再选</text>
        </view>

        <view class="rolechoice">
            <view
                class="rolechoice__card"
                :class="{ 'is-on': !needed }"
                hover-class="is-pressed"
                aria-role="radio"
                :aria-checked="!needed"
                @tap="emit('setNeeded', false)"
            >
                <text class="rolechoice__title">不需要</text>
                <text class="rolechoice__desc">不增加该项服务</text>
                <view class="rolechoice__tick" aria-hidden="true">
                    <tn-icon name="check" :size="18" />
                </view>
            </view>
            <view
                class="rolechoice__card"
                :class="{
                    'is-on': needed,
                    'is-disabled': role.schedule_available === false
                }"
                hover-class="is-pressed"
                aria-role="radio"
                :aria-checked="needed"
                @tap="emit('setNeeded', true)"
            >
                <text class="rolechoice__title">需要</text>
                <text class="rolechoice__desc">{{ priceText }}</text>
                <view class="rolechoice__tick" aria-hidden="true">
                    <tn-icon name="check" :size="18" />
                </view>
            </view>
        </view>

        <!-- 人员列表：横向滑动，卡片可直接选中，「详情」进人员主页对比价格与资料 -->
        <view v-if="needed" class="roster">
            <view class="roster__head">
                <text class="roster__title">选择{{ role.role_label }}</text>
                <text v-if="candidates.length" class="roster__count">
                    {{ candidates.length }} 位可选
                </text>
            </view>

            <scroll-view v-if="candidates.length" scroll-x class="opts" :show-scrollbar="false">
                <view class="opts__track">
                    <view
                        v-for="c in candidates"
                        :key="c.staff_id || c.id"
                        class="pcard"
                        :class="{ 'is-on': isPicked(c) }"
                        hover-class="is-pressed"
                        aria-role="radio"
                        :aria-checked="isPicked(c)"
                        @tap="emit('pick', c)"
                    >
                        <view class="pcard__top">
                            <image
                                v-if="c.avatar"
                                class="pcard__avatar"
                                :src="imageUrl(c.avatar)"
                                mode="aspectFill"
                            />
                            <view v-else class="pcard__avatar pcard__avatar--blank">
                                <text class="pcard__avatar-text">{{
                                    String(c.name || '').slice(0, 1)
                                }}</text>
                            </view>
                            <view class="pcard__body">
                                <text class="pcard__name ellipsis">{{ c.name }}</text>
                                <text v-if="candidateMeta(c)" class="pcard__meta ellipsis">
                                    {{ candidateMeta(c) }}
                                </text>
                            </view>
                            <view class="pcard__tick" aria-hidden="true">
                                <tn-icon name="check" :size="18" />
                            </view>
                        </view>
                        <view class="pcard__foot">
                            <price size="sm" :content="c.price" />
                            <text class="pcard__detail" @tap.stop="emit('openDetail', c)">详情</text>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <view v-else-if="loading" class="inline-note">
                <text class="inline-note__text">正在加载可安排人员…</text>
            </view>
            <view v-else class="inline-note">
                <text class="inline-note__text"
                    >当前日期暂无可安排人员，可换个日期或选择「不需要」</text
                >
            </view>
        </view>

        <view class="bkline">
            <text class="bkline__sum">{{ footText }}</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { candidateMeta, type RolePickedPerson } from '../booking-helpers'

const props = defineProps<{
    role: any
    needed: boolean
    picked: RolePickedPerson | null
    candidates: any[]
    loading: boolean
    priceText: string
    footText: string
    imageUrl: (url?: string) => string
}>()

const emit = defineEmits<{
    (e: 'setNeeded', needed: boolean): void
    (e: 'pick', candidate: any): void
    (e: 'openDetail', candidate: any): void
}>()

function isPicked(candidate: any) {
    return !!props.picked && Number(props.picked.staff_id) === Number(candidate?.staff_id)
}
</script>

<style lang="scss" scoped>
.panel {
    padding: 24rpx 42rpx 0;
}

.stephead {
    margin-bottom: 20rpx;

    &__title {
        display: block;
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__hint {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-20);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.rolechoice {
    display: flex;
    gap: 20rpx;
}

.rolechoice__card {
    flex: 1;
    min-width: 0;
    position: relative;
    padding: 24rpx 22rpx;
    min-height: 150rpx;
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-sm);
    background: var(--c-surface);

    &.is-disabled {
        opacity: 0.55;
    }

    &.is-on {
        border-color: var(--c-brand-text);

        .rolechoice__tick {
            opacity: 1;
            transform: scale(1);
        }
    }
}

.rolechoice__title {
    display: block;
    font-size: var(--fs-28);
    font-weight: var(--fw-semibold);
    color: var(--c-ink);
    line-height: var(--lh-tight);
}

.rolechoice__desc {
    display: block;
    margin-top: 10rpx;
    font-size: var(--fs-20);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.rolechoice__tick {
    position: absolute;
    right: 14rpx;
    top: 14rpx;
    width: 32rpx;
    height: 32rpx;
    border-radius: var(--r-pill);
    background: var(--c-ink);
    color: var(--c-on-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0.7);
    transition: opacity var(--dur-fast) var(--ease-out), transform var(--dur-fast) var(--ease-out);
}

.roster {
    margin-top: var(--sp-3);

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__title {
        font-size: var(--fs-22);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__count {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.opts {
    margin: 0 -42rpx;
    width: auto;
    white-space: nowrap;
}

.opts__track {
    display: inline-flex;
    align-items: stretch;
    gap: 20rpx;
    padding: 0 42rpx;
}

.pcard {
    width: 320rpx;
    flex: none;
    padding: 20rpx;
    border: 1rpx solid var(--c-line-strong);
    border-radius: var(--r-sm);
    background: var(--c-surface);

    &.is-on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .pcard__tick {
            opacity: 1;
            transform: scale(1);
        }
    }

    &__top {
        display: flex;
        align-items: center;
        gap: 14rpx;
        min-width: 0;
    }

    &__avatar {
        flex: none;
        width: 76rpx;
        height: 76rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);

        &--blank {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    &__avatar-text {
        font-size: var(--fs-24);
        color: var(--c-ink-3);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__name {
        display: block;
        font-size: var(--fs-24);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        line-height: var(--lh-tight);
    }

    &__meta {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__tick {
        flex: none;
        width: 32rpx;
        height: 32rpx;
        border-radius: var(--r-pill);
        background: var(--c-ink);
        color: var(--c-on-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: scale(0.7);
        transition: opacity var(--dur-fast) var(--ease-out),
            transform var(--dur-fast) var(--ease-out);
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-top: 14rpx;
        padding-top: 14rpx;
        border-top: 1rpx dashed var(--c-line);
    }

    &__detail {
        flex: none;
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        text-decoration: underline;
        padding: 10rpx 0;
        margin: -10rpx 0;
    }
}

.inline-note {
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__text {
        font-size: var(--fs-20);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.bkline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: 20rpx;
    font-size: var(--fs-20);
    color: var(--c-ink-3);

    &__sum {
        flex: 1;
        min-width: 0;
    }
}
</style>
