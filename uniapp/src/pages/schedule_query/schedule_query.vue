<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" class="schedule-query-page">
        <BaseNavbar
            class="schedule-query-page__navbar"
            title="档期查询"
            variant="solid"
            bg-color="var(--wm-nav-bg, #000000)"
            text-color="var(--wm-nav-text, #FFFDF8)"
        />

        <view class="schedule-query-page__content">
            <view class="showcase-picker-stack query-panel query-panel--fields">
                <BasePickerField
                    label="预约日期"
                    :model-value="selectedDate ? selectedDateText : ''"
                    placeholder="请选择婚礼日期"
                    icon="calendar"
                    hint="点击后打开底部日期选择器"
                    @click="openDatePicker"
                />
                <BasePickerField
                    label="预约地区"
                    :model-value="hasSelectedRegion ? selectedRegionText : ''"
                    placeholder="请选择服务地区"
                    icon="location"
                    status-text="省市区"
                    hint="请选择平台可服务的婚礼举办地区"
                    @click="openRegionPicker"
                />
            </view>

            <BaseCard variant="list" class="query-panel query-panel--category">
                <view class="section-head">
                    <view class="section-head__copy">
                        <text class="section-head__title">服务分类</text>
                        <text class="section-head__desc">选择本次需要预约的服务类型</text>
                    </view>
                    <text v-if="selectedCategoryName" class="section-head__value">{{
                        selectedCategoryName
                    }}</text>
                </view>
                <view v-if="categories.length" class="chip-list">
                    <FilterChip
                        v-for="item in categories"
                        :key="item.id"
                        :label="item.name"
                        :selected="selectedCategoryId === item.id"
                        @click="handleCategorySelect(item.id)"
                    />
                </view>
                <text v-else class="helper">暂无可选服务分类</text>
            </BaseCard>

            <BaseCard variant="panel" class="query-panel query-panel--tags">
                <BasePickerField
                    class="query-picker-field"
                    :class="{ 'query-picker-field--disabled': tagDisabled }"
                    label="风格标签"
                    :model-value="selectedTagSummary"
                    :placeholder="tagFieldText"
                    icon="tag"
                    :status-text="selectedTagIds.length ? `已选 ${selectedTagIds.length} 项` : '可选'"
                    :hint="tagHelperText"
                    @click="openTagPicker"
                />
            </BaseCard>

            <BaseCard variant="panel" class="query-panel query-panel--keyword">
                <view class="section-head section-head--compact">
                    <view class="section-head__copy">
                        <text class="section-head__title">关键词</text>
                        <text class="section-head__desc">可输入主持人姓名、团队名称等线索</text>
                    </view>
                </view>
                <view class="keyword-box">
                    <textarea
                        v-model="keyword"
                        class="keyword-box__input"
                        auto-height
                        confirm-type="search"
                        maxlength="80"
                        placeholder="主持人姓名等"
                        :placeholder-style="keywordPlaceholderStyle"
                        @confirm="handleSubmit"
                    />
                </view>
            </BaseCard>

            <BaseCard variant="list" class="query-panel query-panel--sort">
                <view class="section-head">
                    <view class="section-head__copy">
                        <text class="section-head__title">排序方式</text>
                        <text class="section-head__desc">按匹配度、价格或热度筛选服务团队</text>
                    </view>
                    <text class="section-head__value">{{ currentSortName }}</text>
                </view>
                <view class="chip-list chip-list--sort">
                    <FilterChip
                        v-for="item in sortOptions"
                        :key="item.value"
                        :label="item.label"
                        :selected="currentSort === item.value"
                        @click="handleSortChange(item.value)"
                    />
                </view>
            </BaseCard>
        </view>

        <ActionArea class="schedule-query-page__action" sticky safeBottom>
            <BaseButton variant="cta" size="lg" block class="submit" @click="handleSubmit">
                开始查询
            </BaseButton>
        </ActionArea>

        <BaseServiceRegionPicker
            v-model="selectedRegion"
            v-model:open="showRegionPopup"
            :data="regionTree"
            @confirm="handleServiceRegionConfirm"
            @cancel="closeRegionPicker"
        />

        <BaseDateTimePicker
            v-model="datePickerModel"
            v-model:open="showDatePopup"
            mode="date"
            format="YYYY-MM-DD"
            :min-time="datePickerMinText"
            :max-time="datePickerMaxText"
            @confirm="handleDatePickerConfirm"
            @cancel="closeDatePicker"
            @close="closeDatePicker"
        />

        <BaseMultiTextPicker
            v-model="selectedTagValueList"
            v-model:open="showTagPopup"
            title="选择风格标签"
            description="多选后会同步作为档期查询筛选条件。"
            :options="tagPickerOptions"
            @confirm="handleTagPickerConfirm"
            @cancel="handleTagPickerCancel"
        />
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseDateTimePicker from '@/components/base/BaseDateTimePicker.vue'
import BaseMultiTextPicker from '@/components/base/BaseMultiTextPicker.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BasePickerField from '@/components/base/BasePickerField.vue'
import BaseServiceRegionPicker from '@/components/base/BaseServiceRegionPicker.vue'
import FilterChip from '@/components/base/FilterChip.vue'
import PageShell from '@/components/base/PageShell.vue'
import { getServiceCategories, getServiceRegionTree, getStyleTags } from '@/api/service'
import { useThemeStore } from '@/stores/theme'
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
const currentSort = ref('default')
const selectedDate = ref('')
const entrySource = ref('')
const showDatePopup = ref(false)
const showRegionPopup = ref(false)
const showTagPopup = ref(false)
const datePickerModel = ref('')
const regionTree = ref<any[]>([])
const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))
const keywordPlaceholderStyle =
    'color: rgba(138, 128, 111, 0.78); font-size: 28rpx; font-weight: 600; line-height: 1.55;'

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
const extractPickerString = (value: unknown) => {
    if (typeof value === 'string') return value
    if (value && typeof value === 'object') {
        const record = value as Record<string, any>
        const detail = record.detail as Record<string, any> | undefined
        return String(record.value || detail?.value || '')
    }
    return String(value || '')
}
const flattenCategories = (tree: any[], result: CategoryItem[] = []): CategoryItem[] => {
    tree.forEach((item) => {
        result.push({ id: Number(item.id), name: item.name })
        if (Array.isArray(item.children) && item.children.length)
            flattenCategories(item.children, result)
    })
    return result
}

const datePickerMinText = computed(() => formatDateText(getTomorrowDate()))
const datePickerMaxText = computed(() => formatDateText(getMaxDateForPicker()))
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
        : '请选择风格标签'
)
const tagHelperText = computed(() =>
    !selectedCategoryId.value
        ? '选择服务分类后可继续筛选风格'
        : !styleTags.value.length
        ? '当前分类暂无可选风格标签'
        : selectedTagSummary.value || '可按婚礼风格继续缩小范围'
)
const tagDisabled = computed(() => !selectedCategoryId.value || !styleTags.value.length)
const tagPickerOptions = computed(() =>
    styleTags.value
        .map((item) => ({
            label: String(item.name || '').trim(),
            value: String(item.id || '').trim()
        }))
        .filter((item) => item.label && item.value)
)
const selectedTagValueList = computed<string[]>({
    get: () => selectedTagIds.value.map((id) => String(id)),
    set: (value) => {
        selectedTagIds.value = parseIdList(value)
    }
})
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
    return formatServiceRegionText(selectedRegion.value, ' / ')
})

const getCategories = async () => {
    try {
        const data = await getServiceCategories()
        categories.value = flattenCategories(Array.isArray(data) ? data : [])
        const hasValidSelectedCategory = categories.value.some(
            (item) => item.id === selectedCategoryId.value
        )
        if (!hasValidSelectedCategory) {
            selectedCategoryId.value = categories.value[0]?.id || ''
            selectedTagIds.value = []
        }
    } catch (error) {
        categories.value = []
        selectedCategoryId.value = ''
        console.error('获取服务分类失败：', error)
    }
}
const getRegionTree = async () => {
    try {
        const data = await getServiceRegionTree()
        regionTree.value = Array.isArray(data) ? data : []
        if (!regionTree.value.length) {
            selectedRegion.value = normalizeServiceRegion({})
            return
        }
    } catch (error) {
        regionTree.value = []
        console.error('获取服务地区失败：', error)
    }
}
const getCategoryTags = async () => {
    if (!selectedCategoryId.value) {
        styleTags.value = []
        selectedTagIds.value = []
        return
    }
    try {
        const data = await getStyleTags({ category_id: Number(selectedCategoryId.value) })
        styleTags.value = Array.isArray(data) ? data : []
        const validIds = new Set(styleTags.value.map((item) => Number(item.id)))
        selectedTagIds.value = selectedTagIds.value.filter((id) => validIds.has(id))
    } catch (error) {
        styleTags.value = []
        selectedTagIds.value = []
        console.error('获取风格标签失败：', error)
    }
}

const openDatePicker = () => {
    datePickerModel.value = formatDateText(getEffectiveSelectableDate(selectedDate.value))
    showDatePopup.value = true
}
const closeDatePicker = () => {
    showDatePopup.value = false
}
const handleDatePickerConfirm = (value: unknown) => {
    const pickerValue = extractPickerString(value) || datePickerModel.value
    selectedDate.value = normalizeSelectedDateText(pickerValue) || formatDateText(getTomorrowDate())
    closeDatePicker()
}

const openRegionPicker = () => {
    showRegionPopup.value = true
}
const closeRegionPicker = () => {
    showRegionPopup.value = false
}
const handleServiceRegionConfirm = (value: Record<string, any>) => {
    selectedRegion.value = normalizeServiceRegion(value)
    saveServiceRegionSelection(selectedRegion.value)
    closeRegionPicker()
}

const handleCategorySelect = async (id: number) => {
    if (selectedCategoryId.value === id) return
    selectedCategoryId.value = id
    selectedTagIds.value = []
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
    showTagPopup.value = true
}
const handleTagPickerConfirm = (value: string[]) => {
    selectedTagIds.value = parseIdList(value)
    showTagPopup.value = false
}
const handleTagPickerCancel = () => {
    showTagPopup.value = false
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
    --schedule-page-x: 18rpx;
    --schedule-panel-gap: 10rpx;
    --schedule-card-radius: 28rpx;
    --schedule-card-pad-x: 26rpx;
    --schedule-card-pad-y: 20rpx;
    --schedule-action-reserve: calc(216rpx + env(safe-area-inset-bottom));
    background: var(--wm-color-bg-page, #F5F1E8);
}

.schedule-query-page__content {
    position: relative;
    z-index: 1;
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: var(--schedule-panel-gap);
    padding: 12rpx var(--schedule-page-x) var(--schedule-action-reserve);
    box-sizing: border-box;
}

.showcase-picker-stack {
    display: flex;
    flex-direction: column;
    gap: 14rpx;
    padding: 16rpx;
    border-radius: var(--schedule-card-radius);
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
    background: linear-gradient(
        180deg,
        rgba(255, 253, 248, 0.98) 0%,
        var(--wm-color-bg-card, #FFFDF8) 100%
    );
    box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));
    box-sizing: border-box;
}

.query-panel {
    position: relative;
    width: 100%;
    min-width: 0;
}

.query-panel--tags,
.query-panel--keyword {
    --wm-space-card-padding-lg: var(--schedule-card-pad-y) var(--schedule-card-pad-x);
}

.query-panel--category,
.query-panel--sort {
    --wm-radius-list-panel: var(--schedule-card-radius);
    --wm-space-list-panel-y: var(--schedule-card-pad-y);
    --wm-space-list-panel-x: var(--schedule-card-pad-x);
}

.query-panel--tags :deep(.base-picker-field) {
    min-height: 92rpx;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}

.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14rpx;
    padding: 0 0 4rpx;
}

.section-head--compact {
    padding-bottom: 14rpx;
}

.section-head__copy {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8rpx;
}

.section-head__title {
    font-size: 28rpx;
    font-weight: 900;
    line-height: 1.25;
    color: var(--wm-text-primary, #191713);
}

.section-head__desc {
    font-size: 21rpx;
    font-weight: 600;
    line-height: 1.45;
    color: var(--wm-text-secondary, #665E52);
}

.section-head__value {
    flex-shrink: 0;
    max-width: 38%;
    padding-top: 2rpx;
    font-size: 22rpx;
    font-weight: 900;
    line-height: 1.35;
    color: var(--wm-color-gold, #B8954A);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.chip-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 14rpx;
    padding: 16rpx 0 0;
}

.chip-list--sort {
    gap: 14rpx 12rpx;
    padding-top: 14rpx;
}

.schedule-query-page :deep(.filter-chip) {
    max-width: 100%;
    box-sizing: border-box;
    min-height: 60rpx;
    padding: 0 22rpx;
    box-shadow: 0 8rpx 18rpx rgba(74, 43, 24, 0.045);
}

.schedule-query-page :deep(.filter-chip__text) {
    max-width: 220rpx;
    font-size: 23rpx;
}

.schedule-query-page :deep(.chip-list--sort .filter-chip) {
    min-width: 156rpx;
    min-height: 58rpx;
    padding: 0 18rpx;
}

.schedule-query-page :deep(.chip-list--sort .filter-chip__text) {
    max-width: 150rpx;
    font-size: 22rpx;
}

.schedule-query-page :deep(.base-picker-field) {
    width: 100%;
    min-width: 0;
    min-height: 96rpx;
    gap: 16rpx;
    padding: 0 22rpx;
    border-radius: 28rpx;
    box-sizing: border-box;
}

.schedule-query-page :deep(.base-picker-field__icon) {
    width: 54rpx;
    height: 54rpx;
    border-radius: 18rpx;
}

.schedule-query-page :deep(.base-picker-field__copy),
.schedule-query-page :deep(.base-picker-field__meta),
.schedule-query-page :deep(.base-picker-field__value),
.schedule-query-page :deep(.base-picker-field__hint) {
    min-width: 0;
    max-width: 100%;
}

.schedule-query-page :deep(.base-picker-field__label) {
    flex: 1;
}

.schedule-query-page :deep(.base-picker-field__hint) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 19rpx;
    line-height: 1.3;
}

.schedule-query-page :deep(.base-picker-field__label) {
    font-size: 21rpx;
}

.schedule-query-page :deep(.base-picker-field__status) {
    min-height: 26rpx;
    padding: 0 10rpx;
    font-size: 17rpx;
}

.schedule-query-page :deep(.base-picker-field__value) {
    font-size: 27rpx;
    line-height: 1.24;
}

.helper {
    display: block;
    padding: 16rpx 0 0;
    font-size: 24rpx;
    line-height: 1.55;
    color: var(--wm-text-tertiary, #8A806F);
}

.query-picker-field--disabled {
    opacity: 0.68;
}

.keyword-box {
    min-height: 86rpx;
    padding: 18rpx 24rpx;
    border-radius: 28rpx;
    border: 1rpx solid var(--wm-color-border, #D8C9AD);
    background: var(--wm-color-bg-soft, #FAF6EE);
    box-sizing: border-box;
}

.keyword-box__input {
    width: 100%;
    min-height: 46rpx;
    font-size: 27rpx;
    font-weight: 600;
    line-height: 1.55;
    color: var(--wm-text-primary, #191713);
}

.schedule-query-page :deep(.schedule-query-page__action.wm-action-area) {
    z-index: var(--wm-z-action, 90);
    padding: 24rpx 20rpx 34rpx;
    border-top: 0;
    background: linear-gradient(
        180deg,
        rgba(245, 241, 232, 0) 0%,
        rgba(245, 241, 232, 0.78) 18%,
        rgba(245, 241, 232, 0.98) 48%,
        #FFFDF8 100%
    );
    box-shadow: 0 -22rpx 48rpx rgba(74, 43, 24, 0.08);
}

.schedule-query-page :deep(.submit.base-button) {
    min-height: 82rpx;
    height: 82rpx;
    border-radius: 999rpx;
}

.schedule-query-page :deep(.schedule-query-page__action.wm-action-area--safe) {
    padding-bottom: calc(34rpx + env(safe-area-inset-bottom));
}
</style>
