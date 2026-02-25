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

    <view class="staff-list-page">
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
                        <tn-search-box
                            v-model="keyword"
                            placeholder="搜索人员姓名"
                            shape="round"
                            :show-action="true"
                            :search-button-bg-color="$theme.primaryColor"
                            :bg-color="'#F9FAFB'"
                            border
                            height="56"
                            @search="handleSearch"
                            @clear="handleSearch"
                        />
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
                                                  background: $theme.primaryColor,
                                                  borderColor: $theme.primaryColor,
                                                  color: '#FFFFFF'
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
                                                  background: $theme.primaryColor,
                                                  borderColor: $theme.primaryColor,
                                                  color: '#FFFFFF'
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
                        <view class="filter-item" @click="openTagPicker">
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
                            <view class="filter-item" @click="showDatePicker = true">
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
                        <view class="filter-item" @click="showSortPicker = true">
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

            <!-- 人员卡片列表 -->
            <view class="staff-cards">
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
                                <tn-icon name="bookmark" size="24" color="#999999" />
                                <text>{{ item.category_name }}</text>
                                <text v-if="item.experience_years" class="experience">
                                    | {{ item.experience_years }}年经验
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
                                <text class="price-symbol" :style="{ color: $theme.primaryColor }"
                                    >¥</text
                                >
                                <text class="price-value" :style="{ color: $theme.primaryColor }">{{
                                    item.price
                                }}</text>
                                <text class="price-unit">/次</text>
                            </view>
                        </view>
                        <view
                            class="book-btn"
                            :style="{
                                background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                            }"
                            @click.stop="goToDetail(item.id)"
                        >
                            <tn-icon name="calendar" size="28" color="#FFFFFF" />
                            <text>立即预约</text>
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
                                          color: '#FFFFFF'
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
                        :style="{ background: $theme.primaryColor }"
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
                                          color: '#FFFFFF'
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
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import TnDateTimePicker from '@tuniao/tnui-vue3-uniapp/components/date-time-picker/src/date-time-picker.vue'

const $theme = useThemeStore()

// 获取标签背景色（主题色浅色变体）
const getTagBgColor = () => {
    const color = $theme.primaryColor
    // 将hex转为rgba，透明度10%
    const hex = color.replace('#', '')
    const r = parseInt(hex.substring(0, 2), 16)
    const g = parseInt(hex.substring(2, 4), 16)
    const b = parseInt(hex.substring(4, 6), 16)
    return `rgba(${r}, ${g}, ${b}, 0.1)`
}

// 获取标签边框色（主题色浅色变体）
const getTagBorderColor = () => {
    const color = $theme.primaryColor
    // 将hex转为rgba，透明度30%
    const hex = color.replace('#', '')
    const r = parseInt(hex.substring(0, 2), 16)
    const g = parseInt(hex.substring(2, 4), 16)
    const b = parseInt(hex.substring(4, 6), 16)
    return `rgba(${r}, ${g}, ${b}, 0.3)`
}

const pagingRef = ref()
const categoryScrollNativeRef = ref<any>(null)
const keyword = ref('')
const staffList = ref<any[]>([])
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
    background: linear-gradient(180deg, #faf5ff 0%, #f5f5f5 100%);
}

/* 筛选头部 */
.filter-header {
    background: #ffffff;
    box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.06);
    position: sticky;
    top: 0;
    z-index: 100;
}

/* 搜索区域 */
.search-section {
    padding: 16rpx 20rpx;
    background: #ffffff;
}

/* 分类横滑区域 */
.category-scroll-wrapper {
    padding: 12rpx 0;
    border-top: 1rpx solid #f0f0f0;

    .category-scroll,
    .category-scroll-h5 {
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
        padding: 0 20rpx;
        white-space: nowrap;
        width: max-content;
    }

    .category-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-right: 12rpx;
        min-width: 120rpx;
        height: 56rpx;
        padding: 0 24rpx;
        border-radius: 28rpx;
        border: 2rpx solid #e5e7eb;
        background: #f9fafb;
        font-size: 26rpx;
        color: #4b5563;
        text-align: center;
        transition: all 0.2s ease;
        white-space: nowrap;

        &:active {
            transform: scale(0.96);
            opacity: 0.85;
        }

        &.active {
            font-weight: 600;
            box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.2);
        }

        &:last-child {
            margin-right: 20rpx;
        }
    }
}

/* 筛选条件栏 */
.filter-bar {
    display: flex;
    align-items: center;
    padding: 16rpx 20rpx;
    gap: 12rpx;
    border-top: 1rpx solid #f0f0f0;
    background: #ffffff;

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
            right: 8rpx;
            top: 50%;
            transform: translateY(-50%);
            width: 48rpx;
            height: 48rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;

            &:active {
                opacity: 0.6;
            }
        }
    }

    .filter-item {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6rpx;
        padding: 16rpx 20rpx;
        background: #f9fafb;
        border-radius: 16rpx;
        border: 2rpx solid transparent;
        font-size: 26rpx;
        color: #666666;
        transition: all 0.2s ease;
        min-height: 64rpx;

        &:active {
            transform: scale(0.95);
            background: #f0f0f0;
        }

        text {
            max-width: 120rpx;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;

            &.active {
                font-weight: 600;
            }
        }
    }
}

/* 选择器容器 */
.picker-container {
    background: #ffffff;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    width: 90vw;
    margin: 0 auto;

    .picker-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20rpx 24rpx;
        border-bottom: 1rpx solid #f0f0f0;

        .picker-header-left {
            display: flex;
            align-items: center;
            gap: 20rpx;
        }

        .picker-clear {
            font-size: 24rpx;
            color: #6b7280;

            &:active {
                opacity: 0.7;
            }
        }

        .picker-title {
            font-size: 30rpx;
            font-weight: 700;
            color: #333333;
        }

        .picker-close {
            width: 48rpx;
            height: 48rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;

            &:active {
                background: #f5f5f5;
            }
        }
    }

    .picker-content {
        flex: 1;
        overflow-y: auto;
        padding: 8rpx 0;

        .picker-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20rpx 24rpx;
            font-size: 28rpx;
            color: #333333;
            transition: all 0.2s ease;

            &:active {
                background: #f9fafb;
            }

            &.active {
                color: var(--color-primary);
                font-weight: 600;
                background: var(--color-primary-light-9);
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
                background: #f9fafb;
                border: 2rpx solid #e5e5e5;
                border-radius: 16rpx;
                text-align: center;
                font-size: 26rpx;
                color: #333333;
                font-weight: 500;
                transition: all 0.2s ease;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;

                &:active {
                    transform: scale(0.95);
                }

                &.active {
                    font-weight: 600;
                    border-color: transparent;
                    box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.3);
                }
            }
        }
    }

    .empty-picker {
        padding: 48rpx 0;
        text-align: center;
        font-size: 26rpx;
        color: #9ca3af;
    }

    .picker-footer {
        display: flex;
        gap: 16rpx;
        padding: 16rpx 24rpx 24rpx;
        border-top: 1rpx solid #f0f0f0;

        .picker-btn {
            flex: 1;
            height: 80rpx;
            border-radius: 16rpx;
            background: #f3f4f6;
            color: #374151;
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
            box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.3);

            &:active {
                box-shadow: 0 3rpx 10rpx rgba(124, 58, 237, 0.3);
            }
        }
    }
}

/* 人员卡片列表 */
.staff-cards {
    padding: 20rpx;
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

/* 人员卡片 */
.staff-card {
    background: #ffffff;
    border-radius: 20rpx;
    overflow: hidden;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;

    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
    }

    /* 卡片头部 */
    .card-header {
        display: flex;
        padding: 24rpx;
        gap: 20rpx;

        .staff-avatar {
            width: 160rpx;
            height: 160rpx;
            border-radius: 16rpx;
            flex-shrink: 0;
            box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
        }

        .staff-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;

            .info-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12rpx;

                .staff-name {
                    flex: 1;
                    font-size: 32rpx;
                    font-weight: 700;
                    color: #333333;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .favorite-btn {
                    width: 56rpx;
                    height: 56rpx;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    transition: all 0.2s ease;

                    &:active {
                        transform: scale(1.2);
                    }
                }
            }

            .staff-category {
                display: flex;
                align-items: center;
                gap: 8rpx;
                font-size: 24rpx;
                color: #999999;
                margin-top: 12rpx;

                .experience {
                    margin-left: 8rpx;
                }
            }

            .staff-rating {
                display: flex;
                align-items: center;
                gap: 24rpx;
                margin-top: 12rpx;

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
                    color: #999999;
                }
            }
        }
    }

    /* 卡片内容 */
    .card-content {
        padding: 0 24rpx 24rpx;

        .staff-profile {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 26rpx;
            color: #666666;
            line-height: 1.6;
            margin-bottom: 16rpx;
        }

        /* 人员标签 */
        .staff-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 12rpx;

            .tag-item {
                padding: 8rpx 16rpx;
                border-radius: 16rpx;

                .tag-text {
                    font-size: 24rpx;
                    font-weight: 500;
                }
            }
        }
    }

    /* 卡片底部 */
    .card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 24rpx;
        background: #f9fafb;
        border-top: 1rpx solid #f0f0f0;

        .price-section {
            flex: 1;

            .price-label {
                display: block;
                font-size: 22rpx;
                color: #999999;
                margin-bottom: 8rpx;
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
                    color: #999999;
                    margin-left: 4rpx;
                }
            }
        }

        .book-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8rpx;
            padding: 20rpx 40rpx;
            border-radius: 48rpx;
            color: #ffffff;
            font-size: 28rpx;
            font-weight: 600;
            box-shadow: 0 8rpx 24rpx rgba(124, 58, 237, 0.35);
            transition: all 0.2s ease;

            &:active {
                transform: scale(0.95);
                box-shadow: 0 4rpx 12rpx rgba(124, 58, 237, 0.35);
            }
        }
    }
}
</style>
