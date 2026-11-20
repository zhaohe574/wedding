<template>
    <tn-popup
        :model-value="open"
        open-direction="bottom"
        :radius="24"
        :safe-area-inset-bottom="true"
        @update:model-value="onVisible"
        @close="close"
    >
        <view class="sheet">
            <view class="sheet__head">
                <text class="sheet__title">风格偏好</text>
                <tn-icon name="close" :size="34" @tap="close" />
            </view>

            <!-- 这句是 OR 语义的唯一澄清位，不可省、不可改成「同时满足」 -->
            <text class="sheet__sub">可多选，结果包含任一风格的老师</text>

            <scroll-view class="sheet__scroll" scroll-y>
                <view v-if="error" class="tip">
                    <text class="tip__text">标签没加载出来</text>
                    <text class="tip__link" @tap="emit('retry')">重试</text>
                    <text class="tip__weak">已选风格会保留，可直接点「确定」用它查询</text>
                </view>

                <view v-else-if="!tags.length && !loading" class="tip">
                    <text class="tip__text">{{ emptyText }}</text>
                    <text class="tip__link" @tap="emit('change-category')">换一个服务类型</text>
                </view>

                <!-- 已失效：后端仍在过滤但不展示，必须留一个可移除的入口 -->
                <view v-if="invalidTags.length" class="group">
                    <text class="group__name">已失效</text>
                    <view class="chips">
                        <view
                            v-for="t in invalidTags"
                            :key="`invalid-${t.id}`"
                            hover-class="is-pressed"
                            class="chip chip--invalid"
                            @tap="removeInvalid(t.id)"
                        >
                            <text class="chip__text">{{ t.name }}</text>
                            <text class="chip__badge">移除</text>
                        </view>
                    </view>
                </view>

                <view v-for="g in groups" :key="g.type" class="group">
                    <text class="group__name">{{ g.label }}</text>
                    <view class="chips">
                        <view
                            v-for="t in g.tags"
                            :key="t.id"
                            hover-class="is-pressed"
                            class="chip"
                            :class="{ 'is-on': draft.includes(t.id) }"
                            @tap="toggle(t.id)"
                        >
                            <text class="chip__text">{{ t.name }}</text>
                        </view>
                    </view>
                </view>
            </scroll-view>

            <view class="sheet__foot">
                <text v-if="draft.length" class="clear" @tap="clearDraft">清空已选</text>
                <base-button text="确定" variant="ink" size="lg" @click="commitAndClose" />
            </view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
/**
 * 风格偏好弹窗：多选（≤5），选中不关窗，关闭即完成。
 *
 * 三个必须遵守的约束：
 *  1. 展示按标签大类分「风格」「特长」两组；「其他(type=3)」整组不渲染。
 *  2. 多选语义是 OR（后端 whereIn + group by staff_id），所以副标题必须写「包含任一」。
 *  3. 「已失效」组：已选但不在可选集里的标签（被删/隐藏，或不再属于当前类型）。
 *     后端对已下架标签仍然过滤（getTagStaffIds 不 join style_tag），所以不能静默剔除，
 *     否则会出现「卡上有、弹窗里看不到、结果却真的在筛」的幽灵条件。
 */
import { computed, ref, watch } from 'vue'
import { toast } from '@/composables/useToast'
import { SERVICE_TAG_TYPE, type ServiceTagVO } from '@/api/types/catalog'

const props = withDefaults(
    defineProps<{
        open: boolean
        modelValue?: number[]
        /** 可选集：type ∈ {1,2} 且 category_id ∈ {0, 当前类型} */
        tags: ServiceTagVO[]
        /** 已选但不在可选集里的标签，单独成组 */
        invalidTags?: { id: number; name: string }[]
        max?: number
        /** 当前服务类型名，用于空态文案 */
        categoryName?: string
        loading?: boolean
        error?: boolean
    }>(),
    {
        modelValue: () => [],
        invalidTags: () => [],
        max: 5,
        categoryName: '',
        loading: false,
        error: false
    }
)

const emit = defineEmits<{
    (e: 'update:open', v: boolean): void
    (e: 'commit', ids: number[]): void
    (e: 'retry'): void
    (e: 'change-category'): void
}>()

const draft = ref<number[]>([])

watch(
    () => props.open,
    (v) => {
        if (v) draft.value = [...(props.modelValue || [])]
    },
    { immediate: true }
)

const groups = computed(() => {
    const defs = [
        { type: SERVICE_TAG_TYPE.Style, label: '风格' },
        { type: SERVICE_TAG_TYPE.Skill, label: '特长' }
    ]
    return defs
        .map((d) => ({ ...d, tags: props.tags.filter((t) => Number(t.type) === d.type) }))
        .filter((g) => g.tags.length > 0)
})

const emptyText = computed(() =>
    props.categoryName ? `「${props.categoryName}」暂无可选风格` : '暂无可选风格'
)

function toggle(id: number) {
    const idx = draft.value.indexOf(id)
    if (idx >= 0) {
        draft.value.splice(idx, 1)
        return
    }
    if (draft.value.length >= props.max) {
        toast.info(`最多选 ${props.max} 个风格`)
        return
    }
    draft.value.push(id)
}

function removeInvalid(id: number) {
    const idx = draft.value.indexOf(id)
    if (idx >= 0) draft.value.splice(idx, 1)
}

function clearDraft() {
    draft.value = []
}

function commitAndClose() {
    emit('commit', [...draft.value])
    close()
}

function close() {
    emit('update:open', false)
}

function onVisible(v: boolean) {
    if (!v) commitAndClose()
}
</script>

<style lang="scss" scoped>
.sheet {
    padding: 30rpx 42rpx calc(24rpx + var(--safe-bottom));

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: var(--c-ink-3);
        padding-bottom: 8rpx;
    }

    &__title {
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__sub {
        display: block;
        font-size: 20rpx;
        color: var(--c-ink-3);
        padding-bottom: 16rpx;
    }

    &__scroll {
        max-height: 56vh;
    }

    &__foot {
        display: flex;
        align-items: center;
        gap: 24rpx;
        padding-top: 20rpx;
    }
}

.group {
    padding-bottom: 20rpx;

    &__name {
        display: block;
        font-size: 20rpx;
        color: var(--c-ink-3);
        letter-spacing: 0.04em;
        padding-bottom: 12rpx;
    }
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 8rpx;
    height: 56rpx;
    padding: 0 22rpx;
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);

    &__text {
        font-size: var(--fs-21);
        color: var(--c-ink-2);
    }

    &__badge {
        font-size: var(--fs-16);
        color: var(--c-ink-3);
    }

    &.is-on {
        background: var(--c-ink);
        border-color: var(--c-ink);

        .chip__text {
            color: var(--c-on-dark);
        }
    }

    &--invalid {
        border-color: var(--c-line);
        background: var(--c-surface-2);

        .chip__text {
            color: var(--c-ink-3);
            text-decoration: line-through;
        }
    }
}

.clear {
    font-size: 20rpx;
    color: var(--c-ink-3);
    text-decoration: underline;
    padding: 20rpx 0;
    flex-shrink: 0;
}

.tip {
    padding: 40rpx 0;
    text-align: center;

    &__text {
        display: block;
        font-size: 24rpx;
        color: var(--c-ink-3);
    }

    &__link {
        display: inline-block;
        margin-top: 14rpx;
        font-size: var(--fs-22);
        color: var(--c-brand-text);
        text-decoration: underline;
    }

    &__weak {
        display: block;
        margin-top: 12rpx;
        font-size: 20rpx;
        color: var(--c-ink-3);
    }
}
</style>
