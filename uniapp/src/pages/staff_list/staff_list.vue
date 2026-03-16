<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar
            title="服务人员"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
        <!-- #endif -->
    </page-meta>

    <view class="staff-list-page page-with-tabbar-safe-bottom">
        <!-- 人员列表 -->
        <z-paging
            ref="pagingRef"
            v-model="staffList"
            @query="queryList"
            :auto="false"
            :refresher-enabled="pagingRefresherEnabled"
        >
            <!-- 顶部固定区域 -->
            <template #top>
                <!-- 筛选头部 -->
                <view class="filter-header">
                    <!-- 搜索栏 -->
                    <view class="search-section">
                        <view class="search-row">
                            <view class="search-shell">
                                <tn-search-box
                                    v-model="keyword"
                                    placeholder="搜索人员姓名"
                                    shape="round"
                                    :show-action="true"
                                    :search-button-bg-color="$theme.primaryColor"
                                    :bg-color="'#FFFFFF'"
                                    border
                                    height="56"
                                    @search="handleSearch"
                                    @clear="handleSearch"
                                />
                            </view>
                            <view
                                class="view-switch-btn"
                                :style="{
                                    backgroundColor: alphaColor($theme.primaryColor, 0.08),
                                    borderColor: alphaColor($theme.primaryColor, 0.24),
                                    boxShadow: `0 6rpx 16rpx ${alphaColor($theme.primaryColor, 0.12)}`
                                }"
                                @tap.stop="handleToggleViewMode"
                            >
                                <view v-if="staffViewMode === 'poster'" class="switch-icon-list">
                                    <view class="switch-icon-list-row">
                                        <view
                                            class="switch-icon-list-dot"
                                            :style="{ background: $theme.primaryColor }"
                                        />
                                        <view
                                            class="switch-icon-list-line"
                                            :style="{ background: $theme.primaryColor }"
                                        />
                                    </view>
                                    <view class="switch-icon-list-row">
                                        <view
                                            class="switch-icon-list-dot"
                                            :style="{ background: $theme.primaryColor }"
                                        />
                                        <view
                                            class="switch-icon-list-line"
                                            :style="{ background: $theme.primaryColor }"
                                        />
                                    </view>
                                    <view class="switch-icon-list-row">
                                        <view
                                            class="switch-icon-list-dot"
                                            :style="{ background: $theme.primaryColor }"
                                        />
                                        <view
                                            class="switch-icon-list-line"
                                            :style="{ background: $theme.primaryColor }"
                                        />
                                    </view>
                                </view>
                                <view v-else class="switch-icon-grid">
                                    <view
                                        v-for="cell in 4"
                                        :key="cell"
                                        class="switch-icon-grid-cell"
                                        :style="{ borderColor: $theme.primaryColor }"
                                    />
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 顶部横滑分类 -->
                    <!-- #ifdef H5 -->
                    <view
                        class="category-scroll-wrapper category-scroll-wrapper-h5"
                        @touchstart.stop="handleCategoryTouchStart"
                        @touchmove.stop.prevent="handleCategoryTouchMove"
                        @touchend.stop="handleCategoryDragEnd"
                        @touchcancel.stop="handleCategoryDragEnd"
                        @mousedown.prevent="handleCategoryMouseDown"
                        @wheel.prevent="handleCategoryWheel"
                    >
                        <view
                            ref="categoryScrollNativeRef"
                            class="category-scroll category-scroll-h5-native"
                        >
                            <view class="category-scroll-content">
                                <view
                                    v-for="item in categories"
                                    :key="item.id"
                                    class="category-chip"
                                    :class="{ active: currentCategoryId === item.id }"
                                    :style="
                                        currentCategoryId === item.id
                                            ? {
                                                  background: getPrimaryGradient(),
                                                  borderColor: $theme.primaryColor,
                                                  color: '#FFFFFF',
                                                  boxShadow: getCategoryChipActiveShadow()
                                              }
                                            : {}
                                    "
                                    @click="handleCategoryChange(item.id)"
                                >
                                    {{ item.name }}
                                </view>
                            </view>
                        </view>
                    </view>
                    <!-- #endif -->

                    <!-- #ifndef H5 -->
                    <view class="category-scroll-wrapper">
                        <scroll-view
                            :scroll-x="true"
                            class="category-scroll"
                            :show-scrollbar="false"
                        >
                            <view class="category-scroll-content">
                                <view
                                    v-for="item in categories"
                                    :key="item.id"
                                    class="category-chip"
                                    :class="{ active: currentCategoryId === item.id }"
                                    :style="
                                        currentCategoryId === item.id
                                            ? {
                                                  background: getPrimaryGradient(),
                                                  borderColor: $theme.primaryColor,
                                                  color: '#FFFFFF',
                                                  boxShadow: getCategoryChipActiveShadow()
                                              }
                                            : {}
                                    "
                                    @click="handleCategoryChange(item.id)"
                                >
                                    {{ item.name }}
                                </view>
                            </view>
                        </scroll-view>
                    </view>
                    <!-- #endif -->

                    <!-- 筛选条件栏 -->
                    <view class="filter-bar">
                        <!-- 标签筛选 -->
                        <view
                            class="filter-item"
                            :style="selectedTagIds.length ? getFilterItemActiveStyle() : {}"
                            @click="openTagPicker"
                        >
                            <tn-icon
                                name="list"
                                size="28"
                                :color="selectedTagIds.length ? $theme.primaryColor : '#666666'"
                            />
                            <text
                                :class="{ active: selectedTagIds.length }"
                                :style="selectedTagIds.length ? { color: $theme.primaryColor } : {}"
                            >
                                {{ tagFilterText }}
                            </text>
                            <tn-icon
                                name="arrow-down"
                                size="24"
                                :color="selectedTagIds.length ? $theme.primaryColor : '#999999'"
                            />
                        </view>

                        <!-- 日期筛选 -->
                        <view class="filter-item-wrapper">
                            <view
                                class="filter-item"
                                :style="selectedDate ? getFilterItemActiveStyle() : {}"
                                @click="showDatePicker = true"
                            >
                                <tn-icon
                                    name="calendar"
                                    size="28"
                                    :color="selectedDate ? $theme.primaryColor : '#666666'"
                                />
                                <text
                                    :class="{ active: selectedDate }"
                                    :style="selectedDate ? { color: $theme.primaryColor } : {}"
                                >
                                    {{ dateRangeText }}
                                </text>
                                <tn-icon
                                    name="arrow-down"
                                    size="24"
                                    :color="selectedDate ? $theme.primaryColor : '#999999'"
                                />
                            </view>
                            <view
                                v-if="selectedDate"
                                class="clear-date-btn"
                                @click.stop="clearDate"
                            >
                                <tn-icon name="close-circle-fill" size="32" color="#999999" />
                            </view>
                        </view>

                        <!-- 排序筛选 -->
                        <view
                            class="filter-item"
                            :style="currentSort !== 'default' ? getFilterItemActiveStyle() : {}"
                            @click="showSortPicker = true"
                        >
                            <tn-icon
                                name="sort"
                                size="28"
                                :color="currentSort !== 'default' ? $theme.primaryColor : '#666666'"
                            />
                            <text
                                :class="{ active: currentSort !== 'default' }"
                                :style="
                                    currentSort !== 'default' ? { color: $theme.primaryColor } : {}
                                "
                            >
                                {{ currentSortName }}
                            </text>
                            <tn-icon
                                name="arrow-down"
                                size="24"
                                :color="currentSort !== 'default' ? $theme.primaryColor : '#999999'"
                            />
                        </view>
                    </view>
                </view>
            </template>

            <!-- 自定义空状态 -->
            <template #empty>
                <view class="empty-state">
                    <view class="empty-icon-wrap">
                        <tn-icon name="inbox" size="156" color="#D1D5DB" />
                    </view>
                    <text class="empty-title">暂无符合条件的服务人员</text>
                    <text class="empty-subtitle">试试调整筛选条件，发现更多优质团队</text>
                    <view
                        class="empty-action-btn"
                        :style="getPrimaryButtonStyle(0.26)"
                        @click="handleResetFilters"
                    >
                        <text class="empty-action-text" :style="{ color: $theme.btnColor }">
                            重置筛选
                        </text>
                    </view>
                </view>
            </template>

            <!-- 人员卡片列表 -->
            <view v-if="staffViewMode === 'poster'" class="poster-grid">
                <view
                    v-for="item in staffList"
                    :key="item.id"
                    class="poster-card"
                    @click="goToDetail(item.id)"
                >
                    <view class="poster-media">
                        <image
                            class="poster-image"
                            :src="item.avatar || '/static/images/user/default_avatar.png'"
                            mode="aspectFill"
                        />
                        <view class="poster-image-mask"></view>
                        <view
                            class="poster-category-badge"
                            :style="{
                                background: alphaColor('#111827', 0.72)
                            }"
                        >
                            <text>{{ item.category_name }}</text>
                        </view>
                        <view class="poster-favorite" @click.stop="handleToggleFavorite(item)">
                            <tn-icon
                                :name="item.is_favorite ? 'like-fill' : 'like'"
                                size="36"
                                :color="item.is_favorite ? '#FF4D5A' : '#FFFFFF'"
                            />
                        </view>
                        <view class="poster-overlay">
                            <view class="poster-name-row">
                                <text class="poster-name">{{ item.name }}</text>
                                <text v-if="item.experience_years" class="poster-experience">
                                    {{ item.experience_years }}年
                                </text>
                            </view>
                            <view class="poster-meta">
                                <view class="poster-rating">
                                    <tn-icon name="star-fill" size="24" color="#FFD166" />
                                    <text>{{ item.rating }}</text>
                                </view>
                                <text class="poster-orders">{{ item.order_count }}单</text>
                            </view>
                        </view>
                    </view>

                    <view class="poster-body">
                        <view v-if="item.tags && item.tags.length" class="poster-tags">
                            <view
                                v-for="(tag, index) in item.tags.slice(0, 2)"
                                :key="index"
                                class="poster-tag"
                                :style="{
                                    background: getTagBgColor(),
                                    borderColor: getTagBorderColor()
                                }"
                            >
                                <text :style="{ color: $theme.primaryColor }">{{ tag }}</text>
                            </view>
                        </view>
                        <view v-else class="poster-tags poster-tags-empty"></view>

                        <view class="poster-footer">
                            <view class="poster-price-section">
                                <template
                                    v-if="
                                        item.has_price !== false &&
                                        item.price !== null &&
                                        item.price !== undefined
                                    "
                                >
                                    <view class="poster-price-row">
                                        <text
                                            class="poster-price-symbol"
                                            :style="{ color: $theme.primaryColor }"
                                        >
                                            ¥
                                        </text>
                                        <text
                                            class="poster-price-value"
                                            :style="{ color: $theme.primaryColor }"
                                        >
                                            {{ item.price_text || item.price }}
                                        </text>
                                    </view>
                                    <text class="poster-price-unit">/次</text>
                                </template>
                                <text v-else class="poster-price-negotiable">面议</text>
                            </view>
                            <view
                                class="poster-book-btn"
                                :style="getPrimaryButtonStyle(0.2)"
                                @click.stop="goToDetail(item.id)"
                            >
                                <text :style="{ color: $theme.btnColor }">预约</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <view v-else class="staff-cards">
                <view
                    v-for="item in staffList"
                    :key="item.id"
                    class="staff-card"
                    @click="goToDetail(item.id)"
                >
                    <!-- 卡片头部 -->
                    <view class="card-header">
                        <image
                            class="staff-avatar"
                            :src="item.avatar || '/static/images/user/default_avatar.png'"
                            mode="aspectFill"
                        />
                        <view class="staff-info">
                            <view class="info-top">
                                <text class="staff-name">{{ item.name }}</text>
                                <view class="favorite-btn" @click.stop="handleToggleFavorite(item)">
                                    <tn-icon
                                        :name="item.is_favorite ? 'like-fill' : 'like'"
                                        size="40"
                                        :color="item.is_favorite ? '#FF2C3C' : '#CCCCCC'"
                                    />
                                </view>
                            </view>
                            <view class="staff-category">
                                <view
                                    class="category-chip-mini"
                                    :style="{
                                        background: getTagBgColor(),
                                        borderColor: getTagBorderColor()
                                    }"
                                >
                                    <text :style="{ color: $theme.primaryColor }">
                                        {{ item.category_name }}
                                    </text>
                                </view>
                                <text v-if="item.experience_years" class="experience">
                                    {{ item.experience_years }}年经验
                                </text>
                            </view>
                            <view class="staff-rating">
                                <view class="rating-stars">
                                    <tn-icon name="star-fill" size="28" color="#FFD700" />
                                    <text :style="{ color: $theme.ctaColor }">{{
                                        item.rating
                                    }}</text>
                                </view>
                                <view class="order-count">
                                    <tn-icon name="shopping-bag" size="24" color="#999999" />
                                    <text>{{ item.order_count }}单</text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 卡片内容 -->
                    <view class="card-content">
                        <text class="staff-profile">{{ item.profile || '暂无简介' }}</text>

                        <!-- 人员标签 -->
                        <view v-if="item.tags && item.tags.length" class="staff-tags">
                            <view
                                v-for="(tag, index) in item.tags"
                                :key="index"
                                class="tag-item"
                                :style="{
                                    background: getTagBgColor(),
                                    border: `1rpx solid ${getTagBorderColor()}`
                                }"
                            >
                                <text class="tag-text" :style="{ color: $theme.primaryColor }">{{
                                    tag
                                }}</text>
                            </view>
                        </view>
                    </view>

                    <!-- 卡片底部 -->
                    <view class="card-footer">
                        <view class="price-section">
                            <text class="price-label">服务价格</text>
                            <view class="price-amount">
                                <template
                                    v-if="
                                        item.has_price !== false &&
                                        item.price !== null &&
                                        item.price !== undefined
                                    "
                                >
                                    <text
                                        class="price-symbol"
                                        :style="{ color: $theme.primaryColor }"
                                        >¥</text
                                    >
                                    <text
                                        class="price-value"
                                        :style="{ color: $theme.primaryColor }"
                                    >
                                        {{ item.price_text || item.price }}
                                    </text>
                                    <text class="price-unit">/次</text>
                                </template>
                                <text v-else class="price-text-negotiable">面议</text>
                            </view>
                        </view>
                        <view
                            class="book-btn"
                            :style="getPrimaryButtonStyle(0.24)"
                            @click.stop="goToDetail(item.id)"
                        >
                            <text :style="{ color: $theme.btnColor }">立即预约</text>
                        </view>
                    </view>
                </view>
            </view>
        </z-paging>

        <!-- 标签选择器 -->
        <TnPopup
            v-model="showTagPicker"
            open-direction="bottom"
            :radius="24"
            :safe-area-inset-bottom="true"
        >
            <view class="picker-container">
                <view class="picker-header">
                    <view class="picker-header-left">
                        <view class="picker-clear" @click="resetTagSelection">重置</view>
                        <text class="picker-title">选择标签</text>
                    </view>
                    <view class="picker-close" @click="closeTagPicker">
                        <tn-icon name="close" size="32" color="#666666" />
                    </view>
                </view>
                <view class="button-picker-content">
                    <view v-if="styleTags.length" class="button-grid">
                        <view
                            v-for="item in styleTags"
                            :key="item.id"
                            class="button-item"
                            :class="{ active: tempSelectedTagIds.includes(item.id) }"
                            :style="
                                tempSelectedTagIds.includes(item.id)
                                    ? {
                                          background: $theme.primaryColor,
                                          color: '#FFFFFF',
                                          boxShadow: getPrimaryShadow(0.2)
                                      }
                                    : {}
                            "
                            @click="toggleTagSelection(item.id)"
                        >
                            {{ item.name }}
                        </view>
                    </view>
                    <view v-else class="empty-picker">当前分类暂无可选标签</view>
                </view>
                <view class="picker-footer">
                    <view class="picker-btn" @click="resetTagSelection">清空</view>
                    <view
                        class="picker-btn picker-btn-primary"
                        :style="{
                            background: $theme.primaryColor,
                            boxShadow: getPrimaryShadow(0.24)
                        }"
                        @click="handleTagFilterConfirm"
                    >
                        确定
                    </view>
                </view>
            </view>
        </TnPopup>

        <!-- 排序选择器 -->
        <TnPopup
            v-model="showSortPicker"
            open-direction="bottom"
            :radius="24"
            :safe-area-inset-bottom="true"
        >
            <view class="picker-container">
                <view class="picker-header">
                    <text class="picker-title">选择排序</text>
                    <view class="picker-close" @click="showSortPicker = false">
                        <tn-icon name="close" size="32" color="#666666" />
                    </view>
                </view>
                <view class="button-picker-content">
                    <view class="button-grid">
                        <view
                            v-for="item in sortOptions"
                            :key="item.value"
                            class="button-item"
                            :class="{ active: currentSort === item.value }"
                            :style="
                                currentSort === item.value
                                    ? {
                                          background: $theme.primaryColor,
                                          color: '#FFFFFF',
                                          boxShadow: getPrimaryShadow(0.2)
                                      }
                                    : {}
                            "
                            @click="handleSortChange(item.value)"
                        >
                            {{ item.label }}
                        </view>
                    </view>
                </view>
            </view>
        </TnPopup>

        <!-- 日期时间选择器 -->
        <TnDateTimePicker
            v-model="selectedDate"
            v-model:open="showDatePicker"
            mode="date"
            :min-time="getTomorrowDate()"
            :init-current-date-time="false"
            format="YYYY-MM-DD"
            :confirm-color="$theme.primaryColor"
            cancel-text="重置"
            cancel-color="#666666"
            @confirm="handleDateConfirm"
            @cancel="handleDateCancel"
            @close="handleDateClose"
        />

        <tabbar />
    </view>
</template>

<script lang="ts" setup>
import { ref, computed, onUnmounted } from 'vue'
import { onLoad, onReady } from '@dcloudio/uni-app'
import { getStaffList, toggleStaffFavorite } from '@/api/staff'
import { getServiceCategories, getStyleTags } from '@/api/service'
import { useThemeStore } from '@/stores/theme'
import { alphaColor } from '@/utils/color'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import TnDateTimePicker from '@tuniao/tnui-vue3-uniapp/components/date-time-picker/src/date-time-picker.vue'

type StaffViewMode = 'poster' | 'list'

const $theme = useThemeStore()
const STAFF_VIEW_MODE_STORAGE_KEY = 'staff_list_view_mode'

const getPrimaryGradient = () =>
    `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`

const getPrimaryShadow = (alpha = 0.2) => `0 8rpx 24rpx ${alphaColor($theme.primaryColor, alpha)}`

const getPrimaryButtonStyle = (alpha = 0.2) => ({
    backgroundColor: $theme.primaryColor,
    backgroundImage: getPrimaryGradient(),
    boxShadow: getPrimaryShadow(alpha)
})

const getCategoryChipActiveShadow = () => `0 2rpx 8rpx ${alphaColor($theme.primaryColor, 0.14)}`

const getFilterItemActiveStyle = () => ({
    background: alphaColor($theme.primaryColor, 0.1),
    borderColor: alphaColor($theme.primaryColor, 0.32),
    boxShadow: `0 6rpx 14rpx ${alphaColor($theme.primaryColor, 0.12)}`
})

// 获取标签背景色（主题色浅色变体）
const getTagBgColor = () => {
    return alphaColor($theme.primaryColor, 0.1)
}

// 获取标签边框色（主题色浅色变体）
const getTagBorderColor = () => {
    return alphaColor($theme.primaryColor, 0.28)
}

const isValidStaffViewMode = (value: unknown): value is StaffViewMode => {
    return value === 'poster' || value === 'list'
}

const getInitialStaffViewMode = (): StaffViewMode => {
    try {
        const cachedMode = uni.getStorageSync(STAFF_VIEW_MODE_STORAGE_KEY)
        return isValidStaffViewMode(cachedMode) ? cachedMode : 'poster'
    } catch (error) {
        console.error(error)
        return 'poster'
    }
}

const pagingRef = ref()
const categoryScrollNativeRef = ref<any>(null)
const keyword = ref('')
const staffList = ref<any[]>([])
const staffViewMode = ref<StaffViewMode>(getInitialStaffViewMode())
const categories = ref<any[]>([])
const currentCategoryId = ref<string | number>('')
const styleTags = ref<any[]>([])
const selectedTagIds = ref<number[]>([])
const tempSelectedTagIds = ref<number[]>([])
const currentSort = ref('default')

const categoryStartX = ref(0)
const categoryStartScrollLeft = ref(0)
const categoryMouseDragging = ref(false)
const categoryTouchDragging = ref(false)
const categoryMovedDistance = ref(0)
const categoryMoveThreshold = 6

// 弹窗控制
const showTagPicker = ref(false)
const showDatePicker = ref(false)
const showSortPicker = ref(false)

// 获取明天的日期（最小可选日期）
const getTomorrowDate = () => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    const year = tomorrow.getFullYear()
    const month = String(tomorrow.getMonth() + 1).padStart(2, '0')
    const day = String(tomorrow.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

// 日期筛选
const selectedDate = ref('')

const sortOptions = [
    { label: '综合排序', value: 'default' },
    { label: '价格从低到高', value: 'price_asc' },
    { label: '价格从高到低', value: 'price_desc' },
    { label: '评分最高', value: 'rating' },
    { label: '销量最高', value: 'order_count' }
]

// 计算属性
const tagFilterText = computed(() => {
    const count = selectedTagIds.value.length
    if (!count) {
        return '标签筛选'
    }
    if (count === 1) {
        const tag = styleTags.value.find((item) => Number(item.id) === selectedTagIds.value[0])
        return tag?.name || '已选1项'
    }
    return `已选${count}项`
})

const currentSortName = computed(() => {
    const sort = sortOptions.find((s) => s.value === currentSort.value)
    return sort ? sort.label : '排序'
})

const dateRangeText = computed(() => {
    if (selectedDate.value) {
        return selectedDate.value
    }
    return '日期'
})

// 日期选择处理
const pagingRefresherEnabled = computed(() => {
    return import.meta.env.UNI_PLATFORM !== 'h5'
})

const handleDateConfirm = (value: string) => {
    selectedDate.value = value
    showDatePicker.value = false
    pagingRef.value.reload()
}

const handleDateCancel = () => {
    selectedDate.value = ''
    showDatePicker.value = false
    pagingRef.value.reload()
}

const handleDateClose = () => {
    showDatePicker.value = false
}

// 清除日期
const clearDate = () => {
    selectedDate.value = ''
    pagingRef.value.reload()
}

const handleResetFilters = () => {
    keyword.value = ''
    selectedDate.value = ''
    selectedTagIds.value = []
    tempSelectedTagIds.value = []
    currentSort.value = 'default'
    showTagPicker.value = false
    showSortPicker.value = false
    pagingRef.value?.reload()
}

// 扁平化分类树
const flattenCategories = (tree: any[], result: any[] = []): any[] => {
    tree.forEach((item) => {
        result.push({ id: Number(item.id), name: item.name })
        if (item.children && item.children.length) {
            flattenCategories(item.children, result)
        }
    })
    return result
}

// 获取分类
const getCategories = async () => {
    try {
        const data = await getServiceCategories()
        categories.value = flattenCategories(data)
        if (!categories.value.length) {
            currentCategoryId.value = ''
            return
        }
        const hasCurrentCategory = categories.value.some(
            (item) => Number(item.id) === Number(currentCategoryId.value)
        )
        if (!hasCurrentCategory) {
            currentCategoryId.value = categories.value[0].id
        }
    } catch (e) {
        console.error(e)
    }
}

// 获取当前分类关联标签
const getCategoryTags = async () => {
    try {
        const params: { category_id?: number } = {}
        if (currentCategoryId.value !== '') {
            params.category_id = Number(currentCategoryId.value)
        }
        const data = await getStyleTags(params)
        styleTags.value = Array.isArray(data) ? data : []

        const validTagIds = new Set(styleTags.value.map((item) => Number(item.id)))
        selectedTagIds.value = selectedTagIds.value.filter((id) => validTagIds.has(id))
        tempSelectedTagIds.value = [...selectedTagIds.value]
    } catch (e) {
        styleTags.value = []
        selectedTagIds.value = []
        tempSelectedTagIds.value = []
        console.error(e)
    }
}

// 打开标签选择
const openTagPicker = async () => {
    if (!styleTags.value.length) {
        await getCategoryTags()
    }
    tempSelectedTagIds.value = [...selectedTagIds.value]
    showTagPicker.value = true
}

// 关闭标签选择
const closeTagPicker = () => {
    tempSelectedTagIds.value = [...selectedTagIds.value]
    showTagPicker.value = false
}

// 切换标签（多选）
const toggleTagSelection = (tagId: number | string) => {
    const id = Number(tagId)
    const index = tempSelectedTagIds.value.indexOf(id)
    if (index > -1) {
        tempSelectedTagIds.value.splice(index, 1)
    } else {
        tempSelectedTagIds.value.push(id)
    }
}

// 重置标签选择（弹窗内）
const resetTagSelection = () => {
    tempSelectedTagIds.value = []
}

// 确认标签筛选
const handleTagFilterConfirm = () => {
    selectedTagIds.value = [...tempSelectedTagIds.value]
    showTagPicker.value = false
    pagingRef.value.reload()
}

// 查询列表
const queryList = async (pageNo: number, pageSize: number) => {
    try {
        const params: any = {
            page_no: pageNo,
            page_size: pageSize,
            sort: currentSort.value
        }
        if (keyword.value) {
            params.keyword = keyword.value
        }
        if (currentCategoryId.value !== '') {
            params.category_id = Number(currentCategoryId.value)
        }
        if (selectedTagIds.value.length) {
            params.tag_ids = selectedTagIds.value.join(',')
        }
        if (selectedDate.value) {
            params.date = selectedDate.value
        }
        const res = await getStaffList(params)
        pagingRef.value.complete(res.lists)
    } catch (e) {
        pagingRef.value.complete(false)
    }
}

// 搜索
const handleSearch = () => {
    pagingRef.value.reload()
}

const handleToggleViewMode = () => {
    staffViewMode.value = staffViewMode.value === 'poster' ? 'list' : 'poster'
    try {
        uni.setStorageSync(STAFF_VIEW_MODE_STORAGE_KEY, staffViewMode.value)
    } catch (error) {
        console.error(error)
    }
}

// 切换分类
const handleCategoryChange = async (id: string | number) => {
    if (categoryMovedDistance.value > categoryMoveThreshold) {
        return
    }
    if (currentCategoryId.value === id) {
        return
    }
    currentCategoryId.value = id
    selectedTagIds.value = []
    tempSelectedTagIds.value = []
    await getCategoryTags()
    pagingRef.value.reload()
}

const getCategoryScrollElement = () => {
    const fromRef = categoryScrollNativeRef.value?.$el || categoryScrollNativeRef.value
    if (fromRef) {
        return fromRef as HTMLElement
    }
    if (typeof window === 'undefined') {
        return null
    }
    return document.querySelector('.category-scroll-h5-native') as HTMLElement | null
}

const clampCategoryScrollLeft = (value: number, scrollEl: HTMLElement) => {
    const maxScrollLeft = Math.max(0, scrollEl.scrollWidth - scrollEl.clientWidth)
    return Math.min(maxScrollLeft, Math.max(0, value))
}

const updateCategoryScrollByClientX = (clientX: number) => {
    const scrollEl = getCategoryScrollElement()
    if (!scrollEl) {
        return
    }
    const deltaX = clientX - categoryStartX.value
    const absDistance = Math.abs(deltaX)
    if (absDistance > categoryMovedDistance.value) {
        categoryMovedDistance.value = absDistance
    }
    const nextScrollLeft = categoryStartScrollLeft.value - deltaX
    scrollEl.scrollLeft = clampCategoryScrollLeft(nextScrollLeft, scrollEl)
}

const handleCategoryTouchStart = (event: TouchEvent) => {
    const scrollEl = getCategoryScrollElement()
    const touch = event.touches?.[0]
    if (!scrollEl || !touch) {
        return
    }
    categoryTouchDragging.value = true
    categoryStartX.value = touch.clientX
    categoryStartScrollLeft.value = scrollEl.scrollLeft
    categoryMovedDistance.value = 0
}

const handleCategoryTouchMove = (event: TouchEvent) => {
    if (!categoryTouchDragging.value) {
        return
    }
    const touch = event.touches?.[0]
    if (!touch) {
        return
    }
    updateCategoryScrollByClientX(touch.clientX)
}

const handleCategoryMouseMove = (event: MouseEvent) => {
    if (!categoryMouseDragging.value) {
        return
    }
    updateCategoryScrollByClientX(event.clientX)
}

const handleCategoryDragEnd = () => {
    categoryMouseDragging.value = false
    categoryTouchDragging.value = false
    if (typeof window !== 'undefined') {
        window.removeEventListener('mousemove', handleCategoryMouseMove)
        window.removeEventListener('mouseup', handleCategoryDragEnd)
    }
    setTimeout(() => {
        categoryMovedDistance.value = 0
    }, 0)
}

const handleCategoryMouseDown = (event: MouseEvent) => {
    const scrollEl = getCategoryScrollElement()
    if (!scrollEl) {
        return
    }
    categoryMouseDragging.value = true
    categoryStartX.value = event.clientX
    categoryStartScrollLeft.value = scrollEl.scrollLeft
    categoryMovedDistance.value = 0
    if (typeof window !== 'undefined') {
        window.addEventListener('mousemove', handleCategoryMouseMove)
        window.addEventListener('mouseup', handleCategoryDragEnd)
    }
}

const handleCategoryWheel = (event: WheelEvent) => {
    const scrollEl = getCategoryScrollElement()
    if (!scrollEl) {
        return
    }
    const moveX = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY
    scrollEl.scrollLeft = clampCategoryScrollLeft(scrollEl.scrollLeft + moveX, scrollEl)
}

onUnmounted(() => {
    handleCategoryDragEnd()
})

// 切换排序
const handleSortChange = (sort: string) => {
    currentSort.value = sort
    showSortPicker.value = false
    pagingRef.value.reload()
}

// 收藏/取消收藏
const handleToggleFavorite = async (item: any) => {
    try {
        await toggleStaffFavorite({ id: item.id })
        item.is_favorite = !item.is_favorite
        uni.showToast({
            title: item.is_favorite ? '收藏成功' : '已取消收藏',
            icon: 'none'
        })
    } catch (e: any) {
        uni.showToast({ title: e.msg || '操作失败', icon: 'none' })
    }
}

// 跳转详情
const goToDetail = (id: number) => {
    let url = `/packages/pages/staff_detail/staff_detail?id=${id}`
    if (selectedDate.value) {
        url += `&date=${selectedDate.value}`
    }
    uni.navigateTo({ url })
}

onLoad(async (options) => {
    if (options?.category_id) {
        const categoryId = Number(options.category_id)
        if (!Number.isNaN(categoryId) && categoryId > 0) {
            currentCategoryId.value = categoryId
        }
    }
    await getCategories()
    await getCategoryTags()
})

onReady(() => {
    pagingRef.value?.reload()
})
</script>

<style lang="scss" scoped>
.staff-list-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #fcf8ff 0%, #f8f6fb 42%, #f5f5f5 100%);
}

/* 顶部筛选区 */
.filter-header {
    position: sticky;
    top: 0;
    z-index: 100;
    padding: 12rpx 0 8rpx;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14rpx);
    border-bottom: 1rpx solid rgba(229, 231, 235, 0.8);
    box-shadow: 0 8rpx 24rpx rgba(15, 23, 42, 0.06);
}

.search-section {
    padding: 6rpx 20rpx 10rpx;
}

.search-row {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.search-shell {
    flex: 1;
    padding: 8rpx;
    border-radius: 34rpx;
    background: #ffffff;
    border: 1rpx solid #eceff4;
    box-shadow: 0 4rpx 14rpx rgba(15, 23, 42, 0.05);
}

.view-switch-btn {
    flex-shrink: 0;
    width: 88rpx;
    height: 88rpx;
    border-radius: 24rpx;
    border: 1rpx solid transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6rpx 16rpx rgba(15, 23, 42, 0.06);
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.98);
        opacity: 0.9;
    }
}

.switch-icon-list {
    width: 34rpx;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.switch-icon-list-row {
    display: flex;
    align-items: center;
    gap: 6rpx;
}

.switch-icon-list-dot {
    width: 6rpx;
    height: 6rpx;
    border-radius: 2rpx;
    flex-shrink: 0;
}

.switch-icon-list-line {
    flex: 1;
    height: 4rpx;
    border-radius: 999rpx;
}

.switch-icon-grid {
    width: 32rpx;
    height: 32rpx;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 4rpx;
}

.switch-icon-grid-cell {
    border: 2rpx solid transparent;
    border-radius: 6rpx;
    background: rgba(255, 255, 255, 0.45);
}

/* 分类横滑区域 */
.category-scroll-wrapper {
    padding: 8rpx 0 4rpx;

    .category-scroll {
        width: 100%;
        white-space: nowrap;

        &::-webkit-scrollbar {
            display: none;
        }
    }

    .category-scroll-h5-native {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x;

        &::-webkit-scrollbar {
            display: none;
        }
    }

    .category-scroll-content {
        display: inline-flex;
        align-items: center;
        padding: 6rpx 20rpx 10rpx;
        white-space: nowrap;
        width: max-content;
    }

    .category-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-right: 12rpx;
        min-width: 126rpx;
        height: 68rpx;
        padding: 0 26rpx;
        border-radius: 999rpx;
        border: 1rpx solid #e7eaf0;
        background: #f7f8fb;
        font-size: 26rpx;
        color: #4b5563;
        font-weight: 500;
        text-align: center;
        transition: all 0.2s ease;
        white-space: nowrap;

        &:active {
            transform: scale(0.98);
            opacity: 0.88;
        }

        &.active {
            font-weight: 600;
        }

        &:last-child {
            margin-right: 20rpx;
        }
    }
}

.category-scroll-wrapper-h5 {
    user-select: none;
    cursor: grab;

    &:active {
        cursor: grabbing;
    }
}

/* 筛选栏 */
.filter-bar {
    display: flex;
    align-items: center;
    padding: 12rpx 20rpx 8rpx;
    gap: 12rpx;

    .filter-item-wrapper {
        flex: 1;
        position: relative;
        display: flex;
        align-items: center;

        picker {
            flex: 1;
        }

        .clear-date-btn {
            position: absolute;
            right: 10rpx;
            top: 50%;
            transform: translateY(-50%);
            width: 56rpx;
            height: 56rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 8;

            &:active {
                opacity: 0.65;
            }
        }
    }

    .filter-item {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
        min-height: 88rpx;
        padding: 0 18rpx;
        background: #ffffff;
        border-radius: 20rpx;
        border: 1rpx solid #e8ebf0;
        font-size: 26rpx;
        color: #5b6473;
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.98);
            background: #f7f8fb;
        }

        text {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            text-align: center;

            &.active {
                font-weight: 600;
            }
        }
    }
}

/* 空状态 */
.empty-state {
    min-height: 58vh;
    padding: 180rpx 48rpx 220rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-icon-wrap {
    margin-bottom: 26rpx;
}

.empty-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 12rpx;
}

.empty-subtitle {
    font-size: 26rpx;
    color: #9aa3af;
    text-align: center;
    line-height: 1.5;
}

.empty-action-btn {
    margin-top: 34rpx;
    min-width: 240rpx;
    height: 88rpx;
    padding: 0 40rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;

    &:active {
        transform: translateY(2rpx) scale(0.98);
    }
}

.empty-action-text {
    font-size: 28rpx;
    font-weight: 600;
}

/* 选择器弹层 */
.picker-container {
    background: #ffffff;
    width: 100vw;
    max-width: 100vw;
    margin: 0;
    border-radius: 24rpx 24rpx 0 0;
    box-shadow: 0 -12rpx 36rpx rgba(15, 23, 42, 0.1);
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .picker-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22rpx 24rpx;
        border-bottom: 1rpx solid #eef1f5;

        .picker-header-left {
            display: flex;
            align-items: center;
            gap: 18rpx;
        }

        .picker-clear {
            font-size: 24rpx;
            color: #667085;

            &:active {
                opacity: 0.7;
            }
        }

        .picker-title {
            font-size: 30rpx;
            font-weight: 700;
            color: #1f2937;
        }

        .picker-close {
            width: 56rpx;
            height: 56rpx;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;

            &:active {
                background: #f4f5f7;
            }
        }
    }

    .button-picker-content {
        padding: 24rpx;
        max-height: 60vh;
        overflow-y: auto;

        .button-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16rpx;

            .button-item {
                padding: 24rpx 16rpx;
                background: #f8f9fb;
                border: 1rpx solid #e7ebf1;
                border-radius: 16rpx;
                text-align: center;
                font-size: 26rpx;
                color: #3f4a5a;
                font-weight: 500;
                transition: all 0.2s ease;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;

                &:active {
                    transform: scale(0.98);
                    opacity: 0.9;
                }

                &.active {
                    font-weight: 600;
                    border-color: transparent;
                    box-shadow: 0 6rpx 16rpx rgba(15, 23, 42, 0.14);
                }
            }
        }
    }

    .empty-picker {
        padding: 48rpx 0;
        text-align: center;
        font-size: 26rpx;
        color: #98a2b3;
    }

    .picker-footer {
        display: flex;
        gap: 16rpx;
        padding: 16rpx 24rpx 24rpx;
        border-top: 1rpx solid #eef1f5;

        .picker-btn {
            flex: 1;
            height: 82rpx;
            border-radius: 16rpx;
            background: #f3f4f6;
            color: #475467;
            font-size: 28rpx;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;

            &:active {
                opacity: 0.85;
                transform: scale(0.98);
            }
        }

        .picker-btn-primary {
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 6rpx 16rpx rgba(15, 23, 42, 0.14);

            &:active {
                box-shadow: 0 4rpx 10rpx rgba(15, 23, 42, 0.16);
            }
        }
    }
}

/* 人员卡片列表 */
.poster-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18rpx;
    padding: 20rpx;
}

.poster-card {
    background: #ffffff;
    border-radius: 24rpx;
    border: 1rpx solid #edf0f4;
    overflow: hidden;
    box-shadow: 0 8rpx 22rpx rgba(15, 23, 42, 0.08);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 12rpx 28rpx rgba(15, 23, 42, 0.12);
    }

    .poster-media {
        position: relative;
        height: 460rpx;
        overflow: hidden;
        background: #f3f4f6;
    }

    .poster-image {
        width: 100%;
        height: 100%;
        display: block;
    }

    .poster-image-mask {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            180deg,
            rgba(15, 23, 42, 0.04) 0%,
            rgba(15, 23, 42, 0.14) 48%,
            rgba(15, 23, 42, 0.82) 100%
        );
    }

    .poster-category-badge {
        position: absolute;
        top: 18rpx;
        left: 18rpx;
        max-width: calc(100% - 100rpx);
        padding: 8rpx 12rpx;
        border-radius: 12rpx;
        backdrop-filter: blur(10rpx);

        text {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 22rpx;
            font-weight: 600;
            color: #ffffff;
        }
    }

    .poster-favorite {
        position: absolute;
        top: 12rpx;
        right: 12rpx;
        width: 64rpx;
        height: 64rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(15, 23, 42, 0.22);
        backdrop-filter: blur(10rpx);

        &:active {
            transform: scale(1.04);
        }
    }

    .poster-overlay {
        position: absolute;
        right: 0;
        bottom: 0;
        left: 0;
        padding: 88rpx 18rpx 18rpx;
        color: #ffffff;
    }

    .poster-name-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    .poster-name {
        flex: 1;
        min-width: 0;
        font-size: 32rpx;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .poster-experience {
        flex-shrink: 0;
        padding: 4rpx 10rpx;
        border-radius: 999rpx;
        font-size: 20rpx;
        color: rgba(255, 255, 255, 0.92);
        background: rgba(255, 255, 255, 0.16);
    }

    .poster-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;
        margin-top: 12rpx;
    }

    .poster-rating {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;

        text {
            font-size: 24rpx;
            font-weight: 700;
            color: #ffd166;
        }
    }

    .poster-orders {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.86);
    }

    .poster-body {
        padding: 18rpx 18rpx 20rpx;
    }

    .poster-tags {
        min-height: 56rpx;
        display: flex;
        flex-wrap: wrap;
        gap: 8rpx;
        align-content: flex-start;
    }

    .poster-tags-empty {
        min-height: 0;
        margin-bottom: 0;
    }

    .poster-tag {
        max-width: 100%;
        padding: 6rpx 12rpx;
        border-radius: 999rpx;
        border: 1rpx solid transparent;

        text {
            display: block;
            max-width: 100%;
            font-size: 22rpx;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    .poster-footer {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 12rpx;
        margin-top: 16rpx;
    }

    .poster-price-section {
        flex: 1;
        min-width: 0;
    }

    .poster-price-row {
        display: flex;
        align-items: baseline;
        min-width: 0;
    }

    .poster-price-symbol {
        font-size: 24rpx;
        font-weight: 700;
    }

    .poster-price-value {
        max-width: 100%;
        font-size: 38rpx;
        font-weight: 800;
        line-height: 1;
        margin-left: 4rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .poster-price-unit {
        display: block;
        margin-top: 6rpx;
        font-size: 20rpx;
        color: #98a2b3;
    }

    .poster-price-negotiable {
        display: block;
        font-size: 32rpx;
        font-weight: 700;
        color: #98a2b3;
        line-height: 1.1;
    }

    .poster-book-btn {
        flex-shrink: 0;
        min-width: 118rpx;
        height: 64rpx;
        padding: 0 18rpx;
        border-radius: 999rpx;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4rpx;

        text {
            font-size: 22rpx;
            font-weight: 600;
        }

        &:active {
            transform: translateY(2rpx) scale(0.98);
        }
    }
}

.staff-cards {
    padding: 20rpx;
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.staff-card {
    background: #ffffff;
    border-radius: 24rpx;
    border: 1rpx solid #edf0f4;
    overflow: hidden;
    box-shadow: 0 8rpx 22rpx rgba(15, 23, 42, 0.08);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 12rpx 28rpx rgba(15, 23, 42, 0.12);
    }

    .card-header {
        display: flex;
        padding: 24rpx 24rpx 18rpx;
        gap: 20rpx;

        .staff-avatar {
            width: 172rpx;
            height: 172rpx;
            border-radius: 20rpx;
            flex-shrink: 0;
            border: 2rpx solid #ffffff;
            box-shadow: 0 8rpx 18rpx rgba(15, 23, 42, 0.12);
            background: #f3f4f6;
        }

        .staff-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;

            .info-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12rpx;

                .staff-name {
                    flex: 1;
                    font-size: 32rpx;
                    font-weight: 700;
                    color: #1f2937;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .favorite-btn {
                    width: 88rpx;
                    height: 88rpx;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    transition: all 0.2s ease;

                    &:active {
                        transform: scale(1.06);
                    }
                }
            }

            .staff-category {
                display: flex;
                align-items: center;
                gap: 12rpx;
                margin-top: 12rpx;

                .category-chip-mini {
                    display: inline-flex;
                    align-items: center;
                    max-width: 230rpx;
                    padding: 8rpx 16rpx;
                    border-radius: 14rpx;
                    border: 1rpx solid transparent;
                    font-size: 24rpx;
                    font-weight: 500;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                .experience {
                    font-size: 24rpx;
                    color: #7b8494;
                    white-space: nowrap;
                }
            }

            .staff-rating {
                display: flex;
                align-items: center;
                gap: 20rpx;
                margin-top: 14rpx;

                .rating-stars {
                    display: flex;
                    align-items: center;
                    gap: 8rpx;

                    text {
                        font-size: 28rpx;
                        font-weight: 700;
                    }
                }

                .order-count {
                    display: flex;
                    align-items: center;
                    gap: 8rpx;
                    font-size: 24rpx;
                    color: #98a2b3;
                }
            }
        }
    }

    .card-content {
        padding: 0 24rpx 20rpx;

        .staff-profile {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 26rpx;
            line-height: 1.62;
            color: #667085;
            margin-bottom: 16rpx;
        }

        .staff-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10rpx;

            .tag-item {
                padding: 8rpx 16rpx;
                border-radius: 999rpx;

                .tag-text {
                    font-size: 24rpx;
                    font-weight: 500;
                }
            }
        }
    }

    .card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        padding: 22rpx 24rpx;
        background: linear-gradient(180deg, #ffffff 0%, #fafbfc 100%);
        border-top: 1rpx solid #eef1f5;

        .price-section {
            flex: 1;

            .price-label {
                display: block;
                font-size: 22rpx;
                color: #98a2b3;
                margin-bottom: 6rpx;
            }

            .price-amount {
                display: flex;
                align-items: baseline;

                .price-symbol {
                    font-size: 28rpx;
                    font-weight: 700;
                    margin-right: 4rpx;
                }

                .price-value {
                    font-size: 44rpx;
                    font-weight: 800;
                    line-height: 1;
                }

                .price-unit {
                    font-size: 24rpx;
                    color: #98a2b3;
                    margin-left: 4rpx;
                }

                .price-text-negotiable {
                    font-size: 34rpx;
                    font-weight: 700;
                    color: #98a2b3;
                    line-height: 1.1;
                }
            }
        }

        .book-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8rpx;
            min-width: 196rpx;
            min-height: 88rpx;
            padding: 0 30rpx;
            border-radius: 999rpx;
            font-size: 28rpx;
            font-weight: 600;
            transition: all 0.2s ease;

            &:active {
                transform: translateY(2rpx) scale(0.98);
            }
        }
    }
}
</style>
