<template>
    <view class="empty" :class="{ 'empty--fixed': fixed }">
        <!-- 加载 -->
        <template v-if="status === 'loading'">
            <u-loading :size="36" mode="circle" color="var(--c-brand)" />
            <text class="empty__text">{{ loadingText }}</text>
        </template>

        <!-- 空 -->
        <template v-else-if="status === 'empty'">
            <u-icon :name="emptyIcon" :size="96" color="var(--c-ink-3)" />
            <text class="empty__text">{{ emptyText }}</text>
            <view v-if="showAction" class="empty__action">
                <base-button :text="actionText" size="sm" variant="ghost" @click="emit('action')" />
            </view>
        </template>

        <!-- 错误：先分清「能不能重试」，再决定给不给按钮 -->
        <template v-else-if="status === 'error'">
            <u-icon :name="kind.icon" :size="96" :color="kind.color" />
            <text class="empty__text">{{ errorText || kind.text }}</text>
            <view v-if="kind.retry" class="empty__action">
                <base-button text="重新加载" size="sm" variant="ghost" @click="emit('retry')" />
            </view>
            <view v-else-if="actionText" class="empty__action">
                <base-button :text="actionText" size="sm" variant="ghost" @click="emit('action')" />
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/**
 * 三态占位：loading / empty / error。
 *
 * error 必须区分「能不能重试」：
 *   network    断网/超时/5xx —— 给「重新加载」
 *   business   业务状态不允许（如订单已关闭）—— 重试也没用，不给按钮
 *   permission 无权限/未登录 —— 不给按钮，避免反复撞墙
 *   unknown    兜底，按可重试处理
 * 不分类型统一给「重新加载」，会让用户在不可恢复的错误上反复点。
 */
import { computed } from 'vue'

type ErrorKind = 'network' | 'business' | 'permission' | 'unknown'

const props = withDefaults(
    defineProps<{
        status: 'normal' | 'loading' | 'empty' | 'error'
        /** 仅 status=error 时生效 */
        errorKind?: ErrorKind
        loadingText?: string
        emptyText?: string
        /** 传了就用它，覆盖 errorKind 的默认文案 */
        errorText?: string
        emptyIcon?: string
        actionText?: string
        showAction?: boolean
        /** 固定占满可视区（用于整页列表） */
        fixed?: boolean
    }>(),
    {
        errorKind: 'unknown',
        loadingText: '加载中…',
        emptyText: '暂无数据',
        errorText: '',
        emptyIcon: 'empty-list',
        actionText: '',
        showAction: false,
        fixed: false
    }
)

const emit = defineEmits<{ (e: 'retry'): void; (e: 'action'): void }>()

const KIND_MAP: Record<ErrorKind, { icon: string; text: string; color: string; retry: boolean }> = {
    network: {
        icon: 'empty-wifi',
        text: '网络开小差了，请检查网络后重试',
        color: 'var(--c-ink-3)',
        retry: true
    },
    business: {
        icon: 'empty-data',
        text: '当前状态不支持此操作',
        color: 'var(--c-warning)',
        retry: false
    },
    permission: {
        icon: 'empty-permission',
        text: '没有查看权限，请联系客服',
        color: 'var(--c-ink-3)',
        retry: false
    },
    unknown: {
        icon: 'empty-list',
        text: '加载失败，请稍后重试',
        color: 'var(--c-ink-3)',
        retry: true
    }
}

const kind = computed(() => KIND_MAP[props.errorKind] || KIND_MAP.unknown)
</script>

<style lang="scss" scoped>
.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--sp-3);
    padding: var(--sp-6) var(--sp-4);

    &--fixed {
        min-height: 60vh;
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
        line-height: 1.6;
    }

    &__action {
        margin-top: var(--sp-2);
    }
}
</style>
