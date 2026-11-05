<template>
    <view class="page-wrap apply">
        <base-card>
            <text class="block-title">暂停类型</text>
            <view class="chips">
                <view hover-class="is-pressed"
                    v-for="t in PAUSE_TYPES"
                    :key="t.value"
                    class="chips__item"
                    :class="{ 'is-active': form.pause_type === t.value }"
                    @tap="form.pause_type = t.value"
                >
                    <text class="chips__text">{{ t.label }}</text>
                </view>
            </view>
        </base-card>

        <base-card>
            <text class="block-title">暂停区间</text>
            <picker mode="date" :value="form.start_date" :start="today" @change="onStartDate">
                <view class="date-row">
                    <text class="date-row__label">开始</text>
                    <text :class="form.start_date ? 'date-row__text' : 'date-row__ph'">
                        {{ form.start_date || '请选择' }}
                    </text>
                </view>
            </picker>
            <picker mode="date" :value="form.end_date" :start="form.start_date || today" @change="onEndDate">
                <view class="date-row date-row--last">
                    <text class="date-row__label">结束</text>
                    <text :class="form.end_date ? 'date-row__text' : 'date-row__ph'">
                        {{ form.end_date || '请选择' }}
                    </text>
                </view>
            </picker>
        </base-card>

        <base-card>
            <text class="block-title">暂停原因（必填）</text>
            <textarea
                v-model="form.reason"
                class="textarea"
                placeholder="请说明需要暂停的原因，便于商家审核"
                placeholder-class="textarea__ph"
                maxlength="255"
            />
            <text class="counter">{{ form.reason.length }}/255</text>
        </base-card>

        <base-card>
            <text class="block-title">证明材料（选填，最多 10 张）</text>
            <view class="images">
                <view v-for="(img, i) in form.proof_images" :key="i" class="image">
                    <image class="image__img" :src="img" mode="aspectFill" />
                    <view hover-class="is-pressed" class="image__del" @tap="form.proof_images.splice(i, 1)">
                        <u-icon name="close" :size="24" color="#FFFFFF" />
                    </view>
                </view>
                <view hover-class="is-pressed" v-if="form.proof_images.length < 10" class="image image--add" @tap="chooseImages">
                    <u-icon name="plus" :size="40" color="var(--c-ink-3)" />
                </view>
            </view>
        </base-card>

        <view class="tips">
            <u-icon name="info-circle" :size="24" color="var(--c-ink-3)" />
            <text class="tips__text">暂停需商家审核。暂停期间服务人员档期会被释放，恢复后需重新确认日期。</text>
        </view>

        <view class="submit">
            <base-button text="提交申请" variant="ink" size="lg" :loading="submitting" @click="handleSubmit" />
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/**
 * 申请暂停：pause_type(1-4) + start_date + end_date + reason(必填) + proof_images?
 * 注意 reason 是 append('require') 强制的，前端必须拦空，否则后端返回的是通用校验错误。
 */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { ORDER_PAUSE_TYPE_NAME_MAP, OrderPauseType } from '@shared/contracts'
import { applyPause } from '@/api/change'
import { uploadImage } from '@/api/app'

const PAUSE_TYPE_ORDER = [
    OrderPauseType.EPIDEMIC,
    OrderPauseType.EMERGENCY,
    OrderPauseType.PERSONAL,
    OrderPauseType.OTHER
]
const PAUSE_TYPES = PAUSE_TYPE_ORDER.map((v) => ({ value: v, label: ORDER_PAUSE_TYPE_NAME_MAP[v] }))

const orderId = ref(0)
const submitting = ref(false)
const form = reactive({
    pause_type: OrderPauseType.PERSONAL as number,
    start_date: '',
    end_date: '',
    reason: '',
    proof_images: [] as string[]
})

const today = (() => {
    const d = new Date()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${d.getFullYear()}-${m}-${day}`
})()

function onStartDate(e: any) {
    form.start_date = String(e?.detail?.value || '')
    if (form.end_date && form.end_date < form.start_date) form.end_date = ''
}

function onEndDate(e: any) {
    form.end_date = String(e?.detail?.value || '')
}

function chooseImages() {
    const remain = 10 - form.proof_images.length
    uni.chooseImage({
        count: remain,
        sizeType: ['compressed'],
        success: async (res) => {
            for (const p of (res.tempFilePaths || []).slice(0, remain)) {
                try {
                    const up = await uploadImage(p)
                    form.proof_images.push((up as any)?.uri || (up as any)?.url || p)
                } catch {
                    // 单张失败跳过
                }
            }
        }
    })
}

async function handleSubmit() {
    if (submitting.value) return
    if (!form.start_date || !form.end_date) {
        toast.info('请选择暂停区间')
        return
    }
    if (form.end_date < form.start_date) {
        toast.info('结束日期不能早于开始日期')
        return
    }
    if (!form.reason.trim()) {
        toast.info('请填写暂停原因')
        return
    }
    submitting.value = true
    try {
        await applyPause({
            order_id: orderId.value,
            pause_type: form.pause_type,
            reason: form.reason.trim(),
            start_date: form.start_date,
            end_date: form.end_date,
            proof_images: form.proof_images.length ? form.proof_images : undefined
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
    }
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

.block-title {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-md);
    font-weight: 500;
    color: var(--c-ink);
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);

    &__item {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 64rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
        box-sizing: border-box;

        &.is-active {
            background: var(--c-ink);
            border-color: var(--c-ink);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__item.is-active &__text {
        color: var(--c-on-dark);
    }
}

.date-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 88rpx;
    padding: var(--sp-2) 0;
    border-bottom: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &--last {
        border-bottom: none;
    }

    &__label {
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__text,
    &__ph {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        text-align: right;
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

.counter {
    display: block;
    margin-top: var(--sp-1);
    text-align: right;
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
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
            border-radius: 50%;
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
        line-height: 1.6;
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
