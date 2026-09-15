<template>
    <view class="receipt-editor">
        <text class="receipt-editor__hint">凭证提交后由后台核实到账，审核通过前不入账、不锁档。</text>
        <picker :range="phases" range-key="label" @change="choosePhase">
            <view class="receipt-editor__field">收款阶段：{{ currentPhase?.label || '请选择' }} <text>¥{{ currentPhase?.amount || 0 }} ›</text></view>
        </picker>
        <picker :range="['人员代收', '平台收款']" @change="chooseOwner">
            <view class="receipt-editor__field">收款归属 <text>{{ modelValue.collection_owner === 1 ? '平台收款' : '人员代收' }} ›</text></view>
        </picker>
        <view class="receipt-editor__upload" @click="chooseVoucher">
            <image v-if="modelValue.voucher" :src="modelValue.voucher" mode="aspectFit" />
            <text>{{ uploading ? '正在上传…' : modelValue.voucher ? '重新上传收款凭证' : '＋ 上传收款凭证（必填）' }}</text>
        </view>
    </view>
</template>
<script setup lang="ts">
import { computed, ref } from 'vue'
import { uploadImage } from '@/api/app'
import { showError } from '@/utils/feedback'
import type { ReceiptDraft, ReceiptPhase } from '@/api/staffManualOrder'
const props = defineProps<{ modelValue: ReceiptDraft; phases: ReceiptPhase[]; disabled?: boolean }>()
const emit = defineEmits<{ (event: 'update:modelValue', value: ReceiptDraft): void; (event: 'uploading', value: boolean): void }>()
const uploading = ref(false)
const currentPhase = computed(() => props.phases.find(item => item.value === props.modelValue.pay_type))
const update = (values: Partial<ReceiptDraft>) => { if (!props.disabled) emit('update:modelValue', { ...props.modelValue, ...values }) }
const choosePhase = (event: any) => update({ pay_type: props.phases[Number(event.detail.value)]?.value || 0 })
const chooseOwner = (event: any) => update({ collection_owner: Number(event.detail.value) === 0 ? 2 : 1 })
const chooseVoucher = () => {
    if (uploading.value || props.disabled) return
    uni.chooseImage({ count: 1, sizeType: ['compressed'], sourceType: ['album', 'camera'], success: async result => {
        const path = result.tempFilePaths[0]
        if (!path) return
        uploading.value = true
        emit('uploading', true)
        try {
            const file: any = await uploadImage(path)
            if (!file?.uri) throw new Error('上传失败，请重试')
            update({ voucher: file.uri })
        } catch (error) { showError(error, '上传失败') }
        finally { uploading.value = false; emit('uploading', false) }
    } })
}
</script>
<style scoped>
.receipt-editor { display: flex; flex-direction: column; gap: 24rpx; }
.receipt-editor__hint { color: var(--wm-text-tertiary); font-size: 24rpx; line-height: 1.7; }
.receipt-editor__field { display: flex; justify-content: space-between; padding: 24rpx 0; border-bottom: 1rpx solid var(--wm-color-border); font-size: 27rpx; color: var(--wm-text-primary); }
.receipt-editor__upload { padding: 32rpx; border: 2rpx dashed var(--wm-color-champagne); border-radius: var(--wm-radius-control, 44rpx); display: flex; align-items: center; flex-direction: column; gap: 16rpx; color: var(--wm-color-gold); font-size: 26rpx; }
.receipt-editor__upload image { width: 100%; height: 240rpx; }
</style>
