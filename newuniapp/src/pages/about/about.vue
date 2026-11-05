<template>
    <view class="page-wrap about">
        <view class="brand">
            <view class="brand__mark">
                <image
                    v-if="logo && !logoFailed"
                    class="brand__logo"
                    :src="logo"
                    mode="aspectFit"
                    @error="logoFailed = true"
                />
                <view v-else class="brand__logo brand__logo--empty">
                    <u-icon name="photo" :size="48" color="var(--c-brand-text)" />
                </view>
            </view>
            <text class="brand__name">{{ shopName }}</text>
            <text v-if="slogan" class="brand__slogan">{{ slogan }}</text>
            <text class="brand__version">版本 {{ version }}</text>
        </view>

        <base-card class="about__card" :flush="true">
            <view class="row" hover-class="row--hover" @tap="openAgreement('service')">
                <text class="row__label">服务协议</text>
                <text class="row__hint">服务内容与约定</text>
                <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
            </view>
            <view class="row" hover-class="row--hover" @tap="openAgreement('privacy')">
                <text class="row__label">隐私政策</text>
                <text class="row__hint">信息使用与保护</text>
                <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
            </view>
            <view class="row row--last" hover-class="row--hover" @tap="goContact">
                <text class="row__label">联系客服</text>
                <text class="row__hint">咨询与帮助</text>
                <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
            </view>
        </base-card>

        <view v-if="copyrights.length" class="copyright">
            <view hover-class="is-pressed"
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
</template>

<script setup lang="ts">
/** 关于我们：品牌信息 + 协议入口 + 备案信息 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { useAppStore } from '@/stores/app'
import { navigate } from '@/utils/link'

const appStore = useAppStore()

const version = ref('1.0.0')
// 仅处理 Logo 加载失败的表现层兜底，不影响品牌配置。
const logoFailed = ref(false)

const website = computed<any>(() => (appStore.config && (appStore.config as any).website) || {})
const shopName = computed(() => website.value.shop_name || '婚礼服务')
const slogan = computed(() => website.value.shop_slogan || '')
const logo = computed(() => website.value.shop_logo || '')
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
    if (/^https:\/\//i.test(value)) {
        navigate({ path: '/pages/webview/webview', query: { url: value } })
        return
    }
    uni.setClipboardData({ data: value })
}

onLoad(() => {
    try {
        const info = uni.getAccountInfoSync()
        version.value = (info as any)?.miniProgram?.version || '1.0.0'
    } catch {
        version.value = '1.0.0'
    }
})
</script>

<style lang="scss" scoped>
.about {
    background: var(--c-bg);
    display: flex;
    flex-direction: column;
    min-height: 100vh;

    &__card {
        margin: 0 var(--sp-4);
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
        margin-bottom: var(--sp-3);
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-lg);
        background: var(--c-hero-bg);
        box-shadow: var(--shadow-card);
    }

    &__logo {
        display: block;
        width: 144rpx;
        height: 144rpx;
        border-radius: var(--r-lg);
        background: var(--c-surface);

        &--empty {
            display: flex;
            align-items: center;
            justify-content: center;
        }
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
        margin-top: var(--sp-3);
        padding: var(--sp-1) var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        font-size: var(--fs-xs);
        color: var(--c-ink-2);   /* --c-hero-bg 上不叠 --c-ink-3 小字 */
    }
}

.row {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    min-height: 112rpx;
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &--last {
        border-bottom: none;
    }

    &--hover {
        background: var(--c-surface-2);
    }

    &__label {
        flex-shrink: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__hint {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
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
    margin-top: auto;
    padding: var(--sp-5) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
    text-align: center;
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}
</style>
