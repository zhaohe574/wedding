<template>
    <view v-if="content.enabled !== 0 && showList.length" class="quick-entry-widget">
        <view v-if="showHeading" class="profile-quick-heading">
            <text class="profile-quick-title">{{ headingTitle }}</text>
            <text v-if="headingMeta" class="profile-quick-meta">{{ headingMeta }}</text>
        </view>

        <view v-if="isRoleEntry" class="profile-role-panel">
            <view class="profile-role-track">
                <BaseCard
                    v-for="(item, index) in showList"
                    :key="item.key || index"
                    class="profile-role-pill"
                    :variant="item.key === 'staff-center' ? 'dark' : 'soft'"
                    :class="[
                        item.key ? `profile-role-pill--${item.key}` : '',
                        { 'profile-role-pill--disabled': !!item.disabled }
                    ]"
                    padding="0"
                    border-radius="30rpx"
                    @click="handleClick(item)"
                >
                    <view class="profile-role-pill__inner">
                        <view class="profile-role-copy">
                            <text
                                class="profile-role-title"
                                :style="{
                                    color:
                                        item.key === 'staff-center'
                                            ? '#FFFDF8'
                                            : 'var(--wm-text-primary, #191713)'
                                }"
                            >
                                {{ item.title }}
                            </text>

                            <text
                                v-if="getItemDetail(item)"
                                class="profile-role-desc"
                                :style="{
                                    color:
                                        item.key === 'staff-center'
                                            ? 'rgba(255, 253, 248, 0.82)'
                                            : 'var(--wm-text-secondary, #665E52)'
                                }"
                            >
                                {{ getItemDetail(item) }}
                            </text>
                        </view>

                        <view class="profile-role-arrow">
                            <BaseIcon
                                name="right"
                                size="26"
                                :color="
                                    item.key === 'staff-center'
                                        ? '#D9BE82'
                                        : 'var(--wm-text-tertiary, #8A806F)'
                                "
                            />
                        </view>
                    </view>
                </BaseCard>
            </view>
        </view>

        <BaseCard v-else class="profile-entry-panel" variant="listDark">
            <BaseMenuRow
                v-if="primaryEntry"
                class="profile-entry-primary"
                :label="primaryEntry.title"
                :value="getItemDetail(primaryEntry)"
                :dark="true"
                :divided="secondaryEntries.length > 0"
                density="comfortable"
                :class="{ 'profile-entry-primary--disabled': !!primaryEntry.disabled }"
                @click="handleClick(primaryEntry)"
            />

            <view v-if="secondaryEntries.length" class="profile-entry-list">
                <BaseMenuRow
                    v-for="(item, index) in secondaryEntries"
                    :key="item.key || index"
                    class="profile-entry-row"
                    :label="item.title"
                    :value="getItemDetail(item)"
                    :dark="true"
                    :divided="index < secondaryEntries.length - 1"
                    density="comfortable"
                    :class="{ 'profile-entry-row--disabled': !!item.disabled }"
                    @click="handleClick(item)"
                />
            </view>
        </BaseCard>
    </view>
</template>

<script setup lang="ts">
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseMenuRow from '@/components/base/BaseMenuRow.vue'
import { hasConfiguredLink, navigateTo } from '@/utils/util'
import { computed } from 'vue'

interface QuickEntryItem {
    key?: string
    title: string
    subtitle?: string
    link?: any
    is_show?: string
    disabled?: boolean
    requiresLogin?: boolean
}

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    },
    isLogin: {
        type: Boolean,
        default: false
    }
})

const conciseSubtitleMap: Record<string, string> = {
    favorite: '已收藏',
    aftersale: '售后进度',
    waitlist: '候补进度',
    settings: '账号设置',
    notification: '消息更新',
    profile: '资料维护'
}

const showList = computed<QuickEntryItem[]>(() => {
    const list = Array.isArray(props.content?.data) ? props.content.data : []
    return list.filter(
        (item: QuickEntryItem) =>
            String(item.is_show ?? '1') !== '0' && hasConfiguredLink(item.link)
    )
})

const headingTitle = computed(() => {
    const title = String(props.content?.title || '').trim()
    if (!title || title === '快捷功能') return '账户入口'
    return title
})

const headingMeta = computed(() => {
    const subtitle = String(props.content?.subtitle || '').trim()
    if (subtitle && subtitle !== '常用入口') return subtitle
    return `${showList.value.length} 项`
})

const showHeading = computed(() => Boolean(headingTitle.value || headingMeta.value))
const isRoleEntry = computed(() => headingTitle.value === '角色入口')
const primaryEntry = computed(() => (isRoleEntry.value ? null : showList.value[0] || null))
const secondaryEntries = computed(() => (isRoleEntry.value ? [] : showList.value.slice(1)))

const getItemDetail = (item: QuickEntryItem) => {
    const rawSubtitle = String(item.subtitle || '').trim()
    if (!rawSubtitle || rawSubtitle === '点击进入') return ''

    if (/\d/.test(rawSubtitle) || /婚期|待处理|进行中|同步/.test(rawSubtitle)) {
        return rawSubtitle
    }

    return conciseSubtitleMap[item.key || ''] || rawSubtitle
}

const handleClick = (item: QuickEntryItem) => {
    if (item.disabled) {
        uni.showToast({ title: item.subtitle || '当前不可用', icon: 'none' })
        return
    }

    if (item.requiresLogin && !props.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }

    navigateTo(item.link)
}
</script>

<style scoped lang="scss">
.quick-entry-widget {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 16rpx;
}

.profile-quick-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    padding: 0 4rpx;
}

.profile-quick-title {
    min-width: 0;
    font-size: 24rpx;
    line-height: 1.4;
    font-weight: 700;
    color: var(--wm-text-primary, #191713);
}

.profile-quick-meta {
    flex-shrink: 0;
    font-size: 22rpx;
    line-height: 1.3;
    font-weight: 600;
    color: var(--wm-color-gold, #B8954A);
}

.profile-role-panel {
    width: 100%;
}

.profile-role-track {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14rpx;
}

.profile-role-pill {
    min-height: 104rpx;

    display: block;
}

.profile-role-pill__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 104rpx;
    padding: var(--wm-user-quick-item-padding, 24rpx);
    box-sizing: border-box;
}

.profile-role-pill--admin-dashboard {
    --wm-color-bg-soft: #FAF6EE;
}

.profile-role-pill--disabled {
    opacity: 0.54;
}

.profile-role-copy {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
    padding-right: 14rpx;
    box-sizing: border-box;
}

.profile-role-arrow {
    width: 34rpx;
    height: 34rpx;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-role-title {
    display: block;
    max-width: 100%;
    font-size: 27rpx;
    line-height: 1.35;
    font-weight: 700;
    color: var(--wm-text-primary, #191713);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-role-desc {
    font-size: 21rpx;
    line-height: 1.35;
    font-weight: 600;
    color: var(--wm-text-secondary, #665E52);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-role-pill--staff-center {
    .profile-role-title {
        color: var(--wm-text-inverse, #FFFDF8);
        font-weight: 900;
    }

    .profile-role-desc {
        color: rgba(255, 253, 248, 0.82);
    }
}

.profile-role-pill--staff-center.profile-role-pill--disabled {
    opacity: 1;

    .profile-role-title {
        color: var(--wm-text-inverse, #FFFDF8);
    }

    .profile-role-desc {
        color: rgba(255, 253, 248, 0.78);
    }
}

.profile-entry-panel {
    display: block;
    --wm-space-list-panel-y: 14rpx;
    --wm-space-list-panel-x: 30rpx;
}

.profile-entry-list {
    display: flex;
    flex-direction: column;
}

.profile-entry-primary--disabled,
.profile-entry-row--disabled {
    opacity: 0.54;
}
</style>
