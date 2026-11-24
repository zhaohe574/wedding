<template>
    <el-form label-width="110px">
        <!-- ============ 1. 素材 ============ -->
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">开屏素材</div>
                <div class="ml-2 text-xs text-tx-secondary">全屏竖版，建议 750×1624</div>
            </div>
            <el-form-item label="显示开屏广告">
                <div class="flex items-center gap-3">
                    <el-switch
                        v-model="contentData.enabled"
                        :active-value="1"
                        :inactive-value="0"
                    />
                    <span class="text-xs text-tx-secondary">
                        关闭后冷启动直接进首页；配置仍会缓存，重新开启后下一次冷启动生效
                    </span>
                </div>
            </el-form-item>
            <el-form-item label="广告主图">
                <material-picker
                    v-model="contentData.image"
                    size="122px"
                    upload-class="bg-body"
                    exclude-domain
                >
                    <template #upload>
                        <div class="w-[122px] h-[122px] splash-upload-btn">开屏图</div>
                    </template>
                </material-picker>
            </el-form-item>
            <el-form-item label="品牌标识">
                <material-picker
                    v-model="contentData.logo_image"
                    size="122px"
                    upload-class="bg-body"
                    exclude-domain
                >
                    <template #upload>
                        <div class="w-[122px] h-[122px] splash-upload-btn">Logo</div>
                    </template>
                </material-picker>
                <div class="text-xs text-tx-secondary mt-1">
                    选填，透明 PNG。会叠在主图上方居中偏上位置，建议浅色图标。
                </div>
            </el-form-item>
            <div class="p-[12px] rounded-[8px] bg-[#f3f8ff] text-[#136bdf] mb-4 text-xs leading-5">
                未上传主图时，即使开关打开也不会展示（小程序直接进首页）。
            </div>
        </el-card>

        <!-- ============ 2. 展示与主按钮 ============ -->
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">展示与主按钮</div>
            </div>
            <el-form-item label="展示时长">
                <el-input-number
                    v-model="contentData.auto_seconds"
                    :min="1"
                    :max="10"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
                <div class="text-xs text-tx-secondary mt-1">
                    单位：秒（1–10）。到点后按下方「自动进入」决定是否自动跳首页。
                </div>
            </el-form-item>
            <el-form-item label="自动进入">
                <el-switch
                    v-model="contentData.auto_enter_enabled"
                    :active-value="1"
                    :inactive-value="0"
                />
                <span class="ml-3 text-xs text-tx-secondary">
                    关闭后只能点主按钮离开；按钮不可点击时无法退出，请谨慎关闭
                </span>
            </el-form-item>
            <el-form-item label="主按钮可点击">
                <el-radio-group v-model="contentData.skip_mode">
                    <el-radio v-for="item in SPLASH_SKIP_MODES" :key="item.value" :value="item.value">
                        {{ item.label }}
                    </el-radio>
                </el-radio-group>
            </el-form-item>
            <el-form-item v-if="contentData.skip_mode === 'countdown'" label="第几秒可点击">
                <el-input-number
                    v-model="contentData.skip_delay"
                    :min="0"
                    :max="contentData.auto_seconds"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
                <div class="text-xs text-tx-secondary mt-1">
                    按钮一直显示，第几秒变为可点击（0 = 一开始就可点），不能超过展示时长
                </div>
            </el-form-item>
            <el-form-item label="按钮文案">
                <el-input
                    v-model="contentData.button_text"
                    maxlength="12"
                    show-word-limit
                    placeholder="点击进入"
                />
            </el-form-item>
            <el-form-item label="点击跳转">
                <link-picker
                    v-model="contentData.link"
                    placeholder="留空：点击仅关闭开屏进入首页"
                />
                <div class="text-xs text-tx-secondary mt-1">
                    配了跳转后，点主图或主按钮将关闭开屏并直达目标；右上角会自动出现「跳过」按钮供直接进首页。
                </div>
            </el-form-item>
            <el-form-item label="生效时间">
                <el-date-picker
                    v-model="activeTimeRange"
                    type="datetimerange"
                    start-placeholder="开始时间"
                    end-placeholder="结束时间"
                    value-format="YYYY-MM-DD HH:mm:ss"
                    class="!w-full"
                />
                <div class="text-xs text-tx-secondary mt-1">留空 = 立即生效且长期有效</div>
            </el-form-item>
        </el-card>

        <!-- ============ 3. 展示频次 ============ -->
        <el-card shadow="never" class="!border-none flex mt-2">
            <div class="flex items-end mb-4">
                <div class="text-base text-[#101010] dark:text-[#ffffff] font-medium">展示频次</div>
            </div>
            <el-form-item label="频次">
                <el-select v-model="contentData.frequency" class="!w-full">
                    <el-option
                        v-for="item in SPLASH_FREQUENCIES"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                    />
                </el-select>
            </el-form-item>
            <el-form-item v-if="contentData.frequency === 'interval_days'" label="间隔天数">
                <el-input-number
                    v-model="contentData.interval_days"
                    :min="1"
                    :max="365"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
            </el-form-item>
            <el-form-item label="每日上限">
                <el-input-number
                    v-model="contentData.max_daily_count"
                    :min="0"
                    :max="99"
                    :step="1"
                    controls-position="right"
                    class="!w-full"
                />
                <div class="text-xs text-tx-secondary mt-1">
                    0 = 不限制。与上面的频次叠加生效（例如「每次启动」+ 上限 3 次/天）
                </div>
            </el-form-item>
            <div class="p-[12px] rounded-[8px] bg-[#f3f8ff] text-[#136bdf] text-xs leading-5">
                <div>{{ frequencyHint }}</div>
                <div class="mt-1 opacity-80">
                    开屏判定用本地缓存：保存后需完全退出小程序、再次冷启动才生效，不是实时刷新。
                </div>
            </div>
        </el-card>
    </el-form>
</template>
<script lang="ts" setup>
import {
    effectiveSkipDelay,
    formatDecorateLinkSummary,
    normalizeSplashContent,
    SPLASH_FREQUENCIES,
    SPLASH_SKIP_MODES
} from '@shared/contracts/decorate'
import type { PropType } from 'vue'

import type options from './options'

type OptionsType = ReturnType<typeof options>

const emits = defineEmits<(event: 'update:content', data: OptionsType['content']) => void>()

const props = defineProps({
    content: {
        type: Object as PropType<OptionsType['content']>,
        default: () => ({})
    },
    styles: {
        type: Object as PropType<OptionsType['styles']>,
        default: () => ({})
    }
})

const contentData = computed({
    get: () => props.content,
    set: (newValue) => {
        emits('update:content', newValue)
    }
})

type TimeRange = [] | [string, string]

const activeTimeRange = computed<TimeRange>({
    get: (): TimeRange => {
        if (!contentData.value.start_time && !contentData.value.end_time) {
            return []
        }
        return [contentData.value.start_time || '', contentData.value.end_time || '']
    },
    set: (value: TimeRange) => {
        contentData.value.start_time = value?.[0] || ''
        contentData.value.end_time = value?.[1] || ''
    }
})

/** 展示时长改短时，把「可点击秒数」夹回时长内，避免出现永远等不到的可点击状态 */
watch(
    () => contentData.value.auto_seconds,
    (seconds) => {
        if (contentData.value.skip_delay > seconds) {
            emits('update:content', { ...contentData.value, skip_delay: seconds })
        }
    }
)

/** 用契约里的同一套规则把当前配置翻译成一句人话，避免运营理解偏差 */
const frequencyHint = computed(() => {
    const content = normalizeSplashContent(contentData.value)
    if (content.enabled !== 1) return '当前未启用，小程序不会展示开屏广告。'
    if (!content.image) return '已启用但未上传主图，小程序仍会直接进首页。'

    const freqLabel =
        SPLASH_FREQUENCIES.find((f) => f.value === content.frequency)?.label || content.frequency
    const delay = effectiveSkipDelay(content)
    const buttonLabel =
        content.skip_mode === 'never'
            ? content.auto_enter_enabled === 1
                ? '按钮常显但不可点击，只等自动进入'
                : '按钮常显但不可点击，已关闭自动进入，真机会兜底立即可点'
            : content.skip_mode === 'always' || delay <= 0
              ? '按钮常显、立即可点击'
              : `按钮常显、第 ${delay} 秒后可点击`

    const auto =
        content.auto_enter_enabled === 1
            ? `展示 ${content.auto_seconds} 秒后自动进入`
            : '不自动进入，只能点主按钮离开'
    const daily = content.max_daily_count > 0 ? `，每日最多 ${content.max_daily_count} 次` : ''
    const linkSummary = formatDecorateLinkSummary(content.link)
    const linkText = linkSummary.configured
        ? ` · 点击跳转「${linkSummary.title}」（右上角带跳过）`
        : ''

    return `预计效果：${auto} · ${buttonLabel} · ${freqLabel}${daily}${linkText}`
})
</script>

<style lang="scss" scoped>
.splash-upload-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 122px;
    height: 122px;
    color: #8c8c8c;
    border: 1px dashed #dcdfe6;
    border-radius: 8px;
}
</style>
