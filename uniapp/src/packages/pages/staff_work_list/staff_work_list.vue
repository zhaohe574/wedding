<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="作品管理" />

    <view class="page-container">
        <z-paging ref="pagingRef" v-model="workList" @query="queryList" :auto="false" :hide-empty-view="true">
            <template #top>
                <view class="hero-wrap">
                    <view
                        class="hero-card"
                        :style="{
                            background: `linear-gradient(145deg, ${$theme.primaryColor} 0%, ${$theme.secondaryColor || $theme.primaryColor} 78%)`
                        }"
                    >
                        <view class="hero-top">
                            <view>
                                <text class="hero-title">作品工作区</text>
                                <text class="hero-desc">维护案例内容，持续提升展示力与信任感</text>
                            </view>
                            <view class="hero-add-btn" @click="handleAdd">
                                <tn-icon name="add" size="28" color="#FFFFFF" />
                                <text>新增作品</text>
                            </view>
                        </view>

                        <view class="hero-stats">
                            <view class="hero-stat">
                                <text class="hero-stat-label">总作品</text>
                                <text class="hero-stat-value">{{ workList.length }}</text>
                            </view>
                            <view class="hero-stat">
                                <text class="hero-stat-label">展示中</text>
                                <text class="hero-stat-value">{{ visibleCount }}</text>
                            </view>
                            <view class="hero-stat">
                                <text class="hero-stat-label">待审核</text>
                                <text class="hero-stat-value">{{ pendingAuditCount }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </template>

            <!-- 作品列表 -->
            <view class="work-list">
                <view
                    v-for="item in workList"
                    :key="item.id"
                    class="work-card"
                    @click="handleEdit(item)"
                >
                    <!-- 作品封面 -->
                    <view class="work-cover-wrapper">
                        <image
                            class="work-cover"
                            :src="item.cover || item.images?.[0] || defaultCover"
                            mode="aspectFill"
                        />
                        <!-- 审核状态标签 -->
                        <view class="audit-badge" :style="getAuditStyle(item.audit_status)">
                            <tn-icon
                                :name="getAuditIcon(item.audit_status)"
                                size="24"
                                color="#FFFFFF"
                            />
                            <text>{{ auditStatusText(item.audit_status) }}</text>
                        </view>
                    </view>

                    <!-- 作品信息 -->
                    <view class="work-info">
                        <view class="work-title">{{ item.title || '未命名作品' }}</view>
                        <view class="work-meta">
                            <view class="meta-item">
                                <tn-icon name="eye" size="24" color="#999999" />
                                <text>{{ item.view_count || 0 }}</text>
                            </view>
                            <view class="meta-item">
                                <tn-icon name="like" size="24" color="#999999" />
                                <text>{{ item.like_count || 0 }}</text>
                            </view>
                            <view
                                class="show-status"
                                :style="{ color: item.is_show ? $theme.primaryColor : '#999999' }"
                            >
                                <tn-icon
                                    :name="item.is_show ? 'eye' : 'eye-off'"
                                    size="24"
                                    :color="item.is_show ? $theme.primaryColor : '#999999'"
                                />
                                <text>{{ item.is_show ? '显示中' : '已隐藏' }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 操作按钮 -->
                    <view class="work-actions">
                        <view
                            class="action-btn edit-btn"
                            :style="{
                                color: $theme.primaryColor,
                                borderColor: $theme.primaryColor
                            }"
                            @click.stop="handleEdit(item)"
                        >
                            <tn-icon name="edit" size="28" :color="$theme.primaryColor" />
                            <text>编辑</text>
                        </view>
                        <view class="action-btn delete-btn" @click.stop="handleDelete(item)">
                            <tn-icon name="delete" size="28" color="#FF2C3C" />
                            <text>删除</text>
                        </view>
                    </view>
                </view>

                <!-- 空状态 -->
                <view v-if="workList.length === 0" class="empty-state">
                    <tn-icon name="image" size="120" color="#E5E5E5" />
                    <text class="empty-text">暂无作品</text>
                    <view
                        class="empty-btn"
                        :style="{
                            background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`,
                            color: $theme.btnColor
                        }"
                        @click="handleAdd"
                    >
                        立即添加
                    </view>
                </view>
            </view>
        </z-paging>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { staffCenterWorkLists, staffCenterWorkDelete } from '@/api/staffCenter'
import { ensureStaffCenterAccess } from '@/utils/staff-center'
import { useThemeStore } from '@/stores/theme'

const $theme = useThemeStore()
const pagingRef = ref<any>(null)
const workList = ref<any[]>([])
const defaultCover = '/static/images/user/default_avatar.png'

const visibleCount = computed(() => workList.value.filter((item) => Number(item.is_show) === 1).length)
const pendingAuditCount = computed(
    () => workList.value.filter((item) => Number(item.audit_status) === 0).length
)

// 审核状态文本
const auditStatusText = (status: number) => {
    const map: Record<number, string> = {
        0: '待审核',
        1: '已通过',
        2: '已拒绝'
    }
    return map[status] || '未知'
}

// 审核状态样式
const getAuditStyle = (status: number) => {
    const styles: Record<number, any> = {
        0: { background: 'rgba(255, 153, 0, 0.9)', color: '#FFFFFF' },
        1: { background: 'rgba(25, 190, 107, 0.9)', color: '#FFFFFF' },
        2: { background: 'rgba(255, 44, 60, 0.9)', color: '#FFFFFF' }
    }
    return styles[status] || styles[0]
}

// 审核状态图标
const getAuditIcon = (status: number) => {
    const icons: Record<number, string> = {
        0: 'clock',
        1: 'check-circle',
        2: 'close-circle'
    }
    return icons[status] || 'clock'
}

// 查询列表
const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const res: any = await staffCenterWorkLists({ page: pageNo, page_size: pageSize })
        const list = res?.data || []
        pagingRef.value.complete(list)
    } catch (e) {
        pagingRef.value.complete(false)
    }
}

// 新增作品
const handleAdd = () => {
    uni.navigateTo({ url: '/packages/pages/staff_work_edit/staff_work_edit' })
}

// 编辑作品
const handleEdit = (item: any) => {
    uni.navigateTo({ url: `/packages/pages/staff_work_edit/staff_work_edit?id=${item.id}` })
}

// 删除作品
const handleDelete = (item: any) => {
    uni.showModal({
        title: '确认删除',
        content: '删除后不可恢复，是否继续？',
        success: async (res) => {
            if (!res.confirm) return
            try {
                await staffCenterWorkDelete({ id: item.id })
                uni.showToast({ title: '删除成功', icon: 'success' })
                pagingRef.value.reload()
            } catch (e: any) {
                const msg = typeof e === 'string' ? e : e?.msg || e?.message || '删除失败'
                uni.showToast({ title: msg, icon: 'none' })
            }
        }
    })
}

onShow(async () => {
    if (!(await ensureStaffCenterAccess())) return
    pagingRef.value?.reload()
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    background:
        radial-gradient(circle at top left, rgba(191, 219, 254, 0.72) 0, rgba(246, 248, 252, 0) 36%),
        linear-gradient(180deg, #F6F8FC 0%, #F4F6FB 100%);
}

.hero-wrap {
    padding: 24rpx 24rpx 0;
}

.hero-card {
    padding: 28rpx;
    border-radius: 30rpx;
    box-shadow: 0 18rpx 36rpx rgba(37, 99, 235, 0.18);
}

.hero-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.hero-title {
    display: block;
    font-size: 36rpx;
    font-weight: 700;
    color: #FFFFFF;
}

.hero-desc {
    display: block;
    margin-top: 10rpx;
    font-size: 22rpx;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.8);
}

.hero-add-btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 16rpx 22rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.16);
    font-size: 24rpx;
    font-weight: 600;
    color: #FFFFFF;
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 26rpx;
}

.hero-stat {
    padding: 20rpx;
    border-radius: 22rpx;
    background: rgba(255, 255, 255, 0.14);
}

.hero-stat-label {
    display: block;
    font-size: 22rpx;
    color: rgba(255, 255, 255, 0.75);
}

.hero-stat-value {
    display: block;
    margin-top: 12rpx;
    font-size: 38rpx;
    font-weight: 800;
    color: #FFFFFF;
}

/* 作品列表 */
.work-list {
    padding: 24rpx;
}

.work-card {
    margin-bottom: 20rpx;
    background: #FFFFFF;
    border-radius: 24rpx;
    overflow: hidden;
    box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.05);
    transition: all 0.15s ease;

    &:active {
        background: #FAFAFA;
    }

    &:last-child {
        margin-bottom: 0;
    }
}

/* 作品封面 */
.work-cover-wrapper {
    position: relative;
    width: 100%;
    height: 400rpx;
}

.work-cover {
    width: 100%;
    height: 100%;
    background: #f5f5f5;
}

.audit-badge {
    position: absolute;
    top: 16rpx;
    right: 16rpx;
    display: flex;
    align-items: center;
    gap: 6rpx;
    padding: 8rpx 16rpx;
    border-radius: 24rpx;
    font-size: 24rpx;
    font-weight: 500;
    backdrop-filter: blur(10rpx);
}

/* 作品信息 */
.work-info {
    padding: 24rpx;
}

.work-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #333333;
    line-height: 1.4;
    margin-bottom: 16rpx;
}

.work-meta {
    display: flex;
    align-items: center;
    gap: 32rpx;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 24rpx;
    color: #999999;
}

.show-status {
    display: flex;
    align-items: center;
    gap: 8rpx;
    font-size: 24rpx;
    font-weight: 500;
    margin-left: auto;
}

/* 操作按钮 */
.work-actions {
    display: flex;
    gap: 16rpx;
    padding: 0 24rpx 24rpx;
}

.action-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    height: 64rpx;
    border-radius: 48rpx;
    font-size: 28rpx;
    font-weight: 500;
    border: 2rpx solid;
    transition: all 0.2s ease;

    &:active {
        opacity: 0.8;
    }
}

.edit-btn {
    background: transparent;
}

.delete-btn {
    background: transparent;
    color: #ff2c3c;
    border-color: #ff2c3c;
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 120rpx 0;
    gap: 24rpx;
}

.empty-text {
    font-size: 28rpx;
    color: #999999;
}

.empty-btn {
    margin-top: 16rpx;
    padding: 16rpx 48rpx;
    border-radius: 48rpx;
    font-size: 28rpx;
    font-weight: 600;
    box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.3);

    &:active {
        opacity: 0.9;
    }
}
</style>
