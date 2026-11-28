<template>
    <page-shell
        :title="isEdit ? '编辑套餐' : '新增套餐'"
        bg="page"
        :show-back="true"
        :bottom-bar="guardAllowed"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            :skeleton-rows="5"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <template v-else>
            <view v-if="loading" class="page-pad">
                <base-skeleton :rows="5" variant="card" />
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
                @retry="() => init(true)"
            />

            <view v-else class="pe">
                <view v-if="!canWrite" class="gate-note">
                    <tn-icon name="tips" :size="24" class="gate-note__icon" />
                    <text class="gate-note__text">{{ STAFF_WRITE_GATE_TIP }}</text>
                </view>

                <base-card>
                    <section-header title="基础信息与定价" flush />
                    <form-field
                        label="套餐名称"
                        type="input"
                        :required="true"
                        :maxlength="100"
                        placeholder="如：全天双机位精修跟拍"
                        :error="errors.name"
                        :model-value="form.name"
                        @update:model-value="(v: string) => (form.name = v)"
                    />
                    <form-field label="销售价格" :required="true" :error="errors.price">
                        <base-input
                            type="digit"
                            placeholder="元，如 2999"
                            :model-value="form.price"
                            @update:model-value="(v: string) => (form.price = v)"
                        >
                            <template #suffix>
                                <text class="unit">元</text>
                            </template>
                        </base-input>
                    </form-field>
                    <form-field label="市场原价" :error="errors.original_price">
                        <base-input
                            type="digit"
                            placeholder="选填，元"
                            :model-value="form.original_price"
                            @update:model-value="(v: string) => (form.original_price = v)"
                        >
                            <template #suffix>
                                <text class="unit">元</text>
                            </template>
                        </base-input>
                    </form-field>
                    <form-field label="服务时长" :error="errors.duration">
                        <base-input
                            type="number"
                            placeholder="选填，如 8（不填为全天）"
                            :model-value="form.duration"
                            @update:model-value="(v: string) => (form.duration = v)"
                        >
                            <template #suffix>
                                <text class="unit">小时</text>
                            </template>
                        </base-input>
                    </form-field>
                    <form-field label="排序权重">
                        <base-input
                            type="number"
                            placeholder="选填，数字越大越靠前"
                            :model-value="form.sort"
                            @update:model-value="(v: string) => (form.sort = v)"
                        />
                    </form-field>
                    <form-field
                        label="套餐封面"
                        type="upload"
                        layout="stack"
                        :max="1"
                        :model-value="imageList"
                        @update:model-value="(v: string[]) => (imageList = v)"
                    />
                    <form-field
                        label="服务说明"
                        type="textarea"
                        layout="stack"
                        :maxlength="500"
                        placeholder="介绍包含的人员配置、服务流程、交付标准等（选填，最多 500 字）"
                        :model-value="form.description"
                        @update:model-value="(v: string) => (form.description = v)"
                    />
                </base-card>

                <base-card>
                    <view class="rp-head">
                        <section-header
                            class="rp-head__sec"
                            title="地区定价"
                            subtitle="优先级：区县 > 城市 > 省级"
                            flush
                        />
                        <view
                            v-if="canWrite"
                            hover-class="is-pressed"
                            class="rp-add-btn hit-88"
                            aria-label="添加地区定价"
                            @tap="openCreateRegionPrice"
                        >
                            <tn-icon name="add" :size="24" class="rp-add-btn__icon" />
                            <text class="rp-add-btn__text">添加</text>
                        </view>
                    </view>

                    <view
                        v-if="!regionGroups.length"
                        class="rp-empty"
                        :hover-class="canWrite ? 'is-pressed' : 'none'"
                        @tap="canWrite && openCreateRegionPrice()"
                    >
                        <view class="rp-empty__badge">
                            <tn-icon name="add" :size="28" class="rp-empty__icon" />
                        </view>
                        <text class="rp-empty__title">
                            {{ canWrite ? '添加地区定价' : '暂未配置地区定价' }}
                        </text>
                        <text class="rp-empty__desc">
                            未配置地区价时，客户选区县后将无法预约该套餐，请至少添加一条地区定价
                        </text>
                    </view>

                    <template v-else>
                        <view class="rp-summary">
                            <text class="rp-summary__text t-num">
                                已配置 {{ regionSummary.total }} 条地区规则（省级
                                {{ regionSummary.provinceCount }} · 城市
                                {{ regionSummary.cityCount }} · 区县
                                {{ regionSummary.districtCount }}）
                            </text>
                        </view>

                        <view v-if="regionGroups.length > 4" class="rp-filter">
                            <view
                                v-for="tab in REGION_FILTER_TABS"
                                :key="tab.key"
                                hover-class="is-pressed"
                                class="rp-filter__pill"
                                :class="{ 'is-on': regionLevelFilter === tab.key }"
                                @tap="setRegionFilter(tab.key)"
                            >
                                <text class="rp-filter__text">{{ tab.label }}</text>
                            </view>
                        </view>

                        <view class="rp-list">
                            <view
                                v-for="group in filteredRegionGroups"
                                :key="group.key"
                                :hover-class="canWrite ? 'is-pressed' : 'none'"
                                class="rp-item"
                                @tap="canWrite && openEditRegionPrice(group)"
                            >
                                <view class="rp-item__main">
                                    <view class="rp-item__title-row">
                                        <view
                                            class="rp-level-tag"
                                            :class="`is-lv-${group.region_level}`"
                                        >
                                            <text class="rp-level-tag__text">{{
                                                getLevelShortLabel(group.region_level)
                                            }}</text>
                                        </view>
                                        <text class="rp-item__title ellipsis">{{
                                            buildGroupTitle(group)
                                        }}</text>
                                    </view>
                                    <text class="rp-item__sub ellipsis">{{
                                        buildGroupSubtitle(group)
                                    }}</text>
                                </view>

                                <view class="rp-item__right">
                                    <view class="rp-item__price-col">
                                        <text class="rp-item__price t-num"
                                            >¥{{ formatMoneyNum(group.price) }}</text
                                        >
                                        <text
                                            v-if="groupDiffText(group.price)"
                                            class="rp-item__diff t-num"
                                            >{{ groupDiffText(group.price) }}</text
                                        >
                                    </view>
                                    <view
                                        v-if="canWrite"
                                        hover-class="is-pressed"
                                        class="rp-item__del hit-88"
                                        aria-label="移除该地区价格"
                                        @tap.stop="confirmDeleteGroup(group)"
                                    >
                                        <tn-icon
                                            name="delete"
                                            :size="26"
                                            class="rp-item__del-icon"
                                        />
                                    </view>
                                </view>
                            </view>
                        </view>
                    </template>
                </base-card>

                <base-card>
                    <view class="sec-head">
                        <section-header class="sec-head__sec" title="可搭配附加项" flush />
                        <view v-if="canWrite && addonOptions.length" class="sec-head__ops">
                            <view
                                hover-class="is-pressed"
                                class="mini-btn"
                                aria-role="button"
                                aria-label="全选附加项"
                                @tap="selectAllAddons"
                            >
                                <text class="mini-btn__text">全选</text>
                            </view>
                            <view
                                hover-class="is-pressed"
                                class="mini-btn"
                                aria-role="button"
                                aria-label="清空已选附加项"
                                @tap="clearAddons"
                            >
                                <text class="mini-btn__text">清空</text>
                            </view>
                        </view>
                    </view>

                    <view v-if="!addonOptions.length" class="empty-block">
                        <text class="empty-block__title">还没有附加项</text>
                        <text class="empty-block__desc">新增后可在此勾选搭配到当前套餐</text>
                        <base-button
                            v-if="canWrite"
                            text="新增附加项"
                            size="sm"
                            variant="ghost"
                            :block="false"
                            @click="goCreateAddon"
                        />
                    </view>

                    <view v-else class="addon-list">
                        <view
                            v-for="ad in addonOptions"
                            :key="ad.id"
                            class="addon"
                            :class="{ 'is-on': isAddonSelected(ad.id) }"
                            hover-class="is-pressed"
                            aria-role="checkbox"
                            :aria-checked="isAddonSelected(ad.id)"
                            :aria-label="ad.name"
                            @tap="toggleAddon(ad.id)"
                        >
                            <view class="addon__check" :class="{ 'is-on': isAddonSelected(ad.id) }">
                                <tn-icon
                                    v-if="isAddonSelected(ad.id)"
                                    name="check"
                                    :size="20"
                                    class="addon__check-icon"
                                />
                            </view>
                            <view class="addon__main">
                                <text class="addon__name ellipsis">{{ ad.name }}</text>
                                <status-tag
                                    v-if="ad.is_show === 0"
                                    label="已停用"
                                    tone="neutral"
                                    size="sm"
                                    :dot="false"
                                />
                            </view>
                            <text class="addon__price t-num"
                                >+¥{{ formatMoneyNum(Number(ad.price)) }}</text
                            >
                        </view>
                    </view>
                </base-card>

                <base-card>
                    <section-header title="上架与推荐设置" flush />
                    <form-field label="上架状态">
                        <view class="pills">
                            <view
                                class="pill"
                                :class="{ 'is-on': form.is_show === 1 }"
                                hover-class="is-pressed"
                                @tap="setShow(1)"
                            >
                                <text class="pill__text">已上架</text>
                            </view>
                            <view
                                class="pill"
                                :class="{ 'is-on': form.is_show === 0 }"
                                hover-class="is-pressed"
                                @tap="setShow(0)"
                            >
                                <text class="pill__text">已停用</text>
                            </view>
                        </view>
                    </form-field>

                    <form-field label="主页推荐">
                        <view class="pills">
                            <view
                                class="pill"
                                :class="{ 'is-on': form.is_recommend === 1 }"
                                hover-class="is-pressed"
                                @tap="setRecommend(1)"
                            >
                                <text class="pill__text">推荐展示</text>
                            </view>
                            <view
                                class="pill"
                                :class="{ 'is-on': form.is_recommend === 0 }"
                                hover-class="is-pressed"
                                @tap="setRecommend(0)"
                            >
                                <text class="pill__text">普通展示</text>
                            </view>
                        </view>
                    </form-field>
                </base-card>

                <view v-if="isEdit && canWrite" class="danger-zone">
                    <base-button
                        text="删除套餐"
                        variant="danger"
                        size="md"
                        :loading="deleting"
                        haptic-level="heavy"
                        @click="onDelete"
                    />
                </view>
            </view>
        </template>

        <template #bottom>
            <bottom-bar v-if="guardAllowed">
                <text v-if="!canWrite" class="bar__note">{{ STAFF_WRITE_GATE_TIP }}</text>
                <template #actions>
                    <base-button
                        v-if="canWrite"
                        :text="submitting ? '保存中…' : isEdit ? '保存修改' : '确认新增'"
                        variant="ink"
                        size="lg"
                        :block="false"
                        :loading="submitting"
                        :disabled="submitting"
                        haptic-level="medium"
                        @click="submit"
                    />
                </template>
            </bottom-bar>

            <region-price-sheet
                v-model="regionSheetVisible"
                :region-tree="regionTree"
                :tree-loading="treeLoading"
                :tree-error="treeError"
                :existing-flat="regionPrices"
                :editing-group="editingRegionGroup"
                :base-price="basePriceNum"
                @retry-tree="loadRegionTree"
                @save="onSaveRegionPrice"
                @delete="onDeleteRegionPrice"
            />
            <app-toast />
        </template>
    </page-shell>
</template>

<script setup lang="ts">
/** 服务人员：新增 / 编辑套餐 */
import { computed, reactive, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import { getRegionTree } from '@/api/catalog'
import type { RegionProvinceVO } from '@/api/types/catalog'
import {
    getStaffAddonList,
    getStaffPackageDetail,
    staffPackageAdd,
    staffPackageRemove,
    staffPackageUpdate,
    type StaffPackageRegionPriceItem
} from '@/api/staff-center'
import RegionPriceSheet from '../../components/region-price-sheet/region-price-sheet.vue'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { useStaffWriteGate, STAFF_WRITE_GATE_TIP } from '@/composables/useStaffWriteGate'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'
import { schemeColor } from '@/utils/scheme-color'
import {
    buildGroupSubtitle,
    buildGroupTitle,
    formatMoneyNum,
    formatPriceDiff,
    getLevelShortLabel,
    groupRegionPrices,
    normalizeRawRegionPrices,
    removeGroupFromFlat,
    summarizeRegionPrices,
    type RegionPriceGroup
} from './regionPriceHelper'

const editId = ref(0)
const isEdit = computed(() => editId.value > 0)

const { canWrite } = useStaffWriteGate()
const { guardPhase, guardReason, guardError, guardAllowed, verify, onGuardRetry, onGuardAction } =
    useStaffGuard(() => init(true))

const loading = ref(false)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))

const submitting = ref(false)
const deleting = ref(false)
const addonOptions = ref<
    Array<{ id: number; name: string; price: string | number; is_show: number }>
>([])

const REGION_FILTER_TABS: Array<{ key: 0 | 1 | 2 | 3; label: string }> = [
    { key: 0, label: '全部' },
    { key: 1, label: '省级' },
    { key: 2, label: '城市' },
    { key: 3, label: '区县' }
]
const regionPrices = ref<StaffPackageRegionPriceItem[]>([])
const regionTree = ref<RegionProvinceVO[]>([])
const treeLoading = ref(false)
const treeError = ref(false)
const regionSheetVisible = ref(false)
const editingRegionGroup = ref<RegionPriceGroup | null>(null)
const regionLevelFilter = ref<0 | 1 | 2 | 3>(0)

const form = reactive({
    name: '',
    price: '',
    original_price: '',
    duration: '',
    sort: '0',
    description: '',
    is_show: 1 as 0 | 1,
    is_recommend: 0 as 0 | 1,
    addon_ids: [] as number[]
})
const imageList = ref<string[]>([])
const errors = reactive<Record<string, string>>({})

const basePriceNum = computed(() => {
    const n = Number(form.price)
    return Number.isFinite(n) && n >= 0 ? n : 0
})
const regionGroups = computed(() => groupRegionPrices(regionPrices.value))
const regionSummary = computed(() => summarizeRegionPrices(regionPrices.value))
const filteredRegionGroups = computed(() => {
    if (regionLevelFilter.value === 0) return regionGroups.value
    return regionGroups.value.filter((g) => g.region_level === regionLevelFilter.value)
})

function groupDiffText(price: number): string {
    return formatPriceDiff(price, basePriceNum.value)
}

async function loadRegionTree() {
    treeLoading.value = true
    treeError.value = false
    try {
        const res = await getRegionTree()
        regionTree.value = Array.isArray(res) ? res : []
    } catch {
        treeError.value = true
    } finally {
        treeLoading.value = false
    }
}

function openCreateRegionPrice() {
    if (!canWrite.value) return
    editingRegionGroup.value = null
    regionSheetVisible.value = true
    if (!regionTree.value.length && !treeLoading.value) {
        void loadRegionTree()
    }
}

function openEditRegionPrice(group: RegionPriceGroup) {
    if (!canWrite.value) return
    editingRegionGroup.value = group
    regionSheetVisible.value = true
    if (!regionTree.value.length && !treeLoading.value) {
        void loadRegionTree()
    }
}

function onSaveRegionPrice(payload: {
    replaceGroupKey: string | null
    items: StaffPackageRegionPriceItem[]
}) {
    // 触觉由 region-price-sheet 的保存按钮统一发 medium，这里不重复触发
    const base = payload.replaceGroupKey
        ? removeGroupFromFlat(regionPrices.value, payload.replaceGroupKey)
        : regionPrices.value
    regionPrices.value = normalizeRawRegionPrices([...base, ...payload.items])
    regionSheetVisible.value = false
    toast.success(payload.replaceGroupKey ? '已更新地区价格' : '已添加地区价格')
}

function onDeleteRegionPrice(groupKey: string) {
    // 触觉同样由 sheet 内删除按钮发 heavy
    regionPrices.value = removeGroupFromFlat(regionPrices.value, groupKey)
    regionSheetVisible.value = false
    toast.success('已移除地区价格')
}

function confirmDeleteGroup(group: RegionPriceGroup) {
    uni.showModal({
        title: '移除地区价格',
        content: `确认移除「${buildGroupTitle(group)}」地区价格？`,
        confirmText: '移除',
        cancelText: '取消',
        // 移除属不可逆操作，uni.showModal 居中确认弹窗保持原生居中形态
        confirmColor: schemeColor('danger'),
        success: (res) => {
            if (!res.confirm) return
            haptic('heavy', 'pkg-region-group-delete')
            regionPrices.value = removeGroupFromFlat(regionPrices.value, group.key)
            toast.success('已移除地区价格')
        }
    })
}

function isAddonSelected(id: number): boolean {
    return form.addon_ids.includes(Number(id))
}

function toggleAddon(id: number) {
    if (!canWrite.value) return
    const numId = Number(id)
    if (isAddonSelected(numId)) {
        form.addon_ids = form.addon_ids.filter((x) => x !== numId)
    } else {
        form.addon_ids = [...form.addon_ids, numId]
    }
}

function selectAllAddons() {
    if (!canWrite.value) return
    form.addon_ids = addonOptions.value.map((ad) => ad.id)
}

function clearAddons() {
    if (!canWrite.value) return
    form.addon_ids = []
}

function setShow(val: 0 | 1) {
    if (!canWrite.value || form.is_show === val) return
    form.is_show = val
}

function setRecommend(val: 0 | 1) {
    if (!canWrite.value || form.is_recommend === val) return
    form.is_recommend = val
}

function setRegionFilter(val: 0 | 1 | 2 | 3) {
    if (regionLevelFilter.value === val) return
    regionLevelFilter.value = val
}

function goCreateAddon() {
    navigate({ path: '/packages/pkg-staff/pages/addon-edit/addon-edit' })
}

/**
 * 拉取附加项可选列表。
 * prune=true 时剔除已不存在的已选项（附加项被删除后表单里不该再提交它）；
 * 其余表单字段一律不动，从附加项编辑页返回时用户未保存的编辑内容不丢。
 */
async function loadAddonOptions(prune = true) {
    try {
        const res = await getStaffAddonList({ page: 1, page_size: 100 })
        const rows = Array.isArray(res?.data)
            ? res.data
            : Array.isArray(res?.lists)
            ? res.lists
            : []
        addonOptions.value = rows.map((x) => ({
            id: Number(x.id),
            name: String(x.name || '附加项'),
            price: x.price ?? 0,
            is_show: Number(x.is_show ?? 1)
        }))
        if (prune) {
            const valid = new Set(addonOptions.value.map((ad) => ad.id))
            form.addon_ids = form.addon_ids.filter((id) => valid.has(id))
        }
    } catch {
        // 附加项拉取失败不阻断套餐表单，用户可以先去维护附加项再回来
    }
}

function validate(): boolean {
    Object.keys(errors).forEach((k) => delete errors[k])
    if (!form.name.trim()) {
        errors.name = '请填写套餐名称'
        toast.warning(errors.name)
        return false
    }
    if (form.price.trim() === '' || Number.isNaN(Number(form.price)) || Number(form.price) < 0) {
        errors.price = '请填写有效的销售价格'
        toast.warning(errors.price)
        return false
    }
    if (
        form.original_price.trim() !== '' &&
        (Number.isNaN(Number(form.original_price)) || Number(form.original_price) < 0)
    ) {
        errors.original_price = '市场原价不能小于 0'
        toast.warning(errors.original_price)
        return false
    }
    if (
        form.duration.trim() !== '' &&
        (!/^\d+$/.test(form.duration.trim()) || Number(form.duration) < 0)
    ) {
        errors.duration = '服务时长须为非负整数（小时）'
        toast.warning(errors.duration)
        return false
    }
    return true
}

async function submit() {
    if (!canWrite.value) {
        toast.info(STAFF_WRITE_GATE_TIP)
        return
    }
    if (submitting.value || !validate()) return

    submitting.value = true
    try {
        const payload = {
            name: form.name.trim(),
            price: Number(form.price),
            original_price: form.original_price.trim() ? Number(form.original_price) : 0,
            duration: form.duration.trim() ? parseInt(form.duration, 10) : 0,
            sort: form.sort.trim() ? Math.max(0, parseInt(form.sort, 10) || 0) : 0,
            description: form.description.trim(),
            image: imageList.value[0] || '',
            is_show: form.is_show,
            is_recommend: form.is_recommend,
            addon_ids: form.addon_ids,
            region_prices: regionPrices.value
        }
        if (isEdit.value) {
            await staffPackageUpdate({ ...payload, package_id: editId.value })
            toast.success('保存成功')
        } else {
            await staffPackageAdd(payload)
            toast.success('新增套餐成功')
        }
        setTimeout(() => uni.navigateBack(), 450)
    } catch (e: any) {
        toast.error(String(e?.message || '提交失败，请稍后重试'))
    } finally {
        submitting.value = false
    }
}

function onDelete() {
    if (!isEdit.value || deleting.value) return
    uni.showModal({
        title: '删除套餐',
        content: '删除后不可恢复，确认删除这个套餐？',
        confirmText: '删除',
        cancelText: '取消',
        // 删除属不可逆操作，uni.showModal 居中确认弹窗保持原生居中形态
        confirmColor: schemeColor('danger'),
        success: async (r) => {
            if (!r.confirm) return
            deleting.value = true
            try {
                await staffPackageRemove({ package_id: editId.value })
                toast.success('已删除')
                setTimeout(() => uni.navigateBack(), 450)
            } catch (e: any) {
                toast.error(String(e?.message || '删除失败，请稍后重试'))
            } finally {
                deleting.value = false
            }
        }
    })
}

function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        void init(true)
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    uni.navigateBack()
}

let hasLoaded = false

async function init(forceGuard = false) {
    if (!(await verify(forceGuard))) return
    loading.value = true
    rawError.value = null
    try {
        const treePromise = loadRegionTree()
        const detailPromise = isEdit.value
            ? getStaffPackageDetail({ package_id: editId.value })
            : Promise.resolve(null)
        const [detail] = await Promise.all([detailPromise, treePromise, loadAddonOptions(false)])

        if (detail) {
            form.name = String(detail.name || '')
            form.price =
                detail.price !== undefined && detail.price !== null ? String(detail.price) : ''
            form.original_price =
                Number(detail.original_price || 0) > 0 ? String(detail.original_price) : ''
            form.duration = Number(detail.duration || 0) > 0 ? String(detail.duration) : ''
            form.sort = String(detail.sort ?? 0)
            form.description = String(detail.description || '')
            form.is_show = Number(detail.is_show ?? 1) === 1 ? 1 : 0
            form.is_recommend = Number(detail.is_recommend ?? 0) === 1 ? 1 : 0
            form.addon_ids = Array.isArray(detail.addon_ids)
                ? detail.addon_ids.map((v: any) => Number(v))
                : []
            imageList.value = detail.image ? [String(detail.image)] : []
            regionPrices.value = normalizeRawRegionPrices(detail.region_prices)
        } else {
            regionPrices.value = []
        }

        // 详情回填后再修剪一次，避免已删除的附加项残留在已选项里
        const valid = new Set(addonOptions.value.map((ad) => ad.id))
        form.addon_ids = form.addon_ids.filter((id) => valid.has(id))
    } catch (e) {
        rawError.value = e
    } finally {
        loading.value = false
        hasLoaded = true
    }
}

onLoad((options?: Record<string, any>) => {
    editId.value = Number(options?.id || 0)
    void init()
})

/** 从附加项编辑页返回时刷新可选项；只动选项，不覆盖用户未保存的表单 */
onShow(() => {
    if (hasLoaded) void loadAddonOptions()
})
</script>

<style lang="scss" scoped>
.pe {
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-6) + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.gate-note {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &__icon {
        color: var(--c-warning);
    }

    &__text {
        flex: 1;
        font-size: var(--fs-xs);
        color: var(--c-warning);
        line-height: var(--lh-body);
    }
}

.rp-head,
.sec-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-2);

    &__sec {
        flex: 1;
        min-width: 0;
    }
}

.sec-head {
    align-items: center;

    &__ops {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }
}

.mini-btn {
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.96);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.rp-add-btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6rpx;
    min-height: 56rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);
    color: var(--c-brand-text);
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.94);
    }

    &__icon {
        color: inherit;
    }

    &__text {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: inherit;
    }
}

.rp-empty {
    margin-top: var(--sp-2);
    padding: var(--sp-4) var(--sp-3);
    border-radius: var(--r-md);
    border: 1rpx dashed var(--c-control-border);
    background: var(--c-surface-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-1);

    &__badge {
        width: 64rpx;
        height: 64rpx;
        margin-bottom: var(--sp-1);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        border: 1rpx solid var(--c-line);
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__icon {
        color: inherit;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
        line-height: var(--lh-body);
    }
}

.rp-summary {
    margin-top: var(--sp-2);
    padding: var(--sp-1) var(--sp-2);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.rp-filter {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-top: var(--sp-2);

    &__pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        /* 筛选胶囊可能换行：不用 .hit-88（上下伪元素会跨行叠），直接把盒高做到 88rpx */
        min-height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        /* 可点胶囊：控件边界色，未选中态在白/黑底上都能看清 */
        border: 1rpx solid var(--c-control-border);
        background: var(--c-bg);
        transition: transform var(--dur-press) var(--ease-out);

        &.is-pressed {
            transform: scale(0.96);
        }

        &.is-on {
            border-color: var(--c-active-border);
            background: var(--c-active-bg);

            .rp-filter__text {
                color: var(--c-active-fg);
                font-weight: var(--fw-medium);
            }
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.rp-list {
    margin-top: var(--sp-2);
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.rp-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    /* 可点行：盒高 88rpx + 控件边界色，未选中态也看得清边界 */
    min-height: 88rpx;
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-control-border);

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title-row {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__right {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__price-col {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2rpx;
    }

    &__price {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }

    &__diff {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__del {
        width: 56rpx;
        height: 56rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface);
        border: 1rpx solid var(--c-control-border);
        color: var(--c-ink-3);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform var(--dur-press) var(--ease-out);

        &.is-pressed {
            transform: scale(0.92);
        }
    }

    &__del-icon {
        color: inherit;
    }
}

.rp-level-tag {
    flex-shrink: 0;
    padding: 2rpx 10rpx;
    border-radius: var(--r-xs);
    background: var(--c-neutral-bg);
    border: 1rpx solid var(--c-neutral-border);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-neutral);
    }

    &.is-lv-1 {
        background: var(--c-info-bg);
        border-color: var(--c-info-border);

        .rp-level-tag__text {
            color: var(--c-info);
        }
    }

    &.is-lv-2 {
        background: var(--c-brand-bg);
        border-color: var(--c-primary-border);

        .rp-level-tag__text {
            color: var(--c-brand-text);
        }
    }

    &.is-lv-3 {
        background: var(--c-success-bg);
        border-color: var(--c-success-border);

        .rp-level-tag__text {
            color: var(--c-success);
        }
    }
}

.empty-block {
    margin-top: var(--sp-2);
    padding: var(--sp-4) var(--sp-3);
    border-radius: var(--r-md);
    border: 1rpx dashed var(--c-control-border);
    background: var(--c-surface-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-1);

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
        line-height: var(--lh-body);
    }
}

.addon-list {
    margin-top: var(--sp-2);
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.addon {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    min-height: 88rpx;
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-control-border);

    &.is-on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__check {
        flex-shrink: 0;
        width: 40rpx;
        height: 40rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;

        &.is-on {
            border-color: var(--c-brand-text);
            background: var(--c-brand-text);
        }
    }

    &__check-icon {
        color: var(--c-surface);
    }

    &__main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--sp-1);
    }

    &__name {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__price {
        flex-shrink: 0;
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }
}

.unit {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

.pills {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-bg);
    border: 1rpx solid var(--c-control-border);
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.96);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-on {
        background: var(--c-active-bg);
        border-color: var(--c-active-border);

        .pill__text {
            color: var(--c-active-fg);
            font-weight: var(--fw-medium);
        }
    }
}

.danger-zone {
    padding-top: var(--sp-2);
}

.bar__note {
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
}

/* v10 §3.13：减弱动效下按压不缩放 */
@media (prefers-reduced-motion: reduce) {
    .mini-btn,
    .rp-add-btn,
    .rp-filter__pill,
    .rp-item__del,
    .pill {
        transition: none;
    }

    .mini-btn.is-pressed,
    .rp-add-btn.is-pressed,
    .rp-filter__pill.is-pressed,
    .rp-item__del.is-pressed,
    .pill.is-pressed {
        transform: none;
    }
}
</style>
