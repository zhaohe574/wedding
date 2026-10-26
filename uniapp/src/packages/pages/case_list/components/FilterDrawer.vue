<template>
    <view v-if="visible" class="filter-drawer-mask" @tap.self="handleClose">
        <view class="filter-drawer-panel" :style="{ top: `${topOffset}px` }">
            <!-- 1. 综合排序 -->
            <view v-if="mode === 'sort'" class="sort-sheet">
                <view
                    v-for="(item, index) in sortList"
                    :key="index"
                    class="sort-item"
                    :class="{ 'sort-item--active': currentSortIndex === index }"
                    @tap="handleSelectSort(index)"
                >
                    <text class="sort-text">{{ item }}</text>
                    <image
                        v-if="currentSortIndex === index"
                        class="sort-check-icon"
                        src="/packages/static/images/case/case_sort_check.png"
                        mode="aspectFit"
                    />
                </view>
            </view>

            <!-- 2. 地区筛选 -->
            <view v-else-if="mode === 'city'" class="city-sheet">
                <view class="sheet-body">
                    <scroll-view scroll-y class="province-col">
                        <view
                            class="province-item"
                            :class="{ 'province-item--active': selectedProvince === '' }"
                            @tap="handleSelectProvince('')"
                        >
                            全部省份
                        </view>
                        <view
                            v-for="prov in provinces"
                            :key="prov.province"
                            class="province-item"
                            :class="{ 'province-item--active': selectedProvince === prov.province }"
                            @tap="handleSelectProvince(prov.province)"
                        >
                            {{ prov.province }}
                        </view>
                    </scroll-view>
                    <scroll-view scroll-y class="city-col">
                        <view
                            class="city-chip"
                            :class="{ 'city-chip--active': selectedCity === '' }"
                            @tap="handleSelectCity('')"
                        >
                            全省/全部城市
                        </view>
                        <view
                            v-for="city in currentCityList"
                            :key="city"
                            class="city-chip"
                            :class="{ 'city-chip--active': selectedCity === city }"
                            @tap="handleSelectCity(city)"
                        >
                            {{ city }}
                        </view>
                    </scroll-view>
                </view>
                <view class="sheet-footer">
                    <view class="btn-reset" @tap="handleResetCity">重置</view>
                    <view class="btn-confirm" @tap="handleConfirmCity">确定</view>
                </view>
            </view>

            <!-- 3. 风格与色系 -->
            <view v-else-if="mode === 'style'" class="style-sheet">
                <scroll-view scroll-y class="sheet-scroll">
                    <!-- 风格 -->
                    <view class="section-title">
                        <text class="title-main">婚礼风格</text>
                        <text class="title-sub">最多可选3个</text>
                    </view>
                    <view class="tags-grid">
                        <view
                            v-for="item in styles"
                            :key="item.id"
                            class="tag-box"
                            :class="{ 'tag-box--active': tempStyles.includes(item.name) }"
                            @tap="handleToggleStyle(item.name)"
                        >
                            {{ item.name }}
                        </view>
                    </view>

                    <!-- 色系 -->
                    <view class="section-title mt-4">
                        <text class="title-main">婚礼色系</text>
                        <text class="title-sub">最多可选3个</text>
                    </view>
                    <view class="color-grid">
                        <view
                            v-for="color in colors"
                            :key="color.id"
                            class="color-item"
                            :class="{ 'color-item--active': tempColors.includes(color.name) }"
                            @tap="handleToggleColor(color.name)"
                        >
                            <view
                                class="color-dot"
                                :style="{ backgroundColor: color.hex, borderColor: color.name === '白色' ? '#E2E8F0' : color.hex }"
                            ></view>
                            <text class="color-label">{{ color.name }}</text>
                            <image
                                v-if="tempColors.includes(color.name)"
                                class="color-check-badge"
                                src="/packages/static/images/case/color-checked-icon.png"
                                mode="aspectFit"
                            />
                        </view>
                    </view>
                </scroll-view>
                <view class="sheet-footer">
                    <view class="btn-reset" @tap="handleResetStyle">重置</view>
                    <view class="btn-confirm" @tap="handleConfirmStyle">确定</view>
                </view>
            </view>

            <!-- 4. 场景与预算 -->
            <view v-else-if="mode === 'scene'" class="scene-sheet">
                <scroll-view scroll-y class="sheet-scroll">
                    <!-- 婚礼场景 -->
                    <view class="section-title">
                        <text class="title-main">仪式场景</text>
                        <text class="title-sub">多选</text>
                    </view>
                    <view class="tags-grid">
                        <view
                            v-for="item in scenes"
                            :key="item.id"
                            class="tag-box"
                            :class="{ 'tag-box--active': tempScenes.includes(item.name) }"
                            @tap="handleToggleScene(item.name)"
                        >
                            {{ item.name }}
                        </view>
                    </view>

                    <!-- 预算区间 -->
                    <view class="section-title mt-4">
                        <text class="title-main">预算区间</text>
                        <text class="title-sub">单选或自定义</text>
                    </view>
                    <view class="price-grid">
                        <view
                            v-for="(price, idx) in prices"
                            :key="idx"
                            class="price-chip"
                            :class="{ 'price-chip--active': tempPriceIndex === idx }"
                            @tap="handleSelectPriceRange(idx)"
                        >
                            {{ price.name }}
                        </view>
                    </view>

                    <!-- 自定义价格输入 -->
                    <view class="custom-price-row">
                        <input
                            v-model="customMinPrice"
                            type="digit"
                            class="price-input"
                            placeholder="最低价"
                            placeholder-class="input-placeholder"
                        />
                        <text class="price-separator">-</text>
                        <input
                            v-model="customMaxPrice"
                            type="digit"
                            class="price-input"
                            placeholder="最高价"
                            placeholder-class="input-placeholder"
                        />
                        <text class="price-unit">元</text>
                    </view>
                </scroll-view>
                <view class="sheet-footer">
                    <view class="btn-reset" @tap="handleResetScene">重置</view>
                    <view class="btn-confirm" @tap="handleConfirmScene">确定</view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

export type FilterMode = 'sort' | 'city' | 'style' | 'scene'

interface Props {
    visible: boolean
    mode: FilterMode
    topOffset?: number
    sortList: string[]
    currentSortIndex: number
    styles: Array<{ id: string; name: string }>
    colors: Array<{ id: string; name: string; hex: string }>
    scenes: Array<{ id: string; name: string }>
    prices: Array<{ name: string; minPrice: number; maxPrice: number }>
    provinces: Array<{ province: string; cities: string[] }>
    activeStyles?: string[]
    activeColors?: string[]
    activeScenes?: string[]
    activeCity?: string
    activeProvince?: string
}

const props = withDefaults(defineProps<Props>(), {
    visible: false,
    mode: 'sort',
    topOffset: 0,
    sortList: () => [],
    currentSortIndex: 0,
    styles: () => [],
    colors: () => [],
    scenes: () => [],
    prices: () => [],
    provinces: () => [],
    activeStyles: () => [],
    activeColors: () => [],
    activeScenes: () => [],
    activeCity: '',
    activeProvince: ''
})

const emit = defineEmits<{
    (e: 'close'): void
    (e: 'update:sort', index: number): void
    (e: 'update:city', data: { province: string; city: string }): void
    (e: 'update:style', data: { styles: string[]; colors: string[] }): void
    (e: 'update:scene', data: { scenes: string[]; minPrice: number; maxPrice: number }): void
}>()

// 临时状态
const selectedProvince = ref('')
const selectedCity = ref('')
const tempStyles = ref<string[]>([])
const tempColors = ref<string[]>([])
const tempScenes = ref<string[]>([])
const tempPriceIndex = ref<number>(-1)
const customMinPrice = ref('')
const customMaxPrice = ref('')

watch(
    () => props.visible,
    val => {
        if (val) {
            selectedProvince.value = props.activeProvince || ''
            selectedCity.value = props.activeCity || ''
            tempStyles.value = [...props.activeStyles]
            tempColors.value = [...props.activeColors]
            tempScenes.value = [...props.activeScenes]
        }
    }
)

const currentCityList = computed(() => {
    if (!selectedProvince.value) {
        return props.provinces.flatMap(p => p.cities)
    }
    const found = props.provinces.find(p => p.province === selectedProvince.value)
    return found ? found.cities : []
})

const handleClose = () => {
    emit('close')
}

// 排序
const handleSelectSort = (index: number) => {
    emit('update:sort', index)
    emit('close')
}

// 城市
const handleSelectProvince = (prov: string) => {
    selectedProvince.value = prov
    selectedCity.value = ''
}

const handleSelectCity = (city: string) => {
    selectedCity.value = city
}

const handleResetCity = () => {
    selectedProvince.value = ''
    selectedCity.value = ''
}

const handleConfirmCity = () => {
    emit('update:city', {
        province: selectedProvince.value,
        city: selectedCity.value
    })
    emit('close')
}

// 风格与色系
const handleToggleStyle = (name: string) => {
    const idx = tempStyles.value.indexOf(name)
    if (idx > -1) {
        tempStyles.value.splice(idx, 1)
    } else {
        if (tempStyles.value.length >= 3) {
            uni.showToast({ title: '最多选择3个风格', icon: 'none' })
            return
        }
        tempStyles.value.push(name)
    }
}

const handleToggleColor = (name: string) => {
    const idx = tempColors.value.indexOf(name)
    if (idx > -1) {
        tempColors.value.splice(idx, 1)
    } else {
        if (tempColors.value.length >= 3) {
            uni.showToast({ title: '最多选择3个色系', icon: 'none' })
            return
        }
        tempColors.value.push(name)
    }
}

const handleResetStyle = () => {
    tempStyles.value = []
    tempColors.value = []
}

const handleConfirmStyle = () => {
    emit('update:style', {
        styles: [...tempStyles.value],
        colors: [...tempColors.value]
    })
    emit('close')
}

// 场景与预算
const handleToggleScene = (name: string) => {
    const idx = tempScenes.value.indexOf(name)
    if (idx > -1) {
        tempScenes.value.splice(idx, 1)
    } else {
        tempScenes.value.push(name)
    }
}

const handleSelectPriceRange = (idx: number) => {
    if (tempPriceIndex.value === idx) {
        tempPriceIndex.value = -1
    } else {
        tempPriceIndex.value = idx
        const item = props.prices[idx]
        customMinPrice.value = item.minPrice.toString()
        customMaxPrice.value = item.maxPrice > 0 ? item.maxPrice.toString() : ''
    }
}

const handleResetScene = () => {
    tempScenes.value = []
    tempPriceIndex.value = -1
    customMinPrice.value = ''
    customMaxPrice.value = ''
}

const handleConfirmScene = () => {
    const minP = customMinPrice.value ? parseFloat(customMinPrice.value) : -1
    const maxP = customMaxPrice.value ? parseFloat(customMaxPrice.value) : -1
    emit('update:scene', {
        scenes: [...tempScenes.value],
        minPrice: minP,
        maxPrice: maxP
    })
    emit('close')
}
</script>

<style scoped lang="scss">
.filter-drawer-mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 500;
}

.filter-drawer-panel {
    position: absolute;
    left: 0;
    right: 0;
    background: #ffffff;
    border-bottom-left-radius: 28rpx;
    border-bottom-right-radius: 28rpx;
    box-shadow: 0 12rpx 36rpx rgba(0, 0, 0, 0.12);
    overflow: hidden;
    animation: slideDown 0.25s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-20rpx);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* 排序 */
.sort-sheet {
    padding: 10rpx 0;
}

.sort-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30rpx 40rpx;
    border-bottom: 1rpx solid #f2f3f5;

    &.sort-item--active {
        .sort-text {
            color: #c29b6b;
            font-weight: 600;
        }
    }
}

.sort-text {
    font-size: 28rpx;
    color: #323233;
}

.sort-check-icon {
    width: 32rpx;
    height: 32rpx;
}

/* 城市 */
.city-sheet {
    display: flex;
    flex-direction: column;
    height: 600rpx;
}

.sheet-body {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.province-col {
    width: 240rpx;
    background: #f8f9fa;
    height: 100%;
}

.province-item {
    padding: 26rpx 28rpx;
    font-size: 26rpx;
    color: #646566;

    &.province-item--active {
        background: #ffffff;
        color: #c29b6b;
        font-weight: 600;
        border-left: 6rpx solid #c29b6b;
    }
}

.city-col {
    flex: 1;
    padding: 20rpx 28rpx;
    height: 100%;
}

.city-chip {
    padding: 20rpx 24rpx;
    border-bottom: 1rpx solid #f5f6f8;
    font-size: 26rpx;
    color: #323233;

    &.city-chip--active {
        color: #c29b6b;
        font-weight: 600;
    }
}

/* 风格 & 场景 通用 */
.sheet-scroll {
    max-height: 720rpx;
    padding: 32rpx 36rpx;
    box-sizing: border-box;
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 20rpx;
}

.title-main {
    font-size: 30rpx;
    font-weight: 600;
    color: #323233;
}

.title-sub {
    font-size: 24rpx;
    color: #969799;
}

.tags-grid,
.price-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.tag-box,
.price-chip {
    width: calc((100% - 32rpx) / 3);
    height: 72rpx;
    line-height: 72rpx;
    text-align: center;
    background: #f8f8fa;
    border: 1rpx solid #f8f8fa;
    border-radius: 12rpx;
    font-size: 26rpx;
    color: #323233;
    box-sizing: border-box;
    transition: all 0.2s ease;

    &.tag-box--active,
    &.price-chip--active {
        background: #fff9f3;
        border-color: #d3ab79;
        color: #c29b6b;
        font-weight: 600;
    }
}

.color-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 20rpx;
}

.color-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    padding: 12rpx 0;

    &.color-item--active {
        .color-label {
            color: #c29b6b;
            font-weight: 600;
        }
    }
}

.color-dot {
    width: 48rpx;
    height: 48rpx;
    border-radius: 50%;
    border-width: 2rpx;
    border-style: solid;
}

.color-label {
    margin-top: 10rpx;
    font-size: 22rpx;
    color: #646566;
}

.color-check-badge {
    position: absolute;
    top: 4rpx;
    right: 18rpx;
    width: 24rpx;
    height: 24rpx;
}

/* 自定义价格输入 */
.custom-price-row {
    display: flex;
    align-items: center;
    margin-top: 24rpx;
    background: #f8f8fa;
    border-radius: 16rpx;
    padding: 16rpx 24rpx;
}

.price-input {
    flex: 1;
    font-size: 28rpx;
    text-align: center;
    height: 60rpx;
    line-height: 60rpx;
}

.input-placeholder {
    color: #c0c4cc;
}

.price-separator {
    color: #999;
    margin: 0 20rpx;
}

.price-unit {
    font-size: 26rpx;
    color: #646566;
    margin-left: 12rpx;
}

/* 底部操作条 */
.sheet-footer {
    display: flex;
    justify-content: space-between;
    padding: 24rpx 36rpx;
    border-top: 1rpx solid #f2f3f5;
    background: #ffffff;
}

.btn-reset,
.btn-confirm {
    height: 84rpx;
    line-height: 84rpx;
    text-align: center;
    border-radius: 12rpx;
    font-size: 28rpx;
    font-weight: 600;
}

.btn-reset {
    width: 260rpx;
    background: #ffffff;
    border: 1rpx solid #dcdee0;
    color: #323233;
}

.btn-confirm {
    width: 380rpx;
    background: #c29b6b;
    color: #ffffff;
}
</style>
