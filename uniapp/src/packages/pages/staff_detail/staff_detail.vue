<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer" tone="editorial" hasSafeBottom>
        <BaseNavbar
            title="人员详情"
            :back="!isShareEntry"
            variant="solid"
            bg-color="#000000"
            text-color="#FFFDF8"
        />

        <view class="staff-detail" v-if="staffInfo">
            <view class="staff-detail__content wm-page-content">
                <BaseCard variant="hero" scene="consumer" class="hero-card" padding="0">
                    <staff-banner
                        class="hero-card__banner"
                        :banner-list="bannerList"
                        :config="bannerConfig"
                        :default-image="
                            staffInfo.avatar || '/static/images/user/default_avatar.png'
                        "
                    />
                </BaseCard>

                <BaseCard variant="dark" scene="consumer" class="info-card" padding="0">
                    <view class="info-card__inner">
                        <view class="info-card__header">
                            <view class="info-card__identity">
                                <text class="info-card__name">{{ staffInfo.name }}</text>

                                <text class="info-card__summary">{{ primaryMetaText }}</text>
                            </view>

                            <view class="info-card__favorite" @click.stop="handleToggleFavorite">
                                <BaseIconButton
                                    :icon="staffInfo.is_favorite ? 'like-fill' : 'like'"
                                    :variant="staffInfo.is_favorite ? 'dark' : 'light'"
                                    size="sm"
                                    width="76rpx"
                                    height="76rpx"
                                    icon-size="30"
                                />
                            </view>
                        </view>

                        <view v-if="statusBadgeList.length" class="info-card__badge-list">
                            <StatusBadge
                                v-for="badge in statusBadgeList"
                                :key="badge"
                                tone="warning"
                                size="xs"
                            >
                                {{ badge }}
                            </StatusBadge>
                        </view>

                        <view class="info-card__metric-row">
                            <view
                                v-for="metric in compactMetricList"
                                :key="metric.label"
                                class="info-card__metric"
                            >
                                <text class="info-card__metric-value">{{ metric.value }}</text>

                                <text class="info-card__metric-label">{{ metric.label }}</text>
                            </view>
                        </view>

                        <view class="info-card__price-row">
                            <text class="info-card__price-label">服务价格</text>

                            <view class="info-card__price-group">
                                <template v-if="staffPrice.hasPrice">
                                    <text class="info-card__price-symbol">¥</text>

                                    <text class="info-card__price-value">{{
                                        staffPrice.value
                                    }}</text>

                                    <text class="info-card__price-unit">/次起</text>
                                </template>

                                <text v-else class="info-card__price-negotiable">面议</text>
                            </view>
                        </view>
                    </view>
                </BaseCard>

                <BaseCard variant="list" scene="consumer" class="booking-brief-card" padding="0">
                    <view class="booking-brief-card__inner">
                        <view class="booking-brief-card__grid">
                            <BasePickerField
                                class="booking-brief-card__field booking-brief-card__field--region"
                                label="服务地区"
                                :model-value="hasSelectedRegion ? selectedDistrictText : ''"
                                placeholder="请选择区县"
                                icon="location"
                                @click="handleInlineRegionEdit"
                            />

                            <BasePickerField
                                class="booking-brief-card__field booking-brief-card__field--date"
                                label="预约日期"
                                :model-value="presetDate"
                                placeholder="请选择预约日期"
                                icon="calendar"
                                @click="handleInlineDateEdit"
                            />
                        </view>
                    </view>
                </BaseCard>

                <view class="tabs-section">
                    <view class="tabs-wrapper">
                        <view
                            v-for="tab in tabs"
                            :key="tab.key"
                            class="tab-item"
                            :class="{ 'tab-item--active': currentTab === tab.key }"
                            @click="currentTab = tab.key"
                        >
                            <text
                                class="tab-text"
                                :class="{ 'tab-text--active': currentTab === tab.key }"
                            >
                                {{ tab.label }}
                            </text>
                        </view>
                    </view>
                </view>

                <view class="tab-content">
                    <view
                        v-if="currentTab === 'intro'"
                        class="content-section content-section--stack"
                    >
                        <view v-if="hasLongDetail" class="detail-stream-shell">
                            <staff-long-detail-renderer :content="staffInfo.long_detail" />
                        </view>

                        <view v-if="displayTagList.length" class="soft-card wm-soft-card">
                            <text class="soft-card__title">擅长风格</text>

                            <view class="soft-tags">
                                <view v-for="tag in displayTagList" :key="tag" class="soft-tag">
                                    <text class="soft-tag__text">{{ tag }}</text>
                                </view>
                            </view>
                        </view>

                        <view v-if="displayCertificates.length" class="soft-card wm-soft-card">
                            <text class="soft-card__title">资质证书</text>

                            <scroll-view scroll-x class="certs-scroll">
                                <view class="certs-wrapper">
                                    <view
                                        v-for="cert in displayCertificates"
                                        :key="cert.id || cert.image"
                                        class="cert-item"
                                        @click="openCertificatePopup(cert)"
                                    >
                                        <image
                                            :src="
                                                resolveDetailImageSrc(
                                                    'certificate',

                                                    cert.image,

                                                    cert.id || cert.image
                                                )
                                            "
                                            mode="aspectFill"
                                            class="cert-image"
                                            @error="
                                                handleDetailImageError(
                                                    'certificate',

                                                    cert.image,

                                                    cert.id || cert.image,

                                                    $event
                                                )
                                            "
                                        />

                                        <text class="cert-name">{{ cert.name }}</text>
                                    </view>
                                </view>
                            </scroll-view>
                        </view>
                    </view>

                    <view v-else-if="currentTab === 'works'" class="content-section">
                        <view v-if="worksLoading" class="loading-state">
                            <tn-loading mode="circle" />
                        </view>

                        <view v-else-if="worksList.length" class="works-grid">
                            <view
                                v-for="work in worksList"
                                :key="work.id"
                                class="work-item"
                                @click="goWorkDetail(work)"
                            >
                                <image
                                    :src="
                                        resolveDetailImageSrc(
                                            'work',

                                            work.cover || work.images?.[0],

                                            work.id
                                        )
                                    "
                                    mode="aspectFill"
                                    class="work-image"
                                    lazy-load
                                    @error="
                                        handleDetailImageError(
                                            'work',

                                            work.cover || work.images?.[0],

                                            work.id,

                                            $event
                                        )
                                    "
                                />

                                <view class="work-overlay">
                                    <text class="work-title">{{ work.title || '婚礼作品' }}</text>
                                </view>
                            </view>
                        </view>

                        <view v-else class="empty-card">
                            <text class="empty-card__text">暂无作品</text>
                        </view>
                    </view>

                    <view v-else class="content-section content-section--stack">
                        <view class="review-summary">
                            <view class="review-summary-card">
                                <text class="review-summary-value">
                                    {{ reviewStats.avg_score || '0.0' }}
                                </text>

                                <text class="review-summary-label">综合评分</text>
                            </view>

                            <view class="review-summary-card">
                                <text class="review-summary-value">
                                    {{ reviewStats.total_count || 0 }}
                                </text>

                                <text class="review-summary-label">全部评价</text>
                            </view>

                            <view class="review-summary-card">
                                <text class="review-summary-value">
                                    {{ reviewStats.good_rate || 0 }}%
                                </text>

                                <text class="review-summary-label">好评率</text>
                            </view>
                        </view>

                        <view class="review-filter-row">
                            <view class="review-filter-item">
                                好评 {{ reviewStats.good_count || 0 }}
                            </view>

                            <view class="review-filter-item">
                                中评 {{ reviewStats.medium_count || 0 }}
                            </view>

                            <view class="review-filter-item">
                                差评 {{ reviewStats.bad_count || 0 }}
                            </view>

                            <view class="review-filter-item">
                                有图 {{ reviewStats.image_count || 0 }}
                            </view>
                        </view>

                        <view v-if="reviewsLoading && !reviewsList.length" class="loading-state">
                            <tn-loading mode="circle" />
                        </view>

                        <view v-else-if="reviewsList.length" class="reviews-list">
                            <view
                                v-for="review in reviewsList"
                                :key="review.id"
                                class="review-card"
                                @click="goReviewDetail(review)"
                            >
                                <view class="review-card-header">
                                    <view class="review-user">
                                        <image
                                            class="review-user-avatar"
                                            :src="
                                                review.user?.avatar ||
                                                '/static/images/user/default_avatar.png'
                                            "
                                            mode="aspectFill"
                                        />

                                        <view class="review-user-info">
                                            <text class="review-user-name">
                                                {{ review.user?.nickname || '匿名用户' }}
                                            </text>

                                            <text class="review-time">
                                                {{
                                                    review.create_time_text ||
                                                    formatReviewTime(review.create_time)
                                                }}
                                            </text>
                                        </view>
                                    </view>

                                    <view class="review-score">
                                        <BaseIcon
                                            v-for="star in 5"
                                            :key="`${review.id}-${star}`"
                                            :name="
                                                star <= Number(review.score || 0)
                                                    ? 'star-fill'
                                                    : 'star'
                                            "
                                            size="20"
                                            :color="
                                                star <= Number(review.score || 0)
                                                    ? '#C8A45D'
                                                    : '#D8D3C7'
                                            "
                                        />
                                    </view>
                                </view>

                                <text v-if="review.content" class="review-content">
                                    {{ review.content }}
                                </text>

                                <view v-if="review.tags?.length" class="review-tag-list">
                                    <view
                                        v-for="tag in review.tags"
                                        :key="tag.id || tag.name"
                                        class="review-tag"
                                    >
                                        {{ tag.name }}
                                    </view>
                                </view>

                                <view v-if="review.images?.length" class="review-image-list">
                                    <image
                                        v-for="(image, index) in review.images"
                                        :key="`${review.id}-${index}`"
                                        class="review-image"
                                        :src="image"
                                        mode="aspectFill"
                                        @click.stop="previewReviewImages(review.images, index)"
                                    />
                                </view>

                                <view v-if="review.replies?.length" class="review-reply-list">
                                    <view
                                        v-for="reply in review.replies"
                                        :key="reply.id"
                                        class="review-reply-item"
                                    >
                                        <text class="review-reply-type">
                                            {{
                                                Number(reply.reply_type) === 1
                                                    ? '用户追评'
                                                    : '商家回复'
                                            }}
                                        </text>

                                        <text class="review-reply-content">
                                            {{ reply.content }}
                                        </text>
                                    </view>
                                </view>
                            </view>

                            <view v-if="reviewsHasMore" class="review-load-more">
                                <text v-if="reviewsLoading" class="review-load-more-text">
                                    加载中...
                                </text>

                                <text
                                    v-else
                                    class="review-load-more-text review-load-more-text--action"
                                    @click="loadMoreReviews"
                                >
                                    加载更多评价
                                </text>
                            </view>

                            <view v-else class="review-load-more">
                                <text class="review-load-more-text">没有更多评价了</text>
                            </view>
                        </view>

                        <view v-else class="empty-card">
                            <text class="empty-card__text">暂无评价</text>
                        </view>
                    </view>
                </view>

                <!-- 底部操作栏 -->

                <ActionArea sticky safeBottom>
                    <view class="staff-detail__action-bar">
                        <view class="action-button share-action-item" @click="handleShareFallback">
                            <text class="action-button__text">分享</text>

                            <!-- #ifdef MP-WEIXIN -->

                            <button
                                class="share-action-trigger"
                                open-type="share"
                                hover-class="none"
                            ></button>

                            <!-- #endif -->
                        </view>

                        <view class="action-button" @click="handleContact">
                            <text class="action-button__text">咨询</text>
                        </view>

                        <view class="action-button action-button--primary" @click="handleBook">
                            <text class="action-button__text action-button__text--primary">
                                立即预约
                            </text>
                        </view>
                    </view>
                </ActionArea>
            </view>

            <BaseServiceRegionPicker
                v-model="selectedRegion"
                v-model:open="showRegionPopup"
                :data="regionTree"
                @confirm="handleServiceRegionConfirm"
                @cancel="closeRegionPicker"
            />

            <BaseDateTimePicker
                v-model="datePickerModel"
                v-model:open="showDatePopup"
                mode="date"
                format="YYYY-MM-DD"
                :min-time="datePickerMinText"
                :max-time="datePickerMaxText"
                @confirm="handleDatePickerConfirm"
                @cancel="closeDatePicker"
                @close="closeDatePicker"
            />

            <BaseOverlayMask
                :show="showAlternativeStaffPopup"
                :closeable="!alternativeStaffQuerying"
                @close="closeAlternativeStaffPopup"
            />

            <tn-popup
                v-model="showAlternativeStaffPopup"
                open-direction="bottom"
                :overlay="false"
                :overlay-closeable="!alternativeStaffQuerying"
                safe-area-inset-bottom
                :radius="28"
            >
                <view class="alternative-popup">
                    <view class="alternative-popup__header">
                        <view class="alternative-popup__badge">
                            <text class="alternative-popup__badge-text">档期提醒</text>
                        </view>

                        <text class="alternative-popup__title">该日期暂不可预约</text>

                        <text class="alternative-popup__desc">
                            {{ alternativeStaffReason || '当前档期暂不可预约' }}
                        </text>
                    </view>

                    <view v-if="alternativeStaffLoading" class="alternative-popup__loading">
                        <tn-loading mode="circle" />
                    </view>

                    <scroll-view
                        v-else-if="alternativeStaffList.length"
                        scroll-y
                        class="alternative-popup__scroll"
                    >
                        <view class="alternative-popup__list">
                            <view
                                v-for="item in alternativeStaffList"
                                :key="item.id"
                                class="alternative-card"
                                @click="handleAlternativeStaffSelect(item)"
                            >
                                <image
                                    class="alternative-card__avatar"
                                    :src="item.avatar || '/static/images/user/default_avatar.png'"
                                    mode="aspectFill"
                                    lazy-load
                                />

                                <view class="alternative-card__content">
                                    <view class="alternative-card__head">
                                        <view class="alternative-card__name-group">
                                            <text class="alternative-card__name">
                                                {{ item.name || '未命名人员' }}
                                            </text>

                                            <text
                                                v-if="item.is_recommend"
                                                class="alternative-card__badge"
                                            >
                                                推荐
                                            </text>
                                        </view>

                                        <text class="alternative-card__price">
                                            {{ formatAlternativePrice(item) }}
                                        </text>
                                    </view>

                                    <text class="alternative-card__role">
                                        {{ formatAlternativeRoleLine(item) }}
                                    </text>

                                    <view
                                        v-if="getAlternativeStaffTags(item).length"
                                        class="alternative-card__tags"
                                    >
                                        <text
                                            v-for="tag in getAlternativeStaffTags(item)"
                                            :key="`${item.id}-${tag}`"
                                            class="alternative-card__tag"
                                        >
                                            {{ tag }}
                                        </text>
                                    </view>

                                    <text v-else-if="item.profile" class="alternative-card__desc">
                                        {{ item.profile }}
                                    </text>

                                    <view class="alternative-card__footer">
                                        <view class="alternative-card__score">
                                            <BaseIcon name="star-fill" size="20" color="#C8A45D" />

                                            <text class="alternative-card__score-text">
                                                {{ formatAlternativeRating(item) }}
                                            </text>
                                        </view>

                                        <text class="alternative-card__orders">
                                            已服务{{ item.order_count || 0 }}单
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>

                    <view v-else class="alternative-popup__empty">
                        <text class="alternative-popup__empty-title">暂无可替代人员</text>

                        <text class="alternative-popup__empty-desc">
                            当前日期下暂无同类可预约人员。
                        </text>
                    </view>

                    <view class="alternative-popup__actions">
                        <view
                            class="alternative-popup__btn alternative-popup__btn--ghost"
                            @click="handleAlternativePickDate"
                        >
                            <text class="alternative-popup__btn-text">重新选日期</text>
                        </view>

                        <view
                            v-if="alternativeStaffList.length"
                            class="alternative-popup__btn alternative-popup__btn--primary"
                            :style="{ background: $theme.primaryColor }"
                            @click="closeAlternativeStaffPopup"
                        >
                            <text
                                class="alternative-popup__btn-text alternative-popup__btn-text--primary"
                            >
                                关闭
                            </text>
                        </view>

                        <view
                            v-else
                            class="alternative-popup__btn alternative-popup__btn--primary"
                            :style="{ background: $theme.primaryColor }"
                            @click="handleAlternativeJoinWaitlist"
                        >
                            <text
                                class="alternative-popup__btn-text alternative-popup__btn-text--primary"
                            >
                                {{ alternativeStaffQuerying ? '处理中...' : '加入候补' }}
                            </text>
                        </view>
                    </view>
                </view>
            </tn-popup>

            <BaseOverlayMask :show="showCertificatePopup" @close="closeCertificatePopup" />

            <tn-popup
                v-model="showCertificatePopup"
                open-direction="bottom"
                :overlay="false"
                :overlay-closeable="true"
                safe-area-inset-bottom
                :radius="28"
            >
                <view v-if="activeCertificate" class="certificate-popup">
                    <view class="certificate-popup__header">
                        <view class="certificate-popup__badge">
                            <text class="certificate-popup__badge-text">资质详情</text>
                        </view>

                        <text class="certificate-popup__title">
                            {{ activeCertificate.name || '未命名证书' }}
                        </text>

                        <text class="certificate-popup__desc">
                            {{ getCertificateStatusText(activeCertificate) }}
                        </text>
                    </view>

                    <image
                        v-if="activeCertificate.image"
                        :src="
                            resolveDetailImageSrc(
                                'certificate-popup',

                                activeCertificate.image,

                                activeCertificate.id || activeCertificate.image
                            )
                        "
                        mode="aspectFill"
                        class="certificate-popup__image"
                        @click="previewCertificateImage(activeCertificate.image)"
                        lazy-load
                        @error="
                            handleDetailImageError(
                                'certificate-popup',

                                activeCertificate.image,

                                activeCertificate.id || activeCertificate.image,

                                $event
                            )
                        "
                    />

                    <view class="certificate-popup__meta-list">
                        <view class="certificate-popup__meta-item">
                            <text class="certificate-popup__meta-label">证书类型</text>

                            <text class="certificate-popup__meta-value">
                                {{ formatCertificateField(activeCertificate.type) }}
                            </text>
                        </view>

                        <view class="certificate-popup__meta-item">
                            <text class="certificate-popup__meta-label">证书编号</text>

                            <text class="certificate-popup__meta-value">
                                {{ getCertificateSerialNumber(activeCertificate) }}
                            </text>
                        </view>

                        <view class="certificate-popup__meta-item">
                            <text class="certificate-popup__meta-label">发证机构</text>

                            <text class="certificate-popup__meta-value">
                                {{ formatCertificateField(activeCertificate.issue_org) }}
                            </text>
                        </view>

                        <view class="certificate-popup__meta-item">
                            <text class="certificate-popup__meta-label">发证日期</text>

                            <text class="certificate-popup__meta-value">
                                {{ formatCertificateField(activeCertificate.issue_date) }}
                            </text>
                        </view>

                        <view class="certificate-popup__meta-item">
                            <text class="certificate-popup__meta-label">有效期至</text>

                            <text class="certificate-popup__meta-value">
                                {{ getCertificateValidityText(activeCertificate) }}
                            </text>
                        </view>

                        <view class="certificate-popup__meta-item">
                            <text class="certificate-popup__meta-label">当前状态</text>

                            <text
                                class="certificate-popup__meta-value certificate-popup__meta-value--status"
                            >
                                {{ getCertificateStatusText(activeCertificate) }}
                            </text>
                        </view>
                    </view>

                    <view class="certificate-popup__actions">
                        <view
                            class="certificate-popup__btn"
                            :style="{ background: $theme.primaryColor }"
                            @click="closeCertificatePopup"
                        >
                            <text class="certificate-popup__btn-text">我知道了</text>
                        </view>
                    </view>
                </view>
            </tn-popup>
        </view>

        <view v-else-if="detailLoading" class="loading-container">
            <LoadingState text="人员详情加载中..." />
        </view>

        <view v-else class="detail-state-shell wm-page-content">
            <EmptyState
                :title="detailError?.title || '人员信息暂不可用'"
                :description="detailError?.message || '该人员可能已下架，或当前网络不可用。'"
                :action-text="detailError?.actionText || '重新加载'"
                @action="handleDetailRecoveryAction"
            />

            <view class="detail-state-shell__actions">
                <view class="detail-state-shell__link" @click="goHome">
                    <text>返回首页</text>
                </view>
            </view>
        </view>
    </PageShell>
</template>

<script lang="ts" setup>
import { computed, nextTick, ref, watch } from 'vue'

import {
    onLoad,
    onPageScroll,
    onShow,
    onShareAppMessage,
    onShareTimeline
} from '@dcloudio/uni-app'

import PageShell from '@/components/base/PageShell.vue'

import BaseNavbar from '@/components/base/BaseNavbar.vue'

import ActionArea from '@/components/base/ActionArea.vue'

import BaseCard from '@/components/base/BaseCard.vue'

import BaseDateTimePicker from '@/components/base/BaseDateTimePicker.vue'

import BaseIconButton from '@/components/base/BaseIconButton.vue'

import BasePickerField from '@/components/base/BasePickerField.vue'

import BaseServiceRegionPicker from '@/components/base/BaseServiceRegionPicker.vue'

import EmptyState from '@/components/base/EmptyState.vue'

import LoadingState from '@/components/base/LoadingState.vue'

import StatusBadge from '@/components/base/StatusBadge.vue'

import { getStaffDetail, getStaffList, toggleStaffFavorite, getStaffWorks } from '@/api/staff'

import { getStaffReviews, getStaffReviewStats } from '@/packages/common/api/review'

import { checkScheduleAvailable, joinWaitlist } from '@/packages/common/api/schedule'

import { getServiceRegionTree } from '@/api/service'

import { ClientEnum } from '@/enums/appEnums'

import StaffLongDetailRenderer from '@/packages/components/staff-long-detail/staff-long-detail-renderer.vue'

import { hasLongDetailContent } from '@/packages/components/staff-long-detail/utils'

import { BACK_URL } from '@/enums/constantEnums'

import { useThemeStore } from '@/stores/theme'

import { useUserStore } from '@/stores/user'

import StaffBanner from '@/packages/components/staff-banner/staff-banner.vue'

import cache from '@/utils/cache'

import { client } from '@/utils/client'

import { isDevMode } from '@/utils/env'

import { goHome, goLoginWithBack, normalizePageRecoveryError } from '@/utils/page-recovery'

import {
    buildServiceRegionQuery,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection,
    toServiceRegionParams
} from '@/utils/service-region'

import {
    BOOKING_RETURN_MODE_DETAIL_BACK,
    consumeStaffDetailRestoreSnapshot,
    consumeStaffDetailReturnState,
    getStaffBookingPageUrl,
    saveStaffDetailRestoreSnapshot,
    type StaffDetailRestoreSnapshot
} from '@/packages/common/utils/staff-booking'

import { subscribeWaitlistScenes } from '../../../utils/subscribe'

type AlternativeStaffItem = {
    id: number

    name?: string

    avatar?: string

    category_name?: string

    experience_years?: number

    rating?: number | string

    order_count?: number

    is_recommend?: number

    price?: number | string | null

    price_text?: number | string

    has_price?: boolean

    profile?: string

    tags?: string[]

    [key: string]: any
}

type StaffCertificateItem = {
    id?: number | string

    name?: string

    type?: string

    sn?: string

    certificate_no?: string

    issue_org?: string

    issue_date?: string

    expire_date?: string

    image?: string

    verify_status_desc?: string

    audit_status_desc?: string

    is_expired?: number | boolean

    [key: string]: any
}

type StaffDetailPageOptions = Record<string, any>

type WechatEntryOptions = {
    path?: string
    scene?: number | string
    query?: StaffDetailPageOptions
}

const STAFF_DETAIL_ROUTE = 'packages/pages/staff_detail/staff_detail'

const WECHAT_SHARE_ENTRY_SCENES = new Set([1007, 1008, 1044, 1154])

const normalizeEntryPath = (path?: string) => String(path || '').replace(/^\/+/, '')

const normalizeStaffId = (value: unknown) => {
    const staffId = Number(value || 0)

    return Number.isFinite(staffId) ? staffId : 0
}

const isShareEntryFlag = (value: unknown) => {
    if (value === true || value === 1) {
        return true
    }

    const normalized = String(value ?? '').trim().toLowerCase()

    return normalized === '1' || normalized === 'true'
}

const hasShareEntryFlag = (options?: StaffDetailPageOptions) => {
    return isShareEntryFlag(options?.from_share)
}

const isStaffDetailEntryPath = (path?: string) => normalizeEntryPath(path) === STAFF_DETAIL_ROUTE

const isDirectEntryPage = () => {
    try {
        return getCurrentPages().length <= 1
    } catch {
        return false
    }
}

const isWechatShareScene = (scene: unknown) => {
    const sceneCode = Number(scene)

    return Number.isFinite(sceneCode) && WECHAT_SHARE_ENTRY_SCENES.has(sceneCode)
}

const getWechatEntryOptions = () => {
    const optionList: WechatEntryOptions[] = []

    // #ifdef MP-WEIXIN
    const uniRuntime = uni as unknown as {
        getEnterOptionsSync?: () => WechatEntryOptions
        getLaunchOptionsSync?: () => WechatEntryOptions
    }

    try {
        const enterOptions = uniRuntime.getEnterOptionsSync?.()

        if (enterOptions) {
            optionList.push(enterOptions)
        }

        const launchOptions = uniRuntime.getLaunchOptionsSync?.()

        if (launchOptions) {
            optionList.push(launchOptions)
        }
    } catch (error) {
        console.warn('读取微信入口参数失败：', error)
    }
    // #endif

    return optionList
}

const getStaffDetailWechatEntryQuery = () => {
    const staffDetailEntry = getWechatEntryOptions().find((entryOptions) =>
        isStaffDetailEntryPath(entryOptions.path)
    )

    return staffDetailEntry?.query || {}
}

const resolveStaffDetailPageOptions = (options?: StaffDetailPageOptions) => ({
    ...getStaffDetailWechatEntryQuery(),
    ...(options || {})
})

const resolveStaffIdFromOptions = (options?: StaffDetailPageOptions) => {
    return normalizeStaffId(options?.id ?? options?.staff_id ?? options?.staffId)
}

const getCurrentPageRuntimeOptions = () => {
    try {
        const pages = getCurrentPages()

        const currentPage = pages[pages.length - 1] as { options?: StaffDetailPageOptions }

        return currentPage?.options || {}
    } catch {
        return {}
    }
}

const isWechatShareDirectEntry = () => {
    if (!isDirectEntryPage()) {
        return false
    }

    return getWechatEntryOptions().some((entryOptions) => {
        if (!isStaffDetailEntryPath(entryOptions.path)) {
            return false
        }

        return hasShareEntryFlag(entryOptions.query) || isWechatShareScene(entryOptions.scene)
    })
}

const resolveShareEntry = (options?: StaffDetailPageOptions) => {
    return hasShareEntryFlag(options) || isWechatShareDirectEntry()
}

const staffId = ref<number>(0)

const staffInfo = ref<any>(null)

const detailLoading = ref(true)

const detailError = ref<ReturnType<typeof normalizePageRecoveryError> | null>(null)

const isShareEntry = ref(resolveShareEntry())

const getCurrentStaffIdForQuery = () => {
    return (
        normalizeStaffId(staffInfo.value?.id) ||
        normalizeStaffId(staffId.value) ||
        resolveStaffIdFromOptions(getCurrentPageRuntimeOptions())
    )
}

const hideWechatHomeButtonForShareEntry = () => {
    if (!isShareEntry.value) {
        return
    }

    // #ifdef MP-WEIXIN
    const hideHomeButtonTask = uni.hideHomeButton() as unknown

    if (
        hideHomeButtonTask &&
        typeof (hideHomeButtonTask as Promise<unknown>).catch === 'function'
    ) {
        ;(hideHomeButtonTask as Promise<unknown>).catch((error: unknown) => {
            console.warn('隐藏首页按钮失败：', error)
        })
    }
    // #endif
}

const currentTab = ref('intro')

const presetDate = ref('') // 预设日期

const showDatePopup = ref(false)

const showRegionPopup = ref(false)

const datePickerModel = ref('')

const openDatePickerRequested = ref(false)

const openBookingPopupRequested = ref(false)

const pendingDatePickerAfterRegion = ref(false)

const selectedPackageId = ref<number>(0)

const waitlistId = ref<number>(0)

const showAlternativeStaffPopup = ref(false)

const showCertificatePopup = ref(false)

const alternativeStaffLoading = ref(false)

const alternativeStaffReason = ref('')

const alternativeStaffList = ref<AlternativeStaffItem[]>([])

const alternativeStaffQuerying = ref(false)

const activeCertificate = ref<StaffCertificateItem | null>(null)

const regionTree = ref<any[]>([])

const regionTreeLoading = ref(false)

let regionTreeLoadTask: Promise<void> | null = null

const detailScrollTop = ref(0)

let pendingRestoreScrollTop: number | null = null

const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))

const $theme = useThemeStore()

const userStore = useUserStore()

const DETAIL_TAB_KEYS = ['intro', 'works', 'reviews'] as const

// 轮播图数据

const bannerList = ref<any[]>([])

const bannerConfig = ref({
    banner_mode: 1,

    banner_small_height: 360,

    banner_large_height: 520,

    banner_indicator_style: 1,

    banner_autoplay: 1,

    banner_interval: 3000
})

// 作品列表

const worksList = ref<any[]>([])

const worksLoading = ref(false)

// 评价列表

const reviewsList = ref<any[]>([])

const reviewsLoading = ref(false)

const reviewsPage = ref(1)

const reviewsHasMore = ref(true)

const reviewsInitialized = ref(false)

const reviewStatsLoaded = ref(false)

const reviewStats = ref({
    total_count: 0,

    good_count: 0,

    medium_count: 0,

    bad_count: 0,

    image_count: 0,

    video_count: 0,

    avg_score: '0.0',

    good_rate: 100
})

const detailImageFallbackMap = ref<Record<string, string>>({})

const detailImageFallback = '/static/images/user/default_avatar.png'

const getTomorrowDate = () => {
    const tomorrow = new Date()

    tomorrow.setHours(0, 0, 0, 0)

    tomorrow.setDate(tomorrow.getDate() + 1)

    return tomorrow
}

const getMaxDateForPicker = () => {
    const maxDate = getTomorrowDate()

    maxDate.setFullYear(maxDate.getFullYear() + 5)

    return maxDate
}

const formatDateText = (date: Date) => {
    const year = date.getFullYear()

    const month = String(date.getMonth() + 1).padStart(2, '0')

    const day = String(date.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
}

const parseDateText = (value = '') => {
    const [year, month, day] = value.split('-').map((item) => Number(item))

    if (!year || !month || !day) {
        return null
    }

    const date = new Date(year, month - 1, day)

    date.setHours(0, 0, 0, 0)

    if (Number.isNaN(date.getTime())) {
        return null
    }

    return date
}

const isSelectableDate = (value = '') => {
    const parsedDate = parseDateText(value)

    if (!parsedDate) {
        return false
    }

    const minDate = getTomorrowDate()

    const maxDate = getMaxDateForPicker()

    return parsedDate >= minDate && parsedDate <= maxDate
}

const normalizeSelectedDateText = (value = '') => {
    if (!isSelectableDate(value)) {
        return ''
    }

    return formatDateText(parseDateText(value) as Date)
}

const getDetailResourceKey = (section: string, identifier: unknown) =>
    `${section}:${String(identifier ?? '')}`

const resolveDetailImageSrc = (section: string, src: unknown, identifier?: unknown) => {
    const resourceKey = getDetailResourceKey(section, identifier ?? src)

    const text = String(src || '').trim()

    return detailImageFallbackMap.value[resourceKey] || text || detailImageFallback
}

const logDetailResourceError = (section: string, src: unknown, error: any) => {
    if (!isDevMode()) {
        return
    }

    console.warn('人员详情资源加载失败', {
        section,

        staffId: staffId.value,

        src: String(src || ''),

        error: error?.detail || error || null
    })
}

const handleDetailImageError = (section: string, src: unknown, identifier: unknown, error: any) => {
    logDetailResourceError(section, src, error)

    const source = String(src || '').trim()

    const resourceKey = getDetailResourceKey(section, identifier ?? source)

    if (!source || source === detailImageFallback || detailImageFallbackMap.value[resourceKey]) {
        return
    }

    detailImageFallbackMap.value[resourceKey] = detailImageFallback
}

const resetDetailImageFallbacks = () => {
    detailImageFallbackMap.value = {}
}

const getEffectiveSelectableDate = (value = '') => {
    const parsedDate = parseDateText(value)

    const minDate = getTomorrowDate()

    const maxDate = getMaxDateForPicker()

    if (!parsedDate || parsedDate < minDate) {
        return minDate
    }

    if (parsedDate > maxDate) {
        return maxDate
    }

    return parsedDate
}

const datePickerMinText = computed(() => formatDateText(getTomorrowDate()))

const datePickerMaxText = computed(() => formatDateText(getMaxDateForPicker()))

const hasSelectedRegion = computed(() => hasServiceRegion(selectedRegion.value))

const selectedRegionText = computed(() => {
    if (!hasSelectedRegion.value) {
        return '请选择服务区县'
    }

    const cityName = String(selectedRegion.value.city_name || '').trim()

    const districtName = String(selectedRegion.value.district_name || '').trim()

    return [cityName, districtName].filter(Boolean).join(' / ') || districtName || '请选择服务区县'
})

const selectedDistrictText = computed(() => {
    if (!hasSelectedRegion.value) {
        return ''
    }

    const districtName = String(selectedRegion.value.district_name || '').trim()

    const cityName = String(selectedRegion.value.city_name || '').trim()

    const provinceName = String(selectedRegion.value.province_name || '').trim()

    return districtName || cityName || provinceName
})

const displayTagList = computed(() => {
    const tags = Array.isArray(staffInfo.value?.tags) ? staffInfo.value.tags : []

    return tags.map((item: any) => String(item || '').trim()).filter((item: string) => item)
})

const displayCertificates = computed(() => {
    const certificates = Array.isArray(staffInfo.value?.certificates)
        ? (staffInfo.value.certificates as StaffCertificateItem[])
        : []

    return certificates.filter((item: any) => String(item?.image || '').trim())
})

const hasLongDetail = computed(() => hasLongDetailContent(staffInfo.value?.long_detail))

const statusBadgeList = computed(() => {
    const badges: string[] = []

    if (staffInfo.value?.is_verified) {
        badges.push('已认证')
    }

    if (staffInfo.value?.is_vip) {
        badges.push('VIP')
    }

    if (staffInfo.value?.is_recommend) {
        badges.push('推荐')
    }

    return badges
})

const staffSummaryText = computed(() => {
    const categoryName = String(
        staffInfo.value?.category?.name || staffInfo.value?.category_name || ''
    ).trim()

    const parts: string[] = []

    if (categoryName) {
        parts.push(categoryName)
    }

    const summaryTags = displayTagList.value.slice(0, 2)

    if (summaryTags.length) {
        parts.push(summaryTags.join('｜'))
    }

    const orderCount = Number(staffInfo.value?.order_count || 0)

    if (orderCount > 0) {
        parts.push(`服务 ${orderCount} 场`)
    }

    return parts.join('｜') || '资料正在完善中'
})

const primaryMetaText = computed(() => {
    const parts: string[] = []

    const categoryName = String(
        staffInfo.value?.category?.name || staffInfo.value?.category_name || ''
    ).trim()

    if (categoryName) {
        parts.push(categoryName)
    }

    const experienceYears = Number(staffInfo.value?.experience_years || 0)

    if (experienceYears > 0) {
        parts.push(`${experienceYears}年经验`)
    }

    if (hasSelectedRegion.value) {
        parts.push(selectedRegionText.value)
    }

    return parts.join(' · ') || staffSummaryText.value
})

const compactMetricList = computed(() => [
    {
        label: '评分',
        value: staffInfo.value?.rating ?? '0.0'
    },
    {
        label: '服务',
        value: staffInfo.value?.order_count || 0
    },
    {
        label: '浏览',
        value: staffInfo.value?.view_count || 0
    }
])

const staffPrice = computed(() => {
    const hasPrice =
        staffInfo.value?.has_price !== false &&
        staffInfo.value?.price !== null &&
        staffInfo.value?.price !== undefined &&
        staffInfo.value?.price !== ''

    return {
        hasPrice,

        value: String(staffInfo.value?.price_text || staffInfo.value?.price || '')
    }
})

const currentCategoryId = computed(() =>
    Number(staffInfo.value?.category_id || staffInfo.value?.category?.id || 0)
)

const getPackageId = (pkg: any) => Number(pkg?.package_id || pkg?.id || 0)

const isRecommendedPackage = (pkg: any) =>
    Number(pkg?.is_recommend ?? pkg?.package?.is_recommend ?? 0) === 1

const getAlternativeStaffTags = (item: AlternativeStaffItem, limit = 2) => {
    const tags = Array.isArray(item?.tags) ? item.tags : []

    return tags

        .map((tag) => String(tag || '').trim())

        .filter(Boolean)

        .slice(0, limit)
}

const formatAlternativeRoleLine = (item: AlternativeStaffItem) => {
    const parts = [
        String(item?.category_name || staffInfo.value?.category?.name || '服务人员').trim()
    ]

    const experienceYears = Number(item?.experience_years || 0)

    if (experienceYears > 0) {
        parts.push(`${experienceYears}年经验`)
    }

    return parts.filter(Boolean).join(' · ')
}

const formatAlternativeRating = (item: AlternativeStaffItem) => {
    const rating = Number(item?.rating || 0)

    return Number.isFinite(rating) ? rating.toFixed(1) : '0.0'
}

const hasAlternativeStaffPrice = (item: AlternativeStaffItem) =>
    !(item?.has_price === false || item?.price === null || item?.price === undefined)

const formatAlternativePrice = (item: AlternativeStaffItem) => {
    if (!hasAlternativeStaffPrice(item)) {
        return '面议'
    }

    return `¥${item.price_text || item.price}/次`
}

const resetAlternativeStaffState = () => {
    showAlternativeStaffPopup.value = false

    alternativeStaffLoading.value = false

    alternativeStaffReason.value = ''

    alternativeStaffList.value = []
}

const cloneSerializable = <T>(value: T): T | null => {
    try {
        return JSON.parse(JSON.stringify(value ?? null)) as T | null
    } catch (error) {
        console.warn('详情快照序列化失败：', error)
        return null
    }
}

const applyStaffBannerData = (data: any) => {
    bannerList.value = Array.isArray(data?.banners) ? data.banners : []

    if (data?.banner_mode === undefined) {
        return
    }

    bannerConfig.value = {
        banner_mode: data.banner_mode || 1,

        banner_small_height: data.banner_small_height || 400,

        banner_large_height: data.banner_large_height || 600,

        banner_indicator_style:
            data.banner_indicator_style !== undefined ? data.banner_indicator_style : 1,

        banner_autoplay: data.banner_autoplay !== undefined ? data.banner_autoplay : 1,

        banner_interval: data.banner_interval || 3000
    }
}

const applyStaffDetailDisplayData = (data: any) => {
    resetDetailImageFallbacks()

    staffInfo.value = data

    applyStaffBannerData(data)
}

const scheduleRestoreDetailScroll = () => {
    if (pendingRestoreScrollTop === null) {
        return
    }

    const scrollTop = pendingRestoreScrollTop

    nextTick(() => {
        setTimeout(() => {
            uni.pageScrollTo({
                scrollTop,
                duration: 0
            })
        }, 0)
    })
}

const applyDetailRestoreSnapshot = () => {
    const snapshot = consumeStaffDetailRestoreSnapshot()

    if (!snapshot || snapshot.staff_id !== staffId.value || !snapshot.staff_info) {
        return null
    }

    const normalizedRegion = normalizeServiceRegion(snapshot.selected_region)

    applyStaffDetailDisplayData(snapshot.staff_info)

    selectedPackageId.value = Number(snapshot.package_id || 0)

    syncSelectedPackage()

    selectedRegion.value = normalizedRegion

    if (hasServiceRegion(normalizedRegion)) {
        saveServiceRegionSelection(normalizedRegion)
    }

    if (snapshot.preset_date) {
        presetDate.value = normalizeSelectedDateText(snapshot.preset_date)
    }

    if (DETAIL_TAB_KEYS.includes(snapshot.current_tab as (typeof DETAIL_TAB_KEYS)[number])) {
        currentTab.value = snapshot.current_tab
    }

    pendingRestoreScrollTop = snapshot.scroll_top > 0 ? snapshot.scroll_top : 0

    return snapshot
}

const saveCurrentDetailRestoreSnapshot = () => {
    const snapshot: StaffDetailRestoreSnapshot = {
        staff_id: staffId.value,
        package_id: selectedPackageId.value,
        staff_info: cloneSerializable(staffInfo.value),
        selected_region: cloneSerializable(selectedRegion.value) || normalizeServiceRegion(null),
        preset_date: presetDate.value,
        current_tab: currentTab.value,
        scroll_top: detailScrollTop.value,
        saved_at: Date.now()
    }

    saveStaffDetailRestoreSnapshot(snapshot)
}

const syncSelectedPackage = () => {
    const packages = Array.isArray(staffInfo.value?.packages) ? staffInfo.value.packages : []

    if (!packages.length) {
        selectedPackageId.value = 0

        return
    }

    const hasSelected = packages.some((pkg: any) => getPackageId(pkg) === selectedPackageId.value)

    if (hasSelected) {
        return
    }

    const recommendedPackage = packages.find((pkg: any) => isRecommendedPackage(pkg))

    selectedPackageId.value = getPackageId(recommendedPackage || packages[0])
}

const applyPendingDetailReturnState = () => {
    const state = consumeStaffDetailReturnState()

    if (!state || state.staff_id !== staffId.value) {
        return
    }

    selectedPackageId.value = state.package_id

    if (staffInfo.value) {
        syncSelectedPackage()
    }
}

const handleInlineRegionEdit = () => {
    pendingDatePickerAfterRegion.value = false

    openRegionPicker()
}

const handleInlineDateEdit = () => {
    if (!hasSelectedRegion.value) {
        uni.showToast({ title: '请先选择服务地区', icon: 'none' })

        pendingDatePickerAfterRegion.value = true

        openRegionPicker()

        return
    }

    pendingDatePickerAfterRegion.value = false

    openDatePicker()
}

const handleDetailRecoveryAction = () => {
    if (detailError.value?.kind === 'auth') {
        goLoginWithBack(buildStaffDetailQuery())
        return
    }

    void getDetail()
}

const closeAlternativeStaffPopup = () => {
    if (alternativeStaffQuerying.value) {
        return
    }

    showAlternativeStaffPopup.value = false
}

const handleAlternativePickDate = () => {
    if (alternativeStaffQuerying.value) {
        return
    }

    showAlternativeStaffPopup.value = false

    setTimeout(() => handleInlineDateEdit(), 0)
}

// 标签页配置

const tabs = [
    { key: 'intro', label: '人员简介' },

    { key: 'works', label: '人员作品' },

    { key: 'reviews', label: '人员评价' }
]

// 监听标签页切换

watch(currentTab, (newTab) => {
    if (newTab === 'works' && worksList.value.length === 0) {
        loadWorks()
    } else if (newTab === 'reviews') {
        if (!reviewStatsLoaded.value) {
            loadReviewStats()
        }

        if (!reviewsInitialized.value) {
            loadReviews(true)
        }
    }
})

watch(showCertificatePopup, (visible) => {
    if (!visible) {
        activeCertificate.value = null
    }
})

// 获取详情

const getDetail = async () => {
    if (!staffId.value) {
        staffInfo.value = null
        detailLoading.value = false
        detailError.value = normalizePageRecoveryError('缺少人员信息，请从列表重新进入', '缺少人员信息，请从列表重新进入')
        return
    }

    detailLoading.value = true
    detailError.value = null

    try {
        const params: Record<string, any> & { id: number } = { id: staffId.value }

        if (presetDate.value) {
            params.date = presetDate.value
        }

        Object.assign(params, toServiceRegionParams(selectedRegion.value))

        const data = await getStaffDetail(params)

        if (!data?.id) {
            throw new Error('人员不存在或已下架，请返回列表重新选择')
        }

        applyStaffDetailDisplayData(data)

        syncSelectedPackage()

        if (currentTab.value === 'reviews') {
            if (!reviewStatsLoaded.value) {
                loadReviewStats()
            }

            if (!reviewsInitialized.value) {
                loadReviews(true)
            }
        }

        scheduleRestoreDetailScroll()

        pendingRestoreScrollTop = null
    } catch (e: any) {
        staffInfo.value = null
        detailError.value = normalizePageRecoveryError(e, '获取人员详情失败，请稍后重试')
    } finally {
        detailLoading.value = false
    }
}

const getRegionTree = async (force = false) => {
    if (!force && regionTree.value.length) {
        return Promise.resolve()
    }

    if (regionTreeLoading.value && regionTreeLoadTask) {
        return regionTreeLoadTask
    }

    regionTreeLoading.value = true

    regionTreeLoadTask = (async () => {
        try {
            const data = await getServiceRegionTree()

            regionTree.value = Array.isArray(data) ? data : []
        } catch (error: any) {
            const errorMsg =
                typeof error === 'string'
                    ? error
                    : error?.msg || error?.message || '加载服务地区失败'

            uni.showToast({ title: errorMsg, icon: 'none' })
        } finally {
            regionTreeLoading.value = false
            regionTreeLoadTask = null
        }
    })()

    return regionTreeLoadTask
}

// 加载作品列表

const loadWorks = async () => {
    if (worksLoading.value) return

    worksLoading.value = true

    try {
        const data = await getStaffWorks({ staff_id: staffId.value })

        worksList.value = data || []
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载作品失败'

        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        worksLoading.value = false
    }
}

const loadReviewStats = async () => {
    if (!staffId.value || reviewStatsLoaded.value) return

    try {
        const data = await getStaffReviewStats({ staff_id: staffId.value })

        reviewStats.value = {
            total_count: Number(data?.total_count || 0),

            good_count: Number(data?.good_count || 0),

            medium_count: Number(data?.medium_count || 0),

            bad_count: Number(data?.bad_count || 0),

            image_count: Number(data?.image_count || 0),

            video_count: Number(data?.video_count || 0),

            avg_score: Number(data?.avg_score ?? 0).toFixed(1),

            good_rate: Number(data?.good_rate || 0)
        }

        reviewStatsLoaded.value = true
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载评价统计失败'

        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

const loadReviews = async (refresh = false) => {
    if (reviewsLoading.value || (!refresh && !reviewsHasMore.value)) return

    if (refresh) {
        reviewsPage.value = 1

        reviewsHasMore.value = true
    }

    reviewsLoading.value = true

    try {
        const data = await getStaffReviews({
            staff_id: staffId.value,

            page: reviewsPage.value,

            limit: 10
        })

        const list = data?.lists || []

        reviewsList.value = refresh ? list : [...reviewsList.value, ...list]

        reviewsHasMore.value = Boolean(data?.has_more)

        reviewsInitialized.value = true

        reviewsPage.value += 1
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '加载评价失败'

        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        reviewsLoading.value = false
    }
}

// 收藏/取消收藏

const handleToggleFavorite = async () => {
    // 检查登录状态

    if (!userStore.isLogin) {
        uni.showToast({ title: '请先登录', icon: 'none' })

        setTimeout(() => {
            uni.navigateTo({ url: '/pages/login/login' })
        }, 1500)

        return
    }

    try {
        await toggleStaffFavorite({ id: staffId.value })

        staffInfo.value.is_favorite = !staffInfo.value.is_favorite

        uni.showToast({
            title: staffInfo.value.is_favorite ? '收藏成功' : '已取消收藏',

            icon: 'success'
        })
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '操作失败'

        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

// 联系咨询

const handleContact = () => {
    uni.navigateTo({
        url: `/packages/pages/customer_service/customer_service?scene=staff_detail&staff_id=${staffId.value}`
    })
}

const handleShareFallback = () => {
    const payload = buildSharePayload()

    let shareContent = `${payload.title} ${payload.path}`

    let toastTitle = '已复制分享信息'

    // #ifdef H5

    if (typeof window !== 'undefined' && window.location?.href) {
        shareContent = window.location.href

        toastTitle = '已复制分享链接'
    }

    // #endif

    uni.setClipboardData({
        data: shareContent,

        success: () => {
            uni.showToast({ title: toastTitle, icon: 'none' })
        }
    })
}

const openRegionPicker = () => {
    if (showRegionPopup.value) {
        return
    }

    showRegionPopup.value = true
}

const hideRegionPicker = () => {
    showRegionPopup.value = false
}

const closeRegionPicker = () => {
    hideRegionPicker()

    pendingDatePickerAfterRegion.value = false
}

const handleServiceRegionConfirm = async (value: Record<string, any>) => {
    const nextRegion = normalizeServiceRegion(value)

    if (!hasServiceRegion(nextRegion)) {
        uni.showToast({ title: '请选择到区县', icon: 'none' })

        return
    }

    selectedRegion.value = nextRegion

    saveServiceRegionSelection(selectedRegion.value)

    hideRegionPicker()

    resetAlternativeStaffState()

    await getDetail()

    if (pendingDatePickerAfterRegion.value) {
        pendingDatePickerAfterRegion.value = false

        setTimeout(() => openDatePicker(), 0)

        return
    }
}

const openDatePicker = () => {
    if (!hasSelectedRegion.value) {
        pendingDatePickerAfterRegion.value = true

        openRegionPicker()

        return
    }

    datePickerModel.value = formatDateText(getEffectiveSelectableDate(presetDate.value))

    showDatePopup.value = true
}

const hideDatePicker = () => {
    showDatePopup.value = false
}

const closeDatePicker = () => {
    hideDatePicker()

    pendingDatePickerAfterRegion.value = false
}

const handleDatePickerConfirm = async (value: unknown) => {
    const pickerValue =
        typeof value === 'string'
            ? value
            : String((value as Record<string, any>)?.value || datePickerModel.value || '')

    const nextDate = normalizeSelectedDateText(pickerValue) || formatDateText(getTomorrowDate())

    presetDate.value = nextDate

    hideDatePicker()

    resetAlternativeStaffState()

    await getDetail()

    pendingDatePickerAfterRegion.value = false
}

const buildStaffDetailQuery = (extra: Record<string, any> = {}) => {
    const queryStaffId = getCurrentStaffIdForQuery()

    const params = [`id=${queryStaffId}`]

    const regionQuery = buildServiceRegionQuery(selectedRegion.value)

    if (regionQuery) {
        params.push(regionQuery)
    }

    if (presetDate.value) {
        params.push(`date=${encodeURIComponent(presetDate.value)}`)
    }

    if (selectedPackageId.value) {
        params.push(`package_id=${selectedPackageId.value}`)
    }

    Object.entries(extra).forEach(([key, value]) => {
        if (value === '' || value === undefined || value === null) {
            return
        }

        params.push(`${key}=${encodeURIComponent(String(value))}`)
    })

    return params.join('&')
}

const buildTargetStaffDetailQuery = (targetStaffId: number, extra: Record<string, any> = {}) => {
    const params = [`id=${targetStaffId}`]

    const regionQuery = buildServiceRegionQuery(selectedRegion.value)

    if (regionQuery) {
        params.push(regionQuery)
    }

    if (presetDate.value) {
        params.push(`date=${encodeURIComponent(presetDate.value)}`)
    }

    if (selectedPackageId.value) {
        params.push(`package_id=${selectedPackageId.value}`)
    }

    Object.entries(extra).forEach(([key, value]) => {
        if (value === '' || value === undefined || value === null) {
            return
        }

        params.push(`${key}=${encodeURIComponent(String(value))}`)
    })

    return params.join('&')
}

const getBookingPageUrl = () =>
    getStaffBookingPageUrl({
        staff_id: staffId.value,

        package_id: selectedPackageId.value,

        waitlist_id: waitlistId.value,

        date: presetDate.value,

        return_mode: BOOKING_RETURN_MODE_DETAIL_BACK,

        ...selectedRegion.value
    })

const navigateToBookingPage = () => {
    saveCurrentDetailRestoreSnapshot()

    uni.navigateTo({
        url: getBookingPageUrl()
    })
}

const ensureBookingLogin = (message = '请先登录后预约') => {
    if (userStore.isLogin) {
        return true
    }

    cache.set(BACK_URL, getBookingPageUrl())

    uni.showToast({ title: message, icon: 'none' })

    setTimeout(() => {
        uni.navigateTo({ url: '/pages/login/login' })
    }, 300)

    return false
}

const promptWaitlistSubscribe = async () => {
    if (client !== ClientEnum.MP_WEIXIN) {
        return true
    }

    const result = await uni.showModal({
        title: '接收候补状态提醒',
        content: '订阅后可接收候补释放或失效提醒。',
        confirmText: '去订阅',
        cancelText: '暂不订阅'
    })

    if (!result.confirm) {
        return false
    }

    try {
        await subscribeWaitlistScenes()
    } catch (error) {
        console.error('请求候补订阅失败', error)
    }

    return true
}

const fetchAlternativeStaffList = async () => {
    if (!currentCategoryId.value || !presetDate.value || !hasSelectedRegion.value) {
        alternativeStaffList.value = []

        return
    }

    const result = await getStaffList({
        page_no: 1,

        page_size: 6,

        category_id: currentCategoryId.value,

        date: presetDate.value,

        sort: 'default',

        ...toServiceRegionParams(selectedRegion.value)
    })

    const list = Array.isArray(result?.lists) ? result.lists : []

    alternativeStaffList.value = list

        .filter((item: AlternativeStaffItem) => Number(item?.id || 0) !== staffId.value)

        .slice(0, 6)
}

const openAlternativeStaffPopup = async (reason = '') => {
    alternativeStaffReason.value = reason || '当前档期暂不可预约'

    alternativeStaffLoading.value = true

    alternativeStaffList.value = []

    showAlternativeStaffPopup.value = true

    try {
        await fetchAlternativeStaffList()
    } catch (error: any) {
        const errorMsg =
            typeof error === 'string'
                ? error
                : error?.msg || error?.message || '加载同类服务人员失败'

        uni.showToast({ title: errorMsg, icon: 'none' })

        alternativeStaffList.value = []
    } finally {
        alternativeStaffLoading.value = false
    }
}

const handleAlternativeStaffSelect = (item: AlternativeStaffItem) => {
    if (alternativeStaffQuerying.value) {
        return
    }

    const targetStaffId = Number(item?.id || 0)

    if (!targetStaffId) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })

        return
    }

    showAlternativeStaffPopup.value = false

    uni.navigateTo({
        url: `/packages/pages/staff_detail/staff_detail?${buildTargetStaffDetailQuery(
            targetStaffId,

            {
                open_booking_popup: 1
            }
        )}`
    })
}

const handleAlternativeJoinWaitlist = async () => {
    if (alternativeStaffQuerying.value) {
        return
    }

    if (selectedPackageId.value <= 0) {
        uni.showToast({ title: '当前人员暂无可候补套餐，请重新选择日期', icon: 'none' })

        return
    }

    alternativeStaffQuerying.value = true

    try {
        await promptWaitlistSubscribe()

        await joinWaitlist({
            staff_id: staffId.value,

            date: presetDate.value,

            package_id: selectedPackageId.value
        })

        showAlternativeStaffPopup.value = false

        uni.showToast({ title: '已加入候补', icon: 'success' })
    } catch (error: any) {
        const errorMsg =
            typeof error === 'string' ? error : error?.msg || error?.message || '加入候补失败'

        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        alternativeStaffQuerying.value = false
    }
}

// 立即预约

const handleBook = async () => {
    if (!staffId.value || staffId.value === 0) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })

        return
    }

    if (!hasSelectedRegion.value || !presetDate.value) {
        uni.showToast({ title: '请先选择服务地区与预约日期', icon: 'none' })

        if (!hasSelectedRegion.value) {
            handleInlineRegionEdit()

            return
        }

        handleInlineDateEdit()

        return
    }

    if (!ensureBookingLogin()) {
        return
    }

    if (alternativeStaffQuerying.value) {
        return
    }

    alternativeStaffQuerying.value = true

    try {
        const result = await checkScheduleAvailable({
            staff_id: staffId.value,

            date: presetDate.value,

            ...toServiceRegionParams(selectedRegion.value)
        })

        if (result?.is_available !== false) {
            navigateToBookingPage()

            return
        }

        await openAlternativeStaffPopup(
            String(result?.message || result?.status_desc || '').trim() || '当前档期暂不可预约'
        )
    } catch (error: any) {
        const errorMsg =
            typeof error === 'string' ? error : error?.msg || error?.message || '预约校验失败'

        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        alternativeStaffQuerying.value = false
    }
}

// 预览作品图片

const goWorkDetail = (work: any) => {
    if (!work?.id) {
        uni.showToast({ title: '作品信息错误', icon: 'none' })

        return
    }

    uni.navigateTo({
        url: `/packages/pages/staff_work_detail/staff_work_detail?id=${work.id}`
    })
}

const formatCertificateField = (value: unknown, fallback = '暂无') => {
    const text = String(value ?? '').trim()

    return text || fallback
}

const getCertificateSerialNumber = (certificate: StaffCertificateItem | null) => {
    return formatCertificateField(certificate?.sn || certificate?.certificate_no)
}

const getCertificateValidityText = (certificate: StaffCertificateItem | null) => {
    const expireDate = String(certificate?.expire_date || '').trim()

    return expireDate || '长期有效'
}

const getCertificateStatusText = (certificate: StaffCertificateItem | null) => {
    const statusText = String(
        certificate?.verify_status_desc || certificate?.audit_status_desc || ''
    ).trim()

    if (statusText) {
        return statusText
    }

    if (Number(certificate?.is_expired || 0) === 1 || certificate?.is_expired === true) {
        return '已过期'
    }

    return '有效中'
}

const openCertificatePopup = (certificate: StaffCertificateItem) => {
    if (!certificate) {
        return
    }

    activeCertificate.value = certificate

    showCertificatePopup.value = true
}

const closeCertificatePopup = () => {
    showCertificatePopup.value = false
}

const previewCertificateImage = (url: string) => {
    const imageUrl = String(url || '').trim()

    if (!imageUrl) {
        return
    }

    uni.previewImage({
        urls: [imageUrl],

        current: imageUrl
    })
}

const previewReviewImages = (images: Array<string | number>, index: number | string = 0) => {
    const urls = (images || []).map((item) => String(item)).filter(Boolean)

    if (!urls.length) return

    const currentIndex = Number(index || 0)

    uni.previewImage({
        urls,

        current: urls[currentIndex] || urls[0]
    })
}

const loadMoreReviews = () => {
    loadReviews()
}

const goReviewDetail = (review: any) => {
    if (!review?.id) {
        return
    }

    uni.navigateTo({
        url: `/packages/pages/review/detail?id=${review.id}`
    })
}

const formatReviewTime = (timestamp: number) => {
    if (!timestamp) {
        return '-'
    }

    return new Date(timestamp * 1000).toLocaleDateString()
}

const getShareTitle = () => {
    const staffName = String(staffInfo.value?.name || '').trim()

    const categoryName = String(staffInfo.value?.category?.name || '').trim()

    if (staffName && categoryName) {
        return `${staffName}｜${categoryName}`
    }

    if (staffName) {
        return `${staffName}｜服务人员详情`
    }

    return '服务人员详情'
}

const buildSharePayload = () => {
    const payload: {
        title: string

        path: string

        imageUrl?: string
    } = {
        title: getShareTitle(),

        path: `/packages/pages/staff_detail/staff_detail?${buildStaffDetailQuery({
            from_share: 1
        })}`
    }

    const avatar = String(staffInfo.value?.avatar || '').trim()

    if (avatar) {
        payload.imageUrl = avatar
    }

    return payload
}

onLoad((options) => {
    $theme.setScene('consumer')

    const pageOptions = resolveStaffDetailPageOptions(options)

    isShareEntry.value = resolveShareEntry(pageOptions)

    hideWechatHomeButtonForShareEntry()

    const pageStaffId = resolveStaffIdFromOptions(pageOptions)

    if (pageStaffId) {
        staffId.value = pageStaffId
    }

    selectedRegion.value = normalizeServiceRegion({
        ...loadServiceRegionSelection(),

        ...pageOptions
    })

    if (hasServiceRegion(selectedRegion.value)) {
        saveServiceRegionSelection(selectedRegion.value)
    }

    if (pageOptions?.date) {
        presetDate.value = normalizeSelectedDateText(pageOptions.date)
    }

    if (pageOptions?.package_id) {
        selectedPackageId.value = Number(pageOptions.package_id)
    }

    if (pageOptions?.waitlist_id) {
        waitlistId.value = Number(pageOptions.waitlist_id)
    }

    if (pageOptions?.open_date_picker === '1') {
        openDatePickerRequested.value = true
    }

    if (pageOptions?.open_booking_popup === '1') {
        openBookingPopupRequested.value = true
    }

    if (pageOptions?.tab && ['intro', 'works', 'reviews'].includes(pageOptions.tab)) {
        currentTab.value = pageOptions.tab
    }

    applyDetailRestoreSnapshot()
})

onShow(async () => {
    $theme.setScene('consumer')

    isShareEntry.value = isShareEntry.value || resolveShareEntry()

    hideWechatHomeButtonForShareEntry()

    applyPendingDetailReturnState()

    scheduleRestoreDetailScroll()

    void getRegionTree().catch(() => null)

    await getDetail()

    if (staffInfo.value && (openBookingPopupRequested.value || openDatePickerRequested.value)) {
        const shouldOpenDateEditor = openDatePickerRequested.value

        openBookingPopupRequested.value = false

        openDatePickerRequested.value = false

        if (shouldOpenDateEditor) {
            setTimeout(() => handleInlineDateEdit(), 0)
        } else {
            setTimeout(() => handleBook(), 0)
        }
    }
})

onPageScroll((event) => {
    detailScrollTop.value = Number(event.scrollTop || 0)
})

onShareAppMessage(() => {
    return buildSharePayload()
})

// #ifdef MP-WEIXIN

onShareTimeline(() => {
    const sharePayload = buildSharePayload()

    const timelinePayload: {
        title: string

        query: string

        imageUrl?: string
    } = {
        title: sharePayload.title,

        query: buildStaffDetailQuery({ from_share: 1 })
    }

    if (sharePayload.imageUrl) {
        timelinePayload.imageUrl = sharePayload.imageUrl
    }

    return timelinePayload
})

// #endif
</script>

<style lang="scss" scoped>
/* 加载状态 */

.loading-container,
.detail-state-shell {
    display: flex;

    align-items: center;

    justify-content: center;

    min-height: 100vh;

    background: var(--wm-color-bg-page, #ffffff);
}

.detail-state-shell {
    flex-direction: column;

    gap: 18rpx;

    box-sizing: border-box;
}

.detail-state-shell__actions {
    display: flex;

    align-items: center;

    justify-content: center;
}

.detail-state-shell__link {
    min-height: 64rpx;

    padding: 0 28rpx;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    font-size: 24rpx;

    font-weight: 600;

    color: var(--wm-text-secondary, #5f5a50);
}

.staff-detail {
    min-height: 100%;

    background:
        linear-gradient(180deg, rgba(25, 23, 19, 0.08) 0, rgba(255, 253, 248, 0) 260rpx),
        transparent;
}

.staff-detail__content {
    display: flex;

    flex-direction: column;

    gap: 18rpx;

    padding: 16rpx 28rpx calc(var(--wm-safe-bottom-action, 150rpx) + 28rpx);
}

.hero-card {
    position: relative;

    overflow: hidden;

    border-radius: 26rpx;

    background: linear-gradient(135deg, #d8c28a 0%, #f4ead2 100%);

    box-shadow: 0 18rpx 42rpx rgba(25, 23, 19, 0.14);
}

.hero-card__banner {
    display: block;

    max-height: 520rpx;
}

.hero-card__banner :deep(.banner-container),
.hero-card__banner :deep(.banner-swiper),
.hero-card__banner :deep(.media-container),
.hero-card__banner :deep(.banner-media),
.hero-card__banner :deep(.banner-video) {
    border-radius: 26rpx;
}

.info-card {
    margin-top: -44rpx;

    position: relative;

    z-index: 2;

    border-color: rgba(217, 190, 130, 0.84) !important;

    box-shadow: 0 18rpx 42rpx rgba(17, 17, 17, 0.2) !important;
}

.info-card__inner {
    display: flex;

    flex-direction: column;

    gap: 16rpx;

    padding: 26rpx 26rpx 24rpx;
}

.info-card__header {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 18rpx;
}

.info-card__identity {
    flex: 1;

    min-width: 0;

    display: flex;

    flex-direction: column;

    gap: 8rpx;
}

.info-card__name {
    display: block;

    font-size: 38rpx;

    line-height: 1.2;

    font-weight: 800;

    color: #fffdf8;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.info-card__summary {
    display: block;

    font-size: 24rpx;

    line-height: 1.35;

    color: rgba(255, 253, 248, 0.72);

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.info-card__favorite {
    flex-shrink: 0;

    width: 76rpx;

    height: 76rpx;
}

.info-card__favorite :deep(.base-icon-button) {
    box-shadow: none;
}

.info-card__badge-list {
    display: flex;

    flex-wrap: nowrap;

    gap: 8rpx;

    min-width: 0;

    overflow: hidden;
}

.info-card__badge-list :deep(.status-badge) {
    flex-shrink: 1;

    min-width: 0;
}

.info-card__badge-list :deep(.status-badge__text) {
    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.info-card__metric-row {
    display: grid;

    grid-template-columns: repeat(3, minmax(0, 1fr));

    gap: 10rpx;
}

.info-card__metric {
    display: flex;

    flex-direction: row;

    align-items: baseline;

    justify-content: center;

    gap: 6rpx;

    min-width: 0;

    padding: 14rpx 8rpx;

    border-radius: 18rpx;

    background: rgba(255, 253, 248, 0.1);

    border: 1rpx solid rgba(217, 190, 130, 0.2);
}

.info-card__metric-value {
    min-width: 0;

    font-size: 29rpx;

    line-height: 1.1;

    font-weight: 800;

    color: #d9be82;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.info-card__metric-label {
    font-size: 20rpx;

    line-height: 1.2;

    color: rgba(255, 253, 248, 0.62);

    white-space: nowrap;
}

.info-card__price-row {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;

    min-height: 58rpx;

    padding: 14rpx 16rpx;

    border-radius: 20rpx;

    background: rgba(255, 253, 248, 0.92);
}

.info-card__price-label {
    font-size: 24rpx;

    line-height: 1.3;

    color: #5f5a50;
}

.info-card__price-group {
    display: flex;

    align-items: baseline;

    justify-content: flex-end;

    flex-wrap: wrap;

    gap: 4rpx;

    min-width: 0;
}

.info-card__price-symbol,
.info-card__price-value {
    color: #0b0b0b;

    font-weight: 700;
}

.info-card__price-symbol {
    font-size: 24rpx;
}

.info-card__price-value {
    font-size: 40rpx;

    line-height: 1;
}

.info-card__price-unit,
.info-card__price-negotiable {
    font-size: 22rpx;

    line-height: 1.3;

    color: #5f5a50;
}

.info-card__price-negotiable {
    font-size: 28rpx;

    font-weight: 700;

    color: #111111;
}

.booking-brief-card__inner {
    padding: 10rpx 8rpx;

    overflow: hidden;
}

.booking-brief-card__grid {
    display: grid;

    grid-template-columns: minmax(0, 0.92fr) minmax(0, 1.08fr);

    width: 100%;

    max-width: 100%;

    gap: 8rpx;

    min-width: 0;

    box-sizing: border-box;
}

.booking-brief-card__field {
    display: block;

    width: 100%;

    max-width: 100%;

    min-width: 0;

    overflow: hidden;

    box-sizing: border-box;
}

.booking-brief-card__field :deep(.base-picker-field) {
    width: 100%;

    max-width: 100%;

    gap: 8rpx;

    min-height: 82rpx;

    padding: 0 10rpx;

    border-radius: 20rpx;

    box-shadow: none;

    overflow: hidden;

    box-sizing: border-box;
}

.booking-brief-card__field :deep(.base-picker-field__icon) {
    width: 40rpx;

    height: 40rpx;

    border-radius: 14rpx;
}

.booking-brief-card__field :deep(.base-picker-field__copy) {
    flex: 1 1 auto;

    min-width: 0;

    gap: 4rpx;
}

.booking-brief-card__field :deep(.base-picker-field__meta) {
    gap: 6rpx;

    overflow: hidden;

    max-width: 100%;
}

.booking-brief-card__field :deep(.base-picker-field__label) {
    font-size: 19rpx;

    line-height: 1.1;
}

.booking-brief-card__field :deep(.base-picker-field__status) {
    min-height: 24rpx;

    padding: 0 8rpx;

    font-size: 16rpx;
}

.booking-brief-card__field :deep(.base-picker-field__value) {
    font-size: 23rpx;

    line-height: 1.2;

    max-width: 100%;
}

.booking-brief-card__field :deep(.base-picker-field > .base-icon:last-child) {
    flex: 0 0 22rpx;

    width: 22rpx;

    font-size: 22rpx !important;

    flex-shrink: 0;
}

.booking-brief-card__field--date :deep(.base-picker-field) {
    padding-right: 8rpx;
}

.booking-brief-card__field--date :deep(.base-picker-field__value) {
    letter-spacing: 0;

    font-size: 22rpx;
}

@media (max-width: 360px) {
    .booking-brief-card__grid {
        grid-template-columns: minmax(0, 0.88fr) minmax(0, 1.12fr);

        gap: 8rpx;
    }

    .booking-brief-card__field :deep(.base-picker-field) {
        gap: 8rpx;

        padding: 0 10rpx;
    }

    .booking-brief-card__field :deep(.base-picker-field__icon) {
        display: none;
    }
}

.alternative-popup {
    max-height: 78vh;

    padding: 32rpx 28rpx 28rpx;

    background: linear-gradient(180deg, #FFFFFF 0%, #FFFFFF 100%);
}

.alternative-popup__header {
    display: flex;

    flex-direction: column;

    gap: 12rpx;
}

.alternative-popup__badge {
    align-self: flex-start;

    padding: 8rpx 18rpx;

    border-radius: 999rpx;

    background: rgba(11, 11, 11, 0.1);

    border: 1rpx solid rgba(11, 11, 11, 0.16);
}

.alternative-popup__badge-text {
    font-size: 22rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #9f7a2e;
}

.alternative-popup__title {
    font-size: 34rpx;

    line-height: 1.25;

    font-weight: 700;

    color: #111111;
}

.alternative-popup__desc {
    font-size: 24rpx;

    line-height: 1.7;

    color: #5f5a50;
}

.alternative-popup__loading,
.alternative-popup__empty {
    min-height: 280rpx;

    display: flex;

    align-items: center;

    justify-content: center;
}

.alternative-popup__loading {
    padding: 40rpx 0 24rpx;
}

.alternative-popup__scroll {
    max-height: 620rpx;

    margin-top: 24rpx;
}

.alternative-popup__list {
    display: flex;

    flex-direction: column;

    gap: 16rpx;

    padding-bottom: 8rpx;
}

.alternative-card {
    display: flex;

    gap: 18rpx;

    padding: 20rpx;

    border-radius: 30rpx;

    background: rgba(255, 255, 255, 0.94);

    border: 1rpx solid rgba(231, 226, 214, 0.96);

    box-shadow: 0 14rpx 28rpx rgba(17, 17, 17, 0.1);
}

.alternative-card__avatar {
    width: 148rpx;

    height: 148rpx;

    flex-shrink: 0;

    border-radius: 24rpx;

    background: linear-gradient(135deg, #f7f0df 0%, #d8c28a 100%);
}

.alternative-card__content {
    flex: 1;

    min-width: 0;

    display: flex;

    flex-direction: column;
}

.alternative-card__head {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 12rpx;
}

.alternative-card__name-group {
    min-width: 0;

    display: flex;

    align-items: center;

    gap: 8rpx;
}

.alternative-card__name {
    min-width: 0;

    font-size: 30rpx;

    line-height: 1.35;

    font-weight: 700;

    color: #111111;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.alternative-card__badge {
    flex-shrink: 0;

    padding: 4rpx 12rpx;

    border-radius: 999rpx;

    font-size: 20rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #ffffff;

    background: linear-gradient(135deg, #0b0b0b 0%, #c8a45d 100%);
}

.alternative-card__price {
    flex-shrink: 0;

    font-size: 26rpx;

    line-height: 1.2;

    font-weight: 700;

    color: #0b0b0b;
}

.alternative-card__role {
    display: block;

    margin-top: 8rpx;

    font-size: 22rpx;

    line-height: 1.45;

    color: #5f5a50;
}

.alternative-card__tags {
    display: flex;

    flex-wrap: wrap;

    gap: 8rpx;

    margin-top: 10rpx;
}

.alternative-card__tag {
    padding: 6rpx 12rpx;

    border-radius: 999rpx;

    font-size: 20rpx;

    line-height: 1.2;

    color: #0b0b0b;

    background: rgba(11, 11, 11, 0.08);

    border: 1rpx solid rgba(11, 11, 11, 0.16);
}

.alternative-card__desc {
    display: -webkit-box;

    margin-top: 10rpx;

    font-size: 22rpx;

    line-height: 1.5;

    color: #5f5a50;

    overflow: hidden;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;
}

.alternative-card__footer {
    margin-top: auto;

    padding-top: 14rpx;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 12rpx;
}

.alternative-card__score {
    display: inline-flex;

    align-items: center;

    gap: 6rpx;
}

.alternative-card__score-text {
    font-size: 22rpx;

    line-height: 1.2;

    font-weight: 700;

    color: #9F7A2E;
}

.alternative-card__orders {
    font-size: 20rpx;

    line-height: 1.2;

    color: #5f5a50;
}

.alternative-popup__empty {
    flex-direction: column;

    gap: 12rpx;

    padding: 40rpx 10rpx 16rpx;

    text-align: center;
}

.alternative-popup__empty-title {
    font-size: 30rpx;

    line-height: 1.3;

    font-weight: 700;

    color: #111111;
}

.alternative-popup__empty-desc {
    font-size: 24rpx;

    line-height: 1.7;

    color: #5f5a50;
}

.alternative-popup__actions {
    display: flex;

    gap: 16rpx;

    margin-top: 28rpx;
}

.alternative-popup__btn {
    flex: 1;

    min-height: 88rpx;

    border-radius: 999rpx;

    display: flex;

    align-items: center;

    justify-content: center;
}

.alternative-popup__btn--ghost {
    background: rgba(255, 255, 255, 0.94);

    border: 1rpx solid rgba(231, 226, 214, 0.96);
}

.alternative-popup__btn--primary {
    box-shadow: 0 14rpx 28rpx rgba(11, 11, 11, 0.18);
}

.alternative-popup__btn-text {
    font-size: 28rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #5F5A50;
}

.alternative-popup__btn-text--primary {
    color: #ffffff;
}

.tabs-section {
    padding: 6rpx;

    border-radius: 26rpx;

    background: rgba(255, 253, 248, 0.96);

    border: 1rpx solid var(--wm-color-border, #d8c9ad);

    box-shadow: 0 12rpx 28rpx rgba(74, 43, 24, 0.08);

    backdrop-filter: none;

    -webkit-backdrop-filter: none;
}

.tabs-wrapper {
    display: flex;

    align-items: center;

    gap: 6rpx;
}

.tab-item {
    flex: 1;

    min-width: 0;

    height: 68rpx;

    padding: 0 16rpx;

    border-radius: 22rpx;

    display: flex;

    align-items: center;

    justify-content: center;
}

.tab-item--active {
    background: linear-gradient(135deg, #191713 0%, #332817 100%);

    box-shadow: 0 10rpx 20rpx rgba(25, 23, 19, 0.2);
}

.tab-text {
    font-size: 25rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #9A9388;
}

.tab-text--active {
    color: #ffffff;
}

.tab-content {
    margin: 0;
}

.content-section {
    padding: 0;

    background: transparent;

    border-radius: 0;
}

.content-section--stack {
    display: flex;

    flex-direction: column;

    gap: 18rpx;
}

.detail-stream-shell {
    overflow: hidden;

    border-radius: 26rpx;

    border: 1rpx solid rgba(216, 201, 173, 0.78);

    box-shadow: 0 14rpx 30rpx rgba(74, 43, 24, 0.08);
}

.soft-card {
    display: flex;

    flex-direction: column;

    gap: 18rpx;

    padding: 24rpx 26rpx;

    border-radius: 24rpx;

    background: rgba(255, 253, 248, 0.96);

    border: 1rpx solid var(--wm-color-border, #d8c9ad);

    box-shadow: 0 12rpx 28rpx rgba(74, 43, 24, 0.07);

    backdrop-filter: none;

    -webkit-backdrop-filter: none;
}

.soft-card__title {
    font-size: 29rpx;

    line-height: 1.2;

    font-weight: 700;

    color: #111111;
}

.soft-card__content {
    font-size: 26rpx;

    line-height: 1.8;

    color: #5F5A50;
}

.soft-tags {
    display: flex;

    flex-wrap: wrap;

    gap: 10rpx;
}

.soft-tag {
    padding: 9rpx 16rpx;

    border-radius: 999rpx;

    background: #f8f7f2;

    border: 1rpx solid rgba(11, 11, 11, 0.14);
}

.soft-tag__text {
    font-size: 23rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #9f7a2e;
}

.works-grid {
    display: grid;

    grid-template-columns: repeat(2, minmax(0, 1fr));

    gap: 14rpx;
}

.work-item {
    position: relative;

    overflow: hidden;

    border-radius: 24rpx;

    background: linear-gradient(135deg, #F7F0DF 0%, #D8D3C7 100%);

    box-shadow: 0 12rpx 26rpx rgba(17, 17, 17, 0.12);
}

.work-image {
    width: 100%;

    height: 232rpx;
}

.work-overlay {
    position: absolute;

    inset: auto 0 0 0;

    padding: 16rpx 14rpx;

    background: linear-gradient(180deg, rgba(11, 11, 11, 0) 0%, rgba(11, 11, 11, 0.6) 100%);
}

.work-title {
    display: block;

    font-size: 24rpx;

    line-height: 1.35;

    font-weight: 600;

    color: #ffffff;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}

.review-summary {
    display: grid;

    grid-template-columns: repeat(3, minmax(0, 1fr));

    gap: 10rpx;
}

.review-summary-card {
    display: flex;

    flex-direction: column;

    gap: 6rpx;

    padding: 22rpx 14rpx;

    border-radius: 22rpx;

    background: rgba(255, 253, 248, 0.96);

    border: 1rpx solid var(--wm-color-border, #d8c9ad);
}

.review-summary-value {
    font-size: 31rpx;

    line-height: 1.1;

    font-weight: 700;

    color: #111111;
}

.review-summary-label {
    font-size: 21rpx;

    line-height: 1.3;

    color: #9a9388;
}

.review-filter-row {
    display: flex;

    flex-wrap: wrap;

    gap: 10rpx;
}

.review-filter-item {
    padding: 9rpx 16rpx;

    border-radius: 999rpx;

    background: #f8f7f2;

    border: 1rpx solid rgba(11, 11, 11, 0.12);

    font-size: 22rpx;

    line-height: 1.2;

    color: #5A4433;
}

.reviews-list {
    display: flex;

    flex-direction: column;

    gap: 14rpx;
}

.review-card {
    padding: 24rpx 26rpx;

    border-radius: 24rpx;

    background: rgba(255, 253, 248, 0.96);

    border: 1rpx solid var(--wm-color-border, #d8c9ad);

    box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.06);
}

.review-card-header {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 14rpx;
}

.review-user {
    flex: 1;

    min-width: 0;

    display: flex;

    align-items: center;

    gap: 14rpx;
}

.review-user-avatar {
    width: 68rpx;

    height: 68rpx;

    border-radius: 50%;

    background: #F8F7F2;

    flex-shrink: 0;
}

.review-user-info {
    flex: 1;

    min-width: 0;

    display: flex;

    flex-direction: column;

    gap: 6rpx;
}

.review-user-name {
    font-size: 26rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #111111;
}

.review-time {
    font-size: 21rpx;

    line-height: 1.2;

    color: #9a9388;
}

.review-score {
    display: inline-flex;

    align-items: center;

    gap: 4rpx;
}

.review-content {
    display: block;

    margin-top: 14rpx;

    font-size: 25rpx;

    line-height: 1.65;

    color: #5F5A50;
}

.review-tag-list {
    display: flex;

    flex-wrap: wrap;

    gap: 10rpx;

    margin-top: 14rpx;
}

.review-tag {
    padding: 7rpx 13rpx;

    border-radius: 999rpx;

    background: #f8f7f2;

    border: 1rpx solid rgba(11, 11, 11, 0.12);

    font-size: 22rpx;

    line-height: 1.2;

    color: #5A4433;
}

.review-image-list {
    display: flex;

    flex-wrap: wrap;

    gap: 10rpx;

    margin-top: 14rpx;
}

.review-image {
    width: calc((100% - 20rpx) / 3);

    height: 172rpx;

    border-radius: 20rpx;

    background: #F8F7F2;
}

.review-reply-list {
    display: flex;

    flex-direction: column;

    gap: 10rpx;

    margin-top: 14rpx;
}

.review-reply-item {
    padding: 18rpx 20rpx;

    border-radius: 20rpx;

    background: #FFFFFF;

    border: 1rpx solid rgba(11, 11, 11, 0.08);
}

.review-reply-type {
    display: block;

    margin-bottom: 8rpx;

    font-size: 22rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #9f7a2e;
}

.review-reply-content {
    display: block;

    font-size: 24rpx;

    line-height: 1.7;

    color: #5F5A50;
}

.review-load-more {
    padding-top: 4rpx;

    text-align: center;
}

.review-load-more-text {
    font-size: 24rpx;

    line-height: 1.3;

    color: #9a9388;
}

.review-load-more-text--action {
    color: #0b0b0b;

    font-weight: 600;
}

.certs-scroll {
    white-space: nowrap;
}

.certs-wrapper {
    display: inline-flex;

    gap: 12rpx;
}

.cert-item {
    display: inline-flex;

    flex-direction: column;

    gap: 10rpx;

    width: 200rpx;
}

.cert-image {
    width: 200rpx;

    height: 134rpx;

    border-radius: 20rpx;

    background: #F8F7F2;
}

.cert-name {
    font-size: 22rpx;

    line-height: 1.4;

    color: #5F5A50;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}

.certificate-popup {
    display: flex;

    flex-direction: column;

    gap: 22rpx;

    padding: 28rpx 28rpx 34rpx;

    background: linear-gradient(180deg, #FFFFFF 0%, #F8F7F2 100%);
}

.certificate-popup__header {
    display: flex;

    flex-direction: column;

    gap: 12rpx;
}

.certificate-popup__badge {
    display: inline-flex;

    align-self: flex-start;

    padding: 10rpx 18rpx;

    border-radius: 999rpx;

    background: rgba(11, 11, 11, 0.1);
}

.certificate-popup__badge-text {
    font-size: 22rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #9f7a2e;
}

.certificate-popup__title {
    font-size: 34rpx;

    line-height: 1.35;

    font-weight: 700;

    color: #111111;
}

.certificate-popup__desc {
    font-size: 24rpx;

    line-height: 1.6;

    color: #5f5a50;
}

.certificate-popup__image {
    width: 100%;

    height: 360rpx;

    border-radius: var(--wm-radius-card-lg, 28rpx);

    background: #F8F7F2;

    box-shadow: 0 14rpx 32rpx rgba(17, 17, 17, 0.14);
}

.certificate-popup__meta-list {
    display: flex;

    flex-direction: column;

    gap: 14rpx;
}

.certificate-popup__meta-item {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 24rpx;

    padding: 22rpx 24rpx;

    border-radius: 30rpx;

    background: rgba(255, 255, 255, 0.94);

    border: 1rpx solid rgba(231, 226, 214, 0.96);
}

.certificate-popup__meta-label {
    flex-shrink: 0;

    font-size: 24rpx;

    line-height: 1.5;

    color: #9a9388;
}

.certificate-popup__meta-value {
    flex: 1;

    min-width: 0;

    font-size: 25rpx;

    line-height: 1.6;

    font-weight: 600;

    color: #111111;

    text-align: right;

    word-break: break-all;
}

.certificate-popup__meta-value--status {
    color: #9f7a2e;
}

.certificate-popup__actions {
    padding-top: 6rpx;
}

.certificate-popup__btn {
    min-height: 88rpx;

    border-radius: 999rpx;

    display: flex;

    align-items: center;

    justify-content: center;

    box-shadow: 0 14rpx 28rpx rgba(11, 11, 11, 0.18);
}

.certificate-popup__btn-text {
    font-size: 28rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #ffffff;
}

.empty-card {
    display: flex;

    align-items: center;

    justify-content: center;

    min-height: 190rpx;

    padding: 28rpx;

    border-radius: 24rpx;

    background: rgba(255, 253, 248, 0.96);

    border: 1rpx dashed rgba(216, 194, 138, 0.8);
}

.empty-card__text {
    font-size: 26rpx;

    line-height: 1.4;

    color: #9a9388;
}

.loading-container,
.loading-state {
    display: flex;

    align-items: center;

    justify-content: center;
}

.loading-state {
    min-height: 190rpx;

    border-radius: 24rpx;

    background: rgba(255, 253, 248, 0.82);
}

.staff-detail__action-bar {
    display: flex;

    align-items: center;

    gap: 14rpx;

    width: 100%;
}

.action-button {
    position: relative;

    flex: 0 0 132rpx;

    min-height: 84rpx;

    border-radius: var(--wm-radius-action, 999rpx);

    display: flex;

    align-items: center;

    justify-content: center;

    background: rgba(255, 253, 248, 0.96);

    border: 1rpx solid rgba(226, 222, 213, 0.96);

    box-shadow: var(--wm-shadow-soft, 0 8rpx 20rpx rgba(17, 17, 17, 0.05));

    overflow: hidden;
}

.action-button--primary {
    flex: 1;

    min-width: 0;

    background: #0b0b0b;

    border-color: #0b0b0b;

    box-shadow: 0 14rpx 28rpx rgba(11, 11, 11, 0.22);
}

.action-button__text {
    font-size: 26rpx;

    line-height: 1.2;

    font-weight: 700;

    color: #111111;
}

.action-button__text--primary {
    color: #ffffff;

    font-size: 29rpx;
}

.share-action-item {
    position: relative;
}

.share-action-trigger {
    position: absolute;

    inset: 0;

    width: 100%;

    height: 100%;

    padding: 0;

    margin: 0;

    background: transparent;

    border: none;

    box-shadow: none;

    opacity: 0;

    appearance: none;

    -webkit-appearance: none;

    -webkit-tap-highlight-color: transparent;
}

.share-action-trigger::after {
    display: none;
}

/* #ifdef MP-WEIXIN */
.info-card__inner {
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
/* #endif */

@media (max-width: 360px) {
    .staff-detail__content {
        padding-left: 22rpx;
        padding-right: 22rpx;
    }

    .info-card__inner {
        padding: 24rpx 22rpx 22rpx;
    }

    .info-card__name {
        font-size: 35rpx;
    }

    .info-card__metric {
        padding-left: 6rpx;
        padding-right: 6rpx;
    }

    .tab-text {
        font-size: 23rpx;
    }

    .action-button {
        flex-basis: 112rpx;
    }

    .action-button__text {
        font-size: 24rpx;
    }

    .action-button__text--primary {
        font-size: 27rpx;
    }
}
</style>
