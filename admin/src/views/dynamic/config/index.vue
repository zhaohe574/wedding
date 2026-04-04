<template>
    <div class="dynamic-config-page">
        <el-card class="!border-none" shadow="never">
            <div class="text-lg font-medium mb-4">动态配置</div>
            <el-form ref="formRef" :model="formData" label-width="120px">
                <el-form-item label="评论审核">
                    <div>
                        <el-switch
                            v-model="formData.comment_review_enabled"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleSaveConfig"
                        />
                        <div class="form-tips mt-2">
                            开启后，用户发表的评论需要管理员审核通过后才能显示
                        </div>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="dynamicConfig">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGetReviewConfig, apiSetReviewConfig } from '@/api/dynamic'

const formData = ref({
    comment_review_enabled: 0
})

// 获取配置
const getConfig = async () => {
    try {
        const res = await apiGetReviewConfig()
        formData.value.comment_review_enabled = res.enabled
    } catch (error) {
        console.error('获取配置失败', error)
    }
}

// 保存配置
const handleSaveConfig = async () => {
    try {
        await apiSetReviewConfig({
            enabled: formData.value.comment_review_enabled
        })
        ElMessage.success('设置成功')
    } catch (error) {
        ElMessage.error('设置失败')
        // 恢复原值
        formData.value.comment_review_enabled = formData.value.comment_review_enabled === 1 ? 0 : 1
    }
}

onMounted(() => {
    getConfig()
})
</script>

<style lang="scss" scoped>
.form-tips {
    font-size: 12px;
    color: #999;
    line-height: 1.5;
}
</style>
