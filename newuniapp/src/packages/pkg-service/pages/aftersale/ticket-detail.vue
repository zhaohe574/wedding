<template>
    <!-- 头部统一：走 page-shell 的 nav-bar，不再依赖系统导航 -->
    <page-shell
        title="工单详情"
        bg="page"
        :show-back="true"
        :bottom-bar="showBottomBar"
        :pull-refresh="true"
        @refresh="onRefresh"
    >
        <view class="page-wrap ticket-detail">
            <view v-if="loading" class="detail-pad">
                <base-skeleton :rows="4" />
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
                @retry="load"
            />

            <template v-else>
                <view class="detail-pad body">
                    <!-- 内嵌墨金圆角头卡 -->
                    <view class="hero-card">
                        <view class="hero-card__top">
                            <text class="hero-card__eyebrow">SERVICE TICKET</text>
                            <status-tag :label="statusLabel" :tone="statusTone" size="md" />
                        </view>
                        <text class="hero-card__title">{{ data.title || '服务工单' }}</text>
                        <view class="hero-card__meta">
                            <text class="hero-card__sn">{{ data.ticket_sn || '—' }}</text>
                            <text v-if="data.type" class="hero-card__type">{{ typeText }}</text>
                        </view>
                    </view>

                    <base-card>
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">工单信息</text>
                        </view>
                        <view class="kv">
                            <text class="kv__k">提交时间</text>
                            <text class="kv__v t-num">{{ data.create_time || '—' }}</text>
                        </view>
                        <view v-if="data.type" class="kv">
                            <text class="kv__k">工单类型</text>
                            <text class="kv__v">{{ typeText }}</text>
                        </view>
                        <view v-if="data.order_sn" class="kv">
                            <text class="kv__k">关联订单</text>
                            <text class="kv__v kv__v--serif t-num">{{ data.order_sn }}</text>
                        </view>
                    </base-card>

                    <base-card v-if="data.content">
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">问题描述</text>
                        </view>
                        <text class="block-text">{{ data.content }}</text>
                    </base-card>

                    <base-card v-if="images.length">
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">附件凭证</text>
                        </view>
                        <view class="images">
                            <image
                                v-for="(img, i) in images"
                                :key="i"
                                class="images__item"
                                :src="imageUrl(img)"
                                mode="aspectFill"
                                @tap="preview(i)"
                            />
                        </view>
                    </base-card>

                    <!-- 处理记录时间轴 -->
                    <base-card v-if="logs.length">
                        <view class="sec-title">
                            <view class="sec-title__bar" />
                            <text class="sec-title__text">处理记录</text>
                        </view>
                        <view class="timeline">
                            <view
                                v-for="(l, i) in logs"
                                :key="i"
                                class="log"
                                :class="{
                                    'log--first': i === 0,
                                    'log--last': i === logs.length - 1
                                }"
                            >
                                <view class="log__rail">
                                    <view class="log__dot" :class="{ 'is-latest': i === 0 }" />
                                </view>
                                <view class="log__body">
                                    <text class="log__text">{{
                                        l.content || l.remark || l.action_desc
                                    }}</text>
                                    <text class="log__time t-num">{{ l.create_time }}</text>
                                </view>
                            </view>
                        </view>
                    </base-card>
                </view>
            </template>

            <!-- 确认完成弹层 -->
            <view v-if="confirmVisible" class="mask" @tap="confirmVisible = false">
                <view class="sheet" @tap.stop>
                    <view class="sheet__handle" />
                    <text class="sheet__title">确认处理结果</text>
                    <text class="sheet__hint">请为本次处理打分</text>
                    <view class="stars">
                        <view
                            hover-class="is-pressed"
                            :hover-start-time="20"
                            :hover-stay-time="70"
                            :hover-stop-propagation="true"
                            v-for="n in 5"
                            :key="n"
                            class="stars__item hit-88"
                            @tap="pickStar(n)"
                        >
                            <!-- v1 u-icon → v2 tn-icon：评分星形保留（star-fill / star） -->
                            <!-- 批 1.5 C：未选星原 --c-line-strong 仅 1.60:1 → --c-ink-3 -->
                            <tn-icon
                                :name="n <= satisfaction ? 'star-fill' : 'star'"
                                :size="48"
                                class="ic-star"
                                :class="{ 'is-on': n <= satisfaction }"
                            />
                        </view>
                    </view>
                    <text class="stars-feedback">{{ SCORE_LABELS[satisfaction] }}</text>
                    <textarea
                        v-model="remark"
                        class="sheet__textarea"
                        placeholder="补充说明（选填）"
                        maxlength="200"
                    />
                    <view class="sheet__actions">
                        <base-button
                            text="取消"
                            variant="ghost"
                            size="md"
                            @click="confirmVisible = false"
                        />
                        <base-button
                            text="确认"
                            variant="ink"
                            size="md"
                            :loading="acting"
                            @click="doConfirm"
                        />
                    </view>
                </view>
            </view>

            <!-- 拒绝弹层 -->
            <view v-if="rejectVisible" class="mask" @tap="rejectVisible = false">
                <view class="sheet" @tap.stop>
                    <view class="sheet__handle" />
                    <text class="sheet__title">拒绝处理结果</text>
                    <text class="sheet__hint">退回后将由专席人员重新为您核查处理</text>
                    <textarea
                        v-model="rejectReason"
                        class="sheet__textarea"
                        placeholder="请说明拒绝原因（必填）"
                        maxlength="500"
                    />
                    <view class="sheet__actions">
                        <base-button
                            text="取消"
                            variant="ghost"
                            size="md"
                            @click="rejectVisible = false"
                        />
                        <base-button
                            text="提交"
                            variant="ink"
                            size="md"
                            :loading="acting"
                            @click="doReject"
                        />
                    </view>
                </view>
            </view>
        </view>
        <template #bottom>
            <bottom-bar v-if="showBottomBar">
                <view class="bbar-actions">
                    <base-button
                        v-if="canCancel"
                        text="取消工单"
                        variant="danger"
                        size="md"
                        @click="handleCancel"
                    />
                    <base-button
                        v-if="canConfirm"
                        text="拒绝并说明"
                        variant="ghost"
                        size="md"
                        @click="openReject"
                    />
                    <base-button
                        v-if="canConfirm"
                        text="确认已完成"
                        variant="ink"
                        size="md"
                        @click="openConfirm"
                    />
                </view>
            </bottom-bar>
        </template>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 工单详情：确认完成 / 拒绝 / 取消 */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import {
    AFTER_SALE_TICKET_STATUS_NAME_MAP,
    AFTER_SALE_TICKET_STATUS_TONE,
    AfterSaleTicketStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import {
    getTicketDetail,
    confirmTicketComplete,
    rejectTicketComplete,
    cancelTicket
} from '@/api/aftersale'
import { useAppStore } from '@/stores/app'
import { haptic } from '@/utils/haptics'

const appStore = useAppStore()

const TYPE_TEXT: Record<number, string> = { 1: '投诉', 2: '咨询', 3: '售后', 4: '建议', 5: '其他' }
const SCORE_LABELS: Record<number, string> = {
    1: '1 分 · 很不满意',
    2: '2 分 · 不太满意',
    3: '3 分 · 一般',
    4: '4 分 · 比较满意',
    5: '5 分 · 非常满意'
}

const id = ref(0)
const data = ref<any>({})
const loading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        load()
        return
    }
    if (kind === 'reselect' || kind === 'back') {
        uni.navigateBack()
        return
    }
    if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    // contact / 其余：保持现有客服入口，不新增跳转
}
const acting = ref(false)

const confirmVisible = ref(false)
const rejectVisible = ref(false)
const satisfaction = ref(5)
const remark = ref('')
const rejectReason = ref('')

const images = computed<string[]>(() => {
    const raw = data.value.images
    if (Array.isArray(raw)) return raw
    if (typeof raw === 'string' && raw) {
        try {
            const parsed = JSON.parse(raw)
            return Array.isArray(parsed) ? parsed : []
        } catch {
            return []
        }
    }
    return []
})

const logs = computed<any[]>(() => (Array.isArray(data.value.logs) ? data.value.logs : []))

const status = computed(() => Number(data.value.status))

const statusLabel = computed(() => {
    const mapped = resolveStatusLabel(AFTER_SALE_TICKET_STATUS_NAME_MAP as any, status.value)
    return mapped.known ? mapped.label : data.value.status_desc || mapped.label
})

const statusTone = computed(() => resolveTone(AFTER_SALE_TICKET_STATUS_TONE as any, status.value))

const typeText = computed(() => TYPE_TEXT[Number(data.value.type)] || '工单')

/** 待确认状态才允许确认/拒绝；待分配或处理中允许取消 */
const canConfirm = computed(() => status.value === AfterSaleTicketStatus.CONFIRMING)
const canCancel = computed(
    () =>
        status.value === AfterSaleTicketStatus.PENDING ||
        status.value === AfterSaleTicketStatus.PROCESSING
)
const canAct = computed(() => canConfirm.value || canCancel.value)
const showBottomBar = computed(() => !loading.value && !error.value && canAct.value)

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

function preview(index: number) {
    uni.previewImage({ urls: images.value.map((u) => imageUrl(u)), current: index })
}

async function load() {
    loading.value = true
    error.value = false
    try {
        const res = await getTicketDetail({ id: id.value })
        if (!res) throw new Error('empty')
        data.value = res
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

function pickStar(n: number) {
    satisfaction.value = n
    haptic('light', 'ticket-star')
}

function openConfirm() {
    satisfaction.value = 5
    remark.value = ''
    confirmVisible.value = true
}

function openReject() {
    rejectReason.value = ''
    rejectVisible.value = true
}

async function doConfirm() {
    if (acting.value) return
    acting.value = true
    haptic('medium', 'ticket-confirm')
    try {
        await confirmTicketComplete({
            id: id.value,
            satisfaction: satisfaction.value,
            remark: remark.value || undefined
        })
        toast.success('已确认')
        confirmVisible.value = false
        load()
    } catch {
        // 请求层已提示
    } finally {
        acting.value = false
    }
}

async function doReject() {
    if (!rejectReason.value.trim()) {
        toast.info('请填写拒绝原因')
        return
    }
    if (acting.value) return
    acting.value = true
    try {
        await rejectTicketComplete({ id: id.value, reason: rejectReason.value.trim() })
        toast.success('已提交')
        rejectVisible.value = false
        load()
    } catch {
        // 请求层已提示
    } finally {
        acting.value = false
    }
}

async function handleCancel() {
    /* 取消工单属不可逆操作：确认类二次弹窗保留居中形态，不做底部抽屉 */
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '取消工单',
            content: '取消后不可恢复，确定吗？',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
    haptic('heavy', 'ticket-cancel')
    try {
        await cancelTicket({ id: id.value })
        toast.info('已取消')
        load()
    } catch {
        // 请求层已提示
    }
}

onLoad((options: any) => {
    id.value = Number(options?.id || 0)
    if (!id.value) {
        error.value = true
        loading.value = false
        return
    }
    load()
})

/** page-shell 内置下拉刷新：无论同步/异步成功或异常，都必定调 done 收起刷新态 */
function onRefresh(done: () => void) {
    Promise.resolve(load()).finally(done)
}
</script>

<style lang="scss" scoped>
.ticket-detail {
    background: var(--c-bg);
    padding-bottom: var(--sp-4);
}

.detail-pad {
    padding: 0 var(--page-gutter);
}

.body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
}

.hero-card {
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: linear-gradient(135deg, var(--c-dark-1), var(--c-dark-2));
    color: var(--c-on-dark);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

    &__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-bottom: var(--sp-2);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-20);
        letter-spacing: 0.08em;
        color: var(--c-brand-soft);
    }

    &__title {
        display: block;
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }

    &__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        margin-top: var(--sp-3);
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-dark-3);
    }

    &__sn {
        font-family: var(--font-serif);
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-brand-soft);
        overflow-wrap: anywhere;
    }

    &__type {
        flex: none;
        padding: 4rpx var(--sp-2);
        border-radius: var(--r-pill);
        border: 1rpx solid var(--c-brand-soft);
        font-size: var(--fs-20);
        line-height: var(--lh-none);
        color: var(--c-brand-soft);
    }
}

.sec-title {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    margin-bottom: var(--sp-3);

    &__bar {
        width: 4rpx;
        height: 24rpx;
        border-radius: var(--r-pill);
        background: var(--c-brand-text);
        flex: none;
    }

    &__text {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }
}

.kv {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--sp-3);
    min-height: 56rpx;
    padding: var(--sp-2) 0;
    box-sizing: border-box;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &__k {
        width: 140rpx;
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink);
        text-align: right;
        overflow-wrap: anywhere;

        &--serif {
            font-family: var(--font-serif);
        }
    }
}

.block-text {
    display: block;
    font-size: var(--fs-md);
    color: var(--c-ink-2);
    line-height: var(--lh-loose);
    overflow-wrap: anywhere;
}

.images {
    display: flex;
    flex-wrap: wrap;
    gap: var(--sp-2);

    &__item {
        width: 160rpx;
        height: 160rpx;
        border-radius: var(--r-sm);
        background: var(--c-neutral-bg);
    }
}

.timeline {
    display: flex;
    flex-direction: column;
}

.log {
    display: flex;
    gap: var(--sp-3);

    &__rail {
        width: var(--sp-3);
        flex: none;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    &__dot {
        width: var(--sp-2);
        height: var(--sp-2);
        border-radius: var(--r-pill);
        background: var(--c-line-strong);
        margin-top: var(--sp-1);
        flex-shrink: 0;

        &.is-latest {
            background: var(--c-brand-text);
            box-shadow: 0 0 0 6rpx var(--c-brand-bg);
        }
    }

    &:not(.log--last) .log__rail::after {
        content: '';
        flex: 1;
        width: 2rpx;
        margin-top: var(--sp-1);
        background: var(--c-line);
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        padding-bottom: var(--sp-3);
    }

    &--last .log__body {
        padding-bottom: 0;
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: var(--lh-body);
        overflow-wrap: anywhere;
    }

    &--first .log__text {
        color: var(--c-ink);
        font-weight: var(--fw-medium);
    }

    &__time {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.bbar-actions {
    display: flex;
    align-items: center;
    gap: var(--sp-2);
    width: 100%;
}

.mask {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--c-mask);
    z-index: 200;
    display: flex;
    align-items: flex-end;
}

.sheet {
    width: 100%;
    max-height: 80vh;
    /* 浮层第三层表面：暖白 / 深咖 + 抽屉顶部投影（--shadow-sheet 内含 1rpx 分隔线） */
    background: var(--c-surface-float);
    border-radius: var(--r-48) var(--r-48) 0 0;
    box-shadow: var(--shadow-sheet);
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    box-sizing: border-box;

    &__handle {
        width: 64rpx;
        height: 8rpx;
        border-radius: var(--r-pill);
        background: var(--c-line-strong);
        margin: 0 auto var(--sp-1);
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
    }

    &__hint {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__textarea {
        width: 100%;
        min-height: 160rpx;
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-control-border);
        border-radius: var(--r-md);
        padding: var(--sp-3);
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: var(--lh-body);
        box-sizing: border-box;
    }

    &__actions {
        display: flex;
        gap: var(--sp-2);
    }
}

.stars {
    display: flex;
    width: 100%;
    max-width: 440rpx;

    &__item {
        flex: 1;
        min-width: 0;
        max-width: 88rpx;
        height: 88rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

.stars-feedback {
    font-size: var(--fs-xs);
    font-weight: var(--fw-medium);
    color: var(--c-brand-text);
}

/* 动态图标颜色：状态由父级/自身的 is-on 表达，颜色留在 CSS（tn-icon 的 color prop 不认 var()） */
.ic-star {
    color: var(--c-ink-3);
}
.ic-star.is-on {
    color: var(--c-warning);
}
</style>
