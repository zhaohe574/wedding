<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer" tone="detail">
        <BaseNavbar
            :title="pageTitle"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="agreement-page wm-page-content">
            <BaseCard
                v-if="loading"
                class="agreement-state-card"
                variant="list"
                scene="consumer"
                padding="0"
            >
                <LoadingState text="正在加载协议..." tone="wedding" compact />
            </BaseCard>

            <BaseCard
                v-else-if="errorMessage"
                class="agreement-state-card"
                variant="list"
                scene="consumer"
                padding="0"
            >
                <EmptyState
                    title="协议加载失败"
                    :description="errorMessage"
                    action-text="重新加载"
                    tone="error"
                    compact
                    @action="reloadAgreement"
                />
            </BaseCard>

            <BaseCard
                v-else-if="!hasAgreementContent"
                class="agreement-state-card"
                variant="list"
                scene="consumer"
                padding="0"
            >
                <EmptyState
                    title="暂无协议内容"
                    description="当前协议暂未配置，请稍后再试。"
                    tone="neutral"
                    compact
                />
            </BaseCard>

            <BaseCard
                v-else
                class="agreement-content-card"
                variant="list"
                scene="consumer"
                padding="0"
            >
                <view class="agreement-content-card__header">
                    <text class="agreement-content-card__title">协议正文</text>

                    <text class="agreement-content-card__meta">{{ agreementBadgeText }}</text>
                </view>

                <view class="agreement-rich-text">
                    <u-parse :html="agreementContent"></u-parse>
                </view>
            </BaseCard>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { getPolicy } from '@/api/app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import { AgreementEnum } from '@/enums/agreementEnums'
import { useThemeStore } from '@/stores/theme'

const agreementType = ref('')
const pageTitle = ref('协议')
const agreementContent = ref('')
const loading = ref(false)
const errorMessage = ref('')
const $theme = useThemeStore()

const agreementBadgeText = computed(() => {
    if (agreementType.value === AgreementEnum.PRIVACY) return '隐私协议'
    if (agreementType.value === AgreementEnum.SERVICE) return '服务协议'
    return '协议说明'
})

const hasAgreementContent = computed(() => String(agreementContent.value || '').trim().length > 0)

const getData = async (type: string) => {
    const safeType = String(type || AgreementEnum.SERVICE).trim()
    agreementType.value = safeType
    loading.value = true
    errorMessage.value = ''

    try {
        const res = await getPolicy({ type: safeType })
        agreementContent.value = String(res?.content || '')
        pageTitle.value = String(res?.title || agreementBadgeText.value)
    } catch (error: any) {
        agreementContent.value = ''
        errorMessage.value = error?.message || '网络连接异常，请稍后重试。'
    } finally {
        loading.value = false
    }
}

const reloadAgreement = () => {
    void getData(agreementType.value || AgreementEnum.SERVICE)
}

onLoad((options: any) => {
    void getData(String(options?.type || AgreementEnum.SERVICE))
})
</script>

<style lang="scss" scoped>
.agreement-page {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding-top: 24rpx;
    padding-bottom: calc(48rpx + env(safe-area-inset-bottom));
}

.agreement-state-card,
.agreement-content-card {
    display: block;
}

.agreement-content-card__header {
    min-height: 86rpx;
    padding: 0 30rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    border-bottom: 1rpx solid rgba(216, 201, 173, 0.78);
}

.agreement-content-card__title {
    min-width: 0;
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.4;
    color: var(--wm-text-primary, #191713);
}

.agreement-content-card__meta {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1.4;
    color: var(--wm-color-gold, #B8954A);
}

.agreement-rich-text {
    padding: 30rpx;
    box-sizing: border-box;
    font-size: 28rpx;
    line-height: 1.82;
    color: var(--wm-text-primary, #191713);
    word-break: break-word;
}

.agreement-rich-text :deep(p),
.agreement-rich-text :deep(div) {
    margin: 0 0 22rpx;
    line-height: 1.82;
    color: var(--wm-text-primary, #191713);
}

.agreement-rich-text :deep(h1),
.agreement-rich-text :deep(h2),
.agreement-rich-text :deep(h3),
.agreement-rich-text :deep(strong) {
    font-weight: 900;
    color: var(--wm-text-primary, #191713);
}

.agreement-rich-text :deep(h1),
.agreement-rich-text :deep(h2),
.agreement-rich-text :deep(h3) {
    margin: 26rpx 0 16rpx;
    line-height: 1.42;
}

.agreement-rich-text :deep(img) {
    max-width: 100%;
    border-radius: 20rpx;
}
</style>
