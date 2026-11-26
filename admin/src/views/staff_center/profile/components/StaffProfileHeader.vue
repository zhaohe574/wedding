<template>
    <section class="staff-profile-header admin-page-card">
        <div class="staff-profile-header__top">
            <div class="staff-profile-header__identity">
                <el-avatar :src="profile.avatar" :size="64">
                    {{ initial }}
                </el-avatar>
                <div class="staff-profile-header__main">
                    <div class="staff-profile-header__title-row">
                        <h2 class="staff-profile-header__name">{{ profile.name || '服务人员' }}</h2>
                        <el-tag :type="statusTagType" effect="plain">{{ statusText }}</el-tag>
                        <el-tag :type="auditTagType" effect="plain">{{ auditText }}</el-tag>
                    </div>
                    <div class="staff-profile-header__meta">
                        <span>工号 {{ profile.sn || '未分配' }}</span>
                        <span class="staff-profile-header__dot" />
                        <span>{{ profile.category_name || '未设置服务分类' }}</span>
                        <span class="staff-profile-header__dot" />
                        <span>{{ priceText }}</span>
                    </div>
                </div>
            </div>
            <div class="staff-profile-header__overview">
                <div class="staff-profile-header__metric">
                    <strong>{{ ratingText }}</strong>
                    <span>评分</span>
                </div>
                <div class="staff-profile-header__metric">
                    <strong>{{ profile.order_count || 0 }}</strong>
                    <span>接单数</span>
                </div>
            </div>
        </div>

        <div class="staff-profile-header__bottom">
            <div class="staff-profile-header__switch">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="staff-profile-header__switch-item"
                    :class="{ 'is-active': active === tab.key }"
                    @click="go(tab.path)"
                >
                    {{ tab.label }}
                </button>
            </div>
            <div class="staff-profile-header__shortcuts">
                <el-button type="primary" link :disabled="!routes.work" @click="go(routes.work)">
                    我的作品
                </el-button>
                <el-button
                    type="primary"
                    link
                    :disabled="!routes.certificate"
                    @click="go(routes.certificate)"
                >
                    我的证书
                </el-button>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts" name="StaffProfileHeader">
import { ElMessage } from 'element-plus'
import { computed } from 'vue'
import { useRouter } from 'vue-router'

import type { StaffProfileFormData } from '../use-staff-center-profile'

interface StaffProfileRoutes {
    profile: string
    showcase: string
    package: string
    work: string
    certificate: string
}

const props = defineProps<{
    active: 'profile' | 'showcase' | 'package'
    profile: StaffProfileFormData
    routes: StaffProfileRoutes
}>()

const router = useRouter()

const initial = computed(() => String(props.profile.name || '人').slice(0, 1))

const statusText = computed(() => (props.profile.status === 1 ? '接单中' : '暂停接单'))
const statusTagType = computed(() => (props.profile.status === 1 ? 'success' : 'info'))

const auditText = computed(() => {
    if (props.profile.audit_status === 1) {
        return '资料已通过'
    }
    if (props.profile.audit_status === 0) {
        return '资料待审核'
    }
    if (props.profile.audit_status === 2) {
        return '资料被驳回'
    }
    return props.profile.audit_status_desc || '审核状态未知'
})
const auditTagType = computed(() => {
    if (props.profile.audit_status === 1) {
        return 'success'
    }
    if (props.profile.audit_status === 2) {
        return 'danger'
    }
    return 'warning'
})

const priceText = computed(() => props.profile.price_text || '价格待配置')
const ratingText = computed(() =>
    Number(props.profile.rating || 0) > 0 ? Number(props.profile.rating).toFixed(1) : '暂无'
)

const tabs = computed(() => [
    { key: 'profile' as const, label: '基本资料', path: props.routes.profile },
    { key: 'showcase' as const, label: '服务展示', path: props.routes.showcase },
    { key: 'package' as const, label: '我的套餐', path: props.routes.package }
])

const go = (path: string) => {
    if (!path) {
        ElMessage.warning('当前账号暂无该页面入口')
        return
    }
    router.push(path)
}
</script>

<style lang="scss" scoped>
.staff-profile-header {
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.staff-profile-header__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.staff-profile-header__identity {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
}

.staff-profile-header__main {
    min-width: 0;
}

.staff-profile-header__title-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.staff-profile-header__name {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    line-height: 1.35;
    color: var(--admin-color-title);
}

.staff-profile-header__meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 8px;
    font-size: 13px;
    line-height: 1.6;
    color: var(--admin-color-muted);
}

.staff-profile-header__dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--admin-color-border);
}

.staff-profile-header__overview {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 10px 18px;
    border: 1px solid var(--admin-color-border);
    border-radius: var(--admin-radius-sm);
    background: var(--admin-surface-soft);
}

.staff-profile-header__metric {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;

    strong {
        font-size: 18px;
        font-weight: 600;
        line-height: 1.2;
        color: var(--admin-color-title);
        font-variant-numeric: tabular-nums;
    }

    span {
        font-size: 12px;
        line-height: 1.4;
        color: var(--admin-color-muted);
    }
}

.staff-profile-header__bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding-top: 14px;
    border-top: 1px dashed var(--admin-color-border);
}

.staff-profile-header__switch {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px;
    border-radius: 999px;
    background: var(--el-fill-color-light);
}

.staff-profile-header__switch-item {
    padding: 6px 18px;
    border: none;
    border-radius: 999px;
    background: transparent;
    color: var(--admin-color-muted);
    font-size: 13px;
    line-height: 1.4;
    cursor: pointer;
    transition:
        color 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease;

    &:hover {
        color: var(--el-color-primary);
    }

    &.is-active {
        background: var(--admin-surface-card);
        color: var(--el-color-primary);
        font-weight: 600;
        box-shadow: 0 1px 4px rgba(16, 24, 40, 0.08);
    }
}

.staff-profile-header__shortcuts {
    display: flex;
    align-items: center;
    gap: 8px;

    :deep(.el-button + .el-button) {
        margin-left: 0;
    }
}

@media (max-width: 960px) {
    .staff-profile-header__top {
        align-items: flex-start;
    }

    .staff-profile-header__overview {
        width: 100%;
        justify-content: space-around;
    }
}
</style>
