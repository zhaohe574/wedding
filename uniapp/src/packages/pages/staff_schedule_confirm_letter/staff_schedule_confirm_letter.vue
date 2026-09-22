<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="staff" tone="workspace" hasSafeBottom>
        <BaseNavbar
            title="档期海报设计"
            title-align="center"
            variant="solid"
            bg-color="#181614"
            text-color="#FFFDF8"
        />

        <view class="letter-page">
            <view class="letter-page__content wm-page-content">
                <!-- 模板版本选择 -->
                <BaseCard variant="panel" scene="staff" class="letter-section" padding="28rpx 28rpx">
                    <view class="section-head">
                        <view class="section-head__left">
                            <text class="section-head__title">设计版式风格</text>
                            <text class="section-head__subtitle">切换不同主理人海报版型</text>
                        </view>
                    </view>
                    <scroll-view scroll-x class="template-scroll" :show-scrollbar="false">
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
                                <view class="template-chip__top">
                                    <text class="template-chip__name">{{ item.template_name || '主理人海报' }}</text>
                                    <text v-if="Number(item.is_default) === 1" class="template-chip__default-tag">默认</text>
                                </view>
                                <view class="template-chip__bottom">
                                    <text class="template-chip__meta">版式 v{{ item.template_version || 1 }}</text>
                                    <text class="template-chip__status-dot" />
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </BaseCard>

                <!-- 海报即时预览舞台 -->
                <BaseCard
                    variant="hero"
                    scene="staff"
                    class="letter-section letter-stage-card"
                    background="linear-gradient(145deg, #24201A 0%, #161513 60%, #2A1F14 100%)"
                    border="1rpx solid rgba(217, 190, 130, 0.45)"
                    box-shadow="0 24rpx 60rpx rgba(10, 8, 6, 0.4)"
                    padding="28rpx 28rpx"
                >
                    <view class="section-head section-head--dark">
                        <view class="section-head__left">
                            <text class="section-head__title section-head__title--gold">实时渲染画幅</text>
                            <text class="section-head__subtitle section-head__subtitle--muted">1080 × 1920 高定档期海报视觉</text>
                        </view>
                        <view class="refresh-pill" @click="refreshPreview">
                            <text class="refresh-pill__icon">↻</text>
                            <text class="refresh-pill__text">刷新效果</text>
                        </view>
                    </view>

                    <view class="poster-stage">
                        <view v-if="previewImage" class="poster-frame" @click="openPreview">
                            <image :src="previewImage" mode="widthFix" class="poster-frame__img" />
                            <view class="poster-frame__overlay">
                                <view class="poster-frame__badge">
                                    <text class="poster-frame__badge-icon">🔍</text>
                                    <text class="poster-frame__badge-text">点击放大查看海报大图</text>
                                </view>
                            </view>
                        </view>
                        <view v-else class="poster-empty" @click="refreshPreview">
                            <view class="poster-empty__icon-box">
                                <text class="poster-empty__glyph">✦</text>
                            </view>
                            <text class="poster-empty__title">海报正在生成中</text>
                            <text class="poster-empty__desc">点击此处或右上角「刷新效果」获取渲染结果</text>
                        </view>
                    </view>
                </BaseCard>

                <!-- 文案内容定制 -->
                <BaseCard variant="panel" scene="staff" class="letter-section" padding="28rpx 28rpx">
                    <view class="section-head">
                        <view class="section-head__left">
                            <text class="section-head__title">定制主理人文案</text>
                            <text class="section-head__subtitle">个性化主标题与确认信寄语</text>
                        </view>
                    </view>

                    <view class="form-inputs-group">
                        <BaseInput
                            v-if="editableFields.includes('title')"
                            v-model="liteForm.title"
                            label="主标题"
                            placeholder="如：WEDDING CEREMONY 档期确认"
                            :maxlength="40"
                            clearable
                        />
                        <BaseInput
                            v-if="editableFields.includes('subtitle')"
                            v-model="liteForm.subtitle"
                            label="副标题"
                            placeholder="如：致 最美的一天 · 官方履约凭据"
                            :maxlength="80"
                            clearable
                        />

                        <!-- 正文模板与动态变量 -->
                        <view v-if="editableFields.includes('content_template')" class="textarea-field">
                            <view class="textarea-field__head">
                                <text class="textarea-field__label">正文寄语文案</text>
                                <text class="textarea-field__count">{{ liteForm.content_template.length }}/500</text>
                            </view>

                            <view class="dynamic-var-box">
                                <view class="dynamic-var-box__head">
                                    <text class="dynamic-var-box__title">点击快速插入动态字段</text>
                                    <text class="dynamic-var-box__tip">将自动替换为实际订单数据</text>
                                </view>
                                <view class="dynamic-var-tags">
                                    <view
                                        v-for="field in dynamicFields"
                                        :key="field.token"
                                        class="dynamic-var-tag"
                                        @click="insertDynamicField('content_template', field.token)"
                                    >
                                        <view class="dynamic-var-tag__top">
                                            <text class="dynamic-var-tag__label">{{ field.label }}</text>
                                            <text class="dynamic-var-tag__plus">+</text>
                                        </view>
                                        <text class="dynamic-var-tag__example">{{ field.example }}</text>
                                    </view>
                                </view>
                            </view>

                            <textarea
                                v-model="liteForm.content_template"
                                class="letter-textarea"
                                placeholder="输入档期确认信正文，可插入上方动态字段..."
                                :maxlength="500"
                                :show-confirm-bar="false"
                                auto-height
                            />
                        </view>

                        <!-- 页脚文案 -->
                        <view v-if="editableFields.includes('footer_note')" class="textarea-field">
                            <view class="textarea-field__head">
                                <text class="textarea-field__label">页脚备注与防伪说明</text>
                                <text class="textarea-field__count">{{ liteForm.footer_note.length }}/160</text>
                            </view>

                            <view class="dynamic-var-box dynamic-var-box--compact">
                                <view class="dynamic-var-box__head">
                                    <text class="dynamic-var-box__title">页脚可用字段</text>
                                </view>
                                <view class="dynamic-var-tags">
                                    <view
                                        v-for="field in dynamicFields"
                                        :key="field.token"
                                        class="dynamic-var-tag"
                                        @click="insertDynamicField('footer_note', field.token)"
                                    >
                                        <view class="dynamic-var-tag__top">
                                            <text class="dynamic-var-tag__label">{{ field.label }}</text>
                                            <text class="dynamic-var-tag__plus">+</text>
                                        </view>
                                        <text class="dynamic-var-tag__example">{{ field.example }}</text>
                                    </view>
                                </view>
                            </view>

                            <textarea
                                v-model="liteForm.footer_note"
                                class="letter-textarea"
                                placeholder="输入页脚文案，如官方认证提示等..."
                                :maxlength="160"
                                :show-confirm-bar="false"
                                auto-height
                            />
                        </view>
                    </view>
                </BaseCard>

                <!-- 背景与视觉基调 -->
                <BaseCard v-if="editableFields.includes('background')" variant="panel" scene="staff" class="letter-section" padding="28rpx 28rpx">
                    <view class="section-head">
                        <view class="section-head__left">
                            <text class="section-head__title">视觉背景基调</text>
                            <text class="section-head__subtitle">设定海报画面的底色或品牌底图</text>
                        </view>
                    </view>

                    <view class="bg-config-group">
                        <view class="segment-pill-row">
                            <view
                                :class="['segment-pill', { 'segment-pill--active': liteForm.background_type === 'color' }]"
                                @click="liteForm.background_type = 'color'"
                            >
                                <text>纯色沉浸底色</text>
                            </view>
                            <view
                                :class="['segment-pill', { 'segment-pill--active': liteForm.background_type === 'image' }]"
                                @click="liteForm.background_type = 'image'"
                            >
                                <text>自定背景底图</text>
                            </view>
                        </view>

                        <BaseInput
                            v-if="liteForm.background_type === 'color'"
                            v-model="liteForm.background_color"
                            label="背景色值"
                            placeholder="#191713"
                            :maxlength="20"
                            clearable
                        />

                        <template v-if="liteForm.background_type === 'image'">
                            <view class="segment-pill-row">
                                <view
                                    :class="['segment-pill', { 'segment-pill--active': liteForm.background_fit === 'cover' }]"
                                    @click="liteForm.background_fit = 'cover'"
                                >
                                    <text>铺满全屏裁剪</text>
                                </view>
                                <view
                                    :class="['segment-pill', { 'segment-pill--active': liteForm.background_fit === 'contain' }]"
                                    @click="liteForm.background_fit = 'contain'"
                                >
                                    <text>完整内嵌显示</text>
                                </view>
                            </view>

                            <view class="upload-studio-card" @click="chooseImage('background_image')">
                                <image v-if="liteForm.background_image" class="upload-studio-card__img" :src="liteForm.background_image" mode="aspectFill" />
                                <view v-else class="upload-studio-card__placeholder">
                                    <text class="upload-studio-card__icon">📷</text>
                                    <text class="upload-studio-card__text">点击上传海报背景图</text>
                                    <text class="upload-studio-card__hint">推荐 1080 × 1920 竖版高清底图</text>
                                </view>
                            </view>
                        </template>
                    </view>
                </BaseCard>
            </view>

            <!-- 底部保存悬浮栏 -->
            <ActionArea sticky safeBottom tone="solid">
                <view class="letter-action-bar">
                    <BaseButton
                        block
                        variant="dark"
                        height="92rpx"
                        :loading="saving"
                        :label="saving ? '正在保存海报配置...' : '保存海报配置并生效'"
                        @click="handleSave"
                    />
                </view>
            </ActionArea>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { onShow, onUnload } from '@dcloudio/uni-app'
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
const previewImage = ref('')
const openPreview = () => {
    if (previewImage.value) uni.previewImage({ urls: [previewImage.value] })
}
onUnload(() => {
    if (previewImage.value) uni.getFileSystemManager().unlink({ filePath: previewImage.value })
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

const dynamicFields = [
    { label: '服务日期', token: '{service_date_label}', example: '2026年08月18日' },
    { label: '客户称呼', token: '{customer_alias}', example: '张姓新人' },
    { label: '服务类型', token: '{service_name}', example: '婚礼跟拍' },
    { label: '服务城市', token: '{city_label}', example: '杭州 西湖区' },
    { label: '服务人员', token: '{staff_name}', example: '服务人员姓名' }
]

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
        const dataUrl = String(data?.preview?.image_data_url || '')
        if (!dataUrl.startsWith('data:image/jpeg;base64,')) throw new Error('海报预览生成失败')
        const path = `${wx.env.USER_DATA_PATH}/poster-preview-${Date.now()}.jpg`
        const fs = uni.getFileSystemManager()
        await new Promise<void>((resolve, reject) => fs.writeFile({
            filePath: path, data: dataUrl.slice('data:image/jpeg;base64,'.length),
            encoding: 'base64', success: () => resolve(), fail: reject
        }))
        if (previewImage.value) fs.unlink({ filePath: previewImage.value })
        previewImage.value = path
        if (data?.config?.design_config) {
            designConfig.value = data.config.design_config
        }
    } catch (error: any) {
        showError(error, '海报预览失败')
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
    padding-bottom: 160rpx;
}

.letter-section {
    display: flex;
    flex-direction: column;
    gap: 24rpx;
}

.section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;

    &__left {
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__title {
        font-size: 30rpx;
        font-weight: 800;
        color: var(--wm-text-primary, #191713);
        letter-spacing: 0.5rpx;

        &--gold {
            color: #E8D3A7;
        }
    }

    &__subtitle {
        font-size: 22rpx;
        color: var(--wm-text-tertiary, #9C9487);

        &--muted {
            color: rgba(232, 211, 167, 0.65);
        }
    }
}

/* 模板横滑选择条 */
.template-scroll {
    width: 100%;
    white-space: nowrap;
}

.template-list {
    display: inline-flex;
    gap: 16rpx;
    padding: 4rpx 4rpx 10rpx;
}

.template-chip {
    width: 270rpx;
    min-height: 120rpx;
    padding: 22rpx 24rpx;
    border-radius: 20rpx;
    border: 1.5rpx solid rgba(217, 190, 130, 0.28);
    background: #FFFFFF;
    box-sizing: border-box;
    display: inline-flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 12rpx;
    transition: all 0.2s ease;
    box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.03);

    &--active {
        border-color: #D9BE82;
        background: linear-gradient(145deg, #24201A 0%, #161513 100%);
        box-shadow: 0 8rpx 24rpx rgba(33, 26, 17, 0.22);

        .template-chip__name {
            color: #FAF6EF;
        }

        .template-chip__meta {
            color: #C8A45D;
        }

        .template-chip__status-dot {
            background: #D9BE82;
            box-shadow: 0 0 10rpx rgba(217, 190, 130, 0.8);
        }
    }

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8rpx;
    }

    &__name {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 27rpx;
        font-weight: 700;
        color: #191713;
    }

    &__default-tag {
        font-size: 19rpx;
        padding: 2rpx 10rpx;
        border-radius: 8rpx;
        background: rgba(200, 164, 93, 0.16);
        color: #B2883A;
        font-weight: 700;
        flex-shrink: 0;
    }

    &__bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__meta {
        font-size: 21rpx;
        color: #8C8273;
        font-weight: 500;
    }

    &__status-dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: 50%;
        background: rgba(140, 130, 115, 0.3);
    }
}

/* 刷新胶囊 */
.refresh-pill {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    padding: 10rpx 20rpx;
    border-radius: 999rpx;
    background: rgba(217, 190, 130, 0.15);
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    &__icon {
        font-size: 26rpx;
        color: #E8D3A7;
        line-height: 1;
    }

    &__text {
        font-size: 22rpx;
        font-weight: 600;
        color: #E8D3A7;
    }
}

/* 海报渲染舞台 */
.poster-stage {
    margin-top: 8rpx;
    display: flex;
    justify-content: center;
    background: rgba(14, 13, 11, 0.6);
    border-radius: 24rpx;
    padding: 24rpx;
    border: 1rpx solid rgba(217, 190, 130, 0.2);
}

.poster-frame {
    position: relative;
    max-width: 520rpx;
    width: 100%;
    border-radius: 16rpx;
    overflow: hidden;
    box-shadow: 0 20rpx 50rpx rgba(0, 0, 0, 0.45);
    border: 2rpx solid rgba(217, 190, 130, 0.4);

    &__img {
        display: block;
        width: 100%;
    }

    &__overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 20rpx 16rpx;
        background: linear-gradient(180deg, transparent 0%, rgba(0, 0, 0, 0.75) 100%);
        display: flex;
        justify-content: center;
    }

    &__badge {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        padding: 8rpx 20rpx;
        border-radius: 999rpx;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        border: 1rpx solid rgba(255, 255, 255, 0.35);
    }

    &__badge-icon {
        font-size: 20rpx;
    }

    &__badge-text {
        font-size: 21rpx;
        color: #FFFFFF;
        font-weight: 600;
    }
}

.poster-empty {
    width: 100%;
    min-height: 420rpx;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16rpx;
    padding: 40rpx;
    text-align: center;

    &__icon-box {
        width: 90rpx;
        height: 90rpx;
        border-radius: 50%;
        background: rgba(217, 190, 130, 0.12);
        border: 1rpx solid rgba(217, 190, 130, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__glyph {
        font-size: 38rpx;
        color: #D9BE82;
    }

    &__title {
        font-size: 29rpx;
        font-weight: 700;
        color: #FAF6EF;
    }

    &__desc {
        font-size: 22rpx;
        color: rgba(250, 246, 239, 0.55);
        max-width: 440rpx;
        line-height: 1.5;
    }
}

/* 输入表单组 */
.form-inputs-group {
    display: flex;
    flex-direction: column;
    gap: 22rpx;
}

.textarea-field {
    display: flex;
    flex-direction: column;
    gap: 14rpx;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__label {
        font-size: 25rpx;
        font-weight: 700;
        color: var(--wm-text-primary, #191713);
    }

    &__count {
        font-size: 21rpx;
        color: #9C9487;
    }
}

/* 动态字段辅助板 */
.dynamic-var-box {
    padding: 18rpx 20rpx;
    border-radius: 18rpx;
    background: #F8F5EE;
    border: 1rpx solid rgba(217, 190, 130, 0.35);

    &--compact {
        padding: 14rpx 16rpx;
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14rpx;
    }

    &__title {
        font-size: 22rpx;
        font-weight: 700;
        color: #786236;
    }

    &__tip {
        font-size: 20rpx;
        color: #9C907A;
    }
}

.dynamic-var-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.dynamic-var-tag {
    flex: 1 1 calc(50% - 12rpx);
    min-width: 220rpx;
    box-sizing: border-box;
    padding: 14rpx 16rpx;
    border-radius: 14rpx;
    background: #FFFFFF;
    border: 1rpx solid rgba(200, 164, 93, 0.3);
    display: flex;
    flex-direction: column;
    gap: 4rpx;
    transition: transform 0.15s ease;

    &:active {
        transform: scale(0.98);
        border-color: #C8A45D;
        background: #FFFDF8;
    }

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__label {
        font-size: 23rpx;
        font-weight: 700;
        color: #191713;
    }

    &__plus {
        font-size: 24rpx;
        font-weight: 700;
        color: #C8A45D;
    }

    &__example {
        font-size: 19rpx;
        color: #9A8A70;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.letter-textarea {
    box-sizing: border-box;
    width: 100%;
    min-height: 180rpx;
    padding: 22rpx 24rpx;
    border-radius: 18rpx;
    background: #FAF8F5;
    border: 1rpx solid rgba(217, 190, 130, 0.3);
    color: #191713;
    font-size: 26rpx;
    line-height: 1.6;
}

/* 背景配置组 */
.bg-config-group {
    display: flex;
    flex-direction: column;
    gap: 20rpx;
}

.segment-pill-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14rpx;
}

.segment-pill {
    height: 76rpx;
    border-radius: 18rpx;
    background: #F7F5EE;
    border: 1rpx solid rgba(217, 190, 130, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24rpx;
    font-weight: 600;
    color: #6E6556;
    transition: all 0.2s ease;

    &--active {
        background: #181614;
        border-color: #D9BE82;
        color: #FAF6EF;
        font-weight: 700;
        box-shadow: 0 4rpx 14rpx rgba(24, 22, 20, 0.2);
    }
}

.upload-studio-card {
    height: 240rpx;
    border-radius: 20rpx;
    border: 2rpx dashed rgba(200, 164, 93, 0.5);
    background: #FAF7F0;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;

    &__img {
        width: 100%;
        height: 100%;
    }

    &__placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8rpx;
    }

    &__icon {
        font-size: 40rpx;
    }

    &__text {
        font-size: 25rpx;
        font-weight: 700;
        color: #9A7E46;
    }

    &__hint {
        font-size: 20rpx;
        color: #A89B84;
    }
}

.letter-action-bar {
    width: 100%;
}
</style>
