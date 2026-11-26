<template>
    <div class="order-confirm-letter-setting">
        <el-card shadow="never" class="!border-none">
            <div class="setting-head">
                <div class="setting-head__main">
                    <div class="setting-title">海报字体资源</div>
                </div>
                <div class="setting-actions">
                    <el-upload
                        :action="fontUploadUrl"
                        :headers="uploadHeaders"
                        :show-file-list="false"
                        accept=".ttf,.otf"
                        :before-upload="beforeFontUpload"
                        :on-success="handleFontUploadSuccess"
                        :on-error="handleFontUploadError"
                    >
                        <el-button
                            v-perms="['setting.order_confirm_letter/uploadFont']"
                            type="primary"
                            :loading="uploading"
                        >
                            上传字体
                        </el-button>
                    </el-upload>
                    <el-button :loading="loading" @click="refreshFonts">刷新</el-button>
                    <el-button :loading="checking" @click="handleCheckFont">检测</el-button>
                </div>
            </div>

            <div v-if="!props.embedded" class="setting-migrate">
                <el-icon class="setting-migrate__icon"><InfoFilled /></el-icon>
                <span class="setting-migrate__text">
                    海报二维码与字体资源已迁移至「品牌与站点 › 海报资源」
                </span>
                <el-button type="primary" link @click="goPosterAssets">前往品牌与站点</el-button>
            </div>

            <div class="font-select-grid">
                <div class="font-select-card">
                    <div class="font-select-card__head">
                        <span class="font-select-card__title">正文字体</span>
                        <el-tag size="small" effect="plain" type="primary">用于正文 / 日期 / 城市</el-tag>
                    </div>
                    <el-select
                        v-model="fontConfig.sans_file"
                        class="w-full"
                        filterable
                        placeholder="选择正文字体"
                    >
                        <el-option
                            v-for="item in fontList"
                            :key="item.file"
                            :label="`${item.name}${item.is_builtin ? '（内置）' : ''}`"
                            :value="item.file"
                        />
                    </el-select>
                </div>
                <div class="font-select-card">
                    <div class="font-select-card__head">
                        <span class="font-select-card__title">衬线字体</span>
                        <el-tag size="small" effect="plain" type="warning">用于标题 / 仪式主视觉</el-tag>
                    </div>
                    <el-select
                        v-model="fontConfig.serif_file"
                        class="w-full"
                        filterable
                        placeholder="选择衬线字体"
                    >
                        <el-option
                            v-for="item in fontList"
                            :key="item.file"
                            :label="`${item.name}${item.is_builtin ? '（内置）' : ''}`"
                            :value="item.file"
                        />
                    </el-select>
                </div>
            </div>

            <div class="flex justify-end mt-4">
                <el-button
                    v-perms="['setting.order_confirm_letter/setFont']"
                    type="primary"
                    :loading="saving"
                    @click="handleSaveFont"
                >
                    保存字体设置
                </el-button>
            </div>

            <el-table v-loading="loading" :data="fontList" border size="small" class="mt-5">
                <el-table-column label="字体文件" min-width="220">
                    <template #default="{ row }">
                        <div class="cell-main">{{ row.name }}</div>
                        <div class="cell-sub">{{ row.file }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="来源" width="90">
                    <template #default="{ row }">
                        <el-tag :type="row.is_builtin ? 'info' : 'success'" size="small" effect="plain">
                            {{ row.is_builtin ? '内置' : '上传' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="size_text" label="文件大小" width="110" />
                <el-table-column label="当前用途" width="140">
                    <template #default="{ row }">
                        <div class="tag-row">
                            <el-tag v-if="row.is_current_sans" type="primary" size="small">正文</el-tag>
                            <el-tag v-if="row.is_current_serif" type="warning" size="small">衬线</el-tag>
                            <span v-if="!row.is_current_sans && !row.is_current_serif" class="cell-sub">未使用</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="可读性" width="100">
                    <template #default="{ row }">
                        <el-tag :type="row.readable ? 'success' : 'danger'" size="small" effect="plain">
                            {{ row.readable ? '可读' : '不可读' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="ImageMagick" width="120">
                    <template #default="{ row }">
                        <el-tag :type="row.imagick_query_matched ? 'success' : 'info'" size="small" effect="plain">
                            {{ row.imagick_query_matched ? '已识别' : '未识别' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="100" fixed="right">
                    <template #default="{ row }">
                        <el-button
                            v-perms="['setting.order_confirm_letter/deleteFont']"
                            type="danger"
                            link
                            :disabled="row.is_builtin || row.is_current_sans || row.is_current_serif"
                            @click="handleDeleteFont(row)"
                        >
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <div class="diagnostics">
                <div class="diagnostics__title">环境诊断</div>
                <div class="diagnostics__grid">
                    <div class="diagnostics-card">
                        <div class="diagnostics-card__label">Imagick 扩展</div>
                        <el-tag :type="fontDiagnostics?.imagick_loaded ? 'success' : 'danger'" size="small" effect="plain">
                            {{ fontDiagnostics?.imagick_loaded ? '已启用' : '未启用' }}
                        </el-tag>
                        <div
                            v-if="fontDiagnostics && !fontDiagnostics.imagick_loaded"
                            class="diagnostics-card__hint is-danger"
                        >
                            请安装并启用 PHP Imagick 扩展，并重启 PHP-FPM 后重新检测。
                        </div>
                    </div>
                    <div class="diagnostics-card">
                        <div class="diagnostics-card__label">字体查询接口</div>
                        <el-tag
                            :type="fontDiagnostics?.query_fonts_available ? 'success' : 'warning'"
                            size="small"
                            effect="plain"
                        >
                            {{ fontDiagnostics?.query_fonts_available ? '可用' : '不可用' }}
                        </el-tag>
                        <div
                            v-if="fontDiagnostics && !fontDiagnostics.query_fonts_available"
                            class="diagnostics-card__hint is-danger"
                        >
                            Imagick 未提供 queryFonts 能力，请升级 ImageMagick 或改用字体文件直读。
                        </div>
                    </div>
                    <div class="diagnostics-card">
                        <div class="diagnostics-card__label">正文字体</div>
                        <el-tag :type="fontDiagnostics?.sans?.readable ? 'success' : 'danger'" size="small" effect="plain">
                            {{ fontDiagnostics?.sans?.readable ? '可读' : '不可读' }}
                        </el-tag>
                        <div class="diagnostics-card__value">{{ fontDiagnostics?.sans?.file || '未选择' }}</div>
                        <div
                            v-if="fontDiagnostics && !fontDiagnostics.sans?.readable"
                            class="diagnostics-card__hint is-danger"
                        >
                            字体文件不存在或无读取权限，请重新上传或在字体清单中删除。
                        </div>
                    </div>
                    <div class="diagnostics-card">
                        <div class="diagnostics-card__label">衬线字体</div>
                        <el-tag :type="fontDiagnostics?.serif?.readable ? 'success' : 'danger'" size="small" effect="plain">
                            {{ fontDiagnostics?.serif?.readable ? '可读' : '不可读' }}
                        </el-tag>
                        <div class="diagnostics-card__value">{{ fontDiagnostics?.serif?.file || '未选择' }}</div>
                        <div
                            v-if="fontDiagnostics && !fontDiagnostics.serif?.readable"
                            class="diagnostics-card__hint is-danger"
                        >
                            字体文件不存在或无读取权限，请重新上传或在字体清单中删除。
                        </div>
                    </div>
                    <div class="diagnostics-card diagnostics-card--wide">
                        <div class="diagnostics-card__label">中文渲染检测</div>
                        <el-tag :type="fontDiagnostics?.render_test?.ok ? 'success' : 'danger'" size="small" effect="plain">
                            {{ fontDiagnostics?.render_test?.ok ? '通过' : '未通过' }}
                        </el-tag>
                        <div class="diagnostics-card__value">
                            {{ fontDiagnostics?.render_test?.message || '尚未检测' }}
                        </div>
                        <div
                            v-if="fontDiagnostics && !fontDiagnostics.render_test?.ok"
                            class="diagnostics-card__hint is-danger"
                        >
                            请检查 Imagick 策略是否禁用字体、字体文件是否包含中文字形，并确认字体目录可读。
                        </div>
                    </div>
                </div>
            </div>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="orderConfirmLetterSetting">
/**
 * 海报字体资源设置。
 * embedded=true 时作为海报资源聚合 Tab 的下半屏内嵌使用：隐藏迁移提示条，其余功能不变。
 * 数据变动通过 diagnostics-change 向上冒泡，供顶部摘要卡聚合渲染链路状态。
 */
import { InfoFilled } from '@element-plus/icons-vue'
import { ElMessageBox } from 'element-plus'
import { useRouter } from 'vue-router'

import {
    checkOrderConfirmLetterFont,
    deleteOrderConfirmLetterFont,
    getOrderConfirmLetterFonts,
    setOrderConfirmLetterFont
} from '@/api/setting/orderConfirmLetter'
import config from '@/config'
import { RequestCodeEnum } from '@/enums/requestEnums'
import { getMenuPath } from '@/router'
import useUserStore from '@/stores/modules/user'
import feedback from '@/utils/feedback'

const props = defineProps<{ embedded?: boolean }>()

const emit = defineEmits<{
    (e: 'diagnostics-change', payload: any): void
}>()

const router = useRouter()

const goPosterAssets = () => {
    router.push({
        path: getMenuPath('page.setting.website.hub') || '/setting/brand-site',
        query: { tab: 'poster' }
    })
}

const userStore = useUserStore()

const fontConfig = reactive({
    sans_file: '',
    serif_file: ''
})
const fontList = ref<any[]>([])
const fontDiagnostics = ref<any>(null)
const loading = ref(false)
const saving = ref(false)
const checking = ref(false)
const uploading = ref(false)

const fontUploadUrl = computed(
    () => `${config.baseUrl}${config.urlPrefix}/setting.order_confirm_letter/uploadFont`
)
const uploadHeaders = computed(() => ({
    token: userStore.token,
    version: config.version
}))

const notifyDiagnosticsChange = () => {
    emit('diagnostics-change', {
        config: { ...fontConfig },
        diagnostics: fontDiagnostics.value
    })
}

const refreshFonts = async () => {
    loading.value = true
    try {
        const data = await getOrderConfirmLetterFonts()
        fontList.value = data?.fonts || []
        Object.assign(fontConfig, data?.config || {})
        fontDiagnostics.value = data?.diagnostics || null
        notifyDiagnosticsChange()
    } catch {
        // 业务错误已由 request 层统一提示，保留上一次成功数据
    } finally {
        loading.value = false
    }
}

const handleSaveFont = async () => {
    saving.value = true
    try {
        await setOrderConfirmLetterFont({
            sans_file: fontConfig.sans_file,
            serif_file: fontConfig.serif_file
        })
        feedback.msgSuccess('字体配置已保存')
        await refreshFonts()
    } finally {
        saving.value = false
    }
}

const handleCheckFont = async () => {
    checking.value = true
    try {
        fontDiagnostics.value = await checkOrderConfirmLetterFont()
        notifyDiagnosticsChange()
    } finally {
        checking.value = false
    }
}

const beforeFontUpload = (file: File) => {
    const ext = file.name.split('.').pop()?.toLowerCase()
    if (!['ttf', 'otf'].includes(ext || '')) {
        feedback.msgError('仅支持上传 ttf、otf 字体文件')
        return false
    }
    if (file.size > 30 * 1024 * 1024) {
        feedback.msgError('字体文件大小不能超过30MB')
        return false
    }
    uploading.value = true
    return true
}

const handleFontUploadSuccess = (response: any) => {
    uploading.value = false
    if (response.code === RequestCodeEnum.SUCCESS) {
        feedback.msgSuccess(response.msg || '上传成功')
        refreshFonts()
    } else {
        feedback.msgError(response.msg || '上传失败')
    }
}

const handleFontUploadError = () => {
    uploading.value = false
    feedback.msgError('字体文件上传失败')
}

const handleDeleteFont = async (row: any) => {
    try {
        await ElMessageBox.confirm(`确认删除字体“${row.name}”？`, '删除字体', {
            type: 'warning'
        })
    } catch {
        return
    }
    await deleteOrderConfirmLetterFont({ file: row.file })
    feedback.msgSuccess('字体已删除')
    refreshFonts()
}

refreshFonts()
</script>

<style lang="scss" scoped>
.order-confirm-letter-setting {
    .setting-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }

    .setting-head__main {
        min-width: 0;
        flex: 1;
    }

    .setting-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    .setting-migrate {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px 8px;
        margin-bottom: 16px;
        padding: 0 2px;
        font-size: 13px;
        line-height: 1.6;
        color: var(--el-text-color-secondary);
    }

    .setting-migrate__icon {
        font-size: 14px;
        color: var(--el-color-primary);
    }

    .setting-migrate__text {
        min-width: 0;
    }

    .setting-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    .font-select-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .font-select-card {
        padding: 14px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-fill-color-lighter);
    }

    .font-select-card__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }

    .font-select-card__title {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    .cell-main {
        font-size: 13px;
        font-weight: 500;
        line-height: 1.5;
        color: var(--el-text-color-primary);
    }

    .cell-sub {
        margin-top: 2px;
        font-size: 12px;
        line-height: 1.5;
        color: var(--el-text-color-secondary);
        word-break: break-all;
    }

    .tag-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .diagnostics {
        margin-top: 20px;
    }

    .diagnostics__title {
        font-size: 14px;
        font-weight: 600;
        color: var(--el-text-color-primary);
        margin-bottom: 12px;
    }

    .diagnostics__grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .diagnostics-card {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        padding: 14px;
        border: 1px solid var(--el-border-color-light);
        border-radius: 8px;
        background: var(--el-bg-color);

        &--wide {
            grid-column: span 4;
        }
    }

    .diagnostics-card__label {
        font-size: 13px;
        font-weight: 600;
        color: var(--el-text-color-primary);
    }

    .diagnostics-card__value {
        font-size: 12px;
        line-height: 1.5;
        color: var(--el-text-color-regular);
        word-break: break-all;
    }

    .diagnostics-card__hint {
        font-size: 12px;
        line-height: 1.6;
        color: var(--el-text-color-secondary);

        &.is-danger {
            color: var(--el-color-danger);
        }
    }
}

@media (max-width: 1200px) {
    .order-confirm-letter-setting {
        .diagnostics__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .diagnostics-card--wide {
            grid-column: span 2;
        }
    }
}

@media (max-width: 768px) {
    .order-confirm-letter-setting {
        .font-select-grid,
        .diagnostics__grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .diagnostics-card--wide {
            grid-column: span 1;
        }
    }
}
</style>
