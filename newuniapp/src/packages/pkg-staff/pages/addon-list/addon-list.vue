<template>
    <page-shell
        title="我的附加项"
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

        <view v-else class="page-wrap addon">
            <view v-if="!canWrite" class="gate page-pad">
                <text class="gate__text">附加项维护功能灰度中，暂未开放</text>
            </view>

            <view class="filter page-pad">
                <chip-group :items="tabItems" :model-value="active" @change="switchTab" />
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
                empty-text="还没有附加项"
                :desc="
                    canWrite
                        ? '点击下方「新增附加项」，可用于套餐加购'
                        : '附加项是套餐之外可单独加购的服务'
                "
                fixed
            />

            <template v-else>
                <base-empty
                    v-if="!filteredList.length"
                    status="empty"
                    empty-text="当前筛选下暂无附加项"
                />

                <view v-else class="page-pad list">
                    <base-card
                        v-for="item in filteredList"
                        :key="item.id"
                        compact
                        clickable
                        :aria-label="`编辑附加项：${item.name || '未命名附加项'}`"
                        @tap="goEdit(item)"
                    >
                        <view class="acard">
                            <view class="acard__cover">
                                <image
                                    v-if="item.image"
                                    class="acard__img"
                                    :src="item.image"
                                    mode="aspectFill"
                                    aria-label="附加项封面"
                                />
                                <view v-else class="acard__ph" aria-hidden="true">
                                    <tn-icon name="image" :size="40" class="acard__ph-icon" />
                                </view>
                            </view>

                            <view class="acard__body">
                                <view class="acard__head">
                                    <text class="acard__name ellipsis">{{
                                        item.name || '未命名附加项'
                                    }}</text>
                                    <view
                                        v-if="canWrite"
                                        class="sw"
                                        :class="{
                                            'is-on': Number(item.is_show) === 1,
                                            'is-busy': togglingId === Number(item.id)
                                        }"
                                        hover-class="is-pressed"
                                        aria-role="switch"
                                        :aria-checked="Number(item.is_show) === 1"
                                        :aria-label="`${
                                            Number(item.is_show) === 1 ? '下架' : '上架'
                                        }附加项：${item.name || ''}`"
                                        @tap.stop="onToggleShow(item)"
                                    >
                                        <view class="sw__knob" />
                                    </view>
                                    <status-tag
                                        v-else
                                        :label="Number(item.is_show) === 1 ? '已上架' : '已停用'"
                                        :tone="Number(item.is_show) === 1 ? 'success' : 'neutral'"
                                        size="sm"
                                        :dot="false"
                                    />
                                </view>

                                <view class="acard__price">
                                    <price
                                        size="md"
                                        :content="item.price"
                                        color="var(--c-brand-text)"
                                    />
                                    <text
                                        v-if="Number(item.original_price) > 0"
                                        class="acard__origin"
                                    >
                                        ¥{{ item.original_price }}
                                    </text>
                                </view>

                                <text v-if="item.description" class="acard__desc ellipsis">
                                    {{ item.description }}
                                </text>

                                <view class="acard__meta">
                                    <text v-if="attachedCount(item) > 0" class="meta">
                                        已关联 {{ attachedCount(item) }} 个套餐
                                    </text>
                                    <status-tag
                                        v-else
                                        label="未关联套餐"
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
                                :aria-label="`编辑附加项：${item.name || ''}`"
                                @tap.stop="goEdit(item)"
                            >
                                <text class="ops__text">编辑</text>
                            </view>
                            <view
                                hover-class="is-pressed"
                                class="ops__btn"
                                aria-role="button"
                                :aria-label="`删除附加项：${item.name || ''}`"
                                @tap.stop="onDelete(item)"
                            >
                                <text class="ops__text ops__text--danger">删除</text>
                            </view>
                        </view>
                    </base-card>

                    <load-more :status="loadMoreStatus" @retry="loadMore" />
                </view>
            </template>
        </view>

        <template #bottom>
            <bottom-bar v-if="guardAllowed && canWrite">
                <base-button text="新增附加项" variant="ink" size="lg" @click="goCreate" />
            </bottom-bar>
            <app-toast />
        </template>
    </page-shell>
</template>

<script setup lang="ts">
/** 服务人员：我的附加项（支持增删改、快捷上下架与关联套餐透出）。 */
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    getStaffAddonList,
    staffAddonRemove,
    staffAddonUpdate,
    type StaffAddonListItem
} from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { usePagedList } from '@/composables/usePagedList'
import { useStaffWriteGate } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'

const { canWrite } = useStaffWriteGate()
const { guardPhase, guardReason, guardError, guardAllowed, verify, onGuardRetry, onGuardAction } =
    useStaffGuard(() => init(true))

/** 筛选与计数都在已加载列表上做：一次取回（最多 100 条）后本地过滤，三个 tab 的数量才一致 */
const active = ref<string | number>('')
const togglingId = ref(0)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const { list, phase, loadMoreStatus, reload, loadMore, refresh } = usePagedList<StaffAddonListItem>(
    {
        fetcher: (q) => getStaffAddonList(q as any),
        pageSize: 100,
        onError: (e) => {
            rawError.value = e
        }
    }
)

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

let hasLoaded = false

/** package_count / package_ids（数组或逗号串）三种形态都认 */
function attachedCount(item: StaffAddonListItem): number {
    if (typeof item?.package_count === 'number') return item.package_count
    const v = item?.package_ids
    if (Array.isArray(v)) return v.length
    if (typeof v === 'string' && v.trim()) return v.split(',').filter(Boolean).length
    return 0
}

function goCreate() {
    if (!guardAllowed.value) return
    navigate({ path: '/packages/pkg-staff/pages/addon-edit/addon-edit' })
}

function goEdit(item: StaffAddonListItem) {
    if (!guardAllowed.value) return
    if (!item?.id) {
        toast.warning('附加项编号缺失，请刷新后重试')
        return
    }
    navigate({
        path: '/packages/pkg-staff/pages/addon-edit/addon-edit',
        query: { id: item.id }
    })
}

/**
 * 只提交上下架开关需要的字段。
 * 不传 package_ids：后端 addonUpdate 对未传字段保留原值，避免开关把适用套餐清空。
 */
async function onToggleShow(item: StaffAddonListItem) {
    if (!guardAllowed.value || togglingId.value > 0) return
    if (!item?.id) return
    const nextShow = Number(item.is_show) === 1 ? 0 : 1
    togglingId.value = Number(item.id)
    try {
        await staffAddonUpdate({
            addon_id: Number(item.id),
            name: String(item.name || '未命名附加项'),
            price: Number(item.price || 0),
            original_price: Number(item.original_price || 0),
            description: String(item.description || ''),
            image: String(item.image || ''),
            sort: Number(item.sort || 0),
            is_show: nextShow as 0 | 1
        })
        toast.success(nextShow === 1 ? '已上架' : '已下架')
        await refresh()
    } catch (e: any) {
        toast.error(String(e?.message || '操作失败，请稍后重试'))
    } finally {
        togglingId.value = 0
    }
}

function onDelete(item: StaffAddonListItem) {
    if (!guardAllowed.value) return
    if (!item?.id) {
        toast.warning('附加项编号缺失，请刷新后重试')
        return
    }
    uni.showModal({
        title: '删除附加项',
        content: '删除后不可恢复，并会解除套餐关联，确认删除？',
        confirmText: '删除',
        cancelText: '取消',
        success: async (r) => {
            if (!r.confirm) return
            try {
                await staffAddonRemove({ addon_id: Number(item.id) })
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
.addon {
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

.acard {
    display: flex;
    gap: var(--sp-3);

    &__cover {
        width: 152rpx;
        height: 152rpx;
        border-radius: var(--r-sm);
        overflow: hidden;
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
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

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
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
    transition: background var(--dur-fast) var(--ease-out);

    &__knob {
        width: 36rpx;
        height: 36rpx;
        border-radius: var(--r-pill);
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
