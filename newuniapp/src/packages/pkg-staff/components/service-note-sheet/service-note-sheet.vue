<template>
    <!-- 服务手记：磁吸半屏抽屉。标题 / 关闭 / 拖拽 / 脚部安全区由 snap-sheet 统一承担 -->
    <snap-sheet
        v-model="visible"
        :title="isEdit ? '编辑服务手记' : '写服务手记'"
        :enable-full="true"
        :has-foot="true"
    >
        <view class="field">
            <text class="field__label">手记分类 *</text>
            <view class="cat-list">
                <view
                    v-for="item in categories"
                    :key="item.value"
                    hover-class="is-pressed"
                    class="cat-chip"
                    :class="{ 'is-active': form.category === item.value }"
                    @tap="pickCategory(item.value)"
                >
                    <text class="cat-chip__text">{{ item.label }}</text>
                </view>
            </view>
        </view>

        <view class="field">
            <view class="field__head">
                <text class="field__label">手记内容 *</text>
                <text class="field__count t-num">{{ form.content.length }}/1000</text>
            </view>
            <textarea
                v-model="form.content"
                class="field__textarea"
                placeholder="记录新人特殊忌讳、彩排走位要点、音乐偏好或现场复盘细节"
                placeholder-class="field__ph"
                maxlength="1000"
            />
        </view>

        <view class="field">
            <view class="field__head">
                <text class="field__label">现场附图（选填，最多 4 张）</text>
                <text class="field__count t-num">{{ form.imageItems.length }}/4</text>
            </view>
            <view class="img-grid">
                <view
                    v-for="(img, idx) in form.imageItems"
                    :key="`${img.uri}-${idx}`"
                    class="img-slot"
                >
                    <image
                        :src="img.url || img.uri"
                        class="img-slot__pic"
                        mode="aspectFill"
                        @tap="previewImage(idx)"
                    />
                    <view
                        hover-class="is-pressed"
                        class="img-slot__del hit-88"
                        aria-role="button"
                        aria-label="移除图片"
                        @tap="removeImage(idx)"
                    >
                        <tn-icon name="close" :size="20" class="img-slot__del-icon" />
                    </view>
                </view>

                <view
                    v-if="form.imageItems.length < 4"
                    hover-class="is-pressed"
                    class="img-add"
                    @tap="chooseImages"
                >
                    <view class="img-add__badge">
                        <tn-icon
                            :name="uploading ? 'loading' : 'add'"
                            :size="24"
                            class="img-add__icon"
                        />
                    </view>
                    <text class="img-add__text">{{ uploading ? '上传中…' : '添加图片' }}</text>
                </view>
            </view>
        </view>

        <view
            hover-class="is-pressed"
            class="pin-row"
            :class="{ 'is-checked': form.is_pinned === 1 }"
            @tap="togglePinned"
        >
            <view class="pin-row__box">
                <tn-icon
                    v-if="form.is_pinned === 1"
                    name="success"
                    :size="22"
                    class="pin-row__icon"
                />
            </view>
            <view class="pin-row__texts">
                <text class="pin-row__title">置顶此手记到新人档案顶部</text>
                <text class="pin-row__desc">重要禁忌、关键联系人或现场必看提示建议置顶</text>
            </view>
        </view>

        <template #foot>
            <view class="foot">
                <view class="foot__btn">
                    <base-button text="取消" variant="ghost" size="md" block @click="close" />
                </view>
                <view class="foot__btn">
                    <base-button
                        :text="saving ? '保存中…' : '保存手记'"
                        variant="ink"
                        size="md"
                        block
                        :loading="saving"
                        :disabled="saving || uploading"
                        @click="submit"
                    />
                </view>
            </view>
        </template>
    </snap-sheet>
</template>

<script setup lang="ts">
/**
 * 服务手记新建 / 编辑底部抽屉。
 *
 * 抽屉自身的关闭（遮罩 / 关闭键 / 下拉）不按 saving / uploading 拦截：
 * snap-sheet 会自行收起并 emit，父级吞掉 update:modelValue 会把组件留在
 * 「已收起但 modelValue=true」的锁死态。防误关只落在脚部「取消」上。
 */
import { computed, reactive, ref, watch } from 'vue'
import { uploadImage } from '@/api/app'
import type { StaffServiceNoteItem } from '@/api/staff'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        categories: Array<{ value: string; label: string }>
        editing?: StaffServiceNoteItem | null
        saving?: boolean
    }>(),
    {
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
            category: string
            content: string
            images: string[]
            is_pinned: number
        }
    ): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const uploading = ref(false)
const isEdit = computed(() => Number(props.editing?.id || 0) > 0)

const form = reactive<{
    category: string
    content: string
    imageItems: Array<{ uri: string; url: string }>
    is_pinned: number
}>({
    category: 'communication',
    content: '',
    imageItems: [],
    is_pinned: 0
})

function resetForm(): void {
    const defaultCat = props.categories?.[0]?.value || 'communication'
    if (props.editing) {
        form.category = String(props.editing.category || defaultCat)
        form.content = String(props.editing.content || '')
        form.imageItems = Array.isArray(props.editing.image_items)
            ? props.editing.image_items.map((it) => ({
                  uri: String(it.uri || it.url || ''),
                  url: String(it.url || it.uri || '')
              }))
            : []
        form.is_pinned = Number(props.editing.is_pinned) === 1 ? 1 : 0
        return
    }
    form.category = defaultCat
    form.content = ''
    form.imageItems = []
    form.is_pinned = 0
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) resetForm()
    },
    { immediate: true }
)

function close(): void {
    if (props.saving || uploading.value) return
    emit('update:modelValue', false)
}

function pickCategory(value: string): void {
    if (form.category === value) return
    haptic('light', 'service-note-category')
    form.category = value
}

function togglePinned(): void {
    haptic('light', 'service-note-pin')
    form.is_pinned = form.is_pinned === 1 ? 0 : 1
}

function removeImage(idx: number): void {
    haptic('light', 'service-note-image')
    form.imageItems.splice(idx, 1)
}

function previewImage(idx: number): void {
    const urls = form.imageItems.map((it) => it.url || it.uri).filter(Boolean)
    if (!urls.length) return
    uni.previewImage({ urls, current: urls[idx] || urls[0] })
}

function chooseImages(): void {
    if (uploading.value) return
    const remain = Math.max(0, 4 - form.imageItems.length)
    if (remain <= 0) return

    haptic('light', 'service-note-image-add')
    uni.chooseImage({
        count: remain,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: async (res) => {
            const paths = Array.isArray(res.tempFilePaths) ? res.tempFilePaths : []
            if (!paths.length) return
            uploading.value = true
            try {
                for (const p of paths) {
                    const uploaded = await uploadImage(p)
                    form.imageItems.push({
                        uri: uploaded.uri,
                        url: uploaded.url || uploaded.uri
                    })
                }
            } catch (e: any) {
                toast.error(String(e?.message || '图片上传失败，请重试'))
            } finally {
                uploading.value = false
            }
        }
    })
}

function submit(): void {
    if (props.saving || uploading.value) return
    const category = form.category.trim()
    const content = form.content.trim()
    if (!category) {
        toast.warning('请选择手记分类')
        return
    }
    if (!content) {
        toast.warning('请输入手记正文内容')
        return
    }

    emit('submit', {
        id: isEdit.value ? Number(props.editing?.id || 0) : undefined,
        category,
        content,
        images: form.imageItems.map((it) => it.uri).filter(Boolean),
        is_pinned: form.is_pinned
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

    &__textarea {
        width: 100%;
        min-height: 240rpx;
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

.cat-list {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);
}

.cat-chip {
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

        .cat-chip__text {
            color: var(--c-brand-text);
            font-weight: var(--fw-semibold);
        }
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.img-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--sp-2);
}

.img-slot {
    position: relative;
    width: 100%;
    height: 144rpx;
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-line);
    box-sizing: border-box;
    overflow: hidden;
    background: var(--c-surface-2);

    &__pic {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__del {
        position: absolute;
        top: 6rpx;
        right: 6rpx;
        width: 36rpx;
        height: 36rpx;
        border-radius: var(--r-pill);
        background: var(--c-mask);
        color: var(--c-on-dark);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__del-icon {
        color: inherit;
    }
}

.img-add {
    height: 144rpx;
    border-radius: var(--r-sm);
    border: 1rpx dashed var(--c-control-border);
    background: var(--c-surface-2);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8rpx;
    color: var(--c-ink-2);
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.97);
    }

    &__badge {
        width: 48rpx;
        height: 48rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface);
        border: 1rpx solid var(--c-line);
        color: var(--c-brand-text);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__icon {
        color: inherit;
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.pin-row {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-2);
    min-height: 88rpx;
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-sm);
    border: 1rpx solid var(--c-card-border);
    background: var(--c-surface-2);
    box-sizing: border-box;
    transition: background-color var(--dur-fast) var(--ease-out);

    &__box {
        width: 36rpx;
        height: 36rpx;
        margin-top: 2rpx;
        border-radius: var(--r-xs);
        border: 1rpx solid var(--c-control-border);
        background: var(--c-surface);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--c-ink-inverse);
    }

    &__icon {
        color: inherit;
    }

    &.is-checked {
        border-color: var(--c-primary-border);
        background: var(--c-brand-bg);

        .pin-row__box {
            border-color: var(--c-brand-text);
            background: var(--c-brand-text);
        }
    }

    &__texts {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

@media (prefers-reduced-motion: reduce) {
    .cat-chip,
    .img-add {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}
</style>
