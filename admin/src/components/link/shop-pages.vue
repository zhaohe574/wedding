<template>
    <div class="shop-pages h-[530px] overflow-y-auto">
        <div v-for="(group, gIndex) in linkGroups" :key="gIndex" class="mb-4">
            <div class="group-title text-xs text-gray-400 mb-2">{{ group.label }}</div>
            <div class="link-list flex flex-wrap">
                <div
                    class="link-item border border-br px-5 py-[5px] rounded-[3px] cursor-pointer mr-[10px] mb-[10px]"
                    v-for="(item, index) in group.items"
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

const T = LinkTypeEnum.SHOP_PAGES

const linkGroups = ref([
    {
        label: '核心页面',
        items: [
            { path: '/pages/index/index', name: '商城首页', type: T, canTab: true },
            { path: '/pages/search/search', name: '搜索', type: T },
            { path: '/pages/news/news', name: '文章资讯', type: T, canTab: true }
        ]
    },
    {
        label: '个人中心',
        items: [
            { path: '/pages/user/user', name: '个人中心', type: T, canTab: true },
            { path: '/pages/user_set/user_set', name: '个人设置', type: T },
            { path: '/pages/user_data/user_data', name: '个人资料', type: T },
            { path: '/pages/collection/collection', name: '我的收藏', type: T },
            { path: '/packages/pages/user_wallet/user_wallet', name: '我的钱包', type: T }
        ]
    },
    {
        label: '婚庆服务',
        items: [
            { path: '/packages/pages/staff_list/staff_list', name: '人员列表', type: T },
            { path: '/packages/pages/staff_favorite/staff_favorite', name: '收藏人员', type: T },
            { path: '/packages/pages/schedule_calendar/schedule_calendar', name: '档期查询', type: T },
            { path: '/packages/pages/staff_center/staff_center', name: '服务人员中心', type: T },
            { path: '/packages/pages/admin_dashboard/admin_dashboard', name: '管理员看板', type: T }
        ]
    },
    {
        label: '订单与购物',
        items: [
            { path: '/pages/order/order', name: '我的订单', type: T },
            { path: '/packages/pages/waitlist/waitlist', name: '候补列表', type: T }
        ]
    },
    {
        label: '售后与评价',
        items: [
            { path: '/pages/aftersale/index', name: '售后列表', type: T },
            { path: '/pages/aftersale/ticket', name: '我的工单', type: T },
            { path: '/pages/review/list', name: '我的评价', type: T },
            { path: '/pages/review/publish', name: '发布评价', type: T }
        ]
    },
    {
        label: '优惠活动',
        items: [
            { path: '/pages/coupon/list', name: '我的优惠券', type: T },
            { path: '/pages/coupon/center', name: '领券中心', type: T },
            { path: '/packages/pages/recharge/recharge', name: '充值中心', type: T },
            { path: '/packages/pages/recharge_record/recharge_record', name: '充值记录', type: T }
        ]
    },
    {
        label: '社区互动',
        items: [
            { path: '/pages/dynamic/dynamic', name: '动态广场', type: T },
            { path: '/pages/dynamic_publish/dynamic_publish', name: '发布动态', type: T }
        ]
    },
    {
        label: '信息与服务',
        items: [
            { path: '/pages/notification/index', name: '消息通知', type: T },
            { path: '/pages/customer_service/customer_service', name: '联系客服', type: T },
            { path: '/pages/as_us/as_us', name: '关于我们', type: T },
            { path: '/pages/agreement/agreement', name: '隐私政策', type: T, query: { type: 'privacy' } },
            { path: '/pages/agreement/agreement', name: '服务协议', type: T, query: { type: 'service' } }
        ]
    }
])

const handleSelect = (value: Link) => {
    emit('update:modelValue', value)
}
</script>

<style lang="scss" scoped>
.group-title {
    padding-left: 2px;
    border-left: 3px solid var(--el-color-primary);
    padding-left: 8px;
    font-weight: 500;
    color: var(--el-text-color-secondary);
}
</style>
