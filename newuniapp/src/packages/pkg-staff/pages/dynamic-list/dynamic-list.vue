<template>
    <page-shell title="我的动态" bg="page" :show-back="true">
        <view class="page-wrap dyn">
            <view class="sum">
                <view v-for="s in summaryRows" :key="s.key" class="sum__item">
                    <text class="sum__num">{{ s.value }}</text>
                    <text class="sum__label">{{ s.label }}</text>
                </view>
            </view>

            <!-- 发布入口：放内容区而不是 nav-right（message.vue 踩过窄屏压字坑，别挪回导航）；
                 灰度关闭（feature_switch.staff_center_write=0）时整个隐藏 -->
            <view v-if="canWrite" class="action page-pad">
                <view
                    hover-class="is-pressed"
                    class="action__btn"
                    aria-role="button"
                    aria-label="发布动态"
                    @tap="goPublish"
                >
                    <tn-icon name="add" :size="24" class="action__icon" />
                    <text class="action__text">发布动态</text>
                </view>
            </view>
            <view v-else class="gate page-pad">
                <text class="gate__text">内容发布功能灰度中，暂未开放</text>
            </view>

            <view class="tabs page-pad">
                <chip-group
                    :items="TABS.map((t) => ({ label: t.label, value: t.key }))"
                    :model-value="active"
                    @change="switchTab"
                />
            </view>

            <view v-if="phase === 'loading' || phase === 'idle'" class="page-pad">
                <base-skeleton :rows="3" />
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
                empty-text="还没有发布动态"
                desc="点击上方「发布动态」，审核通过后展示在小程序动态页"
                fixed
            />

            <view v-else class="page-pad list">
                <base-card v-for="item in list" :key="item.id" compact>
                    <view class="head">
                        <text class="head__type">{{ item.dynamic_type_desc || '动态' }}</text>
                        <status-tag
                            :label="statusLabel(item)"
                            :tone="statusTone(item.status)"
                            size="sm"
                        />
                    </view>
                    <text class="content ellipsis-2">{{ item.content || '（无正文）' }}</text>
                    <view class="foot">
                        <text class="foot__time">{{ item.create_time || '' }}</text>
                        <text class="foot__hint">{{ item.status_hint || '' }}</text>
                    </view>
                    <text v-if="rejectText(item)" class="reason">{{ rejectText(item) }}</text>
                    <!-- 卡片操作：待审/已发布可编辑；删除全状态可用（showModal 二次确认）。
                         灰度关闭时整行隐藏 -->
                    <view v-if="canWrite" class="ops">
                        <view
                            v-if="canEdit(item)"
                            hover-class="is-pressed"
                            class="ops__btn"
                            :aria-label="`编辑动态：${item.title || ''}`"
                            @tap="goEdit(item)"
                        >
                            <text class="ops__text">编辑</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            class="ops__btn"
                            :aria-label="`删除动态：${item.title || ''}`"
                            @tap="onDelete(item)"
                        >
                            <text class="ops__text ops__text--danger">删除</text>
                        </view>
                    </view>
                </base-card>

                <load-more :status="loadMoreStatus" @retry="loadMore" />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员：我的动态。
 *
 * P0 已开放动态的发布/编辑/删除（/staff_center/dynamicAdd|dynamicEdit|dynamicDelete），
 * 内容均走审核流：提交后为待审核态，删除需二次确认。
 * 写入口受 feature_switch.staff_center_write 灰度控制（关闭时隐藏入口与操作行）。
 * 用户端「浏览」动态的页面在 pkg-catalog/case-list；这里管理的是自己发布的。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom } from '@dcloudio/uni-app'
import { DynamicStatus, DYNAMIC_STATUS_NAME_MAP } from '@shared/contracts'
import { getStaffDynamicList, staffDynamicDelete } from '@/api/staff-center'
import { usePagedList } from '@/composables/usePagedList'
import { useStaffWriteGate } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'

const TABS = [
    { key: '', label: '全部' },
    { key: DynamicStatus.PENDING, label: '待审核' },
    { key: DynamicStatus.PUBLISHED, label: '已发布' },
    { key: DynamicStatus.OFFLINE, label: '已下架' },
    { key: DynamicStatus.REJECTED, label: '已拒绝' }
]

const STATUS_TONE: Record<DynamicStatus, 'warning' | 'success' | 'neutral' | 'danger'> = {
    [DynamicStatus.PENDING]: 'warning',
    [DynamicStatus.PUBLISHED]: 'success',
    [DynamicStatus.OFFLINE]: 'neutral',
    [DynamicStatus.REJECTED]: 'danger'
}

/** 可编辑状态：待审（改后重新排队）与已发布（改后重新审核）；已拒绝回编辑页重提也常见，一并放开 */
const EDITABLE_STATUS: ReadonlySet<DynamicStatus> = new Set([
    DynamicStatus.PENDING,
    DynamicStatus.PUBLISHED
])

const { canWrite } = useStaffWriteGate()

const active = ref<string | number>('')
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const summary = ref<Record<string, number>>({})
const summaryRows = computed(() => [
    { key: 'total', label: '总数', value: summary.value.total ?? 0 },
    { key: 'published', label: '已发布', value: summary.value.published_count ?? 0 },
    { key: 'pending', label: '待审核', value: summary.value.pending_count ?? 0 },
    { key: 'offline', label: '已下架', value: summary.value.offline_count ?? 0 }
])

const query = computed(() => ({
    status: active.value === '' ? '' : Number(active.value)
}))

const { list, phase, loadMoreStatus, reload, loadMore, refresh } = usePagedList<any>({
    fetcher: async (q) => {
        const res: any = await getStaffDynamicList(q as any)
        if (Number((q as any).page) === 1 && res?.summary) summary.value = res.summary
        return res
    },
    query,
    onError: (e) => {
        rawError.value = e
    }
})

function statusTone(v: number) {
    return (
        (STATUS_TONE as Record<number, 'success' | 'warning' | 'danger' | 'neutral'>)[Number(v)] ||
        'warning'
    )
}

/** 状态文案：后端 status_desc 优先，缺省用契约 NAME_MAP（新状态后端先行时前端不显示 undefined） */
function statusLabel(item: any) {
    return (
        item?.status_desc ||
        (DYNAMIC_STATUS_NAME_MAP as Record<number, string>)[Number(item?.status)] ||
        '待审核'
    )
}

/** 拒绝原因：优先 status_reason，兜底 audit_remark（两端字段名历史上不一致） */
function rejectText(item: any) {
    return String(item?.status_reason || item?.audit_remark || '')
}

function canEdit(item: any) {
    return EDITABLE_STATUS.has(Number(item?.status))
}

function goPublish() {
    navigate({ path: '/packages/pkg-staff/pages/dynamic-publish/dynamic-publish' })
}

function goEdit(item: any) {
    if (!item?.id) {
        toast.warning('动态编号缺失，请刷新后重试')
        return
    }
    navigate({
        path: '/packages/pkg-staff/pages/dynamic-publish/dynamic-publish',
        query: { id: item.id }
    })
}

function onDelete(item: any) {
    if (!item?.id) {
        toast.warning('动态编号缺失，请刷新后重试')
        return
    }
    uni.showModal({
        title: '删除动态',
        content: '删除后不可恢复，确认删除这条动态？',
        confirmText: '删除',
        cancelText: '取消',
        confirmColor: '#D8584A',
        success: async (r) => {
            if (!r.confirm) return
            try {
                await staffDynamicDelete({ id: Number(item.id) })
                toast.success('已删除')
                // refresh 保留旧列表直到新数据回来，不闪空屏；summary 在第 1 页响应里顺带更新
                await refresh()
            } catch (e: any) {
                // 灰度关闭 / 内容安全命中 / 其他业务拒绝：后端 msg 直接展示（写封装 silent:true）
                toast.error(String(e?.message || '删除失败，请稍后重试'))
            }
        }
    })
}

function switchTab(key: string | number) {
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

onLoad(() => reload())

onReachBottom(() => loadMore())

onPullDownRefresh(async () => {
    try {
        await refresh()
    } finally {
        uni.stopPullDownRefresh()
    }
})
</script>

<style lang="scss" scoped>
.dyn {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.sum {
    display: flex;
    margin: var(--sp-2) var(--sp-4) 0;

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

/* 发布入口行：右对齐文字按钮，88rpx 热区由 padding 保证 */
.action {
    display: flex;
    justify-content: flex-end;
    padding-top: var(--sp-1);

    &__btn {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-brand-bg);
    }

    &__icon {
        color: var(--c-brand-text);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }
}

/* 灰度关闭时的占位说明（入口隐藏，但让用户知道这块为什么没了） */
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

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);

    &__type {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.content {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-md);
    line-height: var(--lh-body);
    color: var(--c-ink);
}

.foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    margin-top: var(--sp-2);

    &__time {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.reason {
    display: block;
    margin-top: var(--sp-2);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-danger);
}

/* 卡片操作行：编辑在左，删除靠右；文字按钮热区由 min-height + padding 保证 */
.ops {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
    padding-top: var(--sp-2);
    border-top: 1rpx solid var(--c-line);

    &__btn {
        min-height: 56rpx;
        padding: var(--sp-1) var(--sp-3);
        border-radius: var(--r-pill);
        display: flex;
        align-items: center;
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);

        &--danger {
            color: var(--c-danger);
        }
    }
}
</style>
