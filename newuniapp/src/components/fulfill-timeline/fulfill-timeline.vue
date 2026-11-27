<template>
    <view class="tl" :aria-label="ariaLabel">
        <view v-for="(it, i) in items" :key="it.label + i" class="tl__row">
            <text class="tl__label t-num">{{ it.label }}</text>
            <view class="tl__body">
                <text class="tl__title t-balance">{{ it.title }}</text>
                <text v-if="it.desc" class="tl__desc">{{ it.desc }}</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 履约时间轴（T-30 / T-01 / T-0 这类「婚前倒计时节点」）。
 *
 * 与 steps 的分工，别混用：
 *  - steps   = 当前流程进行到第几步（可含失败态，读屏播报「第 N 步」）
 *  - timeline= 静态服务承诺清单，全部条目同时成立，无「当前步」概念
 *
 * 契约：纯展示。数据由页面给（label 用 T-30 / T-01 / T-0 约定好的相对时间标记），
 * 组件不计算日期、不排序、不做完成态判断。
 */
import { computed } from 'vue'

interface TimelineItem {
    /** 左侧时间标记，如 T-30 / T-01 / T-0 */
    label: string
    title: string
    desc?: string
}

const props = withDefaults(defineProps<{ items: TimelineItem[] }>(), { items: () => [] })

/** computed 而非一次性字符串：items 异步到达时读屏标签随之更新 */
const ariaLabel = computed(
    () =>
        `服务流程共 ${props.items.length} 个节点：${props.items
            .map((i) => `${i.label}，${i.title}`)
            .join('；')}`
)
</script>

<style lang="scss" scoped>
.tl {
    padding: 0 var(--sp-4);

    &__row {
        display: flex;
        gap: var(--sp-3);
        padding: var(--sp-3) 0;
        border-bottom: 1rpx solid var(--c-line);

        &:last-child {
            border-bottom: 0;
        }
    }

    /* 时间标记：金色仅作装饰位（与 section-header 竖条同级），文案由右侧标题承载 */
    &__label {
        width: 88rpx;
        flex: none;
        padding-top: 4rpx;
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        letter-spacing: 0.08em;
        color: var(--c-brand-text); /* 金色对米白 2.25:1，改用可读的 --c-brand-text */
        line-height: var(--lh-none);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
        line-height: var(--lh-tight);
        letter-spacing: 0.01em;
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-loose);
    }
}
</style>
