<template>
    <tn-popup v-model="visible" open-direction="bottom" :radius="40">
        <view v-if="item" class="cancel-sheet">
            <view class="cancel-sheet__head">
                <text class="cancel-sheet__title">{{ sheetTitle }}</text>
                <view
                    class="cancel-sheet__close"
                    hover-class="is-pressed"
                    aria-role="button"
                    aria-label="关闭弹窗"
                    @tap="close"
                >
                    <tn-icon name="close" :size="30" class="cancel-sheet__close-ic" />
                </view>
            </view>

            <view class="cancel-sheet__body">
                <view class="summary">
                    <view class="summary__row">
                        <text class="summary__k">活动主题</text>
                        <text class="summary__v ellipsis">{{ titleText }}</text>
                    </view>
                    <view class="summary__row">
                        <text class="summary__k">票种规格</text>
                        <text class="summary__v">{{ item.ticket_name || '活动门票' }} · {{ item.ticket_price_label || '免费' }}</text>
                    </view>
                    <view v-if="!isDirect" class="summary__row">
                        <text class="summary__k">预计退款</text>
                        <text class="summary__v summary__v--brand">{{ item.ticket_price_label || '¥0.00' }}（审核通过后原路退回）</text>
                    </view>
                </view>

                <view v-if="isDirect" class="direct-tip">
                    <text class="direct-tip__text">
                        确认取消后将立即释放您占用的活动名额，取消后如需参加需重新报名。
                    </text>
                </view>

                <template v-else>
                    <text class="section-label">选择取消原因</text>
                    <view class="chips">
                        <view
                            v-for="preset in PRESET_REASONS"
                            :key="preset"
                            class="chip"
                            :class="{ 'is-on': selectedPreset === preset }"
                            hover-class="is-pressed"
                            @tap="pickPreset(preset)"
                        >
                            <text class="chip__text">{{ preset }}</text>
                        </view>
                    </view>
                    <textarea
                        v-model="extraNote"
                        class="note-input"
                        placeholder="可补充具体原因（选填，最多 200 字）"
                        placeholder-class="note-input__ph"
                        :maxlength="200"
                    />
                </template>
            </view>

            <view class="cancel-sheet__foot">
                <view class="foot-btn">
                    <base-button text="先不取消" variant="ghost" size="md" @click="close" />
                </view>
                <view class="foot-btn">
                    <base-button
                        :text="isDirect ? '确认取消' : '提交申请'"
                        variant="ink"
                        size="md"
                        :loading="submitting"
                        @click="submit"
                    />
                </view>
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { ActivityRegistrationVO } from '@/api/types/activity'
import { cancelActivityRegistration } from '@/api/activity'
import { toast } from '@/composables/useToast'
import { activityTitle } from '@/utils/activityFormat'

const PRESET_REASONS = ['临时行程变动', '选错票种或时间', '同行人无法参加', '其他原因']

const props = defineProps<{
    show: boolean
    item: ActivityRegistrationVO | null
}>()

const emit = defineEmits<{
    (e: 'update:show', value: boolean): void
    (e: 'cancelled'): void
}>()

const visible = computed({
    get: () => props.show,
    set: (val: boolean) => {
        if (!val && submitting.value) return
        emit('update:show', val)
    }
})

const selectedPreset = ref(PRESET_REASONS[0])
const extraNote = ref('')
const submitting = ref(false)

watch(
    () => props.show,
    (open) => {
        if (open) {
            selectedPreset.value = PRESET_REASONS[0]
            extraNote.value = ''
        }
    }
)

const isDirect = computed(() => props.item?.cancel_mode === 'direct')
const titleText = computed(() => (props.item ? activityTitle(props.item) : ''))
const sheetTitle = computed(() => (isDirect.value ? '确认取消报名' : '申请取消并退款'))

function pickPreset(preset: string) {
    selectedPreset.value = preset
}

function close() {
    if (submitting.value) return
    emit('update:show', false)
}

async function submit() {
    if (!props.item || submitting.value) return
    const note = extraNote.value.trim()
    let reason = ''
    if (!isDirect.value) {
        if (selectedPreset.value === '其他原因') {
            reason = note || '其他原因'
        } else {
            reason = note ? `${selectedPreset.value}：${note}` : selectedPreset.value
        }
    }

    submitting.value = true
    try {
        await cancelActivityRegistration({
            registration_id: props.item.id,
            reason: reason || undefined
        })
        toast.success(isDirect.value ? '已取消报名，名额已释放' : '取消申请已提交，等待审核')
        emit('update:show', false)
        emit('cancelled')
    } catch (e: any) {
        toast.error(e?.message || '取消失败，请稍后重试')
    } finally {
        submitting.value = false
    }
}
</script>

<style lang="scss" scoped>
.cancel-sheet {
    display: flex;
    flex-direction: column;
    background: var(--c-surface);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--sp-4) var(--page-gutter) var(--sp-3);
        border-bottom: 1rpx solid var(--c-line);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__close {
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
    }

    &__close-ic {
        color: var(--c-ink-3);
    }

    &__body {
        padding: var(--sp-3) var(--page-gutter) var(--sp-4);
    }

    &__foot {
        display: flex;
        gap: var(--sp-2);
        padding: var(--sp-3) var(--page-gutter) calc(var(--sp-4) + var(--safe-bottom));
        border-top: 1rpx solid var(--c-line);
    }
}

.foot-btn {
    flex: 1;
    min-width: 0;
}

.summary {
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-bg);
    border: 1rpx solid var(--c-line);

    &__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
        padding: var(--sp-1) 0;
    }

    &__k {
        flex: none;
        font-size: var(--fs-23);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        text-align: right;
        font-size: var(--fs-23);
        color: var(--c-ink);

        &--brand {
            color: var(--c-brand-text);
            font-weight: var(--fw-medium);
        }
    }
}

.direct-tip {
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-warning-bg);
    border: 1rpx solid var(--c-warning-border);

    &__text {
        font-size: var(--fs-23);
        line-height: var(--lh-body);
        color: var(--c-warning);
    }
}

.section-label {
    display: block;
    margin-top: var(--sp-3);
    font-size: var(--fs-xs);
    font-weight: var(--fw-semibold);
    color: var(--c-ink);
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
    margin-top: var(--sp-2);
}

.chip {
    padding: 12rpx 24rpx;
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-line-strong);
    background: var(--c-bg);

    &__text {
        font-size: var(--fs-23);
        color: var(--c-ink-2);
    }

    &.is-on {
        background: var(--c-brand-bg);
        border-color: var(--c-brand);

        .chip__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-medium);
        }
    }
}

.note-input {
    width: 100%;
    height: 150rpx;
    margin-top: var(--sp-3);
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-bg);
    border: 1rpx solid var(--c-control-border);
    box-sizing: border-box;
    font-size: var(--fs-23);
    line-height: var(--lh-body);
    color: var(--c-ink);

    &__ph {
        color: var(--c-ink-3);
    }
}
</style>
