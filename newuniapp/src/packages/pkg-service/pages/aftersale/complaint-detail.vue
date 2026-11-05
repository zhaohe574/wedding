<template>
    <view class="page-wrap detail">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="4" />
        </view>

        <base-empty v-else-if="error" status="error"
            :error-kind="view?.errorKind || 'unknown'"
            :error-text="view?.title" :desc="view?.desc"
            :primary-action="view?.primaryAction"
            fixed @primary="onPrimary" @retry="load" />

        <template v-else>
            <view class="status-head">
                <view class="status-head__row">
                    <text class="status-head__title">{{ data.title || '投诉' }}</text>
                    <status-tag :label="statusLabel" :tone="statusTone" size="md" />
                </view>
                <text class="status-head__sub">{{ typeText }} · {{ levelText }}</text>
            </view>

            <view class="page-pad body">
                <base-card v-if="data.content">
                    <text class="block-title">投诉内容</text>
                    <text class="block-text">{{ data.content }}</text>
                </base-card>

                <base-card v-if="data.expect_result">
                    <text class="block-title">期望处理结果</text>
                    <text class="block-text">{{ data.expect_result }}</text>
                </base-card>

                <base-card v-if="images.length">
                    <text class="block-title">凭证</text>
                    <view class="images">
                        <image
                            v-for="(img, i) in images"
                            :key="i"
                            class="image__img"
                            :src="imageUrl(img)"
                            mode="aspectFill"
                            @tap="preview(i)"
                        />
                    </view>
                </base-card>

                <base-card>
                    <view class="kv">
                        <text class="kv__k">提交时间</text>
                        <text class="kv__v">{{ data.create_time }}</text>
                    </view>
                    <view v-if="data.handle_time" class="kv">
                        <text class="kv__k">处理时间</text>
                        <text class="kv__v">{{ data.handle_time }}</text>
                    </view>
                    <view v-if="data.handle_result" class="kv">
                        <text class="kv__k">处理结果</text>
                        <text class="kv__v">{{ data.handle_result }}</text>
                    </view>
                </base-card>

                <base-card v-if="canRate">
                    <text class="block-title">处理满意度</text>
                    <view class="stars">
                        <view hover-class="is-pressed" v-for="n in 5" :key="n" class="stars__item" @tap="score = n">
                            <u-icon
                                :name="n <= score ? 'star-fill' : 'star'"
                                :size="48"
                                :color="n <= score ? 'var(--c-warning)' : 'var(--c-line-strong)'"
                            />
                        </view>
                    </view>
                    <textarea
                        v-model="feedback"
                        class="textarea"
                        placeholder="补充说明（选填）"
                        placeholder-class="textarea__ph"
                        maxlength="300"
                    />
                    <view class="rate-submit">
                        <base-button text="提交评价" variant="ink" size="md" :loading="rating" @click="handleRate" />
                    </view>
                </base-card>

                <view class="body__space" />
            </view>
        </template>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/** 投诉详情：处理完成后可评分（rateComplaint） */
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import {
    COMPLAINT_STATUS_NAME_MAP,
    COMPLAINT_STATUS_TONE,
    COMPLAINT_TYPE_NAME_MAP,
    COMPLAINT_LEVEL_NAME_MAP,
    ComplaintStatus,
    resolveStatusLabel,
    resolveTone
} from '@shared/contracts'
import { getComplaintDetail, rateComplaint } from '@/api/aftersale'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()

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
const rating = ref(false)
const score = ref(5)
const feedback = ref('')

const status = computed(() => Number(data.value.status))

const typeText = computed(
    () => (COMPLAINT_TYPE_NAME_MAP as Record<number, string>)[Number(data.value.type)] || '投诉'
)
const levelText = computed(
    () => (COMPLAINT_LEVEL_NAME_MAP as Record<number, string>)[Number(data.value.level)] || '-'
)

const statusLabel = computed(() => {
    const mapped = resolveStatusLabel(COMPLAINT_STATUS_NAME_MAP as any, status.value)
    return mapped.known ? mapped.label : data.value.status_desc || mapped.label
})

const statusTone = computed(() => resolveTone(COMPLAINT_STATUS_TONE as any, status.value))

/** 已处理且未评价才显示评分 */
const canRate = computed(
    () => status.value === ComplaintStatus.HANDLED && Number(data.value.is_rated ?? 0) !== 1
)

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
        const res = await getComplaintDetail({ id: id.value })
        if (!res) throw new Error('empty')
        data.value = res
    } catch (e) {
        rawError.value = e
        error.value = true
    } finally {
        loading.value = false
    }
}

async function handleRate() {
    if (rating.value) return
    rating.value = true
    try {
        await rateComplaint({ id: id.value, score: score.value, feedback: feedback.value.trim() || undefined })
        toast.success('感谢反馈')
        load()
    } catch {
        // 请求层已提示
    } finally {
        rating.value = false
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
.detail {
    background: var(--c-bg);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.status-head {
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

    &__sub {
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
}

.image__img {
    width: 160rpx;
    height: 160rpx;
    border-radius: var(--r-sm);
    background: var(--c-neutral-bg);
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
        line-height: 1.6;
        color: var(--c-ink);
        text-align: right;
        overflow-wrap: anywhere;
    }
}

.stars {
    display: flex;
    width: 100%;
    max-width: 440rpx;
    margin-bottom: var(--sp-3);

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

.textarea {
    width: 100%;
    min-height: 180rpx;
    background: var(--c-surface-2);
    border: 1rpx solid var(--c-line);
    border-radius: var(--r-md);
    padding: var(--sp-3);
    font-size: var(--fs-md);
    color: var(--c-ink);
    line-height: 1.6;
    box-sizing: border-box;

    &__ph {
        color: var(--c-ink-3);
    }
}

.rate-submit {
    margin-top: var(--sp-3);
}
</style>
