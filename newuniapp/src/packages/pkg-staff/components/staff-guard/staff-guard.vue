<template>
    <view v-if="phase === 'checking'" class="staff-guard__pad">
        <base-skeleton :rows="skeletonRows" variant="card" />
    </view>

    <base-empty
        v-else-if="phase === 'denied'"
        status="empty"
        empty-icon="empty-permission"
        :empty-text="deniedTitle"
        :desc="deniedDesc"
        :show-action="true"
        :action-text="deniedActionText"
        fixed
        @action="emit('action')"
    />

    <base-empty
        v-else-if="phase === 'error'"
        status="error"
        :error-kind="errorView?.errorKind || 'unknown'"
        :error-text="errorView?.title"
        :desc="errorView?.desc"
        :primary-action="errorView?.primaryAction"
        fixed
        @primary="onErrorPrimary"
        @retry="emit('retry')"
    />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { FB } from '@/constants/feedback'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { toLogin } from '@/utils/login'
import type { StaffGuardPhase, StaffGuardReason } from '@/composables/useStaffGuard'

const props = withDefaults(
    defineProps<{
        phase: StaffGuardPhase
        reason: StaffGuardReason
        error?: Record<string, any> | Error | string | null
        skeletonRows?: number
    }>(),
    {
        error: null,
        skeletonRows: 4
    }
)

const emit = defineEmits<{
    (e: 'retry'): void
    (e: 'action'): void
}>()

const deniedTitle = computed(() => (props.reason === 'unauth' ? FB.EMPTY_LOGIN : FB.EMPTY_PERMISSION))

const deniedDesc = computed(() => {
    if (props.reason === 'unauth') return '请先登录后再访问服务人员工作台'
    if (props.reason === 'disabled') return '服务人员工作台暂未开放，如有疑问请联系客服'
    return '该页面仅对已认证服务人员开放，如需开通请联系门店管理员'
})

const deniedActionText = computed(() => {
    if (props.reason === 'unauth') return FB.ACTION_LOGIN
    return getCurrentPages().length > 1 ? FB.ACTION_BACK : FB.ACTION_HOME
})

const errorView = computed(() => resolveErrorView(props.error as any, { hasForm: false }))

function onErrorPrimary(): void {
    const kind = errorView.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        emit('retry')
        return
    }
    if (kind === 'login') {
        toLogin()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        emit('action')
    }
}
</script>

<style lang="scss" scoped>
.staff-guard {
    &__pad {
        padding: var(--sp-3) var(--sp-4);
    }
}
</style>
