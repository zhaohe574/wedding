<template>
    <page-shell title="活动核销" bg="page" :show-back="true">
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="checkin-page">
            <!-- 扫一扫主操作区 -->
            <view class="scan-card" hover-class="is-pressed" @tap="onScan">
                <view class="scan-card__icon-wrap">
                    <tn-icon name="scan" :size="44" class="scan-card__icon" />
                </view>
                <view class="scan-card__body">
                    <text class="scan-card__title">扫一扫核销入场</text>
                    <text class="scan-card__desc">扫描宾客「我的活动」入场二维码快速验票</text>
                </view>
                <tn-icon name="right" :size="24" class="scan-card__arrow" />
            </view>

            <!-- 手动输码查询 -->
            <view class="code-card">
                <text class="code-card__label">手动输入 8 位核销码</text>
                <view class="code-card__row">
                    <input
                        v-model="codeInput"
                        class="code-card__input"
                        placeholder="如 K7M9P4XR"
                        placeholder-class="code-card__ph"
                        :maxlength="24"
                        confirm-type="search"
                        @confirm="onQueryCode"
                    />
                    <view class="code-card__btn">
                        <base-button
                            text="查询凭证"
                            variant="ink"
                            size="sm"
                            haptic-level="none"
                            :loading="querying"
                            @click="onQueryCode"
                        />
                    </view>
                </view>
            </view>

            <!-- 核销预览确认卡 -->
            <view v-if="previewReg" class="preview-card">
                <view class="preview-card__head">
                    <text class="preview-card__kicker">VOUCHER PREVIEW · 核销凭证</text>
                    <status-tag
                        :label="previewBadge.label"
                        :tone="previewBadge.tone"
                        size="sm"
                    />
                </view>

                <text class="preview-card__title">{{
                    previewReg.dynamic?.title || '精彩主题活动'
                }}</text>

                <view class="preview-card__rows">
                    <view class="p-row">
                        <text class="p-row__k">核销码</text>
                        <text class="p-row__v p-row__v--code t-num">{{
                            previewReg.checkin_code || normalizedCode
                        }}</text>
                    </view>
                    <view class="p-row">
                        <text class="p-row__k">报名人</text>
                        <text class="p-row__v">{{ previewReg.contact_name || '—' }}</text>
                    </view>
                    <view class="p-row">
                        <text class="p-row__k">手机号</text>
                        <text class="p-row__v t-num">{{ previewReg.contact_mobile || '—' }}</text>
                    </view>
                    <view class="p-row">
                        <text class="p-row__k">票种费用</text>
                        <text class="p-row__v t-num"
                            >{{ previewReg.ticket_name || '—' }} ·
                            {{ previewReg.ticket_price_label || '免费' }}</text
                        >
                    </view>
                    <view class="p-row">
                        <text class="p-row__k">活动时间</text>
                        <text class="p-row__v t-num">{{
                            formatShortTime(previewReg.activity_start_time)
                        }}</text>
                    </view>
                </view>

                <view v-if="!canCheckin && blockReason" class="preview-card__warn">
                    <text class="preview-card__warn-text">{{ blockReason }}</text>
                </view>

                <view class="preview-card__acts">
                    <base-button
                        v-if="canCheckin"
                        text="确认签到核销"
                        variant="ink"
                        size="lg"
                        haptic-level="medium"
                        :loading="submitting"
                        @click="onConfirmCheckin"
                    />
                    <base-button
                        v-else
                        text="继续核销下一位"
                        variant="ghost"
                        size="md"
                        @click="resetPreview"
                    />
                </view>
            </view>

            <!-- 本次会话核销记录 -->
            <view v-if="recentLogs.length" class="logs">
                <text class="logs__title">本次核销记录（{{ recentLogs.length }}）</text>
                <view class="logs__items" :class="{ stagger: staggerIn, 'is-in': staggerIn }">
                    <view
                        v-for="(log, index) in recentLogs"
                        :key="log.id"
                        class="logs__item"
                        :style="{ '--i': index }"
                    >
                        <view class="logs__main">
                            <text class="logs__name"
                                >{{ log.contact_name }} · {{ log.ticket_name }}</text
                            >
                            <text class="logs__sub ellipsis">{{ log.activity_title }}</text>
                        </view>
                        <view class="logs__right">
                            <text class="logs__code t-num">{{ log.checkin_code }}</text>
                            <text class="logs__time t-num">{{ log.time_text }}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import { useStaffGuard } from '@/composables/useStaffGuard'
import {
    confirmStaffActivityCheckin,
    previewStaffActivityCheckin
} from '@/api/staff-center'
import { toast } from '@/composables/useToast'
import {
    formatClockTime,
    formatShortTime,
    resolveStatusBadge
} from '@/utils/activityFormat'

interface CheckinLogItem {
    id: number
    contact_name: string
    ticket_name: string
    activity_title: string
    checkin_code: string
    time_text: string
}

const { guardPhase, guardReason, guardError, guardAllowed, verify, onGuardRetry, onGuardAction } =
    useStaffGuard()

const codeInput = ref('')
const querying = ref(false)
const submitting = ref(false)
const previewReg = ref<Record<string, any> | null>(null)
const canCheckin = ref(false)
const blockReason = ref('')
const recentLogs = ref<CheckinLogItem[]>([])

const normalizedCode = computed(() => {
    let raw = codeInput.value.trim().toUpperCase()
    if (raw.startsWith('ACT_CHECKIN:')) {
        raw = raw.slice('ACT_CHECKIN:'.length).trim()
    }
    return raw.replace(/[^0-9A-Z]/g, '')
})

const previewBadge = computed(() =>
    previewReg.value
        ? resolveStatusBadge(previewReg.value)
        : { label: '', tone: 'neutral' as const }
)

function resetPreview() {
    previewReg.value = null
    canCheckin.value = false
    blockReason.value = ''
    codeInput.value = ''
}

function onScan() {
    uni.scanCode({
        onlyFromCamera: false,
        scanType: ['qrCode'],
        success: (res) => {
            const raw = String(res?.result || '').trim()
            if (!raw) {
                toast.info('未识别到有效二维码内容')
                return
            }
            codeInput.value = raw
            onQueryCode()
        },
        fail: () => {
            toast.info('已取消扫码')
        }
    })
}

async function onQueryCode() {
    const code = normalizedCode.value
    if (!code) {
        toast.info('请输入或扫描核销码')
        return
    }
    querying.value = true
    try {
        const res = await previewStaffActivityCheckin({ code })
        previewReg.value = res?.registration || null
        canCheckin.value = Boolean(res?.can_checkin)
        blockReason.value = String(res?.reason || '')
        if (!previewReg.value) {
            toast.info('未找到匹配的报名凭证')
        }
    } catch (e: any) {
        previewReg.value = null
        canCheckin.value = false
        blockReason.value = ''
        toast.error(e?.message || '未找到匹配的报名凭证')
    } finally {
        querying.value = false
    }
}

/* ---- 核销记录错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger(): void {
    if (staggerIn.value || staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        staggerIn.value = true
    }, 40)
}

onUnmounted(() => {
    if (staggerTimer) clearTimeout(staggerTimer)
})

async function onConfirmCheckin() {
    if (!previewReg.value || !canCheckin.value || submitting.value) return
    submitting.value = true
    try {
        const res = await confirmStaffActivityCheckin({
            registration_id: Number(previewReg.value.id || 0),
            code: normalizedCode.value || undefined
        })
        const updated = res?.registration || previewReg.value
        previewReg.value = updated
        canCheckin.value = false
        blockReason.value = '已完成签到核销'
        toast.success('签到核销成功')

        const logId = Number(updated?.id || Date.now())
        recentLogs.value = [
            {
                id: logId,
                contact_name: String(updated?.contact_name || '宾客'),
                ticket_name: String(updated?.ticket_name || '活动门票'),
                activity_title: String(updated?.dynamic?.title || '主题活动'),
                checkin_code: String(updated?.checkin_code || normalizedCode.value),
                time_text: formatClockTime(updated?.checkin_time || Math.floor(Date.now() / 1000))
            },
            ...recentLogs.value.filter((item) => item.id !== logId)
        ].slice(0, 20)
        playStagger()
    } catch (e: any) {
        toast.error(e?.message || '核销失败，请重试')
    } finally {
        submitting.value = false
    }
}

onLoad(() => {
    verify()
})
</script>

<style lang="scss" scoped>
.checkin-page {
    padding: var(--sp-3) var(--page-gutter) calc(var(--sp-6) + var(--safe-bottom));
}

.scan-card {
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: linear-gradient(145deg, var(--c-dark-1), var(--c-dark-2));
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    transition: transform var(--dur-press) var(--ease-out);

    &.is-pressed {
        transform: scale(0.99);
    }

    &__icon-wrap {
        width: 88rpx;
        height: 88rpx;
        border-radius: var(--r-md);
        background: rgba(217, 190, 130, 0.14);
        border: 1rpx solid rgba(217, 190, 130, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        flex: none;
    }

    &__icon {
        color: var(--c-brand-soft);
    }

    &__body {
        flex: 1;
        min-width: 0;
    }

    &__title {
        display: block;
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
    }

    &__desc {
        display: block;
        margin-top: 6rpx;
        font-size: var(--fs-21);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }

    &__arrow {
        color: var(--c-brand-soft);
        flex: none;
    }
}

.code-card {
    margin-top: var(--sp-3);
    padding: var(--sp-3);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

    &__label {
        display: block;
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        margin-bottom: var(--sp-2);
    }

    &__row {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__input {
        flex: 1;
        min-width: 0;
        height: 88rpx;
        padding: 0 var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-bg);
        border: 1rpx solid var(--c-control-border);
        font-size: var(--fs-md);
        color: var(--c-ink);
        letter-spacing: 0.08em;
        font-variant-numeric: tabular-nums;
    }

    &__ph {
        color: var(--c-ink-3);
        letter-spacing: 0;
    }

    &__btn {
        flex: none;
    }
}

.preview-card {
    margin-top: var(--sp-3);
    padding: var(--sp-4);
    border-radius: var(--r-md);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__kicker {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 0.14em;
        color: var(--c-brand-text);
    }

    &__title {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__rows {
        margin-top: var(--sp-3);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-bg);
        border: 1rpx solid var(--c-line);
    }

    &__warn {
        margin-top: var(--sp-3);
        padding: var(--sp-2) var(--sp-3);
        border-radius: var(--r-sm);
        background: var(--c-warning-bg);
        border: 1rpx solid var(--c-warning-border);
    }

    &__warn-text {
        font-size: var(--fs-22);
        line-height: var(--lh-body);
        color: var(--c-warning);
    }

    &__acts {
        margin-top: var(--sp-3);
    }
}

.p-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-3);
    padding: var(--sp-1) 0;

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

        &--code {
            font-family: var(--font-serif);
            font-size: var(--fs-md);
            font-weight: var(--fw-semibold);
            color: var(--c-brand-text);
            letter-spacing: 0.12em;
        }
    }
}

.logs {
    margin-top: var(--sp-4);

    &__title {
        display: block;
        margin-bottom: var(--sp-2);
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-ink-2);
    }

    &__items {
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
    }

    &__item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
        padding: var(--sp-3);
        border-radius: var(--r-md);
        background: var(--c-surface);
        border: 1rpx solid var(--c-card-border);
        box-shadow: var(--shadow-card);
    }

    &__main {
        flex: 1;
        min-width: 0;
    }

    &__name {
        display: block;
        font-size: var(--fs-25);
        font-weight: var(--fw-medium);
        color: var(--c-ink);
    }

    &__sub {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }

    &__right {
        flex: none;
        text-align: right;
    }

    &__code {
        display: block;
        font-family: var(--font-serif);
        font-size: var(--fs-22);
        color: var(--c-brand-text);
        letter-spacing: 0.08em;
    }

    &__time {
        display: block;
        margin-top: 4rpx;
        font-size: var(--fs-20);
        color: var(--c-ink-3);
    }
}

/* v10 §3.13：减弱动效下按压不缩放 */
@media (prefers-reduced-motion: reduce) {
    .scan-card {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}
</style>
