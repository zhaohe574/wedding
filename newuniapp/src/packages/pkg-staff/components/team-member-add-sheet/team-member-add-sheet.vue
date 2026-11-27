<template>
    <!-- 添加团队成员：磁吸半屏抽屉。标题 / 关闭 / 拖拽由 snap-sheet 统一承担；
         本抽屉没有吸底按钮，因此不传 has-foot（mp-weixin 下 $slots 恒为空，必须显式声明） -->
    <snap-sheet v-model="visible" title="添加团队成员" :enable-full="true">
        <view class="search-row">
            <view class="search-input-wrap">
                <tn-icon name="search" :size="26" class="search-input-wrap__icon" />
                <input
                    v-model="keyword"
                    class="search-input-wrap__input"
                    placeholder="搜索姓名、工号或手机号"
                    placeholder-class="field__ph"
                    confirm-type="search"
                    @confirm="onSearch"
                />
            </view>
            <base-button
                text="搜索"
                variant="ink"
                size="sm"
                :block="false"
                :loading="loading"
                @click="onSearch"
            />
        </view>

        <view v-if="loading" class="loading-box">
            <base-skeleton :rows="3" variant="card" />
        </view>

        <view v-else-if="!candidates.length" class="empty-box">
            <text class="empty-box__text">
                暂无符合条件的可添加成员（需已启用且审核通过，且未加入其他有效团队）
            </text>
        </view>

        <view v-else class="cand-list">
            <view v-for="item in candidates" :key="item.staff_id" class="cand-row">
                <image
                    v-if="item.avatar"
                    :src="item.avatar"
                    class="cand-row__avatar"
                    mode="aspectFill"
                />
                <view v-else class="cand-row__avatar cand-row__avatar--text">
                    <text class="cand-row__initial">{{ item.name.slice(0, 1) || '师' }}</text>
                </view>

                <view class="cand-row__info">
                    <text class="cand-row__name">{{ item.name }}</text>
                    <text class="cand-row__meta t-num">
                        {{ item.category_name }} · {{ item.experience_years }}年 ·
                        {{ Number(item.rating || 5).toFixed(1) }}分
                    </text>
                    <text v-if="item.mobile_masked" class="cand-row__sub t-num">{{
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
    </snap-sheet>
</template>

<script setup lang="ts">
/**
 * 队长添加团队成员搜索与加入抽屉（无吸底按钮：整块内容由 snap-sheet 承载）。
 */
import { computed, ref, watch } from 'vue'
import {
    addStaffTeamMember,
    getStaffTeamMemberCandidates,
    type StaffTeamCandidateItem
} from '@/api/staff'
import { toast } from '@/composables/useToast'
import { haptic } from '@/utils/haptics'

const props = defineProps<{
    modelValue: boolean
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'added'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

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

function onSearch(): void {
    haptic('light', 'team-member-search')
    void fetchCandidates()
}

async function onAddMember(item: StaffTeamCandidateItem): Promise<void> {
    if (addingId.value > 0) return
    haptic('medium', 'team-member-add')
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
.search-row {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
}

.search-input-wrap {
    flex: 1;
    min-width: 0;
    height: 88rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface-2);
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    color: var(--c-ink-3);
    box-sizing: border-box;

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
    padding: var(--sp-3) 0 0;
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
    padding-top: var(--sp-3);
}

.cand-row {
    padding: var(--sp-3);
    border-radius: var(--r-md);
    border: 1rpx solid var(--c-card-border);
    background: var(--c-surface);
    box-shadow: var(--shadow-card);
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
    }
}
</style>
