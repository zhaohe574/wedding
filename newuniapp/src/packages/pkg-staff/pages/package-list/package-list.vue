<template>
    <page-shell
        title="我的套餐"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        :bottom-bar="guardAllowed && canWrite"
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

        <view v-else class="page-wrap pkg">
            <view v-if="!canWrite" class="gate page-pad">
                <text class="gate__text">套餐维护功能灰度中，暂未开放</text>
            </view>

            <view class="filter page-pad">
                <chip-group :items="tabItems" :model-value="active" @change="switchTab" />
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
                empty-text="还没有配置套餐"
                :desc="
                    canWrite
                        ? '点击下方「新增套餐」，上架后客户可在主页预约选择'
                        : '套餐上架后客户在下单页即可选择'
                "
                fixed
            />

            <template v-else>
                <base-empty
                    v-if="!filteredList.length"
                    status="empty"
                    empty-text="当前筛选下暂无套餐"
                />

                <view v-else class="page-pad list">
                    <view class="list__items" :class="{ stagger: staggerIn, 'is-in': staggerIn }">
                        <base-card
                            v-for="(item, index) in filteredList"
                            :key="item.id"
                            compact
                            clickable
                            :style="{ '--i': index }"
                            :aria-label="`编辑套餐：${item.name || '未命名套餐'}`"
                            @tap="goEdit(item)"
                        >
                            <view class="pcard">
                                <view class="pcard__cover">
                                    <image
                                        v-if="item.image"
                                        class="pcard__img"
                                        :src="item.image"
                                        mode="aspectFill"
                                        aria-label="套餐封面"
                                    />
                                    <view v-else class="pcard__ph" aria-hidden="true">
                                        <tn-icon name="image" :size="40" class="pcard__ph-icon" />
                                    </view>
                                </view>

                                <view class="pcard__body">
                                    <view class="pcard__head">
                                        <text class="pcard__name ellipsis">{{
                                            item.name || '未命名套餐'
                                        }}</text>
                                        <status-tag
                                            v-if="Number(item.is_recommend) === 1"
                                            label="推荐"
                                            tone="primary"
                                            size="sm"
                                            :dot="false"
                                        />
                                        <view
                                            v-if="canWrite"
                                            class="sw hit-88"
                                            :class="{
                                                'is-on': Number(item.is_show) === 1,
                                                'is-busy': togglingId === Number(item.id)
                                            }"
                                            hover-class="is-pressed"
                                            aria-role="switch"
                                            :aria-checked="Number(item.is_show) === 1"
                                            :aria-label="`${
                                                Number(item.is_show) === 1 ? '下架' : '上架'
                                            }套餐：${item.name || ''}`"
                                            @tap.stop="onToggleShow(item)"
                                        >
                                            <view class="sw__knob" />
                                        </view>
                                        <status-tag
                                            v-else
                                            :label="
                                                Number(item.is_show) === 1 ? '已上架' : '已停用'
                                            "
                                            :tone="
                                                Number(item.is_show) === 1 ? 'success' : 'neutral'
                                            "
                                            size="sm"
                                            :dot="false"
                                        />
                                    </view>

                                    <view class="pcard__price">
                                        <price
                                            size="md"
                                            :content="item.price"
                                            color="var(--c-brand-text)"
                                        />
                                        <text
                                            v-if="Number(item.original_price) > 0"
                                            class="pcard__origin t-num"
                                        >
                                            ¥{{ item.original_price }}
                                        </text>
                                    </view>

                                    <view class="pcard__meta">
                                        <text
                                            v-if="Number(item.duration) > 0"
                                            class="meta t-num"
                                        >
                                            {{ item.duration }} 小时
                                        </text>
                                        <text v-else class="meta">不限时长</text>
                                        <text v-if="addonCount(item)" class="meta t-num">
                                            {{ addonCount(item) }} 项加购
                                        </text>
                                        <text
                                            v-if="regionPriceCount(item)"
                                            class="meta t-num"
                                        >
                                            {{ regionPriceCount(item) }} 条地区价
                                        </text>
                                        <text class="meta t-num"
                                            >排序 {{ Number(item.sort || 0) }}</text
                                        >
                                        <status-tag
                                            v-if="regionPriceCount(item) === 0"
                                            label="未配地区价"
                                            tone="warning"
                                            size="sm"
                                            :dot="false"
                                        />
                                    </view>
                                </view>
                            </view>

                            <view v-if="canWrite" class="ops">
                                <view
                                    hover-class="is-pressed"
                                    class="ops__btn"
                                    aria-role="button"
                                    :aria-label="`编辑套餐：${item.name || ''}`"
                                    @tap.stop="goEdit(item)"
                                >
                                    <text class="ops__text">编辑</text>
                                </view>
                                <view
                                    hover-class="is-pressed"
                                    class="ops__btn"
                                    aria-role="button"
                                    :aria-label="`删除套餐：${item.name || ''}`"
                                    @tap.stop="onDelete(item)"
                                >
                                    <text class="ops__text ops__text--danger">删除</text>
                                </view>
                            </view>
                        </base-card>
                    </view>

                    <load-more :status="loadMoreStatus" @retry="loadMore" />
                </view>
            </template>
        </view>

        <template #bottom>
            <bottom-bar v-if="guardAllowed && canWrite">
                <base-button text="新增套餐" variant="ink" size="lg" @click="goCreate" />
            </bottom-bar>
            <app-toast />
        </template>
    </page-shell>
</template>

<script setup lang="ts">
/** 服务人员：我的套餐（支持增删改与快捷上下架）。 */
import { computed, onUnmounted, ref, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    getStaffPackageList,
    staffPackageRemove,
    staffPackageUpdate,
    type StaffPackageListItem
} from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { usePagedList } from '@/composables/usePagedList'
import { useStaffWriteGate } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'
import { schemeColor } from '@/utils/scheme-color'

const { canWrite } = useStaffWriteGate()
const { guardPhase, guardReason, guardError, guardAllowed, verify, onGuardRetry, onGuardAction } =
    useStaffGuard(() => init(true))

/** 筛选与计数都在已加载列表上做：一次取回（最多 100 条）后本地过滤，三个 tab 的数量才一致 */
const active = ref<string | number>('')
const togglingId = ref(0)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const { list, phase, loadMoreStatus, reload, loadMore, refresh } =
    usePagedList<StaffPackageListItem>({
        fetcher: (q) => getStaffPackageList(q as any),
        pageSize: 100,
        onError: (e) => {
            rawError.value = e
        }
    })

const counts = computed(() => {
    let show = 0
    for (const item of list.value) {
        if (Number(item.is_show) === 1) show += 1
    }
    return { all: list.value.length, show, hidden: list.value.length - show }
})

const tabItems = computed(() => {
    // 列表未加载完（或本就为空）时不显示 0，避免首屏计数闪烁
    if (!list.value.length) {
        return [
            { label: '全部', value: '' },
            { label: '上架中', value: 1 },
            { label: '已停用', value: 0 }
        ]
    }
    return [
        { label: `全部 ${counts.value.all}`, value: '' },
        { label: `上架中 ${counts.value.show}`, value: 1 },
        { label: `已停用 ${counts.value.hidden}`, value: 0 }
    ]
})

const filteredList = computed(() => {
    if (active.value === '') return list.value
    return list.value.filter((item) => Number(item.is_show) === Number(active.value))
})

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

function addonCount(item: StaffPackageListItem): number {
    return Array.isArray(item?.addon_ids) ? item.addon_ids.length : 0
}

function regionPriceCount(item: StaffPackageListItem): number {
    return Array.isArray(item?.region_prices) ? item.region_prices.length : 0
}

function goCreate() {
    if (!guardAllowed.value) return
    navigate({ path: '/packages/pkg-staff/pages/package-edit/package-edit' })
}

function goEdit(item: StaffPackageListItem) {
    if (!guardAllowed.value) return
    if (!item?.id) {
        toast.warning('套餐编号缺失，请刷新后重试')
        return
    }
    navigate({
        path: '/packages/pkg-staff/pages/package-edit/package-edit',
        query: { id: item.id }
    })
}

/**
 * 只提交上下架开关需要的字段。
 * 后端 packageUpdate 对未传字段保留原值：不传 region_prices / addon_ids，
 * 避免一次开关把地区价与关联附加项清空。
 */
async function onToggleShow(item: StaffPackageListItem) {
    if (!guardAllowed.value || togglingId.value > 0) return
    if (!item?.id) return
    haptic('medium', 'pkg-list-toggle')
    const nextShow = Number(item.is_show) === 1 ? 0 : 1
    togglingId.value = Number(item.id)
    try {
        await staffPackageUpdate({
            package_id: Number(item.id),
            name: String(item.name || '未命名套餐'),
            price: Number(item.price || 0),
            original_price: Number(item.original_price || 0),
            duration: Number(item.duration || 0),
            image: String(item.image || ''),
            description: String(item.description || ''),
            sort: Number(item.sort || 0),
            is_show: nextShow as 0 | 1,
            is_recommend: Number(item.is_recommend || 0) === 1 ? 1 : 0
        })
        toast.success(nextShow === 1 ? '已上架' : '已下架')
        await refresh()
    } catch (e: any) {
        toast.error(String(e?.message || '操作失败，请稍后重试'))
    } finally {
        togglingId.value = 0
    }
}

function onDelete(item: StaffPackageListItem) {
    if (!guardAllowed.value) return
    if (!item?.id) {
        toast.warning('套餐编号缺失，请刷新后重试')
        return
    }
    uni.showModal({
        title: '删除套餐',
        content: '删除后不可恢复，确认删除这个套餐？',
        confirmText: '删除',
        cancelText: '取消',
        // 删除属不可逆操作，uni.showModal 居中确认弹窗保持原生居中形态
        confirmColor: schemeColor('danger'),
        success: async (r) => {
            if (!r.confirm) return
            haptic('heavy', 'pkg-list-delete')
            try {
                await staffPackageRemove({ package_id: Number(item.id) })
                toast.success('已删除')
                await refresh()
            } catch (e: any) {
                toast.error(String(e?.message || '删除失败，请稍后重试'))
            }
        }
    })
}

function switchTab(key: string | number) {
    if (!guardAllowed.value) return
    active.value = key
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
.pkg {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.gate {
    padding-top: var(--sp-2);

    &__text {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.filter {
    padding-top: var(--sp-2);
    padding-bottom: var(--sp-2);
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

.pcard {
    display: flex;
    gap: var(--sp-3);

    &__cover {
        width: 152rpx;
        height: 152rpx;
        border-radius: var(--r-sm);
        overflow: hidden;
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-card-border);
        flex-shrink: 0;
    }

    &__img {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__ph {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__ph-icon {
        color: var(--c-ink-3);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__price {
        display: flex;
        align-items: baseline;
        gap: var(--sp-2);
        margin-top: var(--sp-1);
    }

    &__origin {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-decoration: line-through;
        font-variant-numeric: tabular-nums;
    }

    &__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: var(--sp-1);
        margin-top: var(--sp-2);
    }
}

.meta {
    padding: 2rpx 12rpx;
    border-radius: var(--r-xs);
    background: var(--c-surface-2);
    font-size: var(--fs-xs);
    line-height: var(--lh-tight);
    color: var(--c-ink-3);
    font-variant-numeric: tabular-nums;
}

.sw {
    flex-shrink: 0;
    width: 76rpx;
    height: 44rpx;
    padding: 4rpx;
    box-sizing: border-box;
    border-radius: var(--r-pill);
    background: var(--c-control-border);
    display: flex;
    align-items: center;
    transition:
        background var(--dur-fast) var(--ease-out),
        transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.94);
    }

    &__knob {
        width: 36rpx;
        height: 36rpx;
        border-radius: var(--r-pill);
        /* 滑块跟随表面色：浅色暖白、暗色深墨，两态都与轨道拉开对比 */
        background: var(--c-surface);
        transition: transform var(--dur-fast) var(--ease-out);
    }

    &.is-on {
        background: var(--c-success);

        .sw__knob {
            transform: translateX(32rpx);
        }
    }

    &.is-busy {
        opacity: 0.55;
    }
}

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

/* v10 §3.13：减弱动效下按压不缩放、开关不位移过渡 */
@media (prefers-reduced-motion: reduce) {
    .sw,
    .sw__knob,
    .ops__btn {
        transition: none;
    }

    .sw.is-pressed,
    .ops__btn.is-pressed {
        transform: none;
    }
}
</style>
