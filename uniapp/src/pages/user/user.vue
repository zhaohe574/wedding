<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            title="个人中心"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>
    <view class="user-page page-with-tabbar-safe-bottom">
        <!-- 动态装修组件渲染 -->
        <template v-for="(item, index) in state.pages" :key="index">
            <!-- 用户信息组件 -->
            <template v-if="item.name == 'user-info'">
                <w-user-info
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
            <!-- 快捷入口组件 -->
            <template v-if="item.name == 'quick-entry' && isComponentEnabled(item)">
                <w-quick-entry :content="item.content" :styles="item.styles" />
            </template>
            <!-- 优惠券领取组件 -->
            <template v-if="item.name == 'coupon-receive' && isComponentEnabled(item)">
                <w-coupon-receive :content="item.content" :styles="item.styles" />
            </template>
            <!-- 数据统计卡片组件 -->
            <template v-if="item.name == 'data-stats' && isComponentEnabled(item)">
                <w-data-stats :content="item.content" :styles="item.styles" />
            </template>
            <!-- 常见问题组件 -->
            <template v-if="item.name == 'faq' && isComponentEnabled(item)">
                <w-faq :content="item.content" :styles="item.styles" />
            </template>
            <!-- 服务流程组件 -->
            <template v-if="item.name == 'service-process' && isComponentEnabled(item)">
                <w-service-process :content="item.content" :styles="item.styles" />
            </template>
            <!-- 公告通知组件 -->
            <template v-if="item.name == 'notice-bar' && isComponentEnabled(item)">
                <w-notice-bar :content="item.content" :styles="item.styles" />
            </template>
            <!-- 热门话题组件 -->
            <template v-if="item.name == 'hot-topics' && isComponentEnabled(item)">
                <w-hot-topics :content="item.content" :styles="item.styles" />
            </template>
            <!-- 门店地图组件 -->
            <template v-if="item.name == 'store-map' && isComponentEnabled(item)">
                <w-store-map :content="item.content" :styles="item.styles" />
            </template>
            <!-- 婚礼倒计时组件 -->
            <template v-if="item.name == 'wedding-countdown' && isComponentEnabled(item)">
                <w-wedding-countdown :content="item.content" :styles="item.styles" />
            </template>
            <!-- 客服组件 -->
            <template v-if="item.name == 'customer-service' && isComponentEnabled(item)">
                <w-customer-service :content="item.content" :styles="item.styles" />
            </template>
        </template>
        <tabbar />
    </view>
</template>

<script setup lang="ts">
import { getDecorate } from '@/api/shop'
import { useUserStore } from '@/stores/user'
import { onShow } from '@dcloudio/uni-app'
import { storeToRefs } from 'pinia'
import { reactive } from 'vue'

const state = reactive<{
    pages: any[]
}>({
    pages: []
})

const userStore = useUserStore()
const { userInfo, isLogin } = storeToRefs(userStore)

// 判断组件是否启用
const isComponentEnabled = (item: any) => {
    return item.content?.enabled !== 0
}

// 获取装修数据
const getData = async () => {
    try {
        const data = await getDecorate({ id: 2 })
        if (data?.data) {
            // 处理 data.data，可能是字符串或对象
            if (typeof data.data === 'string') {
                state.pages = JSON.parse(data.data)
            } else {
                state.pages = data.data
            }
        }
    } catch (e) {
        console.error('获取装修数据失败:', e)
    }
}

onShow(() => {
    // 只在未登录或用户信息为空时才重新获取
    // App.vue 已经在启动时获取过用户信息
    if (isLogin.value && !userInfo.value?.id) {
        userStore.getUser()
    }
    getData()
})
</script>

<style lang="scss" scoped>
.user-page {
    min-height: 100vh;
    background-color: #f5f5f5 !important;
    background-image: none !important;
}
</style>
