<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="消息详情" bg="page" :show-back="true">
        <view class="page-wrap detail">
            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="4" variant="text" />
            </view>

            <!-- 业务态（消息已删除/无权限）：不可重试、不给按钮（P0 §2-4）。
                 business/permission 的 retry 恒为 false，故 @retry 不会渲染按钮；
                 这里额外把 primary-action 置空，避免出现「返回上一页」这类无效动作。 -->
            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="notFound ? 'business' : view?.errorKind || 'unknown'"
                :error-text="notFound ? FB.EMPTY_REMOVED : view?.title"
                :desc="notFound ? FB.ERR_PERMISSION_HINT : view?.desc"
                :primary-action="isTerminal ? undefined : view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="load"
            />

            <template v-else>
                <view class="page-pad head">
                    <text class="head__title">{{ data.title }}</text>
                    <view class="head__meta">
                        <!-- 样板页：v1 手写纯文字标签 → v2 status-tag（消息类型非状态，dot 关掉） -->
                        <status-tag
                            v-if="data.notify_type_text"
                            size="sm"
                            :label="data.notify_type_text"
                            :dot="false"
                        />
                        <text class="head__time">{{
                            data.create_time_text || data.create_time
                        }}</text>
                    </view>
                </view>

                <view class="page-pad body">
                    <base-card>
                        <view class="body__content">
                            <u-parse v-if="isHtml" :html="data.content || ''" />
                            <text v-else class="body__text">{{
                                data.content || '（无正文）'
                            }}</text>
                        </view>
                    </base-card>

                    <view v-if="hasTarget" class="action">
                        <base-button text="查看详情" variant="ink" size="lg" @click="goTarget" />
                    </view>
                </view>
            </template>
        </view>
        <app-toast />
    </page-shell>
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
import { FB } from '@/constants/feedback'

const id = ref(0)
const data = ref<Partial<NotificationItem>>({})
const loading = ref(true)
const error = ref(false)
/** 接口返回空数据 = 消息已删除/撤回：这是业务态，不是"可重试的异常" */
const notFound = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作 */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
/**
 * 业务/权限类不可恢复：不渲染任何动作按钮（P0 §2-4「不可重试，不给按钮」）。
 * ⚠️ 坑：`resolveErrorView` 对 business **总是**附带 primaryAction（「返回上一页」），
 * 所以页面必须自己把 primary-action 置空，否则会多出一个按钮。
 * 目前 2 页（本页 + article-detail）各自置空；**出现第 3 页时收编进 resolveErrorView**
 * （给 business 加「不附带 primaryAction」的开关）——2 次是重复，3 次才是模式。
 */
const isTerminal = computed(
    () => notFound.value || ['business', 'permission'].includes(view.value?.errorKind ?? '')
)
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
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
    notFound.value = false
    try {
        const res = await getNotificationDetail({ id: id.value })
        // 空数据不再 throw new Error('empty')：那会落到 unknown 分支给一个永远点不成功的
        // 「重新加载」。消息已删除是确定性业务态，按 business 处理（P0 §2-4）。
        if (!res) {
            notFound.value = true
            error.value = true
            return
        }
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
}

.action {
    margin-top: var(--sp-2);
}
/* 长文正文：--lh-loose + 图片自适应，避免溢出 */
.body__content {
    line-height: var(--lh-loose);
    font-size: var(--fs-md);

    /* 样板页：后端 HTML 自带 1.6/1.7 行高会让正文跳档，段落行高收口到 --lh-body。
       ⚠️ mp-weixin 没有 <p> 标签：u-parse 把 <p> 渲染成 <rich-text class="_p __p">，
       只写 :deep(p) 在小程序端是空规则，所以两套选择器都要挂（p 走 H5，.__p 走小程序）。 */
    :deep(p),
    :deep(.__p) {
        line-height: var(--lh-body);
    }
}
.body__content image,
.body__content img {
    max-width: 100%;
    height: auto;
}
/* 批 1.5 ⑤：上面两条标签选择器在小程序端是空规则 —— u-parse 把 <img> 渲染成
   <image class="_image">，WXML 里既没有 img 也没有 image 标签。
   与 .__p 同一类问题，用同一套写法：img 走 H5，._image 走小程序。 */
.body__content :deep(img),
.body__content :deep(._image) {
    max-width: 100%;
    height: auto;
}
</style>
