<template>
    <div class="shop-pages h-[530px]">
        <div class="link-list flex flex-wrap">
            <div
                class="link-item border border-br px-5 py-[5px] rounded-[3px] cursor-pointer mr-[10px] mb-[10px]"
                v-for="(item, index) in linkList"
                :class="{
                    'border-primary text-primary':
                        modelValue.path == item.path && modelValue.name == item.name
                }"
                :key="index"
                @click="handleSelect(item)"
            >
                {{ item.name }}
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { type Link, LinkTypeEnum } from '.'

defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        default: () => ({})
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const linkList = ref([
    // ===== 基础页面 =====
    {
        path: '/pages/index/index',
        name: '商城首页',
        type: LinkTypeEnum.SHOP_PAGES,
        canTab: true
    },
    {
        path: '/pages/news/news',
        name: '文章资讯',
        type: LinkTypeEnum.SHOP_PAGES,
        canTab: true
    },
    {
        path: '/pages/user/user',
        name: '个人中心',
        type: LinkTypeEnum.SHOP_PAGES,
        canTab: true
    },
    {
        path: '/pages/collection/collection',
        name: '我的收藏',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/customer_service/customer_service',
        name: '联系客服',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/user_set/user_set',
        name: '个人设置',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/as_us/as_us',
        name: '关于我们',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/user_data/user_data',
        name: '个人资料',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/agreement/agreement',
        name: '隐私政策',
        query: {
            type: 'privacy'
        },
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/agreement/agreement',
        name: '服务协议',
        query: {
            type: 'service'
        },
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/search/search',
        name: '搜索',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/packages/pages/user_wallet/user_wallet',
        name: '我的钱包',
        type: LinkTypeEnum.SHOP_PAGES
    },
    // ===== 婚庆服务页面 =====
    {
        path: '/packages/pages/staff_list/staff_list',
        name: '人员列表',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/packages/pages/staff_favorite/staff_favorite',
        name: '收藏人员',
        type: LinkTypeEnum.SHOP_PAGES
    },
    // ===== 订单相关页面 =====
    {
        path: '/pages/order/order',
        name: '我的订单',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/packages/pages/schedule_calendar/schedule_calendar',
        name: '档期查询',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/packages/pages/waitlist/waitlist',
        name: '候补列表',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/packages/pages/cart/cart',
        name: '购物车',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/packages/pages/cart_plan/cart_plan',
        name: '婚礼方案',
        type: LinkTypeEnum.SHOP_PAGES
    },
    // ===== 售后与评价 =====
    {
        path: '/pages/aftersale/list/list',
        name: '售后列表',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/review/list/list',
        name: '我的评价',
        type: LinkTypeEnum.SHOP_PAGES
    },
    // ===== 动态社区 =====
    {
        path: '/pages/dynamic/dynamic',
        name: '动态广场',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/dynamic_publish/dynamic_publish',
        name: '发布动态',
        type: LinkTypeEnum.SHOP_PAGES
    },
    // ===== 优惠与消息 =====
    {
        path: '/pages/coupon/coupon',
        name: '优惠券',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/pages/notification/notification',
        name: '消息通知',
        type: LinkTypeEnum.SHOP_PAGES
    },
    // ===== 充值相关 =====
    {
        path: '/packages/pages/recharge/recharge',
        name: '充值中心',
        type: LinkTypeEnum.SHOP_PAGES
    },
    {
        path: '/packages/pages/recharge_record/recharge_record',
        name: '充值记录',
        type: LinkTypeEnum.SHOP_PAGES
    }
])

const handleSelect = (value: Link) => {
    emit('update:modelValue', value)
}
</script>
