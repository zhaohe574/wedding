<template>
    <view class="page-wrap create">
        <base-card>
            <text class="block-title">工单类型</text>
            <view class="types">
                <view hover-class="is-pressed"
                    v-for="t in TYPES"
                    :key="t.value"
                    class="type"
                    :class="{ 'is-active': form.type === t.value }"
                    @tap="form.type = t.value"
                >
                    <text class="type__text">{{ t.label }}</text>
                </view>
            </view>
        </base-card>

        <base-card>
            <text class="block-title">标题（必填）</text>
            <input
                v-model="form.title"
                class="input"
                placeholder="一句话描述问题（必填）"
                placeholder-class="input__ph"
                maxlength="50"
            />
            <text class="block-title block-title--gap">详细描述</text>
            <textarea
                v-model="form.content"
                class="textarea"
                placeholder="请补充具体情况，便于我们快速处理"
                placeholder-class="textarea__ph"
                maxlength="1000"
            />
            <text class="counter">{{ form.content.length }}/1000</text>
        </base-card>

        <base-card>
            <text class="block-title">上传凭证（选填）</text>
            <view class="images">
                <view v-for="(img, i) in form.images" :key="i" class="image">
                    <image class="image__img" :src="img" mode="aspectFill" />
                    <view hover-class="is-pressed" class="image__del" @tap="form.images.splice(i, 1)">
                        <u-icon name="close" :size="24" color="var(--c-ink-inverse)" />
                    </view>
                </view>
                <view hover-class="is-pressed" v-if="form.images.length < 6" class="image image--add" @tap="chooseImages">
                    <u-icon name="plus" :size="40" color="var(--c-ink-3)" />
                </view>
            </view>
        </base-card>

        <base-card>
            <text class="block-title">联系方式</text>
            <view class="row">
                <text class="row__label">联系人</text>
                <input v-model="form.contact_name" class="row__input" placeholder="选填" placeholder-class="input__ph" />
            </view>
            <view class="row row--last">
                <text class="row__label">手机号</text>
                <input
                    v-model="form.contact_mobile"
                    class="row__input"
                    type="number"
                    placeholder="选填"
                    placeholder-class="input__ph"
                    maxlength="11"
                />
            </view>
        </base-card>

        <view class="submit">
            <base-button text="提交工单" variant="ink" size="lg" :loading="submitting" @click="handleSubmit" />
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 提交工单：type 1-5，title 必填 */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { AFTER_SALE_TICKET_TYPE_NAME_MAP, AfterSaleTicketType } from '@shared/contracts'
import { createTicket } from '@/api/aftersale'
import { uploadImage } from '@/api/app'

/** 类型文案与顺序来自契约，不在这里手写中文 */
const TYPE_ORDER = [
    AfterSaleTicketType.AFTER_SALE,
    AfterSaleTicketType.CONSULT,
    AfterSaleTicketType.COMPLAINT,
    AfterSaleTicketType.SUGGEST,
    AfterSaleTicketType.OTHER
]
const TYPES = TYPE_ORDER.map((v) => ({ value: v, label: AFTER_SALE_TICKET_TYPE_NAME_MAP[v] }))

const submitting = ref(false)
const orderId = ref(0)

const form = reactive({
    type: 3,
    title: '',
    content: '',
    images: [] as string[],
    contact_name: '',
    contact_mobile: ''
})

function chooseImages() {
    const remain = 6 - form.images.length
    uni.chooseImage({
        count: remain,
        sizeType: ['compressed'],
        success: async (res) => {
            const paths = (res.tempFilePaths || []).slice(0, remain)
            for (const p of paths) {
                try {
                    const up = await uploadImage(p)
                    form.images.push((up as any)?.uri || (up as any)?.url || p)
                } catch {
                    // 单张失败跳过
                }
            }
        }
    })
}

async function handleSubmit() {
    if (submitting.value) return
    if (!form.title.trim()) {
        toast.info('请填写标题')
        return
    }
    if (form.contact_mobile && !/^1\d{10}$/.test(form.contact_mobile)) {
        toast.info('手机号格式不正确')
        return
    }
    submitting.value = true
    try {
        await createTicket({
            type: form.type,
            title: form.title.trim(),
            content: form.content.trim() || undefined,
            images: form.images.length ? form.images : undefined,
            order_id: orderId.value || undefined,
            contact_name: form.contact_name.trim() || undefined,
            contact_mobile: form.contact_mobile.trim() || undefined
        })
        toast.success('已提交')
        setTimeout(() => uni.navigateBack(), 600)
    } catch {
        // 请求层已提示
    } finally {
        submitting.value = false
    }
}

onLoad((options: any) => {
    orderId.value = Number(options?.order_id || 0)
})
</script>

<style lang="scss" scoped>
.create {
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

    &--gap {
        margin-top: var(--sp-4);
    }
}

.types {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.type {
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

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &.is-active &__text {
        color: var(--c-on-dark);
    }
}

.input {
    width: 100%;
    height: 88rpx;
    padding: 0 var(--sp-3);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    background: var(--c-surface-2);
    box-sizing: border-box;
    font-size: var(--fs-md);
    color: var(--c-ink);

    &__ph {
        color: var(--c-ink-3);
    }
}

.textarea {
    width: 100%;
    min-height: 220rpx;
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
            border-radius: var(--r-pill);
            background: var(--c-mask);
        }
    }
}

.row {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 88rpx;
    padding: var(--sp-2) 0;
    border-bottom: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &--last {
        border-bottom: none;
    }

    &__label {
        width: 120rpx;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        flex-shrink: 0;
    }

    &__input {
        flex: 1;
        min-width: 0;
        height: 72rpx;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }
}

.submit {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    margin: 0;
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    border-top: 1rpx solid var(--c-line);
    background: var(--c-surface);
    box-sizing: border-box;
}
</style>
