<template>
    <view class="page-wrap apply">
        <base-card>
            <view class="kv">
                <text class="kv__k">订单编号</text>
                <text class="kv__v">{{ orderSn || '-' }}</text>
            </view>
            <view class="kv">
                <text class="kv__k">当前服务日期</text>
                <text class="kv__v">{{ currentDate || '-' }}</text>
            </view>
        </base-card>

        <base-card>
            <text class="block-title">新的服务日期</text>
            <picker mode="date" :value="form.new_date" :start="today" @change="onDateChange">
                <view class="picker-row">
                    <text :class="form.new_date ? 'picker-row__text' : 'picker-row__ph'">
                        {{ form.new_date || '请选择日期' }}
                    </text>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
            </picker>

            <text class="block-title block-title--gap">变更原因</text>
            <textarea
                v-model="form.reason"
                class="textarea"
                placeholder="请说明改期原因（选填）"
                placeholder-class="textarea__ph"
                maxlength="255"
            />
        </base-card>

        <base-card>
            <text class="block-title">证明材料（选填，最多 5 张）</text>
            <view class="images">
                <view v-for="(img, i) in form.attach_images" :key="i" class="image">
                    <image class="image__img" :src="img" mode="aspectFill" />
                    <view hover-class="is-pressed" class="image__del" @tap="form.attach_images.splice(i, 1)">
                        <u-icon name="close" :size="24" color="var(--c-ink-inverse)" />
                    </view>
                </view>
                <view hover-class="is-pressed" v-if="form.attach_images.length < 5" class="image image--add" @tap="chooseImages">
                    <u-icon name="plus" :size="40" color="var(--c-ink-3)" />
                </view>
            </view>
        </base-card>

        <view class="tips">
            <u-icon name="info-circle" :size="24" color="var(--c-ink-3)" />
            <text class="tips__text">改期需商家审核，审核通过后档期才会变更。已支付订单的差价处理以审核结果为准。</text>
        </view>

        <view class="submit">
            <base-button text="提交申请" variant="ink" size="lg" :loading="submitting" @click="handleSubmit" />
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 申请改期：order_id + new_date + reason? + attach_images? */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyDateChange, checkCanChange } from '@/api/change'
import { getOrderDetail } from '@/api/order'
import { uploadImage } from '@/api/app'

const orderId = ref(0)
const orderSn = ref('')
const currentDate = ref('')
const submitting = ref(false)
const form = reactive({ new_date: '', reason: '', attach_images: [] as string[] })

const today = (() => {
    const d = new Date()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${d.getFullYear()}-${m}-${day}`
})()

function onDateChange(e: any) {
    form.new_date = String(e?.detail?.value || '')
}

function chooseImages() {
    const remain = 5 - form.attach_images.length
    uni.chooseImage({
        count: remain,
        sizeType: ['compressed'],
        success: async (res) => {
            for (const p of (res.tempFilePaths || []).slice(0, remain)) {
                try {
                    const up = await uploadImage(p)
                    form.attach_images.push((up as any)?.uri || (up as any)?.url || p)
                } catch {
                    // 单张失败跳过
                }
            }
        }
    })
}

async function loadContext() {
    try {
        const res = await checkCanChange({ order_id: orderId.value })
        if (res && res.can_change === 0) {
            uni.showModal({
                title: '暂不可改期',
                content: res.message || '当前订单状态不支持改期',
                showCancel: false,
                success: () => uni.navigateBack()
            })
        }
    } catch {
        // 检查失败不阻断，提交时后端仍会校验
    }

    try {
        const detail = await getOrderDetail({ id: orderId.value })
        orderSn.value = detail?.order_sn || ''
        const first = (detail?.items || [])[0]
        currentDate.value = first?.service_date || detail?.service_date || ''
    } catch {
        // 忽略
    }
}

async function handleSubmit() {
    if (submitting.value) return
    if (!form.new_date) {
        toast.info('请选择新日期')
        return
    }
    if (form.new_date === currentDate.value) {
        toast.info('新日期与当前日期相同')
        return
    }
    submitting.value = true
    try {
        await applyDateChange({
            order_id: orderId.value,
            new_date: form.new_date,
            reason: form.reason.trim() || undefined,
            attach_images: form.attach_images.length ? form.attach_images : undefined
        })
        toast.success('已提交申请')
        setTimeout(() => uni.navigateBack(), 700)
    } catch {
        // 请求层已提示
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.order_id || 0)
    if (!orderId.value) {
        toast.info('参数缺失')
        setTimeout(() => uni.navigateBack(), 600)
        return
    }
    loadContext()
})
</script>

<style lang="scss" scoped>
.apply {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4) calc(164rpx + var(--safe-bottom));
    background: var(--c-bg);
}

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 56rpx;
    padding: var(--sp-2) 0;
    box-sizing: border-box;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &__k {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }
}

.block-title {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-md);
    font-weight: 500;
    color: var(--c-ink);

    &--gap {
        margin-top: var(--sp-4);
    }
}

.picker-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    box-sizing: border-box;

    &__text,
    &__ph {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__text {
        color: var(--c-ink);
    }

    &__ph {
        color: var(--c-ink-3);
    }
}

.textarea {
    width: 100%;
    min-height: 200rpx;
    padding: var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    box-sizing: border-box;
    font-size: var(--fs-md);
    color: var(--c-ink);
    line-height: 1.7;

    &__ph {
        color: var(--c-ink-3);
    }
}

.images {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-3) var(--sp-2);
}

.image {
    position: relative;
    width: 160rpx;
    height: 160rpx;
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);

    &--add {
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1rpx dashed var(--c-line-strong);
        background: var(--c-surface-2);
        box-sizing: border-box;
        overflow: hidden;
    }

    &__img {
        display: block;
        width: 100%;
        height: 100%;
        border-radius: var(--r-sm);
        overflow: hidden;
    }

    &__del {
        position: absolute;
        top: -24rpx;
        right: -24rpx;
        z-index: 2;
        width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        &::before {
            content: '';
            position: absolute;
            width: 40rpx;
            height: 40rpx;
            border-radius: var(--r-pill);
            background: var(--c-mask);
        }
    }
}

.tips {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-1);
    padding: 0 var(--sp-1);

    &__text {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
        line-height: var(--lh-body);
        overflow-wrap: anywhere;
    }
}

.submit {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    border-top: 1rpx solid var(--c-line);
    background: var(--c-surface);
    box-sizing: border-box;
}
</style>
