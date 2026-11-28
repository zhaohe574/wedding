<template>
    <page-shell
        title="团队成员"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap team-page">
            <view v-if="loading" class="page-pad loading-pad">
                <base-skeleton :rows="4" variant="card" />
            </view>

            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="reload"
            />

            <base-empty
                v-else-if="!data?.team"
                status="empty"
                empty-icon="empty-data"
                empty-text="暂未加入服务团队"
                desc="联系门店管理员在后台配置所属团队或设为队长后，即可查看团队排班并管理成员"
                fixed
            />

            <view
                v-else
                class="page-pad team-body"
                :class="{ stagger: staggerIn, 'is-in': staggerIn }"
            >
                <!-- 深墨团队总览卡 -->
                <view class="team-hero">
                    <view class="team-hero__head">
                        <view class="team-hero__texts">
                            <text class="team-hero__eyebrow">
                                {{ data.is_leader ? '队长工作台 · 可管理成员' : '团队协同 · 成员视角' }}
                            </text>
                            <text class="team-hero__name">{{ data.team.name }}</text>
                            <text v-if="data.team.remark" class="team-hero__remark">{{
                                data.team.remark
                            }}</text>
                        </view>
                        <view v-if="data.is_leader" class="team-hero__head-btns">
                            <view
                                hover-class="is-pressed"
                                class="team-pill-btn"
                                @tap="toggleEditTeam"
                            >
                                <text class="team-pill-btn__text">{{
                                    editingTeam ? '收起' : '改队名'
                                }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 队长编辑团队名称/简介 -->
                    <view v-if="editingTeam && data.is_leader" class="team-edit-box">
                        <input
                            v-model="teamFormName"
                            class="team-edit-box__input"
                            placeholder="团队名称"
                            placeholder-class="field__ph"
                            maxlength="100"
                        />
                        <input
                            v-model="teamFormRemark"
                            class="team-edit-box__input"
                            placeholder="团队简介/协作说明"
                            placeholder-class="field__ph"
                            maxlength="255"
                        />
                        <base-button
                            :text="savingTeam ? '保存中…' : '保存团队信息'"
                            variant="gold"
                            size="sm"
                            block
                            :loading="savingTeam"
                            @click="handleSaveTeamInfo"
                        />
                    </view>

                    <view v-if="data.summary" class="team-hero__stats">
                        <view class="t-stat">
                            <text class="t-stat__num t-num">
                                {{ data.summary.enabled_members }}/{{ data.summary.total_members }}
                            </text>
                            <text class="t-stat__label">启用/总人数</text>
                        </view>
                        <view class="t-stat">
                            <text class="t-stat__num t-num">{{ data.summary.month_order_count }}</text>
                            <text class="t-stat__label">本月团队单</text>
                        </view>
                        <view class="t-stat">
                            <text class="t-stat__num t-num">{{
                                data.summary.upcoming_7d_schedules
                            }}</text>
                            <text class="t-stat__label">近7日场次</text>
                        </view>
                        <view class="t-stat">
                            <text class="t-stat__num t-num">{{
                                data.summary.pending_confirm_orders
                            }}</text>
                            <text class="t-stat__label">待确认单</text>
                        </view>
                    </view>
                </view>

                <!-- 团队成员列表头 -->
                <view class="sec-bar">
                    <section-header
                        title="团队成员与档期速览"
                        :subtitle="`共 ${data.members.length} 位老师`"
                        :bar="true"
                        flush
                    />
                    <base-button
                        v-if="data.is_leader"
                        text="+ 添加成员"
                        variant="ink"
                        size="sm"
                        :block="false"
                        @click="addSheetOpen = true"
                    />
                </view>

                <!-- 成员卡片列表 -->
                <base-card
                    v-for="(m, index) in data.members"
                    :key="`${m.staff_id}-${m.member_id}`"
                    compact
                    class="member-card"
                    :style="{ '--i': index }"
                >
                    <view class="member-card__head">
                        <image
                            v-if="m.avatar"
                            :src="m.avatar"
                            class="member-card__avatar"
                            mode="aspectFill"
                        />
                        <view v-else class="member-card__avatar member-card__avatar--text">
                            <text class="member-card__initial">{{ m.name.slice(0, 1) || '师' }}</text>
                        </view>

                        <view class="member-card__info">
                            <view class="member-card__name-row">
                                <text class="member-card__name">{{ m.name }}</text>
                                <status-tag
                                    :label="m.status_desc"
                                    :tone="
                                        m.is_leader
                                            ? 'primary'
                                            : m.status === 1
                                              ? 'success'
                                              : 'neutral'
                                    "
                                    size="sm"
                                />
                            </view>
                            <text class="member-card__meta t-num">
                                {{ m.category_name }} · {{ m.experience_years }}年 ·
                                {{ Number(m.rating || 5).toFixed(1) }}分
                            </text>
                            <text v-if="m.mobile_masked" class="member-card__sub t-num">
                                {{ m.mobile_masked }}
                            </text>
                        </view>
                    </view>

                    <view class="member-card__kpis">
                        <view class="kpi-pill">
                            <text class="kpi-pill__val t-num">{{ m.month_booked_days }} 天</text>
                            <text class="kpi-pill__label">本月已约</text>
                        </view>
                        <view class="kpi-pill">
                            <text class="kpi-pill__val t-num">{{ m.pending_confirm_count }} 笔</text>
                            <text class="kpi-pill__label">待确认单</text>
                        </view>
                        <view class="kpi-pill">
                            <text class="kpi-pill__val t-num">{{ m.available_days_14d }}/14 天</text>
                            <text class="kpi-pill__label">近两周可约</text>
                        </view>
                    </view>

                    <!-- 近 14 天可约热力折叠面板 -->
                    <view
                        hover-class="is-pressed"
                        class="heat-toggle"
                        @tap="toggleScheduleExpand(m.staff_id)"
                    >
                        <text class="heat-toggle__text">
                            {{
                                expandedIds[m.staff_id]
                                    ? '收起近 14 天可约排班'
                                    : '展开近 14 天可约排班'
                            }}
                        </text>
                        <tn-icon name="calendar" :size="24" class="heat-toggle__icon" />
                    </view>

                    <view v-if="expandedIds[m.staff_id]" class="heat-grid">
                        <view
                            v-for="day in m.upcoming_14d"
                            :key="day.date"
                            class="heat-cell"
                            :class="day.available ? 'is-free' : 'is-busy'"
                        >
                            <text class="heat-cell__date t-num">{{ day.md_label }}</text>
                            <text class="heat-cell__state">{{
                                day.available ? '可约' : '已满'
                            }}</text>
                        </view>
                    </view>

                    <!-- 队长协调动作栏 -->
                    <view
                        v-if="(data.is_leader && m.mobile) || m.can_toggle || m.can_remove"
                        class="member-card__actions"
                    >
                        <base-button
                            v-if="data.is_leader && m.mobile"
                            text="拨打电话"
                            variant="ghost"
                            size="sm"
                            :block="false"
                            haptic-level="none"
                            @click="dialPhone(m.mobile)"
                        />
                        <base-button
                            v-if="m.can_toggle"
                            :text="m.status === 1 ? '暂设为停用' : '恢复启用'"
                            variant="ghost"
                            size="sm"
                            :block="false"
                            haptic-level="none"
                            :disabled="actingMemberId === m.member_id"
                            @click="handleToggleStatus(m)"
                        />
                        <base-button
                            v-if="m.can_remove"
                            text="移出团队"
                            variant="danger"
                            size="sm"
                            :block="false"
                            haptic-level="none"
                            :disabled="actingMemberId === m.member_id"
                            @click="handleRemoveMember(m)"
                        />
                    </view>
                </base-card>
            </view>
        </view>

        <team-member-add-sheet v-model="addSheetOpen" @added="fetchOverview" />
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：我的团队成员协作与排班管理页。
 */
import { computed, onUnmounted, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    getStaffTeamOverview,
    removeStaffTeamMember,
    toggleStaffTeamMemberStatus,
    updateStaffTeamInfo,
    type StaffTeamMemberItem,
    type StaffTeamOverviewResponse
} from '@/api/staff'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import TeamMemberAddSheet from '../../components/team-member-add-sheet/team-member-add-sheet.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { haptic } from '@/utils/haptics'

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const data = ref<StaffTeamOverviewResponse | null>(null)
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)
const actingMemberId = ref(0)

const editingTeam = ref(false)
const teamFormName = ref('')
const teamFormRemark = ref('')
const savingTeam = ref(false)

const addSheetOpen = ref(false)
const expandedIds = reactive<Record<number, boolean>>({})

let firstShow = true

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

/* ---- 成员卡片错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger(): void {
    if (staggerIn.value || staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        staggerIn.value = true
    }, 40)
}

onUnmounted(() => {
    if (staggerTimer) clearTimeout(staggerTimer)
})

async function fetchOverview(): Promise<void> {
    if (!guardAllowed.value) return
    loading.value = true
    error.value = false
    rawError.value = null
    try {
        const res = await getStaffTeamOverview()
        data.value = res
        if (res?.team) {
            teamFormName.value = res.team.name || ''
            teamFormRemark.value = res.team.remark || ''
        }
        playStagger()
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function reload(): void {
    void fetchOverview()
}

function toggleEditTeam(): void {
    editingTeam.value = !editingTeam.value
    if (editingTeam.value && data.value?.team) {
        teamFormName.value = data.value.team.name || ''
        teamFormRemark.value = data.value.team.remark || ''
    }
}

async function handleSaveTeamInfo(): Promise<void> {
    if (savingTeam.value) return
    const name = teamFormName.value.trim()
    if (!name) {
        toast.warning('请输入团队名称')
        return
    }
    savingTeam.value = true
    try {
        await updateStaffTeamInfo({ name, remark: teamFormRemark.value.trim() })
        toast.success('团队信息已更新')
        editingTeam.value = false
        await fetchOverview()
    } catch (e: any) {
        toast.error(String(e?.message || '更新失败'))
    } finally {
        savingTeam.value = false
    }
}

function toggleScheduleExpand(staffId: number): void {
    expandedIds[staffId] = !expandedIds[staffId]
}

function dialPhone(mobile: string): void {
    if (!mobile) return
    uni.makePhoneCall({ phoneNumber: mobile, fail: () => {} })
}

function handleToggleStatus(m: StaffTeamMemberItem): void {
    if (actingMemberId.value > 0 || !m.member_id) return
    const nextStatus = m.status === 1 ? 0 : 1
    const label = nextStatus === 1 ? '恢复启用' : '暂设为停用'
    // 确认类二次弹窗保留居中形态：uni.showModal 是系统原生模态，不做可下拉关闭的抽屉
    uni.showModal({
        title: label,
        content: `确认将「${m.name}」${label}？`,
        confirmText: '确认',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            haptic('medium', 'team-member-toggle')
            actingMemberId.value = m.member_id
            try {
                await toggleStaffTeamMemberStatus({ member_id: m.member_id, status: nextStatus })
                toast.success('成员状态已更新')
                await fetchOverview()
            } catch (e: any) {
                toast.error(String(e?.message || '操作失败'))
            } finally {
                actingMemberId.value = 0
            }
        }
    })
}

function handleRemoveMember(m: StaffTeamMemberItem): void {
    if (actingMemberId.value > 0 || !m.member_id) return
    // 确认类二次弹窗保留居中形态：uni.showModal 是系统原生模态，不做可下拉关闭的抽屉
    uni.showModal({
        title: '移出团队',
        content: `确认将「${m.name}」移出当前团队？`,
        confirmText: '确认移出',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            haptic('heavy', 'team-member-remove')
            actingMemberId.value = m.member_id
            try {
                await removeStaffTeamMember({ member_id: m.member_id })
                toast.success('已移出团队')
                await fetchOverview()
            } catch (e: any) {
                toast.error(String(e?.message || '操作失败'))
            } finally {
                actingMemberId.value = 0
            }
        }
    })
}

function onPrimary(): void {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
    } else if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

async function init(forceGuard = false): Promise<void> {
    if (!(await verify(forceGuard))) return
    await fetchOverview()
    hasLoadedOnce.value = true
}

onLoad(() => {
    firstShow = true
})

onShow(() => {
    if (firstShow) {
        firstShow = false
        void init()
        return
    }
    if (hasLoadedOnce.value) {
        void init(true)
    }
})

function onRefresh(done: () => void) {
    if (!guardAllowed.value) {
        done()
        return
    }
    Promise.resolve(fetchOverview()).finally(done)
}
</script>

<style lang="scss" scoped>
.team-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.loading-pad {
    padding-top: var(--sp-3);
}

.team-body {
    padding-top: var(--sp-3);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.team-hero {
    padding: var(--sp-4);
    border-radius: var(--r-lg);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);
    color: var(--c-on-dark);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__texts {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__eyebrow {
        font-size: var(--fs-20);
        color: var(--c-brand-soft);
    }

    &__name {
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
    }

    &__remark {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__head-btns {
        flex-shrink: 0;
    }

    &__stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        padding-top: var(--sp-3);
        border-top: 1rpx solid var(--c-line-strong);
    }
}

.team-pill-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 88rpx;
    padding: 6rpx var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-dark-3);
    border: 1rpx solid var(--c-brand-soft);
    box-sizing: border-box;
    transition: background-color var(--dur-fast) var(--ease-out);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
    }
}

.team-edit-box {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__input {
        width: 100%;
        height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }
}

.t-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4rpx;

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }
}

.sec-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
}

.member-card {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__head {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
    }

    &__avatar {
        width: 88rpx;
        height: 88rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        flex-shrink: 0;

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
        }
    }

    &__initial {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__name-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta,
    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__kpis {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--sp-2);
    }

    &__actions {
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line);
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2);
    }
}

.kpi-pill {
    padding: var(--sp-1) var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rpx;

    &__val {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }
}

.heat-toggle {
    min-height: 88rpx;
    padding: var(--sp-1) var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-brand-bg);
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: var(--c-brand-text);
    box-sizing: border-box;

    &__text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: inherit;
    }

    &__icon {
        color: inherit;
    }
}

.heat-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: var(--sp-1);
}

.heat-cell {
    padding: 6rpx 2rpx;
    border-radius: var(--r-xs);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rpx;

    &.is-free {
        background: var(--c-success-bg);
        border: 1rpx solid var(--c-success-border);

        .heat-cell__date,
        .heat-cell__state {
            color: var(--c-success);
        }
    }

    &.is-busy {
        background: var(--c-neutral-bg);
        border: 1rpx solid var(--c-neutral-border);

        .heat-cell__date,
        .heat-cell__state {
            color: var(--c-ink-3);
        }
    }

    &__date {
        font-size: var(--fs-18);
        font-weight: var(--fw-medium);
        font-variant-numeric: tabular-nums;
    }

    &__state {
        font-size: var(--fs-16);
    }
}

/* v10 §3.13：减弱动效下不做颜色过渡 */
@media (prefers-reduced-motion: reduce) {
    .team-pill-btn {
        transition: none;
    }
}
</style>
