<template>
    <tn-popup
        :model-value="modelValue"
        open-direction="bottom"
        bg-color="transparent"
        radius="0"
        :safe-area-inset-bottom="true"
        @update:model-value="onPopupChange"
    >
        <view class="sheet">
            <view class="sheet__head">
                <text class="sheet__title">添加团队成员</text>
                <view
                    hover-class="is-pressed"
                    class="sheet__close"
                    aria-role="button"
                    aria-label="关闭"
                    @tap="close"
                >
                    <tn-icon name="close" :size="28" class="sheet__close-icon" />
                </view>
            </view>

            <view class="search-row">
                <view class="search-input-wrap">
                    <tn-icon name="search" :size="26" class="search-input-wrap__icon" />
                    <input
                        v-model="keyword"
                        class="search-input-wrap__input"
                        placeholder="搜索姓名、工号或手机号"
                        placeholder-class="field__ph"
                        confirm-type="search"
                        @confirm="fetchCandidates"
                    />
                </view>
                <base-button
                    text="搜索"
                    variant="ink"
                    size="sm"
                    :block="false"
                    :loading="loading"
                    @click="fetchCandidates"
                />
            </view>

            <scroll-view class="sheet__body" scroll-y>
                <view v-if="loading" class="loading-box">
                    <base-skeleton :rows="3" />
                </view>

                <view v-else-if="!candidates.length" class="empty-box">
                    <text class="empty-box__text">
                        暂无符合条件的可添加成员（需已启用且审核通过，且未加入其他有效团队）
                    </text>
                </view>

                <view v-else class="cand-list">
                    <view
                        v-for="item in candidates"
                        :key="item.staff_id"
                        class="cand-row"
                    >
                        <image
                            v-if="item.avatar"
                            :src="item.avatar"
                            class="cand-row__avatar"
                            mode="aspectFill"
                        />
                        <view v-else class="cand-row__avatar cand-row__avatar--text">
                            <text class="cand-row__initial">{{
                                item.name.slice(0, 1) || '师'
                            }}</text>
                        </view>

                        <view class="cand-row__info">
                            <text class="cand-row__name">{{ item.name }}</text>
                            <text class="cand-row__meta">
                                {{ item.category_name }} · {{ item.experience_years }}年 ·
                                {{ Number(item.rating || 5).toFixed(1) }}分
                            </text>
                            <text v-if="item.mobile_masked" class="cand-row__sub">{{
                                item.mobile_masked
                            }}</text>
                        </view>

                        <base-button
                            :text="addingId === item.staff_id ? '加入中…' : '加入本队'"
                            variant="ink"
                            size="sm"
                            :block="false"
                            :loading="addingId === item.staff_id"
                            :disabled="addingId > 0"
                            @click="onAddMember(item)"
                        />
                    </view>
                </view>
            </scroll-view>
        </view>
    </tn-popup>
</template>

<script setup lang="ts">
/**
 * 队长添加团队成员搜索与加入弹窗。
 */
import { ref, watch } from 'vue'
import {
    addStaffTeamMember,
    getStaffTeamMemberCandidates,
    type StaffTeamCandidateItem
} from '@/api/staff'
import { toast } from '@/composables/useToast'

const props = defineProps<{
    modelValue: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'added'): void
}>()

const keyword = ref('')
const loading = ref(false)
const addingId = ref(0)
const candidates = ref<StaffTeamCandidateItem[]>([])

async function fetchCandidates(): Promise<void> {
    if (loading.value) return
    loading.value = true
    try {
        const res = await getStaffTeamMemberCandidates(
            keyword.value.trim() ? { keyword: keyword.value.trim() } : undefined
        )
        candidates.value = Array.isArray(res?.lists) ? res.lists : []
    } catch (e: any) {
        toast.error(String(e?.message || '搜索候选成员失败'))
        candidates.value = []
    } finally {
        loading.value = false
    }
}

watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            keyword.value = ''
            void fetchCandidates()
        }
    },
    { immediate: true }
)

function onPopupChange(val: boolean): void {
    if (!val && addingId.value > 0) return
    emit('update:modelValue', val)
}

function close(): void {
    if (addingId.value > 0) return
    emit('update:modelValue', false)
}

async function onAddMember(item: StaffTeamCandidateItem): Promise<void> {
    if (addingId.value > 0) return
    addingId.value = item.staff_id
    try {
        await addStaffTeamMember({ target_staff_id: item.staff_id })
        toast.success(`已将 ${item.name} 加入团队`)
        candidates.value = candidates.value.filter((c) => c.staff_id !== item.staff_id)
        emit('added')
    } catch (e: any) {
        toast.error(String(e?.message || '添加失败，请稍后重试'))
    } finally {
        addingId.value = 0
    }
}
</script>

<style lang="scss" scoped>
.sheet {
    background: var(--c-surface);
    padding: var(--sp-4) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
    border-radius: var(--r-lg) var(--r-lg) 0 0;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
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
        color: var(--c-ink-3);
    }

    &__close-icon {
        color: inherit;
    }

    &__body {
        max-height: 62vh;
        margin-top: var(--sp-3);
    }
}

.search-row {
    margin-top: var(--sp-3);
    display: flex;
    align-items: center;
    gap: var(--sp-2);
}

.search-input-wrap {
    flex: 1;
    min-width: 0;
    height: 76rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface-2);
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    color: var(--c-ink-3);

    &__icon {
        color: inherit;
        flex-shrink: 0;
    }

    &__input {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }
}

.loading-box {
    padding: var(--sp-2) 0;
}

.empty-box {
    padding: var(--sp-5) var(--sp-3);
    text-align: center;

    &__text {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
    }
}

.cand-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
}

.cand-row {
    padding: var(--sp-3);
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-line);
    background: var(--c-surface);
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__avatar {
        width: 80rpx;
        height: 80rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        flex-shrink: 0;

        &--text {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
        }
    }

    &__initial {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta,
    &__sub {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }
}
</style>
