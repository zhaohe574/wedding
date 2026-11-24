<template>
    <div class="pv-service">
        <!-- 顶部导航条 -->
        <div class="pv-service__nav">
            <el-icon class="pv-service__back" :size="16"><ArrowLeft /></el-icon>
            <span class="pv-service__nav-title">客服管家</span>
            <span class="pv-service__nav-spacer" />
        </div>

        <div class="pv-service__scroll">
            <div class="pv-service__body">
                <!-- 统一深色礼宾主卡 -->
                <div class="concierge">
                    <div class="concierge__glow" />

                    <div class="concierge__bar">
                        <span v-if="cfg.eyebrow" class="concierge__eyebrow">{{ cfg.eyebrow }}</span>
                        <span v-else />
                        <div class="status-pill">
                            <span class="status-pill__dot" />
                            <span>礼宾服务</span>
                        </div>
                    </div>

                    <div class="concierge__who">
                        <div class="concierge__av">{{ surname }}</div>
                        <div class="concierge__info">
                            <div class="concierge__name-row">
                                <span class="concierge__name">{{ contact.name }}</span>
                                <span class="concierge__badge">{{ contact.role }}</span>
                            </div>
                            <div class="concierge__hours">
                                <el-icon :size="11"><Clock /></el-icon>
                                <span>{{ contact.service_time }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="concierge__tips">{{ effectiveTips }}</div>

                    <div class="concierge__acts">
                        <div class="cbtn cbtn--ivory">
                            <el-icon :size="14"><ChatDotRound /></el-icon>
                            <span>{{ cfg.button_text }}</span>
                        </div>
                        <div v-if="contact.mobile" class="cbtn cbtn--glass">
                            <el-icon :size="13"><Phone /></el-icon>
                            <span>{{ cfg.phone_button_text }}</span>
                        </div>
                    </div>

                    <div v-if="contact.mobile" class="concierge__meta">
                        <span>客服专线 {{ contact.mobile }}</span>
                        <span class="concierge__meta-cta">
                            <el-icon :size="11"><CopyDocument /></el-icon>
                            <span>复制号码</span>
                        </span>
                    </div>
                </div>

                <!-- 自助服务双联条 -->
                <div v-if="cfg.show_quick_bar === 1" class="quick-bar">
                    <div class="quick-bar__cell quick-bar__cell--left">
                        <div class="quick-bar__ic">
                            <el-icon :size="13"><Tickets /></el-icon>
                        </div>
                        <div class="quick-bar__txt">
                            <div class="quick-bar__title">我的订单</div>
                            <div class="quick-bar__desc">合同与尾款</div>
                        </div>
                        <el-icon class="quick-bar__go" :size="11"><ArrowRight /></el-icon>
                    </div>
                    <div class="quick-bar__cell">
                        <div class="quick-bar__ic">
                            <el-icon :size="13"><Headset /></el-icon>
                        </div>
                        <div class="quick-bar__txt">
                            <div class="quick-bar__title">售后服务</div>
                            <div class="quick-bar__desc">改期 · 工单</div>
                        </div>
                        <el-icon class="quick-bar__go" :size="11"><ArrowRight /></el-icon>
                    </div>
                </div>

                <!-- 服务保障 -->
                <template v-if="cfg.show_assurance === 1 && visibleAssurances.length">
                    <div class="sec">
                        <span class="sec__title">{{ cfg.assurance_title }}</span>
                        <span v-if="cfg.assurance_subtitle" class="sec__sub">
                            {{ cfg.assurance_subtitle }}
                        </span>
                    </div>
                    <div class="assurance-grid">
                        <div
                            v-for="(item, i) in visibleAssurances"
                            :key="`${item.title}-${i}`"
                            class="assurance-cell"
                        >
                            <div class="assurance-cell__head">
                                <div class="assurance-cell__ic">
                                    <el-icon :size="11">
                                        <component :is="iconMap[item.icon] || Check" />
                                    </el-icon>
                                </div>
                                <span class="assurance-cell__title">{{ item.title }}</span>
                            </div>
                            <div v-if="item.desc" class="assurance-cell__desc">{{ item.desc }}</div>
                        </div>
                    </div>
                </template>

                <!-- 常见问题 -->
                <template v-if="cfg.show_faq === 1 && visibleFaqs.length">
                    <div class="sec">
                        <span class="sec__title">{{ cfg.faq_title }}</span>
                        <span v-if="cfg.faq_subtitle" class="sec__sub">{{ cfg.faq_subtitle }}</span>
                    </div>
                    <div class="faq-card">
                        <div v-for="(faq, i) in visibleFaqs" :key="`${faq.q}-${i}`" class="faq-row">
                            <div class="faq-row__head" @click="toggleFaq(i)">
                                <span class="faq-row__idx">{{ String(i + 1).padStart(2, '0') }}</span>
                                <span class="faq-row__q">{{ faq.q }}</span>
                                <el-icon class="faq-row__arrow" :size="11">
                                    <ArrowUp v-if="openFaq === i" />
                                    <ArrowDown v-else />
                                </el-icon>
                            </div>
                            <div v-if="openFaq === i" class="faq-row__a">{{ faq.a }}</div>
                        </div>
                    </div>
                </template>

                <div v-if="cfg.footer_text" class="foot">{{ cfg.footer_text }}</div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
/**
 * 客服管家页预览（后台自绘同构版）。
 * 实时预览右侧可配文案、开关、服务保障与 FAQ，并尽量读取「系统设置 → 客服与咨询」的兜底联系人信息。
 */
import {
    ArrowDown,
    ArrowLeft,
    ArrowRight,
    ArrowUp,
    Bell,
    ChatDotRound,
    Check,
    Clock,
    CopyDocument,
    Headset,
    Phone,
    Star,
    Tickets,
    Wallet
} from '@element-plus/icons-vue'
import {
    type CustomerServiceAssuranceIcon,
    normalizeCustomerServiceContent
} from '@shared/contracts/decorate'
import { computed, onMounted, reactive, ref } from 'vue'

import { customerServiceGetConfig } from '@/api/setting/customerService'

const props = defineProps<{
    content: Record<string, any>
}>()

const iconMap: Record<CustomerServiceAssuranceIcon, any> = {
    check: Check,
    pay: Wallet,
    clock: Clock,
    like: Star,
    star: Star,
    notice: Bell
}

const cfg = computed(() => normalizeCustomerServiceContent(props.content))

const contact = reactive({
    name: '统一客服',
    role: '婚礼顾问',
    service_time: '工作日 09:00 - 18:00',
    tips: '',
    mobile: ''
})

const openFaq = ref(-1)
function toggleFaq(index: number) {
    openFaq.value = openFaq.value === index ? -1 : index
}

onMounted(async () => {
    try {
        const res: any = await customerServiceGetConfig()
        if (res && typeof res === 'object') {
            contact.name = String(res.name || '').trim() || '统一客服'
            contact.role = String(res.role || '').trim() || '婚礼顾问'
            contact.service_time = String(res.service_time || '').trim() || '工作日 09:00 - 18:00'
            contact.tips = String(res.tips || '').trim()
            contact.mobile = String(res.mobile || '').trim()
        }
    } catch {
        // 缺少系统设置权限或接口异常时保留默认样例
    }
})

const surname = computed(() => contact.name.slice(0, 1) || '客')
const effectiveTips = computed(() => contact.tips || cfg.value.tips)
const visibleAssurances = computed(() =>
    cfg.value.assurances.filter((item) => item.title.trim().length > 0)
)
const visibleFaqs = computed(() =>
    cfg.value.faqs.filter((item) => item.q.trim().length > 0 && item.a.trim().length > 0)
)
</script>

<style lang="scss" scoped>
@use './preview-tokens.scss' as *;

.pv-service {
    --pv-rpx: 0.5px;
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: $bg;
    font-family: $font-sans;
    -webkit-font-smoothing: antialiased;

    &__nav {
        flex: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 48px;
        padding: 10px rpx(32) 6px;
        background: $bg;
    }

    &__back {
        color: $ink;
    }

    &__nav-title {
        font-size: $fs-md;
        font-weight: 600;
        color: $ink;
    }

    &__nav-spacer {
        width: 16px;
    }

    &__scroll {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
    }

    &__body {
        padding: 0 rpx(42) rpx(48);
    }
}

.concierge {
    position: relative;
    margin-top: rpx(16);
    border-radius: $r-lg;
    padding: rpx(34) rpx(32) rpx(30);
    overflow: hidden;
    background: linear-gradient(150deg, #211d19 0%, #14110f 100%);
    border: 1px solid $line-strong;

    &__glow {
        position: absolute;
        top: rpx(-80);
        right: rpx(-60);
        width: rpx(280);
        height: rpx(280);
        border-radius: $r-pill;
        background: radial-gradient(circle, rgba(217, 190, 130, 0.14) 0%, rgba(217, 190, 130, 0) 70%);
        pointer-events: none;
    }

    &__bar {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    &__eyebrow {
        font-family: $font-serif;
        font-size: rpx(18);
        letter-spacing: 0.22em;
        color: $brand-soft;
        line-height: 1;
    }

    &__who {
        position: relative;
        display: flex;
        align-items: center;
        gap: rpx(24);
        margin-top: rpx(24);
    }

    &__av {
        width: rpx(96);
        height: rpx(96);
        border-radius: $r-pill;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: $font-serif;
        font-size: rpx(38);
        color: $brand-soft;
        background: #1f1a15;
        border: 1px solid rgba(217, 190, 130, 0.45);
    }

    &__info {
        flex: 1;
        min-width: 0;
    }

    &__name-row {
        display: flex;
        align-items: center;
        gap: rpx(12);
        flex-wrap: wrap;
    }

    &__name {
        font-size: rpx(34);
        font-weight: 600;
        color: $on-dark;
        line-height: 1.25;
    }

    &__badge {
        padding: rpx(4) rpx(14);
        border-radius: $r-pill;
        font-size: rpx(20);
        color: $brand-soft;
        background: rgba(198, 161, 91, 0.14);
        border: 1px solid rgba(217, 190, 130, 0.34);
    }

    &__hours {
        display: flex;
        align-items: center;
        gap: rpx(8);
        margin-top: rpx(10);
        font-size: rpx(22);
        color: $on-dark-2;
    }

    &__tips {
        position: relative;
        margin-top: rpx(22);
        font-size: rpx(22);
        line-height: 1.55;
        color: $on-dark-2;
    }

    &__acts {
        position: relative;
        display: flex;
        align-items: center;
        gap: rpx(16);
        margin-top: rpx(26);
    }

    &__meta {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: rpx(24);
        padding-top: rpx(20);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: rpx(22);
        color: $on-dark-2;
    }

    &__meta-cta {
        display: inline-flex;
        align-items: center;
        gap: rpx(6);
        color: $brand-soft;
    }
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: rpx(8);
    padding: rpx(6) rpx(16);
    border-radius: $r-pill;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    font-size: rpx(20);
    color: $on-dark-2;

    &__dot {
        width: rpx(10);
        height: rpx(10);
        border-radius: $r-pill;
        background: #a9c0ac;
    }
}

.cbtn {
    flex: 1;
    min-width: 0;
    height: rpx(88);
    border-radius: $r-pill;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: rpx(10);
    font-size: $fs-md;

    &--ivory {
        background: $surface;
        color: $ink;
        font-weight: 600;
    }

    &--glass {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.24);
        color: $on-dark;
        font-weight: 500;
    }
}

.quick-bar {
    display: flex;
    align-items: stretch;
    margin-top: rpx(24);
    border-radius: $r-md;
    background: $surface;
    border: 1px solid $line;
    overflow: hidden;

    &__cell {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: rpx(16);
        padding: rpx(26) rpx(24);

        &--left {
            border-right: 1px solid $line;
        }
    }

    &__ic {
        flex: none;
        width: rpx(52);
        height: rpx(52);
        border-radius: rpx(8);
        background: $brand-bg;
        color: $brand-text;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__txt {
        flex: 1;
        min-width: 0;
    }

    &__title {
        font-size: rpx(26);
        font-weight: 500;
        color: $ink;
    }

    &__desc {
        margin-top: rpx(4);
        font-size: rpx(20);
        color: $ink-3;
    }

    &__go {
        flex: none;
        color: $ink-3;
    }
}

.sec {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin: rpx(40) rpx(4) rpx(16);

    &__title {
        font-size: rpx(26);
        font-weight: 600;
        color: $ink;
    }

    &__sub {
        font-size: rpx(20);
        color: $ink-3;
    }
}

.assurance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border-radius: $r-md;
    background: $surface;
    border: 1px solid $line;
    overflow: hidden;
}

.assurance-cell {
    padding: rpx(26) rpx(24);
    min-width: 0;

    &:nth-child(odd) {
        border-right: 1px solid $line;
    }

    &:nth-child(n + 3) {
        border-top: 1px solid $line;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: rpx(12);
    }

    &__ic {
        flex: none;
        width: rpx(40);
        height: rpx(40);
        border-radius: rpx(8);
        background: $brand-bg;
        color: $brand-text;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__title {
        font-size: rpx(26);
        font-weight: 600;
        color: $ink;
    }

    &__desc {
        margin-top: rpx(10);
        font-size: rpx(22);
        color: $ink-3;
        line-height: 1.3;
    }
}

.faq-card {
    padding: 0 rpx(32);
    border-radius: $r-md;
    background: $surface;
    border: 1px solid $line;
}

.faq-row {
    border-bottom: 1px solid $line;

    &:last-child {
        border-bottom: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: rpx(12);
        min-height: rpx(92);
        padding: rpx(18) 0;
        cursor: pointer;
    }

    &__idx {
        flex: none;
        width: rpx(34);
        font-family: $font-serif;
        font-size: rpx(22);
        color: $brand-text;
    }

    &__q {
        flex: 1;
        min-width: 0;
        font-size: rpx(26);
        color: $ink;
        line-height: 1.5;
    }

    &__arrow {
        flex: none;
        color: $ink-3;
    }

    &__a {
        padding: 0 0 rpx(26) rpx(46);
        font-size: $fs-xs;
        line-height: 1.65;
        color: $ink-2;
    }
}

.foot {
    margin-top: rpx(40);
    text-align: center;
    font-size: rpx(20);
    line-height: 1.55;
    color: $ink-3;
}
</style>
