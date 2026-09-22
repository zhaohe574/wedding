<template>
    <page-meta :page-style="$theme.pageStyle" :scroll-enabled="!isAnyPickerOpen" />
    <PageShell scene="consumer" class="schedule-query-page">
        <!-- 顶部导航栏 -->
        <BaseNavbar
            class="schedule-query-page__navbar"
            title="档期查询"
            variant="solid"
            bg-color="#181614"
            text-color="#FFFDF8"
            @back="handleNavbarBack"
        />

        <view class="schedule-query-page__content">
            <!-- 核心预约条件卡片组 (Bespoke Inquiry Group) -->
            <view class="bespoke-card">
                <!-- 1. 婚礼日期 -->
                <view
                    class="bespoke-row"
                    hover-class="bespoke-row--hover"
                    :hover-stay-time="100"
                    @click="openDatePicker"
                    @tap="openDatePicker"
                >
                    <view class="bespoke-row__left">
                        <BaseIcon name="calendar" size="22" color="#C6A15B" />
                        <text class="bespoke-row__label">婚礼日期</text>
                        <text class="bespoke-row__required">*</text>
                    </view>
                    <view class="bespoke-row__right">
                        <text
                            class="bespoke-row__value"
                            :class="{ 'bespoke-row__value--placeholder': !selectedDate }"
                        >
                            {{ selectedDateText }}
                        </text>
                        <BaseIcon name="right" size="16" color="#C2BCB2" />
                    </view>
                </view>

                <view class="bespoke-divider" />

                <!-- 2. 服务地区 -->
                <view
                    class="bespoke-row"
                    hover-class="bespoke-row--hover"
                    :hover-stay-time="100"
                    @click="openRegionPicker"
                    @tap="openRegionPicker"
                >
                    <view class="bespoke-row__left">
                        <BaseIcon name="location" size="22" color="#C6A15B" />
                        <text class="bespoke-row__label">服务地区</text>
                        <text class="bespoke-row__required">*</text>
                    </view>
                    <view class="bespoke-row__right">
                        <text
                            class="bespoke-row__value"
                            :class="{ 'bespoke-row__value--placeholder': !hasSelectedRegion }"
                        >
                            {{ selectedRegionText }}
                        </text>
                        <BaseIcon name="right" size="16" color="#C2BCB2" />
                    </view>
                </view>

                <view class="bespoke-divider" />

                <!-- 3. 服务品类 -->
                <view
                    class="bespoke-row"
                    hover-class="bespoke-row--hover"
                    :hover-stay-time="100"
                    @click="openCategoryPicker"
                    @tap="openCategoryPicker"
                >
                    <view class="bespoke-row__left">
                        <BaseIcon name="team" size="22" color="#C6A15B" />
                        <text class="bespoke-row__label">服务品类</text>
                        <text class="bespoke-row__required">*</text>
                    </view>
                    <view class="bespoke-row__right">
                        <text
                            class="bespoke-row__value"
                            :class="{ 'bespoke-row__value--placeholder': !selectedCategoryName }"
                        >
                            {{ selectedCategoryName || '请选择品类' }}
                        </text>
                        <BaseIcon name="right" size="16" color="#C2BCB2" />
                    </view>
                </view>

                <view class="bespoke-divider" />

                <!-- 4. 风格偏好 (品类激活后可点击多选) -->
                <view
                    class="bespoke-row bespoke-row--multi"
                    :class="{ 'bespoke-row--disabled': !selectedCategoryId }"
                    hover-class="bespoke-row--hover"
                    :hover-stay-time="100"
                    @click="openTagPicker"
                    @tap="openTagPicker"
                >
                    <view class="bespoke-row__main">
                        <view class="bespoke-row__left">
                            <BaseIcon
                                name="tag"
                                size="22"
                                :color="selectedCategoryId ? '#C6A15B' : '#B5AFA4'"
                            />
                            <text class="bespoke-row__label">风格偏好</text>
                        </view>
                        <view class="bespoke-row__right">
                            <text
                                v-if="!selectedTagNames.length"
                                class="bespoke-row__value bespoke-row__value--placeholder"
                            >
                                {{ tagPlaceholderText }}
                            </text>
                            <text v-else class="bespoke-row__count">
                                已选 {{ selectedTagNames.length }} 项
                            </text>
                            <BaseIcon name="right" size="16" color="#C2BCB2" />
                        </view>
                    </view>

                    <!-- 已选标签胶囊流 -->
                    <view v-if="selectedTagNames.length" class="bespoke-tags-flow">
                        <view
                            v-for="name in selectedTagNames"
                            :key="name"
                            class="bespoke-tag"
                        >
                            <text class="bespoke-tag__text">{{ name }}</text>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 偏好与检索卡片组 (Preferences & Search) -->
            <view class="bespoke-card">
                <!-- 5. 排序规则 -->
                <view
                    class="bespoke-row"
                    hover-class="bespoke-row--hover"
                    :hover-stay-time="100"
                    @click="openSortPicker"
                    @tap="openSortPicker"
                >
                    <view class="bespoke-row__left">
                        <BaseIcon name="sort" size="22" color="#C6A15B" />
                        <text class="bespoke-row__label">结果排序</text>
                    </view>
                    <view class="bespoke-row__right">
                        <text class="bespoke-row__value">
                            {{ currentSortName }}
                        </text>
                        <BaseIcon name="right" size="16" color="#C2BCB2" />
                    </view>
                </view>

                <view class="bespoke-divider" />

                <!-- 6. 关键词搜索 -->
                <view class="bespoke-row bespoke-row--input">
                    <view class="bespoke-row__left">
                        <BaseIcon name="search" size="22" color="#C6A15B" />
                        <text class="bespoke-row__label">主创搜寻</text>
                    </view>
                    <view class="bespoke-row__right bespoke-row__right--input">
                        <input
                            v-model="keyword"
                            class="bespoke-input"
                            placeholder="输入主创姓名或团队名称"
                            placeholder-class="bespoke-input__placeholder"
                            confirm-type="search"
                            @confirm="handleSubmit"
                        />
                        <view
                            v-if="keyword"
                            class="bespoke-input__clear"
                            @click.stop="keyword = ''"
                            @tap.stop="keyword = ''"
                        >
                            <BaseIcon name="close" size="18" color="#A8A298" />
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 底部大典甄选操作栏 (重置 + 查询档期) -->
        <ActionArea class="schedule-query-page__action" sticky safeBottom>
            <view class="action-dock">
                <!-- 重置按钮 -->
                <view
                    class="action-dock__btn action-dock__btn--reset"
                    hover-class="action-dock__btn--reset-hover"
                    :hover-stay-time="100"
                    @click="handleReset"
                    @tap="handleReset"
                >
                    <BaseIcon name="refresh" size="20" color="#7A7265" />
                    <text class="action-dock__reset-text">重置</text>
                </view>

                <!-- 查询主按钮 -->
                <view
                    class="action-dock__btn action-dock__btn--submit"
                    hover-class="action-dock__btn--submit-hover"
                    :hover-stay-time="100"
                    @click="handleSubmit"
                    @tap="handleSubmit"
                >
                    <BaseIcon name="search" size="22" color="#D9BE82" />
                    <text class="action-dock__submit-text">查询档期</text>
                </view>
            </view>
        </ActionArea>

        <!-- 弹窗 1：日期选择器 (底层 BaseOverlayMask 与穿透统一) -->
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

        <!-- 弹窗 2：省市区选择器 (底层 BaseOverlayMask 统一) -->
        <BaseServiceRegionPicker
            v-model="selectedRegion"
            v-model:open="showRegionPopup"
            :data="regionTree"
            @confirm="handleServiceRegionConfirm"
            @cancel="closeRegionPicker"
        />

        <!-- 弹窗 3：服务品类半屏抽屉 (标准 BaseOverlayMask 与精致列表) -->
        <view
            v-if="showCategoryPopup"
            class="bespoke-drawer"
            :style="{ zIndex: 20080 }"
            @click="closeCategoryPicker"
            @tap="closeCategoryPicker"
            @touchmove.stop.prevent
        >
            <BaseOverlayMask :show="showCategoryPopup" :z-index="20079" @close="closeCategoryPicker" />
            <view class="bespoke-drawer__panel" :style="{ zIndex: 20081 }" @click.stop @tap.stop>
                <view class="bespoke-drawer__handle" />
                <view class="bespoke-drawer__head">
                    <text class="bespoke-drawer__title">选择服务品类</text>
                    <view
                        class="bespoke-drawer__close"
                        @click="closeCategoryPicker"
                        @tap="closeCategoryPicker"
                    >
                        <BaseIcon name="close" size="20" color="#8E8880" />
                    </view>
                </view>

                <scroll-view class="bespoke-drawer__scroll" scroll-y enhanced>
                    <view class="drawer-list">
                        <view
                            v-for="cat in categories"
                            :key="cat.id"
                            class="drawer-item"
                            :class="{ 'drawer-item--active': selectedCategoryId === cat.id }"
                            @click="selectCategoryItem(cat.id)"
                            @tap="selectCategoryItem(cat.id)"
                        >
                            <text class="drawer-item__name">{{ cat.name }}</text>
                            <BaseIcon
                                v-if="selectedCategoryId === cat.id"
                                name="check"
                                size="22"
                                color="#C6A15B"
                            />
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <!-- 弹窗 4：风格标签多选抽屉 (组件化 BaseMultiTextPicker) -->
        <BaseMultiTextPicker
            v-model="selectedTagValueList"
            v-model:open="showTagPopup"
            title="选择风格偏好"
            description="支持多选，精细化匹配心仪格调"
            :options="tagPickerOptions"
            @confirm="handleTagPickerConfirm"
            @cancel="handleTagPickerCancel"
        />

        <!-- 弹窗 5：排序方式半屏抽屉 (标准 BaseOverlayMask 与精致列表) -->
        <view
            v-if="showSortPopup"
            class="bespoke-drawer"
            :style="{ zIndex: 20080 }"
            @click="closeSortPicker"
            @tap="closeSortPicker"
            @touchmove.stop.prevent
        >
            <BaseOverlayMask :show="showSortPopup" :z-index="20079" @close="closeSortPicker" />
            <view class="bespoke-drawer__panel" :style="{ zIndex: 20081 }" @click.stop @tap.stop>
                <view class="bespoke-drawer__handle" />
                <view class="bespoke-drawer__head">
                    <text class="bespoke-drawer__title">选择排序方式</text>
                    <view
                        class="bespoke-drawer__close"
                        @click="closeSortPicker"
                        @tap="closeSortPicker"
                    >
                        <BaseIcon name="close" size="20" color="#8E8880" />
                    </view>
                </view>

                <view class="drawer-list">
                    <view
                        v-for="opt in sortOptions"
                        :key="opt.value"
                        class="drawer-item"
                        :class="{ 'drawer-item--active': currentSort === opt.value }"
                        @click="selectSortItem(opt.value)"
                        @tap="selectSortItem(opt.value)"
                    >
                        <text class="drawer-item__name">{{ opt.label }}</text>
                        <BaseIcon
                            v-if="currentSort === opt.value"
                            name="check"
                            size="22"
                            color="#C6A15B"
                        />
                    </view>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseDateTimePicker from '@/components/base/BaseDateTimePicker.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseMultiTextPicker from '@/components/base/BaseMultiTextPicker.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import BaseServiceRegionPicker from '@/components/base/BaseServiceRegionPicker.vue'
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
import { showError } from '@/utils/feedback'

interface CategoryItem {
    id: number
    name: string
}

const $theme = useThemeStore()
const sortOptions = [
    { label: '综合推荐', value: 'default' },
    { label: '价格从低到高', value: 'price_asc' },
    { label: '价格从高到低', value: 'price_desc' },
    { label: '用户评分最高', value: 'rating' },
    { label: '近期预约最多', value: 'order_count' }
]

const keyword = ref('')
const categories = ref<CategoryItem[]>([])
const selectedCategoryId = ref<number | ''>('')
const styleTags = ref<any[]>([])
const selectedTagIds = ref<number[]>([])
const currentSort = ref('default')
const selectedDate = ref('')
const entrySource = ref('')

// 弹窗状态管理
const showDatePopup = ref(false)
const showRegionPopup = ref(false)
const showCategoryPopup = ref(false)
const showTagPopup = ref(false)
const showSortPopup = ref(false)

const datePickerModel = ref('')
const regionTree = ref<any[]>([])
const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))

const isAnyPickerOpen = computed(
    () =>
        showDatePopup.value ||
        showRegionPopup.value ||
        showCategoryPopup.value ||
        showTagPopup.value ||
        showSortPopup.value
)

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

const tagPlaceholderText = computed(() => {
    if (!selectedCategoryId.value) return '请先选择品类'
    if (!styleTags.value.length) return '暂无风格标签'
    return '请选择风格（可选）'
})

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
    () => sortOptions.find((item) => item.value === currentSort.value)?.label || '综合推荐'
)

const hasSelectedRegion = computed(() => hasServiceRegion(selectedRegion.value))

const selectedDateText = computed(() => {
    if (!selectedDate.value) return '请选择婚期'
    const parsedDate = parseDateText(selectedDate.value)
    if (!parsedDate) return '请选择婚期'
    const weekMap = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
    return `${parsedDate.getFullYear()}年${
        parsedDate.getMonth() + 1
    }月${parsedDate.getDate()}日 (${weekMap[parsedDate.getDay()]})`
})

const selectedRegionText = computed(() => {
    if (!hasSelectedRegion.value) return '请选择服务地区'
    return formatServiceRegionText(selectedRegion.value, ' · ')
})

const getCategories = async () => {
    try {
        const data = await getServiceCategories()
        categories.value = flattenCategories(Array.isArray(data) ? data : [])
        const hasValidSelectedCategory = categories.value.some(
            (item) => item.id === selectedCategoryId.value
        )
        if (!hasValidSelectedCategory && categories.value.length) {
            selectedCategoryId.value = categories.value[0].id
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

/* 日期选择器 */
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

/* 地区选择器 */
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

/* 服务品类弹窗 */
const openCategoryPicker = () => {
    showCategoryPopup.value = true
}

const closeCategoryPicker = () => {
    showCategoryPopup.value = false
}

const selectCategoryItem = async (id: number) => {
    if (selectedCategoryId.value === id) {
        closeCategoryPicker()
        return
    }
    selectedCategoryId.value = id
    selectedTagIds.value = []
    closeCategoryPicker()
    await getCategoryTags()
}

/* 风格标签多选弹窗 */
const openTagPicker = () => {
    if (!selectedCategoryId.value) {
        showError('请先选择服务品类')
        return
    }
    if (!styleTags.value.length) {
        showError('当前品类暂无细分风格标签')
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

/* 排序方式弹窗 */
const openSortPicker = () => {
    showSortPopup.value = true
}

const closeSortPicker = () => {
    showSortPopup.value = false
}

const selectSortItem = (val: string) => {
    currentSort.value = isValidSortValue(val) ? val : 'default'
    closeSortPicker()
}

/* 重置条件 */
const handleReset = () => {
    keyword.value = ''
    selectedDate.value = ''
    selectedTagIds.value = []
    currentSort.value = 'default'
}

/* 提交与验证 */
const validateSubmit = () => {
    if (!selectedDate.value) {
        showError('请选择婚礼日期')
        return false
    }
    if (!hasSelectedRegion.value) {
        showError('请选择服务地区')
        return false
    }
    if (!selectedCategoryId.value) {
        showError('请选择服务品类')
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

const handleNavbarBack = () => {
    const pages = getCurrentPages()
    if (pages && pages.length > 1) {
        const prevPage = pages[pages.length - 2]
        const prevRoute = (prevPage as any)?.route || (prevPage as any)?.__route__ || ''
        if (prevRoute.includes('schedule_query')) {
            uni.navigateBack({ delta: 2 })
            return
        }
        uni.navigateBack({ delta: 1 })
        return
    }
    uni.switchTab({ url: '/pages/index/index' })
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
    const savedRegion = loadServiceRegionSelection()
    if (hasServiceRegion(savedRegion)) {
        selectedRegion.value = normalizeServiceRegion(savedRegion)
    }
})
</script>

<style lang="scss" scoped>
/* ==========================================================================
   Haute Wedding Couture - Schedule Query (Refined Jewelry-grade UI)
   ========================================================================== */
.schedule-query-page {
    background: #FAF8F4;
    min-height: 100vh;
    box-sizing: border-box;

    &__navbar {
        position: sticky;
        top: 0;
        z-index: 50;
    }

    &__content {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
        padding: 24rpx 28rpx calc(220rpx + env(safe-area-inset-bottom));
        box-sizing: border-box;
    }
}

/* 高奢定制卡片 (Bespoke Slate) */
.bespoke-card {
    background: #FFFFFF;
    border-radius: 24rpx;
    border: 1rpx solid rgba(198, 161, 91, 0.2);
    box-shadow: 0 4rpx 20rpx rgba(35, 30, 22, 0.03);
    padding: 0 28rpx;
    box-sizing: border-box;
    overflow: hidden;
}

/* 字段行 (Bespoke Field Row) */
.bespoke-row {
    min-height: 110rpx;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20rpx;
    transition: background 0.15s ease;

    &--hover {
        opacity: 0.75;
    }

    &--disabled {
        opacity: 0.4;
        pointer-events: none;
    }

    &--multi {
        flex-direction: column;
        align-items: stretch;
        justify-content: center;
        padding: 24rpx 0;
        min-height: auto;
    }

    &--input {
        &.bespoke-row--hover {
            opacity: 1;
        }
    }

    &__main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20rpx;
        width: 100%;
    }

    &__left {
        display: flex;
        align-items: center;
        gap: 14rpx;
        flex-shrink: 0;
    }

    &__label {
        font-size: 28rpx;
        font-weight: 700;
        color: #191713;
        line-height: 1;
        letter-spacing: 0.5rpx;
    }

    &__required {
        font-size: 24rpx;
        color: #C6A15B;
        margin-left: -6rpx;
        font-weight: 700;
    }

    &__right {
        display: flex;
        align-items: center;
        gap: 12rpx;
        min-width: 0;
        justify-content: flex-end;
        flex: 1;

        &--input {
            gap: 14rpx;
        }
    }

    &__value {
        font-size: 28rpx;
        font-weight: 600;
        color: #191713;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-align: right;

        &--placeholder {
            font-size: 27rpx;
            font-weight: 400;
            color: #ABA496;
        }
    }

    &__count {
        font-size: 23rpx;
        font-weight: 700;
        color: #8C6D2D;
        background: rgba(217, 190, 130, 0.16);
        padding: 4rpx 12rpx;
        border-radius: 999rpx;
        line-height: 1;
    }
}

/* 行间分割线 */
.bespoke-divider {
    height: 1rpx;
    background: rgba(198, 161, 91, 0.12);
    width: 100%;
}

/* 标签流式回显 */
.bespoke-tags-flow {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    padding-top: 14rpx;
}

.bespoke-tag {
    padding: 6rpx 16rpx;
    border-radius: 8rpx;
    background: rgba(217, 190, 130, 0.12);
    border: 1rpx solid rgba(217, 190, 130, 0.28);

    &__text {
        font-size: 22rpx;
        font-weight: 700;
        color: #7A5B20;
        line-height: 1.2;
    }
}

/* 关键词内嵌输入框 */
.bespoke-input {
    flex: 1;
    text-align: right;
    font-size: 27rpx;
    font-weight: 600;
    color: #191713;

    &__placeholder {
        font-size: 26rpx;
        font-weight: 400;
        color: #ABA496;
    }

    &__clear {
        padding: 8rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

/* ==========================================================================
   底部极简操作栏 (Dual Action Dock: Reset + Submit)
   ========================================================================== */
.schedule-query-page__action {
    background: linear-gradient(180deg, rgba(250, 248, 244, 0) 0%, rgba(250, 248, 244, 0.98) 30%, #FAF8F4 100%);
    padding: 16rpx 28rpx calc(24rpx + env(safe-area-inset-bottom));
}

.action-dock {
    display: flex;
    align-items: center;
    gap: 18rpx;
    width: 100%;

    &__btn {
        height: 96rpx;
        border-radius: 999rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        box-sizing: border-box;
        transition: all 0.15s ease;

        &--reset {
            width: 200rpx;
            background: #FFFFFF;
            border: 1rpx solid rgba(198, 161, 91, 0.35);
            box-shadow: 0 4rpx 16rpx rgba(35, 30, 22, 0.04);
            gap: 10rpx;
            flex-shrink: 0;
        }

        &--reset-hover {
            background: rgba(217, 190, 130, 0.1);
            border-color: rgba(198, 161, 91, 0.55);
            transform: scale(0.98);
        }

        &--submit {
            flex: 1;
            background: #181614;
            border: 1rpx solid rgba(217, 190, 130, 0.38);
            box-shadow: 0 10rpx 28rpx rgba(24, 22, 20, 0.22);
            gap: 14rpx;
        }

        &--submit-hover {
            opacity: 0.88;
            transform: scale(0.99);
        }
    }

    &__reset-text {
        font-size: 28rpx;
        font-weight: 700;
        color: #595245;
        line-height: 1;
    }

    &__submit-text {
        font-size: 30rpx;
        font-weight: 800;
        color: #FFFDF8;
        letter-spacing: 2rpx;
        line-height: 1;
    }
}

/* ==========================================================================
   极简抽屉样式 (Bespoke Bottom Drawer)
   ========================================================================== */
.bespoke-drawer {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    pointer-events: auto;

    &__panel {
        position: relative;
        background: #FFFFFF;
        border-top-left-radius: 36rpx;
        border-top-right-radius: 36rpx;
        padding: 16rpx 32rpx calc(36rpx + env(safe-area-inset-bottom));
        max-height: 72vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 -16rpx 44rpx rgba(24, 22, 20, 0.14);
        animation: drawerSlideUp 0.22s ease-out;
    }

    &__handle {
        width: 64rpx;
        height: 6rpx;
        border-radius: 999rpx;
        background: rgba(142, 136, 128, 0.25);
        margin: 6rpx auto 18rpx;
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 20rpx;
        border-bottom: 1rpx solid rgba(198, 161, 91, 0.16);
    }

    &__title {
        font-size: 30rpx;
        font-weight: 800;
        color: #191713;
        line-height: 1.2;
    }

    &__close {
        width: 52rpx;
        height: 52rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(24, 22, 20, 0.05);
    }

    &__scroll {
        max-height: 54vh;
        margin-top: 10rpx;
    }
}

/* 抽屉列表项 */
.drawer-list {
    display: flex;
    flex-direction: column;
    padding: 8rpx 0;
}

.drawer-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 28rpx 16rpx;
    border-radius: 14rpx;
    transition: background 0.12s ease;

    &:active {
        background: rgba(217, 190, 130, 0.08);
    }

    &__name {
        font-size: 28rpx;
        font-weight: 600;
        color: #332D24;
    }

    &--active {
        background: rgba(217, 190, 130, 0.12);

        .drawer-item__name {
            color: #7A5B20;
            font-weight: 800;
        }
    }
}

@keyframes drawerSlideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}
</style>
