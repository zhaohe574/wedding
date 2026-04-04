<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar title="搜索" />
        <view class="search-page">
            <!-- 搜索框区域 -->
            <view class="search-header">
                <tn-search-box
                    v-model="keyword"
                    placeholder="搜索人员/服务/作品"
                    :search-button-bg-color="$theme.primaryColor"
                    :search-button-text-color="$theme.btnColor"
                    @search="handleSearch"
                    @clear="handleSearchClear"
                ></tn-search-box>
            </view>

            <!-- 分类标签 -->
            <view class="search-tabs">
                <tn-tabs
                    v-model="currentTypeIndex"
                    :scroll="false"
                    height="88rpx"
                    :active-color="$theme.primaryColor"
                    :bar-color="$theme.primaryColor"
                >
                    <tn-tabs-item v-for="tab in searchTypes" :key="tab.value" :title="tab.label" />
                </tn-tabs>
            </view>

            <!-- 内容区域 -->
            <view class="search-content">
                <!-- 搜索建议（未搜索时） -->
                <suggest
                    v-show="!search.searching"
                    @search="handleSearch"
                    @clear="handleClear"
                    :hot_search="search.hot_search"
                    :his_search="search.his_search"
                ></suggest>

                <!-- 搜索结果 -->
                <view class="search-results" v-show="search.searching">
                    <z-paging
                        ref="paging"
                        v-model="search.result"
                        @query="queryList"
                        :fixed="false"
                        height="100%"
                        empty-view-text="暂无搜索结果"
                    >
                        <block v-for="item in search.result" :key="item.id">
                            <!-- 文章卡片 -->
                            <news-card
                                v-if="currentType === 'article'"
                                :item="item"
                                :newsId="item.id"
                            ></news-card>

                            <!-- 动态卡片 -->
                            <view v-else-if="currentType === 'dynamic'" class="dynamic-result-card">
                                <dynamic-card
                                    :dynamic="item"
                                    variant="default"
                                    :show-share="false"
                                    @click="handleDynamicDetail"
                                    @comment="handleDynamicDetail"
                                    @like="handleDynamicLike"
                                    @favorite="handleDynamicFavorite"
                                />
                            </view>

                            <!-- 人员卡片 -->
                            <staff-card
                                v-else-if="currentType === 'staff'"
                                :staff="item"
                                :show-favorite="false"
                                @click="handleStaffDetail"
                            />

                            <!-- 作品卡片 -->
                            <view
                                v-else-if="currentType === 'work'"
                                class="result-card"
                                @click="handleWorkDetail(item)"
                            >
                                <image
                                    class="result-cover"
                                    :src="
                                        item.cover ||
                                        item.images?.[0] ||
                                        '/static/images/user/default_avatar.png'
                                    "
                                    mode="aspectFill"
                                    lazy-load
                                />
                                <view class="result-info">
                                    <text class="result-title">{{
                                        item.title || '未命名作品'
                                    }}</text>
                                    <view class="result-meta">
                                        <tn-icon name="user" size="24" color="#999999" />
                                        <text class="result-staff">{{
                                            item.staff_name || '未知人员'
                                        }}</text>
                                    </view>
                                </view>
                            </view>
                        </block>
                    </z-paging>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { ref, reactive, shallowRef, computed, watch } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import Suggest from './component/suggest.vue'
import DynamicCard from '@/components/business/DynamicCard.vue'
import PageShell from '@/components/base/PageShell.vue'
import StaffCard from '@/components/business/StaffCard.vue'
import { HISTORY } from '@/enums/constantEnums'
import { getHotSearch } from '@/api/shop'
import cache from '@/utils/cache'
import { getArticleList } from '@/api/news'
import { getDynamicList, likeDynamic } from '@/api/dynamic'
import { getStaffList, getWorkLists, toggleStaffFavorite } from '@/api/staff'
import { useUserStore } from '@/stores/user'
import { useThemeStore } from '@/stores/theme'
import { mapDynamicItem } from '@/utils/dynamic'

const $theme = useThemeStore()

interface Search {
    hot_search: {
        data: any[]
        status: number
    }
    his_search: string[]
    result: any
    searching: boolean
}

const search = reactive<Search>({
    hot_search: {
        data: [],
        status: 1
    },
    his_search: [],
    result: [],
    searching: false
})
const keyword = ref<string>('')
const paging = shallowRef()
const userStore = useUserStore()

const searchTypes = [
    { label: '文章', value: 'article' },
    { label: '动态', value: 'dynamic' },
    { label: '人员', value: 'staff' },
    { label: '作品', value: 'work' }
]
const currentTypeIndex = ref(0)
const currentType = computed(() => searchTypes[currentTypeIndex.value]?.value || 'article')

watch(currentTypeIndex, () => {
    search.result = []
    if (keyword.value) {
        search.searching = true
        paging.value?.reload()
    } else {
        search.searching = false
    }
})

const handleSearch = (value: string) => {
    const nextKeyword = (value || '').trim()
    keyword.value = nextKeyword
    if (!keyword.value) {
        search.searching = false
        search.result = []
        return
    }
    if (keyword.value) {
        if (!search.his_search.includes(keyword.value)) {
            search.his_search.unshift(keyword.value)
            cache.set(HISTORY, search.his_search)
        }
    }
    paging.value?.reload()
    search.searching = true
}

const handleSearchClear = () => {
    search.searching = false
    search.result = []
}

const getHotSearchFunc = async () => {
    try {
        search.hot_search = await getHotSearch()
    } catch (e) {
        //TODO handle the exception
        console.log('获取热门搜索失败=>', e)
    }
}

const handleClear = async (): Promise<void> => {
    const resModel: any = await uni.showModal({
        title: '温馨提示',
        content: '是否清空历史记录？'
    })
    if (resModel.confirm) {
        cache.set(HISTORY, '')
        search.his_search = []
    }
}

const queryList = async (page_no: number, page_size: number) => {
    try {
        if (!keyword.value) {
            paging.value.complete([])
            return
        }
        if (currentType.value === 'article') {
            const { lists } = await getArticleList({
                keyword: keyword.value,
                page_no,
                page_size
            })
            paging.value.complete(lists)
            return
        }
        if (currentType.value === 'dynamic') {
            const res = await getDynamicList({
                keyword: keyword.value,
                page: page_no,
                page_size
            })
            const list = (res.data || []).map(mapDynamicItem)
            paging.value.complete(list)
            return
        }
        if (currentType.value === 'staff') {
            const { lists } = await getStaffList({
                keyword: keyword.value,
                page_no,
                page_size
            })
            const list = lists.map((item: any) => ({
                id: item.id,
                name: item.name,
                avatar: item.avatar,
                category: item.category_name || '',
                rating: Number(item.rating || 0),
                reviewCount: Number(item.order_count || 0),
                price: item.price ?? null,
                has_price:
                    item.has_price !== undefined && item.has_price !== null
                        ? Boolean(item.has_price)
                        : item.price !== null && item.price !== undefined,
                price_text: item.price_text || '',
                tags: item.tags || [],
                isFavorite: item.is_favorite || false
            }))
            paging.value.complete(list)
            return
        }
        if (currentType.value === 'work') {
            const { lists } = await getWorkLists({
                keyword: keyword.value,
                page_no,
                page_size
            })
            paging.value.complete(lists)
            return
        }
    } catch (e) {
        console.log('报错=>', e)
        //TODO handle the exception
        paging.value.complete(false)
    }
}

const handleDynamicDetail = (dynamic: any) => {
    if (!dynamic?.id) {
        return
    }
    uni.navigateTo({ url: `/pages/dynamic_detail/dynamic_detail?id=${dynamic.id}` })
}

const handleDynamicLike = async (dynamic: any) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeDynamic({ id: dynamic.id })
        dynamic.isLiked = !dynamic.isLiked
        dynamic.likeCount += dynamic.isLiked ? 1 : -1
    } catch (e: any) {
        uni.showToast({ title: e?.message || e || '操作失败', icon: 'none' })
    }
}

const handleDynamicFavorite = async (staffId: number) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        const target = search.result.find((item: any) => item.user?.staffId === staffId)
        if (!target?.user?.canFavorite) {
            return
        }

        const isFavorite = !target.user.isFavorite
        await toggleStaffFavorite({ id: staffId })
        search.result.forEach((item: any) => {
            if (item.user?.staffId === staffId) {
                item.user.isFavorite = isFavorite
            }
        })
        uni.showToast({ title: isFavorite ? '收藏成功' : '已取消收藏', icon: 'none' })
    } catch (e: any) {
        uni.showToast({ title: e?.message || e || '操作失败', icon: 'none' })
    }
}

const handleStaffDetail = (staff: any) => {
    if (!staff?.id) return
    uni.navigateTo({ url: `/packages/pages/staff_detail/staff_detail?id=${staff.id}` })
}

const handleWorkDetail = (work: any) => {
    if (!work?.staff_id) {
        uni.showToast({ title: '未找到关联人员', icon: 'none' })
        return
    }
    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?id=${work.staff_id}&tab=works`
    })
}

getHotSearchFunc()
search.his_search = cache.get(HISTORY) || []

onLoad((options: any) => {
    if (options?.type) {
        const index = searchTypes.findIndex((item) => item.value === options.type)
        if (index >= 0) {
            currentTypeIndex.value = index
        }
    }
    if (options?.keyword) {
        const preset = decodeURIComponent(options.keyword)
        handleSearch(preset)
    }
})
</script>

<style lang="scss" scoped>
.search-page {
    background: transparent;
}

// 搜索头部
.search-header {
    padding: 24rpx;
    background: #ffffff;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
}

// 分类标签
.search-tabs {
    background: #ffffff;
    padding: 0 12rpx;
    border-bottom: 1rpx solid #f0f0f0;
}

// 内容区域
.search-content {
    height: calc(100vh - 46px - 88rpx - 88rpx - env(safe-area-inset-bottom));
}

.search-results {
    height: 100%;
    padding-top: 24rpx;
}

.dynamic-result-card {
    margin: 0 24rpx 24rpx;
}

// 结果卡片（作品、套餐）
.result-card {
    margin: 0 24rpx 24rpx;
    background: #ffffff;
    border-radius: 16rpx;
    padding: 24rpx;
    display: flex;
    gap: 24rpx;
    box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;

    &:active {
        transform: translateY(-2rpx);
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
    }
}

.result-cover {
    width: 180rpx;
    height: 180rpx;
    border-radius: 12rpx;
    flex-shrink: 0;
    background: #f5f5f5;
}

.result-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-width: 0;
}

.result-header {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    margin-bottom: 12rpx;
}

.result-title {
    flex: 1;
    font-size: 30rpx;
    font-weight: 600;
    color: #333333;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-all;
}

.result-badge {
    padding: 6rpx 16rpx;
    border-radius: 999rpx;
    font-size: 22rpx;
    font-weight: 500;
    white-space: nowrap;
    border: 1rpx solid;
    flex-shrink: 0;
}

.result-meta {
    display: flex;
    align-items: center;
    gap: 8rpx;
    margin-top: 8rpx;
}

.result-staff {
    font-size: 24rpx;
    color: #999999;
}

.result-desc {
    font-size: 26rpx;
    color: #666666;
    line-height: 1.5;
    margin-bottom: 16rpx;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.result-price {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
}

.price-symbol {
    font-size: 28rpx;
    font-weight: 600;
}

.price-value {
    font-size: 40rpx;
    font-weight: 700;
}

.price-original {
    margin-left: 12rpx;
    font-size: 24rpx;
    color: #9ca3af;
    text-decoration: line-through;
}
</style>
