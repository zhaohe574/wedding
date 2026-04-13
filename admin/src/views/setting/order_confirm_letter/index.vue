<template>
    <div class="order-confirm-letter-setting">
        <el-card shadow="never" class="!border-none">
            <div class="font-medium mb-6">订单确认函设置</div>
            <el-form label-width="120px">
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

        <footer-btns>
            <el-button type="primary" @click="handleSave">保存</el-button>
        </footer-btns>
    </div>
</template>

<script lang="ts" setup>
import { getOrderConfirmLetterConfig, setOrderConfirmLetterConfig } from '@/api/setting/orderConfirmLetter'

const formData = reactive({
    remark_template: ''
})

const getData = async () => {
    const data = await getOrderConfirmLetterConfig()
    Object.assign(formData, data || {})
}

const handleSave = async () => {
    await setOrderConfirmLetterConfig({ remark_template: formData.remark_template })
    getData()
}

getData()
</script>

<style lang="scss" scoped></style>
