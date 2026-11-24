<template>
    <view class="poster-editor">
        <view v-if="loading" class="poster-editor__loading">
            <base-skeleton :rows="4" />
        </view>

        <base-empty
            v-else-if="loadError"
            status="error"
            :error-kind="errorView?.errorKind || 'unknown'"
            :error-text="errorView?.title"
            :desc="errorView?.desc"
            :primary-action="errorView?.primaryAction"
            @primary="onErrorPrimary"
            @retry="loadConfig()"
        />

        <template v-else-if="config">
            <!-- 版本切换 -->
            <base-card v-if="versionOptions.length > 1" compact class="poster-card">
                <section-header title="海报模板版本" subtitle="切换当前编辑的模板样式" flush />
                <view class="version-list">
                    <view
                        v-for="item in versionOptions"
                        :key="item.config_id"
                        hover-class="is-pressed"
                        class="version-chip"
                        :class="{ 'is-active': item.config_id === form.config_id }"
                        @tap="switchVersion(item.config_id)"
                    >
                        <text class="version-chip__name">{{
                            item.template_name || `版本 V${item.template_version || item.config_id}`
                        }}</text>
                        <text v-if="Number(item.is_default) === 1" class="version-chip__badge">
                            默认
                        </text>
                    </view>
                </view>
            </base-card>

            <!-- 实时效果预览 -->
            <base-card compact class="poster-card">
                <view class="preview-head">
                    <view class="preview-head__texts">
                        <text class="preview-head__title">档期海报实时预览</text>
                        <text class="preview-head__sub">点击图片可放大查看生成效果</text>
                    </view>
                    <base-button
                        :text="previewLoading ? '生成中…' : '刷新预览'"
                        variant="ghost"
                        size="sm"
                        :block="false"
                        :loading="previewLoading"
                        :disabled="previewLoading || saving"
                        @click="refreshPreview(false)"
                    />
                </view>

                <view class="preview-stage" @tap="openPreviewImage">
                    <image
                        v-if="previewImageSrc"
                        :src="previewImageSrc"
                        class="preview-stage__img"
                        mode="widthFix"
                    />
                    <view v-else class="preview-stage__empty">
                        <tn-icon name="image" :size="48" class="preview-stage__icon" />
                        <text class="preview-stage__empty-text">
                            {{ previewLoading ? '正在渲染高清档期海报…' : '点击右上角“刷新预览”查看效果' }}
                        </text>
                    </view>
                </view>
            </base-card>

            <!-- 文案与背景配置 -->
            <base-card compact class="poster-card">
                <section-header
                    title="版面内容设置"
                    subtitle="订单详情与日程卡生成档期海报时将套用此处配置"
                    flush
                />

                <view v-if="canEditField('title')" class="field">
                    <text class="field__label">主标题</text>
                    <input
                        v-model="form.title"
                        class="field__input"
                        placeholder="例如：档期确认函"
                        placeholder-class="field__ph"
                        maxlength="60"
                    />
                </view>

                <view v-if="canEditField('subtitle')" class="field">
                    <text class="field__label">副标题</text>
                    <input
                        v-model="form.subtitle"
                        class="field__input"
                        placeholder="例如：SCHEDULE CONFIRMATION"
                        placeholder-class="field__ph"
                        maxlength="80"
                    />
                </view>

                <view v-if="canEditField('content_template')" class="field">
                    <view class="field__head">
                        <text class="field__label">正文模板</text>
                        <text class="field__count">{{ form.content_template.length }}/300</text>
                    </view>
                    <textarea
                        v-model="form.content_template"
                        class="field__textarea"
                        placeholder="支持点击下方变量一键插入正文"
                        placeholder-class="field__ph"
                        maxlength="300"
                    />
                    <view class="var-row">
                        <view
                            v-for="v in DYNAMIC_VARIABLES"
                            :key="v.key"
                            hover-class="is-pressed"
                            class="var-chip"
                            @tap="appendVariable(v.key)"
                        >
                            <text class="var-chip__text">+ {{ v.label }}</text>
                        </view>
                    </view>
                </view>

                <view v-if="canEditField('footer_note')" class="field">
                    <text class="field__label">底部落款</text>
                    <input
                        v-model="form.footer_note"
                        class="field__input"
                        placeholder="例如：期待与您共同见证重要时刻"
                        placeholder-class="field__ph"
                        maxlength="80"
                    />
                </view>

                <view v-if="canEditField('background_type')" class="field">
                    <text class="field__label">背景模式</text>
                    <view class="mode-row">
                        <view
                            hover-class="is-pressed"
                            class="mode-pill"
                            :class="{ 'is-active': form.background_type === 'color' }"
                            @tap="setBackgroundType('color')"
                        >
                            <text class="mode-pill__text">质感纯色</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            class="mode-pill"
                            :class="{ 'is-active': form.background_type === 'image' }"
                            @tap="setBackgroundType('image')"
                        >
                            <text class="mode-pill__text">自定义底图</text>
                        </view>
                    </view>
                </view>

                <view
                    v-if="form.background_type === 'color' && canEditField('background_color')"
                    class="field"
                >
                    <text class="field__label">主题底色</text>
                    <view v-if="colorPresets.length" class="color-list">
                        <view
                            v-for="preset in colorPresets"
                            :key="preset.value"
                            hover-class="is-pressed"
                            class="color-item"
                            :class="{ 'is-active': isColorActive(preset.value) }"
                            @tap="pickColor(preset.value)"
                        >
                            <view
                                class="color-item__dot"
                                :style="{ backgroundColor: preset.value }"
                            />
                            <text class="color-item__label">{{ preset.label }}</text>
                        </view>
                    </view>
                    <text v-else class="field__hint">当前模板使用默认底色方案</text>
                </view>

                <view
                    v-if="form.background_type === 'image' && canEditField('background_image')"
                    class="field"
                >
                    <text class="field__label">海报底图</text>
                    <view class="upload-box">
                        <image
                            v-if="backgroundImagePreview"
                            :src="backgroundImagePreview"
                            class="upload-box__img"
                            mode="aspectFill"
                            @tap="chooseBackgroundImage"
                        />
                        <view
                            hover-class="is-pressed"
                            class="upload-box__trigger"
                            @tap="chooseBackgroundImage"
                        >
                            <view class="upload-box__badge">
                                <tn-icon
                                    :name="uploading ? 'loading' : 'upload'"
                                    :size="30"
                                    class="upload-box__icon"
                                />
                            </view>
                            <text class="upload-box__text">{{
                                uploading
                                    ? '上传中…'
                                    : backgroundImagePreview
                                      ? '更换海报底图'
                                      : '点击上传自定义底图'
                            }}</text>
                            <text class="upload-box__sub">建议竖版高清图片</text>
                        </view>
                    </view>
                </view>
            </base-card>

            <view class="editor-actions">
                <view class="editor-actions__btn">
                    <base-button
                        text="生成预览"
                        variant="ghost"
                        size="md"
                        block
                        :loading="previewLoading"
                        :disabled="previewLoading || saving"
                        @click="refreshPreview(false)"
                    />
                </view>
                <view class="editor-actions__btn">
                    <base-button
                        :text="saving ? '保存中…' : '保存模板设置'"
                        variant="ink"
                        size="md"
                        block
                        :loading="saving"
                        :disabled="saving || previewLoading || uploading"
                        @click="handleSave"
                    />
                </view>
            </view>
        </template>
    </view>
</template>

<script setup lang="ts">
/**
 * 档期海报 / 确认函配置与预览编辑器（工作台专用）。
 *
 * 严格约束：
 * - 提交保存/预览仅传允许的轻量化字段与 config_id，绝不提交 design_config；
 * - MP-Weixin 下将后端返回的 base64 预览写入 USER_DATA_PATH 并在卸载时清理。
 */
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import {
    getStaffConfirmLetterConfig,
    previewStaffConfirmLetter,
    saveStaffConfirmLetterConfig,
    type StaffConfirmLetterConfig,
    type StaffConfirmLetterPreviewPayload
} from '@/api/staff'
import { uploadImage } from '@/api/app'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'

const DYNAMIC_VARIABLES = [
    { key: '{customer_alias}', label: '客户称呼' },
    { key: '{confirm_date}', label: '档期日期' },
    { key: '{service_name}', label: '服务项目' },
    { key: '{city}', label: '城市' },
    { key: '{staff_name}', label: '我的名字' }
]

const loading = ref(true)
const loadError = ref(false)
const rawError = ref<unknown>(null)
const errorView = computed(() => resolveErrorView(rawError.value as any, { hasForm: true }))

const config = ref<StaffConfirmLetterConfig | null>(null)
const previewImageSrc = ref('')
const backgroundImagePreview = ref('')
const previewLoading = ref(false)
const saving = ref(false)
const uploading = ref(false)
let lastTempPreviewFile = ''

const form = reactive({
    config_id: 0,
    title: '',
    subtitle: '',
    content_template: '',
    footer_note: '',
    background_type: 'color' as 'color' | 'image',
    background_image: '',
    background_color: '',
    text_theme: 'light' as 'light' | 'dark'
})

const versionOptions = computed(() => {
    const list = config.value?.versions
    return Array.isArray(list) ? list : []
})

const colorPresets = computed(() => {
    const list = config.value?.color_presets
    return Array.isArray(list) ? list : []
})

function canEditField(field: string): boolean {
    const allowed = config.value?.editable_fields
    if (!Array.isArray(allowed) || allowed.length === 0) return true
    return allowed.includes(field)
}

function unlinkTempFile(filePath: string): void {
    if (!filePath) return
    try {
        const fs = typeof uni.getFileSystemManager === 'function' ? uni.getFileSystemManager() : null
        fs?.unlink({ filePath, fail: () => {} })
    } catch {
        // 忽略临时文件清理异常
    }
}

function writePreviewDataUrl(dataUrl: string): Promise<string> {
    const clean = String(dataUrl || '').trim()
    if (!clean) return Promise.resolve('')

    const userDataPath =
        (globalThis as any)?.wx?.env?.USER_DATA_PATH || (uni as any)?.env?.USER_DATA_PATH
    const fs = typeof uni.getFileSystemManager === 'function' ? uni.getFileSystemManager() : null

    if (!userDataPath || !fs || !clean.startsWith('data:image/')) {
        return Promise.resolve(clean)
    }

    const commaIndex = clean.indexOf(',')
    if (commaIndex === -1) return Promise.resolve(clean)
    const base64Body = clean.slice(commaIndex + 1)
    const nextFile = `${userDataPath}/confirm_letter_preview_${Date.now()}.jpg`

    return new Promise((resolve) => {
        fs.writeFile({
            filePath: nextFile,
            data: base64Body,
            encoding: 'base64',
            success: () => {
                if (lastTempPreviewFile && lastTempPreviewFile !== nextFile) {
                    unlinkTempFile(lastTempPreviewFile)
                }
                lastTempPreviewFile = nextFile
                resolve(nextFile)
            },
            fail: () => resolve(clean)
        })
    })
}

function syncFormFromConfig(data: StaffConfirmLetterConfig): void {
    config.value = data
    form.config_id = Number(data.config_id || 0)
    form.title = String(data.title || '')
    form.subtitle = String(data.subtitle || '')
    form.content_template = String(data.content_template || '')
    form.footer_note = String(data.footer_note || '')
    form.background_type = data.background_type === 'image' ? 'image' : 'color'
    form.background_image = String(data.background_image || '')
    backgroundImagePreview.value = String(data.background_image_url || data.background_image || '')
    form.background_color = String(data.background_color || '')
    form.text_theme = data.text_theme === 'dark' ? 'dark' : 'light'
}

function buildPayload(): StaffConfirmLetterPreviewPayload {
    return {
        config_id: form.config_id,
        title: form.title.trim(),
        subtitle: form.subtitle.trim(),
        content_template: form.content_template.trim(),
        footer_note: form.footer_note.trim(),
        background_type: form.background_type,
        background_image: form.background_type === 'image' ? form.background_image : '',
        background_color: form.background_color,
        text_theme: form.text_theme
    }
}

async function refreshPreview(silent = false): Promise<void> {
    if (!form.config_id || previewLoading.value) return
    previewLoading.value = true
    try {
        const res = await previewStaffConfirmLetter(buildPayload())
        const url = res?.preview?.image_data_url || ''
        if (url) {
            previewImageSrc.value = await writePreviewDataUrl(url)
        }
        if (!silent) toast.success('已刷新海报预览')
    } catch (e: any) {
        if (!silent) toast.error(String(e?.message || '预览生成失败，请稍后重试'))
    } finally {
        previewLoading.value = false
    }
}

async function loadConfig(configId?: number): Promise<void> {
    loading.value = true
    loadError.value = false
    rawError.value = null
    try {
        const res = await getStaffConfirmLetterConfig(
            configId && configId > 0 ? { config_id: configId } : undefined
        )
        syncFormFromConfig(res)
        loading.value = false
        await refreshPreview(true)
    } catch (e) {
        rawError.value = e
        loadError.value = true
        loading.value = false
    }
}

function switchVersion(configId: number): void {
    if (!configId || configId === form.config_id || saving.value) return
    void loadConfig(configId)
}

function appendVariable(variableKey: string): void {
    form.content_template = `${form.content_template}${variableKey}`.slice(0, 300)
}

function setBackgroundType(type: 'color' | 'image'): void {
    form.background_type = type
}

function isColorActive(value: string): boolean {
    return form.background_color.trim().toUpperCase() === value.trim().toUpperCase()
}

function pickColor(value: string): void {
    form.background_color = value
    void refreshPreview(true)
}

function chooseBackgroundImage(): void {
    if (uploading.value) return
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            const path = res.tempFilePaths?.[0]
            if (!path) return
            uploading.value = true
            try {
                const uploaded = await uploadImage(path)
                form.background_image = uploaded.uri
                backgroundImagePreview.value = uploaded.url || uploaded.uri
                form.background_type = 'image'
                toast.success('底图上传成功')
                await refreshPreview(true)
            } catch (e: any) {
                toast.error(String(e?.message || '上传失败，请重试'))
            } finally {
                uploading.value = false
            }
        }
    })
}

function openPreviewImage(): void {
    if (!previewImageSrc.value) return
    uni.previewImage({
        urls: [previewImageSrc.value],
        current: previewImageSrc.value
    })
}

async function handleSave(): Promise<void> {
    if (saving.value || !form.config_id) return
    if (!form.title.trim()) {
        toast.warning('请填写海报主标题')
        return
    }
    if (!form.content_template.trim()) {
        toast.warning('请填写正文模板内容')
        return
    }
    if (form.background_type === 'image' && !form.background_image.trim()) {
        toast.warning('请上传自定义海报底图，或切换为纯色模式')
        return
    }

    saving.value = true
    try {
        const updated = await saveStaffConfirmLetterConfig(buildPayload())
        if (updated?.config_id) {
            syncFormFromConfig(updated)
        }
        toast.success('档期海报模板已保存')
        await refreshPreview(true)
    } catch (e: any) {
        toast.error(String(e?.message || '保存失败，请稍后重试'))
    } finally {
        saving.value = false
    }
}

function onErrorPrimary(): void {
    const kind = errorView.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        void loadConfig()
    } else if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

onMounted(() => {
    void loadConfig()
})

onUnmounted(() => {
    if (lastTempPreviewFile) {
        unlinkTempFile(lastTempPreviewFile)
        lastTempPreviewFile = ''
    }
})

defineExpose({ reload: loadConfig })
</script>

<style lang="scss" scoped>
.poster-editor {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__loading {
        padding: var(--sp-2) 0;
    }
}

.poster-card {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.version-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.version-chip {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-1);
    padding: var(--sp-1) var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__name {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__badge {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        font-weight: var(--fw-semibold);
    }
}

.preview-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);

    &__texts {
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.preview-stage {
    width: 100%;
    min-height: 320rpx;
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;

    &__img {
        width: 100%;
        display: block;
    }

    &__empty {
        padding: var(--sp-5) var(--sp-4);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-2);
    }

    &__icon {
        color: var(--c-ink-3);
    }

    &__empty-text {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.field {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__label {
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__count {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__input {
        width: 100%;
        height: 84rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }

    &__textarea {
        width: 100%;
        min-height: 240rpx;
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink);
        box-sizing: border-box;
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.var-row {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-1);
    margin-top: var(--sp-1);
}

.var-chip {
    padding: var(--sp-1) var(--sp-2);
    border-radius: var(--r-pill);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);

    &__text {
        font-size: var(--fs-20);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }
}

.mode-row {
    display: flex;
    gap: var(--sp-2);
}

.mode-pill {
    flex: 1;
    height: 76rpx;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    display: flex;
    align-items: center;
    justify-content: center;

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink);
        font-weight: var(--fw-medium);
    }
}

.color-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--sp-2);
}

.color-item {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-line);
    background: var(--c-surface);

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);
    }

    &__dot {
        width: 36rpx;
        height: 36rpx;
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-line-strong);
        flex-shrink: 0;
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-ink);
        font-weight: var(--fw-medium);
    }
}

.upload-box {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__img {
        width: 144rpx;
        height: 240rpx;
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-line);
        background: var(--c-surface-2);
        flex-shrink: 0;
    }

    &__trigger {
        flex: 1;
        min-height: 240rpx;
        padding: var(--sp-3);
        box-sizing: border-box;
        border-radius: var(--r-md);
        border: 1rpx dashed var(--c-control-border);
        background: var(--c-surface-2);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--sp-1);
        color: var(--c-ink-2);
    }

    &__badge {
        width: 64rpx;
        height: 64rpx;
        margin-bottom: var(--sp-1);
        border-radius: var(--r-pill);
        background: var(--c-surface);
        border: 1rpx solid var(--c-line);
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__icon {
        color: inherit;
    }

    &__text {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__sub {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}

.editor-actions {
    display: flex;
    gap: var(--sp-2);
    padding-bottom: var(--sp-2);

    &__btn {
        flex: 1;
        min-width: 0;
    }
}
</style>
