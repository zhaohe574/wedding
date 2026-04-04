<template>
    <div class="package-region-price-editor">
        <div class="editor-summary">
            <div class="editor-summary__info">
                <div class="editor-summary__metrics">
                    <div class="summary-pill">
                        共 <span class="summary-pill__value">{{ summary.total }}</span> 条
                    </div>
                    <div class="summary-pill">
                        省 <span class="summary-pill__value">{{ summary.provinceCount }}</span>
                    </div>
                    <div class="summary-pill">
                        市 <span class="summary-pill__value">{{ summary.cityCount }}</span>
                    </div>
                    <div class="summary-pill">
                        区 <span class="summary-pill__value">{{ summary.districtCount }}</span>
                    </div>
                </div>
            </div>
            <div class="editor-actions">
                <el-button size="small" plain :disabled="!cityOptions.length" @click="openCreateDrawer(REGION_LEVEL_PROVINCE)">
                    新增省级价
                </el-button>
                <el-button size="small" plain :disabled="!cityOptions.length" @click="openCreateDrawer(REGION_LEVEL_CITY)">
                    新增城市价
                </el-button>
                <el-button
                    size="small"
                    type="primary"
                    plain
                    :disabled="!cityOptions.length"
                    @click="openCreateDrawer(REGION_LEVEL_DISTRICT)"
                >
                    新增区县价
                </el-button>
            </div>
        </div>

        <el-alert
            v-if="!cityOptions.length"
            type="warning"
            :closable="false"
            title="请先在“服务地区”中配置可接单城市"
        />

        <template v-else>
            <div class="editor-panel">
                <div class="editor-controls">
                    <el-input
                        v-model="searchKeyword"
                        class="editor-controls__search"
                        clearable
                        size="small"
                        placeholder="搜索省、市、区县"
                    >
                        <template #prefix>
                            <el-icon><Search /></el-icon>
                        </template>
                    </el-input>
                    <div class="editor-controls__switch">
                        <span class="editor-controls__label">仅看已配置</span>
                        <el-switch v-model="onlyConfigured" size="small" />
                    </div>
                </div>

                <template v-if="hasVisibleResults">
                    <div class="editor-sections">
                        <section class="editor-section">
                            <button type="button" class="editor-section__header" @click="toggleProvinceSection">
                                <span class="editor-section__left">
                                    <el-icon class="editor-section__arrow">
                                        <component :is="isProvinceRulesExpanded ? ArrowDown : ArrowRight" />
                                    </el-icon>
                                    <span class="editor-section__title">省级规则</span>
                                    <span class="editor-section__count">{{ visibleProvinceRules.length }} 条</span>
                                </span>
                                <span class="editor-section__preview">{{ provinceRulePreview }}</span>
                            </button>

                            <div v-show="isProvinceRulesExpanded" class="editor-section__body">
                                <div v-if="visibleProvinceRules.length" class="editor-rule-list">
                                    <div
                                        v-for="rule in visibleProvinceRules"
                                        :key="rule._key"
                                        class="editor-rule-row"
                                    >
                                        <div class="editor-rule-row__main">
                                            <div class="editor-rule-row__title">{{ buildRegionLabel(rule) }}</div>
                                            <div class="editor-rule-row__meta">{{ getRangeHint(rule) }}</div>
                                        </div>
                                        <div class="editor-rule-row__price">¥{{ formatPrice(rule.price) }}</div>
                                        <div class="editor-rule-row__actions">
                                            <el-button size="small" type="primary" link @click="openEditDrawer(rule)">
                                                编辑
                                            </el-button>
                                            <el-button size="small" type="danger" link @click="handleDeleteRule(rule._key)">
                                                删除
                                            </el-button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="editor-section__empty">暂无匹配的省级规则</div>
                            </div>
                        </section>

                        <section class="editor-section">
                            <div class="editor-section__header editor-section__header--static">
                                <span class="editor-section__left">
                                    <span class="editor-section__title">城市规则树</span>
                                    <span class="editor-section__count">{{ visibleCityCount }} 个城市</span>
                                </span>
                                <span class="editor-section__preview">{{ cityTreePreview }}</span>
                            </div>

                            <div class="editor-section__body">
                                <div v-if="visibleProvinceGroups.length" class="province-tree">
                                    <div
                                        v-for="province in visibleProvinceGroups"
                                        :key="province._key"
                                        class="province-tree-node"
                                    >
                                        <button
                                            type="button"
                                            class="province-tree-node__header"
                                            @click="toggleProvinceNode(province._key)"
                                        >
                                            <span class="province-tree-node__left">
                                                <el-icon class="province-tree-node__arrow">
                                                    <component :is="isProvinceExpanded(province._key) ? ArrowDown : ArrowRight" />
                                                </el-icon>
                                                <span class="province-tree-node__title">
                                                    {{ province.province_name || province.province_code || '未命名省份' }}
                                                </span>
                                            </span>
                                            <span class="province-tree-node__meta">{{ province.cityNodes.length }} 个城市</span>
                                        </button>

                                        <div v-show="isProvinceExpanded(province._key)" class="province-tree-node__body">
                                            <div
                                                v-for="city in province.cityNodes"
                                                :key="city._key"
                                                class="city-tree-node"
                                                :class="{ 'is-empty': !cityHasRules(city) }"
                                            >
                                                <div class="city-tree-node__top">
                                                    <button
                                                        type="button"
                                                        class="city-tree-node__header"
                                                        @click="toggleCityNode(city._key)"
                                                    >
                                                        <span class="city-tree-node__left">
                                                            <el-icon class="city-tree-node__arrow">
                                                                <component :is="isCityExpanded(city._key) ? ArrowDown : ArrowRight" />
                                                            </el-icon>
                                                            <span class="city-tree-node__info">
                                                                <span class="city-tree-node__title">
                                                                    {{ buildDisplayCityLabel(city) }}
                                                                </span>
                                                                <span class="city-tree-node__price">
                                                                    {{ buildCityNodePriceSummary(city) }}
                                                                </span>
                                                            </span>
                                                        </span>
                                                        <span class="city-tree-node__summary">
                                                            <span
                                                                v-for="badge in buildCityNodeBadges(city)"
                                                                :key="`${city._key}_${badge.label}`"
                                                                class="city-tree-badge"
                                                            >
                                                                {{ badge.label }}
                                                                <span class="city-tree-badge__value">{{ badge.value }}</span>
                                                            </span>
                                                        </span>
                                                    </button>

                                                    <div class="city-tree-node__actions">
                                                        <el-button
                                                            size="small"
                                                            type="primary"
                                                            link
                                                            @click.stop="handleEditCityNode(city)"
                                                        >
                                                            编辑
                                                        </el-button>
                                                        <el-button
                                                            v-if="city.cityRule"
                                                            size="small"
                                                            type="danger"
                                                            link
                                                            @click.stop="handleDeleteRule(city.cityRule._key)"
                                                        >
                                                            删除
                                                        </el-button>
                                                    </div>
                                                </div>

                                                <div v-show="isCityExpanded(city._key)" class="city-tree-node__body">
                                                    <div v-if="city.cityRule" class="editor-rule-row">
                                                        <div class="editor-rule-row__main">
                                                            <div class="editor-rule-row__title">城市统一价</div>
                                                            <div class="editor-rule-row__meta">
                                                                未命中区县价时，按该城市统一价格匹配
                                                            </div>
                                                        </div>
                                                        <div class="editor-rule-row__price">
                                                            ¥{{ formatPrice(city.cityRule.price) }}
                                                        </div>
                                                    </div>

                                                    <div
                                                        v-for="districtGroup in city.districtGroups"
                                                        :key="districtGroup._key"
                                                        class="editor-district-row"
                                                    >
                                                        <div class="editor-district-row__price">
                                                            ¥{{ formatPrice(districtGroup.price) }}
                                                        </div>
                                                        <div class="editor-district-row__main">
                                                            <el-tooltip placement="top-start" effect="dark">
                                                                <template #content>
                                                                    <div class="district-tooltip">
                                                                        {{ buildDistrictTooltip(districtGroup) }}
                                                                    </div>
                                                                </template>
                                                                <span class="editor-district-row__text">
                                                                    {{ buildDistrictPreview(districtGroup) }}
                                                                </span>
                                                            </el-tooltip>
                                                            <span class="editor-district-row__meta">
                                                                覆盖 {{ districtGroup.district_codes.length }} 个区县
                                                            </span>
                                                        </div>
                                                        <div class="editor-district-row__actions">
                                                            <el-button
                                                                size="small"
                                                                type="primary"
                                                                link
                                                                @click="openEditDrawer(districtGroup)"
                                                            >
                                                                编辑
                                                            </el-button>
                                                            <el-button
                                                                size="small"
                                                                type="danger"
                                                                link
                                                                @click="handleDeleteRule(districtGroup._key)"
                                                            >
                                                                删除
                                                            </el-button>
                                                        </div>
                                                    </div>

                                                    <div
                                                        v-if="!city.cityRule && !city.districtGroups.length"
                                                        class="city-tree-node__empty"
                                                    >
                                                        该城市暂未配置地区规则
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-else class="editor-section__empty">暂无匹配的城市规则</div>
                            </div>
                        </section>
                    </div>
                </template>

                <el-empty v-else :description="emptyDescription" />
            </div>
        </template>

        <el-drawer
            v-model="drawerVisible"
            append-to-body
            destroy-on-close
            size="560px"
            :title="drawerTitle"
            class="region-price-drawer"
        >
            <div class="drawer-intro">
                <div class="drawer-intro__title">{{ getLevelLabel(drawerForm.region_level) }}</div>
            </div>

            <el-form label-width="92px" class="drawer-form">
                <el-form-item label="价格层级">
                    <el-input :model-value="getLevelLabel(drawerForm.region_level)" disabled />
                </el-form-item>

                <el-form-item v-if="drawerForm.region_level === REGION_LEVEL_PROVINCE" label="服务省份" required>
                    <el-select
                        v-model="drawerForm.province_code"
                        class="w-full"
                        clearable
                        filterable
                        placeholder="请选择已开通省份"
                        @change="handleDrawerProvinceChange"
                    >
                        <el-option
                            v-for="province in provinceOptions"
                            :key="province.province_code"
                            :label="province.label"
                            :value="province.province_code"
                        />
                    </el-select>
                </el-form-item>

                <el-form-item v-else label="服务城市" required>
                    <el-select
                        v-model="drawerForm.city_code"
                        class="w-full"
                        clearable
                        filterable
                        placeholder="请选择可接单城市"
                        @change="handleDrawerCityChange"
                    >
                        <el-option
                            v-for="city in cityOptions"
                            :key="city.city_code"
                            :label="city.label"
                            :value="city.city_code"
                        />
                    </el-select>
                </el-form-item>

                <el-form-item v-if="drawerForm.region_level !== REGION_LEVEL_PROVINCE" label="所属省份">
                    <el-input :model-value="drawerForm.province_name || '选择城市后自动带出'" disabled />
                </el-form-item>

                <el-form-item
                    v-if="drawerForm.region_level === REGION_LEVEL_DISTRICT"
                    label="服务区县"
                    required
                >
                    <el-select
                        v-model="drawerForm.district_codes"
                        class="w-full"
                        clearable
                        collapse-tags
                        collapse-tags-tooltip
                        filterable
                        multiple
                        :disabled="!drawerForm.city_code"
                        placeholder="同一城市内可多选多个区县并统一定价"
                        @change="handleDrawerDistrictChange"
                    >
                        <el-option
                            v-for="district in getDistrictOptions(drawerForm.city_code)"
                            :key="district.district_code"
                            :label="district.district_name"
                            :value="district.district_code"
                        />
                    </el-select>
                </el-form-item>

                <el-form-item v-if="drawerForm.region_level === REGION_LEVEL_DISTRICT" label="已选区县">
                    <div v-if="drawerForm.district_codes.length" class="district-selected-list">
                        <el-tag
                            v-for="(districtName, index) in drawerForm.district_names"
                            :key="`${drawerForm.district_codes[index] || index}_${districtName}`"
                            size="small"
                            effect="plain"
                        >
                            {{ districtName }}
                        </el-tag>
                    </div>
                    <span v-else class="table-muted">未选择区县</span>
                </el-form-item>

                <el-form-item label="地区售价" required>
                    <el-input-number
                        v-model="drawerForm.price"
                        class="w-full"
                        :min="0"
                        :precision="2"
                        :step="10"
                    />
                </el-form-item>
            </el-form>

            <template #footer>
                <div class="drawer-footer">
                    <el-button @click="drawerVisible = false">取消</el-button>
                    <el-button type="primary" @click="handleSaveDrawer">保存规则</el-button>
                </div>
            </template>
        </el-drawer>
    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { ArrowDown, ArrowRight, Search } from '@element-plus/icons-vue'
import { regionDistrictOptions, regionEnabledCityOptions } from '@/api/service'
import {
    myProfileRegionDistrictOptions,
    myProfileRegionEnabledCityOptions
} from '@/api/staff-center'
import {
    REGION_LEVEL_CITY,
    REGION_LEVEL_DISTRICT,
    REGION_LEVEL_PROVINCE,
    normalizeRegionLevel,
    normalizeRegionPriceRows,
    summarizeRegionPrices,
    type RegionLevel,
    type RegionPriceRow
} from '@/utils/package-region-price'

type CityOption = {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    label: string
}

type ProvinceOption = {
    province_code: string
    province_name: string
    label: string
}

type DistrictOption = {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
}

type RegionRuleDraft = {
    _key: string
    region_level: RegionLevel
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_codes: string[]
    district_names: string[]
    price: number
}

type RegionDisplayCityNode = {
    _key: string
    province_key: string
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    cityRule: RegionRuleDraft | null
    districtGroups: RegionRuleDraft[]
    totalDistrictCount: number
}

type RegionDisplayProvinceGroup = {
    _key: string
    province_code: string
    province_name: string
    cityNodes: RegionDisplayCityNode[]
}

type RegionDisplayTree = {
    provinceRules: RegionRuleDraft[]
    provinceGroups: RegionDisplayProvinceGroup[]
}

type OptionsApiScene = 'service' | 'staffCenter'

const props = withDefaults(defineProps<{
    modelValue: Record<string, any>[]
    optionsApiScene?: OptionsApiScene
}>(), {
    optionsApiScene: 'service'
})

const emit = defineEmits<{
    (event: 'update:modelValue', value: Record<string, any>[]): void
}>()

const cityOptions = ref<CityOption[]>([])
const districtOptionsMap = ref<Record<string, DistrictOption[]>>({})
const ruleDrafts = ref<RegionRuleDraft[]>([])
const syncingFromOutside = ref(false)
const drawerVisible = ref(false)
const drawerMode = ref<'add' | 'edit'>('add')
const editingKey = ref('')
const searchKeyword = ref('')
const onlyConfigured = ref(true)
const provinceSectionExpanded = ref(false)
const expandedProvinceKeys = ref<string[]>([])
const expandedCityKeys = ref<string[]>([])

const provinceOptions = computed<ProvinceOption[]>(() => {
    const uniqueMap = new Map<string, ProvinceOption>()
    cityOptions.value.forEach((item) => {
        const provinceCode = String(item.province_code || '')
        if (!provinceCode || uniqueMap.has(provinceCode)) {
            return
        }
        uniqueMap.set(provinceCode, {
            province_code: provinceCode,
            province_name: item.province_name,
            label: item.province_name
        })
    })
    return Array.from(uniqueMap.values())
})

const drawerForm = reactive<RegionRuleDraft>({
    _key: '',
    region_level: REGION_LEVEL_CITY,
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_codes: [],
    district_names: [],
    price: 0
})

const summary = computed(() => summarizeRegionPrices(flattenRuleDrafts(ruleDrafts.value)))
const normalizedSearchKeyword = computed(() => searchKeyword.value.trim().toLowerCase())
const displayTree = computed<RegionDisplayTree>(() =>
    buildRegionDisplayTree(ruleDrafts.value, cityOptions.value, onlyConfigured.value)
)
const visibleProvinceRules = computed(() =>
    filterProvinceRules(displayTree.value.provinceRules, normalizedSearchKeyword.value)
)
const visibleProvinceGroups = computed(() =>
    filterProvinceGroups(displayTree.value.provinceGroups, normalizedSearchKeyword.value)
)
const visibleCityCount = computed(() =>
    visibleProvinceGroups.value.reduce((total, group) => total + group.cityNodes.length, 0)
)
const hasVisibleResults = computed(() =>
    visibleProvinceRules.value.length > 0 || visibleProvinceGroups.value.length > 0
)
const isProvinceRulesExpanded = computed(() =>
    normalizedSearchKeyword.value ? visibleProvinceRules.value.length > 0 : provinceSectionExpanded.value
)
const provinceRulePreview = computed(() => {
    if (!visibleProvinceRules.value.length) {
        return normalizedSearchKeyword.value ? '没有匹配的省级规则' : '默认收起，展开后查看'
    }

    return visibleProvinceRules.value
        .slice(0, 2)
        .map((item) => item.province_name || item.province_code)
        .filter(Boolean)
        .join('、')
})
const cityTreePreview = computed(() => {
    if (!visibleProvinceGroups.value.length) {
        return normalizedSearchKeyword.value ? '没有匹配的城市节点' : '默认收起，按省份展开'
    }

    return normalizedSearchKeyword.value
        ? `匹配 ${visibleCityCount.value} 个城市`
        : `已收纳 ${visibleCityCount.value} 个城市`
})
const emptyDescription = computed(() => {
    if (normalizedSearchKeyword.value) {
        return '没有匹配的地区规则'
    }
    if (onlyConfigured.value) {
        return '暂未配置地区价格'
    }
    return '暂无可展示的服务城市'
})

const drawerTitle = computed(() => `${drawerMode.value === 'edit' ? '编辑' : '新增'}${getLevelLabel(drawerForm.region_level)}`)

function normalizeKeywordValue(value: unknown) {
    return String(value || '').trim().toLowerCase()
}

function matchesKeyword(keyword: string, ...values: Array<unknown>) {
    if (!keyword) {
        return true
    }

    return values.some((value) => {
        if (Array.isArray(value)) {
            return value.some((item) => normalizeKeywordValue(item).includes(keyword))
        }
        return normalizeKeywordValue(value).includes(keyword)
    })
}

function buildProvinceGroupKey(provinceCode: string, provinceName: string) {
    return `province:${provinceCode || provinceName || 'unknown'}`
}

function cityHasRules(city: RegionDisplayCityNode) {
    return Boolean(city.cityRule || city.districtGroups.length)
}

function compareByDisplayLabel(labelA: string, labelB: string) {
    return labelA.localeCompare(labelB, 'zh-Hans-CN')
}

function sortProvinceGroups(list: RegionDisplayProvinceGroup[]) {
    return [...list].sort((a, b) =>
        compareByDisplayLabel(
            a.province_name || a.province_code || a._key,
            b.province_name || b.province_code || b._key
        )
    )
}

function sortCityNodes(list: RegionDisplayCityNode[]) {
    return [...list].sort((a, b) => {
        const configuredDiff = Number(cityHasRules(b)) - Number(cityHasRules(a))
        if (configuredDiff !== 0) {
            return configuredDiff
        }

        return compareByDisplayLabel(
            a.city_name || a.city_code || a._key,
            b.city_name || b.city_code || b._key
        )
    })
}

function buildRegionDisplayTree(
    drafts: RegionRuleDraft[],
    options: CityOption[],
    shouldOnlyConfigured: boolean
): RegionDisplayTree {
    const provinceRules = sortRuleDrafts(
        drafts.filter((item) => item.region_level === REGION_LEVEL_PROVINCE)
    )
    const cityNodeMap = new Map<string, RegionDisplayCityNode>()

    const ensureCityNode = (payload: {
        province_code?: string
        province_name?: string
        city_code?: string
        city_name?: string
    }) => {
        const cityCode = String(payload.city_code || '')
        if (!cityCode) {
            return null
        }

        const provinceCode = String(payload.province_code || '')
        const provinceName = String(payload.province_name || '')
        const cityName = String(payload.city_name || '')
        const provinceKey = buildProvinceGroupKey(provinceCode, provinceName)
        const existingNode = cityNodeMap.get(cityCode)

        if (existingNode) {
            existingNode.province_code = existingNode.province_code || provinceCode
            existingNode.province_name = existingNode.province_name || provinceName
            existingNode.city_name = existingNode.city_name || cityName
            existingNode.province_key = buildProvinceGroupKey(
                existingNode.province_code,
                existingNode.province_name
            )
            existingNode._key = `${existingNode.province_key}:city:${existingNode.city_code}`
            return existingNode
        }

        const nextNode: RegionDisplayCityNode = {
            _key: `${provinceKey}:city:${cityCode}`,
            province_key: provinceKey,
            province_code: provinceCode,
            province_name: provinceName,
            city_code: cityCode,
            city_name: cityName,
            cityRule: null,
            districtGroups: [],
            totalDistrictCount: 0
        }
        cityNodeMap.set(cityCode, nextNode)
        return nextNode
    }

    if (!shouldOnlyConfigured) {
        options.forEach((city) => {
            ensureCityNode(city)
        })
    }

    drafts.forEach((draft) => {
        if (draft.region_level === REGION_LEVEL_PROVINCE) {
            return
        }

        const cityNode = ensureCityNode(draft)
        if (!cityNode) {
            return
        }

        if (draft.region_level === REGION_LEVEL_CITY) {
            cityNode.cityRule = draft
            return
        }

        cityNode.districtGroups.push(draft)
    })

    const provinceGroupsMap = new Map<string, RegionDisplayProvinceGroup>()

    Array.from(cityNodeMap.values())
        .filter((cityNode) => (shouldOnlyConfigured ? cityHasRules(cityNode) : true))
        .forEach((cityNode) => {
            cityNode.districtGroups = sortRuleDrafts(
                cityNode.districtGroups.filter((item) => item.region_level === REGION_LEVEL_DISTRICT)
            )
            cityNode.totalDistrictCount = cityNode.districtGroups.reduce(
                (total, item) => total + item.district_codes.length,
                0
            )

            const provinceKey = buildProvinceGroupKey(cityNode.province_code, cityNode.province_name)
            cityNode.province_key = provinceKey
            cityNode._key = `${provinceKey}:city:${cityNode.city_code}`

            const existingGroup = provinceGroupsMap.get(provinceKey)
            if (existingGroup) {
                existingGroup.cityNodes.push(cityNode)
                return
            }

            provinceGroupsMap.set(provinceKey, {
                _key: provinceKey,
                province_code: cityNode.province_code,
                province_name: cityNode.province_name,
                cityNodes: [cityNode]
            })
        })

    return {
        provinceRules,
        provinceGroups: sortProvinceGroups(
            Array.from(provinceGroupsMap.values()).map((group) => ({
                ...group,
                cityNodes: sortCityNodes(group.cityNodes)
            }))
        )
    }
}

function filterProvinceRules(list: RegionRuleDraft[], keyword: string) {
    if (!keyword) {
        return list
    }

    return list.filter((item) => matchesKeyword(keyword, item.province_name, item.province_code))
}

function filterProvinceGroups(list: RegionDisplayProvinceGroup[], keyword: string) {
    if (!keyword) {
        return list
    }

    return list
        .map((provinceGroup) => {
            const provinceMatched = matchesKeyword(
                keyword,
                provinceGroup.province_name,
                provinceGroup.province_code
            )

            if (provinceMatched) {
                return provinceGroup
            }

            const nextCityNodes = provinceGroup.cityNodes
                .map((cityNode) => {
                    const cityMatched = matchesKeyword(
                        keyword,
                        cityNode.province_name,
                        cityNode.province_code,
                        cityNode.city_name,
                        cityNode.city_code
                    )

                    if (cityMatched) {
                        return cityNode
                    }

                    const matchedDistrictGroups = cityNode.districtGroups.filter((group) =>
                        matchesKeyword(keyword, group.district_names, group.district_codes)
                    )

                    if (!matchedDistrictGroups.length) {
                        return null
                    }

                    return {
                        ...cityNode,
                        districtGroups: matchedDistrictGroups,
                        totalDistrictCount: matchedDistrictGroups.reduce(
                            (total, item) => total + item.district_codes.length,
                            0
                        )
                    }
                })
                .filter(Boolean) as RegionDisplayCityNode[]

            if (!nextCityNodes.length) {
                return null
            }

            return {
                ...provinceGroup,
                cityNodes: nextCityNodes
            }
        })
        .filter(Boolean) as RegionDisplayProvinceGroup[]
}

function buildDisplayCityLabel(city: RegionDisplayCityNode) {
    return [city.province_name || city.province_code, city.city_name || city.city_code]
        .filter(Boolean)
        .join(' / ') || '未命名城市'
}

function buildCityNodePriceSummary(city: RegionDisplayCityNode) {
    if (city.cityRule) {
        return `城市统一价 ¥${formatPrice(city.cityRule.price)}`
    }

    if (city.districtGroups.length) {
        return `已配置 ${city.districtGroups.length} 组区县价`
    }

    return '暂未配置价格'
}

function buildCityNodeBadges(city: RegionDisplayCityNode) {
    return [
        { label: '城市价', value: city.cityRule ? 1 : 0 },
        { label: '区县组', value: city.districtGroups.length },
        { label: '区县', value: city.totalDistrictCount }
    ]
}

function toggleExpandedKey(target: typeof expandedProvinceKeys, key: string) {
    if (!key) {
        return
    }

    if (target.value.includes(key)) {
        target.value = target.value.filter((item) => item !== key)
        return
    }

    target.value = [...target.value, key]
}

function ensureExpandedKey(target: typeof expandedProvinceKeys, key: string) {
    if (!key || target.value.includes(key)) {
        return
    }

    target.value = [...target.value, key]
}

function resetExpandedState() {
    provinceSectionExpanded.value = false
    expandedProvinceKeys.value = []
    expandedCityKeys.value = []
}

function toggleProvinceSection() {
    if (normalizedSearchKeyword.value) {
        return
    }

    provinceSectionExpanded.value = !provinceSectionExpanded.value
}

function toggleProvinceNode(key: string) {
    if (normalizedSearchKeyword.value) {
        return
    }

    toggleExpandedKey(expandedProvinceKeys, key)
}

function toggleCityNode(key: string) {
    if (normalizedSearchKeyword.value) {
        return
    }

    toggleExpandedKey(expandedCityKeys, key)
}

function isProvinceExpanded(key: string) {
    return normalizedSearchKeyword.value ? true : expandedProvinceKeys.value.includes(key)
}

function isCityExpanded(key: string) {
    return normalizedSearchKeyword.value ? true : expandedCityKeys.value.includes(key)
}

const createRuleDraft = (level: RegionLevel = REGION_LEVEL_CITY): RegionRuleDraft => ({
    _key: buildDraftKey(),
    region_level: level,
    province_code: '',
    province_name: '',
    city_code: '',
    city_name: '',
    district_codes: [],
    district_names: [],
    price: 0
})

const cloneRuleDraft = (draft: RegionRuleDraft): RegionRuleDraft => ({
    _key: draft._key,
    region_level: draft.region_level,
    province_code: draft.province_code,
    province_name: draft.province_name,
    city_code: draft.city_code,
    city_name: draft.city_name,
    district_codes: [...draft.district_codes],
    district_names: [...draft.district_names],
    price: Number(draft.price ?? 0)
})

function buildDraftKey() {
    return `${Date.now()}_${Math.random().toString(16).slice(2)}`
}

function getLevelLabel(level: RegionLevel) {
    if (level === REGION_LEVEL_PROVINCE) {
        return '省级统一价'
    }
    if (level === REGION_LEVEL_CITY) {
        return '城市统一价'
    }
    return '区县单独价'
}

function getCompactLevelLabel(level: RegionLevel) {
    if (level === REGION_LEVEL_PROVINCE) {
        return '省级价'
    }
    if (level === REGION_LEVEL_CITY) {
        return '城市价'
    }
    return '区县价'
}

function getLevelTagType(level: RegionLevel) {
    if (level === REGION_LEVEL_PROVINCE) {
        return 'success'
    }
    if (level === REGION_LEVEL_CITY) {
        return 'warning'
    }
    return 'danger'
}

function formatPrice(value: number) {
    return Number(value ?? 0).toFixed(2)
}

function serializeRegionRows(list: Record<string, any>[]) {
    return JSON.stringify(
        normalizeRegionPriceRows(list).sort((a, b) =>
            [
                a.region_level - b.region_level,
                a.province_code.localeCompare(b.province_code),
                a.city_code.localeCompare(b.city_code),
                a.district_code.localeCompare(b.district_code),
                Number(a.price) - Number(b.price)
            ].find((value) => value !== 0) || 0
        )
    )
}

function sortRuleDrafts(list: RegionRuleDraft[]) {
    return [...list].sort((a, b) => {
        const labelA = `${a.province_name}${a.city_name}${a.district_names.join('')}`
        const labelB = `${b.province_name}${b.city_name}${b.district_names.join('')}`
        return (
            a.region_level - b.region_level ||
            labelA.localeCompare(labelB) ||
            Number(a.price) - Number(b.price)
        )
    })
}

function getCityOption(cityCode: string) {
    return cityOptions.value.find((item) => item.city_code === cityCode)
}

function getProvinceOption(provinceCode: string) {
    return provinceOptions.value.find((item) => item.province_code === provinceCode)
}

function getDistrictOptions(cityCode: string) {
    return districtOptionsMap.value[String(cityCode || '')] || []
}

function getRegionOptionApi() {
    if (props.optionsApiScene === 'staffCenter') {
        return {
            loadEnabledCities: myProfileRegionEnabledCityOptions,
            loadDistricts: myProfileRegionDistrictOptions
        }
    }

    return {
        loadEnabledCities: regionEnabledCityOptions,
        loadDistricts: regionDistrictOptions
    }
}

async function loadCityOptions() {
    const res = await getRegionOptionApi().loadEnabledCities()
    cityOptions.value = Array.isArray(res) ? res : []
}

async function ensureDistrictOptions(cityCode: string) {
    const normalizedCityCode = String(cityCode || '')
    if (!normalizedCityCode || districtOptionsMap.value[normalizedCityCode]) {
        return
    }

    const res = await getRegionOptionApi().loadDistricts({ city_code: normalizedCityCode })
    districtOptionsMap.value = {
        ...districtOptionsMap.value,
        [normalizedCityCode]: Array.isArray(res) ? res : []
    }
}

async function reloadRegionOptionsByScene() {
    cityOptions.value = []
    districtOptionsMap.value = {}

    try {
        await loadCityOptions()
        const districtCities = Array.from(
            new Set(
                ruleDrafts.value
                    .filter((item) => item.region_level === REGION_LEVEL_DISTRICT)
                    .map((item) => item.city_code)
                    .filter(Boolean)
            )
        )
        await Promise.all(districtCities.map((cityCode) => ensureDistrictOptions(cityCode)))
        ruleDrafts.value.forEach((draft) => {
            if (draft.region_level === REGION_LEVEL_DISTRICT) {
                syncDraftDistrictNames(draft)
            }
        })
    } catch (_error) {
        cityOptions.value = []
        districtOptionsMap.value = {}
    }
}

function groupRegionRows(rows: RegionPriceRow[]) {
    const provinceMap = new Map<string, RegionRuleDraft>()
    const cityMap = new Map<string, RegionRuleDraft>()
    const districtMap = new Map<string, RegionRuleDraft>()

    rows.forEach((row) => {
        if (row.region_level === REGION_LEVEL_PROVINCE) {
            const key = row.province_code
            if (!key || provinceMap.has(key)) {
                return
            }
            provinceMap.set(key, {
                _key: buildDraftKey(),
                region_level: REGION_LEVEL_PROVINCE,
                province_code: row.province_code,
                province_name: row.province_name,
                city_code: '',
                city_name: '',
                district_codes: [],
                district_names: [],
                price: Number(row.price ?? 0)
            })
            return
        }

        if (row.region_level === REGION_LEVEL_CITY) {
            const key = row.city_code
            if (!key || cityMap.has(key)) {
                return
            }
            cityMap.set(key, {
                _key: buildDraftKey(),
                region_level: REGION_LEVEL_CITY,
                province_code: row.province_code,
                province_name: row.province_name,
                city_code: row.city_code,
                city_name: row.city_name,
                district_codes: [],
                district_names: [],
                price: Number(row.price ?? 0)
            })
            return
        }

        const districtKey = `${row.city_code}:${formatPrice(Number(row.price ?? 0))}`
        const current = districtMap.get(districtKey)
        if (!current) {
            districtMap.set(districtKey, {
                _key: buildDraftKey(),
                region_level: REGION_LEVEL_DISTRICT,
                province_code: row.province_code,
                province_name: row.province_name,
                city_code: row.city_code,
                city_name: row.city_name,
                district_codes: row.district_code ? [row.district_code] : [],
                district_names: row.district_name ? [row.district_name] : [],
                price: Number(row.price ?? 0)
            })
            return
        }

        if (row.district_code && !current.district_codes.includes(row.district_code)) {
            current.district_codes.push(row.district_code)
            current.district_names.push(row.district_name || row.district_code)
        }
    })

    return sortRuleDrafts([
        ...Array.from(provinceMap.values()),
        ...Array.from(cityMap.values()),
        ...Array.from(districtMap.values())
    ])
}

function flattenRuleDrafts(list: RegionRuleDraft[]) {
    return list.flatMap((draft) => {
        const price = Number(draft.price ?? 0)
        if (draft.region_level === REGION_LEVEL_PROVINCE) {
            return [{
                region_level: REGION_LEVEL_PROVINCE,
                province_code: draft.province_code,
                province_name: draft.province_name,
                city_code: '',
                city_name: '',
                district_code: '',
                district_name: '',
                price
            }]
        }

        if (draft.region_level === REGION_LEVEL_CITY) {
            return [{
                region_level: REGION_LEVEL_CITY,
                province_code: draft.province_code,
                province_name: draft.province_name,
                city_code: draft.city_code,
                city_name: draft.city_name,
                district_code: '',
                district_name: '',
                price
            }]
        }

        return draft.district_codes.map((districtCode, index) => ({
            region_level: REGION_LEVEL_DISTRICT,
            province_code: draft.province_code,
            province_name: draft.province_name,
            city_code: draft.city_code,
            city_name: draft.city_name,
            district_code: districtCode,
            district_name: draft.district_names[index] || districtCode,
            price
        }))
    })
}

function resetDrawerForm(level: RegionLevel = REGION_LEVEL_CITY) {
    Object.assign(drawerForm, createRuleDraft(level))
    editingKey.value = ''
}

function buildRegionLabel(draft: RegionRuleDraft) {
    if (draft.region_level === REGION_LEVEL_PROVINCE) {
        return draft.province_name || draft.province_code || '未选择省份'
    }

    const cityLabel = [draft.province_name || draft.province_code, draft.city_name || draft.city_code]
        .filter(Boolean)
        .join(' / ')

    if (draft.region_level === REGION_LEVEL_CITY) {
        return cityLabel || '未选择城市'
    }

    return cityLabel ? `${cityLabel} + ${draft.district_codes.length} 个区县` : '未选择城市'
}

function getRangeHint(draft: RegionRuleDraft) {
    if (draft.region_level === REGION_LEVEL_PROVINCE) {
        return '省级兜底规则'
    }
    if (draft.region_level === REGION_LEVEL_CITY) {
        return '城市统一定价'
    }
    return '同城多区县批量定价'
}

function buildDistrictPreview(draft: RegionRuleDraft) {
    if (!draft.district_names.length) {
        return '查看区县明细'
    }
    if (draft.district_names.length === 1) {
        return draft.district_names[0]
    }
    return `${draft.district_names[0]}等 ${draft.district_names.length} 个区县`
}

function buildDistrictTooltip(draft: RegionRuleDraft) {
    return draft.district_names.join('、')
}

function syncDraftDistrictNames(draft: RegionRuleDraft) {
    const nameMap = new Map<string, string>()
    draft.district_codes.forEach((districtCode, index) => {
        nameMap.set(districtCode, draft.district_names[index] || districtCode)
    })
    getDistrictOptions(draft.city_code).forEach((item) => {
        nameMap.set(item.district_code, item.district_name)
    })

    const optionOrderedCodes = getDistrictOptions(draft.city_code)
        .map((item) => item.district_code)
        .filter((districtCode) => draft.district_codes.includes(districtCode))
    const missingCodes = draft.district_codes.filter((districtCode) => !optionOrderedCodes.includes(districtCode))
    draft.district_codes = [...optionOrderedCodes, ...missingCodes]
    draft.district_names = draft.district_codes.map((districtCode) => nameMap.get(districtCode) || districtCode)
}

function handleDrawerProvinceChange() {
    const province = getProvinceOption(drawerForm.province_code)
    drawerForm.province_name = province?.province_name || ''
}

async function handleDrawerCityChange() {
    const city = getCityOption(drawerForm.city_code)
    drawerForm.province_code = city?.province_code || ''
    drawerForm.province_name = city?.province_name || ''
    drawerForm.city_name = city?.city_name || ''
    drawerForm.district_codes = []
    drawerForm.district_names = []
    if (drawerForm.city_code) {
        await ensureDistrictOptions(drawerForm.city_code)
    }
}

function handleDrawerDistrictChange() {
    syncDraftDistrictNames(drawerForm)
}

function openCreateDrawer(level: RegionLevel) {
    drawerMode.value = 'add'
    resetDrawerForm(level)
    drawerVisible.value = true
}

async function openCreateDrawerWithCity(cityCode: string, level: RegionLevel = REGION_LEVEL_CITY) {
    drawerMode.value = 'add'
    resetDrawerForm(level)
    drawerForm.city_code = cityCode
    await handleDrawerCityChange()
    drawerVisible.value = true
}

async function openEditDrawer(row: RegionRuleDraft) {
    drawerMode.value = 'edit'
    editingKey.value = row._key
    Object.assign(drawerForm, cloneRuleDraft(row))
    if (drawerForm.region_level === REGION_LEVEL_DISTRICT && drawerForm.city_code) {
        await ensureDistrictOptions(drawerForm.city_code)
        syncDraftDistrictNames(drawerForm)
    }
    drawerVisible.value = true
}

function handleDeleteRule(key: string) {
    ruleDrafts.value = sortRuleDrafts(ruleDrafts.value.filter((item) => item._key !== key))
}

async function handleEditCityNode(city: RegionDisplayCityNode) {
    if (city.cityRule) {
        await openEditDrawer(city.cityRule)
        return
    }

    if (city.districtGroups.length) {
        ensureExpandedKey(expandedProvinceKeys, city.province_key)
        ensureExpandedKey(expandedCityKeys, city._key)
        return
    }

    await openCreateDrawerWithCity(city.city_code, REGION_LEVEL_CITY)
}

function validateDrawerForm() {
    if (drawerForm.region_level === REGION_LEVEL_PROVINCE && !drawerForm.province_code) {
        throw new Error('请选择服务省份')
    }

    if (drawerForm.region_level !== REGION_LEVEL_PROVINCE && !drawerForm.city_code) {
        throw new Error('请选择服务城市')
    }

    if (drawerForm.region_level === REGION_LEVEL_DISTRICT && !drawerForm.district_codes.length) {
        throw new Error('请至少选择一个服务区县')
    }

    if (Number(drawerForm.price ?? 0) < 0) {
        throw new Error('地区售价不能小于0')
    }
}

function buildConflictDistrictNames(otherDrafts: RegionRuleDraft[], targetDraft: RegionRuleDraft) {
    const conflictNames = new Set<string>()

    otherDrafts.forEach((item) => {
        if (
            item.region_level !== REGION_LEVEL_DISTRICT ||
            item.city_code !== targetDraft.city_code ||
            formatPrice(item.price) === formatPrice(targetDraft.price)
        ) {
            return
        }

        item.district_codes.forEach((districtCode, index) => {
            if (targetDraft.district_codes.includes(districtCode)) {
                conflictNames.add(item.district_names[index] || districtCode)
            }
        })
    })

    return Array.from(conflictNames)
}

function mergeDistrictDraft(target: RegionRuleDraft, source: RegionRuleDraft) {
    const nameMap = new Map<string, string>()
    target.district_codes.forEach((districtCode, index) => {
        nameMap.set(districtCode, target.district_names[index] || districtCode)
    })
    source.district_codes.forEach((districtCode, index) => {
        nameMap.set(districtCode, source.district_names[index] || districtCode)
    })

    target.province_code = source.province_code
    target.province_name = source.province_name
    target.city_code = source.city_code
    target.city_name = source.city_name
    target.price = Number(source.price ?? 0)
    target.district_codes = Array.from(new Set([...target.district_codes, ...source.district_codes]))
    target.district_names = target.district_codes.map((districtCode) => nameMap.get(districtCode) || districtCode)
    syncDraftDistrictNames(target)
}

function handleSaveDrawer() {
    try {
        validateDrawerForm()

        const nextDraft = cloneRuleDraft(drawerForm)
        nextDraft.region_level = normalizeRegionLevel(nextDraft.region_level)
        nextDraft.price = Number(nextDraft.price ?? 0)

        if (nextDraft.region_level === REGION_LEVEL_PROVINCE) {
            const province = getProvinceOption(nextDraft.province_code)
            nextDraft.province_name = province?.province_name || nextDraft.province_name
            nextDraft.city_code = ''
            nextDraft.city_name = ''
            nextDraft.district_codes = []
            nextDraft.district_names = []
        } else {
            const city = getCityOption(nextDraft.city_code)
            nextDraft.province_code = city?.province_code || nextDraft.province_code
            nextDraft.province_name = city?.province_name || nextDraft.province_name
            nextDraft.city_name = city?.city_name || nextDraft.city_name

            if (nextDraft.region_level !== REGION_LEVEL_DISTRICT) {
                nextDraft.district_codes = []
                nextDraft.district_names = []
            } else {
                syncDraftDistrictNames(nextDraft)
            }
        }

        const otherDrafts = ruleDrafts.value.filter((item) => item._key !== editingKey.value)
        if (
            nextDraft.region_level === REGION_LEVEL_PROVINCE &&
            otherDrafts.some(
                (item) =>
                    item.region_level === REGION_LEVEL_PROVINCE &&
                    item.province_code === nextDraft.province_code
            )
        ) {
            throw new Error('该省份已存在省级价格规则')
        }

        if (
            nextDraft.region_level === REGION_LEVEL_CITY &&
            otherDrafts.some(
                (item) => item.region_level === REGION_LEVEL_CITY && item.city_code === nextDraft.city_code
            )
        ) {
            throw new Error('该城市已存在城市统一价')
        }

        if (nextDraft.region_level === REGION_LEVEL_DISTRICT) {
            const conflictNames = buildConflictDistrictNames(otherDrafts, nextDraft)
            if (conflictNames.length) {
                throw new Error(`以下区县已存在不同价格规则：${conflictNames.join('、')}`)
            }

            const samePriceDraft = otherDrafts.find(
                (item) =>
                    item.region_level === REGION_LEVEL_DISTRICT &&
                    item.city_code === nextDraft.city_code &&
                    formatPrice(item.price) === formatPrice(nextDraft.price)
            )

            if (samePriceDraft) {
                mergeDistrictDraft(samePriceDraft, nextDraft)
                ruleDrafts.value = sortRuleDrafts(otherDrafts)
                drawerVisible.value = false
                return
            }
        }

        nextDraft._key = editingKey.value || buildDraftKey()
        ruleDrafts.value = sortRuleDrafts([...otherDrafts, nextDraft])
        drawerVisible.value = false
    } catch (error: any) {
        ElMessage.warning(error?.message || '保存地区规则失败')
    }
}

watch(
    () => props.modelValue,
    async (value) => {
        const currentRows = flattenRuleDrafts(ruleDrafts.value)
        const nextRows = normalizeRegionPriceRows(Array.isArray(value) ? value : [])
        if (serializeRegionRows(currentRows) === serializeRegionRows(nextRows)) {
            return
        }

        syncingFromOutside.value = true
        const nextDrafts = groupRegionRows(nextRows)
        ruleDrafts.value = nextDrafts

        const districtCities = Array.from(
            new Set(
                nextDrafts
                    .filter((item) => item.region_level === REGION_LEVEL_DISTRICT)
                    .map((item) => item.city_code)
                    .filter(Boolean)
            )
        )
        await Promise.all(districtCities.map((cityCode) => ensureDistrictOptions(cityCode)))
        nextDrafts.forEach((draft) => {
            if (draft.region_level === REGION_LEVEL_DISTRICT) {
                syncDraftDistrictNames(draft)
            }
        })
        syncingFromOutside.value = false
    },
    { deep: true, immediate: true }
)

watch(
    ruleDrafts,
    (value) => {
        if (syncingFromOutside.value) {
            return
        }
        emit('update:modelValue', flattenRuleDrafts(value))
    },
    { deep: true }
)

watch(drawerVisible, (visible) => {
    if (!visible) {
        resetDrawerForm()
    }
})

watch(normalizedSearchKeyword, (value, oldValue) => {
    if (!value && oldValue) {
        resetExpandedState()
    }
})

watch(onlyConfigured, () => {
    resetExpandedState()
})

watch(
    () => props.optionsApiScene,
    () => {
        reloadRegionOptionsByScene()
    },
    { immediate: true }
)
</script>

<style scoped lang="scss">
.package-region-price-editor {
    width: 100%;

    .editor-summary {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px 16px;
        margin-bottom: 12px;
    }

    .editor-summary__info {
        display: flex;
        flex: 1 1 320px;
        min-width: 0;
    }

    .editor-summary__metrics {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .summary-pill {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #f8fafc;
        font-size: 12px;
        line-height: 1;
        color: #6b7280;
        white-space: nowrap;
    }

    .summary-pill__value {
        margin: 0 2px;
        color: #111827;
        font-weight: 600;
    }

    .editor-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
        margin-left: auto;
    }

    .editor-panel {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 14px;
        background: #fff;
    }

    .editor-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px 12px;
        margin-bottom: 12px;
    }

    .editor-controls__search {
        flex: 1 1 260px;
        min-width: 220px;
    }

    .editor-controls__switch {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-left: auto;
        color: #4b5563;
        font-size: 12px;
        white-space: nowrap;
    }

    .editor-sections {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .editor-section {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        background: #fbfcfd;
    }

    .editor-section__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        padding: 12px 14px;
        border: 0;
        background: transparent;
        font: inherit;
        text-align: left;
        cursor: pointer;
    }

    .editor-section__header--static {
        cursor: default;
    }

    .editor-section__left {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .editor-section__arrow {
        flex: none;
        color: #6b7280;
        font-size: 14px;
    }

    .editor-section__title {
        color: #111827;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }

    .editor-section__count {
        color: #6b7280;
        font-size: 12px;
        white-space: nowrap;
    }

    .editor-section__preview {
        overflow: hidden;
        color: #6b7280;
        font-size: 12px;
        text-align: right;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .editor-section__body {
        padding: 0 14px 14px;
    }

    .editor-section__empty {
        padding: 18px 0;
        color: #9ca3af;
        font-size: 13px;
        text-align: center;
    }

    .editor-rule-list,
    .province-tree {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .province-tree-node {
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
    }

    .province-tree-node__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        padding: 12px 14px;
        border: 0;
        background: transparent;
        font: inherit;
        text-align: left;
        cursor: pointer;
    }

    .province-tree-node__left {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .province-tree-node__arrow {
        flex: none;
        color: #6b7280;
        font-size: 14px;
    }

    .province-tree-node__title {
        overflow: hidden;
        color: #111827;
        font-size: 14px;
        font-weight: 600;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .province-tree-node__meta {
        color: #6b7280;
        font-size: 12px;
        white-space: nowrap;
    }

    .province-tree-node__body {
        display: flex;
        flex-direction: column;
        gap: 10px;
        padding: 0 12px 12px;
    }

    .city-tree-node {
        border: 1px solid #edf0f3;
        border-radius: 10px;
        background: #fbfcfd;
    }

    .city-tree-node.is-empty {
        border-style: dashed;
        background: #fcfcfd;
    }

    .city-tree-node__top {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px;
    }

    .city-tree-node__header {
        display: flex;
        flex: 1 1 auto;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-width: 0;
        padding: 0;
        border: 0;
        background: transparent;
        font: inherit;
        text-align: left;
        cursor: pointer;
    }

    .city-tree-node__left {
        display: flex;
        flex: 1 1 auto;
        align-items: flex-start;
        gap: 8px;
        min-width: 0;
    }

    .city-tree-node__arrow {
        flex: none;
        margin-top: 1px;
        color: #6b7280;
        font-size: 14px;
    }

    .city-tree-node__info {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .city-tree-node__title {
        overflow: hidden;
        color: #111827;
        font-size: 14px;
        font-weight: 600;
        line-height: 1.45;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .city-tree-node__price {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    .city-tree-node__summary {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 6px;
    }

    .city-tree-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        background: #fff;
        color: #6b7280;
        font-size: 12px;
        white-space: nowrap;
    }

    .city-tree-badge__value {
        color: #111827;
        font-weight: 600;
    }

    .city-tree-node__actions {
        display: flex;
        flex: none;
        align-items: center;
        gap: 8px;
    }

    .city-tree-node__actions :deep(.el-button) {
        margin-left: 0;
    }

    .city-tree-node__body {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 0 12px 12px 34px;
    }

    .city-tree-node__empty {
        padding: 10px 12px;
        border: 1px dashed #d1d5db;
        border-radius: 8px;
        background: #fff;
        color: #9ca3af;
        font-size: 13px;
        text-align: center;
    }

    .editor-rule-row,
    .editor-district-row {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
    }

    .editor-rule-row__main,
    .editor-district-row__main {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        gap: 4px;
        min-width: 0;
    }

    .editor-rule-row__title {
        color: #111827;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.45;
    }

    .editor-rule-row__meta,
    .editor-district-row__meta {
        color: #6b7280;
        font-size: 12px;
        line-height: 1.45;
    }

    .editor-rule-row__price,
    .editor-district-row__price {
        flex: none;
        color: #111827;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }

    .editor-rule-row__actions,
    .editor-district-row__actions {
        display: flex;
        flex: none;
        align-items: center;
        gap: 8px;
    }

    .editor-rule-row__actions :deep(.el-button),
    .editor-district-row__actions :deep(.el-button) {
        margin-left: 0;
    }

    .editor-district-row__text {
        overflow: hidden;
        color: #111827;
        font-size: 13px;
        line-height: 1.45;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .table-muted {
        color: #9ca3af;
    }

    .district-tooltip {
        max-width: 320px;
        line-height: 1.6;
        word-break: break-all;
    }

    .drawer-intro {
        margin-bottom: 12px;
        padding: 10px 12px;
        border-radius: 10px;
        background: #f8fafc;
    }

    .drawer-intro__title {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }

    .drawer-form {
        padding-right: 4px;
    }

    .district-selected-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .drawer-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        width: 100%;
    }
}

@media (max-width: 900px) {
    .package-region-price-editor {
        .editor-controls__switch {
            margin-left: 0;
        }

        .city-tree-node__top {
            flex-wrap: wrap;
        }

        .city-tree-node__header {
            width: 100%;
        }

        .city-tree-node__actions {
            width: 100%;
            justify-content: flex-end;
        }

        .city-tree-node__body {
            padding-left: 12px;
        }

        .editor-rule-row,
        .editor-district-row {
            flex-wrap: wrap;
        }

        .editor-rule-row__price,
        .editor-district-row__price {
            order: -1;
        }

        .editor-rule-row__actions,
        .editor-district-row__actions {
            width: 100%;
            justify-content: flex-end;
        }
    }
}

@media (max-width: 768px) {
    .package-region-price-editor {
        .editor-summary {
            gap: 10px 12px;
        }

        .editor-summary__info {
            min-width: 0;
        }

        .editor-actions {
            width: 100%;
            margin-left: 0;
            justify-content: flex-end;
        }

        .editor-panel {
            padding: 12px;
        }

        .editor-controls__search {
            min-width: 0;
        }

        .editor-section__header,
        .province-tree-node__header,
        .city-tree-node__header {
            gap: 10px;
        }

        .editor-section__preview,
        .province-tree-node__meta,
        .city-tree-node__summary {
            max-width: 100%;
        }

        .editor-section__header,
        .province-tree-node__header {
            flex-wrap: wrap;
        }

        .editor-section__preview,
        .province-tree-node__meta {
            width: 100%;
            text-align: left;
            white-space: normal;
        }

        .city-tree-node__title,
        .editor-district-row__text {
            white-space: normal;
        }
    }
}
</style>
