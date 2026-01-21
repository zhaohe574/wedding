<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>
    <view class="user-page">
        <!-- 动态装修组件渲染 -->
        <template v-for="(item, index) in state.pages" :key="index">
            <!-- 用户信息组件 -->
            <template v-if="item.name == 'user-info'">
                <w-user-info
                    :pageMeta="state.meta"
                    :content="item.content"
                    :styles="item.styles"
                    :user="userInfo"
                    :isLogin="isLogin"
                />
            </template>
            <!-- 我的服务组件 -->
            <template v-if="item.name == 'my-service' && isComponentEnabled(item)">
                <w-my-service :content="item.content" :styles="item.styles" />
            </template>
            <!-- 用户banner组件 -->
            <template v-if="item.name == 'user-banner' && isComponentEnabled(item)">
                <w-user-banner :content="item.content" :styles="item.styles" />
            </template>
            <!-- 订单快捷入口组件 -->
            <template v-if="item.name == 'order-quick-entry' && isComponentEnabled(item)">
                <w-order-quick-entry :content="item.content" :styles="item.styles" />
            </template>
            <!-- 客服组件 -->
            <template v-if="item.name == 'customer-service' && isComponentEnabled(item)">
                <w-customer-service :content="item.content" :styles="item.styles" />
            </template>
        </template>

        <view class="safe-bottom"></view>
        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { getDecorate } from '@/api/shop'
import { useUserStore } from '@/stores/user'
import { onShow } from '@dcloudio/uni-app'
import { storeToRefs } from 'pinia'
import { reactive, computed } from 'vue'

const state = reactive<{
    meta: any[]
    pages: any[]
}>({
    meta: [],
    pages: []
})

const userStore = useUserStore()
const { userInfo, isLogin } = storeToRefs(userStore)

// 判断组件是否启用
const isComponentEnabled = (item: any) => {
    return item.content?.enabled !== 0
}

// 页面样式
const pageStyle = computed(() => {
    const { bg_type, bg_color } = state.meta[0]?.content ?? {}
    // 个人中心页面默认使用灰色背景，头部组件自带蓝色渐变背景
    if (bg_type == 1 && bg_color) {
        return { 'background-color': bg_color }
    }
    return { 'background-color': '#f5f5f5' }
})

// 获取装修数据
const getData = async () => {
    try {
        const data = await getDecorate({ id: 2 })
        if (data?.meta) {
            state.meta = JSON.parse(data.meta)
        }
        if (data?.data) {
            state.pages = JSON.parse(data.data)
        }
    } catch (e) {
        console.error('获取装修数据失败:', e)
    }
}

onShow(() => {
    userStore.getUser()
    getData()
})
</script>

<style lang="scss" scoped>
.user-page {
    min-height: 100vh;
    background-color: #f5f5f5 !important;
    background-image: none !important;
    padding-bottom: calc(120rpx + env(safe-area-inset-bottom));
}

.safe-bottom {
    height: env(safe-area-inset-bottom);
}
</style>
