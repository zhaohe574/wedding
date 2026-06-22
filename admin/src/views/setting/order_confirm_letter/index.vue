<template>
    <div class="order-confirm-letter-setting">
        <el-card shadow="never" class="!border-none">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="font-medium">确认函资源设置</div>
                    <div class="text-xs text-gray-500 mt-1">
                        这里维护后端生成确认函图片所需字体资源和档期确认海报统一二维码，业务文案和背景由每个服务人员在服务人员中心单独配置。
                    </div>
                </div>
                <div class="flex gap-2">
                    <el-upload
                        :action="fontUploadUrl"
                        :headers="uploadHeaders"
                        :show-file-list="false"
                        accept=".ttf,.otf"
                        :before-upload="beforeFontUpload"
                        :on-success="handleFontUploadSuccess"
                        :on-error="handleFontUploadError"
                    >
                        <el-button type="primary">上传字体</el-button>
                    </el-upload>
                    <el-button @click="refreshFonts">刷新</el-button>
                    <el-button @click="handleCheckFont">检测</el-button>
                </div>
            </div>

            <el-alert
                class="mb-5"
                type="warning"
                show-icon
                :closable="false"
                title="档期确认海报二维码由系统统一管理，服务人员只能调整模板中的二维码位置和尺寸。"
            />

            <el-form label-width="120px" class="resource-form">
                <el-form-item label="统一二维码" required>
                    <div class="qrcode-setting">
                        <material-picker v-model="resourceConfig.schedule_qrcode_image" :limit="1" />
                        <el-image
                            v-if="scheduleQrcodePreview"
                            class="qrcode-setting__preview"
                            :src="scheduleQrcodePreview"
                            fit="contain"
                            :preview-src-list="[scheduleQrcodePreview]"
                        />
                        <div v-else class="qrcode-setting__empty">未上传</div>
                    </div>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSaveResource">保存二维码设置</el-button>
                </el-form-item>
            </el-form>

            <el-divider />

            <el-form label-width="120px">
                <el-form-item label="正文字体">
                    <el-select v-model="fontConfig.sans_file" class="w-[420px]">
                        <el-option
                            v-for="item in fontList"
                            :key="item.file"
                            :label="`${item.name}${item.is_builtin ? '（内置）' : ''}`"
                            :value="item.file"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item label="衬线字体">
                    <el-select v-model="fontConfig.serif_file" class="w-[420px]">
                        <el-option
                            v-for="item in fontList"
                            :key="item.file"
                            :label="`${item.name}${item.is_builtin ? '（内置）' : ''}`"
                            :value="item.file"
                        />
                    </el-select>
                </el-form-item>
                <el-form-item>
                    <el-button type="primary" @click="handleSaveFont">保存字体设置</el-button>
                </el-form-item>
            </el-form>

            <el-table :data="fontList" border size="small" class="mt-4">
                <el-table-column label="字体文件" min-width="220">
                    <template #default="{ row }">
                        <div class="font-medium">{{ row.name }}</div>
                        <div class="text-xs text-gray-400">{{ row.file }}</div>
                    </template>
                </el-table-column>
                <el-table-column label="类型" width="90">
                    <template #default="{ row }">
                        <el-tag :type="row.is_builtin ? 'info' : 'success'" size="small">
                            {{ row.is_builtin ? '内置' : '上传' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="size_text" label="大小" width="110" />
                <el-table-column label="状态" width="180">
                    <template #default="{ row }">
                        <div class="flex flex-wrap gap-1">
                            <el-tag v-if="row.is_current_sans" type="primary" size="small">正文</el-tag>
                            <el-tag v-if="row.is_current_serif" type="warning" size="small">衬线</el-tag>
                            <el-tag :type="row.readable ? 'success' : 'danger'" size="small">
                                {{ row.readable ? '可读' : '不可读' }}
                            </el-tag>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column label="ImageMagick" width="130">
                    <template #default="{ row }">
                        <el-tag :type="row.imagick_query_matched ? 'success' : 'info'" size="small">
                            {{ row.imagick_query_matched ? '已识别' : '未识别' }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button
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

            <el-descriptions v-if="fontDiagnostics" title="字体检测" :column="2" border class="mt-4">
                <el-descriptions-item label="Imagick">
                    <el-tag :type="fontDiagnostics.imagick_loaded ? 'success' : 'danger'">
                        {{ fontDiagnostics.imagick_loaded ? '已启用' : '未启用' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="字体列表查询">
                    <el-tag :type="fontDiagnostics.query_fonts_available ? 'success' : 'info'">
                        {{ fontDiagnostics.query_fonts_available ? '可用' : '不可用' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="正文字体">
                    {{ fontDiagnostics.sans?.file || '-' }}
                    <el-tag class="ml-2" :type="fontDiagnostics.sans?.readable ? 'success' : 'danger'" size="small">
                        {{ fontDiagnostics.sans?.readable ? '可读' : '不可读' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="衬线字体">
                    {{ fontDiagnostics.serif?.file || '-' }}
                    <el-tag class="ml-2" :type="fontDiagnostics.serif?.readable ? 'success' : 'danger'" size="small">
                        {{ fontDiagnostics.serif?.readable ? '可读' : '不可读' }}
                    </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="中文渲染检测" :span="2">
                    <el-tag :type="fontDiagnostics.render_test?.ok ? 'success' : 'danger'">
                        {{ fontDiagnostics.render_test?.message || '-' }}
                    </el-tag>
                </el-descriptions-item>
            </el-descriptions>
        </el-card>
    </div>
</template>

<script lang="ts" setup>
import { ElMessageBox } from 'element-plus'
import config from '@/config'
import {
    getOrderConfirmLetterConfig,
    checkOrderConfirmLetterFont,
    deleteOrderConfirmLetterFont,
    getOrderConfirmLetterFonts,
    setOrderConfirmLetterConfig,
    setOrderConfirmLetterFont
} from '@/api/setting/orderConfirmLetter'
import useUserStore from '@/stores/modules/user'
import feedback from '@/utils/feedback'
import { RequestCodeEnum } from '@/enums/requestEnums'

const userStore = useUserStore()

const fontConfig = reactive({
    sans_file: '',
    serif_file: ''
})
const resourceConfig = reactive({
    schedule_qrcode_image: '',
    schedule_qrcode_image_url: ''
})
const fontList = ref<any[]>([])
const fontDiagnostics = ref<any>(null)

const fontUploadUrl = computed(
    () => `${config.baseUrl}${config.urlPrefix}/setting.order_confirm_letter/uploadFont`
)
const uploadHeaders = computed(() => ({
    token: userStore.token,
    version: config.version
}))

const refreshFonts = async () => {
    const data = await getOrderConfirmLetterFonts()
    fontList.value = data?.fonts || []
    Object.assign(fontConfig, data?.config || {})
    fontDiagnostics.value = data?.diagnostics || null
}

const scheduleQrcodePreview = computed(() => {
    return resourceConfig.schedule_qrcode_image_url || resourceConfig.schedule_qrcode_image || ''
})

const refreshConfig = async () => {
    const data = await getOrderConfirmLetterConfig()
    resourceConfig.schedule_qrcode_image = data?.schedule_qrcode_image || ''
    resourceConfig.schedule_qrcode_image_url = data?.schedule_qrcode_image_url || resourceConfig.schedule_qrcode_image
}

const handleSaveResource = async () => {
    if (!resourceConfig.schedule_qrcode_image) {
        feedback.msgError('请上传档期确认海报统一二维码')
        return
    }
    const data = await setOrderConfirmLetterConfig({
        schedule_qrcode_image: resourceConfig.schedule_qrcode_image
    })
    resourceConfig.schedule_qrcode_image = data?.schedule_qrcode_image || resourceConfig.schedule_qrcode_image
    resourceConfig.schedule_qrcode_image_url = data?.schedule_qrcode_image_url || resourceConfig.schedule_qrcode_image
    feedback.msgSuccess('二维码设置已保存')
}

const handleSaveFont = async () => {
    await setOrderConfirmLetterFont({
        sans_file: fontConfig.sans_file,
        serif_file: fontConfig.serif_file
    })
    refreshFonts()
}

const handleCheckFont = async () => {
    fontDiagnostics.value = await checkOrderConfirmLetterFont()
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
    return true
}

const handleFontUploadSuccess = (response: any) => {
    if (response.code === RequestCodeEnum.SUCCESS) {
        feedback.msgSuccess(response.msg || '上传成功')
        refreshFonts()
    } else {
        feedback.msgError(response.msg || '上传失败')
    }
}

const handleFontUploadError = () => {
    feedback.msgError('字体文件上传失败')
}

const handleDeleteFont = async (row: any) => {
    await ElMessageBox.confirm(`确认删除字体“${row.name}”？`, '删除字体', {
        type: 'warning'
    })
    await deleteOrderConfirmLetterFont({ file: row.file })
    refreshFonts()
}

refreshConfig()
refreshFonts()
</script>

<style lang="scss" scoped>
.resource-form {
    margin-bottom: 8px;
}

.qrcode-setting {
    display: flex;
    align-items: center;
    gap: 16px;
}

.qrcode-setting__preview,
.qrcode-setting__empty {
    width: 96px;
    height: 96px;
    border-radius: 8px;
    border: 1px solid #e8ecf3;
    background: #fff;
}

.qrcode-setting__empty {
    display: grid;
    place-items: center;
    color: #8b95a5;
    font-size: 12px;
}
</style>
