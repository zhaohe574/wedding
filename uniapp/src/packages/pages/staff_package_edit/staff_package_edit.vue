<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" hasSafeBottom>
        <BaseNavbar :title="pageTitle" />

        <view class="page-container">
            <view class="page-section page-section--content wm-page-content">
                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">封面与价格</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">封面图</text>
                            <text class="field-side-text">{{
                                form.image ? '已上传' : '必填'
                            }}</text>
                        </view>

                        <view v-if="form.image" class="cover-preview">
                            <image
                                :src="form.image"
                                class="cover-preview__image"
                                mode="aspectFill"
                                @click="previewCover"
                            />
                            <view class="cover-preview__toolbar">
                                <view class="cover-preview__action" @click="chooseCover">
                                    <tn-icon name="refresh" size="26" color="#ffffff" />
                                    <text class="cover-preview__action-text">更换</text>
                                </view>
                                <view class="cover-preview__divider" />
                                <view class="cover-preview__action" @click="removeCover">
                                    <tn-icon name="delete" size="26" color="#ffffff" />
                                    <text class="cover-preview__action-text">删除</text>
                                </view>
                            </view>
                        </view>

                        <view
                            v-else
                            class="upload-panel upload-panel--cover wm-soft-card"
                            @click="chooseCover"
                        >
                            <view class="upload-panel__icon-wrap">
                                <tn-icon
                                    name="image"
                                    size="50"
                                    color="var(--wm-color-primary, #0B0B0B)"
                                />
                            </view>
                            <text class="upload-panel__title">上传封面</text>
                        </view>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label field-label--required">套餐名称</text>
                            <text class="field-side-text">{{ form.name.length }}/50</text>
                        </view>
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.name"
                                placeholder="例如：全天主持套餐"
                                :maxlength="50"
                                :border="false"
                                class="field-input"
                            />
                        </view>
                    </view>

                    <view class="field-grid">
                        <view class="field-block field-block--compact">
                            <view class="field-label-row">
                                <text class="field-label field-label--required">默认价格</text>
                            </view>
                            <view class="field-input-shell wm-soft-card">
                                <tn-input
                                    v-model="form.price"
                                    type="digit"
                                    placeholder="输入价格"
                                    :border="false"
                                    class="field-input"
                                />
                            </view>
                        </view>

                        <view class="field-block field-block--compact">
                            <view class="field-label-row">
                                <text class="field-label">原价</text>
                            </view>
                            <view class="field-input-shell wm-soft-card">
                                <tn-input
                                    v-model="form.original_price"
                                    type="digit"
                                    placeholder="选填"
                                    :border="false"
                                    class="field-input"
                                />
                            </view>
                        </view>
                    </view>

                    <view class="field-block field-block--compact">
                        <view class="field-label-row">
                            <text class="field-label">服务时长</text>
                            <text class="field-side-text">单位：小时</text>
                        </view>
                        <view class="field-input-shell wm-soft-card">
                            <tn-input
                                v-model="form.duration"
                                type="number"
                                placeholder="选填，默认 0"
                                :border="false"
                                class="field-input"
                            />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">地区价格</text>
                        <text class="field-side-text">已配置 {{ regionSummary.total }} 条</text>
                    </view>

                    <view class="action-chip-row">
                        <view class="action-chip" @click="openCreateRule(REGION_LEVEL_PROVINCE)"
                            >新增省级价</view
                        >
                        <view class="action-chip" @click="openCreateRule(REGION_LEVEL_CITY)"
                            >新增城市价</view
                        >
                        <view
                            class="action-chip action-chip--accent"
                            @click="openCreateRule(REGION_LEVEL_DISTRICT)"
                            >新增区县价</view
                        >
                    </view>

                    <view v-if="sortedRegionPrices.length" class="rule-list">
                        <view
                            v-for="(item, index) in sortedRegionPrices"
                            :key="buildRegionRuleKey(item)"
                            class="rule-row"
                        >
                            <view class="rule-row__main">
                                <view class="rule-row__head">
                                    <view
                                        :class="[
                                            'rule-badge',
                                            `rule-badge--${getRuleTone(item.region_level)}`
                                        ]"
                                    >
                                        <text class="rule-badge__text">{{
                                            getLevelShortLabel(item.region_level)
                                        }}</text>
                                    </view>
                                    <text class="rule-row__price"
                                        >¥{{ formatMoney(item.price) }}</text
                                    >
                                </view>
                                <text class="rule-row__title">{{
                                    buildRegionRuleLabel(item)
                                }}</text>
                            </view>
                            <view class="rule-row__actions">
                                <view class="rule-row__action" @click="openEditRule(item, index)"
                                    >编辑</view
                                >
                                <view
                                    class="rule-row__action rule-row__action--danger"
                                    @click="removeRule(index)"
                                >
                                    删除
                                </view>
                            </view>
                        </view>
                    </view>

                    <view v-else class="empty-panel">
                        <view class="empty-panel__icon">
                            <tn-icon name="location" size="40" color="#D8D3C7" />
                        </view>
                        <text class="empty-panel__title">暂无地区价</text>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">可选附加项</text>
                        <text class="field-side-text">{{ form.addon_ids.length }} 项已勾选</text>
                    </view>

                    <view v-if="addonOptions.length" class="addon-chip-list">
                        <view
                            v-for="addon in addonOptions"
                            :key="addon.id"
                            :class="[
                                'addon-chip',
                                'wm-soft-card',
                                {
                                    'addon-chip--active': form.addon_ids.includes(
                                        Number(addon.id || 0)
                                    ),
                                    'addon-chip--inactive': Number(addon.is_show ?? 1) !== 1
                                }
                            ]"
                            @click="toggleAddonSelection(Number(addon.id || 0))"
                        >
                            <view class="addon-chip__copy">
                                <text class="addon-chip__title">{{
                                    addon.name || '未命名附加项'
                                }}</text>
                                <text class="addon-chip__meta">
                                    ¥{{ formatMoney(addon.price) }}
                                    <text v-if="Number(addon.is_show ?? 1) !== 1"> · 已下架</text>
                                </text>
                            </view>
                            <text class="addon-chip__check">
                                {{ form.addon_ids.includes(Number(addon.id || 0)) ? '✓' : '+' }}
                            </text>
                        </view>
                    </view>

                    <view v-else class="empty-panel empty-panel--compact">
                        <view class="empty-panel__icon">
                            <tn-icon name="add-circle" size="40" color="#D8D3C7" />
                        </view>
                        <text class="empty-panel__title">暂无附加项可配置</text>
                    </view>
                </BaseCard>

                <BaseCard variant="glass" scene="staff" class="form-card wm-form-block">
                    <view class="card-head">
                        <text class="card-head__title">展示设置</text>
                    </view>

                    <view class="field-block">
                        <view class="field-label-row">
                            <text class="field-label">套餐说明</text>
                            <text class="field-side-text">{{ form.description.length }}/500</text>
                        </view>
                        <view class="textarea-shell wm-soft-card">
                            <textarea
                                v-model="form.description"
                                class="field-textarea"
                                placeholder="输入套餐说明"
                                :maxlength="500"
                                :auto-height="true"
                                :show-confirm-bar="false"
                            />
                        </view>
                    </view>

                    <view class="setting-list">
                        <view class="setting-item">
                            <text class="setting-item__label">排序</text>
                            <view class="setting-item__input setting-item__input--sm">
                                <tn-input
                                    v-model="form.sort"
                                    type="number"
                                    placeholder="默认 0"
                                    :border="false"
                                    class="setting-input setting-input--right"
                                />
                            </view>
                        </view>

                        <view class="setting-item setting-item--switch">
                            <text class="setting-item__label">推荐</text>
                            <switch
                                :checked="recommendSwitch"
                                :color="$theme.primaryColor"
                                style="transform: scale(0.9)"
                                @change="recommendSwitch = $event.detail.value"
                            />
                        </view>

                        <view class="setting-item setting-item--switch">
                            <text class="setting-item__label">上架状态</text>
                            <switch
                                :checked="statusSwitch"
                                :color="$theme.primaryColor"
                                style="transform: scale(0.9)"
                                @change="statusSwitch = $event.detail.value"
                            />
                        </view>
                    </view>
                </BaseCard>
            </view>

            <StaffActionBar
                :primary-text="saveButtonText"
                secondary-text="取消"
                :loading="saving"
                @secondary="handleCancel"
                @primary="handleSave"
            />

            <BaseOverlayMask :show="showRulePopup" @close="closeRulePopup" />
            <tn-popup
                v-model="showRulePopup"
                open-direction="bottom"
                :overlay="false"
                :overlay-closeable="true"
                safe-area-inset-bottom
                :radius="popupBorderRadius"
            >
                <view class="picker">
                    <view class="picker__head">
                        <text class="picker__action" @tap="closeRulePopup">取消</text>
                        <text class="picker__title">{{ rulePopupTitle }}</text>
                        <text class="picker__action picker__action--primary" @tap="saveRule"
                            >保存</text
                        >
                    </view>

                    <view class="picker__body">
                        <view class="level-chip-row">
                            <view
                                v-for="item in levelOptions"
                                :key="item.value"
                                :class="[
                                    'level-chip',
                                    { 'level-chip--active': ruleDraft.region_level === item.value }
                                ]"
                                @tap="switchRuleLevel(item.value)"
                            >
                                {{ item.label }}
                            </view>
                        </view>

                        <view class="field-block field-block--compact">
                            <view class="field-label-row">
                                <text class="field-label field-label--required">地区售价</text>
                            </view>
                            <view class="field-input-shell">
                                <tn-input
                                    v-model="ruleDraft.price"
                                    type="digit"
                                    placeholder="输入价格"
                                    :border="false"
                                    class="field-input"
                                />
                            </view>
                        </view>

                        <view class="region-picker">
                            <view class="region-picker__col">
                                <view class="region-picker__title">省份</view>
                                <scroll-view scroll-y class="region-picker__scroll">
                                    <view
                                        v-for="province in regionProvinces"
                                        :key="province.province_code"
                                        class="region-picker__item"
                                        :style="
                                            getRegionItemStyle(
                                                ruleDraft.province_code === province.province_code
                                            )
                                        "
                                        @tap="handleProvinceSelect(province)"
                                    >
                                        {{ province.province_name }}
                                    </view>
                                </scroll-view>
                            </view>

                            <view
                                v-if="ruleDraft.region_level !== REGION_LEVEL_PROVINCE"
                                class="region-picker__col"
                            >
                                <view class="region-picker__title">城市</view>
                                <scroll-view scroll-y class="region-picker__scroll">
                                    <view
                                        v-for="city in regionCities"
                                        :key="city.city_code"
                                        class="region-picker__item"
                                        :style="
                                            getRegionItemStyle(
                                                ruleDraft.city_code === city.city_code
                                            )
                                        "
                                        @tap="handleCitySelect(city)"
                                    >
                                        {{ city.city_name }}
                                    </view>
                                </scroll-view>
                            </view>

                            <view
                                v-if="ruleDraft.region_level === REGION_LEVEL_DISTRICT"
                                class="region-picker__col"
                            >
                                <view class="region-picker__title">区县</view>
                                <scroll-view scroll-y class="region-picker__scroll">
                                    <view
                                        v-for="district in regionDistricts"
                                        :key="district.district_code"
                                        class="region-picker__item"
                                        :style="
                                            getRegionItemStyle(
                                                ruleDraft.district_code === district.district_code
                                            )
                                        "
                                        @tap="handleDistrictSelect(district)"
                                    >
                                        {{ district.district_name }}
                                    </view>
                                </scroll-view>
                            </view>
                        </view>
                    </view>
                </view>
            </tn-popup>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { uploadImage } from '@/api/app'
import {
    staffCenterAddonLists,
    staffCenterPackageAdd,
    staffCenterPackageDetail,
    staffCenterPackageUpdate
} from '@/api/staffCenter'
import { getServiceRegionTree } from '@/api/service'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import StaffActionBar from '@/packages/components/staff-workspace/staff-action-bar.vue'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'
import {
    REGION_LEVEL_CITY,
    REGION_LEVEL_DISTRICT,
    REGION_LEVEL_PROVINCE,
    normalizeRegionLevel,
    normalizeRegionPriceRows,
    summarizeRegionPrices,
    type RegionLevel,
    type RegionPriceRow
} from '@/packages/common/utils/package-region-price'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'

interface RegionDistrict {
    district_code: string
    district_name: string
}

interface RegionCity {
    city_code: string
    city_name: string
    districts: RegionDistrict[]
}

interface RegionProvince {
    province_code: string
    province_name: string
    cities: RegionCity[]
}

interface RuleDraft {
    region_level: RegionLevel
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
    price: string
}

const $theme = useThemeStore()
const saving = ref(false)
const showRulePopup = ref(false)
const regionTree = ref<RegionProvince[]>([])
const addonOptions = ref<any[]>([])
const editingRuleIndex = ref(-1)

const form = reactive({
    package_id: 0,
    name: '',
    price: '',
    original_price: '',
    image: '',
    description: '',
    region_prices: [] as RegionPriceRow[],
    addon_ids: [] as number[],
    duration: '0',
    sort: '0',
    is_show: 1,
    is_recommend: 0
})

const createEmptyRuleDraft = (level: RegionLevel = REGION_LEVEL_CITY): RuleDraft => ({
    region_level: level,
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_code: '',
    district_name: '',
    price: ''
})

const ruleDraft = reactive<RuleDraft>(createEmptyRuleDraft())

const levelOptions = [
    { label: '省级价', value: REGION_LEVEL_PROVINCE },
    { label: '城市价', value: REGION_LEVEL_CITY },
    { label: '区县价', value: REGION_LEVEL_DISTRICT }
]

const isEdit = computed(() => form.package_id > 0)
const pageTitle = computed(() => (isEdit.value ? '编辑套餐' : '新增套餐'))
const saveButtonText = computed(() => {
    if (saving.value) {
        return isEdit.value ? '保存中...' : '提交中...'
    }
    return isEdit.value ? '保存修改' : '提交套餐'
})
const popupBorderRadius = computed(() => 44)
const rulePopupTitle = computed(() =>
    editingRuleIndex.value >= 0 ? '编辑地区价格' : '新增地区价格'
)
const regionSummary = computed(() => summarizeRegionPrices(form.region_prices))
const sortRegionPriceRows = (list: Record<string, any>[] = []) => {
    return normalizeRegionPriceRows(list).sort((left, right) => {
        if (left.region_level !== right.region_level) {
            return left.region_level - right.region_level
        }
        return buildRegionRuleLabel(left).localeCompare(buildRegionRuleLabel(right), 'zh-Hans-CN')
    })
}
const sortedRegionPrices = computed(() => sortRegionPriceRows(form.region_prices))

const statusSwitch = computed({
    get: () => form.is_show === 1,
    set: (value: boolean) => {
        form.is_show = value ? 1 : 0
    }
})

const recommendSwitch = computed({
    get: () => form.is_recommend === 1,
    set: (value: boolean) => {
        form.is_recommend = value ? 1 : 0
    }
})

const regionProvinces = computed(() => regionTree.value)
const regionCities = computed(() => {
    const province = regionTree.value.find((item) => item.province_code === ruleDraft.province_code)
    return province?.cities || []
})
const regionDistricts = computed(() => {
    const city = regionCities.value.find((item) => item.city_code === ruleDraft.city_code)
    return city?.districts || []
})

const getNodeCode = (source: Record<string, any>, keys: string[]) => {
    for (const key of keys) {
        const value = source?.[key]
        if (value !== undefined && value !== null && value !== '') {
            return String(value)
        }
    }
    return ''
}

const getNodeName = (source: Record<string, any>, keys: string[]) => {
    for (const key of keys) {
        const value = source?.[key]
        if (typeof value === 'string' && value.trim()) {
            return value.trim()
        }
    }
    return ''
}

const normalizeTextValue = (value: unknown) => {
    if (value === undefined || value === null) {
        return ''
    }
    return String(value).trim()
}

const normalizeAddonIds = (value: unknown): number[] => {
    if (!Array.isArray(value)) {
        return []
    }

    return value
        .map((item) => Number(item))
        .filter((item) => Number.isInteger(item) && item > 0)
        .filter((item, index, list) => list.indexOf(item) === index)
}

const getNodeChildren = (source: Record<string, any>) => {
    const candidates = [
        source?.children,
        source?.child,
        source?.list,
        source?.districts,
        source?.cities
    ]
    return candidates.find((item) => Array.isArray(item)) || []
}

const normalizeDistricts = (list: Record<string, any>[] = []): RegionDistrict[] => {
    return list
        .map((item) => ({
            district_code: getNodeCode(item, ['district_code', 'code', 'value', 'id']),
            district_name: getNodeName(item, ['district_name', 'name', 'label', 'title'])
        }))
        .filter((item) => item.district_code || item.district_name)
}

const normalizeCities = (list: Record<string, any>[] = []): RegionCity[] => {
    return list
        .map((item) => ({
            city_code: getNodeCode(item, ['city_code', 'code', 'value', 'id']),
            city_name: getNodeName(item, ['city_name', 'name', 'label', 'title']),
            districts: normalizeDistricts(getNodeChildren(item))
        }))
        .filter((item) => item.city_code || item.city_name)
}

const normalizeProvinces = (list: Record<string, any>[] = []): RegionProvince[] => {
    return list
        .map((item) => ({
            province_code: getNodeCode(item, ['province_code', 'code', 'value', 'id']),
            province_name: getNodeName(item, ['province_name', 'name', 'label', 'title']),
            cities: normalizeCities(getNodeChildren(item))
        }))
        .filter((item) => item.province_code || item.province_name)
}

const fillForm = (data: any) => {
    form.package_id = Number(data.package_id || data.id || 0)
    form.name = data.name || ''
    form.price = data.price !== undefined && data.price !== null ? String(data.price) : ''
    form.original_price =
        data.original_price !== undefined && data.original_price !== null
            ? String(data.original_price)
            : ''
    form.image = data.image || ''
    form.description = data.description || ''
    form.region_prices = sortRegionPriceRows(data.region_prices || [])
    form.addon_ids = normalizeAddonIds(data.addon_ids)
    form.duration =
        data.duration !== undefined && data.duration !== null ? String(data.duration) : '0'
    form.sort = data.sort !== undefined && data.sort !== null ? String(data.sort) : '0'
    form.is_show = Number(data.is_show ?? 1)
    form.is_recommend = Number(data.is_recommend ?? 0)
}

const toggleAddonSelection = (addonId: number) => {
    if (addonId <= 0) {
        return
    }

    if (form.addon_ids.includes(addonId)) {
        form.addon_ids = form.addon_ids.filter((item) => item !== addonId)
        return
    }

    form.addon_ids = [...form.addon_ids, addonId]
}

const formatMoney = (value: number | string) => {
    const amount = Number(value || 0)
    if (!Number.isFinite(amount)) {
        return '0'
    }
    return Number.isInteger(amount) ? String(amount) : amount.toFixed(2)
}

const getLevelShortLabel = (level: RegionLevel) => {
    if (level === REGION_LEVEL_PROVINCE) return '省'
    if (level === REGION_LEVEL_DISTRICT) return '区'
    return '市'
}

const getRuleTone = (level: RegionLevel) => {
    if (level === REGION_LEVEL_PROVINCE) return 'province'
    if (level === REGION_LEVEL_DISTRICT) return 'district'
    return 'city'
}

const buildRegionRuleLabel = (item: Partial<RegionPriceRow>) => {
    const level = normalizeRegionLevel(item.region_level)
    if (level === REGION_LEVEL_PROVINCE) {
        return item.province_name || item.province_code || '未选择省份'
    }
    if (level === REGION_LEVEL_CITY) {
        return (
            [item.province_name || item.province_code, item.city_name || item.city_code]
                .filter(Boolean)
                .join(' / ') || '未选择城市'
        )
    }
    return (
        [
            item.province_name || item.province_code,
            item.city_name || item.city_code,
            item.district_name || item.district_code
        ]
            .filter(Boolean)
            .join(' / ') || '未选择区县'
    )
}

const buildRegionRuleKey = (item: Partial<RegionPriceRow>) => {
    return [
        normalizeRegionLevel(item.region_level),
        item.province_code || '',
        item.city_code || '',
        item.district_code || ''
    ].join(':')
}

const getRegionItemStyle = (active: boolean) => {
    if (!active) {
        return {}
    }

    return {
        background: alphaColor($theme.primaryColor || '#0B0B0B', 0.1),
        borderColor: alphaColor($theme.primaryColor || '#0B0B0B', 0.28),
        color: $theme.primaryColor || '#0B0B0B'
    }
}

const resetRuleDraft = (level: RegionLevel = REGION_LEVEL_CITY) => {
    Object.assign(ruleDraft, createEmptyRuleDraft(level))
}

const handleProvinceSelect = (province: RegionProvince) => {
    ruleDraft.province_code = province.province_code
    ruleDraft.province_name = province.province_name
    ruleDraft.city_code = ''
    ruleDraft.city_name = ''
    ruleDraft.district_code = ''
    ruleDraft.district_name = ''
}

const handleCitySelect = (city: RegionCity) => {
    ruleDraft.city_code = city.city_code
    ruleDraft.city_name = city.city_name
    ruleDraft.district_code = ''
    ruleDraft.district_name = ''
}

const handleDistrictSelect = (district: RegionDistrict) => {
    ruleDraft.district_code = district.district_code
    ruleDraft.district_name = district.district_name
}

const applyDefaultRegionSelection = (level: RegionLevel) => {
    const province = regionProvinces.value[0]
    if (!province) {
        return
    }

    handleProvinceSelect(province)
    if (level === REGION_LEVEL_PROVINCE) {
        return
    }

    const city = province.cities[0]
    if (!city) {
        return
    }

    handleCitySelect(city)
    if (level !== REGION_LEVEL_DISTRICT) {
        return
    }

    const district = city.districts[0]
    if (district) {
        handleDistrictSelect(district)
    }
}

const switchRuleLevel = (level: RegionLevel) => {
    if (ruleDraft.region_level === level) {
        return
    }

    ruleDraft.region_level = level
    if (level === REGION_LEVEL_PROVINCE) {
        ruleDraft.city_code = ''
        ruleDraft.city_name = ''
        ruleDraft.district_code = ''
        ruleDraft.district_name = ''
        return
    }

    if (level === REGION_LEVEL_CITY) {
        ruleDraft.district_code = ''
        ruleDraft.district_name = ''
    }
}

const closeRulePopup = () => {
    showRulePopup.value = false
    editingRuleIndex.value = -1
    resetRuleDraft()
}

const openCreateRule = (level: RegionLevel) => {
    if (!regionProvinces.value.length) {
        uni.showToast({ title: '暂无可用服务地区', icon: 'none' })
        return
    }

    editingRuleIndex.value = -1
    resetRuleDraft(level)
    applyDefaultRegionSelection(level)
    showRulePopup.value = true
}

const openEditRule = (item: RegionPriceRow, index: number) => {
    editingRuleIndex.value = index
    Object.assign(ruleDraft, {
        region_level: normalizeRegionLevel(item.region_level),
        province_code: item.province_code || '',
        province_name: item.province_name || '',
        city_code: item.city_code || '',
        city_name: item.city_name || '',
        district_code: item.district_code || '',
        district_name: item.district_name || '',
        price: item.price !== undefined && item.price !== null ? String(item.price) : ''
    })
    showRulePopup.value = true
}

const removeRule = (index: number) => {
    uni.showModal({
        title: '确认删除',
        content: '确定删除这条地区价格吗？',
        success: (res) => {
            if (res.confirm) {
                form.region_prices.splice(index, 1)
            }
        }
    })
}

const saveRule = () => {
    const rulePriceText = normalizeTextValue(ruleDraft.price)
    if (!rulePriceText) {
        uni.showToast({ title: '请输入地区售价', icon: 'none' })
        return
    }

    const price = Number(rulePriceText)
    if (!Number.isFinite(price) || price < 0) {
        uni.showToast({ title: '地区售价格式不正确', icon: 'none' })
        return
    }

    const level = normalizeRegionLevel(ruleDraft.region_level)
    if (level === REGION_LEVEL_PROVINCE && !ruleDraft.province_code) {
        uni.showToast({ title: '请选择省份', icon: 'none' })
        return
    }
    if (level !== REGION_LEVEL_PROVINCE && !ruleDraft.city_code) {
        uni.showToast({ title: '请选择城市', icon: 'none' })
        return
    }
    if (level === REGION_LEVEL_DISTRICT && !ruleDraft.district_code) {
        uni.showToast({ title: '请选择区县', icon: 'none' })
        return
    }

    const nextRow: RegionPriceRow = {
        region_level: level,
        province_code: ruleDraft.province_code,
        province_name: ruleDraft.province_name,
        city_code: level === REGION_LEVEL_PROVINCE ? '' : ruleDraft.city_code,
        city_name: level === REGION_LEVEL_PROVINCE ? '' : ruleDraft.city_name,
        district_code: level === REGION_LEVEL_DISTRICT ? ruleDraft.district_code : '',
        district_name: level === REGION_LEVEL_DISTRICT ? ruleDraft.district_name : '',
        price
    }

    const nextKey = buildRegionRuleKey(nextRow)
    const conflictIndex = form.region_prices.findIndex((item, index) => {
        if (editingRuleIndex.value >= 0 && index === editingRuleIndex.value) {
            return false
        }
        return buildRegionRuleKey(item) === nextKey
    })

    if (conflictIndex >= 0) {
        uni.showToast({ title: '该地区已存在价格规则', icon: 'none' })
        return
    }

    if (editingRuleIndex.value >= 0) {
        form.region_prices.splice(editingRuleIndex.value, 1, nextRow)
    } else {
        form.region_prices.push(nextRow)
    }
    form.region_prices = sortRegionPriceRows(form.region_prices)

    closeRulePopup()
}

const loadRegionTree = async () => {
    try {
        const data = await getServiceRegionTree()
        const list = Array.isArray(data)
            ? data
            : Array.isArray(data?.data)
            ? data.data
            : Array.isArray(data?.list)
            ? data.list
            : []
        regionTree.value = normalizeProvinces(list)
    } catch (error: any) {
        regionTree.value = []
        const msg =
            typeof error === 'string' ? error : error?.msg || error?.message || '获取服务地区失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const loadAddonOptions = async () => {
    try {
        const data = await staffCenterAddonLists({ page_size: 200 })
        addonOptions.value = Array.isArray(data?.data) ? data.data : []
    } catch (error: any) {
        addonOptions.value = []
        const msg =
            typeof error === 'string' ? error : error?.msg || error?.message || '获取附加项失败'
        uni.showToast({ title: msg, icon: 'none' })
    }
}

const loadDetail = async (packageId: number) => {
    const data = await staffCenterPackageDetail({ package_id: packageId })
    fillForm(data || {})
}

const previewCover = () => {
    if (!form.image) {
        return
    }

    uni.previewImage({
        urls: [form.image],
        current: form.image
    })
}

const chooseCover = () => {
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            uni.showLoading({ title: '上传中...' })
            try {
                const uploadRes = await uploadImage(res.tempFilePaths[0])
                form.image = uploadRes?.uri || uploadRes?.url || ''
            } catch (error: any) {
                const msg =
                    typeof error === 'string' ? error : error?.msg || error?.message || '上传失败'
                uni.showToast({ title: msg, icon: 'none' })
            } finally {
                uni.hideLoading()
            }
        }
    })
}

const removeCover = () => {
    uni.showModal({
        title: '提示',
        content: '确定删除封面图吗？',
        success: (res) => {
            if (res.confirm) {
                form.image = ''
            }
        }
    })
}

const handleCancel = () => {
    uni.showModal({
        title: '提示',
        content: '确定放弃当前编辑吗？',
        success: (res) => {
            if (res.confirm) {
                uni.navigateBack()
            }
        }
    })
}

const handleSave = async () => {
    const nameText = normalizeTextValue(form.name)
    const priceText = normalizeTextValue(form.price)
    const imageText = normalizeTextValue(form.image)
    const descriptionText = normalizeTextValue(form.description)

    if (!nameText) {
        uni.showToast({ title: '请输入套餐名称', icon: 'none' })
        return
    }
    if (!priceText) {
        uni.showToast({ title: '请输入套餐价格', icon: 'none' })
        return
    }
    if (!imageText) {
        uni.showToast({ title: '请上传封面图', icon: 'none' })
        return
    }

    const payload: any = {
        name: nameText,
        price: Number(priceText || 0),
        original_price:
            normalizeTextValue(form.original_price) === '' ? 0 : Number(form.original_price),
        image: imageText,
        description: descriptionText,
        region_prices: normalizeRegionPriceRows(form.region_prices),
        addon_ids: [...form.addon_ids],
        duration: Number(normalizeTextValue(form.duration) || 0),
        sort: Number(normalizeTextValue(form.sort) || 0),
        is_show: form.is_show,
        is_recommend: form.is_recommend
    }

    saving.value = true
    try {
        if (form.package_id) {
            await staffCenterPackageUpdate({
                ...payload,
                package_id: form.package_id
            })
        } else {
            await staffCenterPackageAdd(payload)
        }
        uni.showToast({ title: '保存成功', icon: 'success' })
        setTimeout(() => uni.navigateBack(), 1200)
    } catch (e: any) {
        const msg = typeof e === 'string' ? e : e?.msg || e?.message || '保存失败'
        uni.showToast({ title: msg, icon: 'none' })
    } finally {
        saving.value = false
    }
}

onLoad(async (options: any) => {
    if (!(await ensureStaffCenterAccess())) return

    const packageId = Number(options?.package_id || options?.id || 0)
    const instance = getCurrentInstance()
    const channel = instance?.proxy?.getOpenerEventChannel?.()
    channel?.on('detail', (data: any) => fillForm(data))

    await loadRegionTree()
    await loadAddonOptions()

    if (packageId > 0) {
        try {
            await loadDetail(packageId)
        } catch (error: any) {
            const msg =
                typeof error === 'string'
                    ? error
                    : error?.msg || error?.message || '加载套餐详情失败'
            uni.showToast({ title: msg, icon: 'none' })
        }
    }
})
</script>

<style lang="scss" scoped>
.page-container {
    min-height: 100vh;
    padding-top: 20rpx;
    padding-bottom: calc(180rpx + env(safe-area-inset-bottom));
    box-sizing: border-box;
    background: radial-gradient(
            circle at top left,
            rgba(11, 11, 11, 0.1) 0,
            rgba(248, 247, 242, 0) 36%
        ),
        linear-gradient(180deg, var(--wm-color-bg-page, #ffffff) 0%, #f8f7f2 100%);
}

.form-card {
    overflow: hidden;
}

.page-section {
    display: flex;
    flex-direction: column;
    gap: 16rpx;
    padding: 0 var(--wm-space-page-x, 37rpx);
    box-sizing: border-box;
}

.page-section--content {
    padding-top: 20rpx;
}

.form-card + .form-card {
    margin-top: 18rpx;
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 22rpx;
}

.card-head__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.field-block + .field-block {
    margin-top: 22rpx;
}

.field-block--compact {
    margin-top: 20rpx;
}

.field-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.field-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 16rpx;
}

.field-label {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.field-label--required::before {
    content: '*';
    margin-right: 6rpx;
    color: var(--wm-color-primary, #0b0b0b);
}

.field-side-text {
    flex-shrink: 0;
    font-size: 22rpx;
    font-weight: 600;
    line-height: 1;
    color: var(--wm-text-tertiary, #9a9388);
}

.field-input-shell,
.textarea-shell {
    border-radius: 28rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    overflow: hidden;
}

.field-input-shell {
    min-height: 94rpx;
    padding: 0 24rpx;
    display: flex;
    align-items: center;
}

.field-input,
.setting-input {
    width: 100%;
}

.textarea-shell {
    padding: 22rpx 24rpx;
}

.field-input :deep(.tn-input),
.setting-input :deep(.tn-input) {
    background: transparent !important;
}

.field-input :deep(.input-placeholder),
.setting-input :deep(.input-placeholder) {
    color: #9a9388 !important;
}

.field-input :deep(.input-text),
.setting-input :deep(.input-text) {
    font-size: 28rpx !important;
    color: #111111 !important;
}

.field-textarea {
    width: 100%;
    min-height: 220rpx;
    font-size: 28rpx;
    line-height: 1.65;
    color: var(--wm-text-primary, #111111);
}

.cover-preview,
.upload-panel--cover {
    height: 392rpx;
}

.cover-preview {
    position: relative;
    width: 100%;
    overflow: hidden;
    border-radius: 32rpx;
    background: #f8f7f2;
}

.cover-preview__image {
    width: 100%;
    height: 100%;
    display: block;
}

.cover-preview__toolbar {
    position: absolute;
    left: 16rpx;
    right: 16rpx;
    bottom: 16rpx;
    min-height: 68rpx;
    display: flex;
    align-items: center;
    border-radius: 999rpx;
    background: rgba(11, 11, 11, 0.48);
    backdrop-filter: blur(14rpx);
    -webkit-backdrop-filter: blur(14rpx);
}

.cover-preview__action {
    flex: 1;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
}

.cover-preview__action-text {
    font-size: 24rpx;
    font-weight: 600;
    color: #ffffff;
}

.cover-preview__divider {
    width: 1rpx;
    height: 30rpx;
    background: rgba(255, 255, 255, 0.24);
}

.upload-panel {
    width: 100%;
    border-radius: 32rpx;
    border: 1rpx dashed rgba(216, 194, 138, 0.88);
    background: linear-gradient(
        180deg,
        rgba(248, 247, 242, 0.9) 0%,
        rgba(255, 255, 255, 0.72) 100%
    );
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14rpx;
}

.upload-panel__icon-wrap {
    width: 108rpx;
    height: 108rpx;
    border-radius: 999rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid rgba(216, 194, 138, 0.72);
}

.upload-panel__title {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-primary, #111111);
}

.metric-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12rpx;
}

.metric-chip {
    display: flex;
    flex-direction: column;
    gap: 8rpx;
    padding: 18rpx 20rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.76);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.metric-chip__label {
    font-size: 21rpx;
    font-weight: 700;
    line-height: 1.2;
    color: var(--wm-text-secondary, #5f5a50);
}

.metric-chip__value {
    font-size: 38rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-text-primary, #111111);
}

.action-chip-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 20rpx;
}

.action-chip {
    min-height: 64rpx;
    padding: 0 24rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    font-size: 24rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }
}

.action-chip--accent {
    background: var(--wm-color-primary-soft, #f3f2ee);
    border-color: var(--wm-color-border-strong, #d8c28a);
    color: var(--wm-color-primary, #0b0b0b);
}

.rule-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    margin-top: 22rpx;
}

.rule-row {
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding: 20rpx 22rpx;
    border-radius: 28rpx;
    background: rgba(255, 255, 255, 0.78);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.rule-row__main {
    flex: 1;
    min-width: 0;
}

.rule-row__head {
    display: flex;
    align-items: center;
    gap: 12rpx;
}

.rule-badge {
    min-width: 48rpx;
    height: 48rpx;
    padding: 0 12rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
}

.rule-badge__text {
    font-size: 22rpx;
    font-weight: 700;
    line-height: 1;
}

.rule-badge--province {
    background: rgba(200, 164, 93, 0.14);
    color: #9F7A2E;
}

.rule-badge--city {
    background: rgba(11, 11, 11, 0.12);
    color: var(--wm-color-primary, #0b0b0b);
}

.rule-badge--district {
    background: rgba(77, 74, 66, 0.12);
    color: #4d4a42;
}

.rule-row__price {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1;
    color: var(--wm-color-primary, #0b0b0b);
}

.rule-row__title {
    display: block;
    margin-top: 12rpx;
    font-size: 25rpx;
    font-weight: 600;
    line-height: 1.45;
    color: var(--wm-text-primary, #111111);
    word-break: break-all;
}

.rule-row__actions {
    display: flex;
    flex-shrink: 0;
    gap: 12rpx;
}

.rule-row__action {
    min-width: 96rpx;
    min-height: 56rpx;
    padding: 0 18rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid rgba(11, 11, 11, 0.18);
    font-size: 22rpx;
    font-weight: 700;
    color: var(--wm-color-primary, #0b0b0b);
}

.rule-row__action--danger {
    color: var(--wm-color-danger, #5a4433);
    border-color: rgba(90, 68, 51, 0.14);
    background: rgba(90, 68, 51, 0.08);
}

.empty-panel {
    margin-top: 22rpx;
    padding: 42rpx 28rpx;
    border-radius: 30rpx;
    border: 1rpx dashed rgba(216, 194, 138, 0.88);
    background: rgba(255, 255, 255, 0.66);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 14rpx;
}

.empty-panel--compact {
    margin-top: 0;
}

.empty-panel__icon {
    width: 96rpx;
    height: 96rpx;
    border-radius: 32rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        180deg,
        rgba(248, 247, 242, 0.96) 0%,
        rgba(255, 255, 255, 0.74) 100%
    );
}

.empty-panel__title {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.addon-chip-list {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
}

.addon-chip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 22rpx 24rpx;
    border-radius: 28rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.addon-chip--active {
    border-color: var(--wm-color-border-strong, #d8c28a);
    background: var(--wm-color-primary-soft, #f3f2ee);
}

.addon-chip--inactive {
    opacity: 0.66;
}

.addon-chip__copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.addon-chip__title {
    font-size: 28rpx;
    font-weight: 700;
    line-height: 1.4;
    color: var(--wm-text-primary, #111111);
}

.addon-chip__meta {
    font-size: 24rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #5f5a50);
}

.addon-chip__check {
    flex-shrink: 0;
    width: 48rpx;
    height: 48rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.82);
    color: var(--wm-color-primary, #0b0b0b);
    font-size: 28rpx;
    font-weight: 700;
}

.setting-list {
    border-radius: 30rpx;
    background: #ffffff;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    overflow: hidden;
}

.setting-item {
    min-height: 98rpx;
    padding: 0 22rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    border-bottom: 1rpx solid rgba(231, 226, 214, 0.9);
    box-sizing: border-box;

    &:last-child {
        border-bottom: none;
    }
}

.setting-item__label {
    flex-shrink: 0;
    font-size: 28rpx;
    font-weight: 600;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.setting-item__input {
    flex: 1;
    min-width: 0;
    display: flex;
    justify-content: flex-end;
}

.setting-item__input--sm {
    max-width: 200rpx;
}

.setting-input--right :deep(.tn-input) {
    justify-content: flex-end !important;
}

.setting-input--right :deep(.input-text),
.setting-input--right :deep(.input-placeholder) {
    text-align: right !important;
}

.bottom-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 40;
    padding: 12rpx var(--wm-space-page-x, 37rpx) calc(20rpx + env(safe-area-inset-bottom));
    background: rgba(248, 247, 242, 0.88);
    border-top: 1rpx solid rgba(231, 226, 214, 0.9);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
    box-sizing: border-box;
}

.bottom-bar__inner {
    display: flex;
    gap: 12rpx;
}

.bottom-bar__action {
    min-height: 88rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    border-radius: 36rpx;
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(2rpx);
        opacity: 0.92;
    }
}

.bottom-bar__action--ghost {
    flex: 1;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
}

.bottom-bar__action--primary {
    flex: 1.35;
    background: linear-gradient(135deg, var(--wm-color-primary, #0b0b0b) 0%, #9f7a2e 100%);
    box-shadow: 0 14rpx 28rpx rgba(11, 11, 11, 0.18);
}

.bottom-bar__action-text {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1;
    color: #ffffff;
}

.bottom-bar__action-text--ghost {
    color: var(--wm-text-primary, #111111);
}

.bottom-bar__loading {
    animation: rotate 1s linear infinite;
}

.picker {
    overflow: hidden;
    border-radius: 44rpx 44rpx 0 0;
    background: linear-gradient(180deg, rgba(248, 247, 242, 0.98) 0%, #f8f7f2 100%);
}

.picker__head {
    min-height: 108rpx;
    padding: 0 30rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    border-bottom: 1rpx solid rgba(231, 226, 214, 0.9);
}

.picker__action {
    min-width: 88rpx;
    font-size: 26rpx;
    font-weight: 700;
    color: var(--wm-text-secondary, #5f5a50);
}

.picker__action--primary {
    text-align: right;
    color: var(--wm-color-primary, #0b0b0b);
}

.picker__title {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1.3;
    color: var(--wm-text-primary, #111111);
}

.picker__body {
    padding: 26rpx 30rpx calc(34rpx + env(safe-area-inset-bottom));
}

.level-chip-row {
    display: flex;
    gap: 12rpx;
    margin-bottom: 22rpx;
}

.level-chip {
    flex: 1;
    min-height: 72rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.82);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    font-size: 24rpx;
    font-weight: 700;
    color: var(--wm-text-secondary, #5f5a50);
}

.level-chip--active {
    color: var(--wm-color-primary, #0b0b0b);
    background: var(--wm-color-primary-soft, #f3f2ee);
    border-color: var(--wm-color-border-strong, #d8c28a);
    box-shadow: 0 8rpx 18rpx rgba(11, 11, 11, 0.12);
}

.region-picker {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14rpx;
    margin-top: 20rpx;
}

.region-picker__col {
    min-width: 0;
    border-radius: 30rpx;
    overflow: hidden;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    background: rgba(255, 255, 255, 0.82);
}

.region-picker__title {
    min-height: 68rpx;
    padding: 0 22rpx;
    display: flex;
    align-items: center;
    font-size: 24rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #111111);
    border-bottom: 1rpx solid rgba(231, 226, 214, 0.9);
}

.region-picker__scroll {
    height: 420rpx;
}

.region-picker__item {
    min-height: 72rpx;
    padding: 0 22rpx;
    display: flex;
    align-items: center;
    border-bottom: 1rpx solid rgba(231, 226, 214, 0.82);
    font-size: 24rpx;
    font-weight: 600;
    line-height: 1.4;
    color: var(--wm-text-secondary, #5f5a50);
    box-sizing: border-box;

    &:last-child {
        border-bottom: none;
    }
}

@keyframes rotate {
    from {
        transform: rotate(0deg);
    }

    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 720rpx) {
    .field-grid,
    .metric-row,
    .region-picker {
        grid-template-columns: 1fr;
    }

    .rule-row {
        flex-wrap: wrap;
    }

    .rule-row__actions {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
