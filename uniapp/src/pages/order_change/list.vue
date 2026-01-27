<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="我的申请"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>
    <view class="change-list">
        <!-- 类型筛选 -->
        <view class="type-tabs bg-white sticky top-0 z-10">
            <scroll-view scroll-x class="whitespace-nowrap">
                <view
                    v-for="tab in typeTabs"
                    :key="tab.value"
                    class="inline-block px-4 py-3 text-sm"
                    :class="
                        currentType === tab.value
                            ? 'text-primary border-b-2 border-primary font-medium'
                            : 'text-gray-500'
                    "
                    @click="changeType(tab.value)"
                >
                    {{ tab.label }}
                </view>
            </scroll-view>
        </view>

        <!-- 列表 -->
        <view class="p-3">
            <view v-if="loading && list.length === 0" class="py-20 text-center text-gray-400">
                加载中...
            </view>
            <view v-else-if="list.length === 0" class="py-20 text-center text-gray-400">
                <image
                    src="/static/images/empty.png"
                    class="w-32 h-32 mx-auto mb-4"
                    mode="aspectFit"
                />
                <text>暂无申请记录</text>
            </view>
            <view v-else>
                <!-- 变更申请列表 -->
                <template v-if="currentType === 'change'">
                    <view
                        v-for="item in list"
                        :key="item.id"
                        class="bg-white rounded-lg mb-3 p-4"
                        @click="goChangeDetail(item.id)"
                    >
                        <view class="flex justify-between items-start mb-2">
                            <view>
                                <text class="text-xs text-gray-400">{{ item.change_sn }}</text>
                                <view class="flex items-center mt-1">
                                    <view class="tag" :class="getTypeClass(item.change_type)">
                                        {{ item.change_type_desc }}
                                    </view>
                                    <view
                                        class="tag ml-2"
                                        :class="getStatusClass(item.change_status)"
                                    >
                                        {{ item.change_status_desc }}
                                    </view>
                                </view>
                            </view>
                            <text class="text-xs text-gray-400">{{ item.create_time }}</text>
                        </view>

                        <!-- 改期 -->
                        <view v-if="item.change_type === 1" class="text-sm text-gray-600 mt-2">
                            <text>服务日期: </text>
                            <text class="text-gray-400">{{ item.old_service_date }}</text>
                            <text class="mx-1">→</text>
                            <text class="text-primary">{{ item.new_service_date }}</text>
                        </view>

                        <!-- 换人 -->
                        <view v-else-if="item.change_type === 2" class="text-sm text-gray-600 mt-2">
                            <text>人员: </text>
                            <text class="text-gray-400">{{ item.old_staff_name }}</text>
                            <text class="mx-1">→</text>
                            <text class="text-primary">{{ item.new_staff_name }}</text>
                            <text
                                v-if="item.price_diff !== 0"
                                :class="item.price_diff > 0 ? 'text-red-500' : 'text-green-500'"
                                class="ml-2"
                            >
                                {{ item.price_diff > 0 ? '+' : '' }}{{ item.price_diff }}元
                            </text>
                        </view>

                        <!-- 加项 -->
                        <view v-else-if="item.change_type === 3" class="text-sm text-gray-600 mt-2">
                            <text>新增: </text>
                            <text class="text-primary">{{ item.add_staff_name }}</text>
                            <text class="text-gray-400 ml-1">({{ item.add_package_name }})</text>
                            <text class="text-red-500 ml-2">+{{ item.add_price }}元</text>
                        </view>

                        <view v-if="item.apply_reason" class="text-xs text-gray-400 mt-2">
                            原因: {{ item.apply_reason }}
                        </view>

                        <!-- 操作按钮 -->
                        <view v-if="item.change_status === 0" class="mt-3 flex justify-end">
                            <button
                                class="btn-outline text-red-500 border-red-500"
                                @click.stop="handleCancelChange(item)"
                            >
                                取消申请
                            </button>
                        </view>
                    </view>
                </template>

                <!-- 转让申请列表 -->
                <template v-if="currentType === 'transfer'">
                    <view
                        v-for="item in list"
                        :key="item.id"
                        class="bg-white rounded-lg mb-3 p-4"
                        @click="goTransferDetail(item.id)"
                    >
                        <view class="flex justify-between items-start mb-2">
                            <view>
                                <text class="text-xs text-gray-400">{{ item.transfer_sn }}</text>
                                <view class="mt-1">
                                    <view
                                        class="tag"
                                        :class="getTransferStatusClass(item.transfer_status)"
                                    >
                                        {{ item.transfer_status_desc }}
                                    </view>
                                </view>
                            </view>
                            <text class="text-xs text-gray-400">{{ item.create_time }}</text>
                        </view>

                        <view class="text-sm text-gray-600 mt-2">
                            <view class="flex items-center">
                                <text class="text-gray-400 w-16">转让方:</text>
                                <text>{{ item.from_user_name }} ({{ item.from_user_mobile }})</text>
                            </view>
                            <view class="flex items-center mt-1">
                                <text class="text-gray-400 w-16">接收方:</text>
                                <text class="text-primary"
                                    >{{ item.to_user_name }} ({{ item.to_user_mobile }})</text
                                >
                            </view>
                        </view>

                        <view v-if="item.transfer_reason" class="text-xs text-gray-400 mt-2">
                            原因: {{ item.transfer_reason }}
                        </view>

                        <!-- 操作按钮 -->
                        <view
                            v-if="item.transfer_status <= 1 && item.is_from_user"
                            class="mt-3 flex justify-end"
                        >
                            <button
                                class="btn-outline text-red-500 border-red-500"
                                @click.stop="handleCancelTransfer(item)"
                            >
                                取消转让
                            </button>
                        </view>
                    </view>
                </template>

                <!-- 暂停申请列表 -->
                <template v-if="currentType === 'pause'">
                    <view
                        v-for="item in list"
                        :key="item.id"
                        class="bg-white rounded-lg mb-3 p-4"
                        @click="goPauseDetail(item.id)"
                    >
                        <view class="flex justify-between items-start mb-2">
                            <view>
                                <text class="text-xs text-gray-400">{{ item.pause_sn }}</text>
                                <view class="flex items-center mt-1">
                                    <view class="tag" :class="getPauseTypeClass(item.pause_type)">
                                        {{ item.pause_type_desc }}
                                    </view>
                                    <view
                                        class="tag ml-2"
                                        :class="getPauseStatusClass(item.pause_status)"
                                    >
                                        {{ item.pause_status_desc }}
                                    </view>
                                </view>
                            </view>
                            <text class="text-xs text-gray-400">{{ item.create_time }}</text>
                        </view>

                        <view class="text-sm text-gray-600 mt-2">
                            <text>暂停时间: </text>
                            <text class="text-primary"
                                >{{ item.pause_start_date }} ~ {{ item.pause_end_date }}</text
                            >
                            <text class="text-gray-400 ml-2">({{ item.pause_days }}天)</text>
                        </view>

                        <view v-if="item.pause_reason" class="text-xs text-gray-400 mt-2">
                            原因: {{ item.pause_reason }}
                        </view>

                        <!-- 操作按钮 -->
                        <view v-if="item.pause_status === 0" class="mt-3 flex justify-end">
                            <button
                                class="btn-outline text-red-500 border-red-500"
                                @click.stop="handleCancelPause(item)"
                            >
                                取消申请
                            </button>
                        </view>
                    </view>
                </template>

                <!-- 加载更多 -->
                <view v-if="hasMore" class="py-4 text-center text-gray-400 text-sm">
                    <text v-if="loading">加载中...</text>
                    <text v-else @click="loadMore">加载更多</text>
                </view>
                <view v-else-if="list.length > 0" class="py-4 text-center text-gray-400 text-sm">
                    没有更多了
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onLoad, onShow, onReachBottom } from '@dcloudio/uni-app'
import {
    getChangeList,
    cancelChange,
    getTransferList,
    cancelTransfer,
    getPauseList,
    cancelPause
} from '@/api/orderChange'

const typeTabs = [
    { label: '变更申请', value: 'change' },
    { label: '转让申请', value: 'transfer' },
    { label: '暂停申请', value: 'pause' }
]

const currentType = ref('change')
const list = ref<any[]>([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)

// 变更类型样式
const getTypeClass = (type: number) => {
    const classes: Record<number, string> = {
        1: 'bg-blue-100 text-blue-600',
        2: 'bg-orange-100 text-orange-600',
        3: 'bg-green-100 text-green-600'
    }
    return classes[type] || 'bg-gray-100 text-gray-600'
}

// 变更状态样式
const getStatusClass = (status: number) => {
    const classes: Record<number, string> = {
        0: 'bg-orange-100 text-orange-600',
        1: 'bg-blue-100 text-blue-600',
        2: 'bg-red-100 text-red-600',
        3: 'bg-green-100 text-green-600',
        4: 'bg-gray-100 text-gray-600'
    }
    return classes[status] || 'bg-gray-100 text-gray-600'
}

// 转让状态样式
const getTransferStatusClass = (status: number) => {
    const classes: Record<number, string> = {
        0: 'bg-orange-100 text-orange-600',
        1: 'bg-blue-100 text-blue-600',
        2: 'bg-purple-100 text-purple-600',
        3: 'bg-green-100 text-green-600',
        4: 'bg-red-100 text-red-600',
        5: 'bg-gray-100 text-gray-600'
    }
    return classes[status] || 'bg-gray-100 text-gray-600'
}

// 暂停类型样式
const getPauseTypeClass = (type: number) => {
    const classes: Record<number, string> = {
        1: 'bg-red-100 text-red-600',
        2: 'bg-orange-100 text-orange-600',
        3: 'bg-blue-100 text-blue-600',
        4: 'bg-gray-100 text-gray-600'
    }
    return classes[type] || 'bg-gray-100 text-gray-600'
}

// 暂停状态样式
const getPauseStatusClass = (status: number) => {
    const classes: Record<number, string> = {
        0: 'bg-orange-100 text-orange-600',
        1: 'bg-blue-100 text-blue-600',
        2: 'bg-green-100 text-green-600',
        3: 'bg-red-100 text-red-600',
        4: 'bg-gray-100 text-gray-600'
    }
    return classes[status] || 'bg-gray-100 text-gray-600'
}

const fetchList = async (refresh = false) => {
    if (loading.value) return
    loading.value = true

    try {
        if (refresh) {
            page.value = 1
            list.value = []
        }

        const params = { page: page.value, page_size: 10 }
        let res: any

        if (currentType.value === 'change') {
            res = await getChangeList(params)
        } else if (currentType.value === 'transfer') {
            res = await getTransferList(params)
        } else {
            res = await getPauseList(params)
        }

        const data = res.lists || []

        if (refresh) {
            list.value = data
        } else {
            list.value.push(...data)
        }

        hasMore.value = data.length === 10
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const changeType = (type: string) => {
    currentType.value = type
    fetchList(true)
}

const loadMore = () => {
    if (hasMore.value && !loading.value) {
        page.value++
        fetchList()
    }
}

const goChangeDetail = (id: number) => {
    uni.navigateTo({ url: `/pages/order_change/change_detail?id=${id}` })
}

const goTransferDetail = (id: number) => {
    uni.navigateTo({ url: `/pages/order_change/transfer_detail?id=${id}` })
}

const goPauseDetail = (id: number) => {
    uni.navigateTo({ url: `/pages/order_change/pause_detail?id=${id}` })
}

const handleCancelChange = async (item: any) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该变更申请吗？'
    })
    if (res.confirm) {
        try {
            await cancelChange({ id: item.id })
            uni.showToast({ title: '已取消' })
            fetchList(true)
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const handleCancelTransfer = async (item: any) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该转让申请吗？'
    })
    if (res.confirm) {
        try {
            await cancelTransfer({ id: item.id })
            uni.showToast({ title: '已取消' })
            fetchList(true)
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

const handleCancelPause = async (item: any) => {
    const res = await uni.showModal({
        title: '提示',
        content: '确定要取消该暂停申请吗？'
    })
    if (res.confirm) {
        try {
            await cancelPause({ id: item.id })
            uni.showToast({ title: '已取消' })
            fetchList(true)
        } catch (e: any) {
            uni.showToast({ title: e.message || '操作失败', icon: 'none' })
        }
    }
}

onLoad((options: any) => {
    if (options.type) {
        currentType.value = options.type
    }
})

onShow(() => {
    fetchList(true)
})

onReachBottom(() => {
    loadMore()
})
</script>

<style lang="scss" scoped>
.change-list {
    min-height: 100vh;
    background-color: #f5f5f5;
}

.tag {
    display: inline-block;
    padding: 4rpx 12rpx;
    font-size: 20rpx;
    border-radius: 6rpx;
}

.btn-outline {
    padding: 12rpx 24rpx;
    font-size: 24rpx;
    border: 1rpx solid #ddd;
    border-radius: 8rpx;
    background: #fff;
}
</style>
