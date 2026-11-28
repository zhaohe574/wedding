<template>
    <page-shell
        title="文案模板库"
        bg="page"
        :show-back="true"
        :pull-refresh="true"
        @refresh="onRefresh"
    >
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap tpl-page">
            <view v-if="loading" class="page-pad loading-pad">
                <base-skeleton :rows="4" variant="card" />
            </view>

            <base-empty
                v-else-if="error"
                status="error"
                :error-kind="view?.errorKind || 'unknown'"
                :error-text="view?.title"
                :desc="view?.desc"
                :primary-action="view?.primaryAction"
                fixed
                @primary="onPrimary"
                @retry="reload"
            />

            <view v-else-if="data" class="page-pad tpl-body">
                <!-- 关联场次变量代入 -->
                <base-card compact>
                    <section-header
                        title="场次信息自动代入"
                        :subtitle="
                            selectedTarget
                                ? `已代入：${selectedTarget.title}`
                                : '未选场次时保留占位符，点击任意场次实时替换'
                        "
                        flush
                    />
                    <scroll-view class="target-scroll" scroll-x :show-scrollbar="false">
                        <view class="target-row">
                            <view
                                hover-class="is-pressed"
                                class="target-chip hit-88"
                                :class="{ 'is-active': !selectedTargetKey }"
                                @tap="selectTarget('')"
                            >
                                <text class="target-chip__text">仅代入我的名片信息</text>
                            </view>
                            <view
                                v-for="t in data.recent_targets"
                                :key="t.target_key"
                                hover-class="is-pressed"
                                class="target-chip hit-88"
                                :class="{ 'is-active': selectedTargetKey === t.target_key }"
                                @tap="selectTarget(t.target_key)"
                            >
                                <text class="target-chip__text t-num"
                                    >{{ t.service_date }} · {{ t.customer_name || t.title }}</text
                                >
                            </view>
                        </view>
                    </scroll-view>
                </base-card>

                <!-- 场景筛选条 -->
                <chip-group
                    :items="sceneFilterItems"
                    :model-value="activeScene"
                    scroll
                    @change="onSceneChange"
                />

                <!-- 我的个人模板区 -->
                <view class="section-row">
                    <section-header
                        title="我的常用话术"
                        :subtitle="`共 ${filteredCustoms.length} 条`"
                        :bar="true"
                        flush
                    />
                    <base-button
                        text="新建模板"
                        variant="ink"
                        size="sm"
                        :block="false"
                        @click="openCreateSheet"
                    />
                </view>

                <base-card v-if="!filteredCustoms.length" compact>
                    <text class="empty-hint">
                        当前场景还没有个人模板，点击右上角“新建模板”或把下方统一话术“另存为我的”。
                    </text>
                </base-card>

                <view
                    v-if="filteredCustoms.length"
                    class="tpl-list"
                    :class="{ stagger: staggerIn, 'is-in': staggerIn }"
                >
                    <base-card
                        v-for="(item, index) in filteredCustoms"
                        :key="item.id"
                        compact
                        class="tpl-card"
                        :style="{ '--i': index }"
                    >
                        <view class="tpl-card__head">
                            <view class="tpl-card__title-wrap">
                                <text class="tpl-card__title">{{ item.title }}</text>
                                <text class="tpl-card__meta t-num"
                                    >已用 {{ item.use_count }} 次 · {{ item.update_time_text }}</text
                                >
                            </view>
                            <status-tag
                                :label="item.scene_label"
                                tone="primary"
                                size="sm"
                                :dot="false"
                            />
                        </view>

                        <text class="tpl-card__content">{{ renderContent(item.content) }}</text>

                        <view class="tpl-card__actions">
                            <base-button
                                text="一键复制"
                                variant="ink"
                                size="sm"
                                :block="false"
                                @click="handleCopyCustom(item)"
                            />
                            <base-button
                                text="编辑"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                @click="openEditSheet(item)"
                            />
                            <base-button
                                text="删除"
                                variant="danger"
                                size="sm"
                                :block="false"
                                :disabled="saving"
                                haptic-level="heavy"
                                @click="handleDeleteCustom(item)"
                            />
                        </view>
                    </base-card>
                </view>

                <!-- 平台统一预设模板区 -->
                <section-header
                    title="平台统一话术"
                    :subtitle="`共 ${filteredPresets.length} 条统一模板`"
                    :bar="true"
                    flush
                />

                <base-card v-if="!filteredPresets.length" compact>
                    <text class="empty-hint">当前场景暂无平台统一话术模板。</text>
                </base-card>

                <view
                    v-if="filteredPresets.length"
                    class="tpl-list"
                    :class="{ stagger: staggerIn, 'is-in': staggerIn }"
                >
                    <base-card
                        v-for="(item, index) in filteredPresets"
                        :key="item.preset_key"
                        compact
                        class="tpl-card"
                        :style="{ '--i': index }"
                    >
                        <view class="tpl-card__head">
                            <view class="tpl-card__title-wrap">
                                <text class="tpl-card__title">{{ item.title }}</text>
                            </view>
                            <status-tag
                                :label="item.scene_label"
                                tone="neutral"
                                size="sm"
                                :dot="false"
                            />
                        </view>

                        <text class="tpl-card__content">{{ renderContent(item.content) }}</text>

                        <view class="tpl-card__actions">
                            <base-button
                                text="一键复制"
                                variant="ink"
                                size="sm"
                                :block="false"
                                @click="handleCopyPreset(item)"
                            />
                            <base-button
                                text="另存为我的"
                                variant="ghost"
                                size="sm"
                                :block="false"
                                :disabled="saving"
                                haptic-level="medium"
                                @click="handleSavePresetToMine(item)"
                            />
                        </view>
                    </base-card>
                </view>
            </view>
        </view>

        <copy-template-sheet
            v-model="sheetOpen"
            :scenes="data?.scenes || []"
            :editing="editingItem"
            :saving="saving"
            @submit="handleSheetSubmit"
        />
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 服务人员文案模板库：后台统一配置话术 + 个人模板 CRUD + 场次变量实时代入。
 */
import { computed, onUnmounted, ref, watch } from 'vue'
import { onLoad, onShow } from '@dcloudio/uni-app'
import {
    deleteStaffCopyTemplate,
    getStaffCopyTemplateList,
    markStaffCopyTemplateUsed,
    saveStaffCopyTemplate,
    type StaffCopyTemplateCustom,
    type StaffCopyTemplateListResponse,
    type StaffCopyTemplatePreset
} from '@/api/staff'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import CopyTemplateSheet from '../../components/copy-template-sheet/copy-template-sheet.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { schemeColor } from '@/utils/scheme-color'

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const data = ref<StaffCopyTemplateListResponse | null>(null)
const loading = ref(true)
const error = ref(false)
const hasLoadedOnce = ref(false)
const saving = ref(false)
const activeScene = ref('all')
const selectedTargetKey = ref('')
const sheetOpen = ref(false)
const editingItem = ref<StaffCopyTemplateCustom | null>(null)

/* ---- 列表错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger(): void {
    if (staggerIn.value || staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        staggerIn.value = true
    }, 40)
}

watch(
    () => (data.value?.customs?.length || 0) + (data.value?.presets?.length || 0),
    (len) => {
        if (len) playStagger()
    }
)

onUnmounted(() => {
    if (staggerTimer) clearTimeout(staggerTimer)
})

let initialOrderId = 0
let initialManualId = 0
let firstShow = true

const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))

const sceneFilterItems = computed(() => [
    { label: '全部场景', value: 'all' },
    ...(data.value?.scenes || []).map((s) => ({ label: s.label, value: s.value }))
])

const selectedTarget = computed(() => {
    const list = data.value?.recent_targets || []
    return list.find((t) => t.target_key === selectedTargetKey.value) || null
})

const filteredCustoms = computed(() => {
    const list = data.value?.customs || []
    if (activeScene.value === 'all') return list
    return list.filter((item) => item.scene === activeScene.value)
})

const filteredPresets = computed(() => {
    const list = data.value?.presets || []
    if (activeScene.value === 'all') return list
    return list.filter((item) => item.scene === activeScene.value)
})

function renderContent(raw: string): string {
    let text = String(raw || '')
    const staffCtx = data.value?.staff_context
    if (staffCtx) {
        if (staffCtx.staff_name) text = text.replace(/\{staff_name\}/g, staffCtx.staff_name)
        if (staffCtx.category_name) {
            text = text.replace(/\{category_name\}/g, staffCtx.category_name)
        }
        if (staffCtx.price_text) text = text.replace(/\{price_text\}/g, staffCtx.price_text)
    }

    const t = selectedTarget.value
    if (t) {
        text = text
            .replace(/\{customer_name\}/g, t.customer_name || '新人')
            .replace(/\{service_date\}/g, t.service_date || '待定日期')
            .replace(/\{service_address\}/g, t.service_address || '待确定场地')
            .replace(/\{package_name\}/g, t.package_name || '专属婚礼服务')
    }
    return text
}

function matchInitialTarget(res: StaffCopyTemplateListResponse): void {
    const list = res.recent_targets || []
    if (initialOrderId > 0) {
        const hit = list.find((t) => Number(t.order_id) === initialOrderId)
        if (hit) {
            selectedTargetKey.value = hit.target_key
            return
        }
    }
    if (initialManualId > 0) {
        const hit = list.find((t) => Number(t.manual_schedule_id) === initialManualId)
        if (hit) {
            selectedTargetKey.value = hit.target_key
        }
    }
}

async function fetchTemplates(): Promise<void> {
    if (!guardAllowed.value) return
    loading.value = true
    error.value = false
    rawError.value = null
    try {
        const res = await getStaffCopyTemplateList()
        data.value = res
        if (!selectedTargetKey.value) {
            matchInitialTarget(res)
        }
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function reload(): void {
    void fetchTemplates()
}

function onSceneChange(val: string | number): void {
    activeScene.value = String(val || 'all')
}

function selectTarget(key: string): void {
    if (selectedTargetKey.value === key) return
    selectedTargetKey.value = key
}

function openCreateSheet(): void {
    editingItem.value = null
    sheetOpen.value = true
}

function openEditSheet(item: StaffCopyTemplateCustom): void {
    editingItem.value = item
    sheetOpen.value = true
}

async function handleSheetSubmit(payload: {
    id?: number
    scene: string
    title: string
    content: string
}): Promise<void> {
    if (saving.value) return
    saving.value = true
    try {
        await saveStaffCopyTemplate(payload)
        toast.success(payload.id ? '模板已更新' : '已保存为个人模板')
        sheetOpen.value = false
        editingItem.value = null
        await fetchTemplates()
    } catch (e: any) {
        toast.error(String(e?.message || '保存失败，请稍后重试'))
    } finally {
        saving.value = false
    }
}

function handleCopyCustom(item: StaffCopyTemplateCustom): void {
    const rendered = renderContent(item.content)
    uni.setClipboardData({
        data: rendered,
        success: () => {
            toast.success('已复制话术到剪贴板')
            item.use_count = Number(item.use_count || 0) + 1
            void markStaffCopyTemplateUsed({ id: item.id }).catch(() => {})
        }
    })
}

function handleCopyPreset(item: StaffCopyTemplatePreset): void {
    const rendered = renderContent(item.content)
    uni.setClipboardData({
        data: rendered,
        success: () => {
            toast.success('已复制话术到剪贴板')
            if (Number(item.id) > 0) {
                void markStaffCopyTemplateUsed({ id: item.id }).catch(() => {})
            }
        }
    })
}

async function handleSavePresetToMine(item: StaffCopyTemplatePreset): Promise<void> {
    if (saving.value) return
    saving.value = true
    try {
        await saveStaffCopyTemplate({
            scene: item.scene,
            title: item.title,
            content: item.content
        })
        toast.success('已收藏到我的常用话术')
        await fetchTemplates()
    } catch (e: any) {
        toast.error(String(e?.message || '收藏失败，请稍后重试'))
    } finally {
        saving.value = false
    }
}

function handleDeleteCustom(item: StaffCopyTemplateCustom): void {
    if (saving.value) return
    uni.showModal({
        title: '删除个人模板',
        content: `确认删除模板「${item.title}」？删除后不可恢复。`,
        confirmText: '确认删除',
        cancelText: '取消',
        // 删除属不可逆操作，uni.showModal 居中确认弹窗保持原生居中形态
        confirmColor: schemeColor('danger'),
        success: async (r) => {
            if (!r.confirm) return
            saving.value = true
            try {
                await deleteStaffCopyTemplate({ id: item.id })
                toast.success('模板已删除')
                await fetchTemplates()
            } catch (e: any) {
                toast.error(String(e?.message || '删除失败，请稍后重试'))
            } finally {
                saving.value = false
            }
        }
    })
}

function onPrimary(): void {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        reload()
    } else if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

async function init(forceGuard = false): Promise<void> {
    if (!(await verify(forceGuard))) return
    await fetchTemplates()
    hasLoadedOnce.value = true
}

onLoad((options?: Record<string, any>) => {
    firstShow = true
    initialOrderId = Number(options?.order_id || 0)
    initialManualId = Number(options?.manual_schedule_id || 0)
})

onShow(() => {
    if (firstShow) {
        firstShow = false
        void init()
        return
    }
    if (hasLoadedOnce.value) {
        void init(true)
    }
})

function onRefresh(done: () => void) {
    if (!guardAllowed.value) {
        done()
        return
    }
    Promise.resolve(fetchTemplates()).finally(done)
}
</script>

<style lang="scss" scoped>
.tpl-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.loading-pad {
    padding-top: var(--sp-3);
}

.tpl-body {
    padding-top: var(--sp-3);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.target-scroll {
    /* 上下各留 16rpx：给 chip 的 .hit-88 伪元素留出竖向空间，
       scroll-view 会裁掉溢出内容，不留白则 88rpx 热区被裁回 56rpx */
    margin-top: 0;
    padding: var(--sp-2) 0;
    white-space: nowrap;
}

.target-row {
    display: inline-flex;
    align-items: center;
    gap: var(--sp-2);
}

.target-chip {
    display: inline-flex;
    align-items: center;
    height: 56rpx;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.96);
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

.section-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
}

/* 两个模板列表共用错峰容器：卡片间距与 .tpl-body 的组间距一致 */
.tpl-list {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.empty-hint {
    font-size: var(--fs-xs);
    line-height: var(--lh-body);
    color: var(--c-ink-3);
}

.tpl-card {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);

    &__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__title-wrap {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__meta {
        font-size: var(--fs-20);
        color: var(--c-ink-3);
        font-variant-numeric: tabular-nums;
    }

    &__content {
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-surface-2);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
        white-space: pre-wrap;
        overflow-wrap: anywhere;
    }

    &__actions {
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-2);
        padding-top: var(--sp-1);
    }
}

/* v10 §3.13：减弱动效下按压不缩放 */
@media (prefers-reduced-motion: reduce) {
    .target-chip {
        transition: none;
    }

    .target-chip.is-pressed {
        transform: none;
    }
}
</style>
