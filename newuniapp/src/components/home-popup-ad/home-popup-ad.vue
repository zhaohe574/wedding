<template>
    <view
        v-if="visible"
        class="hpa-mask"
        @tap="onClose"
        @touchmove.stop.prevent
    >
        <view class="hpa" :style="{ backgroundColor: content.background_color }" @tap.stop>
            <image
                v-if="showImage"
                class="hpa__img"
                :src="imageSrc"
                mode="widthFix"
                @error="imageFailed = true"
            />

            <view v-if="showBody" class="hpa__body">
                <text v-if="content.title" class="hpa__title">{{ content.title }}</text>
                <text v-if="content.content" class="hpa__text">{{ content.content }}</text>
            </view>

            <view class="hpa__actions">
                <view
                    v-if="showClose"
                    class="hpa__ghost"
                    hover-class="is-pressed"
                    @tap="onClose"
                >
                    <text class="hpa__ghost-text">暂不</text>
                </view>
                <!-- #ifdef MP-WEIXIN -->
                <button
                    v-if="isContactCta"
                    open-type="contact"
                    class="hpa__cta hpa__cta--btn"
                    hover-class="is-pressed"
                    @tap="onContactCta"
                >
                    <text class="hpa__cta-text">{{ content.button_text }}</text>
                </button>
                <view v-else class="hpa__cta" hover-class="is-pressed" @tap="onCta">
                    <text class="hpa__cta-text">{{ content.button_text }}</text>
                </view>
                <!-- #endif -->
                <!-- #ifndef MP-WEIXIN -->
                <view class="hpa__cta" hover-class="is-pressed" @tap="onCta">
                    <text class="hpa__cta-text">{{ content.button_text }}</text>
                </view>
                <!-- #endif -->
            </view>

            <view
                v-if="showClose"
                class="hpa__close"
                hover-class="is-pressed"
                aria-role="button"
                aria-label="关闭弹窗"
                @tap="onClose"
            >
                <tn-icon name="close" :size="28" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
/**
 * 首页弹窗广告。
 *
 * 数据来自装修组件 `home-popup-ad`（GET /v2/home → page.widgets），
 * 展示规则（频次 / 时机 / 起止时间 / 次数上限）全部走 `shared/contracts/decorate` 的纯函数，
 * 组件本身不写业务判断。
 *
 * 为什么自绘遮罩而不用 tn-popup：
 *   曝光口径要可控（`close_counts_as_shown`），自绘只有一个关闭入口，
 *   不会出现「组件库默认的点击遮罩关闭」与曝光计数打架。
 *
 * 曝光口径（与后台字段一一对应）：
 *   - close_counts_as_shown = 1：弹窗**展示即计一次**曝光（默认）；
 *   - close_counts_as_shown = 0：关闭不计，只有点主按钮才计。
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { normalizeHomePopupContent } from '@shared/contracts'
import { privacyVisible } from '@/composables/usePrivacyGuard'
import { useAppStore } from '@/stores/app'
import { isDecorateLinkConfigured, isWechatContactLink, navigate } from '@/utils/link'
import { commitPopupShown, resolvePopup } from '@/utils/promo'

const props = defineProps<{
    /** 后台下发的 home-popup-ad.content 原始对象 */
    content: Record<string, any>
    /**
     * 每次进入首页自增。
     * tab 页不会重新挂载，只靠 onMounted 判定的话，
     * 「每次进入首页」这个频次实际只会生效一次；用这个 key 驱动重新判定。
     */
    showKey?: number
}>()

const appStore = useAppStore()

const content = computed(() => normalizeHomePopupContent(props.content))

const visible = ref(false)
const imageFailed = ref(false)

/** 纯图片类型不渲染文字；纯文字类型不渲染图；图文两者都渲染 */
const showImage = computed(
    () => content.value.type !== 'text' && !!content.value.image && !imageFailed.value
)
const showBody = computed(
    () =>
        content.value.type !== 'image' &&
        (!!content.value.title || !!content.value.content)
)
const showClose = computed(() => content.value.show_close === 1)

/** 后台可能下发相对路径（本地存储），补上文件域名 */
const imageSrc = computed(() => {
    const url = content.value.image
    if (!url) return ''
    if (/^https?:/i.test(url)) return url
    const domain = (appStore.config as any)?.domain || ''
    return domain ? `${domain}${url}` : url
})

let timer: ReturnType<typeof setTimeout> | null = null
/** 本次展示是否已计过曝光，避免 CTA 与自动曝光重复计数 */
let counted = false

function clearTimer() {
    if (timer) {
        clearTimeout(timer)
        timer = null
    }
}

function countImpression() {
    if (counted) return
    counted = true
    commitPopupShown()
}

/** 判定 + 排期展示；已展示中或已判定不展示时直接返回 */
function evaluate() {
    if (visible.value) return
    clearTimer()

    // 隐私授权弹窗还在时不要叠加第二个模态框：等它关掉再判（见下方 watch）
    if (privacyVisible.value) return

    const { decision } = resolvePopup(props.content)
    if (!decision.show) return

    const delay =
        content.value.show_timing === 'delay' ? Math.max(0, content.value.delay_seconds) : 0
    // 立即展示也延后一帧，避免和首页首屏渲染抢同一帧
    timer = setTimeout(reveal, delay > 0 ? delay * 1000 : 60)
}

function reveal() {
    visible.value = true
    counted = false
    if (content.value.close_counts_as_shown === 1) countImpression()
}

const isContactCta = computed(() => isWechatContactLink(content.value.link))

function onClose() {
    visible.value = false
}

function onContactCta() {
    countImpression()
    visible.value = false
}

function onCta() {
    // 关闭不计曝光的配置下，点主按钮才算一次
    countImpression()
    const link = content.value.link
    visible.value = false
    if (!isDecorateLinkConfigured(link)) return
    navigate(link as any)
}

// 进入首页 / 回到首页都重新判定一次
watch(() => props.showKey, evaluate, { immediate: true })

// 隐私弹窗关闭后补一次判定，避免首次启动时两个模态框打架
watch(privacyVisible, (open) => {
    if (!open) evaluate()
})

// 下拉刷新后装修数据可能变，重新判定（已展示中不重复弹）
watch(
    () => props.content,
    () => {
        counted = false
        evaluate()
    }
)

onBeforeUnmount(clearTimer)
</script>

<style lang="scss" scoped>
.hpa-mask {
    position: fixed;
    inset: 0;
    z-index: 900; /* 高于 tabbar(100)，低于 toast(1000) */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 var(--sp-6);
    background: rgba(24, 22, 20, 0.56);
    animation: hpa-fade var(--dur-fast, 160ms) ease-out;
}

.hpa {
    position: relative;
    width: 100%;
    max-width: 600rpx;
    border-radius: var(--r-lg);
    overflow: hidden;
    box-shadow: var(--shadow-float);
    animation: hpa-pop 220ms cubic-bezier(0.2, 0.8, 0.3, 1);

    &__img {
        display: block;
        width: 100%;
    }

    &__body {
        display: flex;
        flex-direction: column;
        gap: var(--sp-2);
        padding: var(--sp-4) var(--sp-4) 0;
    }

    &__title {
        font-size: var(--fs-lg);
        font-weight: var(--fw-semibold);
        line-height: var(--lh-tight);
        color: var(--c-ink);
    }

    &__text {
        font-size: var(--fs-md);
        line-height: var(--lh-body);
        color: var(--c-ink-2);
    }

    &__actions {
        display: flex;
        gap: var(--sp-2);
        padding: var(--sp-4);
    }

    &__ghost {
        flex: 1;
        height: 80rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-surface-2);
    }

    &__ghost-text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
    }

    &__cta {
        flex: 2;
        height: 80rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: var(--c-ink);

        &--btn {
            margin: 0;
            padding: 0;
            border: none;
            line-height: 1;
            font-size: inherit;
            &::after {
                border: none;
            }
        }
    }

    &__cta-text {
        font-size: var(--fs-md);
        font-weight: var(--fw-semibold);
        color: var(--c-on-dark);
    }

    /* 右上角关闭：压在图上，给一层半透明底保证可见 */
    &__close {
        position: absolute;
        top: var(--sp-2);
        right: var(--sp-2);
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--r-pill);
        background: rgba(24, 22, 20, 0.36);
        color: var(--c-on-dark);
    }
}

@keyframes hpa-fade {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes hpa-pop {
    from {
        opacity: 0;
        transform: scale(0.92);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
</style>
