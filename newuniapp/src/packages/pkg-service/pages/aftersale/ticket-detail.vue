<template>
    <view class="page-wrap ticket-detail">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="4" />
        </view>

        <base-empty v-else-if="error" status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title" :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed @primary="onPrimary" @retry="load" />

        <template v-else>
            <view class="head">
                <view class="head__row">
                    <text class="head__title">{{ data.title || '服务工单' }}</text>
                    <status-tag :label="statusLabel" :tone="statusTone" size="md" />
                </view>
                <text class="head__sn">{{ data.ticket_sn || '' }}</text>
            </view>

            <view class="page-pad body">
                <base-card>
                    <view class="kv">
                        <text class="kv__k">提交时间</text>
                        <text class="kv__v">{{ data.create_time }}</text>
                    </view>
                    <view v-if="data.type" class="kv">
                        <text class="kv__k">工单类型</text>
                        <text class="kv__v">{{ typeText }}</text>
                    </view>
                    <view v-if="data.order_sn" class="kv">
                        <text class="kv__k">关联订单</text>
                        <text class="kv__v">{{ data.order_sn }}</text>
                    </view>
                </base-card>

                <base-card v-if="data.content">
                    <text class="block-title">问题描述</text>
                    <text class="block-text">{{ data.content }}</text>
                </base-card>

                <base-card v-if="images.length">
                    <text class="block-title">附件</text>
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

                <!-- 处理记录 -->
                <base-card v-if="logs.length">
                    <text class="block-title">处理记录</text>
                    <view v-for="(l, i) in logs" :key="i" class="log">
                        <view class="log__dot" />
                        <view class="log__body">
                            <text class="log__text">{{ l.content || l.remark || l.action_desc }}</text>
                            <text class="log__time">{{ l.create_time }}</text>
                        </view>
                    </view>
                </base-card>

                <view class="body__space" />
            </view>

            <view v-if="canAct" class="action-bar">
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
        </template>

        <!-- 确认完成弹层 -->
        <view v-if="confirmVisible" class="mask" @tap="confirmVisible = false">
            <view class="sheet" @tap.stop>
                <text class="sheet__title">确认处理结果</text>
                <text class="sheet__hint">请为本次处理打分</text>
                <view class="stars">
                    <view hover-class="is-pressed" v-for="n in 5" :key="n" class="stars__item" @tap="satisfaction = n">
                        <u-icon
                            :name="n <= satisfaction ? 'star-fill' : 'star'"
                            :size="48"
                            :color="n <= satisfaction ? 'var(--c-warning)' : 'var(--c-line-strong)'"
                        />
                    </view>
                </view>
                <textarea v-model="remark" class="sheet__textarea" placeholder="补充说明（选填）" maxlength="200" />
                <view class="sheet__actions">
                    <base-button text="取消" variant="ghost" size="md" @click="confirmVisible = false" />
                    <base-button text="确认" variant="ghost" size="md" :loading="acting" @click="doConfirm" />
                </view>
            </view>
        </view>

        <!-- 拒绝弹层 -->
        <view v-if="rejectVisible" class="mask" @tap="rejectVisible = false">
            <view class="sheet" @tap.stop>
                <text class="sheet__title">拒绝处理结果</text>
                <textarea v-model="rejectReason" class="sheet__textarea" placeholder="请说明拒绝原因（必填）" maxlength="500" />
                <view class="sheet__actions">
                    <base-button text="取消" variant="ghost" size="md" @click="rejectVisible = false" />
                    <base-button text="提交" variant="ghost" size="md" :loading="acting" @click="doReject" />
                </view>
            </view>
        </view>
    </view>
    <app-toast />
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
import { getTicketDetail, confirmTicketComplete, rejectTicketComplete, cancelTicket } from '@/api/aftersale'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

const TYPE_TEXT: Record<number, string> = { 1: '投诉', 2: '咨询', 3: '售后', 4: '建议', 5: '其他' }

const id = ref(0)
const data = ref<any>({})
const loading = ref(true)
const error = ref(false)

/** O5：真实错误驱动 errorKind / 文案 / 恢复动作（状态机骨架保持不变） */
const rawError = ref<unknown>(null)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
function onPrimary() {
    const kind = view.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') { load(); return }
    if (kind === 'reselect' || kind === 'back') { uni.navigateBack(); return }
    if (kind === 'login') { uni.navigateTo({ url: '/pages/login/login' }); return }
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
    () => status.value === AfterSaleTicketStatus.PENDING || status.value === AfterSaleTicketStatus.PROCESSING
)
const canAct = computed(() => canConfirm.value || canCancel.value)

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
    try {
        await confirmTicketComplete({ id: id.value, satisfaction: satisfaction.value, remark: remark.value || undefined })
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
    const confirmed = await new Promise<boolean>((resolve) => {
        uni.showModal({
            title: '取消工单',
            content: '取消后不可恢复，确定吗？',
            success: (r) => resolve(!!r.confirm),
            fail: () => resolve(false)
        })
    })
    if (!confirmed) return
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
</script>

<style lang="scss" scoped>
.ticket-detail {
    background: var(--c-bg);
    padding-bottom: calc(164rpx + var(--safe-bottom));
}

.head {
    background: var(--c-ink);
    padding: var(--sp-5) var(--sp-4);

    &__row {
        display: flex;
        align-items: flex-start;
        gap: var(--sp-3);
    }

    &__title {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xl);
        font-weight: 500;
        line-height: 1.4;
        color: var(--c-on-dark);
        overflow-wrap: anywhere;
    }

    &__sn {
        display: block;
        margin-top: var(--sp-2);
        font-size: var(--fs-xs);
        line-height: 1.5;
        color: var(--c-on-dark-2);
        overflow-wrap: anywhere;
    }
}

.body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    margin-top: -20rpx;
    position: relative;
    z-index: 2;
    padding-top: var(--sp-3);

    &__space {
        height: var(--sp-4);
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
        flex-shrink: 0;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__v {
        flex: 1;
        min-width: 0;
        font-size: var(--fs-xs);
        line-height: 1.5;
        color: var(--c-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }
}

.block-title {
    display: block;
    margin-bottom: var(--sp-3);
    font-size: var(--fs-md);
    font-weight: 500;
    color: var(--c-ink);
}

.block-text {
    display: block;
    font-size: var(--fs-md);
    color: var(--c-ink-2);
    line-height: 1.7;
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

.log {
    display: flex;
    gap: var(--sp-2);
    padding: var(--sp-3) 0;

    & + & {
        border-top: 1rpx solid var(--c-line);
    }

    &__dot {
        width: 12rpx;
        height: 12rpx;
        border-radius: 50%;
        background: var(--c-brand);
        margin-top: 12rpx;
        flex-shrink: 0;
    }

    &__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
    }

    &__text {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
        line-height: 1.7;
        overflow-wrap: anywhere;
    }

    &__time {
        font-size: var(--fs-xs);
        line-height: 1.5;
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}

.action-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--sp-2);
    padding: var(--sp-3) var(--sp-4) calc(var(--sp-3) + var(--safe-bottom));
    background: var(--c-surface);
    border-top: 1rpx solid var(--c-line);
    box-sizing: border-box;
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
    background: var(--c-surface);
    border-radius: var(--r-lg) var(--r-lg) 0 0;
    padding: var(--sp-4) var(--sp-4) calc(var(--sp-4) + var(--safe-bottom));
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    box-sizing: border-box;

    &__title {
        font-size: var(--fs-lg);
        font-weight: 500;
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
        border: 1rpx solid var(--c-line);
        border-radius: var(--r-md);
        padding: var(--sp-3);
        font-size: var(--fs-md);
        color: var(--c-ink);
        line-height: 1.6;
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
</style>
