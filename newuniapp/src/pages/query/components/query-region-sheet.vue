<template>
    <tn-popup
        :model-value="open"
        open-direction="bottom"
        :radius="24"
        :safe-area-inset-bottom="true"
        @update:model-value="onVisible"
        @close="close"
    >
        <view class="sheet">
            <view class="sheet__head">
                <view class="sheet__head-main">
                    <text class="sheet__title">服务地区</text>
                    <text class="sheet__path" :class="{ 'is-empty': !hasSelectionPath }">
                        {{ pathText }}
                    </text>
                </view>
                <view class="sheet__close" hover-class="is-pressed" aria-label="关闭" @tap="close">
                    <tn-icon name="close" :size="34" />
                </view>
            </view>

            <view v-if="loading && !tree.length" class="state">
                <text class="state__text">地区加载中…</text>
            </view>

            <view v-else-if="error || !tree.length" class="state">
                <text class="state__text">地区数据没加载出来</text>
                <text class="state__retry" hover-class="is-pressed" @tap="emit('retry')">
                    点击重试
                </text>
            </view>

            <view v-else class="cols">
                <view class="cols__head">
                    <text class="cols__head-cell cols__head-cell--prov">省份</text>
                    <text class="cols__head-cell cols__head-cell--city">城市</text>
                    <text class="cols__head-cell cols__head-cell--dist">区县</text>
                </view>

                <view class="cols__body">
                    <!-- 左栏：省份 -->
                    <scroll-view
                        scroll-y
                        :show-scrollbar="false"
                        :scroll-into-view="provScrollId"
                        class="col col--prov"
                    >
                        <view
                            v-for="(p, i) in tree"
                            :id="`p-${i}`"
                            :key="p.province_code || i"
                            class="col__item col__item--prov"
                            :class="{ 'is-on': i === provinceIndex }"
                            hover-class="is-pressed"
                            @tap="pickProvince(i)"
                        >
                            <view v-if="i === provinceIndex" class="col__bar" aria-hidden="true" />
                            <text class="col__label">{{ p.province_name }}</text>
                        </view>
                    </scroll-view>

                    <!-- 中栏：城市 -->
                    <scroll-view
                        scroll-y
                        :show-scrollbar="false"
                        :scroll-into-view="cityScrollId"
                        class="col col--city"
                    >
                        <template v-if="cityList.length">
                            <view
                                v-for="(c, i) in cityList"
                                :id="`c-${i}`"
                                :key="c.city_code || i"
                                class="col__item col__item--city"
                                :class="{ 'is-on': i === cityIndex }"
                                hover-class="is-pressed"
                                @tap="pickCity(i)"
                            >
                                <view
                                    v-if="i === cityIndex"
                                    class="col__bar"
                                    aria-hidden="true"
                                />
                                <text class="col__label">{{ c.city_name }}</text>
                            </view>
                        </template>
                        <view v-else class="col__empty">
                            <text class="col__empty-text">暂无城市</text>
                        </view>
                    </scroll-view>

                    <!-- 右栏：区县（点击即生效并收起弹层） -->
                    <scroll-view
                        scroll-y
                        :show-scrollbar="false"
                        :scroll-into-view="distScrollId"
                        class="col col--dist"
                    >
                        <view v-if="districtList.length" class="dist-list">
                            <view
                                v-for="(d, i) in districtList"
                                :id="`d-${i}`"
                                :key="d.district_code || i"
                                class="dist"
                                :class="{ 'is-on': i === districtIndex }"
                                hover-class="is-pressed"
                                @tap="pickDistrict(i)"
                            >
                                <text class="dist__label">{{ d.district_name }}</text>
                                <tn-icon
                                    v-if="i === districtIndex"
                                    name="check"
                                    :size="26"
                                    class="dist__check"
                                />
                            </view>
                        </view>
                        <view v-else class="col__empty">
                            <text class="col__empty-text">
                                {{ cityIndex >= 0 ? '暂无区县' : '请先选择城市' }}
                            </text>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view class="sheet__foot">
                <text class="sheet__tip">选到区县后自动生效，价格与服务范围按区县计算</text>
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
/**
 * 查档期 · 服务地区弹窗（三栏联动：省份 | 城市 | 区县）。
 *
 * 契约不变：
 *  1. 后端 `hasRegionContext()` 要求 city_code 与 district_code 同时非空
 *     （PackageRegionPriceService），缺区县时价格会退回全国价 —— 必须选到区县；
 *  2. 地区是查档期的硬必选，不提供「全国（不限地区）」；
 *  3. 切换省/市仅更新本地预览，不改外部已生效地区；点击区县时才 emit pick 并关闭；
 *  4. 每次打开根据当前生效地区（或默认首省首市）重新定位，自动丢弃上次未选完的半截状态。
 */
import { computed, nextTick, ref, watch } from 'vue'
import type { PickedRegion, SlimCity, SlimDistrict, SlimProvince } from '@/composables/useCatalogCache'

const props = withDefaults(
    defineProps<{
        open: boolean
        modelValue: PickedRegion | null
        tree: SlimProvince[]
        loading?: boolean
        error?: boolean
    }>(),
    {
        modelValue: null,
        tree: () => [],
        loading: false,
        error: false
    }
)

const emit = defineEmits<{
    (e: 'update:open', v: boolean): void
    (e: 'pick', v: PickedRegion): void
    (e: 'retry'): void
}>()

const provinceIndex = ref(-1)
const cityIndex = ref(-1)
const districtIndex = ref(-1)

const provScrollId = ref('')
const cityScrollId = ref('')
const distScrollId = ref('')

const cityList = computed<SlimCity[]>(() => {
    const province = props.tree[provinceIndex.value]
    return Array.isArray(province?.cities) ? province.cities : []
})

const districtList = computed<SlimDistrict[]>(() => {
    const city = cityList.value[cityIndex.value]
    return Array.isArray(city?.districts) ? city.districts : []
})

const hasSelectionPath = computed(
    () => provinceIndex.value >= 0 && cityIndex.value >= 0
)

const pathText = computed(() => {
    if (!props.tree.length || provinceIndex.value < 0) return '请选择省 / 市 / 区县'
    const p = props.tree[provinceIndex.value]
    const c = cityList.value[cityIndex.value]
    const d = districtList.value[districtIndex.value]
    const parts = [p?.province_name, c?.city_name, d?.district_name || (c ? '请选择区县' : '')]
        .filter(Boolean)
    return parts.length ? parts.join(' · ') : '请选择省 / 市 / 区县'
})

function setScrollTargets(pi: number, ci: number, di: number) {
    provScrollId.value = ''
    cityScrollId.value = ''
    distScrollId.value = ''
    nextTick(() => {
        if (pi >= 0) provScrollId.value = `p-${pi}`
        if (ci >= 0) cityScrollId.value = `c-${ci}`
        if (di >= 0) distScrollId.value = `d-${di}`
    })
}

function syncSelection(scrollToActive = false) {
    if (!props.tree.length) {
        provinceIndex.value = -1
        cityIndex.value = -1
        districtIndex.value = -1
        return
    }

    const v = props.modelValue
    if (v?.cityCode && v?.districtCode) {
        let pi = v.provinceCode
            ? props.tree.findIndex((p) => String(p.province_code) === String(v.provinceCode))
            : -1
        if (pi < 0) {
            pi = props.tree.findIndex((p) =>
                p.cities?.some((c) => String(c.city_code) === String(v.cityCode))
            )
        }
        if (pi >= 0) {
            const cities = props.tree[pi]?.cities || []
            const ci = cities.findIndex((c) => String(c.city_code) === String(v.cityCode))
            if (ci >= 0) {
                const districts = cities[ci]?.districts || []
                const di = districts.findIndex(
                    (d) => String(d.district_code) === String(v.districtCode)
                )
                provinceIndex.value = pi
                cityIndex.value = ci
                districtIndex.value = di
                if (scrollToActive) setScrollTargets(pi, ci, di >= 0 ? di : 0)
                return
            }
        }
    }

    // 首次无已选值时默认定位首省首市，区县待选，减少一次点选步骤
    const defaultCi = props.tree[0]?.cities?.length ? 0 : -1
    provinceIndex.value = 0
    cityIndex.value = defaultCi
    districtIndex.value = -1
    if (scrollToActive) setScrollTargets(0, defaultCi, 0)
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) syncSelection(true)
    },
    { immediate: true }
)

watch(
    () => props.tree,
    (tree) => {
        if (props.open && Array.isArray(tree) && tree.length) {
            syncSelection(true)
        }
    }
)

function pickProvince(index: number) {
    if (index === provinceIndex.value) return
    provinceIndex.value = index
    const cities = props.tree[index]?.cities || []
    const nextCi = cities.length ? 0 : -1
    cityIndex.value = nextCi
    districtIndex.value = -1
    cityScrollId.value = ''
    distScrollId.value = ''
    nextTick(() => {
        if (nextCi >= 0) cityScrollId.value = 'c-0'
        distScrollId.value = 'd-0'
    })
}

function pickCity(index: number) {
    if (index === cityIndex.value) return
    cityIndex.value = index
    districtIndex.value = -1
    distScrollId.value = ''
    nextTick(() => {
        distScrollId.value = 'd-0'
    })
}

function pickDistrict(index: number) {
    const province = props.tree[provinceIndex.value]
    const city = cityList.value[cityIndex.value]
    const district = districtList.value[index]
    if (!province || !city || !district) return

    districtIndex.value = index
    emit('pick', {
        provinceCode: String(city.province_code || province.province_code || ''),
        provinceName: String(city.province_name || province.province_name || ''),
        cityCode: String(city.city_code || ''),
        cityName: String(city.city_name || ''),
        districtCode: String(district.district_code || ''),
        districtName: String(district.district_name || '')
    })
    close()
}

function close() {
    emit('update:open', false)
}

function onVisible(v: boolean) {
    if (!v) close()
}
</script>

<style lang="scss" scoped>
.sheet {
    background: var(--c-surface);
    overflow: hidden;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        padding: 30rpx 42rpx 20rpx;
        color: var(--c-ink-3);
    }

    &__head-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__title {
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__path {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;

        &.is-empty {
            color: var(--c-ink-3);
        }
    }

    &__close {
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 72rpx;
        height: 72rpx;
        margin: -12rpx -12rpx -12rpx 0;
    }

    &__foot {
        padding: 18rpx 42rpx calc(20rpx + var(--safe-bottom));
        border-top: 1rpx solid var(--c-line);
        background: var(--c-surface);
    }

    &__tip {
        display: block;
        font-size: var(--fs-18);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.state {
    height: 460rpx;
    padding: 0 42rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14rpx;
    border-top: 1rpx solid var(--c-line);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__retry {
        font-size: var(--fs-22);
        color: var(--c-brand-text);
        text-decoration: underline;
        padding: 8rpx 16rpx;
    }
}

.cols {
    border-top: 1rpx solid var(--c-line);

    &__head {
        display: flex;
        align-items: center;
        border-bottom: 1rpx solid var(--c-line);
        background: var(--c-surface-2);
    }

    &__head-cell {
        box-sizing: border-box;
        padding: 14rpx 18rpx;
        font-size: var(--fs-20);
        font-weight: var(--fw-medium);
        color: var(--c-ink-3);

        &--prov {
            flex: 1;
        }

        &--city {
            flex: 1.05;
            background: var(--c-bg);
            border-left: 1rpx solid var(--c-line);
            border-right: 1rpx solid var(--c-line);
        }

        &--dist {
            flex: 1.15;
            background: var(--c-surface);
            padding-left: 24rpx;
        }
    }

    &__body {
        display: flex;
        align-items: stretch;
        width: 100%;
        height: 560rpx;
        max-height: 54vh;
        overflow: hidden;
    }
}

.col {
    height: 100%;
    min-width: 0;
    box-sizing: border-box;

    &--prov {
        flex: 1;
        background: var(--c-surface-2);
    }

    &--city {
        flex: 1.05;
        background: var(--c-bg);
        border-left: 1rpx solid var(--c-line);
        border-right: 1rpx solid var(--c-line);
    }

    &--dist {
        flex: 1.15;
        background: var(--c-surface);
    }

    &__item {
        position: relative;
        display: flex;
        align-items: center;
        min-height: 84rpx;
        padding: 16rpx 18rpx 16rpx 22rpx;
        box-sizing: border-box;
        transition: background-color 0.14s ease;

        &--prov.is-on {
            background: var(--c-bg);
        }

        &--city.is-on {
            background: var(--c-surface);
        }

        &.is-on .col__label {
            color: var(--c-ink);
            font-weight: var(--fw-semibold);
        }
    }

    &__bar {
        position: absolute;
        left: 0;
        top: 50%;
        width: 6rpx;
        height: 30rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand);
        transform: translateY(-50%);
    }

    &__label {
        font-size: var(--fs-23);
        line-height: var(--lh-tight);
        color: var(--c-ink-2);
        word-break: break-all;
    }

    &__empty {
        padding: 72rpx 20rpx;
        text-align: center;
    }

    &__empty-text {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }
}

.dist-list {
    padding: 10rpx 14rpx 20rpx;
}

.dist {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10rpx;
    min-height: 80rpx;
    margin-bottom: 6rpx;
    padding: 14rpx 18rpx;
    border-radius: var(--r-sm);
    box-sizing: border-box;
    transition: background-color 0.14s ease;

    &:last-child {
        margin-bottom: 0;
    }

    &__label {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-23);
        line-height: var(--lh-tight);
        color: var(--c-ink-2);
        word-break: break-all;
    }

    &__check {
        flex: none;
        color: var(--c-brand-text);
    }

    &.is-on {
        background: var(--c-brand-bg);

        .dist__label {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }
}
</style>
