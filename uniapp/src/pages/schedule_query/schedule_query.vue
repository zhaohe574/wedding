<template>
    <page-meta :page-style="$theme.pageStyle" />
    <BaseNavbar title="档期查询" transparent />
    <view class="page">
        <view class="content">
            <text class="desc">选择婚礼日期、地区与服务模块，快速筛出可预约团队。</text>

            <view class="card" @tap="openDatePicker">
                <text class="title">预约日期</text>
                <text class="value" :class="{ muted: !selectedDate }">{{ selectedDateText }}</text>
            </view>

            <view class="card" @tap="openRegionPicker">
                <text class="title">预约地区</text>
                <text class="value" :class="{ muted: !hasSelectedRegion }">{{ selectedRegionText }}</text>
            </view>

            <view class="card">
                <view class="head">
                    <text class="title">服务分类</text>
                    <text v-if="selectedCategoryName" class="hint">当前：{{ selectedCategoryName }}</text>
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

            <view class="card">
                <view class="head">
                    <text class="title">风格标签</text>
                    <text v-if="selectedTagIds.length" class="hint">已选 {{ selectedTagIds.length }} 项</text>
                </view>
                <view class="dropdown" :class="{ disabled: tagDisabled, muted: !selectedTagSummary }" @tap.stop="openTagPicker">
                    <text class="dropdown__text">{{ tagFieldText }}</text>
                    <tn-icon name="arrow-down" size="28" :color="tagDisabled ? '#D0C6C0' : '#B4ACA8'" />
                </view>
                <text v-if="!selectedCategoryId" class="helper">请先选择服务分类</text>
                <text v-else-if="selectedCategoryId && !styleTags.length" class="helper">当前分类暂无可选标签</text>
            </view>

            <view class="card">
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

            <view class="card">
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

        <ActionArea sticky safeBottom>
            <view class="submit" :style="{ backgroundColor: $theme.primaryColor, boxShadow: getPrimaryShadow(0.2) }" @tap="handleSubmit">
                <text class="submit__text">开始查询</text>
            </view>
        </ActionArea>

        <u-popup v-model="showRegionPopup" mode="bottom" :mask="true" :mask-close-able="true" :safe-area-inset-bottom="true" :border-radius="24">
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
                            <view v-for="province in regionProvinces" :key="province.province_code" class="region__item" :style="getRegionItemStyle(tempRegion.province_code === province.province_code)" @tap="handleProvinceSelect(province)">{{ province.province_name }}</view>
                        </scroll-view>
                    </view>
                    <view class="region__col">
                        <view class="region__title">城市</view>
                        <scroll-view scroll-y class="region__scroll">
                            <view v-for="city in regionCities" :key="city.city_code" class="region__item" :style="getRegionItemStyle(tempRegion.city_code === city.city_code)" @tap="handleCitySelect(city)">{{ city.city_name }}</view>
                        </scroll-view>
                    </view>
                    <view class="region__col">
                        <view class="region__title">区县</view>
                        <scroll-view scroll-y class="region__scroll">
                            <view v-for="district in regionDistricts" :key="district.district_code" class="region__item" :style="getRegionItemStyle(tempRegion.district_code === district.district_code)" @tap="handleDistrictSelect(district)">{{ district.district_name }}</view>
                        </scroll-view>
                    </view>
                </view>
                <view class="picker__foot">
                    <view class="picker__btn" @tap="resetRegionSelection">清空</view>
                    <view class="picker__btn primary-bg" :style="{ background: $theme.primaryColor, boxShadow: getPrimaryShadow(0.24) }" @tap="confirmRegionPicker">确定</view>
                </view>
            </view>
        </u-popup>

        <u-popup v-model="showDatePopup" mode="bottom" :mask="true" :mask-close-able="true" :safe-area-inset-bottom="true" :border-radius="24">
            <view class="picker">
                <view class="picker__head">
                    <text class="picker__action" @tap="closeDatePicker">取消</text>
                    <text class="picker__title">选择预约日期</text>
                    <text class="picker__action primary" @tap="confirmDatePicker">确定</text>
                </view>
                <view class="date">
                    <picker-view class="date__view" :value="datePickerValue" @change="handleDatePickerChange">
                        <picker-view-column><view v-for="year in datePickerYears" :key="`year-${year}`" class="date__item">{{ year }}年</view></picker-view-column>
                        <picker-view-column><view v-for="month in datePickerMonths" :key="`month-${month}`" class="date__item">{{ month }}月</view></picker-view-column>
                        <picker-view-column><view v-for="day in datePickerDays" :key="`day-${day}`" class="date__item">{{ day }}日</view></picker-view-column>
                    </picker-view>
                </view>
            </view>
        </u-popup>

        <u-popup v-model="showTagPopup" mode="bottom" :mask="true" :mask-close-able="true" :safe-area-inset-bottom="true" :border-radius="24" @close="handleTagPopupClose">
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
                        <view v-for="item in styleTags" :key="item.id" class="grid__item" :class="{ active: tempSelectedTagIds.includes(Number(item.id)) }" @tap="toggleTagSelection(item.id)">
                            {{ item.name }}
                        </view>
                    </view>
                    <view v-else class="empty">当前分类暂无可选标签</view>
                </view>
                <view class="picker__foot">
                    <view class="picker__btn" @tap="resetTagSelection">清空</view>
                    <view class="picker__btn primary-bg" :style="{ background: $theme.primaryColor, boxShadow: getPrimaryShadow(0.24) }" @tap="confirmTagPicker">确定</view>
                </view>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import { getServiceCategories, getServiceRegionTree, getStyleTags } from '@/api/service'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'
import { buildServiceRegionQuery, formatServiceRegionText, hasServiceRegion, loadServiceRegionSelection, normalizeServiceRegion, saveServiceRegionSelection } from '@/utils/service-region'

interface CategoryItem { id: number; name: string }

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
const showDatePopup = ref(false)
const showRegionPopup = ref(false)
const showTagPopup = ref(false)
const datePickerValue = ref([0, 0, 0])
const regionTree = ref<any[]>([])
const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))
const tempRegion = ref(normalizeServiceRegion(selectedRegion.value))
const keywordPlaceholderStyle = 'color: rgba(30, 36, 50, 0.42); font-size: 30rpx; font-weight: 600;'

const getPrimaryShadow = (alpha = 0.2) => `0 12rpx 28rpx ${alphaColor($theme.primaryColor, alpha)}`
const isValidSortValue = (value: unknown) => sortOptions.some((item) => item.value === value)
const parseIdList = (value: unknown) => Array.from(new Set((Array.isArray(value) ? value : String(value || '').split(',')).map((item) => Number(item)).filter((item) => Number.isInteger(item) && item > 0)))
const getTomorrowDate = () => { const tomorrow = new Date(); tomorrow.setHours(0, 0, 0, 0); tomorrow.setDate(tomorrow.getDate() + 1); return tomorrow }
const getMaxDateForPicker = () => { const maxDate = getTomorrowDate(); maxDate.setFullYear(maxDate.getFullYear() + 5); return maxDate }
const parseDateText = (value = '') => { const [year, month, day] = value.split('-').map((item) => Number(item)); if (!year || !month || !day) return null; const date = new Date(year, month - 1, day); date.setHours(0, 0, 0, 0); return Number.isNaN(date.getTime()) ? null : date }
const formatDateText = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const isSelectableDate = (value = '') => { const parsedDate = parseDateText(value); return !!parsedDate && parsedDate >= getTomorrowDate() && parsedDate <= getMaxDateForPicker() }
const normalizeSelectedDateText = (value = '') => (isSelectableDate(value) ? formatDateText(parseDateText(value) as Date) : '')
const getEffectiveSelectableDate = (value = '') => { const parsedDate = parseDateText(value); const minDate = getTomorrowDate(); const maxDate = getMaxDateForPicker(); if (!parsedDate || parsedDate < minDate) return minDate; if (parsedDate > maxDate) return maxDate; return parsedDate }
const flattenCategories = (tree: any[], result: CategoryItem[] = []): CategoryItem[] => { tree.forEach((item) => { result.push({ id: Number(item.id), name: item.name }); if (Array.isArray(item.children) && item.children.length) flattenCategories(item.children, result) }); return result }

const selectedCategoryName = computed(() => categories.value.find((item) => item.id === selectedCategoryId.value)?.name || '')
const selectedTagNames = computed(() => { const idSet = new Set(selectedTagIds.value); return styleTags.value.filter((item) => idSet.has(Number(item.id))).map((item) => String(item.name || '').trim()).filter(Boolean) })
const selectedTagSummary = computed(() => !selectedTagNames.value.length ? '' : selectedTagNames.value.length <= 2 ? selectedTagNames.value.join('、') : `${selectedTagNames.value.slice(0, 2).join('、')} 等${selectedTagNames.value.length}项`)
const tagFieldText = computed(() => !selectedCategoryId.value ? '请先选择服务分类' : !styleTags.value.length ? '当前分类暂无可选标签' : selectedTagSummary.value || '请选择风格标签')
const tagDisabled = computed(() => !selectedCategoryId.value || !styleTags.value.length)
const currentSortName = computed(() => sortOptions.find((item) => item.value === currentSort.value)?.label || '综合排序')
const hasSelectedRegion = computed(() => hasServiceRegion(selectedRegion.value))
const selectedDateText = computed(() => { if (!selectedDate.value) return '请选择婚礼日期'; const parsedDate = parseDateText(selectedDate.value); if (!parsedDate) return '请选择婚礼日期'; const weekMap = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']; return `${parsedDate.getFullYear()} 年 ${parsedDate.getMonth() + 1} 月 ${parsedDate.getDate()} 日（${weekMap[parsedDate.getDay()]}）` })
const selectedRegionText = computed(() => { if (!hasSelectedRegion.value) return '请选择服务地区'; const cityName = selectedRegion.value.city_name || selectedRegion.value.province_name; const districtName = selectedRegion.value.district_name; return cityName && districtName ? `${cityName} · ${districtName}` : formatServiceRegionText(selectedRegion.value, ' / ') })

const datePickerYears = computed(() => { const minDate = getTomorrowDate(); const maxDate = getMaxDateForPicker(); return Array.from({ length: maxDate.getFullYear() - minDate.getFullYear() + 1 }, (_, index) => minDate.getFullYear() + index) })
const getDatePickerMonthsByYear = (year: number) => { const minDate = getTomorrowDate(); const maxDate = getMaxDateForPicker(); const startMonth = year === minDate.getFullYear() ? minDate.getMonth() + 1 : 1; const endMonth = year === maxDate.getFullYear() ? maxDate.getMonth() + 1 : 12; return Array.from({ length: endMonth - startMonth + 1 }, (_, index) => startMonth + index) }
const getDatePickerDaysByYearMonth = (year: number, month: number) => { const minDate = getTomorrowDate(); const maxDate = getMaxDateForPicker(); const isMinMonth = year === minDate.getFullYear() && month === minDate.getMonth() + 1; const isMaxMonth = year === maxDate.getFullYear() && month === maxDate.getMonth() + 1; const startDay = isMinMonth ? minDate.getDate() : 1; const endDay = isMaxMonth ? maxDate.getDate() : new Date(year, month, 0).getDate(); return Array.from({ length: endDay - startDay + 1 }, (_, index) => startDay + index) }
const normalizeDatePickerValue = (value: number[]) => { const yearIndex = Math.min(Math.max(value[0] ?? 0, 0), datePickerYears.value.length - 1); const year = datePickerYears.value[yearIndex]; const months = getDatePickerMonthsByYear(year); const monthIndex = Math.min(Math.max(value[1] ?? 0, 0), months.length - 1); const month = months[monthIndex]; const days = getDatePickerDaysByYearMonth(year, month); const dayIndex = Math.min(Math.max(value[2] ?? 0, 0), days.length - 1); return [yearIndex, monthIndex, dayIndex] }
const datePickerMonths = computed(() => getDatePickerMonthsByYear(datePickerYears.value[Math.max(datePickerValue.value[0] ?? 0, 0)]))
const datePickerDays = computed(() => getDatePickerDaysByYearMonth(datePickerYears.value[Math.max(datePickerValue.value[0] ?? 0, 0)], datePickerMonths.value[Math.max(datePickerValue.value[1] ?? 0, 0)]))

const regionProvinces = computed(() => regionTree.value || [])
const regionCities = computed(() => regionTree.value.find((item: any) => item.province_code === tempRegion.value.province_code)?.cities || [])
const regionDistricts = computed(() => regionCities.value.find((item: any) => item.city_code === tempRegion.value.city_code)?.districts || [])
const getRegionItemStyle = (active: boolean) => active ? { background: alphaColor($theme.primaryColor, 0.1), color: $theme.primaryColor } : {}

const syncTempRegion = (value?: Record<string, any>) => { tempRegion.value = normalizeServiceRegion(value || selectedRegion.value); if (!tempRegion.value.province_code && regionTree.value.length) { handleProvinceSelect(regionTree.value[0]); return } if (!tempRegion.value.city_code && regionCities.value.length) handleCitySelect(regionCities.value[0]) }
const syncDatePickerValue = (value = '') => { const targetDate = getEffectiveSelectableDate(value); const yearIndex = Math.max(datePickerYears.value.indexOf(targetDate.getFullYear()), 0); const months = getDatePickerMonthsByYear(datePickerYears.value[yearIndex]); const monthIndex = Math.max(months.indexOf(targetDate.getMonth() + 1), 0); const days = getDatePickerDaysByYearMonth(datePickerYears.value[yearIndex], months[monthIndex]); const dayIndex = Math.max(days.indexOf(targetDate.getDate()), 0); datePickerValue.value = [yearIndex, monthIndex, dayIndex] }

const getCategories = async () => { try { const data = await getServiceCategories(); categories.value = flattenCategories(Array.isArray(data) ? data : []) } catch (error) { categories.value = []; console.error('获取服务分类失败：', error) } }
const getRegionTree = async () => { try { const data = await getServiceRegionTree(); regionTree.value = Array.isArray(data) ? data : []; if (!regionTree.value.length) { selectedRegion.value = normalizeServiceRegion({}); tempRegion.value = normalizeServiceRegion({}); return } syncTempRegion() } catch (error) { regionTree.value = []; console.error('获取服务地区失败：', error) } }
const getCategoryTags = async () => { if (!selectedCategoryId.value) { styleTags.value = []; selectedTagIds.value = []; tempSelectedTagIds.value = []; return } try { const data = await getStyleTags({ category_id: Number(selectedCategoryId.value) }); styleTags.value = Array.isArray(data) ? data : []; const validIds = new Set(styleTags.value.map((item) => Number(item.id))); selectedTagIds.value = selectedTagIds.value.filter((id) => validIds.has(id)); tempSelectedTagIds.value = [...selectedTagIds.value] } catch (error) { styleTags.value = []; selectedTagIds.value = []; tempSelectedTagIds.value = []; console.error('获取风格标签失败：', error) } }

const openDatePicker = () => { syncDatePickerValue(selectedDate.value); showDatePopup.value = true }
const closeDatePicker = () => { showDatePopup.value = false }
const handleDatePickerChange = (event: any) => { datePickerValue.value = normalizeDatePickerValue(event.detail.value || []) }
const confirmDatePicker = () => { const year = datePickerYears.value[datePickerValue.value[0]]; const month = String(datePickerMonths.value[datePickerValue.value[1]]).padStart(2, '0'); const day = String(datePickerDays.value[datePickerValue.value[2]]).padStart(2, '0'); selectedDate.value = `${year}-${month}-${day}`; closeDatePicker() }

const openRegionPicker = () => { syncTempRegion(); showRegionPopup.value = true }
const closeRegionPicker = () => { showRegionPopup.value = false }
const handleProvinceSelect = (province: any) => { tempRegion.value = normalizeServiceRegion({ province_code: province?.province_code || '', province_name: province?.province_name || '', city_code: '', city_name: '', district_code: '', district_name: '' }); const firstCity = (province?.cities || [])[0]; if (firstCity) handleCitySelect(firstCity) }
const handleCitySelect = (city: any) => { tempRegion.value = normalizeServiceRegion({ province_code: city?.province_code || tempRegion.value.province_code, province_name: city?.province_name || tempRegion.value.province_name, city_code: city?.city_code || '', city_name: city?.city_name || '', district_code: '', district_name: '' }) }
const handleDistrictSelect = (district: any) => { tempRegion.value = normalizeServiceRegion({ ...tempRegion.value, province_code: district?.province_code || tempRegion.value.province_code, province_name: district?.province_name || tempRegion.value.province_name, city_code: district?.city_code || tempRegion.value.city_code, city_name: district?.city_name || tempRegion.value.city_name, district_code: district?.district_code || '', district_name: district?.district_name || '' }) }
const resetRegionSelection = () => { tempRegion.value = normalizeServiceRegion({}) }
const confirmRegionPicker = () => { if (!hasServiceRegion(tempRegion.value)) { uni.showToast({ title: '请选择到区县', icon: 'none' }); return } selectedRegion.value = normalizeServiceRegion(tempRegion.value); saveServiceRegionSelection(selectedRegion.value); closeRegionPicker() }

const handleCategorySelect = async (id: number) => { if (selectedCategoryId.value === id) return; selectedCategoryId.value = id; selectedTagIds.value = []; tempSelectedTagIds.value = []; await getCategoryTags() }
const openTagPicker = () => { if (!selectedCategoryId.value) { uni.showToast({ title: '请先选择服务分类', icon: 'none' }); return } if (!styleTags.value.length) { uni.showToast({ title: '当前分类暂无可选标签', icon: 'none' }); return } tempSelectedTagIds.value = [...selectedTagIds.value]; showTagPopup.value = true }
const closeTagPicker = () => { showTagPopup.value = false }
const handleTagPopupClose = () => { tempSelectedTagIds.value = [...selectedTagIds.value] }
const toggleTagSelection = (id: number | string) => { const tagId = Number(id); if (!tagId) return; const index = tempSelectedTagIds.value.indexOf(tagId); if (index > -1) { tempSelectedTagIds.value.splice(index, 1); return } tempSelectedTagIds.value.push(tagId) }
const resetTagSelection = () => { tempSelectedTagIds.value = [] }
const confirmTagPicker = () => { selectedTagIds.value = [...tempSelectedTagIds.value]; closeTagPicker() }
const handleSortChange = (sort: string) => { currentSort.value = isValidSortValue(sort) ? sort : 'default' }

const validateSubmit = () => {
    if (!selectedDate.value) { uni.showToast({ title: '请选择预约日期', icon: 'none' }); return false }
    if (!hasSelectedRegion.value) { uni.showToast({ title: '请选择服务地区', icon: 'none' }); return false }
    if (!selectedCategoryId.value) { uni.showToast({ title: '请选择服务分类', icon: 'none' }); return false }
    return true
}

const handleSubmit = () => {
    if (!validateSubmit()) return
    const queryParts = [`date=${encodeURIComponent(selectedDate.value)}`, `category_id=${selectedCategoryId.value}`, `category_name=${encodeURIComponent(selectedCategoryName.value)}`]
    const trimmedKeyword = keyword.value.trim()
    if (trimmedKeyword) queryParts.push(`keyword=${encodeURIComponent(trimmedKeyword)}`)
    if (selectedTagIds.value.length) queryParts.push(`tag_ids=${selectedTagIds.value.join(',')}`)
    if (selectedTagNames.value.length) queryParts.push(`tag_names=${encodeURIComponent(selectedTagNames.value.join('、'))}`)
    if (currentSort.value !== 'default') queryParts.push(`sort=${encodeURIComponent(currentSort.value)}`)
    const regionQuery = buildServiceRegionQuery(selectedRegion.value)
    if (regionQuery) queryParts.push(regionQuery)
    uni.navigateTo({ url: `/pages/staff_list/staff_list?${queryParts.join('&')}` })
}

onLoad(async (options) => {
    $theme.setScene('consumer')
    selectedRegion.value = normalizeServiceRegion({ ...loadServiceRegionSelection(), ...options })
    tempRegion.value = normalizeServiceRegion(selectedRegion.value)
    if (typeof options?.keyword === 'string') keyword.value = options.keyword.trim()
    if (typeof options?.date === 'string') selectedDate.value = normalizeSelectedDateText(options.date)
    if (options?.category_id) { const categoryId = Number(options.category_id); if (!Number.isNaN(categoryId) && categoryId > 0) selectedCategoryId.value = categoryId }
    if (options?.tag_ids) selectedTagIds.value = parseIdList(options.tag_ids)
    if (isValidSortValue(options?.sort)) currentSort.value = String(options?.sort)
    await Promise.all([getCategories(), getRegionTree()])
    if (selectedCategoryId.value) await getCategoryTags()
})

onShow(() => { $theme.setScene('consumer') })
</script>

<style lang="scss" scoped>
.page{min-height:100vh;background:#fcfbf9}
.content{padding:12rpx 20rpx calc(184rpx + env(safe-area-inset-bottom))}
.desc{display:block;padding:6rpx 0 8rpx;font-size:28rpx;line-height:1.6;color:#7f7b78}
.card{margin-top:16rpx;padding:32rpx 36rpx;border-radius:48rpx;border:1rpx solid #efe6e1;background:rgba(255,255,255,.84);backdrop-filter:blur(24rpx);box-shadow:0 8rpx 20rpx rgba(214,185,167,.08)}
.head{display:flex;align-items:center;justify-content:space-between;gap:20rpx}
.title{display:block;font-size:28rpx;font-weight:700;color:#1e2432}
.hint{font-size:22rpx;font-weight:600;color:var(--wm-color-primary,#e85a4f)}
.value{display:block;margin-top:12rpx;font-size:30rpx;font-weight:600;line-height:1.6;color:#1e2432;white-space:pre-wrap}
.muted,.helper,.empty{color:rgba(30,36,50,.42)}
.helper,.empty{display:block;margin-top:18rpx;font-size:26rpx;line-height:1.6}
.chips{display:flex;flex-wrap:wrap;gap:16rpx;margin-top:20rpx}
.chips.dense{gap:14rpx}
.chip{display:inline-flex;align-items:center;justify-content:center;min-height:64rpx;padding:0 28rpx;border-radius:999rpx;border:1rpx solid #efe6e1;background:rgba(255,255,255,.84);font-size:24rpx;font-weight:600;color:#7f7b78}
.chip.soft{background:rgba(252,251,249,.88)}
.chip.active{background:var(--wm-color-primary,#e85a4f);border-color:var(--wm-color-primary,#e85a4f);color:#fff;box-shadow:0 10rpx 18rpx rgba(232,90,79,.16)}
.dropdown{display:flex;align-items:center;justify-content:space-between;gap:16rpx;margin-top:18rpx;min-height:88rpx;padding:0 24rpx;border-radius:24rpx;border:1rpx solid #efe6e1;background:rgba(252,251,249,.9)}
.dropdown.disabled{background:rgba(248,241,236,.72)}
.dropdown__text{flex:1;min-width:0;font-size:28rpx;font-weight:600;color:#1e2432;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.dropdown.muted .dropdown__text{color:rgba(30,36,50,.42)}
.keyword{width:100%;min-height:52rpx;margin-top:12rpx;font-size:30rpx;font-weight:600;line-height:1.6;color:#1e2432}
.submit{display:flex;align-items:center;justify-content:center;width:100%;min-height:112rpx;border-radius:44rpx}
.submit__text{font-size:32rpx;font-weight:700;color:#fff}
.picker{padding-bottom:calc(24rpx + env(safe-area-inset-bottom));background:#fff}
.picker__head{display:flex;align-items:center;justify-content:space-between;padding:28rpx 32rpx 24rpx;border-bottom:1rpx solid rgba(239,230,225,.92)}
.picker__group{display:flex;align-items:center;gap:18rpx}
.picker__action{font-size:26rpx;color:#7f7b78}
.picker__action.primary,.picker__title{color:#1e2432;font-weight:700}
.picker__title{font-size:30rpx}
.picker__clear{font-size:24rpx;color:#7f7b78}
.region{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16rpx;padding:24rpx 24rpx 0}
.region__title{padding:0 12rpx 16rpx;font-size:24rpx;font-weight:700;color:#1e2432}
.region__scroll{height:420rpx;border-radius:24rpx;background:#fcfbf9}
.region__item{padding:20rpx 18rpx;font-size:24rpx;line-height:1.5;color:#7f7b78}
.date{padding:12rpx 24rpx 0}
.date__view{width:100%;height:420rpx}
.date__item{display:flex;align-items:center;justify-content:center;font-size:30rpx;color:#1e2432}
.panel{padding:24rpx;max-height:60vh;overflow-y:auto}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16rpx}
.grid__item{padding:24rpx 16rpx;border-radius:18rpx;border:1rpx solid #efe6e1;background:rgba(252,251,249,.92);font-size:26rpx;font-weight:500;line-height:1.4;color:#7f7b78;text-align:center}
.grid__item.active{color:#fff;font-weight:600;border-color:var(--wm-color-primary,#e85a4f);background:var(--wm-color-primary,#e85a4f);box-shadow:0 10rpx 18rpx rgba(232,90,79,.16)}
.picker__foot{display:flex;gap:18rpx;padding:24rpx}
.picker__btn{flex:1;display:flex;align-items:center;justify-content:center;min-height:84rpx;border-radius:999rpx;background:rgba(248,241,236,.86);font-size:28rpx;font-weight:600;color:#1e2432}
.picker__btn.primary-bg{color:#fff}
</style>
