<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="档期确认函"
            title-align="center"
            variant="solid"
            bg-color="#191713"
            text-color="#FFFDF8"
        />

        <view class="letter-page">
            <view class="letter-page__content wm-page-content">
                <BaseCard variant="panel" scene="staff" class="letter-section">
                    <view class="section-head">
                        <text class="section-head__title">模板版本</text>
                    </view>
                    <scroll-view scroll-x class="template-scroll" show-scrollbar="false">
                        <view class="template-list">
                            <view
                                v-for="item in versions"
                                :key="item.config_id"
                                :class="[
                                    'template-chip',
                                    { 'template-chip--active': Number(item.config_id) === Number(activeConfigId) }
                                ]"
                                @click="switchTemplate(Number(item.config_id))"
                            >
                                <text class="template-chip__name">{{ item.template_name || '未命名模板' }}</text>
                                <text class="template-chip__meta">
                                    模板 v{{ item.template_version || 1 }}{{ Number(item.is_default) === 1 ? ' · 默认' : '' }}
                                </text>
                            </view>
                        </view>
                    </scroll-view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="letter-section">
                    <view class="section-head">
                        <text class="section-head__title">海报预览</text>
                        <text class="section-head__action" @click="refreshPreview">刷新</text>
                    </view>
                    <view class="poster-canvas" :style="canvasStyle">
                        <view
                            v-for="layer in sortedLayers"
                            :key="layer.id"
                            class="poster-layer"
                            :style="layerStyle(layer)"
                        >
                            <text v-if="layer.type === 'text'" class="poster-layer__text" :style="textStyle(layer)">
                                {{ renderLayerText(layer) }}
                            </text>
                            <image
                                v-else-if="layer.type === 'image' && (layer.src_url || layer.src)"
                                class="poster-layer__image"
                                :src="layer.src_url || layer.src"
                                :mode="layer.fit === 'contain' ? 'aspectFit' : 'aspectFill'"
                            />
                            <view v-else-if="layer.type === 'qrcode'" class="poster-layer__qrcode">
                                <image
                                    v-if="qrcodeLayerSrc(layer)"
                                    class="poster-layer__qrcode-image"
                                    :src="qrcodeLayerSrc(layer)"
                                    mode="aspectFit"
                                />
                                <text v-else>QR</text>
                            </view>
                            <view v-else-if="layer.type === 'rect'" class="poster-layer__rect" :style="rectStyle(layer)" />
                            <view v-else-if="layer.type === 'line'" class="poster-layer__line" :style="lineStyle(layer)" />
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="panel" scene="staff" class="letter-section">
                    <view class="section-head">
                        <text class="section-head__title">常用内容</text>
                    </view>
                    <BaseInput
                        v-if="editableFields.includes('title')"
                        v-model="liteForm.title"
                        label="标题"
                        maxlength="40"
                        clearable
                    />
                    <BaseInput
                        v-if="editableFields.includes('subtitle')"
                        v-model="liteForm.subtitle"
                        label="副标题"
                        maxlength="80"
                        clearable
                    />
                    <view v-if="editableFields.includes('content_template')" class="textarea-field">
                        <view class="textarea-field__head">
                            <text class="textarea-field__label">正文模板</text>
                            <text class="textarea-field__count">{{ liteForm.content_template.length }}/500</text>
                        </view>
                        <view class="dynamic-field-box">
                            <view class="dynamic-field-box__head">
                                <text>可用动态字段</text>
                                <text>点击插入正文</text>
                            </view>
                            <view class="dynamic-field-tags">
                                <view
                                    v-for="field in dynamicFields"
                                    :key="field.token"
                                    class="dynamic-field-tag"
                                    @click="insertDynamicField('content_template', field.token)"
                                >
                                    <text class="dynamic-field-tag__label">{{ field.label }}</text>
                                    <text class="dynamic-field-tag__example">{{ field.example }}</text>
                                </view>
                            </view>
                        </view>
                        <textarea
                            v-model="liteForm.content_template"
                            class="letter-textarea"
                            :maxlength="500"
                            :show-confirm-bar="false"
                            auto-height
                        />
                    </view>
                    <view v-if="editableFields.includes('footer_note')" class="textarea-field">
                        <view class="textarea-field__head">
                            <text class="textarea-field__label">页脚文案</text>
                            <text class="textarea-field__count">{{ liteForm.footer_note.length }}/160</text>
                        </view>
                        <view class="dynamic-field-box dynamic-field-box--compact">
                            <view class="dynamic-field-box__head">
                                <text>可用动态字段</text>
                                <text>点击插入页脚</text>
                            </view>
                            <view class="dynamic-field-tags">
                                <view
                                    v-for="field in dynamicFields"
                                    :key="field.token"
                                    class="dynamic-field-tag"
                                    @click="insertDynamicField('footer_note', field.token)"
                                >
                                    <text class="dynamic-field-tag__label">{{ field.label }}</text>
                                    <text class="dynamic-field-tag__example">{{ field.example }}</text>
                                </view>
                            </view>
                        </view>
                        <textarea
                            v-model="liteForm.footer_note"
                            class="letter-textarea"
                            :maxlength="160"
                            :show-confirm-bar="false"
                            auto-height
                        />
                    </view>
                </BaseCard>

                <BaseCard v-if="editableFields.includes('background')" variant="panel" scene="staff" class="letter-section">
                    <view class="section-head">
                        <text class="section-head__title">背景</text>
                    </view>
                    <view v-if="editableFields.includes('background')" class="segment-row">
                        <view
                            :class="['segment-item', { 'segment-item--active': liteForm.background_type === 'color' }]"
                            @click="liteForm.background_type = 'color'"
                        >
                            <text>纯色</text>
                        </view>
                        <view
                            :class="['segment-item', { 'segment-item--active': liteForm.background_type === 'image' }]"
                            @click="liteForm.background_type = 'image'"
                        >
                            <text>图片</text>
                        </view>
                    </view>
                    <BaseInput
                        v-if="editableFields.includes('background')"
                        v-model="liteForm.background_color"
                        label="背景色"
                        maxlength="20"
                    />
                    <view v-if="editableFields.includes('background') && liteForm.background_type === 'image'" class="segment-row">
                        <view
                            :class="['segment-item', { 'segment-item--active': liteForm.background_fit === 'cover' }]"
                            @click="liteForm.background_fit = 'cover'"
                        >
                            <text>铺满裁剪</text>
                        </view>
                        <view
                            :class="['segment-item', { 'segment-item--active': liteForm.background_fit === 'contain' }]"
                            @click="liteForm.background_fit = 'contain'"
                        >
                            <text>完整显示</text>
                        </view>
                    </view>
                    <view v-if="editableFields.includes('background') && liteForm.background_type === 'image'" class="upload-tile" @click="chooseImage('background_image')">
                        <image v-if="liteForm.background_image" class="upload-tile__image" :src="liteForm.background_image" mode="aspectFill" />
                        <text v-else class="upload-tile__text">上传背景图</text>
                    </view>
                </BaseCard>
            </view>

            <ActionArea sticky safeBottom tone="solid">
                <view class="letter-action-bar">
                    <BaseButton
                        block
                        variant="dark"
                        height="88rpx"
                        :loading="saving"
                        :label="saving ? '保存中...' : '保存配置'"
                        @click="handleSave"
                    />
                </view>
            </ActionArea>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseInput from '@/components/base/BaseInput.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import PageShell from '@/components/base/PageShell.vue'
import {
    staffCenterScheduleConfirmLetterConfig,
    staffCenterScheduleConfirmLetterPreview,
    staffCenterScheduleConfirmLetterSaveConfig
} from '@/api/staffCenter'
import { uploadImage } from '@/api/app'
import { ensureStaffCenterAccess } from '@/packages/common/utils/staff-center'
import { showError, showSuccess } from '@/utils/feedback'

type ImageKey = 'background_image'

const saving = ref(false)
const versions = ref<any[]>([])
const activeConfigId = ref(0)
const designConfig = ref<any>({
    canvas: { width: 1080, height: 1920 },
    background: { type: 'color', color: '#191713', image: '', fit: 'cover', opacity: 1 },
    layers: []
})
const editableFields = ref<string[]>([])
const previewSnapshot = ref<any>({
    service_date_label: '2026年08月18日',
    customer_alias: '张姓新人',
    service_name: '婚礼跟拍',
    city_label: '杭州 西湖区',
    staff_name: '服务人员',
    variables: {
        service_date_label: '2026年08月18日',
        customer_alias: '张姓新人',
        service_name: '婚礼跟拍',
        city_label: '杭州 西湖区',
        staff_name: '服务人员'
    }
})

const liteForm = reactive<Record<string, any>>({
    title: '',
    subtitle: '',
    content_template: '',
    footer_note: '',
    background_type: 'color',
    background_image: '',
    background_color: '#191713',
    background_fit: 'cover',
    qrcode_image: ''
})

const scale = 420 / 1080

const dynamicFields = [
    { label: '服务日期', token: '{service_date_label}', example: '2026年08月18日' },
    { label: '客户称呼', token: '{customer_alias}', example: '张姓新人' },
    { label: '服务类型', token: '{service_name}', example: '婚礼跟拍' },
    { label: '服务城市', token: '{city_label}', example: '杭州 西湖区' },
    { label: '服务人员', token: '{staff_name}', example: '服务人员姓名' }
]

const sortedLayers = computed(() => {
    return [...(designConfig.value.layers || [])]
        .filter((layer: any) => Number(layer.visible ?? 1) === 1)
        .sort((a: any, b: any) => Number(a.z || 0) - Number(b.z || 0))
})

const canvasStyle = computed(() => {
    const bg = designConfig.value.background || {}
    let style = `width:${1080 * scale}rpx;height:${1920 * scale}rpx;background-color:${liteForm.background_color || bg.color || '#191713'};`
    const image = liteForm.background_type === 'image' ? (liteForm.background_image || bg.image_url || bg.image) : ''
    if (image) {
        const backgroundSize = liteForm.background_fit === 'contain' ? 'contain' : 'cover'
        style += `background-image:url(${image});background-size:${backgroundSize};background-position:center;background-repeat:no-repeat;`
    }
    return style
})

const assignConfig = (data: any) => {
    activeConfigId.value = Number(data?.config_id || 0)
    versions.value = Array.isArray(data?.versions) ? data.versions : versions.value
    designConfig.value = data?.design_config || designConfig.value
    editableFields.value = Array.isArray(data?.editable_fields) ? data.editable_fields : []
    const bg = designConfig.value.background || {}
    liteForm.background_type = bg.type || data?.background_type || 'color'
    liteForm.background_color = bg.color || data?.background_color || '#191713'
    liteForm.background_image = bg.image_url || bg.image || data?.background_image_url || data?.background_image || ''
    liteForm.background_fit = bg.fit === 'contain' ? 'contain' : 'cover'
    liteForm.qrcode_image = data?.qrcode_image_url || data?.qrcode_image || ''
    ;(designConfig.value.layers || []).forEach((layer: any) => {
        if (Number(layer.editable ?? 0) !== 1) return
        if (layer.field && layer.type === 'text') {
            liteForm[layer.field] = layer.text || liteForm[layer.field] || ''
        }
        if (layer.type === 'qrcode') {
            liteForm.qrcode_image = layer.src_url || layer.src || liteForm.qrcode_image
        }
    })
}

const refreshPreview = async () => {
    try {
        const data: any = await staffCenterScheduleConfirmLetterPreview({
            ...liteForm,
            config_id: activeConfigId.value
        })
        previewSnapshot.value = data?.preview?.rendered_snapshot || previewSnapshot.value
        if (data?.config?.design_config) {
            designConfig.value = data.config.design_config
        }
    } catch {
        // 预览失败不阻断本地编辑。
    }
}

const loadConfig = async (configId = Number(activeConfigId.value || 0)) => {
    if (!(await ensureStaffCenterAccess())) return
    try {
        const data: any = await staffCenterScheduleConfirmLetterConfig({ config_id: configId })
        assignConfig(data || {})
        await refreshPreview()
    } catch (error: any) {
        showError(error, '加载失败')
    }
}

const handleSave = async () => {
    saving.value = true
    try {
        const data: any = await staffCenterScheduleConfirmLetterSaveConfig({
            ...liteForm,
            config_id: activeConfigId.value
        })
        assignConfig(data || {})
        await refreshPreview()
        showSuccess('配置已保存')
    } catch (error: any) {
        showError(error, '保存失败')
    } finally {
        saving.value = false
    }
}

const switchTemplate = async (configId: number) => {
    if (!configId || configId === Number(activeConfigId.value)) {
        return
    }
    await loadConfig(configId)
}

const insertDynamicField = (key: 'content_template' | 'footer_note', token: string) => {
    const text = String(liteForm[key] || '').trim()
    if (!text) {
        liteForm[key] = token
        return
    }
    const joiner = /[\s，。；、,.]$/.test(text) ? '' : ' '
    liteForm[key] = `${text}${joiner}${token}`
}

const renderLayerText = (layer: any) => {
    const vars = previewSnapshot.value.variables || previewSnapshot.value || {}
    return String(layer.text || '').replace(/\{([a-zA-Z0-9_]+)\}/g, (_match, key) => String(vars[key] ?? `{${key}}`))
}

const qrcodeLayerSrc = (layer: any) => {
    return String(layer.src_url || layer.src || liteForm.qrcode_image || '')
}

const layerStyle = (layer: any) => {
    return [
        `left:${Number(layer.x || 0) * scale}rpx`,
        `top:${Number(layer.y || 0) * scale}rpx`,
        `width:${Number(layer.w || 1) * scale}rpx`,
        `height:${Number(layer.h || 1) * scale}rpx`,
        `opacity:${Number(layer.opacity ?? 1)}`,
        `transform:rotate(${Number(layer.rotate || 0)}deg)`,
        `z-index:${Number(layer.z || 0)}`
    ].join(';')
}

const textStyle = (layer: any) => {
    return [
        `font-size:${Number(layer.fontSize || 42) * scale}rpx`,
        `font-weight:${layer.fontWeight || '400'}`,
        `line-height:${Number(layer.lineHeight || 1.35)}`,
        `text-align:${layer.align || 'center'}`,
        `color:${layer.color || '#FFF7E6'}`
    ].join(';')
}

const rectStyle = (layer: any) => {
    return [
        `background:${layer.fill || '#FFFFFF'}`,
        `border:${Math.max(0, Number(layer.strokeWidth || 0) * scale)}rpx solid ${layer.stroke || 'transparent'}`,
        `border-radius:${Number(layer.radius || 0) * scale}rpx`
    ].join(';')
}

const lineStyle = (layer: any) => {
    return `border-top:${Math.max(1, Number(layer.strokeWidth || 2) * scale)}rpx solid ${layer.stroke || '#D8C08B'}`
}

const chooseImage = (key: ImageKey) => {
    uni.chooseImage({
        count: 1,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            const path = res.tempFilePaths?.[0]
            if (!path) return
            try {
                const uploadRes: any = await uploadImage(path)
                liteForm[key] = uploadRes?.uri || uploadRes?.url || ''
                showSuccess('图片已上传')
            } catch (error: any) {
                showError(error, '上传失败')
            }
        }
    })
}

onShow(loadConfig)
</script>

<style scoped lang="scss">
.letter-page {
    min-height: 100%;
}

.letter-page__content {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
    padding-bottom: 140rpx;
}

.letter-section {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
}

.template-scroll {
    width: 100%;
    white-space: nowrap;
}

.template-list {
    display: inline-flex;
    gap: 16rpx;
    padding: 2rpx 2rpx 6rpx;
}

.template-chip {
    width: 250rpx;
    min-height: 112rpx;
    padding: 20rpx;
    border-radius: 14rpx;
    border: 1rpx solid #e4d8c0;
    background: #fffdf8;
    box-sizing: border-box;
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    gap: 8rpx;
}

.template-chip--active {
    border-color: #191713;
    background: #191713;
}

.template-chip__name {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 26rpx;
    font-weight: 800;
    color: #191713;
}

.template-chip__meta {
    font-size: 21rpx;
    color: #8d8070;
}

.template-chip--active .template-chip__name,
.template-chip--active .template-chip__meta {
    color: #fffdf8;
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
}

.section-head__title {
    font-size: 30rpx;
    font-weight: 700;
    color: #191713;
}

.section-head__action {
    font-size: 24rpx;
    color: #9a6a22;
}

.poster-canvas {
    position: relative;
    margin: 6rpx auto 0;
    overflow: hidden;
    border-radius: 10rpx;
    background-size: cover;
    background-position: center;
    box-shadow: 0 18rpx 48rpx rgba(31, 27, 22, 0.18);
}

.poster-layer {
    position: absolute;
    overflow: hidden;
}

.poster-layer__text {
    display: block;
    width: 100%;
    height: 100%;
    word-break: break-word;
}

.poster-layer__image,
.poster-layer__rect,
.poster-layer__qrcode-image {
    width: 100%;
    height: 100%;
}

.poster-layer__qrcode {
    width: 100%;
    height: 100%;
    border-radius: 10rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    color: #1f1b16;
    font-size: 22rpx;
    font-weight: 700;
}

.poster-layer__line {
    width: 100%;
    height: 0;
    margin-top: 50%;
}

.textarea-field {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
}

.textarea-field__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.textarea-field__label {
    font-size: 24rpx;
    color: #62594d;
}

.textarea-field__count {
    font-size: 22rpx;
    color: #9a9388;
}

.letter-textarea {
    width: 100%;
    min-height: 180rpx;
    box-sizing: border-box;
    padding: 24rpx;
    border-radius: 10rpx;
    background: #fffaf1;
    color: #191713;
    font-size: 26rpx;
    line-height: 1.6;
}

.dynamic-field-box {
    padding: 18rpx;
    border-radius: 12rpx;
    border: 1rpx solid #e4d8c0;
    background: #fffdf8;
}

.dynamic-field-box--compact {
    padding: 16rpx;
}

.dynamic-field-box__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 14rpx;
    font-size: 22rpx;
    color: #8d8070;
}

.dynamic-field-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.dynamic-field-tag {
    min-width: 196rpx;
    max-width: 100%;
    padding: 14rpx 16rpx;
    border-radius: 10rpx;
    border: 1rpx solid #d9be82;
    background: #fffaf1;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 4rpx;
}

.dynamic-field-tag__label {
    font-size: 24rpx;
    font-weight: 800;
    color: #191713;
}

.dynamic-field-tag__example {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 20rpx;
    color: #9a6a22;
}

.segment-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16rpx;
}

.segment-item {
    height: 72rpx;
    border-radius: 10rpx;
    border: 1rpx solid #e4d8c0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c665c;
    font-size: 24rpx;
    background: #fffdf8;
}

.segment-item--active {
    background: #191713;
    border-color: #191713;
    color: #fffdf8;
}

.upload-tile {
    height: 240rpx;
    border-radius: 12rpx;
    border: 1rpx dashed #d9be82;
    background: #fffaf1;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-tile--small {
    height: 160rpx;
}

.upload-tile__image {
    width: 100%;
    height: 100%;
}

.upload-tile__text {
    color: #9a6a22;
    font-size: 24rpx;
}

.letter-action-bar {
    width: 100%;
}
</style>
