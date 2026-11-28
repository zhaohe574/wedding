<template>
    <page-shell
        title="我的作品"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
        @reach-bottom="onReachBottomLoad"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap work">
            <view class="sum">
                <view v-for="s in summaryRows" :key="s.key" class="sum__item">
                    <text class="sum__num t-num">{{ s.value }}</text>
                    <text class="sum__label">{{ s.label }}</text>
                </view>
            </view>

            <!-- 新增入口：放内容区而不是 nav-right（message.vue 踩过窄屏压字坑，别挪回导航）；
                 灰度关闭（feature_switch.staff_center_write=0）时整个隐藏 -->
            <view v-if="canWrite" class="action page-pad">
                <view
                    hover-class="is-pressed"
                    class="action__btn"
                    aria-role="button"
                    aria-label="新增作品"
                    @tap="goCreate"
                >
                    <tn-icon name="add" :size="24" class="action__icon" />
                    <text class="action__text">新增作品</text>
                </view>
            </view>
            <view v-else class="gate page-pad">
                <text class="gate__text">作品维护功能灰度中，暂未开放</text>
            </view>

            <view class="tabs page-pad">
                <chip-group
                    :items="TABS.map((t) => ({ label: t.label, value: t.key }))"
                    :model-value="active"
                    @change="switchTab"
                />
            </view>

            <view v-if="phase === 'loading' || phase === 'idle'" class="page-pad">
                <base-skeleton :rows="3" variant="card" />
            </view>

            <base-empty
                v-else-if="phase === 'error'"
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
                v-else-if="phase === 'empty'"
                status="empty"
                empty-text="还没有上传作品"
                desc="点击上方「新增作品」，审核通过后展示在人员主页"
                fixed
            />

            <view v-else class="page-pad list">
                <view class="list__items" :class="{ stagger: staggerIn, 'is-in': staggerIn }">
                    <base-card
                        v-for="(item, index) in list"
                        :key="item.id"
                        compact
                        :style="{ '--i': index }"
                    >
                        <view class="item">
                            <image
                                v-if="item.cover"
                                class="item__cover"
                                :src="item.cover"
                                mode="aspectFill"
                                aria-label="作品封面"
                            />
                            <view v-else class="item__cover item__cover--empty">
                                <!-- tn-icon 的 color prop 不认 var()（静默丢弃），颜色写父级 CSS 靠继承（R13） -->
                                <tn-icon name="image" :size="36" />
                            </view>
                            <view class="item__body">
                                <text class="item__title ellipsis">{{
                                    item.title || '未命名作品'
                                }}</text>
                                <text class="item__meta">
                                    {{ item.type_desc || '图片' }} ·
                                    {{ item.is_show_desc || (item.is_show ? '已展示' : '未展示') }}
                                </text>
                                <status-tag
                                    class="item__tag"
                                    :label="auditLabel(item)"
                                    :tone="auditTone(item.audit_status)"
                                    size="sm"
                                />
                            </view>
                        </view>
                        <text v-if="rejectText(item)" class="reject">{{ rejectText(item) }}</text>
                        <!-- 卡片操作：编辑重新提交审核；删除全状态可用（showModal 二次确认）。
                             灰度关闭时整行隐藏 -->
                        <view v-if="canWrite" class="ops">
                            <view
                                hover-class="is-pressed"
                                class="ops__btn"
                                :aria-label="`编辑作品：${item.title || ''}`"
                                @tap="goEdit(item)"
                            >
                                <text class="ops__text">编辑</text>
                            </view>
                            <view
                                hover-class="is-pressed"
                                class="ops__btn"
                                :aria-label="`删除作品：${item.title || ''}`"
                                @tap="onDelete(item)"
                            >
                                <text class="ops__text ops__text--danger">删除</text>
                            </view>
                        </view>
                    </base-card>
                </view>

                <load-more :status="loadMoreStatus" @retry="loadMore" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：我的作品。
 *
 * P0 已开放作品的增删改（/staff_center/workAdd|workEdit|workDelete），
 * 提交后 audit_status=0 待审；被拒绝的作品可编辑后重新提交。
 * 写入口受 feature_switch.staff_center_write 灰度控制（关闭时隐藏入口与操作行）。
 */
import { computed, onUnmounted, ref, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { StaffWorkAuditStatus, STAFF_WORK_AUDIT_STATUS_NAME_MAP } from '@shared/contracts'
import { getStaffWorkList, staffWorkDelete } from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { usePagedList } from '@/composables/usePagedList'
import { useStaffWriteGate } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'
import { schemeColor } from '@/utils/scheme-color'

const TABS = [
    { key: '', label: '全部' },
    { key: StaffWorkAuditStatus.PENDING, label: '待审核' },
    { key: StaffWorkAuditStatus.PASS, label: '已通过' },
    { key: StaffWorkAuditStatus.REJECT, label: '已拒绝' }
]

const AUDIT_TONE: Record<StaffWorkAuditStatus, 'warning' | 'success' | 'danger'> = {
    [StaffWorkAuditStatus.PENDING]: 'warning',
    [StaffWorkAuditStatus.PASS]: 'success',
    [StaffWorkAuditStatus.REJECT]: 'danger'
}

const { canWrite } = useStaffWriteGate()
const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const active = ref<string | number>('')
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const summary = ref<Record<string, number>>({})
const summaryRows = computed(() => [
    { key: 'total', label: '总数', value: summary.value.total ?? 0 },
    { key: 'visible', label: '展示中', value: summary.value.visible_count ?? 0 },
    { key: 'hidden', label: '已隐藏', value: summary.value.hidden_count ?? 0 },
    { key: 'pending', label: '待审核', value: summary.value.pending_audit_count ?? 0 }
])

const query = computed(() => ({
    audit_status: active.value === '' ? '' : Number(active.value)
}))

const { list, phase, loadMoreStatus, reload, loadMore, refresh } = usePagedList<any>({
    fetcher: async (q) => {
        const res: any = await getStaffWorkList(q as any)
        if (Number((q as any).page) === 1 && res?.summary) summary.value = res.summary
        return res
    },
    query,
    onError: (e) => {
        rawError.value = e
    }
})

/** 审核文案：后端 audit_status_desc 优先，缺省用契约 NAME_MAP */
function auditLabel(item: any) {
    return (
        item.audit_status_desc ||
        (STAFF_WORK_AUDIT_STATUS_NAME_MAP as Record<number, string>)[Number(item.audit_status)] ||
        '待审核'
    )
}

function auditTone(v: number) {
    return (AUDIT_TONE as Record<number, 'success' | 'warning' | 'danger'>)[Number(v)] || 'warning'
}

/** 拒绝原因：优先 audit_remark，兜底 reject_reason（两端字段名历史上不一致） */
function rejectText(item: any) {
    return String(item?.audit_remark || item?.reject_reason || '')
}

function goCreate() {
    if (!guardAllowed.value) return
    navigate({ path: '/packages/pkg-staff/pages/work-edit/work-edit' })
}

function goEdit(item: any) {
    if (!guardAllowed.value) return
    if (!item?.id) {
        toast.warning('作品编号缺失，请刷新后重试')
        return
    }
    navigate({
        path: '/packages/pkg-staff/pages/work-edit/work-edit',
        query: { id: item.id }
    })
}

function onDelete(item: any) {
    if (!guardAllowed.value) return
    if (!item?.id) {
        toast.warning('作品编号缺失，请刷新后重试')
        return
    }
    uni.showModal({
        title: '删除作品',
        content: '删除后不可恢复，确认删除这个作品？',
        confirmText: '删除',
        cancelText: '取消',
        // 删除属不可逆操作，uni.showModal 居中确认弹窗保持原生居中形态
        confirmColor: schemeColor('danger'),
        success: async (r) => {
            if (!r.confirm) return
            haptic('heavy', 'work-delete')
            try {
                await staffWorkDelete({ id: Number(item.id) })
                toast.success('已删除')
                // refresh 保留旧列表直到新数据回来；summary 在第 1 页响应里顺带更新
                await refresh()
            } catch (e: any) {
                // 灰度关闭 / 内容安全命中 / 其他业务拒绝：后端 msg 直接展示（写封装 silent:true）
                toast.error(String(e?.message || '删除失败，请稍后重试'))
            }
        }
    })
}

function switchTab(key: string | number) {
    if (!guardAllowed.value) return
    active.value = key
    reload()
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

/* ---- 列表错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger(): void {
    if (staggerIn.value || staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        staggerIn.value = true
    }, 40)
}

watch(
    () => list.value.length,
    (len) => {
        if (len) playStagger()
    }
)

onUnmounted(() => {
    if (staggerTimer) clearTimeout(staggerTimer)
})

let hasLoaded = false

async function init(forceGuard = false) {
    if (!(await verify(forceGuard))) return
    await reload()
    hasLoaded = true
}

onLoad(() => void init())

onShow(() => {
    if (hasLoaded && guardAllowed.value) {
        void refresh()
    }
})

function onReachBottomLoad() {
    if (guardAllowed.value) loadMore()
}

async function onRefresh(done: () => void) {
    try {
        if (await verify(true)) await refresh()
    } finally {
        done()
    }
}
</script>

<style lang="scss" scoped>
.work {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.sum {
    display: flex;
    margin: var(--sp-2) var(--sp-4) 0;
    /* 汇总条按卡片表面渲染：浅色暖影分层、暗色由 --c-card-border 描边 */
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    border-radius: var(--r-md);
    box-shadow: var(--shadow-card);
    overflow: hidden;

    &__item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);
        padding: var(--sp-3) 0;

        & + & {
            border-left: 1rpx solid var(--c-line);
        }
    }

    &__num {
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        white-space: nowrap;
    }
}

.tabs {
    padding-top: var(--sp-2);
    padding-bottom: var(--sp-2);
}

/* 新增入口行：右对齐文字按钮 */
.action {
    display: flex;
    justify-content: flex-end;
    padding-top: var(--sp-1);

    &__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);
        min-height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
        transition: transform var(--dur-press) var(--ease-out);

        &.is-pressed {
            transform: scale(0.96);
        }
    }

    &__icon {
        color: var(--c-brand-text);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

/* 灰度关闭时的占位说明 */
.gate {
    padding-top: var(--sp-1);

    &__text {
        display: block;
        text-align: right;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.list__items {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.item {
    display: flex;
    gap: var(--sp-3);

    &__cover {
        width: 144rpx;
        height: 108rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
        flex-shrink: 0;

        &--empty {
            display: flex;
            align-items: center;
            justify-content: center;
            /* tn-icon 不认 var() color prop（R13），图标颜色由父级继承 */
            color: var(--c-ink-3);
        }
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__tag {
        align-self: flex-start;
    }
}

/* 拒绝原因 */
.reject {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-danger);
}

/* 卡片操作行 */
.ops {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
    padding-top: var(--sp-2);
    border-top: 1rpx solid var(--c-line);

    &__btn {
        /* 相邻小按钮不叠加 .hit-88（伪元素会互相覆盖），直接把盒高做到 88rpx */
        min-height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform var(--dur-press) var(--ease-out);

        &.is-pressed {
            transform: scale(0.96);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);

        &--danger {
            color: var(--c-danger);
        }
    }
}

/* v10 §3.13：减弱动效下按压不缩放 */
@media (prefers-reduced-motion: reduce) {
    .action__btn,
    .ops__btn {
        transition: none;
    }

    .action__btn.is-pressed,
    .ops__btn.is-pressed {
        transform: none;
    }
}
</style>
