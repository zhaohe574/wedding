<template>
    <!-- 跟进提醒：磁吸半屏抽屉。标题 / 关闭 / 拖拽 / 脚部安全区由 snap-sheet 统一承担 -->
    <snap-sheet
        v-model="visible"
        :title="isEdit ? '编辑跟进提醒' : '新建跟进提醒'"
        :enable-full="true"
        :has-foot="true"
    >
        <!-- 关联新人场次 -->
        <view v-if="lockedTarget" class="field">
            <text class="field__label">关联场次</text>
            <view class="locked-target">
                <text class="locked-target__text">{{ lockedTarget.label }}</text>
            </view>
        </view>
        <view v-else-if="recentTargets.length" class="field">
            <text class="field__label">关联新人场次（选填）</text>
            <scroll-view class="target-scroll" scroll-x :show-scrollbar="false">
                <view class="target-row">
                    <view
                        hover-class="is-pressed"
                        class="target-chip"
                        :class="{ 'is-active': form.source_type === 'general' }"
                        @tap="selectGeneralTarget"
                    >
                        <text class="target-chip__text">通用工作提醒</text>
                    </view>
                    <view
                        v-for="t in recentTargets"
                        :key="t.target_key"
                        hover-class="is-pressed"
                        class="target-chip"
                        :class="{
                            'is-active':
                                form.source_type === t.source_type &&
                                form.target_id === t.target_id
                        }"
                        @tap="selectTarget(t)"
                    >
                        <text class="target-chip__text">{{ t.title }}</text>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view class="field">
            <text class="field__label">提醒类型 *</text>
            <view class="type-list">
                <view
                    v-for="item in remindTypes"
                    :key="item.value"
                    hover-class="is-pressed"
                    class="type-chip"
                    :class="{ 'is-active': form.remind_type === item.value }"
                    @tap="pickRemindType(item)"
                >
                    <text class="type-chip__text">{{ item.label }}</text>
                </view>
            </view>
        </view>

        <view class="field">
            <text class="field__label">提醒事项标题 *</text>
            <input
                v-model="form.title"
                class="field__input"
                placeholder="如：确认仪式音乐及双方家长致辞名单"
                placeholder-class="field__ph"
                maxlength="100"
            />
        </view>

        <view class="field">
            <text class="field__label">提醒日期 *</text>
            <view v-if="quickDateOptions.length" class="quick-dates">
                <view
                    v-for="qd in quickDateOptions"
                    :key="qd.label"
                    hover-class="is-pressed"
                    class="quick-date-chip"
                    :class="{ 'is-active': form.remind_date === qd.date }"
                    @tap="pickQuickDate(qd.date)"
                >
                    <text class="quick-date-chip__text">{{ qd.label }}</text>
                </view>
            </view>

            <view class="datetime-row">
                <picker mode="date" :value="form.remind_date || todayStr" @change="onPickDate">
                    <view hover-class="is-pressed" class="picker-box">
                        <tn-icon name="calendar" :size="24" class="picker-box__icon" />
                        <text class="picker-box__text t-num">{{
                            form.remind_date || '选择日期'
                        }}</text>
                    </view>
                </picker>

                <picker mode="time" :value="form.remind_time" @change="onPickTime">
                    <view hover-class="is-pressed" class="picker-box">
                        <tn-icon name="clock" :size="24" class="picker-box__icon" />
                        <text class="picker-box__text t-num">{{ form.remind_time }}</text>
                    </view>
                </picker>
            </view>
        </view>

        <view class="field">
            <view class="field__head">
                <text class="field__label">补充备注（选填）</text>
                <text class="field__count t-num">{{ form.remark.length }}/500</text>
            </view>
            <textarea
                v-model="form.remark"
                class="field__textarea"
                placeholder="可记录需沟通的问题清单、待确认环节等"
                placeholder-class="field__ph"
                maxlength="500"
            />
        </view>

        <template #foot>
            <view class="foot">
                <view class="foot__btn">
                    <base-button text="取消" variant="ghost" size="md" block @click="close" />
                </view>
                <view class="foot__btn">
                    <base-button
                        :text="saving ? '保存中…' : '保存提醒'"
                        variant="ink"
                        size="md"
                        block
                        :loading="saving"
                        :disabled="saving"
                        @click="submit"
                    />
                </view>
            </view>
        </template>
    </snap-sheet>
</template>

<script setup lang="ts">
/**
 * 客户跟进提醒新建 / 编辑底部抽屉。
 *
 * 抽屉自身的关闭（遮罩 / 关闭键 / 下拉）不按 saving 拦截：snap-sheet 会自行收起并 emit，
 * 父级吞掉 update:modelValue 会把组件留在「已收起但 modelValue=true」的锁死态。
 * 防误关只落在脚部「取消」这类显式动作上。
 */
import { computed, reactive, watch } from 'vue'
import type { StaffFollowupReminderItem, StaffFollowupTargetOption } from '@/api/staff'
import { toast } from '@/composables/useToast'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        remindTypes: Array<{ value: string; label: string }>
        recentTargets?: StaffFollowupTargetOption[]
        lockedTarget?: {
            source_type: 'order' | 'manual'
            target_id: number
            label: string
            service_date?: string
        } | null
        editing?: StaffFollowupReminderItem | null
        saving?: boolean
    }>(),
    {
        recentTargets: () => [],
        lockedTarget: null,
        editing: null,
        saving: false
    }
)

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (
        e: 'submit',
        payload: {
            id?: number
            source_type: 'order' | 'manual' | 'general'
            target_id: number
            remind_type: string
            title: string
            remind_date: string
            remind_time: string
            remark: string
        }
    ): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

function offsetDateStr(daysFromToday: number, baseDateStr?: string): string {
    const base = baseDateStr ? new Date(`${baseDateStr}T00:00:00`) : new Date()
    if (Number.isNaN(base.getTime())) return ''
    base.setDate(base.getDate() + daysFromToday)
    const y = base.getFullYear()
    const m = String(base.getMonth() + 1).padStart(2, '0')
    const d = String(base.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

const todayStr = computed(() => offsetDateStr(0))
const isEdit = computed(() => Number(props.editing?.id || 0) > 0)

const form = reactive<{
    source_type: 'order' | 'manual' | 'general'
    target_id: number
    remind_type: string
    title: string
    remind_date: string
    remind_time: string
    remark: string
}>({
    source_type: 'general',
    target_id: 0,
    remind_type: 'pre_comm',
    title: '',
    remind_date: '',
    remind_time: '10:00',
    remark: ''
})

const currentServiceDate = computed(() => {
    if (props.lockedTarget?.service_date) return props.lockedTarget.service_date
    if (form.source_type === 'general' || form.target_id <= 0) return ''
    const hit = props.recentTargets.find(
        (t) => t.source_type === form.source_type && Number(t.target_id) === form.target_id
    )
    return hit?.service_date || ''
})

const quickDateOptions = computed(() => {
    const opts: Array<{ label: string; date: string }> = [
        { label: '今天', date: offsetDateStr(0) },
        { label: '明天', date: offsetDateStr(1) },
        { label: '3 天后', date: offsetDateStr(3) }
    ]
    const sDate = currentServiceDate.value
    if (sDate) {
        const before7 = offsetDateStr(-7, sDate)
        const before1 = offsetDateStr(-1, sDate)
        if (before7 && before7 >= todayStr.value) {
            opts.push({ label: '婚前7天', date: before7 })
        }
        if (before1 && before1 >= todayStr.value) {
            opts.push({ label: '婚前1天', date: before1 })
        }
    }
    return opts
})

function resetForm(): void {
    const defaultType = props.remindTypes?.[0]?.value || 'pre_comm'
    if (props.editing) {
        form.source_type = (props.editing.source_type || 'general') as any
        form.target_id = Number(props.editing.target_id || 0)
        form.remind_type = String(props.editing.remind_type || defaultType)
        form.title = String(props.editing.title || '')
        form.remind_date = String(props.editing.remind_date || offsetDateStr(1))
        form.remind_time = String(props.editing.remind_time || '10:00')
        form.remark = String(props.editing.remark || '')
        return
    }
    if (props.lockedTarget) {
        form.source_type = props.lockedTarget.source_type
        form.target_id = props.lockedTarget.target_id
    } else {
        form.source_type = 'general'
        form.target_id = 0
    }
    form.remind_type = defaultType
    form.title = ''
    form.remind_date = offsetDateStr(1)
    form.remind_time = '10:00'
    form.remark = ''
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) resetForm()
    },
    { immediate: true }
)

function close(): void {
    if (props.saving) return
    emit('update:modelValue', false)
}

function selectGeneralTarget(): void {
    if (form.source_type === 'general') return
    form.source_type = 'general'
    form.target_id = 0
}

function selectTarget(t: StaffFollowupTargetOption): void {
    if (form.source_type === t.source_type && form.target_id === Number(t.target_id || 0)) return
    form.source_type = t.source_type
    form.target_id = Number(t.target_id || 0)
}

function pickRemindType(item: { value: string; label: string }): void {
    if (form.remind_type === item.value) return
    const prevLabel = props.remindTypes.find((r) => r.value === form.remind_type)?.label || ''
    form.remind_type = item.value
    if (!form.title.trim() || form.title.trim() === prevLabel) {
        form.title = item.label
    }
}

function pickQuickDate(date: string): void {
    if (form.remind_date === date) return
    form.remind_date = date
}

function onPickDate(e: any): void {
    const val = String(e?.detail?.value || '').trim().slice(0, 10)
    if (val) form.remind_date = val
}

function onPickTime(e: any): void {
    const val = String(e?.detail?.value || '').trim().slice(0, 5)
    if (val) form.remind_time = val
}

function submit(): void {
    if (props.saving) return
    const title = form.title.trim()
    const remindDate = form.remind_date.trim()
    if (!title) {
        toast.warning('请输入提醒事项标题')
        return
    }
    if (!/^\d{4}-\d{2}-\d{2}$/.test(remindDate)) {
        toast.warning('请选择提醒日期')
        return
    }

    emit('submit', {
        id: isEdit.value ? Number(props.editing?.id || 0) : undefined,
        source_type: form.source_type,
        target_id: form.source_type === 'general' ? 0 : form.target_id,
        remind_type: form.remind_type,
        title,
        remind_date: remindDate,
        remind_time: form.remind_time || '10:00',
        remark: form.remark.trim()
    })
}
</script>

<style lang="scss" scoped>
.foot {
    display: flex;
    gap: var(--sp-2);
}

.foot__btn {
    flex: 1;
    min-width: 0;
}

.field {
    & + & {
        margin-top: var(--sp-3);
    }

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-1);
    }

    &__label {
        display: block;
        margin-bottom: var(--sp-1);
        font-size: var(--fs-xs);
        font-weight: var(--fw-medium);
        color: var(--c-ink-2);
    }

    &__count {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__input {
        width: 100%;
        height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        color: var(--c-ink);
        box-sizing: border-box;
    }

    &__textarea {
        width: 100%;
        min-height: 152rpx;
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink);
        box-sizing: border-box;
    }
}

.locked-target {
    padding: var(--sp-2) var(--sp-3);
    border-radius: var(--r-sm);
    background: var(--c-brand-bg);
    border: 1rpx solid var(--c-primary-border);

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-brand-text);
        font-weight: var(--fw-medium);
    }
}

.target-scroll {
    white-space: nowrap;
}

.target-row {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-2);
}

/* 场次 / 类型 / 快捷日期芯片：热区统一 88rpx，视觉仍是胶囊 */
.target-chip {
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

    &.is-pressed {
        transform: scale(0.97);
    }

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .target-chip__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.type-list,
.quick-dates {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.quick-dates {
    margin-bottom: var(--sp-2);
}

.type-chip,
.quick-date-chip {
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

    &.is-pressed {
        transform: scale(0.97);
    }

    &.is-active {
        border-color: var(--c-brand-text);
        background: var(--c-brand-bg);

        .type-chip__text,
        .quick-date-chip__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.datetime-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--sp-2);
}

.picker-box {
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    color: var(--c-ink-2);
    box-sizing: border-box;

    &__icon {
        color: inherit;
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink);
    }
}

@media (prefers-reduced-motion: reduce) {
    .target-chip,
    .type-chip,
    .quick-date-chip {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}
</style>
