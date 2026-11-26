<template>
    <div class="poster-assets-tab">
        <div class="poster-summary">
            <div class="poster-summary__card">
                <div class="poster-summary__label">统一二维码</div>
                <div class="poster-summary__value">
                    <el-tag :type="qrcodeImage ? 'success' : 'warning'" size="small" effect="plain">
                        {{ qrcodeImage ? '已配置' : '未配置' }}
                    </el-tag>
                </div>
                <div v-if="!qrcodeImage" class="poster-summary__hint is-danger">
                    未配置时海报底部二维码区域为空白
                </div>
            </div>
            <div class="poster-summary__card">
                <div class="poster-summary__label">渲染字体组合</div>
                <div class="poster-summary__value poster-summary__value--text">
                    {{ fontInfo.sansFile || '未加载' }}
                </div>
                <div class="poster-summary__hint">
                    衬线 {{ fontInfo.serifFile || '未加载' }}
                </div>
            </div>
            <div class="poster-summary__card">
                <div class="poster-summary__label">服务端出图检测</div>
                <div class="poster-summary__value">
                    <el-tag :type="renderHealthy ? 'success' : 'danger'" size="small" effect="plain">
                        {{ renderHealthy ? '正常' : '待处理' }}
                    </el-tag>
                </div>
                <div v-if="!renderHealthy" class="poster-summary__hint is-danger">
                    {{ renderHint }}
                </div>
            </div>
        </div>

        <el-card shadow="never" class="!border-none mt-4">
            <div class="poster-qrcode-head">
                <div>
                    <div class="poster-qrcode-title">海报统一二维码</div>
                </div>
            </div>

            <div class="poster-qrcode-grid">
                <div class="poster-qrcode-form">
                    <el-form label-width="100px">
                        <el-form-item label="统一二维码">
                            <div class="w-full">
                                <material-picker v-model="qrcodeImage" :limit="1" />
                                <div class="form-tips">
                                    建议正方形 ≥300×300px，jpg/png 格式，与月报及确认函共用
                                </div>
                            </div>
                        </el-form-item>
                        <el-form-item>
                            <el-button
                                v-perms="[
                                    'setting.order_confirm_letter/setConfig',
                                    'setting.web.web_setting/setWebsite'
                                ]"
                                type="primary"
                                :loading="saving"
                                @click="handleSaveQrcode"
                            >
                                保存
                            </el-button>
                        </el-form-item>
                    </el-form>
                </div>

                <div class="poster-qrcode-preview">
                    <div class="poster-qrcode-preview__title">海报底部二维码样式预览</div>
                    <div class="poster-qrcode-preview__poster">
                        <div class="poster-qrcode-card">
                            <div class="poster-qrcode-card__image">
                                <el-image
                                    v-if="qrcodeImage"
                                    :src="qrcodeImage"
                                    fit="contain"
                                    class="poster-qrcode-card__img"
                                />
                                <div v-else class="poster-qrcode-card__placeholder">
                                    <icon name="el-icon-Picture" :size="22" />
                                    <span>待上传二维码</span>
                                </div>
                            </div>
                            <div class="poster-qrcode-card__text">
                                <div class="poster-qrcode-card__title">扫码查看服务档期</div>
                                <div class="poster-qrcode-card__desc">长按识别 · 了解专属服务与档期安排</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </el-card>

        <OrderConfirmLetterSetting embedded class="mt-4" @diagnostics-change="handleDiagnosticsChange" />
    </div>
</template>

<script lang="ts" setup name="posterAssetsTab">
/**
 * 海报资源聚合 Tab：顶部聚合运行状态摘要，中部维护海报统一二维码，
 * 下半屏内嵌海报字体资源设置（单量月报、档期确认函共用）。
 */
import { getOrderConfirmLetterConfig, setOrderConfirmLetterConfig } from '@/api/setting/orderConfirmLetter'
import OrderConfirmLetterSetting from '@/views/setting/order_confirm_letter/index.vue'
import feedback from '@/utils/feedback'

const qrcodeImage = ref('')
const saving = ref(false)
const fontInfo = reactive({
    sansFile: '',
    serifFile: '',
    imagickLoaded: 0,
    fontReady: 0,
    fontMessage: ''
})

const renderHealthy = computed(() => !!fontInfo.imagickLoaded && !!fontInfo.fontReady)

const renderHint = computed(() => {
    if (!fontInfo.imagickLoaded && !fontInfo.fontReady) {
        return 'Imagick 扩展未启用，且中文渲染检测未通过'
    }
    if (!fontInfo.imagickLoaded) {
        return 'Imagick 扩展未启用，服务端无法出图'
    }
    if (!fontInfo.fontReady) {
        return fontInfo.fontMessage || '中文渲染检测未通过，请检查字体文件'
    }
    return 'Imagick 与中文字体渲染均正常'
})

const getData = async () => {
    try {
        const data: any = await getOrderConfirmLetterConfig()
        qrcodeImage.value = data?.schedule_qrcode_image_url || data?.schedule_qrcode_image || ''
    } catch {
        qrcodeImage.value = ''
    }
}

const handleSaveQrcode = async () => {
    if (!qrcodeImage.value) {
        feedback.msgError('请先上传海报统一二维码')
        return
    }
    saving.value = true
    try {
        await setOrderConfirmLetterConfig({ schedule_qrcode_image: qrcodeImage.value })
        feedback.msgSuccess('海报统一二维码已保存')
        await getData()
    } finally {
        saving.value = false
    }
}

const handleDiagnosticsChange = (payload: any) => {
    const config = payload?.config || {}
    const diagnostics = payload?.diagnostics || {}
    fontInfo.sansFile = config.sans_file || diagnostics?.sans?.file || ''
    fontInfo.serifFile = config.serif_file || diagnostics?.serif?.file || ''
    fontInfo.imagickLoaded = diagnostics?.imagick_loaded ? 1 : 0
    fontInfo.fontReady = diagnostics?.render_test?.ok ? 1 : 0
    fontInfo.fontMessage = String(diagnostics?.render_test?.message || '')
}

getData()
</script>

<style lang="scss" scoped>
.poster-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;

    &__card {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 14px 16px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-bg-color);
    }

    &__label {
        font-size: 12px;
        color: var(--el-text-color-secondary);
    }

    &__value {
        display: flex;
        align-items: center;
        min-height: 24px;
        font-size: 18px;
        font-weight: 600;
        line-height: 1.2;
        color: var(--el-text-color-primary);
        font-variant-numeric: tabular-nums;

        &--text {
            font-size: 14px;
            word-break: break-all;
        }
    }

    &__hint {
        font-size: 12px;
        line-height: 1.5;
        color: var(--el-text-color-secondary);
        word-break: break-all;

        &.is-danger {
            color: var(--el-color-danger);
        }
    }
}

.poster-qrcode-head {
    margin-bottom: 18px;
}

.poster-qrcode-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--el-text-color-primary);
}

.poster-qrcode-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
    gap: 20px;
    align-items: start;
}

.poster-qrcode-form {
    min-width: 0;
}

.poster-qrcode-preview {
    min-width: 0;
    padding: 16px;
    border: 1px dashed var(--el-border-color);
    border-radius: 8px;
    background: var(--el-fill-color-lighter);

    &__title {
        margin-bottom: 12px;
        font-size: 13px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__poster {
        padding: 18px;
        border-radius: 10px;
        background: linear-gradient(180deg, #221d17 0%, #191713 100%);
    }
}

.poster-qrcode-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px;
    border-radius: 12px;
    background: var(--el-bg-color);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);

    &__image {
        flex: none;
        width: 84px;
        height: 84px;
        display: grid;
        place-items: center;
        border-radius: 8px;
        background: var(--el-fill-color-lighter);
        overflow: hidden;
    }

    &__img {
        width: 100%;
        height: 100%;
    }

    &__placeholder {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        line-height: 1.4;
        color: var(--el-text-color-secondary);
        text-align: center;
    }

    &__text {
        min-width: 0;
    }

    &__title {
        font-size: 13px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    &__desc {
        margin-top: 4px;
        font-size: 11px;
        line-height: 1.6;
        color: var(--el-text-color-secondary);
    }
}

.form-tips {
    margin-top: 6px;
    font-size: 12px;
    line-height: 1.6;
    color: var(--el-text-color-secondary);
}

@media (max-width: 1200px) {
    .poster-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .poster-qrcode-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (max-width: 768px) {
    .poster-summary {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
