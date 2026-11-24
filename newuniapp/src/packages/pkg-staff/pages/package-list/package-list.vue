<template>
    <page-shell title="我的套餐" bg="page" :show-back="true">
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap pkg">
            <view v-if="canWrite" class="action page-pad">
                <view
                    hover-class="is-pressed"
                    class="action__btn"
                    aria-role="button"
                    aria-label="新增套餐"
                    @tap="goCreate"
                >
                    <tn-icon name="add" :size="24" class="action__icon" />
                    <text class="action__text">新增套餐</text>
                </view>
            </view>
            <view v-else class="gate page-pad">
                <text class="gate__text">套餐维护功能灰度中，暂未开放</text>
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
                empty-text="还没有配置套餐"
                :desc="canWrite ? '点击上方「新增套餐」，上架后客户可在主页预约选择' : '套餐上架后客户在下单页即可选择'"
                fixed
            />

            <view v-else class="page-pad list">
                <base-card v-for="item in list" :key="item.id" compact>
                    <view class="item">
                        <image
                            v-if="item.image"
                            class="item__cover"
                            :src="item.image"
                            mode="aspectFill"
                            aria-label="套餐封面"
                        />
                        <view class="item__body">
                            <view class="item__line">
                                <text class="item__name ellipsis">{{
                                    item.name || '未命名套餐'
                                }}</text>
                                <status-tag
                                    v-if="Number(item.is_recommend) === 1"
                                    label="推荐"
                                    tone="primary"
                                    size="sm"
                                    :dot="false"
                                />
                                <status-tag
                                    :label="item.is_show ? '已上架' : '已下架'"
                                    :tone="item.is_show ? 'success' : 'neutral'"
                                    size="sm"
                                    :dot="false"
                                />
                            </view>
                            <text v-if="item.description" class="item__desc ellipsis-2">
                                {{ item.description }}
                            </text>
                            <view class="item__foot">
                                <price
                                    size="md"
                                    :content="item.price"
                                    color="var(--c-brand-text)"
                                />
                                <text v-if="Number(item.original_price) > 0" class="item__origin">
                                    原价 ¥{{ item.original_price }}
                                </text>
                                <text v-if="Number(item.duration) > 0" class="item__meta">
                                    {{ item.duration }} 分钟
                                </text>
                                <text v-if="addonCount(item) > 0" class="item__meta">
                                    {{ addonCount(item) }} 项加购
                                </text>
                                <text v-if="regionPriceCount(item) > 0" class="item__meta">
                                    {{ regionPriceCount(item) }} 条地区价
                                </text>
                            </view>
                        </view>
                    </view>

                    <view v-if="canWrite" class="ops">
                        <view
                            hover-class="is-pressed"
                            class="ops__btn"
                            :aria-label="`${item.is_show ? '下架' : '上架'}套餐：${item.name || ''}`"
                            @tap="onToggleShow(item)"
                        >
                            <text class="ops__text">{{
                                togglingId === item.id ? '处理中…' : item.is_show ? '下架' : '上架'
                            }}</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            class="ops__btn"
                            :aria-label="`编辑套餐：${item.name || ''}`"
                            @tap="goEdit(item)"
                        >
                            <text class="ops__text">编辑</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            class="ops__btn"
                            :aria-label="`删除套餐：${item.name || ''}`"
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
/** 服务人员：我的套餐（支持增删改与快捷上下架）。 */
import { computed, ref } from 'vue'
import { onLoad, onPullDownRefresh, onReachBottom, onShow } from '@dcloudio/uni-app'
import {
    getStaffPackageList,
    staffPackageRemove,
    staffPackageUpdate
} from '@/api/staff-center'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { usePagedList } from '@/composables/usePagedList'
import { useStaffWriteGate } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'

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

const TABS = [
    { key: '', label: '全部' },
    { key: 1, label: '已上架' },
    { key: 0, label: '已下架' }
]

const active = ref<string | number>('')
const togglingId = ref(0)
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const query = computed(() => ({
    is_show: active.value === '' ? '' : Number(active.value)
}))

const { list, phase, loadMoreStatus, reload, loadMore, refresh } = usePagedList<any>({
    fetcher: (q) => getStaffPackageList(q as any),
    query,
    onError: (e) => {
        rawError.value = e
    }
})

let hasLoaded = false

function addonCount(item: any): number {
    return Array.isArray(item?.addon_ids) ? item.addon_ids.length : 0
}

function regionPriceCount(item: any): number {
    return Array.isArray(item?.region_prices) ? item.region_prices.length : 0
}

function goCreate() {
    if (!guardAllowed.value) return
    navigate({ path: '/packages/pkg-staff/pages/package-edit/package-edit' })
}

function goEdit(item: any) {
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

async function onToggleShow(item: any) {
    if (!guardAllowed.value || togglingId.value > 0) return
    if (!item?.id) return
    const nextShow = Number(item.is_show) === 1 ? 0 : 1
    togglingId.value = Number(item.id)
    try {
        await staffPackageUpdate({
            package_id: Number(item.id),
            name: String(item.name || '未命名套餐'),
            price: Number(item.price || 0),
            original_price: Number(item.original_price || 0),
            description: String(item.description || ''),
            image: String(item.image || ''),
            duration: Number(item.duration || 0),
            sort: Number(item.sort || 0),
            is_show: nextShow as 0 | 1,
            is_recommend: Number(item.is_recommend || 0) === 1 ? 1 : 0,
            addon_ids: Array.isArray(item.addon_ids) ? item.addon_ids : []
        })
        toast.success(nextShow === 1 ? '已上架' : '已下架')
        await refresh()
    } catch (e: any) {
        toast.error(String(e?.message || '操作失败，请稍后重试'))
    } finally {
        togglingId.value = 0
    }
}

function onDelete(item: any) {
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
        success: async (r) => {
            if (!r.confirm) return
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
.pkg {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.action {
    display: flex;
    justify-content: flex-end;
    padding-top: var(--sp-2);

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

.gate {
    padding-top: var(--sp-2);

    &__text {
        display: block;
        text-align: right;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.tabs {
    padding-top: var(--sp-2);
    padding-bottom: var(--sp-2);
}

.list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.item {
    display: flex;
    gap: var(--sp-3);

    &__cover {
        width: 132rpx;
        height: 132rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__line {
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

    &__desc {
        display: block;
        margin-top: var(--sp-1);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }

    &__foot {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: var(--sp-2);
        margin-top: var(--sp-2);
    }

    &__origin {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-decoration: line-through;
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
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
