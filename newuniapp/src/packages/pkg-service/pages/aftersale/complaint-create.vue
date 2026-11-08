<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="提交投诉" bg="page" :show-back="true" :bottom-bar="true">
        <view class="page-wrap create">
            <base-card>
                <text class="block-title">投诉类型</text>
                <view class="chips">
                    <view
                        hover-class="is-pressed"
                        v-for="t in TYPES"
                        :key="t.value"
                        class="chips__item"
                        :class="{ 'is-active': form.type === t.value }"
                        @tap="form.type = t.value"
                    >
                        <text class="chips__text">{{ t.label }}</text>
                    </view>
                </view>

                <text class="block-title block-title--gap">紧急程度</text>
                <view class="chips">
                    <view
                        hover-class="is-pressed"
                        v-for="l in LEVELS"
                        :key="l.value"
                        class="chips__item"
                        :class="{ 'is-active': form.level === l.value }"
                        @tap="form.level = l.value"
                    >
                        <text class="chips__text">{{ l.label }}</text>
                    </view>
                </view>
            </base-card>

            <base-card>
                <!-- P1 §3-6：必填标识从文案里的「（必填）」改为 form-field 的红星 -->
                <form-field
                    label="标题"
                    :required="true"
                    :model-value="form.title"
                    :maxlength="50"
                    placeholder="一句话说明问题"
                    @update:model-value="(v: string) => (form.title = v)"
                />

                <text class="block-title block-title--gap">详细描述</text>
                <textarea
                    v-model="form.content"
                    class="textarea"
                    placeholder="请描述发生的时间、地点、经过，便于核实"
                    placeholder-class="textarea__ph"
                    maxlength="1000"
                />
                <text class="counter">{{ form.content.length }}/1000</text>
            </base-card>

            <base-card>
                <text class="block-title">期望处理结果</text>
                <textarea
                    v-model="form.expect_result"
                    class="textarea"
                    placeholder="如：希望补拍 / 希望退款 / 希望道歉（选填）"
                    placeholder-class="textarea__ph"
                    maxlength="300"
                />
            </base-card>

            <base-card>
                <text class="block-title">上传凭证（选填，最多 6 张）</text>
                <view class="images">
                    <view v-for="(img, i) in form.images" :key="i" class="image">
                        <image class="image__img" :src="img" mode="aspectFill" />
                        <view
                            hover-class="is-pressed"
                            class="image__del"
                            @tap="form.images.splice(i, 1)"
                        >
                            <!-- v1 u-icon → v2 tn-icon：close → close -->
                            <tn-icon name="close" :size="24" color="var(--c-ink-inverse)" />
                        </view>
                    </view>
                    <view
                        hover-class="is-pressed"
                        v-if="form.images.length < 6"
                        class="image image--add"
                        @tap="chooseImages"
                    >
                        <!-- v1 u-icon → v2 tn-icon：plus → add -->
                        <tn-icon name="add" :size="40" color="var(--c-ink-3)" />
                    </view>
                </view>
            </base-card>

            <view class="tips">
                <!-- v1 u-icon → v2 tn-icon：info-circle → tips -->
                <tn-icon name="tips" :size="24" color="var(--c-ink-3)" />
                <!-- P1 §3-6：41 字长句拆两句 —— 前一句讲后果，后一句讲边界 -->
                <view class="tips__body">
                    <text class="tips__text">投诉会进入平台客服工单，24 小时内响应。</text>
                    <text class="tips__text">恶意投诉可能影响账号信用。</text>
                </view>
            </view>

            <view class="submit">
                <base-button
                    text="提交投诉"
                    variant="ink"
                    size="lg"
                    :loading="submitting"
                    @click="handleSubmit"
                />
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 提交投诉：type(1-5) + level(1-3) + title(必填) + content + expect_result + images */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import {
    COMPLAINT_TYPE_NAME_MAP,
    COMPLAINT_LEVEL_NAME_MAP,
    ComplaintType,
    ComplaintLevel
} from '@shared/contracts'
import { submitComplaint } from '@/api/aftersale'
import { uploadImage } from '@/api/app'

const TYPES = (Object.keys(COMPLAINT_TYPE_NAME_MAP) as string[])
    .filter((k) => isNaN(Number(k)))
    .map((k) => ({
        value: Number((ComplaintType as any)[k]),
        label: (COMPLAINT_TYPE_NAME_MAP as any)[k]
    }))

const LEVELS = (Object.keys(COMPLAINT_LEVEL_NAME_MAP) as string[])
    .filter((k) => isNaN(Number(k)))
    .map((k) => ({
        value: Number((ComplaintLevel as any)[k]),
        label: (COMPLAINT_LEVEL_NAME_MAP as any)[k]
    }))

const submitting = ref(false)
const orderId = ref(0)

const form = reactive({
    type: ComplaintType.SERVICE as number,
    level: ComplaintLevel.NORMAL as number,
    title: '',
    content: '',
    expect_result: '',
    images: [] as string[]
})

function chooseImages() {
    const remain = 6 - form.images.length
    uni.chooseImage({
        count: remain,
        sizeType: ['compressed'],
        success: async (res) => {
            for (const p of (res.tempFilePaths || []).slice(0, remain)) {
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
    submitting.value = true
    try {
        await submitComplaint({
            order_id: orderId.value || undefined,
            type: form.type,
            level: form.level,
            title: form.title.trim(),
            content: form.content.trim() || undefined,
            expect_result: form.expect_result.trim() || undefined,
            images: form.images.length ? form.images : undefined
        })
        toast.success('已提交')
        setTimeout(() => uni.navigateBack(), 700)
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
    /* P2：原 padding-bottom: calc(164rpx + var(--safe-bottom)) 已删除，
       预留改由 page-shell 的 :bottom-bar="true" 统一负责 */
    padding: var(--sp-3) var(--sp-4) var(--sp-4);
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

    /* 拆句后两行容器 */
    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2rpx;
    }

    &__text {
        display: block;
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
    margin: 0;
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    border-top: 1rpx solid var(--c-line);
    background: var(--c-surface);
    box-sizing: border-box;
}
</style>
