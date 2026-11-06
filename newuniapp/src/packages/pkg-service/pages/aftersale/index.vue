<template>
    <view class="page-wrap as-page">
        <!-- 统计 -->
        <view class="stats">
            <view class="stats__heading">
                <text class="stats__title">售后服务</text>
                <text class="stats__desc">工单、投诉与回访进度统一在这里查看</text>
            </view>
            <view class="stats__row">
            <view class="stats__item">
                <text class="stats__num">{{ stats.unfinished ?? 0 }}</text>
                <text class="stats__label">处理中</text>
            </view>
            <view class="stats__divider" />
            <view class="stats__item">
                <text class="stats__num">{{ stats.completed ?? 0 }}</text>
                <text class="stats__label">已完成</text>
            </view>
            <view class="stats__divider" />
            <view class="stats__item">
                <text class="stats__num">{{ stats.total ?? 0 }}</text>
                <text class="stats__label">全部</text>
            </view>
            </view>
        </view>

        <!-- O9：统计失败就地轻提示 + 重试入口，不整页错误、不伪装成 0、不静默吞错 -->
        <view v-if="statsError" class="stats-tip page-pad" aria-live="polite">
            <text class="stats-tip__text">{{ statsError }}</text>
            <base-button :text="FB.ACTION_RETRY" size="sm" variant="text" :block="false" @click="load" />
        </view>

        <view class="page-pad entries">
            <base-card compact clickable aria-label="进入服务工单列表" @tap="go('/packages/pkg-service/pages/aftersale/ticket-list')">
                <view class="entry">
                    <view class="entry__icon entry__icon--brand">
                        <u-icon name="file-text" :size="40" color="var(--c-ink-2)" aria-hidden="true" />
                    </view>
                    <view class="entry__body">
                        <text class="entry__title">服务工单</text>
                        <text class="entry__desc">素材交付、流程协调、服务建议</text>
                    </view>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
            </base-card>

            <base-card compact clickable aria-label="进入投诉建议列表" @tap="go('/packages/pkg-service/pages/aftersale/complaint-list')">
                <view class="entry">
                    <view class="entry__icon entry__icon--danger">
                        <u-icon name="warning" :size="40" color="var(--c-danger)" />
                    </view>
                    <view class="entry__body">
                        <text class="entry__title">投诉建议</text>
                        <text class="entry__desc">服务态度、履约偏差、沟通问题</text>
                    </view>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
            </base-card>

            <base-card compact clickable aria-label="进入服务回访列表" @tap="go('/packages/pkg-service/pages/aftersale/callback-list')">
                <view class="entry">
                    <view class="entry__icon entry__icon--info">
                        <u-icon name="chat" :size="40" color="var(--c-info)" />
                    </view>
                    <view class="entry__body">
                        <text class="entry__title">服务回访</text>
                        <text class="entry__desc">完成回访问卷，帮助我们改进</text>
                    </view>
                    <u-icon name="arrow-right" :size="24" color="var(--c-ink-3)" />
                </view>
            </base-card>
        </view>
    </view>
    <app-toast />
</template>

<script setup lang="ts">
/** 售后首页：统计 + 三条链路入口（工单 / 投诉 / 回访） */
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { getAfterSaleStatistics } from '@/api/aftersale'
import { navigate } from '@/utils/link'
import { FB } from '@/constants/feedback'

const stats = ref<Record<string, number>>({})
/** O9：统计失败的就地提示，不伪装成 0、不静默吞错 */
const statsError = ref('')

async function load() {
    try {
        const res = await getAfterSaleStatistics()
        stats.value = (res as any) || {}
        statsError.value = ''
    } catch {
        // 保留上一次有效值（若有），仅给就地提示 + 重试入口
        statsError.value = FB.ERR_SERVER
    }
}

function go(path: string) {
    navigate({ path })
}

onShow(() => load())
</script>

<style lang="scss" scoped>
.as-page {
    background: var(--c-bg);
}

.stats {
    display: flex;
    flex-direction: column;
    background: var(--c-ink);
    padding: var(--sp-5) var(--sp-4) var(--sp-4);

    &__heading {
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        margin-bottom: var(--sp-4);
    }

    &__title {
        font-size: var(--fs-xl);
        font-weight: 500;
        color: var(--c-on-dark);
    }

    &__desc {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-on-dark-2);
        overflow-wrap: anywhere;
    }

    &__row {
        display: flex;
        align-items: center;
    }

    &__item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: var(--sp-1);
    }

    &__divider {
        width: 1rpx;
        height: 60rpx;
        background: var(--c-on-dark-2);
        opacity: 0.35;
    }

    &__num {
        font-size: var(--fs-2xl);
        font-weight: 500;
        line-height: 1.3;
        color: var(--c-brand-soft);
    }

    &__label {
        font-size: var(--fs-xs);
        color: var(--c-on-dark-2);
    }
}

.entries {
    display: flex;
    flex-direction: column;
    gap: var(--sp-3);
    padding-top: var(--sp-4);
    padding-bottom: calc(var(--sp-4) + var(--safe-bottom));
}

.entry {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    min-height: 112rpx;

    &__icon {
        width: 88rpx;
        height: 88rpx;
        border-radius: var(--r-md);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;

        &--brand {
            background: var(--c-brand-bg);
        }
        &--danger {
            background: var(--c-danger-bg);
        }
        &--info {
            background: var(--c-info-bg);
        }
    }

    &__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: var(--sp-1);
        min-width: 0;
    }

    &__title {
        font-size: var(--fs-md);
        font-weight: 500;
        color: var(--c-ink);
    }

    &__desc {
        font-size: var(--fs-xs);
        line-height: var(--lh-body);
        color: var(--c-ink-3);
        overflow-wrap: anywhere;
    }
}
.stats-tip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--sp-2);
    padding-top: var(--sp-2);
    padding-bottom: var(--sp-2);

    &__text {
        font-size: var(--fs-md);
        color: var(--c-ink-2);
        line-height: var(--lh-body);
    }
}
</style>
