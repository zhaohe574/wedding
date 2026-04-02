<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" hasSafeBottom>
        <BaseNavbar title="我的投诉" />

        <view class="complaint-page">
            <view class="complaint-page__head">
                <text class="complaint-page__title">投诉记录</text>
                <text class="complaint-page__desc">跟进处理进度，查看投诉结果与满意度评价。</text>
            </view>

            <z-paging ref="paging" v-model="dataList" use-page-scroll @query="queryList">
                <template #top>
                    <scroll-view
                        scroll-x
                        class="complaint-page__filter-scroll"
                        :show-scrollbar="false"
                    >
                        <view class="complaint-page__filters">
                            <view
                                v-for="item in statusTabs"
                                :key="String(item.value)"
                                class="complaint-page__filter"
                                :class="{ 'is-active': currentStatus === item.value }"
                                @click="changeStatus(item.value)"
                            >
                                {{ item.label }}
                            </view>
                        </view>
                    </scroll-view>
                </template>

                <view class="complaint-list">
                    <view
                        v-for="item in dataList"
                        :key="item.id"
                        class="complaint-card"
                        @click="goDetail(item.id)"
                    >
                        <view class="complaint-card__head">
                            <view class="complaint-card__badges">
                                <view
                                    class="complaint-card__level"
                                    :class="getLevelClass(item.level)"
                                >
                                    {{ item.level_desc || getLevelText(item.level) }}
                                </view>
                                <view
                                    class="complaint-card__status"
                                    :class="getStatusClass(item.status)"
                                >
                                    {{ item.status_desc || getStatusText(item.status) }}
                                </view>
                            </view>
                            <text class="complaint-card__time">{{ item.create_time }}</text>
                        </view>

                        <text class="complaint-card__title">{{ item.title }}</text>
                        <text v-if="item.content" class="complaint-card__content">{{
                            item.content
                        }}</text>

                        <view class="complaint-card__footer">
                            <text class="complaint-card__chip">
                                {{ item.type_desc || getTypeText(item.type) }}
                            </text>
                            <text class="complaint-card__arrow">查看详情</text>
                        </view>
                    </view>
                </view>

                <template #empty>
                    <view class="complaint-empty">
                        <tn-icon name="warning-circle" size="112" color="#D9CDC7" />
                        <text class="complaint-empty__title">暂无投诉记录</text>
                        <text class="complaint-empty__desc">
                            如遇服务异常、沟通问题或履约风险，可以随时发起投诉。
                        </text>
                    </view>
                </template>
            </z-paging>

            <view class="complaint-page__fab" @click="goCreate">
                <tn-icon name="plus" size="34" color="#FFFFFF" />
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { ref, shallowRef } from 'vue'
import { getComplaintLists } from '@/api/aftersale'
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
    { label: '待处理', value: 0 },
    { label: '处理中', value: 1 },
    { label: '已处理', value: 2 },
    { label: '已申诉', value: 3 }
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
        const res = await getComplaintLists(params)
        const lists = res?.data?.lists || res?.lists || []
        paging.value?.complete(lists)
    } catch (error) {
        paging.value?.complete(false)
    }
}

const getStatusClass = (status: number) => {
    const map: Record<number, string> = {
        0: 'status-pending',
        1: 'status-processing',
        2: 'status-handled',
        3: 'status-appealed',
        4: 'status-closed'
    }
    return map[status] || ''
}

const getStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待处理',
        1: '处理中',
        2: '已处理',
        3: '已申诉',
        4: '已关闭'
    }
    return map[status] || '未知'
}

const getLevelClass = (level: number) => {
    const map: Record<number, string> = {
        1: 'level-normal',
        2: 'level-serious',
        3: 'level-urgent'
    }
    return map[level] || ''
}

const getLevelText = (level: number) => {
    const map: Record<number, string> = {
        1: '一般',
        2: '严重',
        3: '紧急'
    }
    return map[level] || '一般'
}

const getTypeText = (type: number) => {
    const map: Record<number, string> = {
        1: '服务态度',
        2: '专业能力',
        3: '迟到早退',
        4: '违规行为',
        5: '其他'
    }
    return map[type] || '其他'
}

const goDetail = (id: number) => {
    uni.navigateTo({
        url: `/packages/pages/aftersale/complaint_detail?id=${id}`
    })
}

const goCreate = () => {
    uni.navigateTo({ url: '/packages/pages/aftersale/index?action=submit_complaint' })
}

onLoad((options: any) => {
    if (options?.status !== undefined) {
        currentStatus.value = Number(options.status)
    }
})
</script>

<style lang="scss" scoped>
@import '../../../styles/aftersale.scss';

.complaint-page {
    @include aftersale-page-base;
    padding: 0 0 150rpx;
}

.complaint-page__head {
    padding: 14rpx 20rpx 18rpx;
}

.complaint-page__title {
    display: block;
    font-size: 40rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.complaint-page__desc {
    display: block;
    margin-top: 10rpx;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--wm-text-secondary, #7f7b78);
}

.complaint-page__filter-scroll {
    margin-bottom: 18rpx;
}

.complaint-page__filters {
    @include aftersale-filter-tabs;
}

.complaint-page__filter {
    @include aftersale-filter-item;

    &.is-active {
        background: linear-gradient(135deg, var(--wm-color-primary, #e85a4f) 0%, #d9786d 100%);
        border-color: transparent;
        color: #ffffff;
        box-shadow: 0 12rpx 24rpx rgba(232, 90, 79, 0.18);
    }
}

.complaint-list {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 20rpx;
}

.complaint-card {
    @include aftersale-section-card;
}

.complaint-card__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.complaint-card__badges {
    display: flex;
    align-items: center;
    gap: 10rpx;
    flex-wrap: wrap;
}

.complaint-card__level,
.complaint-card__status {
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.complaint-card__time {
    font-size: 22rpx;
    color: var(--wm-text-tertiary, #b4aca8);
}

.complaint-card__title {
    display: block;
    margin-top: 18rpx;
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.35;
    color: var(--wm-text-primary, #1e2432);
}

.complaint-card__content {
    display: block;
    margin-top: 12rpx;
    font-size: 25rpx;
    line-height: 1.65;
    color: var(--wm-text-secondary, #7f7b78);
}

.complaint-card__footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-top: 18rpx;
}

.complaint-card__chip {
    padding: 8rpx 14rpx;
    border-radius: 999rpx;
    background: var(--wm-color-bg-soft, #fff7f4);
    font-size: 22rpx;
    color: var(--wm-color-primary, #e85a4f);
}

.complaint-card__arrow {
    font-size: 22rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.complaint-empty {
    @include aftersale-empty-state;
}

.complaint-empty__title {
    margin-top: 24rpx;
    font-size: 32rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.complaint-empty__desc {
    margin-top: 12rpx;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--wm-text-secondary, #7f7b78);
}

.complaint-page__fab {
    @include aftersale-fab;
    background: linear-gradient(135deg, #e85a4f 0%, #d9786d 100%);
}

.level-normal {
    color: #607086;
    background: rgba(96, 112, 134, 0.12);
}

.level-serious {
    color: #c98524;
    background: rgba(201, 133, 36, 0.12);
}

.level-urgent {
    color: #b44a3a;
    background: rgba(180, 74, 58, 0.12);
}

.status-pending {
    color: #c98524;
    background: rgba(201, 133, 36, 0.12);
}

.status-processing {
    color: #607086;
    background: rgba(96, 112, 134, 0.12);
}

.status-handled {
    color: #2f7d58;
    background: rgba(47, 125, 88, 0.12);
}

.status-appealed {
    color: #8f6ab5;
    background: rgba(143, 106, 181, 0.12);
}

.status-closed {
    color: #978b83;
    background: rgba(151, 139, 131, 0.12);
}
</style>
