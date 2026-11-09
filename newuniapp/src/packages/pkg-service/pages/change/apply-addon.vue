<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell title="申请加项" bg="page" :show-back="true" :bottom-bar="true">
        <view class="page-wrap apply">
            <base-card>
                <!-- 服务日期：原生 picker 放在 form-field 的默认插槽里（标签/错误位统一，控件不变） -->
                <form-field label="服务日期" :required="true">
                    <picker
                        mode="date"
                        :value="form.service_date"
                        :start="today"
                        @change="onDateChange"
                    >
                        <view class="picker-row">
                            <text
                                :class="form.service_date ? 'picker-row__text' : 'picker-row__ph'"
                            >
                                {{ form.service_date || '请选择日期' }}
                            </text>
                            <tn-icon name="right" :size="24" />
                        </view>
                    </picker>
                </form-field>

                <!-- 服务人员：form-field 内置 picker（只负责展示与 tap，选择器由页面打开） -->
                <form-field
                    label="服务人员"
                    type="picker"
                    :required="true"
                    :model-value="selectedStaff?.name || ''"
                    placeholder="请选择"
                    @tap="openStaffPicker"
                />

                <!-- 服务套餐：依赖服务人员。禁用时不能只「点不动」——行内要给原因，
                     否则用户只会觉得坏了（P1 §3-12）。 -->
                <form-field
                    label="服务套餐"
                    type="picker"
                    :required="true"
                    :disabled="!selectedStaff"
                    :model-value="selectedPackage?.name || ''"
                    :placeholder="selectedStaff ? '请选择' : '请先选择服务人员'"
                    :hint="selectedStaff ? '' : '套餐按所选人员的可服务范围过滤，先选人员才能列出'"
                    @tap="onPackageRowTap"
                />
            </base-card>

            <base-card v-if="selectedPackage">
                <view class="summary">
                    <view class="summary__body">
                        <text class="summary__name">{{ selectedPackage.name }}</text>
                        <text v-if="selectedPackage.duration_desc" class="summary__meta">
                            {{ selectedPackage.duration_desc }}
                        </text>
                    </view>
                    <price size="lg" :content="selectedPackage.price ?? 0" />
                </view>
            </base-card>

            <base-card>
                <!-- 施工单 §3-12 写「加项原因 → 默认 row」，但它是 textarea，且 §P3 规则明写
                     「textarea → stack」。按字段类型取 stack（row 下控件只剩约 460rpx，
                     255 字的长文本会在窄栏里反复折行）。 -->
                <form-field
                    label="加项原因（选填）"
                    type="textarea"
                    layout="stack"
                    placeholder="如：增加一位督导 / 追加摄影跟拍"
                    :maxlength="255"
                    count
                    :model-value="form.reason"
                    @update:model-value="(v: string) => (form.reason = v)"
                />
            </base-card>

            <view class="tips">
                <!-- v1 u-icon → v2 tn-icon：info-circle → tips -->
                <tn-icon name="tips" :size="24" />
                <text class="tips__text">加项需商家审核，审核通过后生成补充金额，需另行支付。</text>
            </view>

            <view class="submit">
                <base-button
                    text="提交申请"
                    variant="ink"
                    size="lg"
                    :loading="submitting"
                    @click="handleSubmit"
                />
            </view>

            <!-- 人员选择 -->
            <view v-if="staffVisible" class="mask" @tap="staffVisible = false">
                <view class="sheet" @tap.stop>
                    <text class="sheet__title">选择服务人员</text>
                    <input
                        v-model="keyword"
                        class="search"
                        placeholder="搜索姓名"
                        placeholder-class="search__ph"
                        confirm-type="search"
                        @confirm="searchStaff"
                    />
                    <scroll-view class="sheet__list" scroll-y>
                        <view v-if="staffLoading" class="sheet__hint">
                            <text class="sheet__hint-text">加载中…</text>
                        </view>
                        <view v-else-if="!staffList.length" class="sheet__hint">
                            <text class="sheet__hint-text">没有找到人员</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            v-for="s in staffList"
                            :key="s.id"
                            class="cand"
                            @tap="chooseStaff(s)"
                        >
                            <image
                                class="cand__avatar"
                                :src="imageUrl(s.avatar)"
                                mode="aspectFill"
                            />
                            <view class="cand__body">
                                <text class="cand__name">{{ s.name }}</text>
                                <text class="cand__meta"
                                    >★ {{ s.rating || '-' }} · {{ s.order_count || 0 }} 单</text
                                >
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <!-- 套餐选择 -->
            <view v-if="packageVisible" class="mask" @tap="packageVisible = false">
                <view class="sheet" @tap.stop>
                    <text class="sheet__title">选择套餐</text>
                    <scroll-view class="sheet__list" scroll-y>
                        <view v-if="!packageList.length" class="sheet__hint">
                            <text class="sheet__hint-text">该人员暂无可选套餐</text>
                        </view>
                        <view
                            hover-class="is-pressed"
                            v-for="p in packageList"
                            :key="p.package_id || p.id"
                            class="cand"
                            @tap="choosePackage(p)"
                        >
                            <view class="cand__body">
                                <text class="cand__name">{{ p.package?.name || p.name }}</text>
                                <text v-if="p.duration_desc" class="cand__meta">{{
                                    p.duration_desc
                                }}</text>
                            </view>
                            <price size="sm" :content="p.price ?? p.package?.price ?? 0" />
                        </view>
                    </scroll-view>
                </view>
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 申请加项：order_id + staff_id + package_id + service_date + reason? */
import { reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { applyAddItem } from '@/api/change'
import { getStaffList, getStaffPackages } from '@/api/catalog'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

const orderId = ref(0)
const submitting = ref(false)
const form = reactive({ service_date: '', reason: '' })

const selectedStaff = ref<any>(null)
const selectedPackage = ref<any>(null)

const staffVisible = ref(false)
const staffLoading = ref(false)
const staffList = ref<any[]>([])
const keyword = ref('')

const packageVisible = ref(false)
const packageList = ref<any[]>([])

const today = (() => {
    const d = new Date()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${d.getFullYear()}-${m}-${day}`
})()

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function onDateChange(e: any) {
    form.service_date = String(e?.detail?.value || '')
}

async function openStaffPicker() {
    staffVisible.value = true
    if (!staffList.value.length) await searchStaff()
}

async function searchStaff() {
    staffLoading.value = true
    try {
        const res = await getStaffList({
            page_no: 1,
            page_size: 20,
            keyword: keyword.value || undefined
        })
        const raw: any = res
        staffList.value = Array.isArray(raw) ? raw : raw?.lists || raw?.data || []
    } catch {
        staffList.value = []
    } finally {
        staffLoading.value = false
    }
}

function chooseStaff(s: any) {
    selectedStaff.value = s
    selectedPackage.value = null
    staffVisible.value = false
    openPackagePicker()
}

/** 套餐行点击：未选人员时直接返回 —— 行内 hint 已常显示原因，再弹 toast 就是双反馈 */
function onPackageRowTap() {
    if (!selectedStaff.value) return
    openPackagePicker()
}

async function openPackagePicker() {
    if (!selectedStaff.value) return
    packageVisible.value = true
    packageList.value = []
    try {
        const res = await getStaffPackages({ staff_id: selectedStaff.value.id })
        packageList.value = Array.isArray(res) ? res : []
    } catch {
        packageList.value = []
    }
}

function choosePackage(p: any) {
    selectedPackage.value = {
        id: p.package_id || p.id,
        name: p.package?.name || p.name,
        price: p.price ?? p.package?.price ?? 0,
        duration_desc: p.duration_desc
    }
    packageVisible.value = false
}

async function handleSubmit() {
    if (submitting.value) return
    if (!form.service_date) {
        toast.info('请选择服务日期')
        return
    }
    if (!selectedStaff.value || !selectedPackage.value) {
        toast.info('请选择人员与套餐')
        return
    }
    submitting.value = true
    try {
        await applyAddItem({
            order_id: orderId.value,
            staff_id: selectedStaff.value.id,
            package_id: selectedPackage.value.id,
            service_date: form.service_date,
            reason: form.reason.trim() || undefined
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
}

.picker-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    /* 右侧箭头；&-text / &-ph 各自声明了颜色 */
    color: var(--c-ink-3);
    min-height: 88rpx;
    padding: 0 var(--sp-3);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    box-sizing: border-box;

    &__text,
    &__ph {
        min-width: 0;
        font-size: var(--fs-md);
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

.row {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 96rpx;
    padding: var(--sp-2) 0;
    border-bottom: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &--last {
        border-bottom: none;
    }

    &:active {
        opacity: 0.8;
    }

    &__label {
        width: 120rpx;
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }

    &__value {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: var(--sp-2);

        .picker-row__text,
        .picker-row__ph {
            flex: 1;
            text-align: right;
        }
    }
}

.summary {
    display: flex;
    align-items: center;
    gap: var(--sp-3);

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-md);
        font-weight: 500;
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow-wrap: anywhere;
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.tips {
    display: flex;
    align-items: flex-start;
    gap: var(--sp-1);
    padding: 0 var(--sp-1);
    /* 提示图标与文字同为 --c-ink-3；.tips__text 自己声明了颜色 */
    color: var(--c-ink-3);

    &__text {
        flex: 1;
        min-width: 0;
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
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    border-top: 1rpx solid var(--c-line);
    background: var(--c-surface);
    box-sizing: border-box;
}

.mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 200;
    display: flex;
    align-items: flex-end;
    background: var(--c-mask);
}

.sheet {
    width: 100%;
    max-height: 80vh;
    padding: var(--sp-4) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
    border-radius: var(--r-lg) var(--r-lg) 0 0;
    background: var(--c-surface);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    box-sizing: border-box;

    &__title {
        font-size: var(--fs-lg);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__list {
        max-height: 52vh;
    }

    &__hint {
        padding: var(--sp-5);
        text-align: center;

        &-text {
            font-size: var(--fs-xs);
            color: var(--c-ink-3);
        }
    }
}

.search {
    width: 100%;
    height: 80rpx;
    padding: 0 var(--sp-3);
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    box-sizing: border-box;
    font-size: var(--fs-xs);
    color: var(--c-ink);

    &__ph {
        color: var(--c-ink-3);
    }
}

.cand {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 112rpx;
    padding: var(--sp-3) 0;
    border-bottom: 1rpx solid var(--c-line);
    box-sizing: border-box;

    &:active {
        opacity: 0.8;
    }

    &__avatar {
        width: 88rpx;
        height: 88rpx;
        border-radius: var(--r-pill);
        background: var(--c-neutral-bg);
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__name {
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink);
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    &__meta {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}
</style>
