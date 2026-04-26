<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <view class="search-page" :style="searchPageStyle">
            <MpPageHeader title="搜索" surface="glass" sticky />
            <view class="search-header">
                <view class="search-header__input-shell">
                    <view class="search-header__input-wrap">
                        <tn-icon name="search" size="32" :color="$theme.primaryColor" />
                        <input
                            v-model="keyword"
                            class="search-header__input"
                            confirm-type="search"
                            placeholder="搜索人员、服务、作品"
                            placeholder-class="search-header__placeholder"
                            @confirm="handleSearch(keyword)"
                        />
                        <view v-if="keyword" class="search-header__clear" @tap="handleSearchClear">
                            <tn-icon name="close-circle-fill" size="28" color="#9A9388" />
                        </view>
                    </view>
                    <view class="search-header__action" @tap="handleSearch(keyword)">
                        <text class="search-header__action-text">搜索</text>
                    </view>
                </view>
            </view>

            <view class="search-tabs">
                <scroll-view scroll-x class="search-tabs__scroll" :show-scrollbar="false">
                    <view class="search-tabs__row">
                        <view
                            v-for="(tab, index) in searchTypes"
                            :key="tab.value"
                            class="search-tabs__item"
                            :class="{ 'search-tabs__item--active': currentTypeIndex === index }"
                            @tap="currentTypeIndex = index"
                        >
                            <text class="search-tabs__text">{{ tab.label }}</text>
                        </view>
                    </view>
                </scroll-view>
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

                <view class="search-results" v-show="search.searching">
                    <z-paging
                        ref="paging"
                        v-model="search.result"
                        @query="queryList"
                        :fixed="false"
                        height="100%"
                        empty-view-text=""
                    >
                        <block v-for="item in search.result" :key="item.id">
                            <news-card
                                v-if="currentType === 'article'"
                                :item="item"
                                :newsId="item.id"
                            ></news-card>

                            <view v-else-if="currentType === 'dynamic'" class="dynamic-result-card">
                                <dynamic-card
                                    :dynamic="item"
                                    variant="plaza-unified"
                                    :show-share="false"
                                    @click="handleDynamicDetail"
                                    @comment="handleDynamicDetail"
                                    @like="handleDynamicLike"
                                    @favorite="handleDynamicFavorite"
                                />
                            </view>

                            <staff-card
                                v-else-if="currentType === 'staff'"
                                :staff="item"
                                :show-favorite="false"
                                @click="handleStaffDetail"
                            />

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
                                        <tn-icon name="user" size="24" color="#9A9388" />
                                        <text class="result-staff">{{
                                            item.staff_name || '未知人员'
                                        }}</text>
                                    </view>
                                </view>
                            </view>
                        </block>
                        <template #empty>
                            <EmptyState
                                title="没有找到匹配内容"
                                :description="`换个关键词试试，或切换到${currentTypeLabel}以外的分类。`"
                            />
                        </template>
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
import EmptyState from '@/components/base/EmptyState.vue'
import MpPageHeader from '@/components/base/MpPageHeader.vue'
import PageShell from '@/components/base/PageShell.vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
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
const navBarMetrics = useNavBarMetrics()

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
const currentTypeLabel = computed(() => searchTypes[currentTypeIndex.value]?.label || '当前分类')
const searchPageStyle = computed(() => ({
    '--wm-search-nav-height': `${navBarMetrics.navBarHeight}px`
}))

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
    keyword.value = ''
    search.searching = false
    search.result = []
    paging.value?.clear?.()
}

const getHotSearchFunc = async () => {
    try {
        search.hot_search = await getHotSearch()
    } catch (e) {
        //TODO handle the exception
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
    min-height: 100vh;
}

.search-header {
    position: sticky;
    top: var(--wm-search-nav-height, 0px);
    z-index: 15;
    padding: 16rpx 37rpx 18rpx;
    background: linear-gradient(
        180deg,
        rgba(248, 247, 242, 0.98) 0%,
        rgba(248, 247, 242, 0.88) 100%
    );
    backdrop-filter: blur(18rpx);
    -webkit-backdrop-filter: blur(18rpx);
}

.search-header__input-shell {
    display: flex;
    align-items: center;
    gap: 16rpx;
}

.search-header__input-wrap {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 14rpx;
    min-height: 94rpx;
    padding: 0 28rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.92);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    box-shadow: 0 10rpx 24rpx rgba(17, 17, 17, 0.12);
}

.search-header__input {
    flex: 1;
    min-width: 0;
    height: 94rpx;
    font-size: 28rpx;
    color: var(--wm-text-primary, #111111);
}

.search-header__clear {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.search-header__action {
    height: 94rpx;
    padding: 0 34rpx;
    border-radius: 999rpx;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(
        135deg,
        var(--wm-color-primary, #0b0b0b) 0%,
        var(--wm-color-secondary, #c8a45d) 100%
    );
    box-shadow: 0 12rpx 24rpx rgba(11, 11, 11, 0.18);
}

.search-header__action-text {
    font-size: 26rpx;
    font-weight: 700;
    color: #ffffff;
}

.search-tabs {
    position: sticky;
    top: calc(var(--wm-search-nav-height, 0px) + 128rpx);
    z-index: 14;
    padding: 0 0 12rpx;
    background: linear-gradient(
        180deg,
        rgba(248, 247, 242, 0.96) 0%,
        rgba(248, 247, 242, 0.88) 100%
    );
}

.search-tabs__scroll {
    white-space: nowrap;
}

.search-tabs__row {
    display: inline-flex;
    gap: 16rpx;
    padding: 0 37rpx;
}

.search-tabs__item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 78rpx;
    padding: 0 30rpx;
    border-radius: 999rpx;
    background: rgba(255, 255, 255, 0.86);
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    box-shadow: 0 8rpx 18rpx rgba(17, 17, 17, 0.08);
    transition: all var(--wm-motion-base, 220ms) ease;
}

.search-tabs__item--active {
    background: var(--wm-color-primary-soft, #f3f2ee);
    border-color: var(--wm-color-border-strong, #d8c28a);
}

.search-tabs__text {
    font-size: 26rpx;
    font-weight: 700;
    color: var(--wm-text-secondary, #5f5a50);
}

.search-tabs__item--active .search-tabs__text {
    color: var(--wm-color-primary, #0b0b0b);
}

.search-content {
    min-height: calc(100vh - 320rpx);
}

.search-results {
    padding: 12rpx 0 32rpx;
}

.dynamic-result-card {
    margin: 0 37rpx 24rpx;
}

.result-card {
    margin: 0 37rpx 24rpx;
    background: rgba(255, 255, 255, 0.88);
    border-radius: var(--wm-radius-card, 45rpx);
    padding: 28rpx;
    display: flex;
    gap: 24rpx;
    border: 1rpx solid var(--wm-color-border, #e7e2d6);
    box-shadow: var(--wm-shadow-soft, 0 14rpx 32rpx rgba(17, 17, 17, 0.16));
    transition: all var(--wm-motion-base, 220ms) ease;

    &:active {
        transform: translateY(-2rpx);
    }
}

.result-cover {
    width: 180rpx;
    height: 180rpx;
    border-radius: 28rpx;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.86);
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
    color: var(--wm-text-primary, #111111);
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
    color: var(--wm-text-secondary, #5f5a50);
}

.result-desc {
    font-size: 26rpx;
    color: #5F5A50;
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
    color: #9a9388;
    text-decoration: line-through;
}
</style>
