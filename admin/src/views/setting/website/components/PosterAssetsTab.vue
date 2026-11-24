<template>
    <div class="poster-assets-tab">
        <el-card shadow="never" class="!border-none">
            <div class="font-medium">海报统一二维码</div>
            <div class="text-xs text-gray-500 mt-1 mb-6">
                单量月报、档期确认函共用此二维码（即网站配置中的 schedule_qrcode_image），保存后立即生效。
            </div>
            <el-form label-width="120px">
                <el-form-item label="统一二维码">
                    <div>
                        <material-picker v-model="qrcodeImage" :limit="1" />
                        <div class="form-tips">建议正方形 300×300px 以上，支持 jpg、jpeg、png 格式</div>
                    </div>
                </el-form-item>
                <el-form-item>
                    <el-button v-perms="['setting.web.web_setting/setWebsite']" type="primary" @click="handleSaveQrcode">
                        保存
                    </el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <OrderConfirmLetterSetting embedded class="mt-4" />
    </div>
</template>

<script lang="ts" setup name="posterAssetsTab">
/**
 * 海报资源聚合 Tab（批次 3a）：上半屏维护海报统一二维码，
 * 下半屏内嵌海报字体资源设置（单量月报、档期确认函共用）。
 */
import { getWebsite, setWebsite } from '@/api/setting/website'
import OrderConfirmLetterSetting from '@/views/setting/order_confirm_letter/index.vue'

const qrcodeImage = ref('')

const getData = async () => {
    const data = await getWebsite()
    qrcodeImage.value = data?.schedule_qrcode_image ?? ''
}

const handleSaveQrcode = async () => {
    // setWebsite 服务端校验必传 name/web_favicon/web_logo/login_image/shop_name/shop_logo/pc_logo 等字段，
    // 必须基于 getWebsite() 的完整当前值合并保存，避免把站点名称、logo 等配置置空。
    const fullData = await getWebsite()
    await setWebsite({ ...fullData, schedule_qrcode_image: qrcodeImage.value })
}

getData()
</script>
