<template>
    <view class="regions">
        <view v-if="showHeader" class="regions__head">
            <text class="regions__title">{{ title }}</text>
            <text class="regions__summary" :class="{ 'is-empty': !selectedText }">
                {{ selectedText || '请选择省 / 市 / 区' }}
            </text>
        </view>

        <!-- 分段标签：已选层级可直接点回，未解锁的层级不可点 -->
        <view class="regions__tabs">
            <view
                v-for="tab in tabs"
                :key="tab.level"
                class="rtab"
                :class="{
                    'is-active': level === tab.level,
                    'is-done': tab.done && level !== tab.level,
                    'is-todo': !tab.done && level !== tab.level
                }"
                hover-class="is-pressed"
                @tap="goLevel(tab.level)"
            >
                <text class="rtab__text">{{ tab.label }}</text>
            </view>
        </view>

        <view v-if="pending" class="regions__hint">
            <text class="regions__hint-text">地区加载中…</text>
        </view>
        <view
            v-else-if="failed || !effectiveTree.length"
            class="regions__hint"
            hover-class="is-pressed"
            @tap="loadTree()"
        >
            <text class="regions__hint-text">地区加载失败，点击重试</text>
        </view>

        <scroll-view
            v-else
            scroll-y
            :show-scrollbar="false"
            class="regions__list"
            :style="{ height: bodyHeight }"
        >
            <view
                v-for="(row, i) in rows"
                :key="row.code || i"
                class="ritem"
                :class="{ 'is-on': i === activeIndex }"
                hover-class="is-pressed"
                @tap="pick(i)"
            >
                <text class="ritem__text ellipsis">{{ row.name }}</text>
                <tn-icon v-if="i === activeIndex" name="check" :size="30" class="ritem__check" />
                <text v-else-if="row.hint" class="ritem__hint">{{ row.hint }}</text>
            </view>

            <view v-if="!rows.length" class="regions__empty">
                <text class="regions__empty-text">{{ emptyText }}</text>
            </view>
        </scroll-view>
    </view>
</template>

<script setup lang="ts">
/**
 * 地区选择：分段标签 + 单列列表（省 → 市 → 区，一次只看一级）。
 *
 * 为什么不用三栏并排：中文行政区名长短差异大（「内蒙古自治区」vs「北京市」），
 * 三列同屏每列不到 110px，长名必然省略或折行，视觉上挤且难扫读。
 * 分段标签把「当前在哪一级、已选了什么」放在顶部一行，列表独占整宽，行高 88rpx。
 *
 * 契约（与调用方约定，不要改）：
 *  - `v-model` 是 RegionContext 六字段；**只有选到区县才 emit 完整值**，
 *    改省/改市一律 emit null，父级据此禁用「确定」或保留原值，绝不写入半截地区。
 *  - `tree` 由父级传入时直接用；不传则自行拉取（复用 useCatalogCache 的 30 分钟缓存）。
 *  - `height` 是**列表区**高度；不传则按当前列表长度自适应（3–6 行 × 88rpx）。
 */
import { computed, onMounted, ref, watch } from 'vue'
import { useCatalogCache } from '@/composables/useCatalogCache'
import type { RegionContext } from '@/utils/region'

type Level = 1 | 2 | 3

const props = withDefaults(
    defineProps<{
        modelValue?: RegionContext | null
        tree?: any[]
        title?: string
        /** 列表区高度；不传按 3–6 行自适应 */
        height?: string
        showHeader?: boolean
    }>(),
    {
        modelValue: null,
        tree: () => [],
        title: '服务地区',
        height: '',
        showHeader: true
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', v: RegionContext | null): void
    (e: 'change', v: RegionContext | null): void
}>()

const { loadRegionTree } = useCatalogCache()

const innerTree = ref<any[]>([])
const loading = ref(false)
const failed = ref(false)
const attempted = ref(false)

const effectiveTree = computed<any[]>(() =>
    Array.isArray(props.tree) && props.tree.length ? props.tree : innerTree.value
)

const level = ref<Level>(1)
const provinceIndex = ref(-1)
const cityIndex = ref(-1)
const districtIndex = ref(-1)

const cityList = computed<any[]>(() => {
    const province = effectiveTree.value[provinceIndex.value]
    return Array.isArray(province?.cities) ? province.cities : []
})

const districtList = computed<any[]>(() => {
    const city = cityList.value[cityIndex.value]
    return Array.isArray(city?.districts) ? city.districts : []
})

const activeIndex = computed(() => {
    if (level.value === 1) return provinceIndex.value
    if (level.value === 2) return cityIndex.value
    return districtIndex.value
})

interface Row {
    code: string
    name: string
    hint?: string
}

const rows = computed<Row[]>(() => {
    const source =
        level.value === 1
            ? effectiveTree.value
            : level.value === 2
            ? cityList.value
            : districtList.value

    return (source || []).map((item: any) => ({
        code: String(
            level.value === 1
                ? item?.province_code
                : level.value === 2
                ? item?.city_code
                : item?.district_code
        ),
        name: String(
            level.value === 1
                ? item?.province_name
                : level.value === 2
                ? item?.city_name
                : item?.district_name
        ),
        hint:
            level.value === 1
                ? `${Array.isArray(item?.cities) ? item.cities.length : 0} 市`
                : level.value === 2
                ? `${Array.isArray(item?.districts) ? item.districts.length : 0} 区县`
                : ''
    }))
})

const emptyText = computed(() =>
    level.value === 2 ? '该省暂无城市数据' : level.value === 3 ? '该市暂无区县数据' : '暂无地区数据'
)

const tabs = computed(() => {
    const province = effectiveTree.value[provinceIndex.value]
    const city = cityList.value[cityIndex.value]
    const district = districtList.value[districtIndex.value]
    return [
        {
            level: 1 as Level,
            label: province?.province_name || '请选择省份',
            done: provinceIndex.value >= 0
        },
        {
            level: 2 as Level,
            label: city?.city_name || '请选择城市',
            done: cityIndex.value >= 0
        },
        {
            level: 3 as Level,
            label: district?.district_name || '请选择区县',
            done: districtIndex.value >= 0
        }
    ]
})

/** 「加载中」= 自己正在拉，或父级的树还没到（不能直接判失败） */
const pending = computed(
    () => loading.value || (!effectiveTree.value.length && !attempted.value && !failed.value)
)

/** 列表高度：优先用调用方给的值，否则按当前列表长度取 3–5 行 */
const bodyHeight = computed(() => {
    if (props.height) return props.height
    const count = Math.max(rows.value.length, 1)
    return `${Math.min(5, Math.max(3, count)) * 88 + 16}rpx`
})

const selectedText = computed(() =>
    [
        effectiveTree.value[provinceIndex.value]?.province_name,
        cityList.value[cityIndex.value]?.city_name,
        districtList.value[districtIndex.value]?.district_name
    ]
        .filter(Boolean)
        .join(' · ')
)

/** 把外部值回填到三级；null 时**不重置**（否则刚点省就被清空） */
function syncFromModel() {
    const value = props.modelValue
    if (!value?.district_code || !effectiveTree.value.length) return
    const pi = effectiveTree.value.findIndex(
        (p) => String(p.province_code) === String(value.province_code)
    )
    if (pi < 0) return
    const cities = effectiveTree.value[pi]?.cities || []
    const ci = cities.findIndex((c: any) => String(c.city_code) === String(value.city_code))
    if (ci < 0) return
    const districts = cities[ci]?.districts || []
    const di = districts.findIndex(
        (d: any) => String(d.district_code) === String(value.district_code)
    )
    if (di < 0) return

    provinceIndex.value = pi
    cityIndex.value = ci
    districtIndex.value = di
    level.value = 3
}

function emitValue(value: RegionContext | null) {
    emit('update:modelValue', value)
    emit('change', value)
}

function goLevel(target: Level) {
    if (target === 1) {
        level.value = 1
        return
    }
    if (target === 2 && provinceIndex.value >= 0) {
        level.value = 2
        return
    }
    if (target === 3 && cityIndex.value >= 0) {
        level.value = 3
    }
}

function pick(index: number) {
    if (level.value === 1) {
        if (provinceIndex.value === index) {
            level.value = 2
            return
        }
        provinceIndex.value = index
        cityIndex.value = -1
        districtIndex.value = -1
        level.value = 2
        emitValue(null)
        return
    }

    if (level.value === 2) {
        if (cityIndex.value === index) {
            level.value = 3
            return
        }
        cityIndex.value = index
        districtIndex.value = -1
        level.value = 3
        emitValue(null)
        return
    }

    const province = effectiveTree.value[provinceIndex.value]
    const city = cityList.value[cityIndex.value]
    const district = districtList.value[index]
    if (!province || !city || !district) return

    districtIndex.value = index
    emitValue({
        province_code: String(district.province_code || province.province_code || ''),
        province_name: String(district.province_name || province.province_name || ''),
        city_code: String(district.city_code || city.city_code || ''),
        city_name: String(district.city_name || city.city_name || ''),
        district_code: String(district.district_code || ''),
        district_name: String(district.district_name || '')
    })
}

async function loadTree() {
    if (loading.value) return
    loading.value = true
    failed.value = false
    try {
        const tree = await loadRegionTree()
        innerTree.value = Array.isArray(tree) ? tree : []
        syncFromModel()
    } catch {
        failed.value = true
    } finally {
        attempted.value = true
        loading.value = false
    }
}

watch(
    () => props.tree,
    (tree) => {
        if (Array.isArray(tree) && tree.length) syncFromModel()
    }
)

watch(() => props.modelValue, syncFromModel)

onMounted(() => {
    if (props.tree?.length) syncFromModel()
    else loadTree()
})
</script>

<style lang="scss" scoped>
.regions {
    &__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: var(--sp-2);
        padding-bottom: var(--sp-2);
    }

    &__title {
        flex: none;
        font-size: var(--fs-28);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__summary {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;

        &.is-empty {
            color: var(--c-ink-3);
        }
    }
}

/* ---------- 分段标签 ---------- */
.regions__tabs {
    display: flex;
    align-items: center;
    gap: 12rpx;
    padding: 4rpx 0 var(--sp-2);
}

.rtab {
    display: flex;
    align-items: center;
    min-width: 0;
    height: 64rpx;
    padding: 0 24rpx;
    border-radius: var(--r-pill);
    background: var(--c-surface-2);
    border: 1rpx solid transparent;
    transition: background-color 0.16s ease, border-color 0.16s ease;

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        max-width: 220rpx;
    }

    /* 当前级：黑底反白，明确「我正在这一级里选」 */
    &.is-active {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .rtab__text {
            color: var(--c-on-dark);
            font-weight: var(--fw-medium);
        }
    }

    /* 已选但不在当前级：暖金底，表示可点回去改 */
    &.is-done {
        background: var(--c-brand-bg);

        .rtab__text {
            color: var(--c-brand-text);
        }
    }

    /* 未解锁：弱化且不响应 */
    &.is-todo {
        background: transparent;
        border-color: var(--c-line);

        .rtab__text {
            color: var(--c-ink-3);
        }
    }
}

/* ---------- 列表 ---------- */
.regions__list {
    width: 100%;
    padding: 8rpx;
    border-radius: var(--r-md);
    background: transparent;
}

.ritem {
    display: flex;
    align-items: center;
    gap: 12rpx;
    height: 88rpx;
    margin-bottom: 8rpx;
    padding: 0 24rpx;
    border-radius: var(--r-sm);
    background: var(--c-surface);
    transition: background-color 0.16s ease;

    &:last-child {
        margin-bottom: 0;
    }

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-26);
        color: var(--c-ink-2);
    }

    &__hint {
        flex: none;
        font-size: var(--fs-18);
        color: var(--c-ink-3);
    }

    &__check {
        flex: none;
        color: var(--c-brand-text);
    }

    &.is-on {
        background: var(--c-brand-bg);

        .ritem__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }
}

.regions__hint {
    padding: var(--sp-4);
    border-radius: var(--r-sm);
    background: var(--c-surface-2);
}

.regions__hint-text {
    font-size: var(--fs-20);
    color: var(--c-ink-3);
}

.regions__empty {
    padding: 56rpx 0;
    text-align: center;
}

.regions__empty-text {
    font-size: var(--fs-22);
    color: var(--c-ink-3);
}
</style>
