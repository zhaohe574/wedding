<template>
    <page-shell title="我的证书" bg="page" :show-back="true">
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap cert">
            <!-- 汇总：审核口径一眼看清，避免逐条数 -->
            <view class="sum">
                <view v-for="s in summaryRows" :key="s.key" class="sum__item">
                    <text class="sum__num">{{ s.value }}</text>
                    <text class="sum__label">{{ s.label }}</text>
                </view>
            </view>

            <!-- 上传入口：放内容区而不是 nav-right（message.vue 踩过窄屏压字坑，别挪回导航）；
                 灰度关闭（feature_switch.staff_center_write=0）时整个隐藏 -->
            <view v-if="canWrite" class="action page-pad">
                <view
                    hover-class="is-pressed"
                    class="action__btn"
                    aria-role="button"
                    aria-label="上传证书"
                    @tap="goCreate"
                >
                    <tn-icon name="add" :size="24" class="action__icon" />
                    <text class="action__text">上传证书</text>
                </view>
            </view>
            <view v-else class="gate page-pad">
                <text class="gate__text">证书维护功能灰度中，暂未开放</text>
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
                empty-text="还没有上传证书"
                desc="点击上方「上传证书」，审核通过后展示在人员主页"
                fixed
            />

            <view v-else class="page-pad list">
                <base-card v-for="item in list" :key="item.id" compact>
                    <view class="head">
                        <text class="head__name ellipsis">{{ item.name || '未命名证书' }}</text>
                        <status-tag
                            :label="item.verify_status_desc || verifyLabel(item.verify_status)"
                            :tone="verifyTone(item.verify_status)"
                            size="sm"
                        />
                    </view>
                    <text v-if="item.type" class="meta">{{ item.type }}</text>
                    <view class="foot">
                        <text class="foot__sn ellipsis">{{ item.sn || '暂无编号' }}</text>
                        <text v-if="item.is_expired" class="foot__expired">已过期</text>
                    </view>
                    <text v-if="rejectText(item)" class="reject">
                        驳回原因：{{ rejectText(item) }}
                    </text>
                    <!-- 卡片操作：编辑重新提交审核；删除全状态可用（showModal 二次确认）。
                         编辑回填走 query 传参（契约无 certificateDetail 端点，不私造接口）。
                         灰度关闭时整行隐藏 -->
                    <view v-if="canWrite" class="ops">
                        <view
                            hover-class="is-pressed"
                            class="ops__btn"
                            :aria-label="`编辑证书：${item.name || ''}`"
                            @tap="goEdit(item)"
                        >
                            <text class="ops__text">编辑</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            class="ops__btn"
                            :aria-label="`删除证书：${item.name || ''}`"
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
 * 服务人员：我的证书。
 *
 * P0 已开放证书的增删改（/staff_center/certificateAdd|certificateEdit|certificateDelete），
 * 提交后 verify_status=0 待审；被拒绝的证书可编辑后重新提交。
 * 写入口受 feature_switch.staff_center_write 灰度控制（关闭时隐藏入口与操作行）。
 * 编辑页回填由本页跳转经 query 传参（契约无 certificateDetail 端点，不私造接口）。
 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import {
    StaffCertificateVerifyStatus,
    STAFF_CERTIFICATE_VERIFY_STATUS_NAME_MAP
} from '@shared/contracts'
import { getStaffCertificateList, staffCertificateDelete } from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { usePagedList } from '@/composables/usePagedList'
import { useStaffWriteGate } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'

const TABS = [
    { key: '', label: '全部' },
    { key: StaffCertificateVerifyStatus.PENDING, label: '待审核' },
    { key: StaffCertificateVerifyStatus.PASS, label: '已通过' },
    { key: StaffCertificateVerifyStatus.REJECT, label: '已拒绝' }
]

const VERIFY_TONE: Record<
    StaffCertificateVerifyStatus,
    'warning' | 'success' | 'danger'
> = {
    [StaffCertificateVerifyStatus.PENDING]: 'warning',
    [StaffCertificateVerifyStatus.PASS]: 'success',
    [StaffCertificateVerifyStatus.REJECT]: 'danger'
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

/** 汇总只在第 1 页响应里给；翻页不重算，避免数字跳动 */
const summary = ref<Record<string, number>>({})
const summaryRows = computed(() => [
    { key: 'total', label: '总数', value: summary.value.total ?? 0 },
    { key: 'pending', label: '待审核', value: summary.value.pending_count ?? 0 },
    { key: 'approved', label: '已通过', value: summary.value.approved_count ?? 0 },
    { key: 'rejected', label: '已拒绝', value: summary.value.rejected_count ?? 0 }
])

const query = computed(() => ({
    verify_status: active.value === '' ? '' : Number(active.value)
}))

const { list, phase, loadMoreStatus, reload, loadMore, refresh } = usePagedList<any>({
    // summary 挂在分页对象上，首页顺带取；列表本身完全交给 usePagedList 管，避免重复请求
    fetcher: async (q) => {
        const res: any = await getStaffCertificateList(q as any)
        if (Number((q as any).page) === 1 && res?.summary) summary.value = res.summary
        return res
    },
    query,
    onError: (e) => {
        rawError.value = e
    }
})

/** 核验文案：后端 verify_status_desc 优先，缺省用契约 NAME_MAP */
function verifyLabel(v: number) {
    return (
        (STAFF_CERTIFICATE_VERIFY_STATUS_NAME_MAP as Record<number, string>)[Number(v)] || '待审核'
    )
}

function verifyTone(v: number) {
    return (VERIFY_TONE as Record<number, 'success' | 'warning' | 'danger'>)[Number(v)] || 'warning'
}

/** 驳回原因：优先 reject_reason，兜底 audit_remark（两端字段名历史上不一致） */
function rejectText(item: any) {
    return String(item?.reject_reason || item?.audit_remark || '')
}

function goCreate() {
    if (!guardAllowed.value) return
    navigate({ path: '/packages/pkg-staff/pages/certificate-edit/certificate-edit' })
}

/**
 * 编辑：证书字段全部经 query 传给编辑页。
 * 编码只做一层 —— navigate 的 buildQuery 会统一 encodeURIComponent，
 * 这里再手动编一次就是双重编码（mp-weixin 的 onLoad 不自动 decode，编辑页会拿到 %E4…）。
 * 编辑页用 safeDecode 解一层，与 buildQuery 正好配对。
 */
function goEdit(item: any) {
    if (!guardAllowed.value) return
    if (!item?.id) {
        toast.warning('证书编号缺失，请刷新后重试')
        return
    }
    navigate({
        path: '/packages/pkg-staff/pages/certificate-edit/certificate-edit',
        query: {
            id: item.id,
            name: String(item.name || ''),
            type: String(item.type || ''),
            sn: String(item.sn || ''),
            image: String(item.image || ''),
            issue_org: String(item.issue_org || ''),
            issue_date: String(item.issue_date || '').slice(0, 10),
            expire_date: String(item.expire_date || '').slice(0, 10)
        }
    })
}

function onDelete(item: any) {
    if (!guardAllowed.value) return
    if (!item?.id) {
        toast.warning('证书编号缺失，请刷新后重试')
        return
    }
    uni.showModal({
        title: '删除证书',
        content: '删除后不可恢复，确认删除这个证书？',
        confirmText: '删除',
        cancelText: '取消',
        confirmColor: '#D8584A',
        success: async (r) => {
            if (!r.confirm) return
            try {
                await staffCertificateDelete({ id: Number(item.id) })
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

onReachBottom(() => {
    if (guardAllowed.value) loadMore()
})

onPullDownRefresh(async () => {
    try {
        if (await verify(true)) await refresh()
    } finally {
        uni.stopPullDownRefresh()
    }
})
</script>

<style lang="scss" scoped>
.cert {
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

/* 上传入口行：右对齐文字按钮 */
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

.head {
    display: flex;
    align-items: center;
    gap: var(--sp-2);

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.meta {
    margin-top: var(--sp-1);
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.foot {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-top: var(--sp-2);

    &__sn {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__expired {
        font-size: var(--fs-xs);
        color: var(--c-danger);
    }
}

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
