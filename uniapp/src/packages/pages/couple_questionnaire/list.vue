<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="新人问卷" />

        <view class="questionnaire-list wm-page-content">
            <view class="questionnaire-list__filters">
                <view
                    v-for="tab in tabs"
                    :key="tab.value"
                    class="questionnaire-list__tab"
                    :class="{ 'is-active': currentStatus === tab.value }"
                    @click="changeStatus(tab.value)"
                >
                    {{ tab.label }}
                </view>
            </view>

            <view v-if="listError" class="questionnaire-list__error">
                <EmptyState
                    title="问卷加载失败"
                    :description="listError"
                    actionText="重试"
                    @action="reloadList"
                />
            </view>

            <z-paging v-else ref="paging" v-model="dataList" use-page-scroll @query="queryList">
                <view class="questionnaire-list__items">
                    <BaseCard
                        v-for="item in dataList"
                        :key="item.id"
                        variant="surface"
                        scene="consumer"
                        :interactive="true"
                        class="questionnaire-card"
                        @click="goDetail(item.id)"
                    >
                        <view class="questionnaire-card__top">
                            <text class="questionnaire-card__title">
                                {{ item.title_snapshot || '新人问卷' }}
                            </text>
                            <StatusBadge
                                class="questionnaire-card__status"
                                :tone="getStatusTone(item.status)"
                                size="sm"
                                strong
                            >
                                {{ item.status_desc || getStatusText(item.status) }}
                            </StatusBadge>
                        </view>
                        <text class="questionnaire-card__meta">
                            关联订单：{{ item.order?.order_sn || item.order_id || '-' }}
                        </text>
                        <text class="questionnaire-card__meta">
                            服务人员：{{ item.staff?.name || '待补充' }}
                        </text>
                        <view class="questionnaire-card__meta-row">
                            <text class="questionnaire-card__meta">版本：v{{ item.version_no || '-' }}</text>
                            <text class="questionnaire-card__meta">推送：{{ item.last_send_time || item.send_time || item.send_status_desc || '待推送' }}</text>
                        </view>
                    </BaseCard>
                </view>

                <template #empty>
                    <EmptyState
                        title="暂无新人问卷"
                        description="服务人员发送问卷后会显示在这里，也可从订单详情或站内消息进入。"
                        actionText="刷新看看"
                        @action="reloadList"
                    />
                </template>
            </z-paging>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import { useThemeStore } from '@/stores/theme'
import { getCoupleQuestionnaireLists } from '@/api/coupleQuestionnaire'

const $theme = useThemeStore()
const paging = ref<any>(null)
const dataList = ref<any[]>([])
const listError = ref('')
const currentStatus = ref<string | number>('')
const tabs = [
    { label: '全部', value: '' },
    { label: '待填写', value: 0 },
    { label: '已填写', value: 1 }
]
type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'primary'

const queryList = async (pageNo: number, pageSize: number) => {
    listError.value = ''
    try {
        const res = await getCoupleQuestionnaireLists({
            page: pageNo,
            limit: pageSize,
            status: currentStatus.value
        })
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error: any) {
        listError.value = error?.message || error || '请检查网络后重试，或从站内消息重新进入问卷。'
        paging.value?.complete(false)
    }
}

const reloadList = () => {
    listError.value = ''
    paging.value?.reload()
}

const getStatusText = (status: number | string) => {
    const value = Number(status || 0)
    if (value === 1) return '已填写'
    if (value === 2) return '已取消'
    return '待填写'
}

const getStatusTone = (status: number | string): BadgeTone => {
    const value = Number(status || 0)
    if (value === 1) return 'success'
    if (value === 2) return 'neutral'
    return 'warning'
}

const changeStatus = (value: string | number) => {
    currentStatus.value = value
    reloadList()
}

const goDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/couple_questionnaire/detail?id=${id}`
    })
}

onLoad((options: any) => {
    if (options?.status !== undefined) {
        currentStatus.value = options.status === '' ? '' : Number(options.status)
    }
})
</script>

<style scoped lang="scss">
.questionnaire-list {
    min-height: 100vh;
    padding-top: 16rpx;
}

.questionnaire-list__error {
    padding-top: 60rpx;
}

.questionnaire-list__filters {
    display: flex;
    gap: 12rpx;
    margin-bottom: 18rpx;
}

.questionnaire-list__tab {
    padding: 14rpx 24rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.72);
    color: var(--wm-text-secondary, #5f5a50);
    font-size: 24rpx;
    font-weight: 600;

    &.is-active {
        background: var(--wm-color-primary, #111111);
        color: #ffffff;
    }
}

.questionnaire-list__items {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.questionnaire-card {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.questionnaire-card__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.questionnaire-card__title {
    flex: 1;
    min-width: 0;
    color: var(--wm-text-primary, #111111);
    font-size: 30rpx;
    font-weight: 700;
}

.questionnaire-card__status {
    flex-shrink: 0;
}

.questionnaire-card__meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.questionnaire-card__meta {
    display: block;
    color: var(--wm-text-secondary, #5f5a50);
    font-size: 24rpx;
    line-height: 1.6;
}
</style>
