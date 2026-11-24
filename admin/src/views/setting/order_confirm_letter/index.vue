<template>
    <div class="order-confirm-letter-setting">
        <el-card shadow="never" class="!border-none">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="font-medium">海报字体资源</div>
                    <div class="text-xs text-gray-500 mt-1">
                        这里维护后端生成海报图片（单量月报、档期确认函）所需的服务器字体文件与中文渲染检测，配置完成后日常一般无需改动。
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

            <el-alert v-if="!props.embedded" type="info" show-icon :closable="false" class="mb-5">
                <template #title>
                    <div class="flex items-center justify-between w-full">
                        <span>海报统一二维码（单量月报、档期确认函共用）已迁移至「系统设置 → 网站信息」的品牌与海报资源区维护。</span>
                        <el-button
                            type="primary"
                            link
                            @click="
                                router.push({
                                    path: getMenuPath('page.setting.website.hub') || '/setting/brand-site',
                                    query: { tab: 'poster' }
                                })
                            "
                        >
                            前往网站信息设置
                        </el-button>
                    </div>
                </template>
            </el-alert>

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

<script lang="ts" setup name="orderConfirmLetterSetting">
/**
 * 海报字体资源设置。
 * embedded=true 时作为海报资源聚合 Tab 的下半屏内嵌使用：隐藏迁移提示条，其余功能不变。
 */
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

const router = useRouter()

const userStore = useUserStore()

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

const refreshFonts = async () => {
    const data = await getOrderConfirmLetterFonts()
    fontList.value = data?.fonts || []
    Object.assign(fontConfig, data?.config || {})
    fontDiagnostics.value = data?.diagnostics || null
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

refreshFonts()
</script>

<style lang="scss" scoped></style>
