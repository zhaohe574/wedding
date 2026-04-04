<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="回访问卷" />

        <view class="callback-page">
            <view class="callback-page__head">
                <text class="callback-page__title">回访问卷</text>
                <text class="callback-page__desc">服务完成后的评价与反馈会沉淀在这里。</text>
            </view>

            <z-paging ref="paging" v-model="dataList" use-page-scroll @query="queryList">
                <template #top>
                    <scroll-view
                        scroll-x
                        class="callback-page__filter-scroll"
                        :show-scrollbar="false"
                    >
                        <view class="callback-page__filters">
                            <view
                                v-for="item in statusTabs"
                                :key="String(item.value)"
                                class="callback-page__filter"
                                :class="{ 'is-active': currentStatus === item.value }"
                                @click="changeStatus(item.value)"
                            >
                                {{ item.label }}
                            </view>
                        </view>
                    </scroll-view>
                </template>

                <view class="callback-list">
                    <view
                        v-for="item in dataList"
                        :key="item.id"
                        class="callback-card"
                        @click="goDetail(item)"
                    >
                        <view class="callback-card__head">
                            <view>
                                <text class="callback-card__sn">
                                    {{
                                        item.callback_sn ||
                                        item.order?.order_sn ||
                                        `回访 #${item.id}`
                                    }}
                                </text>
                                <text class="callback-card__title">
                                    {{ item.type_desc || '服务回访问卷' }}
                                </text>
                            </view>
                            <view
                                class="callback-card__status"
                                :class="getStatusClass(item.status)"
                            >
                                {{ item.status_desc || getStatusText(item.status) }}
                            </view>
                        </view>

                        <view class="callback-card__meta-list">
                            <view class="callback-card__meta-item">
                                <text class="callback-card__meta-label">计划时间</text>
                                <text class="callback-card__meta-value">
                                    {{ item.plan_time || item.create_time }}
                                </text>
                            </view>
                            <view
                                v-if="item.staff?.name || item.staff_name"
                                class="callback-card__meta-item"
                            >
                                <text class="callback-card__meta-label">服务人员</text>
                                <text class="callback-card__meta-value">
                                    {{ item.staff?.name || item.staff_name }}
                                </text>
                            </view>
                        </view>

                        <view class="callback-card__footer">
                            <text class="callback-card__time">{{ item.create_time }}</text>
                            <view v-if="item.score" class="callback-card__score">
                                <tn-icon name="star-fill" size="24" color="#C98524" />
                                <text>{{ item.score }} 分</text>
                            </view>
                        </view>
                    </view>
                </view>

                <template #empty>
                    <view class="callback-empty">
                        <tn-icon name="edit" size="112" color="#D9CDC7" />
                        <text class="callback-empty__title">暂无回访问卷</text>
                        <text class="callback-empty__desc">
                            服务结束后生成的评价问卷，会在这里提醒您填写。
                        </text>
                    </view>
                </template>
            </z-paging>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import { getCallbackLists } from '@/api/aftersale'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useThemeStore } from '@/stores/theme'
import { onLoad } from '@dcloudio/uni-app'

const $theme = useThemeStore()
const paging = shallowRef()
const dataList = ref<any[]>([])
const currentStatus = ref<number | string>('')

const statusTabs = [
    { label: '全部', value: '' },
    { label: '待填写', value: 0 },
    { label: '已完成', value: 1 }
]

const changeStatus = (status: number | string) => {
    currentStatus.value = status
    paging.value?.reload()
}

const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const params: any = {
            page: pageNo,
            limit: pageSize
        }
        if (currentStatus.value !== '') {
            params.status = currentStatus.value
        }
        const res = await getCallbackLists(params)
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const getStatusClass = (status: number) => {
    return status === 0 ? 'status-pending' : 'status-completed'
}

const getStatusText = (status: number) => {
    return status === 0 ? '待填写' : '已完成'
}

const goDetail = (item: any) => {
    uni.navigateTo({
        url: `/packages/pages/aftersale/callback_detail?id=${item.id}`
    })
}

onLoad((options: any) => {
    if (options?.status !== undefined) {
        currentStatus.value = Number(options.status)
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.callback-page {
    @include aftersale-page-base;
    padding-bottom: 32rpx;
}

.callback-page__head {
    padding: 14rpx 20rpx 18rpx;
}

.callback-page__title {
    display: block;
    font-size: 40rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.callback-page__desc {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.callback-page__filter-scroll {
    margin-bottom: 18rpx;
}

.callback-page__filters {
    @include aftersale-filter-tabs;
}

.callback-page__filter {
    @include aftersale-filter-item;

    &.is-active {
        background: linear-gradient(135deg, #e85a4f 0%, #c99b73 100%);
        border-color: transparent;
        color: #ffffff;
        box-shadow: 0 12rpx 24rpx rgba(201, 155, 115, 0.2);
    }
}

.callback-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 20rpx;
}

.callback-card {
    @include aftersale-section-card;
}

.callback-card__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16rpx;
}

.callback-card__sn {
    display: block;
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.callback-card__title {
    display: block;
    margin-top: 10rpx;
    font-size: 30rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.callback-card__status {
    flex-shrink: 0;
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.callback-card__meta-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 18rpx;
}

.callback-card__meta-item {
    display: flex;
    gap: 14rpx;
}

.callback-card__meta-label {
    width: 110rpx;
    flex-shrink: 0;
    font-size: 24rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.callback-card__meta-value {
    flex: 1;
    min-width: 0;
    font-size: 25rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.callback-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 18rpx;
}

.callback-card__time {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.callback-card__score {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 24rpx;
    color: #c98524;
}

.callback-empty {
    @include aftersale-empty-state;
}

.callback-empty__title {
    margin-top: 24rpx;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.callback-empty__desc {
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.status-pending {
    color: #c98524;
    background: rgba(201, 133, 36, 0.12);
}

.status-completed {
    color: #2f7d58;
    background: rgba(47, 125, 88, 0.12);
}
</style>
