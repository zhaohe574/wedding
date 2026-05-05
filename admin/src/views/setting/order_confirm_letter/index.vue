<template>
    <div class="order-confirm-letter-setting">
        <el-card shadow="never" class="!border-none">
            <div class="font-medium mb-6">订单确认函设置</div>
            <el-form label-width="120px">
                <el-form-item label="支付节点">
                    <div class="w-[560px] flex flex-col gap-2">
                        <el-input
                            v-model="formData.payment_node"
                            maxlength="60"
                            show-word-limit
                            placeholder="例如：婚礼前 3 日"
                        />
                        <span class="text-xs text-gray-500">
                            显示在确认函金额卡片的支付节点位置，留空时默认“婚礼前 3 日”。
                        </span>
                    </div>
                </el-form-item>
                <el-form-item label="备注模板">
                    <div class="w-[560px] flex flex-col gap-2">
                        <el-input
                            v-model="formData.remark_template"
                            type="textarea"
                            :rows="8"
                            maxlength="1000"
                            show-word-limit
                            placeholder="请输入订单确认函固定备注模板"
                        />
                        <span class="text-xs text-gray-500">
                            仅影响后续新生成的确认函，历史版本不回写。
                        </span>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>

        <el-card shadow="never" class="!border-none mt-4">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="font-medium">确认函字体管理</div>
                    <div class="text-xs text-gray-500 mt-1">
                        字体只用于后端生成确认函图片，不会写入系统字体库。
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

        <footer-btns>
            <el-button type="primary" @click="handleSave">保存备注模板</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup>
import { ElMessageBox } from 'element-plus'
import config from '@/config'
import {
    checkOrderConfirmLetterFont,
    deleteOrderConfirmLetterFont,
    getOrderConfirmLetterConfig,
    getOrderConfirmLetterFonts,
    setOrderConfirmLetterConfig,
    setOrderConfirmLetterFont
} from '@/api/setting/orderConfirmLetter'
import useUserStore from '@/stores/modules/user'
import feedback from '@/utils/feedback'
import { RequestCodeEnum } from '@/enums/requestEnums'

const userStore = useUserStore()

const formData = reactive({
    remark_template: '',
    payment_node: ''
})

const fontConfig = reactive({
    sans_file: '',
    serif_file: ''
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

const getData = async () => {
    const data = await getOrderConfirmLetterConfig()
    Object.assign(formData, data || {})
}

const refreshFonts = async () => {
    const data = await getOrderConfirmLetterFonts()
    fontList.value = data?.fonts || []
    Object.assign(fontConfig, data?.config || {})
    fontDiagnostics.value = data?.diagnostics || null
}

const handleSave = async () => {
    await setOrderConfirmLetterConfig({
        remark_template: formData.remark_template,
        payment_node: formData.payment_node
    })
    getData()
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

getData()
refreshFonts()
</script>

<style lang="scss" scoped></style>
