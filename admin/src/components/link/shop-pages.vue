<template>
    <div class="shop-pages">
        <div class="link-overview">
            <div>
                <div class="link-overview__title">
                    {{ props.isTab ? '底部导航可选页面' : '移动端页面链接' }}
                </div>
                <div class="link-overview__desc">
                    {{
                        props.isTab
                            ? '只展示 uniapp tabBar 中可 switchTab 的页面'
                            : '按当前 uniapp 页面入口重新分组，隐藏依赖业务 ID 的详情页'
                    }}
                </div>
            </div>
            <el-tag effect="plain" round>{{ visibleLinkCount }} 个链接</el-tag>
        </div>

        <el-empty v-if="visibleLinkGroups.length === 0" description="暂无可选页面" :image-size="90" />

        <div v-else class="link-group-list">
            <section v-for="group in visibleLinkGroups" :key="group.label" class="link-group">
                <div class="link-group__head">
                    <div>
                        <div class="link-group__title">{{ group.label }}</div>
                        <div v-if="group.description" class="link-group__desc">
                            {{ group.description }}
                        </div>
                    </div>
                    <el-tag size="small" effect="plain">{{ group.items.length }}</el-tag>
                </div>

                <div class="link-grid">
                    <button
                        v-for="item in group.items"
                        :key="getLinkKey(item)"
                        class="link-card"
                        :class="{ 'is-active': isSelected(item) }"
                        type="button"
                        @click="handleSelect(item)"
                    >
                        <span class="link-card__name">{{ item.name }}</span>
                        <span class="link-card__path">{{ formatPath(item) }}</span>
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>

<script lang="ts" setup>
import type { PropType } from 'vue'

import { type Link, LinkTypeEnum } from '.'

interface LinkOption extends Link {
    canTab?: boolean
}

interface LinkGroup {
    label: string
    description?: string
    items: LinkOption[]
}

const props = defineProps({
    modelValue: {
        type: Object as PropType<Link>,
        default: () => ({})
    },
    isTab: {
        type: Boolean,
        default: false
    }
})
const emit = defineEmits<{
    (event: 'update:modelValue', value: Link): void
}>()

const T = LinkTypeEnum.SHOP_PAGES

const linkGroups = ref<LinkGroup[]>([
    {
        label: '主导航',
        description: '首页、动态、我的与高频公开入口',
        items: [
            { path: '/pages/index/index', name: '首页', type: T, canTab: true },
            { path: '/pages/dynamic/dynamic', name: '动态广场', type: T, canTab: true },
            { path: '/pages/user/user', name: '我的', type: T, canTab: true },
            { path: '/pages/news/news', name: '资讯列表', type: T },
            { path: '/pages/search/search', name: '搜索', type: T },
            { path: '/pages/schedule_query/schedule_query', name: '档期查询', type: T },
            { path: '/pages/staff_list/staff_list', name: '人员列表', type: T }
        ]
    },
    {
        label: '用户与资产',
        description: '个人资料、账号设置、收藏、钱包与消息',
        items: [
            { path: '/pages/user_set/user_set', name: '个人设置', type: T },
            { path: '/pages/user_data/user_data', name: '个人资料', type: T },
            { path: '/pages/change_password/change_password', name: '修改密码', type: T },
            { path: '/pages/bind_mobile/bind_mobile', name: '绑定手机号', type: T },
            { path: '/packages/pages/collection/collection', name: '我的收藏', type: T },
            { path: '/packages/pages/user_wallet/user_wallet', name: '我的钱包', type: T },
            { path: '/packages/pages/recharge/recharge', name: '充值', type: T },
            { path: '/packages/pages/recharge_record/recharge_record', name: '充值记录', type: T },
            { path: '/packages/pages/notification/index', name: '消息中心', type: T }
        ]
    },
    {
        label: '预约与服务',
        description: '服务咨询、人员收藏与候补入口',
        items: [
            { path: '/packages/pages/customer_service/customer_service', name: '联系顾问', type: T },
            { path: '/packages/pages/staff_favorite/staff_favorite', name: '收藏人员', type: T },
            { path: '/packages/pages/waitlist/waitlist', name: '我的候补', type: T }
        ]
    },
    {
        label: '订单、售后与评价',
        description: '订单列表、申请记录、售后工单与评价列表',
        items: [
            { path: '/pages/order/order', name: '我的订单', type: T },
            { path: '/packages/pages/order_change/list', name: '我的申请', type: T },
            { path: '/packages/pages/aftersale/index', name: '售后服务', type: T },
            { path: '/packages/pages/aftersale/ticket', name: '我的工单', type: T },
            { path: '/packages/pages/aftersale/create_ticket', name: '提交工单', type: T },
            { path: '/packages/pages/aftersale/complaint', name: '我的投诉', type: T },
            { path: '/packages/pages/aftersale/create_complaint', name: '发起投诉', type: T },
            { path: '/packages/pages/aftersale/callback', name: '回访问卷', type: T },
            { path: '/packages/pages/review/list', name: '我的评价', type: T }
        ]
    },
    {
        label: '内容与协议',
        description: '内容发布、关于我们与协议页面',
        items: [
            { path: '/packages/pages/dynamic_publish/dynamic_publish', name: '发布动态', type: T },
            { path: '/pages/as_us/as_us', name: '关于我们', type: T },
            {
                path: '/packages/pages/agreement/agreement',
                name: '隐私政策',
                type: T,
                query: { type: 'privacy' }
            },
            {
                path: '/packages/pages/agreement/agreement',
                name: '服务协议',
                type: T,
                query: { type: 'service' }
            }
        ]
    },
    {
        label: '服务人员工作台',
        description: '服务人员可直接进入的管理入口',
        items: [
            { path: '/packages/pages/staff_center/staff_center', name: '服务人员中心', type: T },
            { path: '/packages/pages/staff_order_list/staff_order_list', name: '订单管理', type: T },
            { path: '/packages/pages/staff_profile/staff_profile', name: '人员资料', type: T },
            {
                path: '/packages/pages/staff_certificate_list/staff_certificate_list',
                name: '证书管理',
                type: T
            },
            { path: '/packages/pages/staff_work_list/staff_work_list', name: '作品管理', type: T },
            {
                path: '/packages/pages/staff_package_list/staff_package_list',
                name: '套餐管理',
                type: T
            },
            { path: '/packages/pages/staff_addon_list/staff_addon_list', name: '附加项管理', type: T },
            { path: '/packages/pages/staff_schedule/staff_schedule', name: '档期管理', type: T },
            { path: '/packages/pages/staff_dynamic_list/staff_dynamic_list', name: '动态管理', type: T }
        ]
    },
    {
        label: '管理端',
        description: '管理员经营视图',
        items: [
            { path: '/packages/pages/admin_dashboard/admin_dashboard', name: '经营驾驶舱', type: T }
        ]
    }
])

const visibleLinkGroups = computed(() => {
    if (!props.isTab) {
        return linkGroups.value
    }

    return linkGroups.value
        .map((group) => ({
            ...group,
            items: group.items.filter((item) => item.canTab === true)
        }))
        .filter((group) => group.items.length)
})

const visibleLinkCount = computed(() =>
    visibleLinkGroups.value.reduce((total, group) => total + group.items.length, 0)
)

const getLinkKey = (item: LinkOption) =>
    `${item.path}:${item.name}:${JSON.stringify(item.query || {})}`

const formatPath = (item: LinkOption) => {
    if (!item.query || !Object.keys(item.query).length) {
        return item.path
    }

    const query = new URLSearchParams(item.query as Record<string, string>).toString()
    return `${item.path}?${query}`
}

const isSelected = (item: LinkOption) => {
    return props.modelValue.path === item.path && props.modelValue.name === item.name
}

const handleSelect = (value: Link) => {
    emit('update:modelValue', value)
}
</script>

<style lang="scss" scoped>
.shop-pages {
    height: 530px;
    overflow-y: auto;
    padding-right: 4px;
}

.link-overview {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
    padding: 14px 16px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 10px;
    background: var(--el-fill-color-extra-light);
}

.link-overview__title {
    font-size: 16px;
    font-weight: 600;
    color: var(--el-text-color-primary);
}

.link-overview__desc {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.5;
    color: var(--el-text-color-secondary);
}

.link-group-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.link-group {
    padding: 16px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 10px;
    background: var(--el-bg-color);
}

.link-group__head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.link-group__title {
    padding-left: 8px;
    border-left: 3px solid var(--el-color-primary);
    font-weight: 600;
    line-height: 1.2;
    color: var(--el-text-color-primary);
}

.link-group__desc {
    margin-top: 6px;
    font-size: 12px;
    color: var(--el-text-color-secondary);
}

.link-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.link-card {
    min-width: 0;
    padding: 10px 12px;
    border: 1px solid var(--el-border-color);
    border-radius: 8px;
    background: var(--el-fill-color-blank);
    text-align: left;
    cursor: pointer;
    transition: all 0.16s ease;

    &:hover {
        border-color: var(--el-color-primary-light-5);
        background: var(--el-color-primary-light-9);
    }

    &.is-active {
        border-color: var(--el-color-primary);
        background: var(--el-color-primary-light-9);
        color: var(--el-color-primary);
        box-shadow: 0 0 0 1px var(--el-color-primary-light-7) inset;
    }
}

.link-card__name,
.link-card__path {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.link-card__name {
    font-size: 14px;
    font-weight: 600;
}

.link-card__path {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.35;
    color: var(--el-text-color-secondary);
}

.link-card.is-active .link-card__path {
    color: var(--el-color-primary);
}
</style>
