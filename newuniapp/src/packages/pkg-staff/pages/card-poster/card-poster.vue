<template>
    <page-shell title="档期海报与名片" bg="page" :show-back="true">
        <staff-guard
            v-if="!guardAllowed"
            :phase="guardPhase"
            :reason="guardReason"
            :error="guardError"
            @retry="onGuardRetry"
            @action="onGuardAction"
        />

        <view v-else class="page-wrap card-poster-page">
            <!-- 顶部模式切换 -->
            <view class="tab-bar page-pad">
                <view class="tab-bar__track">
                    <view
                        hover-class="is-pressed"
                        class="tab-bar__item"
                        :class="{ 'is-active': activeTab === 'card' }"
                        @tap="switchTab('card')"
                    >
                        <text class="tab-bar__text">电子名片</text>
                    </view>
                    <view
                        hover-class="is-pressed"
                        class="tab-bar__item"
                        :class="{ 'is-active': activeTab === 'poster' }"
                        @tap="switchTab('poster')"
                    >
                        <text class="tab-bar__text">档期海报模板</text>
                    </view>
                </view>
            </view>

            <!-- 电子名片视图 -->
            <view v-if="activeTab === 'card'" class="page-pad tab-body">
                <view v-if="cardLoading">
                    <base-skeleton :rows="4" />
                </view>

                <base-empty
                    v-else-if="cardError"
                    status="error"
                    :error-kind="cardErrorView?.errorKind || 'unknown'"
                    :error-text="cardErrorView?.title"
                    :desc="cardErrorView?.desc"
                    :primary-action="cardErrorView?.primaryAction"
                    fixed
                    @primary="onCardErrorPrimary"
                    @retry="fetchCard"
                />

                <template v-else-if="card">
                    <!-- 深墨金电子名片主卡 -->
                    <view class="business-card">
                        <view class="business-card__brand-row">
                            <text class="business-card__brand">{{
                                brand.name || '婚礼服务工作台'
                            }}</text>
                            <text class="business-card__eyebrow">STAFF CARD</text>
                        </view>

                        <view class="business-card__profile">
                            <image
                                v-if="card.staff.avatar"
                                :src="card.staff.avatar"
                                class="business-card__avatar"
                                mode="aspectFill"
                            />
                            <view v-else class="business-card__avatar business-card__avatar--fallback">
                                <text class="business-card__avatar-char">{{
                                    card.staff.name.slice(0, 1) || '师'
                                }}</text>
                            </view>
                            <view class="business-card__identity">
                                <text class="business-card__name">{{ card.staff.name }}</text>
                                <text class="business-card__role">
                                    {{ card.staff.category_name || '认证婚礼人' }}
                                    {{
                                        card.staff.experience_years > 0
                                            ? ` · 从业${card.staff.experience_years}年`
                                            : ''
                                    }}
                                </text>
                                <text class="business-card__price">{{
                                    card.staff.price_text || '详询档期报价'
                                }}</text>
                            </view>
                        </view>

                        <view class="business-card__metrics">
                            <view class="metric-item">
                                <text class="metric-item__val"
                                    >{{ Number(card.staff.rating || 5).toFixed(1) }} 分</text
                                >
                                <text class="metric-item__label">综合口碑</text>
                            </view>
                            <view class="metric-item">
                                <text class="metric-item__val">{{ card.staff.order_count }} 场</text>
                                <text class="metric-item__label">累计服务</text>
                            </view>
                            <view class="metric-item">
                                <text class="metric-item__val"
                                    >{{ card.available_days_14d }} / 14</text
                                >
                                <text class="metric-item__label">近两周可约</text>
                            </view>
                        </view>

                        <view v-if="card.staff.tags?.length" class="business-card__tags">
                            <text
                                v-for="(tag, idx) in card.staff.tags.slice(0, 5)"
                                :key="idx"
                                class="tag-pill"
                            >
                                {{ tag }}
                            </text>
                        </view>

                        <text class="business-card__intro">
                            {{
                                card.staff.intro ||
                                card.staff.service_desc ||
                                '用心打磨每一场婚礼现场流程与细节呈现。'
                            }}
                        </text>

                        <view class="business-card__foot">
                            <view class="business-card__foot-texts">
                                <text class="business-card__foot-title">长按或微信扫码直约档期</text>
                                <text class="business-card__foot-sub">
                                    {{
                                        card.staff.mobile_masked
                                            ? `工作联系：${card.staff.mobile_masked}`
                                            : '查看作品集与实时可约日历'
                                    }}
                                </text>
                            </view>
                            <image
                                v-if="card.qrcode_image_url"
                                :src="card.qrcode_image_url"
                                class="business-card__qr"
                                mode="aspectFit"
                                @tap="previewUrl(card.qrcode_image_url)"
                            />
                        </view>
                    </view>

                    <!-- 近 14 天可约档期概览 -->
                    <base-card compact>
                        <section-header
                            title="近 14 天可约档期速览"
                            :subtitle="`可约 ${card.available_days_14d} 天`"
                            flush
                        />
                        <view class="avail-grid">
                            <view
                                v-for="day in card.upcoming_availability"
                                :key="day.date"
                                class="avail-cell"
                                :class="day.available ? 'is-free' : 'is-busy'"
                            >
                                <text class="avail-cell__date">{{ day.md_label }}</text>
                                <text class="avail-cell__week">{{ day.weekday }}</text>
                                <text class="avail-cell__status">{{
                                    day.available ? '可约' : '已满'
                                }}</text>
                            </view>
                        </view>
                    </base-card>

                    <!-- 主推套餐与精选作品 -->
                    <base-card v-if="card.packages?.length" compact>
                        <section-header title="主推服务套餐" flush />
                        <view class="pkg-list">
                            <view
                                v-for="(pkg, idx) in card.packages"
                                :key="pkg.id"
                                class="pkg-row"
                                :class="{ 'pkg-row--divided': idx > 0 }"
                            >
                                <view class="pkg-row__left">
                                    <text class="pkg-row__name">{{ pkg.name }}</text>
                                    <text v-if="pkg.duration_desc" class="pkg-row__meta">{{
                                        pkg.duration_desc
                                    }}</text>
                                </view>
                                <text class="pkg-row__price">¥{{ pkg.price }}</text>
                            </view>
                        </view>
                    </base-card>

                    <base-card v-if="card.works?.length" compact>
                        <section-header title="精选现场作品" flush />
                        <view class="works-grid">
                            <view
                                v-for="w in card.works"
                                :key="w.id"
                                hover-class="is-pressed"
                                class="work-thumb"
                                @tap="previewUrl(w.cover)"
                            >
                                <image
                                    v-if="w.cover"
                                    :src="w.cover"
                                    class="work-thumb__img"
                                    mode="aspectFill"
                                />
                                <text class="work-thumb__title ellipsis">{{ w.title }}</text>
                            </view>
                        </view>
                    </base-card>

                    <!-- 动作区 -->
                    <view class="action-bar">
                        <base-button
                            :text="exporting ? '正在生成名片图…' : '生成并保存名片图'"
                            variant="ink"
                            size="lg"
                            block
                            :loading="exporting"
                            :disabled="exporting"
                            @click="handleSaveCardImage"
                        />
                        <view class="action-bar__sub">
                            <view class="action-bar__half">
                                <base-button
                                    text="复制名片简介"
                                    variant="ghost"
                                    size="md"
                                    block
                                    @click="handleCopyCardText"
                                />
                            </view>
                            <view class="action-bar__half">
                                <button open-type="share" class="share-native-btn">
                                    转发给客户
                                </button>
                            </view>
                        </view>
                    </view>
                </template>
            </view>

            <!-- 档期海报模板编辑器视图 -->
            <view v-else class="page-pad tab-body">
                <schedule-poster-editor />
            </view>
        </view>

        <business-card-canvas ref="canvasRef" :card="card" />
        <app-toast />
    </page-shell>
</template>

<script setup lang="ts">
/**
 * 档期海报与电子名片双模式工作台页。
 *
 * - 电子名片：拉取老师主推名片数据 + 近 14 天可约概览 + 离屏 Canvas 2D 导出保存；
 * - 档期海报模板：挂载 SchedulePosterEditor 完成档期确认函版面定制与实时预览。
 */
import { computed, ref } from 'vue'
import { onLoad, onShareAppMessage, onShow } from '@dcloudio/uni-app'
import { getStaffBusinessCard, type StaffBusinessCard } from '@/api/staff'
import StaffGuard from '../../components/staff-guard/staff-guard.vue'
import BusinessCardCanvas from '../../components/business-card-canvas/business-card-canvas.vue'
import SchedulePosterEditor from '../../components/schedule-poster-editor/schedule-poster-editor.vue'
import { useBrand } from '@/composables/useBrand'
import { useStaffGuard } from '@/composables/useStaffGuard'
import { toast } from '@/composables/useToast'
import { resolveErrorView } from '@/utils/resolveErrorView'

const {
    guardPhase,
    guardReason,
    guardError,
    guardAllowed,
    verify,
    onGuardRetry,
    onGuardAction
} = useStaffGuard(() => init(true))

const { brand } = useBrand()
const activeTab = ref<'card' | 'poster'>('card')
const card = ref<StaffBusinessCard | null>(null)
const cardLoading = ref(true)
const cardError = ref(false)
const exporting = ref(false)
const hasLoadedOnce = ref(false)
const canvasRef = ref<InstanceType<typeof BusinessCardCanvas> | null>(null)

let firstShow = true

const rawCardError = ref<unknown>(null)
const cardErrorView = computed(() =>
    resolveErrorView(rawCardError.value as any, { hasForm: false })
)

async function fetchCard(): Promise<void> {
    if (!guardAllowed.value) return
    cardLoading.value = true
    cardError.value = false
    rawCardError.value = null
    try {
        card.value = await getStaffBusinessCard()
    } catch (e) {
        rawCardError.value = e
        cardError.value = true
    } finally {
        cardLoading.value = false
    }
}

function switchTab(tab: 'card' | 'poster'): void {
    if (tab === activeTab.value) return
    activeTab.value = tab
    if (tab === 'card' && !card.value && !cardLoading.value) {
        void fetchCard()
    }
}

function previewUrl(url: string): void {
    if (!url) return
    uni.previewImage({ urls: [url], current: url })
}

function saveToAlbumWithAuth(filePath: string): Promise<void> {
    return new Promise((resolve, reject) => {
        uni.saveImageToPhotosAlbum({
            filePath,
            success: () => resolve(),
            fail: (err) => {
                const msg = String(err?.errMsg || '')
                if (msg.includes('auth deny') || msg.includes('authorize')) {
                    uni.showModal({
                        title: '需要相册权限',
                        content: '请在设置中允许保存图片到相册后重试',
                        confirmText: '去设置',
                        cancelText: '取消',
                        success: (modalRes) => {
                            if (modalRes.confirm) {
                                uni.openSetting({})
                            }
                        }
                    })
                    reject(new Error('未授予相册保存权限'))
                    return
                }
                reject(new Error('保存相册失败，可点击预览长按保存'))
            }
        })
    })
}

async function handleSaveCardImage(): Promise<void> {
    if (exporting.value || !card.value) return
    exporting.value = true
    try {
        const tempFilePath = await canvasRef.value?.exportImage()
        if (!tempFilePath) {
            throw new Error('生成名片图失败')
        }
        try {
            await saveToAlbumWithAuth(tempFilePath)
            toast.success('电子名片已保存到相册')
        } catch (albumErr: any) {
            toast.warning(String(albumErr?.message || '已生成预览，可长按保存'))
            previewUrl(tempFilePath)
        }
    } catch (e: any) {
        toast.error(String(e?.message || '生成名片图失败'))
    } finally {
        exporting.value = false
    }
}

function handleCopyCardText(): void {
    if (!card.value?.staff) return
    const s = card.value.staff
    const lines = [
        `${s.name} · ${s.category_name || '认证婚礼人'}`,
        s.experience_years > 0 ? `从业 ${s.experience_years} 年 | 累计服务 ${s.order_count} 场` : `累计服务 ${s.order_count} 场`,
        `参考报价：${s.price_text || '欢迎详询'}`,
        `近14天可约：${card.value.available_days_14d} 天`,
        s.intro ? `服务简介：${s.intro}` : ''
    ].filter(Boolean)

    uni.setClipboardData({
        data: lines.join('\n'),
        success: () => toast.success('已复制名片简介')
    })
}

function onCardErrorPrimary(): void {
    const kind = cardErrorView.value?.primaryAction?.kind
    if (kind === 'retry' || kind === 'refresh') {
        void fetchCard()
    } else if (kind === 'login') {
        uni.navigateTo({ url: '/pages/login/login' })
    }
}

onShareAppMessage(() => {
    const staffName = card.value?.staff?.name || '婚礼服务老师'
    const role = card.value?.staff?.category_name || ''
    return {
        title: `${staffName}${role ? `（${role}）` : ''} 的专属档期名片`,
        path: card.value?.share_path || '/pages/index/index',
        imageUrl: card.value?.staff?.cover || card.value?.staff?.avatar || ''
    }
})

async function init(forceGuard = false): Promise<void> {
    if (!(await verify(forceGuard))) return
    if (activeTab.value === 'card') {
        await fetchCard()
    }
    hasLoadedOnce.value = true
}

onLoad((options?: Record<string, any>) => {
    firstShow = true
    if (options?.tab === 'poster') {
        activeTab.value = 'poster'
    }
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
</script>

<style lang="scss" scoped>
.card-poster-page {
    padding-bottom: calc(var(--sp-5) + var(--safe-bottom));
}

.tab-bar {
    padding-top: var(--sp-3);
    padding-bottom: var(--sp-3);

    &__track {
        display: flex;
        padding: 6rpx;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
        border: 1rpx solid var(--c-line);
    }

    &__item {
        flex: 1;
        height: 68rpx;
        border-radius: var(--r-pill);
        display: flex;
        align-items: center;
        justify-content: center;

        &.is-active {
            background: var(--c-active-bg);
            box-shadow: inset 0 0 0 1rpx var(--c-active-border);

            .tab-bar__text {
                color: var(--c-active-fg);
                font-weight: var(--fw-semibold);
            }
        }
    }

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }
}

.tab-body {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
}

.business-card {
    padding: var(--sp-4);
    border-radius: var(--r-lg);
    background: linear-gradient(155deg, var(--c-dark-1), var(--c-dark-2));
    color: var(--c-on-dark);
    border: 1rpx solid var(--c-primary-border);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);

    &__brand-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-2);
    }

    &__brand {
        font-size: var(--fs-xs);
        color: var(--c-brand-soft);
        font-weight: var(--fw-medium);
    }

    &__eyebrow {
        font-family: var(--font-serif);
        font-size: var(--fs-18);
        letter-spacing: 2rpx;
        color: var(--c-on-dark-2);
    }

    &__profile {
        display: flex;
        align-items: center;
        gap: var(--sp-3);
    }

    &__avatar {
        width: 112rpx;
        height: 112rpx;
        border-radius: var(--r-pill);
        border: 2rpx solid var(--c-brand-soft);
        background: var(--c-dark-3);
        flex-shrink: 0;

        &--fallback {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    }

    &__avatar-char {
        font-family: var(--font-serif);
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-soft);
    }

    &__identity {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__name {
        font-size: var(--fs-xl);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
    }

    &__role {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__price {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-soft);
    }

    &__metrics {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        padding: var(--sp-2) 0;
        border-top: 1rpx solid var(--c-line-strong);
        border-bottom: 1rpx solid var(--c-line-strong);
    }

    &__tags {
        display: flex;
        flex-wrap: wrap;
        gap: var(--sp-1);
    }

    &__intro {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
    }

    &__foot {
        padding-top: var(--sp-2);
        border-top: 1rpx solid var(--c-line-strong);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--sp-3);
    }

    &__foot-texts {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__foot-title {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-soft);
    }

    &__foot-sub {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }

    &__qr {
        width: 120rpx;
        height: 120rpx;
        border-radius: var(--r-sm);
        background: var(--c-surface);
        padding: 6rpx;
        flex-shrink: 0;
    }
}

.metric-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4rpx;

    &__val {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: var(--fs-20);
        color: var(--c-on-dark-2);
    }
}

.tag-pill {
    padding: 4rpx var(--sp-2);
    border-radius: var(--r-pill);
    background: var(--c-dark-3);
    border: 1rpx solid var(--c-brand-soft);
    font-size: var(--fs-20);
    color: var(--c-brand-soft);
}

.avail-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: var(--sp-1);
    margin-top: var(--sp-2);
}

.avail-cell {
    padding: var(--sp-1) 4rpx;
    border-radius: var(--r-xs);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rpx;

    &.is-free {
        background: var(--c-success-bg);
        border: 1rpx solid var(--c-success-border);

        .avail-cell__date,
        .avail-cell__status {
            color: var(--c-success);
        }
    }

    &.is-busy {
        background: var(--c-neutral-bg);
        border: 1rpx solid var(--c-neutral-border);

        .avail-cell__date,
        .avail-cell__status {
            color: var(--c-ink-3);
        }
    }

    &__date {
        font-size: var(--fs-18);
        font-weight: var(--fw-semibold);
        font-variant-numeric: tabular-nums;
    }

    &__week {
        font-size: var(--fs-16);
        color: var(--c-ink-3);
    }

    &__status {
        font-size: var(--fs-16);
        font-weight: var(--fw-medium);
    }
}

.pkg-list {
    margin-top: var(--sp-2);
    display: flex;
    flex-direction: column;
}

.pkg-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    padding: var(--sp-2) 0;

    &--divided {
        border-top: 1rpx solid var(--c-line);
    }

    &__left {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__name {
        font-size: var(--fs-md);
        color: var(--c-ink);
        font-weight: var(--fw-medium);
    }

    &__meta {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__price {
        font-family: var(--font-serif);
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
        font-variant-numeric: tabular-nums;
    }
}

.works-grid {
    margin-top: var(--sp-2);
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: var(--sp-2);
}

.work-thumb {
    display: flex;
    flex-direction: column;
    gap: var(--sp-1);

    &__img {
        width: 100%;
        height: 240rpx;
        border-radius: var(--r-sm);
        background: var(--c-surface-2);
    }

    &__title {
        font-size: var(--fs-xs);
        color: var(--c-ink-2);
    }
}

.action-bar {
    display: flex;
    flex-direction: column;
    gap: var(--sp-2);
    padding-top: var(--sp-1);

    &__sub {
        display: flex;
        gap: var(--sp-2);
    }

    &__half {
        flex: 1;
        min-width: 0;
    }
}

.share-native-btn {
    width: 100%;
    height: 80rpx;
    line-height: 80rpx;
    margin: 0;
    padding: 0 var(--sp-3);
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    color: var(--c-ink);
    font-size: var(--fs-md);
    font-weight: var(--fw-medium);
    text-align: center;
    box-sizing: border-box;

    &::after {
        border: none;
    }
}
</style>
