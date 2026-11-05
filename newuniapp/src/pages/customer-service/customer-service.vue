<template>
    <view class="page-wrap cs-page">
        <view v-if="loading" class="page-pad">
            <base-skeleton :rows="3" />
        </view>

        <template v-else>
            <!-- 顾问卡片 -->
            <view class="card">
                <image class="card__avatar" :src="contact.avatar || defaultAvatar" mode="aspectFill" />
                <view class="card__body">
                    <view class="card__head">
                        <text class="card__name">{{ contact.name }}</text>
                        <text v-if="isAdvisor" class="card__badge">专属顾问</text>
                    </view>
                    <text class="card__role">{{ contact.role }}</text>
                    <text v-if="contact.service_time" class="card__time">{{ contact.service_time }}</text>
                </view>
            </view>

            <text v-if="contact.tips" class="tips">{{ contact.tips }}</text>

            <!-- 主入口：微信客服会话 -->
            <view class="actions">
                <button v-if="chatEnabled" class="chat-btn" open-type="contact">
                    <u-icon name="chat" :size="32" color="var(--c-on-dark)" />
                    <text class="chat-btn__text">进入微信客服</text>
                </button>
                <base-button
                    v-else
                    text="进入微信客服"
                    variant="ink"
                    size="lg"
                    @click="handleNoChat"
                />
            </view>

            <!-- 备用联系方式 -->
            <base-card v-if="contact.mobile" :flush="true">
                <view hover-class="is-pressed" class="row" aria-label="拨打电话" aria-role="button" @tap="callMobile">
                    <u-icon name="phone" :size="32" color="var(--c-ink-2)" aria-hidden="true" />
                    <text class="row__label">拨打电话</text>
                    <text class="row__value">{{ contact.mobile }}</text>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
                <view hover-class="is-pressed" class="row row--last" @tap="copyMobile">
                    <u-icon name="file-text" :size="32" color="var(--c-ink-2)" />
                    <text class="row__label">复制号码</text>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
            </base-card>

            <!-- 自助入口 -->
            <base-card :flush="true">
                <view hover-class="is-pressed" class="row" @tap="go('/packages/pkg-service/pages/aftersale/ticket-create')">
                    <u-icon name="file-text" :size="32" color="var(--c-ink-2)" />
                    <text class="row__label">提交服务工单</text>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
                <view hover-class="is-pressed" class="row" @tap="go('/packages/pkg-service/pages/aftersale/complaint-create')">
                    <u-icon name="warning" :size="32" color="var(--c-ink-2)" />
                    <text class="row__label">投诉与建议</text>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
                <view hover-class="is-pressed" class="row row--last" @tap="go('/packages/pkg-service/pages/aftersale/callback-list')">
                    <u-icon name="chat" :size="32" color="var(--c-ink-2)" />
                    <text class="row__label">服务回访</text>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
            </base-card>
        </template>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
import { toast } from '@/composables/useToast'
/**
 * 在线客服。
 *
 * 主入口用小程序原生「微信客服」会话（button open-type="contact"），
 * 而不是自建聊天 —— 自建聊天需要长连接、消息存储、未读同步，成本远超收益。
 * 未分配顾问时后端返回兜底客服，未登录也能进。
 */
import { computed, reactive, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { startConsult, type ConsultContact } from '@/api/service'
import { navigate } from '@/utils/link'
import { report } from '@/platform/report'

const defaultAvatar = '/static/images/user/default_avatar.png'

const loading = ref(true)
const entryType = ref<'advisor' | 'fallback'>('fallback')
const chatEnabled = ref(true)
const contact = reactive<ConsultContact>({
    name: '统一客服',
    role: '婚礼顾问',
    mobile: '',
    avatar: '',
    service_time: '',
    tips: ''
})

const isAdvisor = computed(() => entryType.value === 'advisor')

async function load(params: Record<string, any>) {
    loading.value = true
    try {
        const res = await startConsult(params)
        if (res) {
            entryType.value = res.entry_type || 'fallback'
            chatEnabled.value = res.customer_service_chat?.enabled !== false
            Object.assign(contact, res.contact || {})
        }
    } catch {
        report('api_error', { endpoint: 'customer_service.startConsult', message: '客服入口初始化失败' })
    } finally {
        loading.value = false
    }
}


function handleNoChat() {
    if (contact.mobile) callMobile()
    else toast.error('客服会话暂不可用，请稍后再试')
}

function callMobile() {
    if (!contact.mobile) return
    uni.makePhoneCall({
        phoneNumber: contact.mobile,
        fail: () => {
            // 用户取消或设备不支持，静默
        }
    })
}

function copyMobile() {
    if (!contact.mobile) return
    uni.setClipboardData({ data: contact.mobile })
}

function go(path: string) {
    navigate({ path })
}

onLoad((options: any) => {
    load({
        scene: options?.scene ? String(options.scene) : 'home',
        staff_id: options?.staff_id ? Number(options.staff_id) : undefined,
        order_id: options?.order_id ? Number(options.order_id) : undefined
    })
})
</script>

<style lang="scss" scoped>
.cs-page {
    background: var(--c-bg);
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-3);
}

.card {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    margin: 0 var(--sp-4);
    padding: var(--sp-4);
    background: var(--c-ink);
    border-radius: var(--r-lg);

    &__avatar {
        width: 120rpx;
        height: 120rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
        min-width: 0;
    }

    &__head {
        display: flex;
        align-items: center;
        gap: var(--sp-2);
    }

    &__name {
        font-size: var(--fs-xl);
        font-weight: 500;
        color: var(--c-on-dark);
    }

    &__badge {
        font-size: var(--fs-xs);
        color: var(--c-on-brand);
        background: var(--c-brand-soft);
        padding: 2rpx 10rpx;
        border-radius: var(--r-xs);
    }

    &__role {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }

    &__time {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }
}

.tips {
    margin: 0 var(--sp-4);
    font-size: var(--fs-xs);
    color: var(--c-ink-3);
    line-height: 1.6;
}

.actions {
    margin: var(--sp-2) var(--sp-4) 0;
}

.chat-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    height: 92rpx;
    border-radius: var(--r-pill);
    background: var(--c-ink);
    color: var(--c-on-dark);
    font-size: var(--fs-lg);
    font-weight: 500;
    line-height: 1;
    margin: 0;

    &::after {
        border: none;
    }

    &__text {
        color: var(--c-on-dark);
        font-size: var(--fs-lg);
    }
}

.row {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    padding: var(--sp-3) var(--sp-4);
    border-bottom: 1rpx solid var(--c-line);

    &--last {
        border-bottom: none;
    }

    &__label {
        flex: 1;
        font-size: var(--fs-md);
        color: var(--c-ink);
    }

    &__value {
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }
}
</style>
