<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell :title="title || '协议'" bg="page" :show-back="true">
        <view class="page-wrap agree-page">
            <view v-if="loading" class="page-pad agree-page__loading">
                <base-skeleton :rows="5" variant="text" />
            </view>

            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="load"
            />

            <view v-else class="page-pad agree-page__body">
                <view class="agree-page__header">
                    <!-- P2 §3-6①：删 eyebrow「协议与说明」——它说"这是一份协议"，下一行 title 说
                         "哪一份协议"，信息被 title 完全覆盖且两行相邻，重复感最强。
                         §3-6②：删 meta「请仔细阅读以下内容，了解服务约定与相关说明」——它是
                         教用户怎么读的元指令，不是内容；用户点进协议页已表明要读。
                         §3-6③：正文去掉 base-card 包裹——卡片自带 --sp-4 内边距，
                         正文又有自己的内边距，两层叠加让阅读区变窄；v2 里长文本正文不套卡片。 -->
                    <text class="agree-page__title">{{ title }}</text>
                </view>
                <u-parse class="agree-page__content" :html="content" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/** 协议页：type = service | privacy | booking | aftersale，内容来自后台配置 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { getPolicy } from '@/api/app'

const DEFAULT_TITLES: Record<string, string> = {
    service: '服务协议',
    privacy: '隐私政策',
    booking: '预约服务协议',
    aftersale: '售后服务规则'
}

const title = ref('')
const content = ref('')
const loading = ref(true)
const error = ref(false)
/** 接口成功但正文为空：单独的错误态，不用占位 HTML 掩盖空数据 */
const policyMissing = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => {
    // 后台未配置正文：走同一个 base-empty 错误态，不另起一块硬编码。
    // errorKind 用 unknown（KIND_MAP.unknown.retry=true），重试按钮由 base-empty
    // 自带 FB.ACTION_RETRY 渲染，页面不写中文状态字面量。
    if (policyMissing.value) {
        return {
            errorKind: 'unknown' as const,
            title: '协议暂未配置，请稍后重试',
            desc: '',
            primaryAction: undefined
        }
    }
    return resolveErrorView(rawError.value as any, { hasForm: false })
})
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
let type = 'service'

async function load() {
    loading.value = true
    error.value = false
    policyMissing.value = false
    rawError.value = null
    try {
        const res = await getPolicy({ type })
        title.value = res?.title || DEFAULT_TITLES[type] || '服务协议'
        const text = String(res?.content || '').trim()
        if (!text) {
            // 不再用 '<p>协议内容暂未配置</p>' 冒充正文
            content.value = ''
            policyMissing.value = true
            error.value = true
            return
        }
        content.value = text
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

onLoad((options: any) => {
    if (options?.type) type = String(options.type)
    load()
})
</script>

<style lang="scss" scoped>
.agree-page {
    &__loading {
        padding-top: var(--sp-3);
    }

    &__body {
        padding-top: var(--sp-5);
        padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
    }

    &__header {
        margin-bottom: var(--sp-4);
    }

    &__title {
        display: block;
        margin: var(--sp-2) 0;
        font-size: var(--fs-2xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__content {
        font-size: var(--fs-md);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
        word-break: break-word;

        ::v-deep p {
            margin: 0 0 var(--sp-3);
        }

        ::v-deep h1,
        ::v-deep h2,
        ::v-deep h3,
        ::v-deep h4 {
            margin: var(--sp-4) 0 var(--sp-2);
            font-weight: 500;
            line-height: var(--lh-body);
            color: var(--c-ink);
        }

        ::v-deep img,
        ::v-deep video,
        ::v-deep table {
            max-width: 100% !important;
            box-sizing: border-box;
        }

        ::v-deep a {
            color: var(--c-brand-text);
            word-break: break-all;
        }
    }
}
</style>
