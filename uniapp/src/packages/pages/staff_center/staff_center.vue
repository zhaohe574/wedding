<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="服务人员中心"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="page-container">
        <view class="hero-section">
            <view
                class="hero-bg"
                :style="{
                    background: `linear-gradient(140deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 72%, #111827 100%)`
                }"
            />
            <view class="hero-blur hero-blur--left" />
            <view class="hero-blur hero-blur--right" />

            <view class="hero-card" @click="goPage('/packages/pages/staff_profile/staff_profile')">
                <view class="hero-main">
                    <image class="hero-avatar" :src="dashboard.profile.avatar || defaultAvatar" mode="aspectFill" />
                    <view class="hero-info">
                        <view class="hero-title-row">
                            <text class="hero-name">{{ dashboard.profile.name || '未填写姓名' }}</text>
                            <view class="hero-tag" :style="getAuditTagStyle()">
                                {{ getAuditTagText() }}
                            </view>
                        </view>
                        <text class="hero-category">
                            {{ dashboard.profile.category_name || '待补充服务分类' }}
                        </text>
                        <view class="hero-meta">
                            <view class="hero-meta-item">
                                <tn-icon name="phone" size="22" color="rgba(255,255,255,0.75)" />
                                <text>{{ dashboard.profile.mobile || '未绑定手机号' }}</text>
                            </view>
                            <view class="hero-meta-item">
                                <tn-icon name="money" size="22" color="rgba(255,255,255,0.75)" />
                                <text>{{ dashboard.profile.has_price ? `¥${dashboard.profile.price_text || '0'}/次` : '面议' }}</text>
                            </view>
                        </view>
                    </view>
                    <view class="hero-arrow">
                        <tn-icon name="right" size="26" color="rgba(255,255,255,0.85)" />
                    </view>
                </view>

                <view class="hero-summary">
                    <view class="hero-summary-item">
                        <text class="hero-summary-label">待办总数</text>
                        <text class="hero-summary-value">{{ dashboard.todo.total || 0 }}</text>
                    </view>
                    <view class="hero-summary-divider" />
                    <view class="hero-summary-item">
                        <text class="hero-summary-label">消息未读</text>
                        <text class="hero-summary-value">{{ dashboard.todo.unread_message_count || 0 }}</text>
                    </view>
                    <view class="hero-summary-divider" />
                    <view class="hero-summary-item">
                        <text class="hero-summary-label">今日服务</text>
                        <text class="hero-summary-value">{{ dashboard.todo.today_service_count || 0 }}</text>
                    </view>
                </view>
            </view>
        </view>

        <view class="section-card section-card--todo">
            <view class="section-head">
                <view>
                    <text class="section-title">待办看板</text>
                    <text class="section-subtitle">优先处理高时效事项</text>
                </view>
                <text class="section-side-text">{{ dashboard.todo.total || 0 }} 项</text>
            </view>

            <view class="todo-grid">
                <view
                    v-for="item in todoCards"
                    :key="item.key"
                    class="todo-card"
                    @click="handleTodo(item)"
                >
                    <view class="todo-icon" :style="{ background: item.iconBg }">
                        <tn-icon :name="item.icon" size="34" :color="item.color" />
                    </view>
                    <text class="todo-value" :style="{ color: item.color }">{{ item.value }}</text>
                    <text class="todo-label">{{ item.label }}</text>
                    <text class="todo-desc">{{ item.desc }}</text>
                </view>
            </view>
        </view>

        <view class="section-card">
            <view class="section-head">
                <view>
                    <text class="section-title">经营概览</text>
                    <text class="section-subtitle">围绕内容、订单与档期统一管理</text>
                </view>
            </view>

            <view class="overview-grid">
                <view
                    v-for="item in overviewCards"
                    :key="item.label"
                    class="overview-card"
                    @click="goPage(item.path)"
                >
                    <view class="overview-top">
                        <view class="overview-dot" :style="{ background: item.color }" />
                        <text class="overview-label">{{ item.label }}</text>
                    </view>
                    <text class="overview-value">{{ item.value }}</text>
                </view>
            </view>
        </view>

        <view class="section-card">
            <view class="section-head">
                <view>
                    <text class="section-title">最近订单</text>
                    <text class="section-subtitle">首页直接跟进最近进来的订单</text>
                </view>
                <view class="section-link" @click="goOrders()">
                    <text>全部订单</text>
                    <tn-icon name="right" size="22" color="#6B7280" />
                </view>
            </view>

            <view v-if="dashboard.recent_orders.length" class="recent-list">
                <view
                    v-for="order in dashboard.recent_orders"
                    :key="order.id"
                    class="recent-card"
                    @click="goOrderDetail(order.id)"
                >
                    <view class="recent-head">
                        <view class="recent-sn">
                            <tn-icon name="order" size="24" :color="$theme.primaryColor" />
                            <text>{{ order.order_sn }}</text>
                        </view>
                        <view class="recent-status" :style="getOrderStatusStyle(order.order_status)">
                            {{ order.order_status_desc }}
                        </view>
                    </view>

                    <view class="recent-body">
                        <view class="recent-info">
                            <view class="recent-line">
                                <tn-icon name="calendar" size="22" color="#6B7280" />
                                <text>{{ order.service_date || '待安排服务日期' }}</text>
                            </view>
                            <view class="recent-line">
                                <tn-icon name="my" size="22" color="#6B7280" />
                                <text>{{ order.contact_name || '未填写联系人' }}</text>
                            </view>
                            <view class="recent-line">
                                <tn-icon name="map-pin" size="22" color="#6B7280" />
                                <text>{{ order.service_address || '未填写服务地址' }}</text>
                            </view>
                        </view>
                        <view class="recent-side">
                            <text class="recent-amount-label">订单金额</text>
                            <text class="recent-amount">¥{{ formatMoney(order.pay_amount) }}</text>
                            <text v-if="order.pending_confirm_count > 0" class="recent-pending">
                                待确认 {{ order.pending_confirm_count }}
                            </text>
                        </view>
                    </view>

                    <view class="recent-foot">
                        <text class="recent-package">
                            {{ getOrderPackageSummary(order.package_names, order.item_count) }}
                        </text>
                        <view class="recent-actions">
                            <view class="recent-btn recent-btn--ghost" @click.stop="goOrderDetail(order.id)">
                                查看详情
                            </view>
                            <view
                                v-if="order.order_status === 0 && order.pending_confirm_count > 0"
                                class="recent-btn recent-btn--primary"
                                :style="{
                                    background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 100%)`,
                                    color: $theme.btnColor
                                }"
                                @click.stop="confirmOrder(order)"
                            >
                                确认订单
                            </view>
                        </view>
                    </view>
                </view>
            </view>
            <view v-else class="empty-panel">
                <tn-icon name="order" size="88" color="#D1D5DB" />
                <text class="empty-panel-title">最近还没有订单动态</text>
                <text class="empty-panel-desc">订单进来后会优先显示在这里</text>
            </view>
        </view>

        <view class="section-card">
            <view class="section-head">
                <view>
                    <text class="section-title">快捷操作</text>
                    <text class="section-subtitle">常用模块直达，不再层层找入口</text>
                </view>
            </view>

            <view class="quick-grid">
                <view
                    v-for="item in quickMenus"
                    :key="item.path"
                    class="quick-card"
                    @click="goPage(item.path)"
                >
                    <view class="quick-icon-wrap" :style="{ background: item.iconBg }">
                        <tn-icon :name="item.icon" size="34" :color="item.iconColor" />
                        <view v-if="item.badge > 0" class="quick-badge">
                            {{ formatBadge(item.badge) }}
                        </view>
                    </view>
                    <text class="quick-name">{{ item.name }}</text>
                    <text class="quick-desc">{{ item.desc }}</text>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterDashboard, staffCenterOrderConfirm } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const defaultAvatar = '/static/images/user/default_avatar.png'

const dashboard = ref<any>({
    profile: {},
    overview: {},
    todo: {},
    recent_orders: []
})

const overviewCards = computed(() => [
    {
        label: '订单',
        value: Number(dashboard.value.overview?.order_count || 0),
        color: '#2563EB',
        path: '/packages/pages/staff_order_list/staff_order_list'
    },
    {
        label: '档期',
        value: Number(dashboard.value.overview?.schedule_count || 0),
        color: '#059669',
        path: '/packages/pages/staff_schedule/staff_schedule'
    },
    {
        label: '作品',
        value: Number(dashboard.value.overview?.work_count || 0),
        color: '#D946EF',
        path: '/packages/pages/staff_work_list/staff_work_list'
    },
    {
        label: '套餐',
        value: Number(dashboard.value.overview?.package_count || 0),
        color: '#F97316',
        path: '/packages/pages/staff_package_list/staff_package_list'
    },
    {
        label: '附加',
        value: Number(dashboard.value.overview?.addon_count || 0),
        color: '#0EA5E9',
        path: '/packages/pages/staff_addon_list/staff_addon_list'
    }
])

const todoCards = computed(() => [
    {
        key: 'pending_confirm_orders',
        label: '待确认订单',
        desc: '尽快处理客户下单',
        value: Number(dashboard.value.todo?.pending_confirm_orders || 0),
        icon: 'clock',
        color: '#F97316',
        iconBg: 'linear-gradient(135deg, rgba(249,115,22,0.15) 0%, rgba(251,146,60,0.28) 100%)',
        action: () => goOrders(0)
    },
    {
        key: 'today_service_count',
        label: '今日服务',
        desc: '优先检查行程安排',
        value: Number(dashboard.value.todo?.today_service_count || 0),
        icon: 'calendar',
        color: '#2563EB',
        iconBg: 'linear-gradient(135deg, rgba(37,99,235,0.15) 0%, rgba(96,165,250,0.28) 100%)',
        action: () => goOrders()
    },
    {
        key: 'upcoming_7d_schedule_count',
        label: '未来 7 日安排',
        desc: '提前同步档期状态',
        value: Number(dashboard.value.todo?.upcoming_7d_schedule_count || 0),
        icon: 'calendar-fill',
        color: '#059669',
        iconBg: 'linear-gradient(135deg, rgba(5,150,105,0.15) 0%, rgba(52,211,153,0.28) 100%)',
        action: () => goPage('/packages/pages/staff_schedule/staff_schedule')
    },
    {
        key: 'unread_message_count',
        label: '未读消息',
        desc: '查看通知与系统提醒',
        value: Number(dashboard.value.todo?.unread_message_count || 0),
        icon: 'notification',
        color: '#7C3AED',
        iconBg: 'linear-gradient(135deg, rgba(124,58,237,0.15) 0%, rgba(167,139,250,0.28) 100%)',
        action: () => goPage('/packages/pages/notification/index')
    }
])

const quickMenus = computed(() => [
    {
        name: '个人资料',
        desc: '维护身份信息',
        icon: 'my',
        iconBg: 'linear-gradient(135deg, #E0EAFF 0%, #C7D2FE 100%)',
        iconColor: '#315BFF',
        path: '/packages/pages/staff_profile/staff_profile',
        badge: 0
    },
    {
        name: '订单管理',
        desc: '跟进全部订单',
        icon: 'order',
        iconBg: 'linear-gradient(135deg, #DBEAFE 0%, #BFDBFE 100%)',
        iconColor: '#2563EB',
        path: '/packages/pages/staff_order_list/staff_order_list',
        badge: Number(dashboard.value.todo?.pending_confirm_orders || 0)
    },
    {
        name: '档期管理',
        desc: '维护服务日历',
        icon: 'calendar',
        iconBg: 'linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%)',
        iconColor: '#059669',
        path: '/packages/pages/staff_schedule/staff_schedule',
        badge: 0
    },
    {
        name: '作品管理',
        desc: '展示案例内容',
        icon: 'image',
        iconBg: 'linear-gradient(135deg, #FCE7F3 0%, #F9A8D4 100%)',
        iconColor: '#DB2777',
        path: '/packages/pages/staff_work_list/staff_work_list',
        badge: 0
    },
    {
        name: '套餐管理',
        desc: '更新服务组合',
        icon: 'gift',
        iconBg: 'linear-gradient(135deg, #FFEDD5 0%, #FDBA74 100%)',
        iconColor: '#F97316',
        path: '/packages/pages/staff_package_list/staff_package_list',
        badge: 0
    },
    {
        name: '附加服务',
        desc: '扩展增值项目',
        icon: 'gift-fill',
        iconBg: 'linear-gradient(135deg, #E0F2FE 0%, #7DD3FC 100%)',
        iconColor: '#0284C7',
        path: '/packages/pages/staff_addon_list/staff_addon_list',
        badge: 0
    },
    {
        name: '动态管理',
        desc: '持续内容运营',
        icon: 'edit',
        iconBg: 'linear-gradient(135deg, #FEF3C7 0%, #FCD34D 100%)',
        iconColor: '#D97706',
        path: '/packages/pages/staff_dynamic_list/staff_dynamic_list',
        badge: 0
    },
    {
        name: '消息通知',
        desc: '查看最新提醒',
        icon: 'notification-fill',
        iconBg: 'linear-gradient(135deg, #EDE9FE 0%, #C4B5FD 100%)',
        iconColor: '#7C3AED',
        path: '/packages/pages/notification/index',
        badge: Number(dashboard.value.todo?.unread_message_count || 0)
    }
])

const loadDashboard = async () => {
    try {
        const data = await staffCenterDashboard()
        dashboard.value = {
            profile: data?.profile || {},
            overview: data?.overview || {},
            todo: data?.todo || {},
            recent_orders: Array.isArray(data?.recent_orders) ? data.recent_orders : []
        }
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '加载失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const goPage = (path: string) => {
    uni.navigateTo({ url: path })
}

const goOrders = (status?: number) => {
    const query = status === undefined ? '' : `?status=${status}`
    uni.navigateTo({ url: `/packages/pages/staff_order_list/staff_order_list${query}` })
}

const goOrderDetail = (id: number) => {
    uni.navigateTo({ url: `/packages/pages/staff_order_detail/staff_order_detail?id=${id}` })
}

const handleTodo = (item: { action: () => void }) => {
    item.action()
}

const confirmOrder = (order: any) => {
    uni.showModal({
        title: '确认订单',
        content: '确认后客户可进行支付，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterOrderConfirm({ id: order.id })
                uni.showToast({ title: '确认成功', icon: 'success' })
                loadDashboard()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '确认失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

const getAuditTagText = () => {
    const status = Number(dashboard.value.profile?.status || 0)
    const auditStatus = Number(dashboard.value.profile?.audit_status || 0)
    if (status !== 1 && auditStatus === 1) {
        return '已停用'
    }
    const map: Record<number, string> = {
        0: '待审核',
        1: '已认证',
        2: '已拒绝'
    }
    return map[auditStatus] || '待完善'
}

const getAuditTagStyle = () => {
    const status = Number(dashboard.value.profile?.status || 0)
    const auditStatus = Number(dashboard.value.profile?.audit_status || 0)
    if (status !== 1 && auditStatus === 1) {
        return { background: 'rgba(148,163,184,0.16)', color: '#E2E8F0' }
    }
    const styles: Record<number, Record<string, string>> = {
        0: { background: 'rgba(249,115,22,0.16)', color: '#FED7AA' },
        1: { background: 'rgba(16,185,129,0.18)', color: '#BBF7D0' },
        2: { background: 'rgba(248,113,113,0.16)', color: '#FECACA' }
    }
    return styles[auditStatus] || styles[0]
}

const getOrderStatusStyle = (status: number) => {
    const styles: Record<number, Record<string, string>> = {
        0: { background: 'rgba(249,115,22,0.12)', color: '#EA580C' },
        1: { background: 'rgba(59,130,246,0.12)', color: '#2563EB' },
        2: { background: 'rgba(16,185,129,0.12)', color: '#059669' },
        3: { background: 'rgba(14,165,233,0.12)', color: '#0284C7' },
        4: { background: 'rgba(100,116,139,0.12)', color: '#475569' },
        5: { background: 'rgba(124,58,237,0.12)', color: '#7C3AED' },
        6: { background: 'rgba(239,68,68,0.12)', color: '#DC2626' },
        7: { background: 'rgba(245,158,11,0.12)', color: '#D97706' },
        8: { background: 'rgba(244,63,94,0.12)', color: '#E11D48' }
    }
    return styles[status] || styles[0]
}

const getOrderPackageSummary = (packageNames: string[], itemCount: number) => {
    if (Array.isArray(packageNames) && packageNames.length > 0) {
        return packageNames.join('、')
    }
    return `共 ${itemCount || 0} 个服务项`
}

const formatMoney = (value: number | string) => {
    const amount = Number(value || 0)
    return Number.isInteger(amount) ? String(amount) : amount.toFixed(2)
}

const formatBadge = (value: number) => {
    if (value > 99) return '99+'
    return String(value)
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    loadDashboard()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    padding: 0 24rpx 56rpx;
    background:
        radial-gradient(circle at top left, rgba(219, 234, 254, 0.9) 0, rgba(244, 245, 247, 0) 38%),
        linear-gradient(180deg, #F6F8FC 0%, #F3F5FA 48%, #F7F8FB 100%);
}

.hero-section {
    position: relative;
    padding-top: 24rpx;
}

.hero-bg {
    position: absolute;
    top: 0;
    left: -24rpx;
    right: -24rpx;
    height: 380rpx;
    border-radius: 0 0 44rpx 44rpx;
}

.hero-blur {
    position: absolute;
    border-radius: 50%;
    filter: blur(12rpx);
    opacity: 0.45;
}

.hero-blur--left {
    top: 48rpx;
    left: 0;
    width: 180rpx;
    height: 180rpx;
    background: rgba(255, 255, 255, 0.16);
}

.hero-blur--right {
    top: 20rpx;
    right: 10rpx;
    width: 240rpx;
    height: 240rpx;
    background: rgba(255, 255, 255, 0.12);
}

.hero-card {
    position: relative;
    z-index: 1;
    margin-top: 12rpx;
    padding: 30rpx 28rpx;
    background: linear-gradient(160deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.82) 100%);
    border-radius: 30rpx;
    box-shadow: 0 24rpx 48rpx rgba(15, 23, 42, 0.16);
    overflow: hidden;
}

.hero-main {
    display: flex;
    align-items: center;
}

.hero-avatar {
    width: 126rpx;
    height: 126rpx;
    border-radius: 34rpx;
    border: 4rpx solid rgba(255, 255, 255, 0.16);
    background: rgba(255, 255, 255, 0.08);
}

.hero-info {
    flex: 1;
    min-width: 0;
    margin-left: 22rpx;
}

.hero-title-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.hero-name {
    max-width: 320rpx;
    font-size: 38rpx;
    font-weight: 700;
    color: #FFFFFF;
    line-height: 1.2;
}

.hero-tag {
    flex-shrink: 0;
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.hero-category {
    display: block;
    margin-top: 12rpx;
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.72);
}

.hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx 24rpx;
    margin-top: 18rpx;
}

.hero-meta-item {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.78);
}

.hero-arrow {
    flex-shrink: 0;
    margin-left: 12rpx;
}

.hero-summary {
    display: flex;
    align-items: center;
    margin-top: 28rpx;
    padding: 24rpx;
    border-radius: 24rpx;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(16rpx);
}

.hero-summary-item {
    flex: 1;
    min-width: 0;
}

.hero-summary-label {
    display: block;
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.62);
}

.hero-summary-value {
    display: block;
    margin-top: 10rpx;
    font-size: 34rpx;
    font-weight: 700;
    color: #FFFFFF;
}

.hero-summary-divider {
    width: 1rpx;
    height: 54rpx;
    background: rgba(255, 255, 255, 0.12);
    margin: 0 20rpx;
}

.section-card {
    margin-top: 22rpx;
    padding: 28rpx;
    background: rgba(255, 255, 255, 0.88);
    border: 1rpx solid rgba(255, 255, 255, 0.72);
    border-radius: 28rpx;
    box-shadow: 0 18rpx 36rpx rgba(15, 23, 42, 0.05);
}

.section-card--todo {
    margin-top: 26rpx;
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.section-title {
    display: block;
    font-size: 32rpx;
    font-weight: 700;
    color: #0F172A;
}

.section-subtitle {
    display: block;
    margin-top: 8rpx;
    font-size: 22rpx;
    color: #94A3B8;
}

.section-side-text {
    font-size: 24rpx;
    font-weight: 600;
    color: #475569;
}

.section-link {
    display: inline-flex;
    align-items: center;
    gap: 6rpx;
    font-size: 24rpx;
    color: #6B7280;
}

.todo-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18rpx;
    margin-top: 22rpx;
}

.todo-card {
    min-height: 198rpx;
    padding: 24rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 2rpx solid #EEF2FF;
}

.todo-icon {
    width: 72rpx;
    height: 72rpx;
    border-radius: 22rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.todo-value {
    display: block;
    margin-top: 18rpx;
    font-size: 42rpx;
    font-weight: 800;
    line-height: 1;
}

.todo-label {
    display: block;
    margin-top: 12rpx;
    font-size: 28rpx;
    font-weight: 600;
    color: #0F172A;
}

.todo-desc {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.5;
    color: #94A3B8;
}

.overview-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16rpx;
    margin-top: 22rpx;
}

.overview-card {
    padding: 24rpx 20rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, #F8FAFC 0%, #FFFFFF 100%);
    border: 2rpx solid #EDF2F7;
}

.overview-top {
    display: flex;
    align-items: center;
    gap: 10rpx;
}

.overview-dot {
    width: 14rpx;
    height: 14rpx;
    border-radius: 50%;
}

.overview-label {
    font-size: 24rpx;
    color: #64748B;
}

.overview-value {
    display: block;
    margin-top: 18rpx;
    font-size: 40rpx;
    font-weight: 700;
    color: #0F172A;
}

.recent-list {
    margin-top: 22rpx;
}

.recent-card + .recent-card {
    margin-top: 18rpx;
}

.recent-card {
    padding: 24rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 2rpx solid #EEF2F7;
}

.recent-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.recent-sn {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    min-width: 0;
    font-size: 24rpx;
    color: #475569;
}

.recent-sn text:last-child {
    max-width: 360rpx;
}

.recent-status {
    flex-shrink: 0;
    padding: 8rpx 18rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 600;
}

.recent-body {
    display: flex;
    gap: 20rpx;
    margin-top: 22rpx;
}

.recent-info {
    flex: 1;
    min-width: 0;
}

.recent-line {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 24rpx;
    color: #475569;
}

.recent-line + .recent-line {
    margin-top: 10rpx;
}

.recent-line text:last-child {
    flex: 1;
    min-width: 0;
    line-height: 1.5;
}

.recent-side {
    flex-shrink: 0;
    min-width: 148rpx;
    padding: 18rpx 20rpx;
    border-radius: 20rpx;
    background: #F8FAFC;
}

.recent-amount-label {
    display: block;
    font-size: 20rpx;
    color: #94A3B8;
}

.recent-amount {
    display: block;
    margin-top: 10rpx;
    font-size: 34rpx;
    font-weight: 800;
    color: #0F172A;
}

.recent-pending {
    display: inline-flex;
    margin-top: 10rpx;
    padding: 6rpx 14rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    color: #EA580C;
    background: rgba(249, 115, 22, 0.12);
}

.recent-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18rpx;
    margin-top: 22rpx;
    padding-top: 18rpx;
    border-top: 1rpx solid #EEF2F7;
}

.recent-package {
    flex: 1;
    min-width: 0;
    font-size: 22rpx;
    color: #94A3B8;
    line-height: 1.5;
}

.recent-actions {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.recent-btn {
    min-width: 124rpx;
    height: 64rpx;
    padding: 0 26rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    font-weight: 600;
}

.recent-btn--ghost {
    color: #475569;
    background: #F8FAFC;
    border: 2rpx solid #E2E8F0;
}

.recent-btn--primary {
    box-shadow: 0 12rpx 20rpx rgba(59, 130, 246, 0.16);
}

.quick-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16rpx;
    margin-top: 22rpx;
}

.quick-card {
    padding: 24rpx 14rpx 20rpx;
    border-radius: 24rpx;
    background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
    border: 2rpx solid #EEF2F7;
}

.quick-icon-wrap {
    position: relative;
    width: 76rpx;
    height: 76rpx;
    margin: 0 auto;
    border-radius: 24rpx;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quick-badge {
    position: absolute;
    top: -10rpx;
    right: -12rpx;
    min-width: 34rpx;
    height: 34rpx;
    padding: 0 8rpx;
    border-radius: 999rpx;
    background: #EF4444;
    color: #FFFFFF;
    font-size: 20rpx;
    font-weight: 700;
    line-height: 34rpx;
    text-align: center;
    box-shadow: 0 8rpx 16rpx rgba(239, 68, 68, 0.25);
}

.quick-name {
    display: block;
    margin-top: 18rpx;
    font-size: 24rpx;
    font-weight: 600;
    color: #0F172A;
    text-align: center;
}

.quick-desc {
    display: block;
    margin-top: 8rpx;
    font-size: 20rpx;
    line-height: 1.45;
    color: #94A3B8;
    text-align: center;
}

.empty-panel {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 56rpx 0 32rpx;
}

.empty-panel-title {
    margin-top: 18rpx;
    font-size: 28rpx;
    font-weight: 600;
    color: #475569;
}

.empty-panel-desc {
    margin-top: 10rpx;
    font-size: 22rpx;
    color: #94A3B8;
}
</style>
