<template>
    <!--
        14 我的收藏（设计稿 3271–3453）
        设计意图：收藏不是储物柜，是「还没决定的这几个人」；取消收藏就在卡上，不进编辑态。

        数据现实（决定了本屏与设计稿的差异，均按「不编造」处理）：
        - /staff/myFavorites 只返回 id / name / avatar / rating / category_name，无分页
        - 没有 price、没有 rank（首席/资深）、没有 tags、没有当日可约性
        因此设计稿的「¥3,800 起」「仅剩 1 档」「05.20 可约」三个元素全部不渲染，
        顶部「你选的 05.20 有 3 位可约」同理（既没有婚期也没有可用性接口）。
        筛选只保留能从数据真实派生的分类计数。
    -->
    <page-shell title="我的收藏" bg="page" :show-back="true">
        <view v-if="loading" class="pad">
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
            @retry="load"
        />

        <base-empty
            v-else-if="!list.length"
            status="empty"
            empty-icon="empty-favor"
            empty-text="还没有收藏的主持人"
            desc="看到合适的先收藏，锁档前再慢慢比。"
            action-text="去挑选"
            :show-action="true"
            fixed
            @action="goList"
        />

        <view v-else class="fav">
            <!-- 分类筛选：计数从数据真实派生，没有可约性数据就不做那个 chip -->
            <view class="fav__f">
                <view
                    v-for="c in cats"
                    :key="c.key"
                    class="chip hit-88"
                    :class="{ 'is-on': activeCat === c.key }"
                    hover-class="is-pressed"
                    @tap="pickCat(c.key)"
                >
                    <text class="chip__text t-num">{{ c.label }}</text>
                </view>
            </view>

            <view class="fcard-list" :class="{ stagger: staggerOn, 'is-in': staggerIn }">
                <view
                    v-for="(item, index) in shown"
                    :key="item.id"
                    class="fcard"
                    :style="{ '--i': index + 1 }"
                    hover-class="is-pressed"
                    @tap="goDetail(item)"
                >
                    <view class="fcard__top">
                        <image
                            v-if="item.avatar"
                            class="fcard__av"
                            :src="imageUrl(item.avatar)"
                            mode="aspectFill"
                        />
                        <view v-else class="fcard__av fcard__av--txt">
                            <text class="fcard__av-text">{{ (item.name || '·').slice(0, 1) }}</text>
                        </view>

                        <view class="fcard__info">
                            <view class="fcard__nm">
                                <text class="fcard__name">{{ item.name || '未命名' }}</text>
                            </view>
                            <view v-if="item.rating" class="fcard__rate">
                                <tn-icon name="star-fill" :size="20" />
                                <text class="fcard__rate-num t-num">{{ item.rating }}</text>
                            </view>
                            <text v-if="item.category_name" class="fcard__tg">
                                {{ item.category_name }}
                            </text>
                        </view>
                    </view>

                    <view
                        class="fcard__heart hit-88"
                        :hover-stop-propagation="true"
                        hover-class="is-pressed"
                        aria-role="button"
                        :aria-label="`取消收藏${item.name || ''}`"
                        @tap.stop="remove(item)"
                    >
                        <tn-icon name="like-fill" :size="30" />
                    </view>
                </view>
            </view>

            <view class="fav__tip">
                <text class="fav__tip-b">收藏不占档期</text>
                <text class="fav__tip-p"
                    >看到合适的，先锁档再慢慢比。热门日期的档期随时可能被别人订走。</text
                >
            </view>
        </view>
    </page-shell>
    <app-toast />
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { resolveErrorView } from '@/utils/resolveErrorView'
import { getMyFavorites, toggleStaffFavorite } from '@/api/catalog'
import { useAppStore } from '@/stores/app'
import { haptic } from '@/utils/haptics'
import { navigate } from '@/utils/link'
import { toast } from '@/composables/useToast'
import { report } from '@/platform/report'

const appStore = useAppStore()

const list = ref<any[]>([])
const loading = ref(true)
const rawError = ref<unknown>(null)
const error = computed(() => !!rawError.value)
const view = computed(() => resolveErrorView(rawError.value as any, { hasForm: false }))
const activeCat = ref('all')

/** 分类计数：真实派生，不补任何虚构项 */
const cats = computed(() => {
    const map = new Map<string, number>()
    list.value.forEach((i) => {
        const k = (i.category_name || '').trim()
        if (!k) return
        map.set(k, (map.get(k) || 0) + 1)
    })
    const out = [{ key: 'all', label: `全部 ${list.value.length}` }]
    map.forEach((n, k) => out.push({ key: k, label: `${k} ${n}` }))
    return out
})

const shown = computed(() => {
    if (activeCat.value === 'all') return list.value
    return list.value.filter((i) => (i.category_name || '').trim() === activeCat.value)
})

/* ---- 首屏错峰入场（§3.3）：先挂 .stagger，下一拍再放 .is-in，动画才有起点 ---- */
const staggerOn = ref(false)
const staggerIn = ref(false)
let staggerTimer: ReturnType<typeof setTimeout> | null = null

function playStagger() {
    if (!shown.value.length || staggerIn.value) return
    staggerOn.value = true
    if (staggerTimer) return
    staggerTimer = setTimeout(() => {
        staggerTimer = null
        staggerIn.value = true
    }, 40)
}

watch(
    () => shown.value.length,
    (len) => {
        if (len) playStagger()
    }
)

onUnmounted(() => {
    if (staggerTimer) clearTimeout(staggerTimer)
})

function pickCat(key: string) {
    if (activeCat.value === key) return
    activeCat.value = key
}

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
        navigate({ path: '/pages/login/login' })
    }
}

function imageUrl(url?: string) {
    if (!url) return ''
    if (/^https?:/.test(url)) return url
    const domain = (appStore.config && appStore.config.domain) || ''
    return domain ? `${domain}${url}` : url
}

async function load() {
    loading.value = true
    rawError.value = null
    try {
        const res: any = await getMyFavorites()
        list.value = Array.isArray(res) ? res : Array.isArray(res?.data) ? res.data : []
        if (activeCat.value !== 'all' && !cats.value.some((c) => c.key === activeCat.value)) {
            activeCat.value = 'all'
        }
    } catch (e) {
        rawError.value = e
        report('api_error', { endpoint: 'staff.myFavorites', message: '收藏列表加载失败' })
    } finally {
        loading.value = false
    }
}

async function remove(item: any) {
    // 乐观移除 + 失败回滚：取消收藏是轻动作，不该等一个 loading
    const idx = list.value.findIndex((i) => i.id === item.id)
    if (idx < 0) return
    haptic('light', 'favorite-remove')
    const backup = list.value.slice()
    list.value.splice(idx, 1)
    try {
        await toggleStaffFavorite({ id: item.id })
        toast.info('已取消收藏')
    } catch {
        list.value = backup
        toast.error('取消收藏失败，请重试')
    }
}

function goDetail(item: any) {
    navigate({
        path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
        query: { id: item.id }
    })
}

function goList() {
    navigate({ path: '/pages/query/query' })
}

onShow(() => load())
</script>

<style lang="scss" scoped>
.pad {
    padding: var(--sp-4);
}

.fav {
    padding: 0 0 calc(var(--sp-6) + var(--safe-bottom));

    &__f {
        display: flex;
        gap: 12rpx;
        padding: 24rpx 42rpx;
        overflow-x: auto;
        white-space: nowrap;
    }

    &__tip {
        margin: 24rpx 42rpx 0;
        padding: 24rpx 26rpx;
        border-radius: var(--r-md);
        background: var(--c-brand-bg);
    }

    &__tip-b {
        display: block;
        font-size: var(--fs-xs);
        font-weight: var(--fw-semibold);
        color: var(--c-brand-text);
    }

    &__tip-p {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-21);
        line-height: var(--lh-loose);
        color: var(--c-ink-2);
    }
}

.chip {
    flex: none;
    height: 52rpx;
    padding: 0 22rpx;
    border-radius: var(--r-pill);
    border: 1rpx solid var(--c-control-border);
    background: var(--c-surface);
    display: flex;
    align-items: center;
    transition:
        background-color var(--dur-fast) var(--ease-out),
        border-color var(--dur-fast) var(--ease-out),
        transform var(--dur-press) var(--ease-out);

    &__text {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
    }

    &.is-pressed {
        transform: scale(0.97);
    }

    &.is-on {
        background: var(--c-active-bg);
        border-color: var(--c-active-border);

        .chip__text {
            color: var(--c-active-fg);
        }
    }
}

@media (prefers-reduced-motion: reduce) {
    .chip {
        transition: none;

        &.is-pressed {
            transform: none;
        }
    }
}

/* 同心圆角：外卡 40rpx − 内边距 24rpx = 头像 --r-16 */
.fcard {
    position: relative;
    margin: 0 42rpx 18rpx;
    padding: 24rpx;
    border-radius: var(--r-lg);
    background: var(--c-surface);
    border: 1rpx solid var(--c-card-border);
    box-shadow: var(--shadow-card);

    &__top {
        display: flex;
        gap: 20rpx;
    }

    &__av {
        width: 92rpx;
        height: 92rpx;
        border-radius: var(--r-16);
        background: var(--c-neutral-bg);
        flex: none;

        &--txt {
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--c-brand-bg);
        }
    }

    &__av-text {
        font-family: var(--font-serif);
        font-size: var(--fs-lg);
        color: var(--c-brand-text);
    }

    &__info {
        flex: 1;
        min-width: 0;
        padding-right: 64rpx;
    }

    &__nm {
        display: flex;
        align-items: center;
        gap: 10rpx;
    }

    &__name {
        font-size: var(--fs-30);
        font-weight: var(--fw-semibold);
        color: var(--c-ink);
        letter-spacing: 0.02em;
    }

    &__rate {
        display: flex;
        align-items: center;
        gap: 6rpx;
        margin-top: 8rpx;
        /* 星标；.fcard__rate-num 自己声明了 --c-ink-2 */
        color: var(--c-warning);
    }

    &__rate-num {
        font-size: var(--fs-22);
        color: var(--c-ink-2);
    }

    &__tg {
        display: block;
        margin-top: 8rpx;
        font-size: var(--fs-21);
        color: var(--c-ink-3);
    }

    &__heart {
        /* 心形图标，此容器只放图标 */
        color: var(--c-brand-text);
        position: absolute;
        right: 22rpx;
        top: 22rpx;
        width: 56rpx;
        height: 56rpx;
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
</style>
