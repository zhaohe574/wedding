<template>
    <view class="page-wrap detail">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="4" variant="text" />
        </view>

        <base-empty v-else-if="error" status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title" :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed @primary="onPrimary" @retry="load" />

        <template v-else>
            <view class="page-pad head">
                <text class="head__title">{{ data.title }}</text>
                <view class="head__meta">
                    <text v-if="data.notify_type_text" class="head__type">{{ data.notify_type_text }}</text>
                    <text class="head__time">{{ data.create_time_text || data.create_time }}</text>
                </view>
            </view>

            <view class="page-pad body">
                <base-card>
                    <view class="body__content">
                        <u-parse v-if="isHtml" :html="data.content || ''" />
                        <text v-else class="body__text">{{ data.content || '（无正文）' }}</text>
                    </view>
                </base-card>

                <view v-if="hasTarget" class="action">
                    <base-button text="查看详情" variant="ink" size="lg" @click="goTarget" />
                </view>

                <view class="body__space" />
            </view>
        </template>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
/**
 * 消息详情。
 *
 * 列表页点击先进详情（完整正文），详情页再按 target_type 跳业务页 ——
 * 老端是列表直接跳，长消息在列表里被截断，用户看不到完整内容。
 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getNotificationDetail, type NotificationItem } from '@/api/message'
import { resolveNotificationRoute } from '@/composables/useNotificationRouter'
import { resolveErrorView } from '@/utils/resolveErrorView'

const id = ref(0)
const data = ref<Partial<NotificationItem>>({})
const loading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作 */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') { load(); return }
    if (kind === 'reselect' || kind === 'back') { uni.navigateBack(); return }
    if (kind === 'login') { uni.navigateTo({ url: '/pages/login/login' }); return }
    // contact / 其余：保持现有客服入口，不新增跳转
}

/** 后台可能配 HTML，也可能配纯文本 */
const isHtml = computed(() => /<\/?[a-z][\s\S]*>/i.test(String(data.value.content || '')))

const hasTarget = computed(() => {
    const d = data.value as any
    return !!(d.target_type || d.target_id || d.notify_type)
})

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getNotificationDetail({ id: id.value })
        if (!res) throw new Error('empty')
        data.value = res as any
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function goTarget() {
    resolveNotificationRoute(data.value as any)
}

onLoad((options: any) => {
    id.value = Number(options?.id || 0)
    if (!id.value) {
        error.value = true
        loading.value = false
        return
    }
    load()
})
</script>

<style lang="scss" scoped>
.detail {
    background: var(--c-bg);
}

.head {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding-top: var(--sp-4);
    padding-bottom: var(--sp-3);

    &__title {
        font-size: var(--fs-2xl);
        font-weight: 500;
        color: var(--c-ink);
        line-height: var(--lh-tight);
        overflow-wrap: anywhere;
    }

    &__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--sp-2) var(--sp-3);
    }

    &__type {
        flex-shrink: 0;
        padding: var(--sp-1) var(--sp-2);
        border-radius: var(--r-xs);
        background: var(--c-brand-bg);
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        line-height: var(--lh-body);
    }

    &__time {
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
        overflow-wrap: anywhere;
    }
}

.body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-4);

    &__content {
        max-width: 100%;
        box-sizing: border-box;
        font-family: var(--font-sans);
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        line-height: var(--lh-loose);
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    &__text {
        font-size: inherit;
        color: inherit;
        line-height: inherit;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    &__space {
        height: var(--sp-6);
    }
}

.action {
    margin-top: var(--sp-2);
}
/* 长文正文：--lh-loose + 图片自适应，避免溢出 */
.body__content { line-height: var(--lh-loose); font-size: var(--fs-md); }
.body__content image,
.body__content img { max-width: 100%; height: auto; }
</style>
