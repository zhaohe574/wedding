<template>
    <div class="link-params-form">
        <div class="link-params-form__head">
            <div>
                <div class="link-params-form__title">{{ title }}</div>
                <div class="link-params-form__sub">
                    仅开放目标页面真实支持的白名单参数，留空则不带该筛选条件
                </div>
            </div>
            <el-button v-if="hasAnyValue" type="info" link size="small" @click="handleReset">
                清空参数
            </el-button>
        </div>

        <el-form label-width="88px" size="default" class="mt-3">
            <el-form-item v-for="param in params" :key="param.key" :label="param.label">
                <!-- 服务类型（带可选 category_name 同步） -->
                <el-select
                    v-if="param.kind === 'service_category'"
                    :model-value="queryValue(param.key)"
                    :placeholder="param.placeholder || '请选择服务类型'"
                    clearable
                    filterable
                    class="w-full"
                    @change="(val: string) => handleCategoryChange(param, val)"
                >
                    <el-option
                        v-for="cat in categories"
                        :key="cat.id"
                        :label="cat.name"
                        :value="String(cat.id)"
                    />
                </el-select>

                <!-- 风格标签多选（最多 maxCount 项） -->
                <el-select
                    v-else-if="param.kind === 'style_tag'"
                    :model-value="selectedTagIds(param.key)"
                    :placeholder="param.placeholder || '请选择风格标签'"
                    multiple
                    :multiple-limit="param.maxCount || 5"
                    clearable
                    filterable
                    collapse-tags
                    collapse-tags-tooltip
                    class="w-full"
                    @change="(vals: string[]) => handleTagsChange(param, vals)"
                >
                    <el-option
                        v-for="tag in availableTags"
                        :key="tag.id"
                        :label="tag.name"
                        :value="String(tag.id)"
                    />
                </el-select>

                <!-- 服务地区（城市 + 区县二级联动，省市区 6 字段同进同退） -->
                <div v-else-if="param.kind === 'region'" class="w-full">
                    <div class="flex items-center gap-2">
                        <el-select
                            :model-value="selectedCityCode"
                            placeholder="选择服务城市"
                            clearable
                            filterable
                            class="flex-1"
                            @change="handleCityChange"
                        >
                            <el-option
                                v-for="city in cities"
                                :key="city.city_code"
                                :label="city.label || `${city.province_name} / ${city.city_name}`"
                                :value="city.city_code"
                            />
                        </el-select>
                        <el-select
                            :model-value="queryValue('district_code')"
                            :placeholder="selectedCityCode ? '选择区县（必选）' : '请先选城市'"
                            :disabled="!selectedCityCode"
                            clearable
                            filterable
                            class="flex-1"
                            @change="handleDistrictChange"
                        >
                            <el-option
                                v-for="d in districts"
                                :key="d.district_code"
                                :label="d.district_name"
                                :value="d.district_code"
                            />
                        </el-select>
                    </div>
                    <div
                        v-if="selectedCityCode && !queryValue('district_code')"
                        class="text-xs text-[#e6a23c] mt-1"
                    >
                        需继续选定区县后才会带入地区参数（避免误按全国查询）
                    </div>
                </div>

                <!-- 日期选择器 -->
                <el-date-picker
                    v-else-if="param.kind === 'date'"
                    :model-value="queryValue(param.key)"
                    type="date"
                    value-format="YYYY-MM-DD"
                    :placeholder="param.placeholder || '选择日期'"
                    clearable
                    class="!w-full"
                    @update:model-value="(val: string | null) => handleSimpleChange(param.key, val || '')"
                />

                <!-- 固定枚举选项 -->
                <el-radio-group
                    v-else-if="param.kind === 'enum' && (param.options?.length || 0) <= 4"
                    :model-value="enumValue(param)"
                    @change="(val: string | number | boolean | undefined) => handleEnumChange(param, String(val ?? ''))"
                >
                    <el-radio
                        v-for="opt in param.options || []"
                        :key="opt.value || '__empty'"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </el-radio>
                </el-radio-group>

                <el-select
                    v-else-if="param.kind === 'enum'"
                    :model-value="enumValue(param)"
                    :placeholder="param.placeholder || '请选择'"
                    class="w-full"
                    @change="(val: string) => handleEnumChange(param, val)"
                >
                    <el-option
                        v-for="opt in param.options || []"
                        :key="opt.value || '__empty'"
                        :label="opt.label"
                        :value="opt.value"
                    />
                </el-select>

                <!-- 白名单文本参数 -->
                <el-input
                    v-else
                    :model-value="queryValue(param.key)"
                    :placeholder="param.placeholder || '选填'"
                    :maxlength="param.maxLength || 30"
                    show-word-limit
                    clearable
                    @input="(val: string) => handleSimpleChange(param.key, val)"
                />
            </el-form-item>
        </el-form>
    </div>
</template>

<script lang="ts" setup>
import type { DecorateParamDef } from '@shared/contracts/decorate-links'
import { computed, onMounted, ref, watch } from 'vue'

import {
    categoryAll,
    regionDistrictOptions,
    regionEnabledCityOptions,
    styleTagAll
} from '@/api/service'

interface CategoryOption {
    id: number
    name: string
}

interface TagOption {
    id: number
    name: string
    category_id?: number
}

interface CityOption {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    label?: string
}

interface DistrictOption {
    province_code: string
    province_name: string
    city_code: string
    city_name: string
    district_code: string
    district_name: string
}

let categoriesPromise: Promise<CategoryOption[]> | null = null
let tagsPromise: Promise<TagOption[]> | null = null
let citiesPromise: Promise<CityOption[]> | null = null
const districtsCache = new Map<string, Promise<DistrictOption[]>>()

function loadCategoriesCached(): Promise<CategoryOption[]> {
    if (!categoriesPromise) {
        categoriesPromise = categoryAll()
            .then((res) => (Array.isArray(res) ? res : []))
            .catch(() => {
                categoriesPromise = null
                return []
            })
    }
    return categoriesPromise
}

function loadTagsCached(): Promise<TagOption[]> {
    if (!tagsPromise) {
        tagsPromise = styleTagAll()
            .then((res) => (Array.isArray(res) ? res : []))
            .catch(() => {
                tagsPromise = null
                return []
            })
    }
    return tagsPromise
}

function loadCitiesCached(): Promise<CityOption[]> {
    if (!citiesPromise) {
        citiesPromise = regionEnabledCityOptions()
            .then((res) => (Array.isArray(res) ? res : []))
            .catch(() => {
                citiesPromise = null
                return []
            })
    }
    return citiesPromise
}

function loadDistrictsCached(cityCode: string): Promise<DistrictOption[]> {
    if (!cityCode) return Promise.resolve([])
    if (!districtsCache.has(cityCode)) {
        const p = regionDistrictOptions({ city_code: cityCode })
            .then((res) => (Array.isArray(res) ? res : []))
            .catch(() => {
                districtsCache.delete(cityCode)
                return []
            })
        districtsCache.set(cityCode, p)
    }
    return districtsCache.get(cityCode)!
}

const props = withDefaults(
    defineProps<{
        title?: string
        params: DecorateParamDef[]
        modelValue?: Record<string, string>
    }>(),
    {
        title: '跳转参数',
        modelValue: () => ({})
    }
)

const emit = defineEmits<{
    (event: 'update:modelValue', value: Record<string, string>): void
}>()

const categories = ref<CategoryOption[]>([])
const tags = ref<TagOption[]>([])
const cities = ref<CityOption[]>([])
const districts = ref<DistrictOption[]>([])
const selectedCityCode = ref('')

const hasKind = (kind: DecorateParamDef['kind']) => props.params.some((p) => p.kind === kind)

const queryValue = (key: string): string => {
    const val = props.modelValue?.[key]
    return val === undefined || val === null ? '' : String(val)
}

const enumValue = (param: DecorateParamDef): string => {
    const current = props.modelValue?.[param.key]
    if (current !== undefined && current !== null && current !== '') {
        return String(current)
    }
    return param.defaultValue ?? ''
}

const selectedTagIds = (key: string): string[] => {
    const raw = queryValue(key)
    if (!raw) return []
    return raw
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean)
}

const availableTags = computed(() => {
    const cid = Number(queryValue('category_id') || 0)
    if (!cid) return tags.value
    return tags.value.filter((t) => !t.category_id || Number(t.category_id) === cid)
})

const hasAnyValue = computed(() =>
    Object.values(props.modelValue || {}).some((v) => String(v || '').trim() !== '')
)

function emitQuery(next: Record<string, string>) {
    const cleaned: Record<string, string> = {}
    for (const [k, v] of Object.entries(next)) {
        const val = String(v ?? '').trim()
        if (val !== '') cleaned[k] = val
    }
    emit('update:modelValue', cleaned)
}

function handleSimpleChange(key: string, val: string) {
    const next = { ...(props.modelValue || {}) }
    const cleaned = String(val || '').trim()
    if (cleaned) next[key] = cleaned
    else delete next[key]
    emitQuery(next)
}

function handleEnumChange(param: DecorateParamDef, val: string) {
    const next = { ...(props.modelValue || {}) }
    if (val) next[param.key] = val
    else delete next[param.key]
    emitQuery(next)
}

function handleCategoryChange(param: DecorateParamDef, val: string) {
    const next = { ...(props.modelValue || {}) }
    if (val) {
        next[param.key] = val
        if (param.withNameKey) {
            const hit = categories.value.find((c) => String(c.id) === val)
            if (hit) next[param.withNameKey] = hit.name
        }
    } else {
        delete next[param.key]
        if (param.withNameKey) delete next[param.withNameKey]
    }
    emitQuery(next)
}

function handleTagsChange(param: DecorateParamDef, vals: string[]) {
    const next = { ...(props.modelValue || {}) }
    const joined = vals.map((v) => String(v).trim()).filter(Boolean).join(',')
    if (joined) next[param.key] = joined
    else delete next[param.key]
    emitQuery(next)
}

function stripRegionKeys(target: Record<string, string>) {
    delete target.province_code
    delete target.province_name
    delete target.city_code
    delete target.city_name
    delete target.district_code
    delete target.district_name
}

async function handleCityChange(cityCode: string) {
    const code = String(cityCode || '').trim()
    selectedCityCode.value = code
    const next = { ...(props.modelValue || {}) }
    stripRegionKeys(next)
    emitQuery(next)
    if (!code) {
        districts.value = []
        return
    }
    districts.value = await loadDistrictsCached(code)
}

function handleDistrictChange(districtCode: string) {
    const code = String(districtCode || '').trim()
    const next = { ...(props.modelValue || {}) }
    stripRegionKeys(next)
    if (!code) {
        emitQuery(next)
        return
    }
    const hit = districts.value.find((d) => d.district_code === code)
    const city = cities.value.find((c) => c.city_code === selectedCityCode.value)
    if (hit) {
        next.province_code = hit.province_code || city?.province_code || ''
        next.province_name = hit.province_name || city?.province_name || ''
        next.city_code = hit.city_code || city?.city_code || selectedCityCode.value
        next.city_name = hit.city_name || city?.city_name || ''
        next.district_code = hit.district_code
        next.district_name = hit.district_name
    }
    emitQuery(next)
}

function handleReset() {
    selectedCityCode.value = ''
    districts.value = []
    emitQuery({})
}

async function initOptions() {
    const tasks: Promise<any>[] = []
    if (hasKind('service_category')) {
        tasks.push(loadCategoriesCached().then((res) => (categories.value = res)))
    }
    if (hasKind('style_tag')) {
        tasks.push(loadTagsCached().then((res) => (tags.value = res)))
    }
    if (hasKind('region')) {
        tasks.push(loadCitiesCached().then((res) => (cities.value = res)))
    }
    await Promise.all(tasks)
}

watch(
    () => props.params,
    () => {
        void initOptions()
    },
    { immediate: true }
)

watch(
    () => props.modelValue?.city_code,
    async (code) => {
        const cityCode = String(code || '').trim()
        if (cityCode) {
            selectedCityCode.value = cityCode
            districts.value = await loadDistrictsCached(cityCode)
        } else if (!selectedCityCode.value) {
            districts.value = []
        }
    },
    { immediate: true }
)

onMounted(() => {
    void initOptions()
})
</script>

<style scoped lang="scss">
.link-params-form {
    padding: 14px 16px;
    border: 1px solid var(--el-border-color-lighter);
    border-radius: 10px;
    background: var(--el-fill-color-extra-light);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    &__title {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__sub {
        margin-top: 2px;
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }

    :deep(.el-form-item) {
        margin-bottom: 12px;

        &:last-child {
            margin-bottom: 0;
        }
    }
}
</style>
