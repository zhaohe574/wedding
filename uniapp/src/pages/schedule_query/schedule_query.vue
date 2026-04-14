<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" class="schedule-query-page">
        <BaseNavbar class="schedule-query-page__navbar" title="档期查询" />
        <view class="content wm-page-content">
            <view class="card wm-form-block" @tap="openDatePicker">
                <view class="field-label">
                    <text class="required-mark" :style="{ color: $theme.ctaColor }">*</text>
                    <text class="title">预约日期</text>
                </view>
                <text class="value" :class="{ muted: !selectedDate }">{{ selectedDateText }}</text>
            </view>

            <view class="card wm-form-block" @tap="openRegionPicker">
                <view class="field-label">
                    <text class="required-mark" :style="{ color: $theme.ctaColor }">*</text>
                    <text class="title">预约地区</text>
                </view>
                <text class="value" :class="{ muted: !hasSelectedRegion }">{{
                    selectedRegionText
                }}</text>
            </view>

            <view class="card wm-form-block">
                <view class="head">
                    <view class="field-label">
                        <text class="required-mark" :style="{ color: $theme.ctaColor }">*</text>
                        <text class="title">服务分类</text>
                    </view>
                    <text v-if="selectedCategoryName" class="hint"
                        >当前：{{ selectedCategoryName }}</text
                    >
                </view>
                <view v-if="categories.length" class="chips">
                    <view
                        v-for="item in categories"
                        :key="item.id"
                        class="chip"
                        :class="{ active: selectedCategoryId === item.id }"
                        @tap.stop="handleCategorySelect(item.id)"
                    >
                        {{ item.name }}
                    </view>
                </view>
                <text v-else class="helper">暂无可选服务分类</text>
            </view>

            <view class="card wm-form-block">
                <view class="head">
                    <text class="title">风格标签</text>
                    <text v-if="selectedTagIds.length" class="hint"
                        >已选 {{ selectedTagIds.length }} 项</text
                    >
                </view>
                <view
                    class="dropdown"
                    :class="{ disabled: tagDisabled, muted: !selectedTagSummary }"
                    @tap.stop="openTagPicker"
                >
                    <text class="dropdown__text">{{ tagFieldText }}</text>
                    <tn-icon
                        name="arrow-down"
                        size="28"
                        :color="tagDisabled ? '#D0C6C0' : '#B4ACA8'"
                    />
                </view>
                <text v-if="!selectedCategoryId" class="helper">请先选择服务分类</text>
                <text v-else-if="selectedCategoryId && !styleTags.length" class="helper"
                    >当前分类暂无可选标签</text
                >
            </view>

            <view class="card wm-form-block">
                <text class="title">关键词</text>
                <textarea
                    v-model="keyword"
                    class="keyword"
                    auto-height
                    confirm-type="search"
                    maxlength="80"
                    placeholder="主持人姓名等"
                    :placeholder-style="keywordPlaceholderStyle"
                    @confirm="handleSubmit"
                />
            </view>

            <view class="card wm-form-block">
                <view class="head">
                    <text class="title">排序方式</text>
                    <text class="hint">{{ currentSortName }}</text>
                </view>
                <view class="chips dense">
                    <view
                        v-for="item in sortOptions"
                        :key="item.value"
                        class="chip soft"
                        :class="{ active: currentSort === item.value }"
                        @tap.stop="handleSortChange(item.value)"
                    >
                        {{ item.label }}
                    </view>
                </view>
            </view>
        </view>

        <ActionArea class="schedule-query-page__action" sticky safeBottom>
            <view
                class="submit"
                :style="{ backgroundColor: $theme.primaryColor, boxShadow: getPrimaryShadow(0.2) }"
                @tap="handleSubmit"
            >
                <text class="submit__text">开始查询</text>
            </view>
        </ActionArea>

        <BaseOverlayMask :show="showRegionPopup" @close="closeRegionPicker" />
        <tn-popup
            v-model="showRegionPopup"
            open-direction="bottom"
            :overlay="false"
            :overlay-closeable="true"
            safe-area-inset-bottom
            :radius="popupBorderRadius"
        >
            <view class="picker">
                <view class="picker__head">
                    <text class="picker__action" @tap="closeRegionPicker">取消</text>
                    <text class="picker__title">选择服务地区</text>
                    <text class="picker__action primary" @tap="confirmRegionPicker">确定</text>
                </view>
                <view class="region">
                    <view class="region__col">
                        <view class="region__title">省份</view>
                        <scroll-view scroll-y class="region__scroll">
                            <view
                                v-for="province in regionProvinces"
                                :key="province.province_code"
                                class="region__item"
                                :style="
                                    getRegionItemStyle(
                                        tempRegion.province_code === province.province_code
                                    )
                                "
                                @tap="handleProvinceSelect(province)"
                                >{{ province.province_name }}</view
                            >
                        </scroll-view>
                    </view>
                    <view class="region__col">
                        <view class="region__title">城市</view>
                        <scroll-view scroll-y class="region__scroll">
                            <view
                                v-for="city in regionCities"
                                :key="city.city_code"
                                class="region__item"
                                :style="getRegionItemStyle(tempRegion.city_code === city.city_code)"
                                @tap="handleCitySelect(city)"
                                >{{ city.city_name }}</view
                            >
                        </scroll-view>
                    </view>
                    <view class="region__col">
                        <view class="region__title">区县</view>
                        <scroll-view scroll-y class="region__scroll">
                            <view
                                v-for="district in regionDistricts"
                                :key="district.district_code"
                                class="region__item"
                                :style="
                                    getRegionItemStyle(
                                        tempRegion.district_code === district.district_code
                                    )
                                "
                                @tap="handleDistrictSelect(district)"
                                >{{ district.district_name }}</view
                            >
                        </scroll-view>
                    </view>
                </view>
                <view class="picker__foot">
                    <view class="picker__btn" @tap="resetRegionSelection">清空</view>
                    <view
                        class="picker__btn primary-bg"
                        :style="{
                            background: $theme.primaryColor,
                            boxShadow: getPrimaryShadow(0.24)
                        }"
                        @tap="confirmRegionPicker"
                        >确定</view
                    >
                </view>
            </view>
        </tn-popup>

        <BaseOverlayMask :show="showDatePopup" @close="closeDatePicker" />
        <tn-popup
            v-model="showDatePopup"
            open-direction="bottom"
            :overlay="false"
            :overlay-closeable="true"
            safe-area-inset-bottom
            :radius="popupBorderRadius"
        >
            <view class="picker">
                <view class="picker__head">
                    <text class="picker__action" @tap="closeDatePicker">取消</text>
                    <text class="picker__title">选择预约日期</text>
                    <text class="picker__action primary" @tap="confirmDatePicker">确定</text>
                </view>
                <view class="date">
                    <picker-view
                        class="date__view"
                        :value="datePickerValue"
                        @change="handleDatePickerChange"
                    >
                        <picker-view-column
                            ><view
                                v-for="year in datePickerYears"
                                :key="`year-${year}`"
                                class="date__item"
                                >{{ year }}年</view
                            ></picker-view-column
                        >
                        <picker-view-column
                            ><view
                                v-for="month in datePickerMonths"
                                :key="`month-${month}`"
                                class="date__item"
                                >{{ month }}月</view
                            ></picker-view-column
                        >
                        <picker-view-column
                            ><view
                                v-for="day in datePickerDays"
                                :key="`day-${day}`"
                                class="date__item"
                                >{{ day }}日</view
                            ></picker-view-column
                        >
                    </picker-view>
                </view>
            </view>
        </tn-popup>

        <BaseOverlayMask :show="showTagPopup" @close="handleTagPopupClose" />
        <tn-popup
            v-model="showTagPopup"
            open-direction="bottom"
            :overlay="false"
            :overlay-closeable="true"
            safe-area-inset-bottom
            :radius="popupBorderRadius"
            @close="handleTagPopupClose"
        >
            <view class="picker">
                <view class="picker__head">
                    <view class="picker__group">
                        <view class="picker__clear" @tap="resetTagSelection">重置</view>
                        <text class="picker__title">选择风格标签</text>
                    </view>
                    <text class="picker__action primary" @tap="confirmTagPicker">确定</text>
                </view>
                <view class="panel">
                    <view v-if="styleTags.length" class="grid">
                        <view
                            v-for="item in styleTags"
                            :key="item.id"
                            class="grid__item"
                            :class="{ active: tempSelectedTagIds.includes(Number(item.id)) }"
                            @tap="toggleTagSelection(item.id)"
                        >
                            {{ item.name }}
                        </view>
                    </view>
                    <view v-else class="empty">当前分类暂无可选标签</view>
                </view>
                <view class="picker__foot">
                    <view class="picker__btn" @tap="resetTagSelection">清空</view>
                    <view
                        class="picker__btn primary-bg"
                        :style="{
                            background: $theme.primaryColor,
                            boxShadow: getPrimaryShadow(0.24)
                        }"
                        @tap="confirmTagPicker"
                        >确定</view
                    >
                </view>
            </view>
        </tn-popup>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getServiceCategories, getServiceRegionTree, getStyleTags } from '@/api/service'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'
import {
    buildServiceRegionQuery,
    formatServiceRegionText,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection
} from '@/utils/service-region'

interface CategoryItem {
    id: number
    name: string
}

const $theme = useThemeStore()
const sortOptions = [
    { label: '综合排序', value: 'default' },
    { label: '价格从低到高', value: 'price_asc' },
    { label: '价格从高到低', value: 'price_desc' },
    { label: '评分最高', value: 'rating' },
    { label: '销量最高', value: 'order_count' }
]

const keyword = ref('')
const categories = ref<CategoryItem[]>([])
const selectedCategoryId = ref<number | ''>('')
const styleTags = ref<any[]>([])
const selectedTagIds = ref<number[]>([])
const tempSelectedTagIds = ref<number[]>([])
const currentSort = ref('default')
const selectedDate = ref('')
const entrySource = ref('')
const showDatePopup = ref(false)
const showRegionPopup = ref(false)
const showTagPopup = ref(false)
const datePickerValue = ref([0, 0, 0])
const regionTree = ref<any[]>([])
const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))
const tempRegion = ref(normalizeServiceRegion(selectedRegion.value))
const popupBorderRadius = 28
const keywordPlaceholderStyle =
    'color: rgba(30, 36, 50, 0.42); font-size: 30rpx; font-weight: 600; line-height: 1.6;'

const getPrimaryShadow = (alpha = 0.18) => `0 24rpx 40rpx ${alphaColor($theme.primaryColor, alpha)}`
const isValidSortValue = (value: unknown) => sortOptions.some((item) => item.value === value)
const parseIdList = (value: unknown) =>
    Array.from(
        new Set(
            (Array.isArray(value) ? value : String(value || '').split(','))
                .map((item) => Number(item))
                .filter((item) => Number.isInteger(item) && item > 0)
        )
    )
const getTomorrowDate = () => {
    const tomorrow = new Date()
    tomorrow.setHours(0, 0, 0, 0)
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow
}
const getMaxDateForPicker = () => {
    const maxDate = getTomorrowDate()
    maxDate.setFullYear(maxDate.getFullYear() + 5)
    return maxDate
}
const parseDateText = (value = '') => {
    const [year, month, day] = value.split('-').map((item) => Number(item))
    if (!year || !month || !day) return null
    const date = new Date(year, month - 1, day)
    date.setHours(0, 0, 0, 0)
    return Number.isNaN(date.getTime()) ? null : date
}
const formatDateText = (date: Date) =>
    `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(
        date.getDate()
    ).padStart(2, '0')}`
const isSelectableDate = (value = '') => {
    const parsedDate = parseDateText(value)
    return !!parsedDate && parsedDate >= getTomorrowDate() && parsedDate <= getMaxDateForPicker()
}
const normalizeSelectedDateText = (value = '') =>
    isSelectableDate(value) ? formatDateText(parseDateText(value) as Date) : ''
const getEffectiveSelectableDate = (value = '') => {
    const parsedDate = parseDateText(value)
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    if (!parsedDate || parsedDate < minDate) return minDate
    if (parsedDate > maxDate) return maxDate
    return parsedDate
}
const flattenCategories = (tree: any[], result: CategoryItem[] = []): CategoryItem[] => {
    tree.forEach((item) => {
        result.push({ id: Number(item.id), name: item.name })
        if (Array.isArray(item.children) && item.children.length)
            flattenCategories(item.children, result)
    })
    return result
}

const selectedCategoryName = computed(
    () => categories.value.find((item) => item.id === selectedCategoryId.value)?.name || ''
)
const selectedTagNames = computed(() => {
    const idSet = new Set(selectedTagIds.value)
    return styleTags.value
        .filter((item) => idSet.has(Number(item.id)))
        .map((item) => String(item.name || '').trim())
        .filter(Boolean)
})
const selectedTagSummary = computed(() =>
    !selectedTagNames.value.length
        ? ''
        : selectedTagNames.value.length <= 2
        ? selectedTagNames.value.join('、')
        : `${selectedTagNames.value.slice(0, 2).join('、')} 等${selectedTagNames.value.length}项`
)
const tagFieldText = computed(() =>
    !selectedCategoryId.value
        ? '请先选择服务分类'
        : !styleTags.value.length
        ? '当前分类暂无可选标签'
        : selectedTagSummary.value || '请选择风格标签'
)
const tagDisabled = computed(() => !selectedCategoryId.value || !styleTags.value.length)
const currentSortName = computed(
    () => sortOptions.find((item) => item.value === currentSort.value)?.label || '综合排序'
)
const hasSelectedRegion = computed(() => hasServiceRegion(selectedRegion.value))
const selectedDateText = computed(() => {
    if (!selectedDate.value) return '请选择婚礼日期'
    const parsedDate = parseDateText(selectedDate.value)
    if (!parsedDate) return '请选择婚礼日期'
    const weekMap = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
    return `${parsedDate.getFullYear()} 年 ${
        parsedDate.getMonth() + 1
    } 月 ${parsedDate.getDate()} 日（${weekMap[parsedDate.getDay()]}）`
})
const selectedRegionText = computed(() => {
    if (!hasSelectedRegion.value) return '请选择服务地区'
    const cityName = selectedRegion.value.city_name || selectedRegion.value.province_name
    const districtName = selectedRegion.value.district_name
    return cityName && districtName
        ? `${cityName} · ${districtName}`
        : formatServiceRegionText(selectedRegion.value, ' / ')
})

const datePickerYears = computed(() => {
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    return Array.from(
        { length: maxDate.getFullYear() - minDate.getFullYear() + 1 },
        (_, index) => minDate.getFullYear() + index
    )
})
const getDatePickerMonthsByYear = (year: number) => {
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    const startMonth = year === minDate.getFullYear() ? minDate.getMonth() + 1 : 1
    const endMonth = year === maxDate.getFullYear() ? maxDate.getMonth() + 1 : 12
    return Array.from({ length: endMonth - startMonth + 1 }, (_, index) => startMonth + index)
}
const getDatePickerDaysByYearMonth = (year: number, month: number) => {
    const minDate = getTomorrowDate()
    const maxDate = getMaxDateForPicker()
    const isMinMonth = year === minDate.getFullYear() && month === minDate.getMonth() + 1
    const isMaxMonth = year === maxDate.getFullYear() && month === maxDate.getMonth() + 1
    const startDay = isMinMonth ? minDate.getDate() : 1
    const endDay = isMaxMonth ? maxDate.getDate() : new Date(year, month, 0).getDate()
    return Array.from({ length: endDay - startDay + 1 }, (_, index) => startDay + index)
}
const normalizeDatePickerValue = (value: number[]) => {
    const yearIndex = Math.min(Math.max(value[0] ?? 0, 0), datePickerYears.value.length - 1)
    const year = datePickerYears.value[yearIndex]
    const months = getDatePickerMonthsByYear(year)
    const monthIndex = Math.min(Math.max(value[1] ?? 0, 0), months.length - 1)
    const month = months[monthIndex]
    const days = getDatePickerDaysByYearMonth(year, month)
    const dayIndex = Math.min(Math.max(value[2] ?? 0, 0), days.length - 1)
    return [yearIndex, monthIndex, dayIndex]
}
const datePickerMonths = computed(() =>
    getDatePickerMonthsByYear(datePickerYears.value[Math.max(datePickerValue.value[0] ?? 0, 0)])
)
const datePickerDays = computed(() =>
    getDatePickerDaysByYearMonth(
        datePickerYears.value[Math.max(datePickerValue.value[0] ?? 0, 0)],
        datePickerMonths.value[Math.max(datePickerValue.value[1] ?? 0, 0)]
    )
)

const regionProvinces = computed(() => regionTree.value || [])
const regionCities = computed(
    () =>
        regionTree.value.find((item: any) => item.province_code === tempRegion.value.province_code)
            ?.cities || []
)
const regionDistricts = computed(
    () =>
        regionCities.value.find((item: any) => item.city_code === tempRegion.value.city_code)
            ?.districts || []
)
const getRegionItemStyle = (active: boolean) =>
    active ? { background: alphaColor($theme.primaryColor, 0.1), color: $theme.primaryColor } : {}

const syncTempRegion = (value?: Record<string, any>) => {
    tempRegion.value = normalizeServiceRegion(value || selectedRegion.value)
    if (!tempRegion.value.province_code && regionTree.value.length) {
        handleProvinceSelect(regionTree.value[0])
        return
    }
    if (!tempRegion.value.city_code && regionCities.value.length)
        handleCitySelect(regionCities.value[0])
}
const syncDatePickerValue = (value = '') => {
    const targetDate = getEffectiveSelectableDate(value)
    const yearIndex = Math.max(datePickerYears.value.indexOf(targetDate.getFullYear()), 0)
    const months = getDatePickerMonthsByYear(datePickerYears.value[yearIndex])
    const monthIndex = Math.max(months.indexOf(targetDate.getMonth() + 1), 0)
    const days = getDatePickerDaysByYearMonth(datePickerYears.value[yearIndex], months[monthIndex])
    const dayIndex = Math.max(days.indexOf(targetDate.getDate()), 0)
    datePickerValue.value = [yearIndex, monthIndex, dayIndex]
}

const getCategories = async () => {
    try {
        const data = await getServiceCategories()
        categories.value = flattenCategories(Array.isArray(data) ? data : [])
    } catch (error) {
        categories.value = []
        console.error('获取服务分类失败：', error)
    }
}
const getRegionTree = async () => {
    try {
        const data = await getServiceRegionTree()
        regionTree.value = Array.isArray(data) ? data : []
        if (!regionTree.value.length) {
            selectedRegion.value = normalizeServiceRegion({})
            tempRegion.value = normalizeServiceRegion({})
            return
        }
        syncTempRegion()
    } catch (error) {
        regionTree.value = []
        console.error('获取服务地区失败：', error)
    }
}
const getCategoryTags = async () => {
    if (!selectedCategoryId.value) {
        styleTags.value = []
        selectedTagIds.value = []
        tempSelectedTagIds.value = []
        return
    }
    try {
        const data = await getStyleTags({ category_id: Number(selectedCategoryId.value) })
        styleTags.value = Array.isArray(data) ? data : []
        const validIds = new Set(styleTags.value.map((item) => Number(item.id)))
        selectedTagIds.value = selectedTagIds.value.filter((id) => validIds.has(id))
        tempSelectedTagIds.value = [...selectedTagIds.value]
    } catch (error) {
        styleTags.value = []
        selectedTagIds.value = []
        tempSelectedTagIds.value = []
        console.error('获取风格标签失败：', error)
    }
}

const openDatePicker = () => {
    syncDatePickerValue(selectedDate.value)
    showDatePopup.value = true
}
const closeDatePicker = () => {
    showDatePopup.value = false
}
const handleDatePickerChange = (event: any) => {
    datePickerValue.value = normalizeDatePickerValue(event.detail.value || [])
}
const confirmDatePicker = () => {
    const year = datePickerYears.value[datePickerValue.value[0]]
    const month = String(datePickerMonths.value[datePickerValue.value[1]]).padStart(2, '0')
    const day = String(datePickerDays.value[datePickerValue.value[2]]).padStart(2, '0')
    selectedDate.value = `${year}-${month}-${day}`
    closeDatePicker()
}

const openRegionPicker = () => {
    syncTempRegion()
    showRegionPopup.value = true
}
const closeRegionPicker = () => {
    showRegionPopup.value = false
}
const handleProvinceSelect = (province: any) => {
    tempRegion.value = normalizeServiceRegion({
        province_code: province?.province_code || '',
        province_name: province?.province_name || '',
        city_code: '',
        city_name: '',
        district_code: '',
        district_name: ''
    })
    const firstCity = (province?.cities || [])[0]
    if (firstCity) handleCitySelect(firstCity)
}
const handleCitySelect = (city: any) => {
    tempRegion.value = normalizeServiceRegion({
        province_code: city?.province_code || tempRegion.value.province_code,
        province_name: city?.province_name || tempRegion.value.province_name,
        city_code: city?.city_code || '',
        city_name: city?.city_name || '',
        district_code: '',
        district_name: ''
    })
}
const handleDistrictSelect = (district: any) => {
    tempRegion.value = normalizeServiceRegion({
        ...tempRegion.value,
        province_code: district?.province_code || tempRegion.value.province_code,
        province_name: district?.province_name || tempRegion.value.province_name,
        city_code: district?.city_code || tempRegion.value.city_code,
        city_name: district?.city_name || tempRegion.value.city_name,
        district_code: district?.district_code || '',
        district_name: district?.district_name || ''
    })
}
const resetRegionSelection = () => {
    tempRegion.value = normalizeServiceRegion({})
}
const confirmRegionPicker = () => {
    if (!hasServiceRegion(tempRegion.value)) {
        uni.showToast({ title: '请选择到区县', icon: 'none' })
        return
    }
    selectedRegion.value = normalizeServiceRegion(tempRegion.value)
    saveServiceRegionSelection(selectedRegion.value)
    closeRegionPicker()
}

const handleCategorySelect = async (id: number) => {
    if (selectedCategoryId.value === id) return
    selectedCategoryId.value = id
    selectedTagIds.value = []
    tempSelectedTagIds.value = []
    await getCategoryTags()
}
const openTagPicker = () => {
    if (!selectedCategoryId.value) {
        uni.showToast({ title: '请先选择服务分类', icon: 'none' })
        return
    }
    if (!styleTags.value.length) {
        uni.showToast({ title: '当前分类暂无可选标签', icon: 'none' })
        return
    }
    tempSelectedTagIds.value = [...selectedTagIds.value]
    showTagPopup.value = true
}
const closeTagPicker = () => {
    showTagPopup.value = false
}
const handleTagPopupClose = () => {
    tempSelectedTagIds.value = [...selectedTagIds.value]
}
const toggleTagSelection = (id: number | string) => {
    const tagId = Number(id)
    if (!tagId) return
    const index = tempSelectedTagIds.value.indexOf(tagId)
    if (index > -1) {
        tempSelectedTagIds.value.splice(index, 1)
        return
    }
    tempSelectedTagIds.value.push(tagId)
}
const resetTagSelection = () => {
    tempSelectedTagIds.value = []
}
const confirmTagPicker = () => {
    selectedTagIds.value = [...tempSelectedTagIds.value]
    closeTagPicker()
}
const handleSortChange = (sort: string) => {
    currentSort.value = isValidSortValue(sort) ? sort : 'default'
}

const validateSubmit = () => {
    if (!selectedDate.value) {
        uni.showToast({ title: '请选择预约日期', icon: 'none' })
        return false
    }
    if (!hasSelectedRegion.value) {
        uni.showToast({ title: '请选择服务地区', icon: 'none' })
        return false
    }
    if (!selectedCategoryId.value) {
        uni.showToast({ title: '请选择服务分类', icon: 'none' })
        return false
    }
    return true
}

const handleSubmit = () => {
    if (!validateSubmit()) return
    const queryParts = [
        `date=${encodeURIComponent(selectedDate.value)}`,
        `category_id=${selectedCategoryId.value}`,
        `category_name=${encodeURIComponent(selectedCategoryName.value)}`
    ]
    const trimmedKeyword = keyword.value.trim()
    if (trimmedKeyword) queryParts.push(`keyword=${encodeURIComponent(trimmedKeyword)}`)
    if (selectedTagIds.value.length) queryParts.push(`tag_ids=${selectedTagIds.value.join(',')}`)
    if (selectedTagNames.value.length)
        queryParts.push(`tag_names=${encodeURIComponent(selectedTagNames.value.join('、'))}`)
    if (currentSort.value !== 'default')
        queryParts.push(`sort=${encodeURIComponent(currentSort.value)}`)
    const regionQuery = buildServiceRegionQuery(selectedRegion.value)
    if (regionQuery) queryParts.push(regionQuery)
    const url = `/pages/staff_list/staff_list?${queryParts.join('&')}`
    if (entrySource.value === 'staff_list') {
        uni.redirectTo({ url })
        return
    }
    uni.navigateTo({ url })
}

onLoad(async (options) => {
    $theme.setScene('consumer')
    if (typeof options?.source === 'string') entrySource.value = options.source.trim()
    selectedRegion.value = normalizeServiceRegion({ ...loadServiceRegionSelection(), ...options })
    tempRegion.value = normalizeServiceRegion(selectedRegion.value)
    if (typeof options?.keyword === 'string') keyword.value = options.keyword.trim()
    if (typeof options?.date === 'string')
        selectedDate.value = normalizeSelectedDateText(options.date)
    if (options?.category_id) {
        const categoryId = Number(options.category_id)
        if (!Number.isNaN(categoryId) && categoryId > 0) selectedCategoryId.value = categoryId
    }
    if (options?.tag_ids) selectedTagIds.value = parseIdList(options.tag_ids)
    if (isValidSortValue(options?.sort)) currentSort.value = String(options?.sort)
    await Promise.all([getCategories(), getRegionTree()])
    if (selectedCategoryId.value) await getCategoryTags()
})

onShow(() => {
    $theme.setScene('consumer')
})
</script>

<style lang="scss" scoped>
.schedule-query-page {
    background: transparent;
}

.content {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 30rpx;
    padding: 22rpx 37rpx calc(220rpx + env(safe-area-inset-bottom));
}

.card {
    padding: 34rpx 37rpx;
    border-radius: 45rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.84);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
}

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
}

.field-label {
    display: inline-flex;
    align-items: center;
    gap: 10rpx;
    min-width: 0;
}

.required-mark {
    font-size: 30rpx;
    font-weight: 700;
    line-height: 1;
    flex-shrink: 0;
}

.title {
    display: block;
    font-size: 28rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.hint {
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-color-primary, #e85a4f);
}

.value {
    display: block;
    margin-top: 16rpx;
    font-size: 30rpx;
    font-weight: 600;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
    white-space: pre-wrap;
}

.muted,
.helper,
.empty {
    color: var(--wm-text-tertiary, #b4aca8);
}

.helper,
.empty {
    display: block;
    margin-top: 24rpx;
    font-size: 26rpx;
    line-height: 1.6;
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 20rpx;
    margin-top: 24rpx;
}

.chips.dense {
    gap: 20rpx;
}

.chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 74rpx;
    padding: 0 28rpx;
    border-radius: 999rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.84);
    backdrop-filter: blur(24rpx);
    -webkit-backdrop-filter: blur(24rpx);
    font-size: 24rpx;
    font-weight: 600;
    color: var(--wm-text-secondary, #7f7b78);
}

.chip.soft {
    background: rgba(255, 255, 255, 0.84);
}

.chip.active {
    background: var(--wm-color-primary, #e85a4f);
    border-color: var(--wm-color-primary, #e85a4f);
    color: #fff;
}

.dropdown {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    margin-top: 24rpx;
    min-height: 104rpx;
    padding: 0 30rpx;
    border-radius: 37rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: var(--wm-color-bg-page, #fcfbf9);
}

.dropdown.disabled {
    background: rgba(248, 241, 236, 0.72);
}

.dropdown__text {
    flex: 1;
    min-width: 0;
    font-size: 28rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #1e2432);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.dropdown.muted .dropdown__text {
    color: var(--wm-text-tertiary, #b4aca8);
}

.keyword {
    width: 100%;
    min-height: 52rpx;
    margin-top: 16rpx;
    font-size: 30rpx;
    font-weight: 600;
    line-height: 1.6;
    color: var(--wm-text-primary, #1e2432);
}

.submit {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 116rpx;
    border-radius: 45rpx;
}

.submit__text {
    font-size: 32rpx;
    font-weight: 700;
    color: #fff;
}

.picker {
    border-radius: 52rpx 52rpx 0 0;
    padding-bottom: calc(var(--wm-space-card-padding, 30rpx) + env(safe-area-inset-bottom));
    background: var(--wm-color-bg-page, #fcfbf9);
    overflow: hidden;
}

.picker__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 34rpx 37rpx 30rpx;
    border-bottom: 1rpx solid rgba(239, 230, 225, 0.92);
}

.picker__group {
    display: flex;
    align-items: center;
    gap: 20rpx;
}

.picker__action {
    font-size: 28rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.picker__action.primary,
.picker__title {
    color: var(--wm-text-primary, #1e2432);
    font-weight: 700;
}

.picker__title {
    font-size: 32rpx;
}

.picker__clear {
    font-size: 24rpx;
    color: var(--wm-text-secondary, #7f7b78);
}

.region {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22rpx;
    padding: 30rpx 30rpx 0;
}

.region__title {
    padding: 0 16rpx 16rpx;
    font-size: 24rpx;
    font-weight: 700;
    color: var(--wm-text-primary, #1e2432);
}

.region__scroll {
    height: 420rpx;
    border-radius: 37rpx;
    background: rgba(255, 255, 255, 0.88);
}

.region__item {
    padding: 20rpx 18rpx;
    font-size: 24rpx;
    line-height: 1.5;
    color: var(--wm-text-secondary, #7f7b78);
}

.date {
    padding: 12rpx 24rpx 0;
}

.date__view {
    width: 100%;
    height: 420rpx;
}

.date__item {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30rpx;
    color: var(--wm-text-primary, #1e2432);
}

.panel {
    padding: 30rpx;
    max-height: 60vh;
    overflow-y: auto;
}

.grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22rpx;
}

.grid__item {
    padding: 30rpx 22rpx;
    border-radius: 37rpx;
    border: 1rpx solid var(--wm-color-border, #efe6e1);
    background: rgba(255, 255, 255, 0.94);
    font-size: 26rpx;
    font-weight: 500;
    line-height: 1.4;
    color: var(--wm-text-secondary, #7f7b78);
    text-align: center;
}

.grid__item.active {
    color: #fff;
    font-weight: 600;
    border-color: var(--wm-color-primary, #e85a4f);
    background: var(--wm-color-primary, #e85a4f);
    box-shadow: 0 10rpx 18rpx rgba(232, 90, 79, 0.16);
}

.picker__foot {
    display: flex;
    gap: 22rpx;
    padding: 30rpx;
}

.picker__btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 101rpx;
    border-radius: 37rpx;
    background: rgba(248, 241, 236, 0.86);
    font-size: 28rpx;
    font-weight: 600;
    color: var(--wm-text-primary, #1e2432);
}

.picker__btn.primary-bg {
    color: #fff;
}

.schedule-query-page__navbar :deep(.base-navbar) {
    background: transparent !important;
}

.schedule-query-page__navbar :deep(.base-navbar__bar) {
    padding: 0 45rpx;
}

.schedule-query-page__navbar :deep(.base-navbar__title) {
    font-size: 46rpx;
    line-height: 1.2;
}

.schedule-query-page__navbar :deep(.base-navbar__back-text),
.schedule-query-page__navbar :deep(.base-navbar__placeholder) {
    font-size: 37rpx;
}

.schedule-query-page :deep(.schedule-query-page__action.wm-action-area) {
    padding: 22rpx 37rpx 39rpx;
    background: linear-gradient(
        180deg,
        rgba(252, 251, 249, 0) 0%,
        rgba(252, 251, 249, 0.94) 24%,
        rgba(252, 251, 249, 1) 100%
    );
}

.schedule-query-page :deep(.schedule-query-page__action.wm-action-area--safe) {
    padding-bottom: calc(39rpx + env(safe-area-inset-bottom));
}
</style>
