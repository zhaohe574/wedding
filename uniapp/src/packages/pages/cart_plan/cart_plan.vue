<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="我的方案"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>

    <view class="cart-plan-page">
        <!-- 顶部操作栏 -->
        <view class="header-actions">
            <view class="action-card" @click="goToShareInput">
                <tn-icon name="share" size="40" :color="$theme.primaryColor" />
                <text class="action-text">输入分享码</text>
            </view>
        </view>

        <!-- 空状态 -->
        <view class="empty-state" v-if="!loading && planList.length === 0">
            <tn-icon name="folder" size="160" color="#CCCCCC" />
            <text class="empty-title">暂无保存的方案</text>
            <text class="empty-desc">保存您的预约方案，随时查看和分享</text>
            <view class="btn-action" :style="{ backgroundColor: $theme.primaryColor }" @click="goToShareInput">
                <tn-icon name="share" size="32" color="#FFFFFF" />
                <text>输入分享码</text>
            </view>
        </view>

        <!-- 方案列表 -->
        <view class="plan-list">
            <view class="plan-card" v-for="plan in planList" :key="plan.id">
                <!-- 卡片头部 -->
                <view class="card-header" :style="{ backgroundColor: getColor('primary-light-9') }">
                    <view class="header-left">
                        <view class="plan-title">
                            <text class="plan-name">{{ plan.plan_name }}</text>
                            <view class="plan-meta">
                                <tn-icon name="time" size="24" color="#999999" />
                                <text class="meta-text">{{ plan.cart_items?.length || 0 }} 项服务</text>
                            </view>
                        </view>
                        <view class="plan-price">
                            <text class="price-label">总价</text>
                            <text class="price-symbol" :style="{ color: $theme.ctaColor }">¥</text>
                            <text class="price-value" :style="{ color: $theme.ctaColor }">
                                {{ formatPrice(plan.actual_total_price || plan.total_price) }}
                            </text>
                        </view>
                    </view>
                </view>

                <!-- 方案明细 -->
                <view class="plan-detail">
                    <view class="detail-header">
                        <text class="detail-title">包含服务</text>
                        <text class="detail-count">{{ plan.cart_items?.length || 0 }}项</text>
                    </view>
                    <view class="detail-groups" v-if="plan.groups?.length">
                        <view class="detail-group" v-for="group in plan.groups" :key="group.key">
                            <view class="group-header">
                                <view class="staff-section">
                                    <image
                                        :src="group.staff_avatar || '/static/images/user/default_avatar.png'"
                                        class="staff-avatar"
                                        mode="aspectFill"
                                    />
                                    <view class="staff-info">
                                        <text class="staff-name">{{ group.staff_name || '未知人员' }}</text>
                                        <text class="staff-subtitle">{{ group.schedule_date }}</text>
                                    </view>
                                </view>
                                <view class="group-total">
                                    <text class="group-total-label">小计</text>
                                    <text class="group-total-value" :style="{ color: $theme.ctaColor }">
                                        ¥{{ formatPrice(group.total_price) }}
                                    </text>
                                </view>
                            </view>
                            <view class="group-packages">
                                <view class="package-group" v-for="pkg in group.packages" :key="pkg.key">
                                    <view class="package-header">
                                        <view class="package-title">
                                            <tn-icon name="gift" size="24" />
                                            <text>{{ pkg.package_name || '未命名套餐' }}</text>
                                        </view>
                                        <text class="package-total">¥{{ formatPrice(pkg.total_price) }}</text>
                                    </view>
                                    <view class="package-items">
                                        <view class="package-item" v-for="item in pkg.items" :key="item._key">
                                            <view class="slot-info">
                                                <view class="slot-row">
                                                    <text class="slot-label">{{ item.time_slot_desc || '未知场次' }}</text>
                                                    <text class="slot-price" :style="{ color: $theme.ctaColor }">
                                                        ¥{{ formatPrice(item.price) }}
                                                    </text>
                                                </view>
                                                <view class="slot-remark" v-if="item.remark">
                                                    <tn-icon name="edit" size="20" color="#999999" />
                                                    <text>{{ item.remark }}</text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="detail-empty" v-else>
                        <text>暂无服务项</text>
                    </view>
                </view>

                <!-- 操作按钮组 -->
                <view class="action-group">
                    <view class="action-row">
                        <view class="action-btn secondary" @click="handleCancelDefault(plan)" v-if="plan.is_default">
                            <tn-icon name="close" size="36" color="#999999" />
                            <text>取消默认</text>
                        </view>
                        <view class="action-btn secondary" @click="handleSetDefault(plan)" v-if="!plan.is_default">
                            <tn-icon name="star" size="36" color="#999999" />
                            <text>设为默认</text>
                        </view>
                        <view class="action-btn secondary" @click="handleShare(plan)">
                            <tn-icon name="share" size="36" :color="$theme.primaryColor" />
                            <text>分享方案</text>
                        </view>
                    </view>
                    <view class="action-row">
                        <view class="action-btn primary" :style="{ backgroundColor: $theme.primaryColor }" @click="handleApply(plan)">
                            <tn-icon name="shopping-cart" size="36" color="#FFFFFF" />
                            <text>应用到购物车</text>
                        </view>
                        <view class="action-btn danger" @click="handleDelete(plan)">
                            <tn-icon name="delete" size="36" color="#FF2C3C" />
                        </view>
                    </view>
                </view>
            </view>
        </view>
            <tn-popup v-model="showSharePopup" mode="center" :border-radius="24" :mask-close-able="true">
                <view class="share-modal">
                    <text class="modal-title">分享方案</text>
                    <text class="modal-desc">分享码</text>
                    <view class="share-code">{{ shareCode }}</view>
                    <view class="modal-actions">
                        <view
                            class="modal-btn btn-primary"
                            :style="{ backgroundColor: $theme.primaryColor }"
                            @click="handleShareCopy"
                        >
                            复制分享码
                        </view>
                        <view class="modal-btn btn-secondary" @click="handleShareRegenerate">
                            生成新码
                        </view>
                        <view class="modal-btn btn-ghost" @click="closeSharePopup">
                            关闭
                        </view>
                    </view>
                </view>
            </tn-popup>



</view>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import {
    getMyCartPlans,
    deleteCartPlan,
    setDefaultCartPlan,
    cancelDefaultCartPlan,
    generatePlanShareCode
} from '@/api/cart'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()

const loading = ref(false)
const planList = ref<any[]>([])
const showSharePopup = ref(false)
const shareCode = ref('')
const sharePlanId = ref<number | null>(null)
const shareLoading = ref(false)


const formatPrice = (value: any) => {
    const num = Number(value || 0)
    return Number.isFinite(num) ? num.toFixed(2) : '0.00'
}

const buildPlanGroups = (items: any[]) => {
    const groups: any[] = []
    const groupMap = new Map<string, any>()
    const slotMap: Record<number, string> = {
        0: '全天',
        1: '早礼',
        2: '午宴',
        3: '晚宴'
    }

    items.forEach((item: any, index: number) => {
        const staffId = Number(item.staff_id || item.staff?.id || 0)
        const staffName = item.staff_name || item.staff?.name || ''
        const staffAvatar = item.staff_avatar || item.staff?.avatar || ''
        const scheduleDate = item.schedule_date || ''
        const groupKey = `${staffId}-${scheduleDate}`
        let group = groupMap.get(groupKey)
        if (!group) {
            group = {
                key: groupKey,
                staff_id: staffId,
                staff_name: staffName,
                staff_avatar: staffAvatar,
                schedule_date: scheduleDate,
                total_price: 0,
                packages: [],
                packageMap: new Map<string, any>()
            }
            groupMap.set(groupKey, group)
            groups.push(group)
        }

        const itemPrice = Number(item.price || 0) * Number(item.quantity || 1)
        group.total_price += itemPrice

        const packageId = Number(item.package_id || item.package?.id || 0)
        const packageName = item.package_name || item.package?.name || ''
        const packageKey = `${groupKey}-${packageId}`
        let pkg = group.packageMap.get(packageKey)
        if (!pkg) {
            pkg = {
                key: packageKey,
                package_id: packageId,
                package_name: packageName,
                total_price: 0,
                items: []
            }
            group.packageMap.set(packageKey, pkg)
            group.packages.push(pkg)
        }

        pkg.total_price += itemPrice
        const timeSlot = Number(item.time_slot || 0)
        const itemKey = item.cart_id || item.id || `${packageKey}-${index}`
        pkg.items.push({
            ...item,
            _key: itemKey,
            time_slot_desc: item.time_slot_desc || slotMap[timeSlot] || '未知场次'
        })
    })

    groups.forEach((group: any) => {
        group.total_price = Number(group.total_price.toFixed(2))
        group.packages.forEach((pkg: any) => {
            pkg.total_price = Number(pkg.total_price.toFixed(2))
            pkg.items.sort((a: any, b: any) => Number(a.time_slot || 0) - Number(b.time_slot || 0))
        })
        delete group.packageMap
    })

    return groups
}

// 获取主题色浅色变体
const getColor = (type: string) => {
    const colors: Record<string, string> = {
        'primary-light-9': '#F3E8FF',
        'cta-light-9': '#FFF7E6'
    }
    return colors[type] || '#F5F5F5'
}

// 获取方案列表
const fetchPlans = async () => {
    loading.value = true
    try {
        const res = await getMyCartPlans()
        planList.value = (res || []).map((plan: any) => {
            const items = Array.isArray(plan.cart_items)
                ? plan.cart_items
                : Array.isArray(plan.items)
                  ? plan.items
                  : []
            return {
                ...plan,
                cart_items: items,
                groups: buildPlanGroups(items)
            }
        })
    } finally {
        loading.value = false
    }
}

// 设为默认
const handleSetDefault = async (plan: any) => {
    try {
        await setDefaultCartPlan({ plan_id: plan.id })
        uni.showToast({ title: '设置成功', icon: 'success' })
        fetchPlans()
    } catch (e: any) {
        uni.showToast({ title: e.message || '操作失败', icon: 'none' })
    }
}

// 取消默认
const handleCancelDefault = (plan: any) => {
    uni.showModal({
        title: '取消默认方案',
        content: '确定要取消默认方案吗？',
        confirmColor: $theme.primaryColor,
        success: async (res) => {
            if (res.confirm) {
                try {
                    await cancelDefaultCartPlan()
                    uni.showToast({ title: '已取消默认', icon: 'success' })
                    fetchPlans()
                } catch (e: any) {
                    uni.showToast({ title: e.message || '操作失败', icon: 'none' })
                }
            }
        }
    })
}

// 分享

const openSharePopup = (planId: number, code: string) => {

    sharePlanId.value = planId

    shareCode.value = code || ''

    showSharePopup.value = true

}



const closeSharePopup = () => {

    showSharePopup.value = false

}



const handleShareCopy = () => {

    if (!shareCode.value) {

        uni.showToast({ title: '分享码为空', icon: 'none' })

        return

    }

    uni.setClipboardData({

        data: shareCode.value,

        success: () => {
            uni.showToast({ title: '分享码已复制', icon: 'success' })
            closeSharePopup()
        }

    })

}



const handleShareRegenerate = async () => {

    if (!sharePlanId.value || shareLoading.value) {

        return

    }

    shareLoading.value = true

    try {

        const res = await generatePlanShareCode({ plan_id: sharePlanId.value, force: 1 })

        shareCode.value = res.share_code || ''

        uni.showToast({ title: '已生成新码', icon: 'success' })

    } catch (e: any) {

        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '生成分享码失败'

        uni.showToast({ title: errorMsg, icon: 'none' })

    } finally {

        shareLoading.value = false

    }

}



const handleShare = async (plan: any) => {

    try {

        const res = await generatePlanShareCode({ plan_id: plan.id })

        const code = res.share_code || ''

        openSharePopup(plan.id, code)

    } catch (e: any) {

        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '生成分享码失败'

        uni.showToast({ title: errorMsg, icon: 'none' })

    }

}



const handleApply = (plan: any) => {
    uni.showModal({
        title: '应用方案',
        content: '应用该方案将覆盖当前购物车，确定继续？',
        confirmColor: $theme.primaryColor,
        success: (res) => {
            if (res.confirm) {
                uni.navigateTo({
                    url: `/packages/pages/cart/cart?apply_plan=${plan.id}`
                })
            }
        }
    })
}

// 删除
const handleDelete = (plan: any) => {
    if (plan.is_default) {
        uni.showToast({ title: '默认方案不能删除', icon: 'none' })
        return
    }
    uni.showModal({
        title: '删除方案',
        content: '确定要删除该方案吗？',
        confirmColor: '#FF2C3C',
        success: async (res) => {
            if (res.confirm) {
                await deleteCartPlan({ plan_id: plan.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                fetchPlans()
            }
        }
    })
}

// 跳转到输入分享码页面
const goToShareInput = () => {
    uni.navigateTo({
        url: '/packages/pages/share_plan/share_plan'
    })
}

onShow(() => {
    fetchPlans()
})
</script>

<style lang="scss" scoped>
.cart-plan-page {
    min-height: 100vh;
    background: #F5F5F5;
    padding: 24rpx;
    padding-bottom: 48rpx;
}

/* 顶部操作栏 */
.header-actions {
    margin-bottom: 24rpx;

    .action-card {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        background: #FFFFFF;
        padding: 24rpx 32rpx;
        border-radius: 56rpx;
        box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
            box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
        }

        .action-text {
            font-size: 28rpx;
            font-weight: 500;
            color: #333333;
        }
    }
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 200rpx 48rpx;

    .empty-title {
        font-size: 32rpx;
        font-weight: 600;
        color: #333333;
        margin-top: 32rpx;
        margin-bottom: 16rpx;
    }

    .empty-desc {
        font-size: 26rpx;
        color: #999999;
        margin-bottom: 48rpx;
        text-align: center;
    }

    .btn-action {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12rpx;
        color: #FFFFFF;
        font-size: 30rpx;
        font-weight: 600;
        padding: 28rpx 64rpx;
        border-radius: 64rpx;
        box-shadow: 0 12rpx 32rpx rgba(124, 58, 237, 0.4);
        transition: all 0.3s ease;

        &:active {
            transform: translateY(4rpx) scale(0.98);
            box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.3);
        }
    }
}

/* 方案列表 */
.plan-list {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

/* 方案卡片 */
.plan-card {
    background: #FFFFFF;
    border-radius: 32rpx;
    padding: 0;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin-bottom: 24rpx;

    /* 卡片头部 */
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20rpx 24rpx;

        .header-left {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16rpx;
            flex: 1;
            min-width: 0;

            .plan-title {
                flex: 1;
                min-width: 0;
                display: flex;
                align-items: center;
                gap: 12rpx;

                .plan-name {
                    display: block;
                    font-size: 30rpx;
                    font-weight: 700;
                    color: #333333;
                    margin-bottom: 0;
                    flex: 1;
                    min-width: 0;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .plan-meta {
                    display: flex;
                    align-items: center;
                    gap: 6rpx;
                    flex-shrink: 0;

                    .meta-text {
                        font-size: 22rpx;
                        color: #999999;
                    }
                }
            }

            .plan-price {
                display: flex;
                align-items: baseline;
                gap: 4rpx;
                flex-shrink: 0;

                .price-label {
                    font-size: 22rpx;
                    color: #999999;
                }

                .price-symbol {
                    font-size: 28rpx;
                    font-weight: 700;
                }

                .price-value {
                    font-size: 36rpx;
                    font-weight: 800;
                    line-height: 1;
                }
            }
        }

    }

    /* 价格区域已合并到头部 */

    /* 方案明细 */
    .plan-detail {
        padding: 32rpx;

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20rpx;

            .detail-title {
                font-size: 28rpx;
                font-weight: 600;
                color: #333333;
            }

            .detail-count {
                font-size: 24rpx;
                color: #999999;
                background: #F5F5F5;
                padding: 6rpx 16rpx;
                border-radius: 24rpx;
            }
        }

        .detail-group {
            background: #F9FAFB;
            border-radius: 16rpx;
            padding: 20rpx;
            border: 1rpx solid #F0F0F0;
            margin-bottom: 16rpx;
        }

        .detail-group:last-child {
            margin-bottom: 0;
        }

        .group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12rpx;
        }

        .staff-section {
            display: flex;
            align-items: center;
            gap: 16rpx;
        }

        .staff-avatar {
            width: 72rpx;
            height: 72rpx;
            border-radius: 16rpx;
        }

        .staff-info {
            display: flex;
            flex-direction: column;
            gap: 6rpx;
        }

        .staff-name {
            font-size: 28rpx;
            font-weight: 600;
            color: #333333;
        }

        .staff-subtitle {
            font-size: 24rpx;
            color: #999999;
        }

        .group-total {
            text-align: right;
        }

        .group-total-label {
            display: block;
            font-size: 22rpx;
            color: #999999;
        }

        .group-total-value {
            font-size: 28rpx;
            font-weight: 700;
        }

        .group-packages {
            display: flex;
            flex-direction: column;
            gap: 12rpx;
        }

        .package-group {
            background: #FFFFFF;
            border-radius: 12rpx;
            padding: 16rpx;
            border: 1rpx solid #EEEEEE;
        }

        .package-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12rpx;
        }

        .package-title {
            display: flex;
            align-items: center;
            gap: 8rpx;
            font-size: 26rpx;
            font-weight: 600;
            color: #333333;
        }

        .package-total {
            font-size: 26rpx;
            font-weight: 600;
            color: #333333;
        }

        .package-items {
            display: flex;
            flex-direction: column;
            gap: 12rpx;
        }

        .package-item {
            display: flex;
            align-items: center;
            padding: 16rpx;
            background: #F9FAFB;
            border-radius: 12rpx;
            border: 1rpx solid #EEEEEE;
        }

        .slot-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6rpx;
        }

        .slot-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .slot-label {
            font-size: 26rpx;
            font-weight: 500;
            color: #333333;
        }

        .slot-price {
            font-size: 26rpx;
            font-weight: 600;
        }

        .slot-remark {
            display: flex;
            align-items: center;
            gap: 6rpx;
            font-size: 22rpx;
            color: #999999;
        }

        .detail-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24rpx;
            background: #F9FAFB;
            border-radius: 16rpx;
            color: #999999;
            font-size: 24rpx;
        }
    }

    /* 操作按钮组 */
    .action-group {
        padding: 24rpx 32rpx 32rpx;
        display: flex;
        flex-direction: column;
        gap: 16rpx;

        .action-row {
            display: flex;
            gap: 16rpx;

            .action-btn {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12rpx;
                padding: 24rpx;
                border-radius: 56rpx;
                font-size: 26rpx;
                font-weight: 500;
                transition: all 0.2s ease;

                &.secondary {
                    background: #F5F5F5;
                    color: #666666;

                    &:active {
                        background: #E5E5E5;
                        transform: scale(0.98);
                    }
                }

                &.primary {
                    flex: 3;
                    color: #FFFFFF;
                    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.35);

                    &:active {
                        transform: scale(0.98);
                        box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.35);
                    }
                }

                &.danger {
                    flex: 0 0 auto;
                    width: 96rpx;
                    background: #FEE;
                    color: #FF2C3C;

                    &:active {
                        background: #FDD;
                        transform: scale(0.95);
                    }
                }
            }
        }
    }
}

.share-modal {
    width: 560rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    padding: 32rpx;
    text-align: center;
}

.share-modal .modal-title {
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
}

.share-modal .modal-desc {
    margin-top: 12rpx;
    font-size: 24rpx;
    color: #999999;
}

.share-modal .share-code {
    margin-top: 20rpx;
    padding: 20rpx;
    background: #F5F5F5;
    border-radius: 16rpx;
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
    letter-spacing: 2rpx;
    word-break: break-all;
}

.share-modal .modal-actions {
    margin-top: 24rpx;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.share-modal .modal-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 22rpx;
    border-radius: 48rpx;
    font-size: 26rpx;
    font-weight: 600;
}

.share-modal .modal-btn.btn-primary {
    color: #FFFFFF;
}

.share-modal .modal-btn.btn-secondary {
    background: #F5F5F5;
    color: #666666;
}

.share-modal .modal-btn.btn-ghost {
    background: transparent;
    color: #999999;
}

</style>
