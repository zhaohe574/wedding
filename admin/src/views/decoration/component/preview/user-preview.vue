<template>
    <div class="pv-user">
        <div class="pv-user__nav">
            <span class="pv-user__title">我的</span>
        </div>

        <div class="pv-user__scroll">
            <!-- 用户信息：页面固定内容（/v2/me 驱动），不可装修 -->
            <div class="pv-user__profile">
                <div class="profile__avatar">贺</div>
                <div class="profile__txt">
                    <div class="profile__name">
                        贺贺
                        <el-icon :size="12"><Edit /></el-icon>
                    </div>
                    <div class="profile__sub">
                        {{ cardState === 'empty' ? '准新人 · 还没预约档期' : '准新人 · 婚期 2026-12-20' }}
                    </div>
                </div>
            </div>

            <!-- 婚期卡片：读 wedding-countdown 装修配置 -->
            <div
                class="pv-block pv-user__card-wrap"
                :class="{ 'is-active': isActive('wedding-countdown') }"
                @click="select('wedding-countdown')"
            >
                <span v-if="isActive('wedding-countdown')" class="pv-block__tag">婚期卡片</span>
                <div v-if="countdown.enabled === 1" class="cd">
                    <button class="cd__toggle" @click.stop="toggleState">
                        {{ cardState === 'empty' ? '预览已预约' : '预览未预约' }}
                    </button>
                    <div class="cd__label">{{ countdown.label }}</div>

                    <template v-if="cardState === 'empty'">
                        <div class="cd__empty-title">{{ countdown.empty_title }}</div>
                        <div class="cd__empty-desc">{{ countdown.empty_desc }}</div>
                        <div class="cd__action">{{ countdown.empty_action_text }}</div>
                    </template>

                    <template v-else>
                        <div class="cd__num-row">
                            <span class="cd__num">88</span>
                            <span class="cd__unit">{{ countdown.unit_text }}</span>
                        </div>
                        <div v-if="countdown.show_progress === 1" class="cd__progress">
                            <div class="cd__progress-head">
                                <span>筹备进度</span>
                                <span class="cd__progress-value">60%</span>
                            </div>
                            <div class="cd__progress-bar">
                                <span
                                    v-for="seg in 5"
                                    :key="seg"
                                    class="cd__seg"
                                    :class="{ 'is-done': seg <= 3, 'is-current': seg === 4 }"
                                />
                            </div>
                            <div class="cd__nodes">
                                <span
                                    v-for="(label, index) in milestones"
                                    :key="label"
                                    class="cd__node"
                                    :class="{ 'is-done': index < 3, 'is-current': index === 3 }"
                                >
                                    <i class="cd__dot" />{{ label }}
                                </span>
                            </div>
                        </div>
                        <div class="cd__foot">
                            <span>还有 3 项待确认</span>
                            <span class="cd__venue">宴会厅</span>
                        </div>
                    </template>
                </div>
                <div v-else class="cd cd--hidden">已关闭，真机不显示婚期卡</div>
            </div>

            <!-- 四格资产：页面固定内容 -->
            <div class="pv-user__metrics">
                <div v-for="m in metrics" :key="m.key" class="metric">
                    <span class="metric__value">{{ m.value }}</span>
                    <span class="metric__label">{{ m.label }}</span>
                </div>
            </div>

            <!-- 我的订单 -->
            <div class="panel">
                <div class="panel__head">
                    <span class="panel__title">我的订单</span>
                    <span class="panel__more">全部订单 ›</span>
                </div>
                <div class="orders">
                    <div v-for="o in orders" :key="o.key" class="order-cell">
                        <div class="order-cell__icon">
                            <el-icon :size="16"><component :is="o.icon" /></el-icon>
                            <span
                                v-if="o.count > 0"
                                class="order-cell__badge"
                                :class="{ 'order-cell__badge--urgent': o.urgent }"
                                >{{ o.count }}</span
                            >
                        </div>
                        <span class="order-cell__label">{{ o.label }}</span>
                    </div>
                </div>
            </div>

            <!-- 我的服务：quick-entry 实时配置 -->
            <div
                v-if="quick.enabled === 1 && quickRows.length"
                class="pv-block panel pv-user__services"
                :class="{ 'is-active': isActive('quick-entry') }"
                @click="select('quick-entry')"
            >
                <span v-if="isActive('quick-entry')" class="pv-block__tag">快捷入口</span>
                <div class="panel__head">
                    <span class="panel__title">{{ quick.title }}</span>
                </div>
                <div
                    v-for="(row, i) in quickRows"
                    :key="row.key || row.title + i"
                    class="row"
                    :class="{ 'row--last': i === quickRows.length - 1 }"
                >
                    <div class="row__txt">
                        <span class="row__label">{{ row.title }}</span>
                        <span v-if="row.subtitle" class="row__sub">{{ row.subtitle }}</span>
                    </div>
                    <div class="row__right">
                        <span v-if="quickValue(row.key)" class="row__value">
                            {{ quickValue(row.key) }}
                        </span>
                        <span class="row__arrow">›</span>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="pv-block pv-user__hidden"
                :class="{ 'is-active': isActive('quick-entry') }"
                @click="select('quick-entry')"
            >
                <span v-if="isActive('quick-entry')" class="pv-block__tag">快捷入口</span>
                已关闭或列表为空：真机不显示「{{ quick.title }}」面板
            </div>

            <!-- 更多 -->
            <div class="panel">
                <div class="panel__head">
                    <span class="panel__title">更多</span>
                </div>
                <div
                    v-for="(row, i) in more"
                    :key="row"
                    class="row"
                    :class="{ 'row--last': i === more.length - 1 }"
                >
                    <span class="row__label">{{ row }}</span>
                    <span class="row__arrow">›</span>
                </div>
            </div>

            <div class="pv-user__ver">{{ brand?.name || '婚礼纪' }} · V1.0.0</div>
        </div>

        <!-- 自定义 tabbar -->
        <div class="pv-user__tabbar">
            <div v-for="tab in tabs" :key="tab.key" class="tab" :class="{ 'tab--active': tab.active }">
                <el-icon :size="18"><component :is="tab.icon" /></el-icon>
                <span class="tab__label">{{ tab.label }}</span>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import {
    Calendar,
    ChatDotRound,
    CircleCheck,
    Clock,
    Edit,
    Headset,
    HomeFilled,
    UserFilled,
    Wallet
} from '@element-plus/icons-vue'
import {
    isDecorateLinkConfigured,
    normalizeQuickEntryContent,
    normalizeWeddingCountdownContent
} from '@shared/contracts/decorate'
import { computed, ref } from 'vue'

const props = defineProps<{
    /** 当前页的组件数据（编辑态草稿） */
    widgets: any[]
    /** 当前选中的组件下标 */
    selected?: number
    /** 品牌名，用于底部版本串 */
    brand?: { name?: string } | null
}>()

const emit = defineEmits<{ (e: 'select', index: number): void }>()

const indexOf = (name: string) => props.widgets.findIndex((item) => item?.name === name)
const isActive = (name: string) => indexOf(name) === props.selected

function select(name: string) {
    const index = indexOf(name)
    if (index >= 0) emit('select', index)
}

const weddingWidget = computed(() =>
    props.widgets.find((item) => item?.name === 'wedding-countdown')
)
const countdown = computed(() => normalizeWeddingCountdownContent(weddingWidget.value?.content))

const quickWidget = computed(() => props.widgets.find((item) => item?.name === 'quick-entry'))
const quick = computed(() => normalizeQuickEntryContent(quickWidget.value?.content))
const quickRows = computed(() =>
    quick.value.data.filter(
        (item) => item.is_show === '1' && item.title.trim() && isDecorateLinkConfigured(item.link)
    )
)

/** 样例数量只看认识的 key，避免给自定义入口编数据 */
function quickValue(key: string) {
    if (key === 'favorite') return '12'
    if (key === 'activity') return '3 场待参加'
    if (key === 'waitlist') return '1'
    return ''
}

/** 预览专用：在「未填婚期 / 有婚期」两种真实状态间切换 */
const cardState = ref<'empty' | 'filled'>('empty')
function toggleState() {
    cardState.value = cardState.value === 'empty' ? 'filled' : 'empty'
}

/** 与真机同序的 5 个里程碑；示例完成 3 个（60%） */
const milestones = ['锁定档期', '新人问卷', '尾款结清', '婚礼当天', '服务完成']

/** 四格与订单、服务行都是页面固定内容；数值用样例，不代表真实用户 */
const metrics = [
    { key: 'favorite', label: '收藏', value: '12' },
    { key: 'waitlist', label: '候补', value: '1' },
    { key: 'activity', label: '活动', value: '3' },
    { key: 'message', label: '消息', value: '2' }
]

const orders = [
    { key: 'confirm', label: '待确认', count: 1, urgent: false, icon: Clock },
    { key: 'pay', label: '待支付', count: 1, urgent: true, icon: Wallet },
    { key: 'service', label: '待服务', count: 2, urgent: false, icon: Calendar },
    { key: 'doing', label: '服务中', count: 1, urgent: false, icon: CircleCheck },
    { key: 'refund', label: '售后', count: 0, urgent: false, icon: Headset }
]

/** 普通访客视角：服务人员工作台仅 is_staff=1 时出现，预览不展示 */
const more = ['我的钱包', '通知与提醒', '客服与帮助', '关于我们']

const tabs = [
    { key: 'home', label: '首页', icon: HomeFilled, active: false },
    { key: 'feed', label: '动态', icon: ChatDotRound, active: false },
    { key: 'query', label: '档期', icon: Calendar, active: false },
    { key: 'user', label: '我的', icon: UserFilled, active: true }
]
</script>

<style lang="scss" scoped>
@use './preview-tokens.scss' as *;

.pv-user {
    --pv-rpx: 0.5px;
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    /* 顶部暖渐变与背景自然融合，Profile 不再压在白卡上 */
    background: linear-gradient(180deg, #f5eddd 0%, #faf6ee 220px);
    font-family: $font-sans;
    -webkit-font-smoothing: antialiased;

    &__nav {
        position: sticky;
        top: 0;
        z-index: 6;
        display: flex;
        align-items: flex-end;
        height: 88px;
        padding: 0 21px 12px;
        background: linear-gradient(180deg, rgba(245, 237, 221, 0.96) 0%, rgba(245, 237, 221, 0.72) 100%);
        backdrop-filter: blur(10px);
    }

    &__title {
        font-family: $font-serif;
        font-size: 26px;
        line-height: 1;
        letter-spacing: 0.02em;
        color: $ink;
    }

    &__scroll {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding-bottom: 12px;
    }

    &__profile {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 18px 21px 8px;
    }

    &__card-wrap {
        padding: 8px 21px 0;
    }

    &__metrics {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        margin: 14px 21px 0;
        padding: 4px 0 2px;
    }

    &__ver {
        margin: 18px 0 8px;
        text-align: center;
        font-size: 10px;
        color: $ink-3;
    }

    &__hidden {
        margin: 12px 21px 0;
        padding: 16px;
        border: 1px dashed $line-strong;
        border-radius: $r-md;
        background: rgba(24, 22, 20, 0.04);
        font-size: 12px;
        color: $ink-2;
    }

    &__tabbar {
        flex: none;
        z-index: 7;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        height: 56px;
        border-top: 1px solid $line;
        background: rgba(255, 253, 248, 0.98);
        backdrop-filter: blur(10px);
    }
}

/* ---------------- 选中态 ---------------- */
.pv-block {
    position: relative;

    &::after {
        content: '';
        position: absolute;
        inset: 0;
        border: 1px dashed transparent;
        pointer-events: none;
        z-index: 8;
    }

    &:hover::after {
        border-color: rgba(64, 158, 255, 0.65);
    }

    &.is-active::after {
        border: 1px solid #409eff;
    }

    &__tag {
        position: absolute;
        top: 4px;
        right: 6px;
        z-index: 9;
        padding: 1px 6px;
        border-radius: 4px;
        background: #409eff;
        color: #fff;
        font-size: 10px;
        line-height: 16px;
    }
}

.profile {
    &__avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: $surface-2;
        color: $ink;
        font-family: $font-serif;
        font-size: 20px;
    }

    &__txt {
        min-width: 0;
    }

    &__name {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 18px;
        font-weight: 600;
        color: $ink;
    }

    &__sub {
        margin-top: 4px;
        font-size: 12px;
        color: $ink-2;
    }
}

.cd {
    position: relative;
    padding: 18px;
    border: 1px solid $line-strong;
    border-radius: $r-md;
    background: $ink;

    &__toggle {
        position: absolute;
        top: 8px;
        right: 8px;
        padding: 2px 8px;
        border: 1px solid rgba(255, 255, 255, 0.28);
        border-radius: $r-pill;
        background: rgba(255, 255, 255, 0.08);
        color: $on-dark-2;
        font-size: 10px;
        cursor: pointer;
    }

    &__label {
        padding-right: 58px;
        font-family: $font-serif;
        font-size: 12px;
        letter-spacing: 0.2em;
        color: $on-dark-2;
    }

    &__num-row {
        display: flex;
        align-items: baseline;
        gap: 6px;
        margin-top: 9px;
    }

    &__num {
        font-family: $font-serif;
        font-size: 36px;
        line-height: 1;
        color: $on-dark;
        font-variant-numeric: tabular-nums;
    }

    &__unit {
        font-size: 12px;
        letter-spacing: 0.06em;
        color: $on-dark-2;
    }

    &__foot {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-top: 12px;
        font-size: 12px;
        color: $on-dark-2;
    }

    &__progress {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    &__progress-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 8px;
        font-size: 10px;
        letter-spacing: 0.08em;
        color: rgba(255, 255, 255, 0.55);
    }

    &__progress-value {
        font-family: $font-serif;
        font-size: 16px;
        color: $brand-soft;
        font-variant-numeric: tabular-nums;
    }

    &__progress-bar {
        display: flex;
        gap: 4px;
        margin-top: 8px;
    }

    &__seg {
        flex: 1;
        height: 3px;
        border-radius: $r-pill;
        background: rgba(255, 255, 255, 0.14);

        &.is-done {
            background: $brand-soft;
        }

        &.is-current {
            background: linear-gradient(90deg, $brand-soft 0%, rgba(217, 190, 130, 0.4) 100%);
        }
    }

    &__nodes {
        display: flex;
        justify-content: space-between;
        gap: 3px;
        margin-top: 8px;
    }

    &__node {
        display: flex;
        align-items: center;
        gap: 3px;
        font-size: 9px;
        line-height: 1.3;
        white-space: nowrap;
        color: rgba(255, 255, 255, 0.35);

        &.is-done {
            color: rgba(217, 190, 130, 0.85);
        }

        &.is-current {
            color: $brand-soft;
        }
    }

    &__dot {
        width: 4px;
        height: 4px;
        border-radius: $r-pill;
        background: currentColor;
        flex: none;
    }

    &__venue {
        flex: none;
        color: $brand-soft;
    }

    &__empty-title {
        margin-top: 8px;
        font-size: 16px;
        font-weight: 500;
        color: $on-dark;
    }

    &__empty-desc {
        margin-top: 6px;
        font-size: 12px;
        line-height: 1.6;
        color: $on-dark-2;
    }

    &__action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 10px;
        padding: 0 14px;
        height: 30px;
        border-radius: $r-pill;
        background: $brand;
        color: $ink;
        font-size: 12px;
        font-weight: 500;
    }

    &--hidden {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 88px;
        border-style: dashed;
        background: rgba(24, 22, 20, 0.06);
        color: $ink-2;
        font-size: 12px;
    }
}

.metric {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;

    &__value {
        font-family: $font-serif;
        font-size: 20px;
        color: $ink;
        font-variant-numeric: tabular-nums;
    }

    &__label {
        font-size: 11px;
        color: $ink-2;
    }
}

.panel {
    margin: 12px 21px 0;
    border: 1px solid $line;
    border-radius: $r-md;
    background: $surface;

    &__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 13px 14px 0;
    }

    &__head + .row {
        margin-top: 8px;
    }

    &__title {
        font-size: 14px;
        font-weight: 600;
        color: $ink;
    }

    &__more {
        font-size: 11px;
        color: $ink-3;
    }
}

.orders {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    padding: 14px 4px 16px;
}

.order-cell {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;

    &__icon {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        color: $ink-2;
    }

    &__badge {
        position: absolute;
        top: -5px;
        right: -2px;
        min-width: 0;
        height: auto;
        padding: 0 1px;
        border-radius: 0;
        background: transparent;
        color: $brand-text;
        font-size: 9px;
        font-weight: 600;
        line-height: 1.2;
        text-align: center;

        &--urgent {
            background: transparent;
            color: #a33b2c;
        }
    }

    &__label {
        font-size: 11px;
        color: $ink-2;
    }
}

.row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px;
    height: 46px;
    border-top: 1px solid $line;

    &:first-of-type {
        margin-top: 10px;
    }

    &--last {
        border-bottom: none;
    }

    &__txt {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    &__label {
        font-size: 13px;
        color: $ink;
    }

    &__sub {
        font-size: 11px;
        color: $ink-3;
    }

    &__right {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    &__value {
        font-size: 11px;
        color: $ink-3;
    }

    &__arrow {
        font-size: 18px;
        line-height: 1;
        color: $ink-3;
    }
}

.tab {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    color: $ink-3;

    &--active {
        color: $ink;
    }

    &__label {
        font-size: 10px;
    }
}
</style>
