<template>
    <view
        class="empty"
        :class="[{ 'empty--fixed': fixed }, `empty--${kindKey}`]"
        :aria-live="status === 'error' ? 'polite' : undefined"
    >
        <!-- 加载（局部小区域占位；整页首次加载用 base-skeleton） -->
        <template v-if="status === 'loading'">
            <u-loading :size="36" mode="circle" color="var(--c-ink-3)" aria-hidden="true" />
            <text class="empty__text empty__text--sub">{{ loadingText || FB.LOADING }}</text>
        </template>

        <!-- 空 -->
        <template v-else-if="status === 'empty'">
            <!-- v1 u-icon → v2 tn-icon：默认空态图标 empty-list -->
            <tn-icon :name="emptyIcon" :size="96" class="empty__icon" aria-hidden="true" />
            <text class="empty__text">{{ emptyText || FB.EMPTY_DEFAULT }}</text>
            <text v-if="desc" class="empty__text empty__text--sub">{{ desc }}</text>
            <view v-if="showAction" class="empty__action">
                <base-button :text="actionText" size="sm" variant="ghost" @click="emit('action')" />
            </view>
        </template>

        <!-- 错误：先分清「能不能重试」，再决定给不给按钮 -->
        <template v-else-if="status === 'error'">
            <!-- v1 u-icon → v2 tn-icon：错误态图标随 KIND_MAP -->
            <tn-icon :name="kind.icon" :size="96" class="empty__icon" aria-hidden="true" />
            <text class="empty__text">{{ errorText || kind.text }}</text>
            <text v-if="kind.hint" class="empty__text empty__text--sub">{{ kind.hint }}</text>
            <!-- O5：business/permission 的可恢复入口，动词由 resolveErrorView 决定 -->
            <view v-if="primaryAction" class="empty__action">
                <base-button
                    :text="primaryAction.label"
                    size="sm"
                    variant="ghost"
                    @click="emit('primary')"
                />
            </view>
            <view v-else-if="kind.retry" class="empty__action">
                <base-button
                    :text="FB.ACTION_RETRY"
                    size="sm"
                    variant="ghost"
                    @click="emit('retry')"
                />
            </view>
            <view v-else-if="actionText" class="empty__action">
                <base-button :text="actionText" size="sm" variant="ghost" @click="emit('action')" />
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/**
 * 三态占位：loading / empty / error（完全受控）。
 *
 * error 必须区分「能不能重试」：
 *   network    断网/超时/5xx —— 给「重新加载」
 *   business   业务状态不允许（如订单已关闭）—— 重试也没用，不给按钮
 *   permission 无权限/未登录 —— 不给按钮，避免反复撞墙
 *   unknown    兜底，按可重试处理
 * 不分类型统一给「重新加载」，会让用户在不可恢复的错误上反复点。
 *
 * 契约：errorKind / 文案 / 动作全部由页面经 resolveErrorView 透传；组件不猜错误类型、
 * 不自行 reload、不发起请求；错误态不得伪装成空态；不可重试错误不给「重新加载」。
 */
import { computed } from 'vue'
import { FB } from '@/constants/feedback'
/** type-only 导入，避免与组件形成运行时循环依赖 */
import type { ErrorActionKind, ErrorKind } from '@/utils/resolveErrorView'

const props = withDefaults(
    defineProps<{
        status: 'normal' | 'loading' | 'empty' | 'error'
        /** 仅 status=error 时生效 */
        errorKind?: ErrorKind
        loadingText?: string
        emptyText?: string
        /** 传了就用它，覆盖 errorKind 的默认文案 */
        errorText?: string
        /** 副说明（空态/错误态通用） */
        desc?: string
        emptyIcon?: string
        actionText?: string
        showAction?: boolean
        /** 固定占满可视区（用于整页列表） */
        fixed?: boolean
        /** O5 新增：可恢复但非重试的主操作，动词来自 resolveErrorView */
        primaryAction?: { label: string; kind: ErrorActionKind }
    }>(),
    {
        errorKind: 'unknown',
        loadingText: '',
        emptyText: '',
        errorText: '',
        desc: '',
        emptyIcon: 'empty-list',
        actionText: '',
        showAction: false,
        fixed: false,
        primaryAction: undefined
    }
)

const emit = defineEmits<{
    (e: 'retry'): void
    (e: 'action'): void
    (e: 'primary'): void
}>()

/** 文案全部读 FB.*（O5 + O12）；errorText 仍可覆盖（保留 API） */
const KIND_MAP: Record<ErrorKind, { icon: string; text: string; hint: string; retry: boolean }> = {
    network: {
        // v1 u-icon → v2 tn-icon：empty-wifi 在 tn-icon 字体中不存在，改 empty-network
        icon: 'empty-network',
        text: FB.ERR_NETWORK,
        hint: FB.ERR_NETWORK_HINT,
        retry: true
    },
    business: {
        icon: 'empty-data',
        text: FB.ERR_BUSINESS,
        hint: '',
        retry: false
    },
    permission: {
        icon: 'empty-permission',
        text: FB.ERR_PERMISSION,
        hint: FB.ERR_PERMISSION_HINT,
        retry: false
    },
    unknown: {
        icon: 'empty-list',
        text: FB.ERR_BUSINESS,
        hint: '',
        retry: true
    }
}

const kind = computed(() => KIND_MAP[props.errorKind] || KIND_MAP.unknown)

/**
 * 根节点变体键：错误态用 errorKind，其它（empty）用 'empty'。
 * 图标颜色不能靠 :color 传 var()（tn-icon 只认 tn-* 类或 hex，var() 会被静默丢弃），
 * 所以把「哪种状态」变成 class，颜色写进 CSS。
 */
const kindKey = computed(() => (props.status === 'error' ? props.errorKind || 'unknown' : 'empty'))
</script>

<style lang="scss" scoped>
.empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: var(--sp-3);
    padding: var(--sp-6) var(--sp-4);

    /* 图标颜色：默认灰，业务失败态用琥珀（原 KIND_MAP.color 传 var() 无效） */
    &__icon {
        color: var(--c-ink-3);
    }

    &--business &__icon {
        color: var(--c-warning);
    }

    &--fixed {
        min-height: 60vh;
    }

    &__text {
        text-align: center;
        font-size: var(--fs-lg); /* --fs-xs → --fs-lg（A13 / O12） */
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
        line-height: var(--lh-tight);

        &--sub {
            font-size: var(--fs-md); /* 中文副说明最低 Caption 档 */
            font-weight: var(--fw-regular);
            color: var(--c-ink-3);
            line-height: var(--lh-body); /* 1.6 → --lh-body（O12） */
        }
    }

    &__action {
        margin-top: var(--sp-2);
    }
}
</style>
