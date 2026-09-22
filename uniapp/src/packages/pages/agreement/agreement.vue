<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer" tone="detail" hasSafeBottom>
        <BaseNavbar
            :title="pageTitle"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
            title-align="center"
        />

        <view class="agreement-page">
            <BaseCard
                v-if="loading"
                class="agreement-state-card"
                variant="list"
                scene="consumer"
                padding="40rpx 24rpx"
                border-radius="26rpx"
            >
                <LoadingState text="正在加载协议条款..." tone="wedding" compact />
            </BaseCard>

            <BaseCard
                v-else-if="errorMessage"
                class="agreement-state-card"
                variant="list"
                scene="consumer"
                padding="40rpx 24rpx"
                border-radius="26rpx"
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
                padding="40rpx 24rpx"
                border-radius="26rpx"
            >
                <EmptyState
                    title="暂无协议内容"
                    description="当前协议条款尚未发布，请稍后刷新重试。"
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
                border-radius="26rpx"
            >
                <view class="agreement-content-card__header">
                    <view class="agreement-content-card__title-wrap">
                        <BaseIcon name="shield-check" :size="28" color="#B8954A" />
                        <text class="agreement-content-card__title">平台官方认证条款</text>
                    </view>

                    <StatusBadge tone="warning" size="xs">
                        {{ agreementBadgeText }}
                    </StatusBadge>
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
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { AgreementEnum } from '@/enums/agreementEnums'
import { useThemeStore } from '@/stores/theme'

const agreementType = ref('')
const pageTitle = ref('协议')
const agreementContent = ref('')
const loading = ref(false)
const errorMessage = ref('')
const $theme = useThemeStore()

const agreementBadgeText = computed(() => {
    if (agreementType.value === AgreementEnum.PRIVACY) return '隐私保护指引'
    if (agreementType.value === AgreementEnum.SERVICE) return '用户服务条款'
    return '官方协议'
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
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 18rpx 22rpx calc(48rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
}

.agreement-state-card {
    background: rgba(255, 253, 248, 0.96) !important;
    border: 1rpx solid rgba(216, 201, 173, 0.72) !important;
}

.agreement-content-card {
    background: rgba(255, 253, 248, 0.96) !important;
    border: 1rpx solid rgba(216, 201, 173, 0.75) !important;
    box-shadow: 0 10rpx 26rpx rgba(74, 43, 24, 0.05) !important;
}

.agreement-content-card__header {
    min-height: 84rpx;
    padding: 0 24rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    border-bottom: 1rpx dashed rgba(216, 201, 173, 0.6);
}

.agreement-content-card__title-wrap {
    display: flex;
    align-items: center;
    gap: 10rpx;
    min-width: 0;
}

.agreement-content-card__title {
    font-size: 26rpx;
    font-weight: 900;
    line-height: 1.4;
    color: var(--wm-text-primary, #191713);
}

.agreement-rich-text {
    padding: 26rpx 24rpx 36rpx;
    box-sizing: border-box;
    font-size: 26rpx;
    line-height: 1.85;
    color: var(--wm-text-secondary, #4a453d);
    word-break: break-word;
}

.agreement-rich-text :deep(p),
.agreement-rich-text :deep(div) {
    margin: 0 0 20rpx;
    line-height: 1.85;
    color: var(--wm-text-secondary, #4a453d);
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
    margin: 24rpx 0 14rpx;
    line-height: 1.4;
}

.agreement-rich-text :deep(img) {
    max-width: 100%;
    border-radius: 18rpx;
}
</style>
