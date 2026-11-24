<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="关于我们" bg="page" :show-back="true">
        <view class="page-wrap about">
            <view class="brand">
                <view class="brand__mark">
                    <!-- 图标取自后台「前台LOGO」；未配置或加载失败自动回退名称首字印章 -->
                    <brand-mark :size="144" variant="light" />
                </view>
                <text class="brand__name">{{ brand.name }}</text>
                <text v-if="brand.slogan" class="brand__slogan">{{ brand.slogan }}</text>
                <text class="brand__version">版本 {{ version }}</text>
            </view>

            <view class="about__card-wrap">
                <base-card :flush="true">
                    <view class="row" hover-class="row--hover" @tap="openAgreement('service')">
                        <text class="row__label">服务协议</text>
                        <!-- v1 u-icon → v2 tn-icon：arrow-right → right -->
                        <tn-icon name="right" :size="24" />
                    </view>
                    <view class="row" hover-class="row--hover" @tap="openAgreement('privacy')">
                        <text class="row__label">隐私政策</text>
                        <!-- v1 u-icon → v2 tn-icon：arrow-right → right -->
                        <tn-icon name="right" :size="24" />
                    </view>
                    <view class="row" hover-class="row--hover" @tap="goContact">
                        <text class="row__label">联系客服</text>
                        <!-- v1 u-icon → v2 tn-icon：arrow-right → right -->
                        <tn-icon name="right" :size="24" />
                    </view>
                </base-card>
            </view>

            <view v-if="copyrights.length" class="copyright">
                <view
                    hover-class="is-pressed"
                    v-for="(item, i) in copyrights"
                    :key="i"
                    class="copyright__item"
                    @tap="openCopyright(item)"
                >
                    <text class="copyright__text">{{ item.key }}</text>
                </view>
            </view>

            <text class="footer">用心记录每一场仪式</text>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/** 关于我们：品牌信息 + 协议入口 + 备案信息 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useAppStore } from '@/stores/app'
import { useBrand } from '@/composables/useBrand'
import { navigate } from '@/utils/link'
import { isAllowedWebviewUrl } from '@/constants/domains'
import { toast } from '@/composables/useToast'

const appStore = useAppStore()
/** 品牌（名称 / 图标 / Slogan）唯一来源；图标失败回退由 brand-mark 内部处理 */
const { brand, refresh: refreshBrand } = useBrand()

const version = ref('1.0.0')
const copyrights = computed<any[]>(() => {
    const raw = (appStore.config as any)?.copyright
    return Array.isArray(raw) ? raw : []
})

function openAgreement(type: string) {
    navigate({ path: '/pages/agreement/agreement', query: { type } })
}

function goContact() {
    navigate({ path: '/pages/customer-service/customer-service' })
}

function openCopyright(item: any) {
    const value = String(item?.value || '')
    if (!value) return
    // 判据从「是不是 https」改成「是否在白名单内」：备案类域名（如 beian.miit.gov.cn）
    // 永远进不了业务域名，跳 web-view 必白屏；不允许时改复制链接，用户自行检索。
    if (isAllowedWebviewUrl(value)) {
        navigate({ path: '/pages/webview/webview', query: { url: value } })
        return
    }
    uni.setClipboardData({
        data: value,
        success: () => toast.info('备案号链接已复制')
    })
}

onLoad(() => {
    try {
        const info = uni.getAccountInfoSync()
        version.value = (info as any)?.miniProgram?.version || '1.0.0'
    } catch {
        version.value = '1.0.0'
    }
    // 关于页是「验品牌配置」最直观的一屏：进来补拉一次，后台改完名称/图标能立刻看到
    refreshBrand().catch(() => undefined)
})
</script>

<style lang="scss" scoped>
.about {
    width: 100%;
    min-height: 0;
    background: var(--c-bg);
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
    overflow-x: hidden;
    /* 高度由 page-shell 承担：不继承 .page-wrap 的 100vh，避免再叠加导航条高度把页脚顶进底部安全区 */

    &__card-wrap {
        width: 100%;
        padding: 0 var(--sp-4);
        box-sizing: border-box;
    }
}

.brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--sp-6) var(--sp-4) var(--sp-5);
    text-align: center;

    &__mark {
        padding: var(--sp-2);
        margin-bottom: var(--sp-2); /* 品牌区四行间距统一到 --sp-2 节奏 */
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-lg);
        background: var(--c-hero-bg);
        box-shadow: var(--shadow-card);
    }

    &__name {
        max-width: 100%;
        font-size: var(--fs-2xl);
        font-weight: 500;
        line-height: var(--lh-tight);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__slogan {
        max-width: 100%;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
        overflow-wrap: anywhere;
    }

    &__version {
        margin-top: var(--sp-2);
        padding: var(--sp-1) var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        font-size: var(--fs-xs);
        color: var(--c-ink-2); /* --c-hero-bg 上不叠 --c-ink-3 小字 */
    }
}

.row {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    /* 右侧箭头继承父级；.row__label 自己声明了 --c-ink，不受影响 */
    color: var(--c-ink-3);
    min-height: 112rpx;
    padding: var(--sp-3) var(--sp-4);
    box-sizing: border-box;

    /* P2 §3-5①：分割线由位置决定，不再手写 .row--last —— 加行自动接上 */
    &:not(:last-child) {
        border-bottom: 1rpx solid var(--c-line);
    }

    &--hover {
        background: var(--c-surface-2);
    }

    &__label {
        flex-shrink: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }
}

.copyright {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--sp-1) var(--sp-3);
    padding: var(--sp-4) var(--sp-4) 0;

    &__item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 64rpx;
        max-width: 100%;
    }

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.footer {
    display: block;
    margin-top: var(--sp-6);
    padding: var(--sp-4) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
    text-align: center;
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}
</style>
