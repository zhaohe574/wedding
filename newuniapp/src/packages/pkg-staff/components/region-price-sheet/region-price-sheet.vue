<template>
    <!-- 地区价格：磁吸半屏抽屉。标题 / 关闭 / 拖拽 / 脚部安全区由 snap-sheet 统一承担，
         原 .sheet 包裹层已去掉，样式全部扁平化到插槽内容上 -->
    <snap-sheet
        v-model="visible"
        :title="isEdit ? '编辑地区价格' : '添加地区价格'"
        :enable-full="true"
        :has-foot="true"
    >
        <!-- 原顶部副标题移到主体首行：snap-sheet 的 head 是单行标题结构，副标题放这里更清晰 -->
        <view class="priority-tip">
            <tn-icon name="tips" :size="22" class="priority-tip__icon" />
            <text class="priority-tip__text">优先级：区县价 &gt; 城市价 &gt; 省级价 &gt; 基础价</text>
        </view>

        <!-- 1. 价格层级 -->
        <view class="block">
            <view class="block__head">
                <text class="block__label">1. 价格层级</text>
                <text v-if="isEdit" class="block__tip">编辑时不可切换层级</text>
            </view>
            <view class="level-tabs">
                <view
                    v-for="opt in LEVEL_OPTIONS"
                    :key="opt.value"
                    hover-class="is-pressed"
                    class="level-tab"
                    :class="{
                        'is-on': level === opt.value,
                        'is-locked': isEdit && level !== opt.value
                    }"
                    @tap="selectLevel(opt.value)"
                >
                    <text class="level-tab__title">{{ opt.label }}</text>
                    <text class="level-tab__desc">{{ opt.desc }}</text>
                </view>
            </view>
        </view>

        <!-- 2. 地区选择 -->
        <view class="block">
            <view class="block__head">
                <text class="block__label">2. 选择服务地区 *</text>
                <text class="block__tip">{{ regionSelectionSummary }}</text>
            </view>

            <view v-if="treeLoading && !regionTree.length" class="tree-state">
                <text class="tree-state__text">正在加载已开通服务地区…</text>
            </view>

            <view v-else-if="treeError && !regionTree.length" class="tree-state">
                <text class="tree-state__text">地区列表加载失败</text>
                <view hover-class="is-pressed" class="tree-state__btn" @tap="onRetryTree">
                    <text class="tree-state__btn-text">重新加载</text>
                </view>
            </view>

            <view v-else-if="!regionTree.length" class="tree-state">
                <text class="tree-state__text">暂未开通可配置的服务地区，请联系管理员</text>
            </view>

            <template v-else>
                <!-- 省级价：省份网格单选 -->
                <view v-if="level === REGION_LEVEL_PROVINCE" class="chip-grid">
                    <view
                        v-for="p in regionTree"
                        :key="p.province_code"
                        hover-class="is-pressed"
                        class="region-chip"
                        :class="{
                            'is-on': selectedProvinceCode === p.province_code,
                            'is-occupied': isProvinceOccupied(p.province_code)
                        }"
                        @tap="pickProvinceOption(p)"
                    >
                        <text class="region-chip__name ellipsis">{{ p.province_name }}</text>
                        <text v-if="isProvinceOccupied(p.province_code)" class="region-chip__tag t-num">
                            已设 ¥{{ formatOccupiedPrice(`1:${p.province_code}`) }}
                        </text>
                        <text v-else class="region-chip__sub t-num">
                            {{ p.cities.length }} 个开通城市
                        </text>
                    </view>
                </view>

                <!-- 城市价：省份筛选 + 城市网格单选 -->
                <template v-else-if="level === REGION_LEVEL_CITY">
                    <scroll-view
                        v-if="regionTree.length > 1"
                        class="prov-scroll"
                        scroll-x
                        show-scrollbar="false"
                    >
                        <view class="prov-row">
                            <view
                                v-for="p in regionTree"
                                :key="p.province_code"
                                hover-class="is-pressed"
                                class="prov-pill"
                                :class="{ 'is-on': selectedProvinceCode === p.province_code }"
                                @tap="onProvinceFilterTap(p.province_code)"
                            >
                                <text class="prov-pill__text">{{ p.province_name }}</text>
                            </view>
                        </view>
                    </scroll-view>

                    <view v-if="activeCities.length" class="chip-grid">
                        <view
                            v-for="c in activeCities"
                            :key="c.city_code"
                            hover-class="is-pressed"
                            class="region-chip"
                            :class="{
                                'is-on': selectedCityCode === c.city_code,
                                'is-occupied': isCityOccupied(c.city_code)
                            }"
                            @tap="pickCityOption(c)"
                        >
                            <text class="region-chip__name ellipsis">{{ c.city_name }}</text>
                            <text v-if="isCityOccupied(c.city_code)" class="region-chip__tag t-num">
                                已设 ¥{{ formatOccupiedPrice(`2:${c.city_code}`) }}
                            </text>
                            <text
                                v-else-if="Array.isArray(c.districts) && c.districts.length"
                                class="region-chip__sub t-num"
                            >
                                {{ c.districts.length }} 个区县
                            </text>
                        </view>
                    </view>
                    <view v-else class="tree-state">
                        <text class="tree-state__text">该省份下暂无已开通城市</text>
                    </view>
                </template>

                <!-- 区县价：选省 -> 选城市 -> 区县多选 -->
                <template v-else>
                    <scroll-view
                        v-if="regionTree.length > 1"
                        class="prov-scroll"
                        scroll-x
                        show-scrollbar="false"
                    >
                        <view class="prov-row">
                            <view
                                v-for="p in regionTree"
                                :key="p.province_code"
                                hover-class="is-pressed"
                                class="prov-pill"
                                :class="{ 'is-on': selectedProvinceCode === p.province_code }"
                                @tap="onProvinceFilterTap(p.province_code)"
                            >
                                <text class="prov-pill__text">{{ p.province_name }}</text>
                            </view>
                        </view>
                    </scroll-view>

                    <scroll-view
                        v-if="activeCities.length"
                        class="city-scroll"
                        scroll-x
                        show-scrollbar="false"
                    >
                        <view class="prov-row">
                            <view
                                v-for="c in activeCities"
                                :key="c.city_code"
                                hover-class="is-pressed"
                                class="city-pill"
                                :class="{ 'is-on': selectedCityCode === c.city_code }"
                                @tap="selectDistrictCity(c)"
                            >
                                <text class="city-pill__text">{{ c.city_name }}</text>
                            </view>
                        </view>
                    </scroll-view>

                    <view v-if="activeCityNode" class="district-box">
                        <view class="district-box__bar">
                            <text class="district-box__hint">
                                {{ activeCityNode.city_name }}区县（可多选，统一执行下方价格）
                            </text>
                            <view class="district-box__ops">
                                <view
                                    v-if="availableDistricts.length"
                                    hover-class="is-pressed"
                                    class="district-box__op"
                                    @tap="selectAllAvailableDistricts"
                                >
                                    <text class="district-box__op-text">全选可选</text>
                                </view>
                                <view
                                    v-if="selectedDistrictCodes.length"
                                    hover-class="is-pressed"
                                    class="district-box__op district-box__op--muted"
                                    @tap="clearDistricts"
                                >
                                    <text class="district-box__op-text district-box__op-text--muted"
                                        >清空</text
                                    >
                                </view>
                            </view>
                        </view>

                        <view v-if="activeDistricts.length" class="district-grid">
                            <view
                                v-for="d in activeDistricts"
                                :key="d.district_code"
                                hover-class="is-pressed"
                                class="district-chip"
                                :class="{
                                    'is-on': selectedDistrictCodes.includes(d.district_code),
                                    'is-occupied': isDistrictOccupied(d.city_code, d.district_code)
                                }"
                                @tap="toggleDistrict(d)"
                            >
                                <text class="district-chip__name ellipsis">{{
                                    d.district_name
                                }}</text>
                                <text
                                    v-if="isDistrictOccupied(d.city_code, d.district_code)"
                                    class="district-chip__tag t-num"
                                >
                                    ¥{{
                                        formatOccupiedPrice(
                                            `3:${d.city_code}:${d.district_code}`
                                        )
                                    }}
                                </text>
                            </view>
                        </view>
                        <view v-else class="tree-state">
                            <text class="tree-state__text">该城市下暂无区县数据</text>
                        </view>
                    </view>
                </template>
            </template>
        </view>

        <!-- 3. 地区售价 -->
        <view class="block">
            <view class="block__head">
                <text class="block__label">3. 地区售价 *</text>
                <text v-if="basePrice > 0" class="block__tip t-num">
                    基础价 ¥{{ formatMoneyNum(basePrice) }}
                    <template v-if="liveDiffText"> · {{ liveDiffText }}</template>
                </text>
            </view>

            <view class="price-input-wrap">
                <text class="price-input-wrap__prefix">¥</text>
                <input
                    v-model="priceInput"
                    class="price-input-wrap__field t-num"
                    type="digit"
                    placeholder="请输入该地区执行售价"
                    placeholder-class="price-input-wrap__ph"
                />
                <text class="price-input-wrap__unit">元</text>
            </view>

            <view v-if="basePrice > 0" class="quick-prices">
                <view hover-class="is-pressed" class="quick-pill" @tap="applyQuickPrice(0)">
                    <text class="quick-pill__text">同基础价</text>
                </view>
                <view hover-class="is-pressed" class="quick-pill" @tap="applyQuickPrice(200)">
                    <text class="quick-pill__text t-num">+200 元</text>
                </view>
                <view hover-class="is-pressed" class="quick-pill" @tap="applyQuickPrice(500)">
                    <text class="quick-pill__text t-num">+500 元</text>
                </view>
                <view hover-class="is-pressed" class="quick-pill" @tap="applyQuickPrice(1000)">
                    <text class="quick-pill__text t-num">+1000 元</text>
                </view>
            </view>
        </view>

        <template #foot>
            <view class="foot">
                <view v-if="isEdit" class="foot__btn foot__btn--sm">
                    <base-button text="删除" variant="danger" size="md" block @click="onDeleteTap" />
                </view>
                <view class="foot__btn">
                    <base-button text="取消" variant="ghost" size="md" block @click="close" />
                </view>
                <view class="foot__btn foot__btn--primary">
                    <base-button
                        :text="isEdit ? '保存修改' : '确认添加'"
                        variant="ink"
                        size="md"
                        block
                        @click="onConfirm"
                    />
                </view>
            </view>
        </template>
    </snap-sheet>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { CityNodeVO, DistrictNodeVO, RegionProvinceVO } from '@/api/types/catalog'
import type { StaffPackageRegionPriceItem } from '@/api/staff-center'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'
import {
    REGION_LEVEL_CITY,
    REGION_LEVEL_DISTRICT,
    REGION_LEVEL_PROVINCE,
    buildOccupiedKeyMap,
    formatMoneyNum,
    formatPriceDiff,
    type RegionPriceGroup
} from '../../pages/package-edit/regionPriceHelper'

const LEVEL_OPTIONS: Array<{ value: 1 | 2 | 3; label: string; desc: string }> = [
    { value: REGION_LEVEL_PROVINCE, label: '省级价', desc: '整省统一' },
    { value: REGION_LEVEL_CITY, label: '城市价', desc: '单城生效' },
    { value: REGION_LEVEL_DISTRICT, label: '区县价', desc: '可多选同价' }
]

const props = defineProps<{
    modelValue: boolean
    regionTree: RegionProvinceVO[]
    treeLoading: boolean
    treeError: boolean
    existingFlat: StaffPackageRegionPriceItem[]
    editingGroup: RegionPriceGroup | null
    basePrice: number
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
    (e: 'retry-tree'): void
    (
        e: 'save',
        payload: { replaceGroupKey: string | null; items: StaffPackageRegionPriceItem[] }
    ): void
    (e: 'delete', groupKey: string): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const level = ref<1 | 2 | 3>(REGION_LEVEL_CITY)
const selectedProvinceCode = ref('')
const selectedCityCode = ref('')
const selectedDistrictCodes = ref<string[]>([])
const priceInput = ref('')

const isEdit = computed(() => !!props.editingGroup)

const occupiedMap = computed(() =>
    buildOccupiedKeyMap(props.existingFlat, props.editingGroup?.key ?? null)
)

const activeProvinceNode = computed<RegionProvinceVO | null>(
    () =>
        props.regionTree.find((p) => p.province_code === selectedProvinceCode.value) ||
        props.regionTree[0] ||
        null
)

const activeCities = computed<CityNodeVO[]>(() => activeProvinceNode.value?.cities || [])

const activeCityNode = computed<CityNodeVO | null>(() => {
    for (const p of props.regionTree) {
        const hit = p.cities.find((c) => c.city_code === selectedCityCode.value)
        if (hit) return hit
    }
    return null
})

const activeDistricts = computed<DistrictNodeVO[]>(() =>
    Array.isArray(activeCityNode.value?.districts) ? activeCityNode.value!.districts! : []
)

const availableDistricts = computed(() =>
    activeDistricts.value.filter((d) => !isDistrictOccupied(d.city_code, d.district_code))
)

const regionSelectionSummary = computed(() => {
    if (level.value === REGION_LEVEL_PROVINCE) {
        return activeProvinceNode.value && selectedProvinceCode.value
            ? activeProvinceNode.value.province_name
            : '未选择'
    }
    if (level.value === REGION_LEVEL_CITY) {
        return activeCityNode.value ? activeCityNode.value.city_name : '未选择'
    }
    if (activeCityNode.value && selectedDistrictCodes.value.length) {
        return `${activeCityNode.value.city_name} · 已选 ${selectedDistrictCodes.value.length} 个`
    }
    return activeCityNode.value ? `${activeCityNode.value.city_name} · 未选区县` : '未选择'
})

const liveDiffText = computed(() => {
    const raw = priceInput.value.trim()
    if (!raw) return ''
    const num = Number(raw)
    if (!Number.isFinite(num) || num < 0) return ''
    return formatPriceDiff(num, props.basePrice)
})

function isProvinceOccupied(provinceCode: string): boolean {
    return occupiedMap.value.has(`1:${provinceCode}`)
}

function isCityOccupied(cityCode: string): boolean {
    return occupiedMap.value.has(`2:${cityCode}`)
}

function isDistrictOccupied(cityCode: string, districtCode: string): boolean {
    return occupiedMap.value.has(`3:${cityCode}:${districtCode}`)
}

function formatOccupiedPrice(key: string): string {
    const price = occupiedMap.value.get(key)
    return price === undefined ? '' : formatMoneyNum(price)
}

function findProvinceByCityCode(cityCode: string): RegionProvinceVO | null {
    for (const p of props.regionTree) {
        if (p.cities.some((c) => c.city_code === cityCode)) return p
    }
    return null
}

function syncDefaultsForLevel(targetLevel: 1 | 2 | 3) {
    if (!props.regionTree.length) return
    if (targetLevel === REGION_LEVEL_PROVINCE) {
        const firstFree = props.regionTree.find((p) => !isProvinceOccupied(p.province_code))
        selectedProvinceCode.value = firstFree?.province_code || ''
        selectedCityCode.value = ''
        selectedDistrictCodes.value = []
        return
    }

    const prov =
        props.regionTree.find((p) => p.province_code === selectedProvinceCode.value) ||
        props.regionTree[0]
    selectedProvinceCode.value = prov.province_code

    if (targetLevel === REGION_LEVEL_CITY) {
        const freeCity = prov.cities.find((c) => !isCityOccupied(c.city_code))
        selectedCityCode.value = freeCity?.city_code || ''
        selectedDistrictCodes.value = []
        return
    }

    const hasValidCity = prov.cities.some((c) => c.city_code === selectedCityCode.value)
    if (!hasValidCity) {
        const firstCity = prov.cities[0]
        selectedCityCode.value = firstCity?.city_code || ''
    }
    selectedDistrictCodes.value = []
}

function hydrateFromProps() {
    if (props.editingGroup) {
        const g = props.editingGroup
        level.value = g.region_level
        selectedProvinceCode.value = g.province_code
        selectedCityCode.value = g.city_code
        selectedDistrictCodes.value = g.districts.map((d) => d.code)
        priceInput.value = formatMoneyNum(g.price)
        if (!selectedProvinceCode.value && g.city_code) {
            const p = findProvinceByCityCode(g.city_code)
            if (p) selectedProvinceCode.value = p.province_code
        }
        return
    }

    level.value = REGION_LEVEL_CITY
    selectedProvinceCode.value = props.regionTree[0]?.province_code || ''
    selectedCityCode.value = ''
    selectedDistrictCodes.value = []
    priceInput.value = props.basePrice > 0 ? formatMoneyNum(props.basePrice) : ''
    syncDefaultsForLevel(REGION_LEVEL_CITY)
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) hydrateFromProps()
    }
)

watch(
    () => props.regionTree,
    () => {
        if (props.modelValue && !props.editingGroup && !selectedProvinceCode.value) {
            syncDefaultsForLevel(level.value)
        }
    }
)

function selectLevel(next: 1 | 2 | 3) {
    if (isEdit.value) {
        if (next !== level.value) {
            toast.info('编辑时不可切换层级，如需更换请删除后重新添加')
        }
        return
    }
    if (level.value === next) return
    haptic('light', 'region-price-level')
    level.value = next
    syncDefaultsForLevel(next)
}

function pickProvinceOption(p: RegionProvinceVO) {
    if (isProvinceOccupied(p.province_code)) {
        toast.info('该省份已配置价格，请直接点击原规则编辑')
        return
    }
    if (selectedProvinceCode.value === p.province_code) return
    haptic('light', 'region-price-region')
    selectedProvinceCode.value = p.province_code
}

function onProvinceFilterTap(provinceCode: string) {
    if (selectedProvinceCode.value === provinceCode) return
    haptic('light', 'region-price-province')
    selectedProvinceCode.value = provinceCode
    const cities = props.regionTree.find((p) => p.province_code === provinceCode)?.cities || []
    if (level.value === REGION_LEVEL_CITY) {
        const freeCity = cities.find((c) => !isCityOccupied(c.city_code))
        selectedCityCode.value = freeCity?.city_code || ''
    } else {
        selectedCityCode.value = cities[0]?.city_code || ''
        selectedDistrictCodes.value = []
    }
}

function pickCityOption(c: CityNodeVO) {
    if (isCityOccupied(c.city_code)) {
        toast.info('该城市已配置价格，请直接点击原规则编辑')
        return
    }
    if (selectedCityCode.value === c.city_code) return
    haptic('light', 'region-price-region')
    selectedProvinceCode.value = c.province_code
    selectedCityCode.value = c.city_code
}

function selectDistrictCity(c: CityNodeVO) {
    if (selectedCityCode.value === c.city_code) return
    haptic('light', 'region-price-city')
    selectedProvinceCode.value = c.province_code
    selectedCityCode.value = c.city_code
    selectedDistrictCodes.value = []
}

function toggleDistrict(d: DistrictNodeVO) {
    if (isDistrictOccupied(d.city_code, d.district_code)) {
        toast.info('该区县已配置价格，请直接点击对应规则编辑')
        return
    }
    haptic('light', 'region-price-district')
    const idx = selectedDistrictCodes.value.indexOf(d.district_code)
    if (idx >= 0) {
        selectedDistrictCodes.value = selectedDistrictCodes.value.filter(
            (code) => code !== d.district_code
        )
    } else {
        selectedDistrictCodes.value = [...selectedDistrictCodes.value, d.district_code]
    }
}

function selectAllAvailableDistricts() {
    haptic('light', 'region-price-district-all')
    selectedDistrictCodes.value = availableDistricts.value.map((d) => d.district_code)
}

function clearDistricts() {
    haptic('light', 'region-price-district-clear')
    selectedDistrictCodes.value = []
}

function applyQuickPrice(delta: number) {
    haptic('light', 'region-price-quick')
    const base = Number.isFinite(props.basePrice) && props.basePrice > 0 ? props.basePrice : 0
    const next = Math.max(0, Math.round((base + delta) * 100) / 100)
    priceInput.value = formatMoneyNum(next)
}

function onRetryTree() {
    haptic('light', 'region-price-retry')
    emit('retry-tree')
}

function close() {
    emit('update:modelValue', false)
}

/**
 * 删除是不可逆操作：保留居中确认弹窗形态（uni.showModal 为系统原生模态，
 * 不做可下拉轻滑关闭的普通抽屉），并在确认后才发 delete。
 */
function onDeleteTap() {
    if (!props.editingGroup) return
    const key = props.editingGroup.key
    haptic('heavy', 'region-price-delete')
    uni.showModal({
        title: '移除地区价格',
        content: '确认移除这条地区价格规则？修改将在点击底部保存后生效',
        confirmText: '移除',
        cancelText: '取消',
        success: (res) => {
            if (!res.confirm) return
            emit('delete', key)
        }
    })
}

function onConfirm() {
    const rawPrice = priceInput.value.trim()
    const priceNum = Number(rawPrice)
    if (!rawPrice || !Number.isFinite(priceNum) || priceNum < 0) {
        toast.warning('请填写有效的地区售价（须大于等于 0）')
        return
    }
    const price = Math.round(priceNum * 100) / 100

    if (level.value === REGION_LEVEL_PROVINCE) {
        const prov = props.regionTree.find((p) => p.province_code === selectedProvinceCode.value)
        if (!prov && !props.editingGroup) {
            toast.warning('请选择服务省份')
            return
        }
        const pCode = prov?.province_code || props.editingGroup?.province_code || ''
        const pName = prov?.province_name || props.editingGroup?.province_name || ''
        if (!pCode) {
            toast.warning('请选择服务省份')
            return
        }
        if (isProvinceOccupied(pCode)) {
            toast.info('该省份已配置价格，请直接编辑原规则')
            return
        }
        haptic('medium', 'region-price-save')
        emit('save', {
            replaceGroupKey: props.editingGroup?.key ?? null,
            items: [
                {
                    region_level: REGION_LEVEL_PROVINCE,
                    province_code: pCode,
                    province_name: pName,
                    city_code: '',
                    city_name: '',
                    district_code: '',
                    district_name: '',
                    price
                }
            ]
        })
        return
    }

    if (level.value === REGION_LEVEL_CITY) {
        const city = activeCityNode.value
        const cCode = city?.city_code || props.editingGroup?.city_code || ''
        const cName = city?.city_name || props.editingGroup?.city_name || ''
        const pCode =
            city?.province_code ||
            props.editingGroup?.province_code ||
            selectedProvinceCode.value
        const pName =
            city?.province_name ||
            props.editingGroup?.province_name ||
            activeProvinceNode.value?.province_name ||
            ''
        if (!cCode) {
            toast.warning('请选择服务城市')
            return
        }
        if (isCityOccupied(cCode)) {
            toast.info('该城市已配置价格，请直接编辑原规则')
            return
        }
        haptic('medium', 'region-price-save')
        emit('save', {
            replaceGroupKey: props.editingGroup?.key ?? null,
            items: [
                {
                    region_level: REGION_LEVEL_CITY,
                    province_code: pCode,
                    province_name: pName,
                    city_code: cCode,
                    city_name: cName,
                    district_code: '',
                    district_name: '',
                    price
                }
            ]
        })
        return
    }

    const city = activeCityNode.value
    const cCode = city?.city_code || props.editingGroup?.city_code || ''
    const cName = city?.city_name || props.editingGroup?.city_name || ''
    const pCode =
        city?.province_code || props.editingGroup?.province_code || selectedProvinceCode.value
    const pName =
        city?.province_name ||
        props.editingGroup?.province_name ||
        activeProvinceNode.value?.province_name ||
        ''

    if (!cCode) {
        toast.warning('请先选择服务城市')
        return
    }
    if (!selectedDistrictCodes.value.length) {
        toast.warning('请至少选择一个区县')
        return
    }

    const items: StaffPackageRegionPriceItem[] = []
    for (const dCode of selectedDistrictCodes.value) {
        if (isDistrictOccupied(cCode, dCode)) {
            toast.info('存在已配置的区县，请取消冲突项后再保存')
            return
        }
        const treeHit = activeDistricts.value.find((d) => d.district_code === dCode)
        const editHit = props.editingGroup?.districts.find((d) => d.code === dCode)
        items.push({
            region_level: REGION_LEVEL_DISTRICT,
            province_code: pCode,
            province_name: pName,
            city_code: cCode,
            city_name: cName,
            district_code: dCode,
            district_name: treeHit?.district_name || editHit?.name || dCode,
            price
        })
    }

    haptic('medium', 'region-price-save')
    emit('save', {
        replaceGroupKey: props.editingGroup?.key ?? null,
        items
    })
}
</script>

<style lang="scss" scoped>
.foot {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
}

.foot__btn {
    flex: 1;
    min-width: 0;

    &--sm {
        flex: 0.72;
    }

    &--primary {
        flex: 1.3;
    }
}

.priority-tip {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-1);
    margin-bottom: var(--sp-3);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);

    &__icon {
        flex-shrink: 0;
        margin-top: 2rpx;
        color: var(--c-brand-text);
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-brand-text);
    }
}

.block {
    & + & {
        margin-top: var(--sp-4);
    }

    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__label {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__tip {
        flex-shrink: 0;
        max-width: 60%;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: right;
    }
}

.level-tabs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--sp-2);
}

.level-tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    min-height: 88rpx;
    padding: var(--sp-2);
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    box-sizing: border-box;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.98);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &.is-on {
        border-color: var(--c-active-border);
        background: var(--c-active-bg);

        .level-tab__title {
            color: var(--c-active-fg);
        }

        .level-tab__desc {
            color: var(--c-active-fg);
            opacity: 0.72;
        }
    }

    &.is-locked {
        opacity: 0.48;
    }
}

.tree-state {
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    border: 1rpx dashed var(--c-control-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--sp-2);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        text-align: center;
    }

    &__btn {
        display: inline-flex;
        align-items: center;
        min-height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        border: 1rpx solid var(--c-control-border);
    }

    &__btn-text {
        font-size: var(--fs-xs);
        color: var(--c-ink);
    }
}

.prov-scroll,
.city-scroll {
    white-space: nowrap;
    margin-bottom: var(--sp-2);
}

.prov-row {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-2);
    padding-right: var(--sp-2);
}

.prov-pill,
.city-pill {
    display: inline-flex;
    align-items: center;
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-bg);
    box-sizing: border-box;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .prov-pill__text,
        .city-pill__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }
}

.city-pill.is-on {
    border-color: var(--c-active-border);
    background: var(--c-active-bg);

    .city-pill__text {
        color: var(--c-active-fg);
        font-weight: var(--fw-medium);
    }
}

.chip-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--sp-2);
}

.region-chip {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 4rpx;
    min-width: 0;
    min-height: 88rpx;
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface-2);
    box-sizing: border-box;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out);

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__tag {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
    }

    &.is-on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .region-chip__name {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }

    &.is-occupied {
        background: var(--c-neutral-bg);
        border-color: var(--c-line);

        .region-chip__name {
            color: var(--c-ink-3);
        }
    }
}

.district-box {
    padding: var(--sp-2) var(--sp-3) var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);

    &__bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__hint {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__ops {
        display: flex;
        align-items: center;
        gap: var(--sp-1);
        flex-shrink: 0;
    }

    /* 文字操作也按 88rpx 热区做，视觉上仍是小号链接 */
    &__op {
        display: inline-flex;
        align-items: center;
        min-height: 88rpx;
        padding: 0 var(--sp-2);
    }

    &__op-text {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);

        &--muted {
            color: var(--c-ink-3);
        }
    }
}

.district-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--sp-2);
}

.district-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2rpx;
    min-width: 0;
    min-height: 88rpx;
    padding: var(--sp-2);
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    box-sizing: border-box;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out);

    &__name {
        width: 100%;
        text-align: center;
        font-size: var(--fs-xs);
        color: var(--c-ink);
    }

    &__tag {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &.is-on {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .district-chip__name {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }

    &.is-occupied {
        background: var(--c-neutral-bg);
        border-color: var(--c-line);

        .district-chip__name {
            color: var(--c-ink-3);
        }
    }
}

.price-input-wrap {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    height: 92rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface-2);

    &__prefix {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__field {
        flex: 1;
        min-width: 0;
        height: 100%;
        font-size: var(--fs-lg);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__ph {
        font-size: var(--fs-md);
        font-weight: var(--fw-regular);
        color: var(--c-ink-3);
    }

    &__unit {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.quick-prices {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
}

.quick-pill {
    display: inline-flex;
    align-items: center;
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    background: var(--c-bg);
    border: 1rpx solid var(--c-control-border);
    transition: background-color var(--dur-fast) var(--ease-out);

    &.is-pressed {
        background: var(--c-surface-2);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

@media (prefers-reduced-motion: reduce) {
    .level-tab,
    .prov-pill,
    .city-pill,
    .region-chip,
    .district-chip,
    .quick-pill {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}
</style>
