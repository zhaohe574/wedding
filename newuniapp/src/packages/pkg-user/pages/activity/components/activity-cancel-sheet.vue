<template>
    <!-- 取消报名：磁吸半屏抽屉。标题 / 关闭 / 脚部安全区由 snap-sheet 统一承担；
         原 .cancel-sheet 包裹层已去掉，样式全部扁平化到插槽内容上 -->
    <snap-sheet v-model="visible" :title="sheetTitle" :has-foot="true">
        <view v-if="item" class="cancel-body">
            <view class="summary">
                <view class="summary__row">
                    <text class="summary__k">活动主题</text>
                    <text class="summary__v ellipsis">{{ titleText }}</text>
                </view>
                <view class="summary__row">
                    <text class="summary__k">票种规格</text>
                    <text class="summary__v t-num"
                        >{{ item.ticket_name || '活动门票' }} ·
                        {{ item.ticket_price_label || '免费' }}</text
                    >
                </view>
                <view v-if="!isDirect" class="summary__row">
                    <text class="summary__k">预计退款</text>
                    <text class="summary__v summary__v--brand t-num"
                        >{{ item.ticket_price_label || '¥0.00' }}（审核通过后原路退回）</text
                    >
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
                    placeholder-class="field__ph"
                    :maxlength="200"
                />
            </template>
        </view>

        <template #foot>
            <view v-if="item" class="foot">
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
        </template>
    </snap-sheet>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { ActivityRegistrationVO } from '@/api/types/activity'
import { cancelActivityRegistration } from '@/api/activity'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'
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
    set: (val: boolean) => emit('update:show', val)
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
    if (selectedPreset.value === preset) return
    haptic('light', 'activity-cancel-reason')
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
/* 原 .cancel-sheet / __head / __body / __foot 已由 snap-sheet 承担，
   以下类直接挂在 body / foot 插槽内容上 */
.cancel-body {
    display: flex;
    flex-direction: column;
    padding-top: var(--sp-1);
}

.foot {
    display: flex;
    gap: var(--sp-2);
}

.foot-btn {
    flex: 1;
    min-width: 0;
}

.summary {
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-bg);
    border: 1rpx solid var(--c-card-border);

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

/* 原因胶囊：视觉仍是小胶囊，热区直接给足 88rpx（换行容器不用伪元素外扩，避免相邻项抢点击） */
.chip {
    display: inline-flex;
    align-items: center;
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        transform var(--dur-press) var(--ease-out);

    &__text {
        font-size: var(--fs-23);
        color: var(--c-ink-2);
    }

    &.is-pressed {
        transform: scale(0.97);
    }

    &.is-on {
        background: var(--c-brand-bg);
        border-color: var(--c-brand-text);

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
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-control-border);
    box-sizing: border-box;
    font-size: var(--fs-23);
    line-height: var(--lh-body);
    color: var(--c-ink);
}

@media (prefers-reduced-motion: reduce) {
    .chip {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}
</style>
