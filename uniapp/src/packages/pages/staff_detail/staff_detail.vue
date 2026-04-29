<template>
    <page-meta :page-style="$theme.pageStyle" />

    <PageShell scene="consumer" tone="editorial">
        <BaseNavbar title="人员详情" :back="!isShareEntry" title-align="left" />

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

                <BaseCard
                    variant="glass"
                    scene="consumer"
                    padding="0"
                    border-radius="49rpx"
                    background="rgba(255, 255, 255, 0.84)"
                    border="1rpx solid rgba(231, 226, 214, 0.96)"
                    box-shadow="0 16rpx 34rpx rgba(17, 17, 17, 0.1)"
                >
                    <view class="info-card__inner">
                        <view class="info-card__header">
                            <view class="info-card__identity">
                                <text class="info-card__name">{{ staffInfo.name }}</text>

                                <text class="info-card__summary">{{ staffSummaryText }}</text>
                            </view>

                            <view class="info-card__favorite" @click="handleToggleFavorite">
                                <tn-icon
                                    :name="staffInfo.is_favorite ? 'star-fill' : 'star'"
                                    size="34"
                                    :color="staffInfo.is_favorite ? '#0B0B0B' : '#9A9388'"
                                />

                                <text
                                    class="info-card__favorite-text"
                                    :class="{
                                        'info-card__favorite-text--active': staffInfo.is_favorite
                                    }"
                                >
                                    {{ staffInfo.is_favorite ? '已收藏' : '收藏' }}
                                </text>
                            </view>
                        </view>

                        <view v-if="statusBadgeList.length" class="info-card__badge-list">
                            <StatusBadge
                                v-for="badge in statusBadgeList"
                                :key="badge"
                                tone="neutral"
                                size="sm"
                            >
                                {{ badge }}
                            </StatusBadge>
                        </view>

                        <view class="info-card__metric-row">
                            <view class="info-card__metric">
                                <text class="info-card__metric-value">{{
                                    staffInfo.rating ?? '0.0'
                                }}</text>

                                <text class="info-card__metric-label">综合评分</text>
                            </view>

                            <view class="info-card__metric">
                                <text class="info-card__metric-value">{{
                                    staffInfo.order_count || 0
                                }}</text>

                                <text class="info-card__metric-label">服务场次</text>
                            </view>

                            <view class="info-card__metric">
                                <text class="info-card__metric-value">{{
                                    staffInfo.view_count || 0
                                }}</text>

                                <text class="info-card__metric-label">浏览次数</text>
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

                <BaseCard
                    variant="surface"
                    scene="consumer"
                    padding="0"
                    border-radius="52rpx"
                    background="linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(248, 247, 242, 0.96))"
                    border="1rpx solid #e7e2d6"
                    box-shadow="0 18rpx 34rpx rgba(17, 17, 17, 0.12)"
                >
                    <view class="booking-brief-card__inner">
                        <view class="booking-brief-card__head">
                            <text class="booking-brief-card__eyebrow">预约信息</text>
                            <text class="booking-brief-card__title">先确认服务地区与预约日期</text>
                        </view>

                        <view class="booking-brief-card__grid">
                            <view class="booking-brief-card__item" @click="handleInlineRegionEdit">
                                <text class="booking-brief-card__label">服务地区</text>

                                <text class="booking-brief-card__value">
                                    {{ hasSelectedRegion ? selectedRegionText : '请选择服务区县' }}
                                </text>
                            </view>

                            <view class="booking-brief-card__item" @click="handleInlineDateEdit">
                                <text class="booking-brief-card__label">预约日期</text>

                                <text class="booking-brief-card__value">
                                    {{ presetDate || '请选择预约日期' }}
                                </text>
                            </view>
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
                                    {{ reviewStats.avg_score || '5.0' }}
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
                                        <tn-icon
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

            <BaseOverlayMask :show="showRegionPopup" @close="closeRegionPicker" />

            <tn-popup
                v-model="showRegionPopup"
                open-direction="bottom"
                :overlay="false"
                :overlay-closeable="true"
                safe-area-inset-bottom
                :radius="24"
            >
                <view class="picker-container region-picker-container">
                    <view class="picker-header">
                        <text class="picker-action" @click="closeRegionPicker">取消</text>

                        <text class="picker-title">选择服务地区</text>

                        <text
                            class="picker-action picker-action-primary"
                            @click="confirmRegionPicker"
                        >
                            确定
                        </text>
                    </view>

                    <view class="region-picker-content">
                        <view class="region-picker-col">
                            <view class="region-picker-col__title">省份</view>

                            <scroll-view scroll-y class="region-picker-scroll">
                                <view
                                    v-for="province in regionProvinces"
                                    :key="province.province_code"
                                    class="region-picker-item"
                                    :class="{
                                        active: tempRegion.province_code === province.province_code
                                    }"
                                    @click="handleProvinceSelect(province)"
                                >
                                    {{ province.province_name }}
                                </view>
                            </scroll-view>
                        </view>

                        <view class="region-picker-col">
                            <view class="region-picker-col__title">城市</view>

                            <scroll-view scroll-y class="region-picker-scroll">
                                <view
                                    v-for="city in regionCities"
                                    :key="city.city_code"
                                    class="region-picker-item"
                                    :class="{ active: tempRegion.city_code === city.city_code }"
                                    @click="handleCitySelect(city)"
                                >
                                    {{ city.city_name }}
                                </view>
                            </scroll-view>
                        </view>

                        <view class="region-picker-col">
                            <view class="region-picker-col__title">区县</view>

                            <scroll-view scroll-y class="region-picker-scroll">
                                <view
                                    v-for="district in regionDistricts"
                                    :key="district.district_code"
                                    class="region-picker-item"
                                    :class="{
                                        active: tempRegion.district_code === district.district_code
                                    }"
                                    @click="handleDistrictSelect(district)"
                                >
                                    {{ district.district_name }}
                                </view>
                            </scroll-view>
                        </view>
                    </view>

                    <view class="picker-footer">
                        <view class="picker-btn" @click="resetRegionSelection">清空</view>

                        <view
                            class="picker-btn picker-btn-primary"
                            :style="{ background: $theme.primaryColor }"
                            @click="confirmRegionPicker"
                        >
                            确定
                        </view>
                    </view>
                </view>
            </tn-popup>

            <BaseOverlayMask :show="showDatePopup" @close="closeDatePicker" />

            <tn-popup
                v-model="showDatePopup"
                open-direction="bottom"
                :overlay="false"
                :overlay-closeable="true"
                safe-area-inset-bottom
                :radius="24"
            >
                <view class="picker-container">
                    <view class="picker-header">
                        <text class="picker-action" @click="closeDatePicker">取消</text>

                        <text class="picker-title">选择预约日期</text>

                        <text
                            class="picker-action picker-action-primary"
                            @click="confirmDatePicker"
                        >
                            确定
                        </text>
                    </view>

                    <view class="date-picker-content">
                        <picker-view
                            class="date-picker-view"
                            :value="datePickerValue"
                            @change="handleDatePickerChange"
                        >
                            <picker-view-column>
                                <view
                                    v-for="year in datePickerYears"
                                    :key="`year-${year}`"
                                    class="picker-item"
                                >
                                    {{ year }}年
                                </view>
                            </picker-view-column>

                            <picker-view-column>
                                <view
                                    v-for="month in datePickerMonths"
                                    :key="`month-${month}`"
                                    class="picker-item"
                                >
                                    {{ month }}月
                                </view>
                            </picker-view-column>

                            <picker-view-column>
                                <view
                                    v-for="day in datePickerDays"
                                    :key="`day-${day}`"
                                    class="picker-item"
                                >
                                    {{ day }}日
                                </view>
                            </picker-view-column>
                        </picker-view>
                    </view>
                </view>
            </tn-popup>

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
                                            <tn-icon name="star-fill" size="20" color="#C8A45D" />

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

        <!-- 加载状态 -->

        <view v-else class="loading-container">
            <tn-loading mode="circle" />
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

const staffId = ref<number>(0)

const staffInfo = ref<any>(null)

const isShareEntry = ref(false)

const currentTab = ref('intro')

const presetDate = ref('') // 预设日期

const showDatePopup = ref(false)

const showRegionPopup = ref(false)

const datePickerValue = ref([0, 0, 0])

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

const tempRegion = ref(normalizeServiceRegion(selectedRegion.value))

const $theme = useThemeStore()

const userStore = useUserStore()

const DETAIL_TAB_KEYS = ['intro', 'works', 'reviews'] as const

// 轮播图数据

const bannerList = ref<any[]>([])

const bannerConfig = ref({
    banner_mode: 1,

    banner_small_height: 400,

    banner_large_height: 600,

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

    avg_score: '5.0',

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

const datePickerYears = computed(() => {
    const minDate = getTomorrowDate()

    const maxDate = getMaxDateForPicker()

    const totalYears = maxDate.getFullYear() - minDate.getFullYear() + 1

    return Array.from({ length: totalYears }, (_, index) => minDate.getFullYear() + index)
})

const getDatePickerMonthsByYear = (year: number) => {
    const minDate = getTomorrowDate()

    const maxDate = getMaxDateForPicker()

    const startMonth = year === minDate.getFullYear() ? minDate.getMonth() + 1 : 1

    const endMonth = year === maxDate.getFullYear() ? maxDate.getMonth() + 1 : 12

    return Array.from({ length: endMonth - startMonth + 1 }, (_, index) => startMonth + index)
}

const getDatePickerDaysByYearMonth = (year: number, month: number) => {
    const minDate = getTomorrowDate()

    const maxDate = getMaxDateForPicker()

    const isMinMonth = year === minDate.getFullYear() && month === minDate.getMonth() + 1

    const isMaxMonth = year === maxDate.getFullYear() && month === maxDate.getMonth() + 1

    const startDay = isMinMonth ? minDate.getDate() : 1

    const endDay = isMaxMonth ? maxDate.getDate() : new Date(year, month, 0).getDate()

    return Array.from({ length: endDay - startDay + 1 }, (_, index) => startDay + index)
}

const normalizeDatePickerValue = (value: number[]) => {
    const yearIndex = Math.min(Math.max(value[0] ?? 0, 0), datePickerYears.value.length - 1)

    const year = datePickerYears.value[yearIndex]

    const months = getDatePickerMonthsByYear(year)

    const monthIndex = Math.min(Math.max(value[1] ?? 0, 0), months.length - 1)

    const month = months[monthIndex]

    const days = getDatePickerDaysByYearMonth(year, month)

    const dayIndex = Math.min(Math.max(value[2] ?? 0, 0), days.length - 1)

    return [yearIndex, monthIndex, dayIndex]
}

const datePickerMonths = computed(() => {
    const yearIndex = Math.min(
        Math.max(datePickerValue.value[0] ?? 0, 0),

        datePickerYears.value.length - 1
    )

    const year = datePickerYears.value[yearIndex]

    return getDatePickerMonthsByYear(year)
})

const datePickerDays = computed(() => {
    const yearIndex = Math.min(
        Math.max(datePickerValue.value[0] ?? 0, 0),

        datePickerYears.value.length - 1
    )

    const year = datePickerYears.value[yearIndex]

    const monthIndex = Math.min(
        Math.max(datePickerValue.value[1] ?? 0, 0),

        Math.max(datePickerMonths.value.length - 1, 0)
    )

    const month = datePickerMonths.value[monthIndex]

    return getDatePickerDaysByYearMonth(year, month)
})

const hasSelectedRegion = computed(() => hasServiceRegion(selectedRegion.value))

const selectedRegionText = computed(() => {
    if (!hasSelectedRegion.value) {
        return '请选择服务区县'
    }

    const cityName = String(selectedRegion.value.city_name || '').trim()

    const districtName = String(selectedRegion.value.district_name || '').trim()

    return [cityName, districtName].filter(Boolean).join(' / ') || districtName || '请选择服务区县'
})

const regionProvinces = computed(() => regionTree.value || [])

const regionCities = computed(() => {
    return (
        regionTree.value.find((item: any) => item.province_code === tempRegion.value.province_code)
            ?.cities || []
    )
})

const regionDistricts = computed(() => {
    return (
        regionCities.value.find((item: any) => item.city_code === tempRegion.value.city_code)
            ?.districts || []
    )
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

    tempRegion.value = normalizeServiceRegion(normalizedRegion)

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
    try {
        const params: Record<string, any> & { id: number } = { id: staffId.value }

        if (presetDate.value) {
            params.date = presetDate.value
        }

        Object.assign(params, toServiceRegionParams(selectedRegion.value))

        const data = await getStaffDetail(params)

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
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '获取详情失败'

        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

const getRegionTree = async (force = false) => {
    if (!force && regionTree.value.length) {
        syncTempRegion(selectedRegion.value)
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

            syncTempRegion(selectedRegion.value)
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

            avg_score: Number(data?.avg_score || 5).toFixed(1),

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

const syncTempRegion = (value?: Record<string, any>) => {
    const region = normalizeServiceRegion(value || selectedRegion.value)

    tempRegion.value = region

    if (!tempRegion.value.province_code && regionTree.value.length) {
        handleProvinceSelect(regionTree.value[0])

        return
    }

    if (!tempRegion.value.city_code && regionCities.value.length) {
        handleCitySelect(regionCities.value[0])
    }
}

const openRegionPicker = () => {
    if (showRegionPopup.value) {
        return
    }

    syncTempRegion()

    showRegionPopup.value = true
}

const hideRegionPicker = () => {
    showRegionPopup.value = false
}

const closeRegionPicker = () => {
    hideRegionPicker()

    pendingDatePickerAfterRegion.value = false
}

const handleProvinceSelect = (province: any) => {
    tempRegion.value = normalizeServiceRegion({
        province_code: province?.province_code || '',

        province_name: province?.province_name || '',

        city_code: '',

        city_name: '',

        district_code: '',

        district_name: ''
    })

    const firstCity = (province?.cities || [])[0]

    if (firstCity) {
        handleCitySelect(firstCity)
    }
}

const handleCitySelect = (city: any) => {
    tempRegion.value = normalizeServiceRegion({
        province_code: city?.province_code || tempRegion.value.province_code,

        province_name: city?.province_name || tempRegion.value.province_name,

        city_code: city?.city_code || '',

        city_name: city?.city_name || '',

        district_code: '',

        district_name: ''
    })
}

const handleDistrictSelect = (district: any) => {
    tempRegion.value = normalizeServiceRegion({
        ...tempRegion.value,

        province_code: district?.province_code || tempRegion.value.province_code,

        province_name: district?.province_name || tempRegion.value.province_name,

        city_code: district?.city_code || tempRegion.value.city_code,

        city_name: district?.city_name || tempRegion.value.city_name,

        district_code: district?.district_code || '',

        district_name: district?.district_name || ''
    })
}

const resetRegionSelection = () => {
    tempRegion.value = normalizeServiceRegion({})
}

const confirmRegionPicker = async () => {
    if (!hasServiceRegion(tempRegion.value)) {
        uni.showToast({ title: '请选择到区县', icon: 'none' })

        return
    }

    selectedRegion.value = normalizeServiceRegion(tempRegion.value)

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

const syncDatePickerValue = (value = '') => {
    const targetDate = getEffectiveSelectableDate(value)

    const yearIndex = datePickerYears.value.indexOf(targetDate.getFullYear())

    const safeYearIndex = yearIndex >= 0 ? yearIndex : 0

    const months = getDatePickerMonthsByYear(datePickerYears.value[safeYearIndex])

    const monthIndex = Math.max(months.indexOf(targetDate.getMonth() + 1), 0)

    const days = getDatePickerDaysByYearMonth(
        datePickerYears.value[safeYearIndex],

        months[monthIndex]
    )

    const dayIndex = Math.max(days.indexOf(targetDate.getDate()), 0)

    datePickerValue.value = [safeYearIndex, monthIndex, dayIndex]
}

const openDatePicker = () => {
    if (!hasSelectedRegion.value) {
        openRegionPicker()

        return
    }

    syncDatePickerValue(presetDate.value)

    showDatePopup.value = true
}

const hideDatePicker = () => {
    showDatePopup.value = false
}

const closeDatePicker = () => {
    hideDatePicker()

    pendingDatePickerAfterRegion.value = false
}

const handleDatePickerChange = (event: any) => {
    datePickerValue.value = normalizeDatePickerValue(event.detail.value || [])
}

const confirmDatePicker = async () => {
    const year = datePickerYears.value[datePickerValue.value[0]]

    const month = String(datePickerMonths.value[datePickerValue.value[1]]).padStart(2, '0')

    const day = String(datePickerDays.value[datePickerValue.value[2]]).padStart(2, '0')

    const nextDate = `${year}-${month}-${day}`

    presetDate.value = nextDate

    hideDatePicker()

    resetAlternativeStaffState()

    await getDetail()

    pendingDatePickerAfterRegion.value = false
}

const buildStaffDetailQuery = (extra: Record<string, any> = {}) => {
    const params = [`id=${staffId.value}`]

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

    isShareEntry.value = options?.from_share === '1'

    if (options?.id) {
        staffId.value = Number(options.id)
    }

    selectedRegion.value = normalizeServiceRegion({
        ...loadServiceRegionSelection(),

        ...options
    })

    tempRegion.value = normalizeServiceRegion(selectedRegion.value)

    if (hasServiceRegion(selectedRegion.value)) {
        saveServiceRegionSelection(selectedRegion.value)
    }

    if (options?.date) {
        presetDate.value = normalizeSelectedDateText(options.date)
    }

    if (options?.package_id) {
        selectedPackageId.value = Number(options.package_id)
    }

    if (options?.waitlist_id) {
        waitlistId.value = Number(options.waitlist_id)
    }

    if (options?.open_date_picker === '1') {
        openDatePickerRequested.value = true
    }

    if (options?.open_booking_popup === '1') {
        openBookingPopupRequested.value = true
    }

    if (options?.tab && ['intro', 'works', 'reviews'].includes(options.tab)) {
        currentTab.value = options.tab
    }

    applyDetailRestoreSnapshot()
})

onShow(async () => {
    $theme.setScene('consumer')

    // 微信分享直达时隐藏原生“返回首页”按钮

    // #ifdef MP-WEIXIN

    if (isShareEntry.value) {
        const hideHomeButtonTask = uni.hideHomeButton() as unknown

        if (
            hideHomeButtonTask &&
            typeof (hideHomeButtonTask as Promise<unknown>).catch === 'function'
        ) {
            ;(hideHomeButtonTask as Promise<unknown>).catch((error: unknown) => {
                console.warn('隐藏首页按钮失败：', error)
            })
        }
    }

    // #endif

    applyPendingDetailReturnState()

    scheduleRestoreDetailScroll()

    void getRegionTree().catch(() => null)

    if (staffId.value) {
        await getDetail()

        if (openBookingPopupRequested.value || openDatePickerRequested.value) {
            const shouldOpenDateEditor = openDatePickerRequested.value

            openBookingPopupRequested.value = false

            openDatePickerRequested.value = false

            if (shouldOpenDateEditor) {
                setTimeout(() => handleInlineDateEdit(), 0)
            } else {
                setTimeout(() => handleBook(), 0)
            }
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

.loading-container {
    display: flex;

    align-items: center;

    justify-content: center;

    min-height: 100vh;

    background: var(--wm-color-bg-page, #ffffff);
}

.picker-container {
    background: #ffffff;

    width: 100vw;

    max-width: 100vw;

    margin: 0;

    border-radius: 28rpx 28rpx 0 0;

    box-shadow: 0 -12rpx 36rpx rgba(17, 17, 17, 0.1);

    max-height: 80vh;

    display: flex;

    flex-direction: column;

    overflow: hidden;

    .picker-header {
        display: flex;

        align-items: center;

        justify-content: space-between;

        padding: 20rpx;

        border-bottom: 1rpx solid #F8F7F2;
    }

    .picker-title {
        font-size: 30rpx;

        font-weight: 700;

        color: #111111;
    }

    .picker-action {
        min-width: 96rpx;

        font-size: 28rpx;

        color: #5F5A50;

        text-align: center;

        &:active {
            opacity: 0.72;
        }
    }

    .picker-action-primary {
        color: var(--color-primary);

        font-weight: 600;
    }
}

.date-picker-content {
    padding: 12rpx 20rpx 24rpx;
}

.date-picker-view {
    height: 420rpx;
}

.picker-item {
    display: flex;

    align-items: center;

    justify-content: center;

    height: 100%;

    font-size: 30rpx;

    color: #111111;
}

.picker-footer {
    display: flex;

    gap: 12rpx;

    padding: 16rpx 20rpx 20rpx;

    border-top: 1rpx solid #F8F7F2;
}

.picker-btn {
    flex: 1;

    height: 82rpx;

    border-radius: 18rpx;

    background: #f8f7f2;

    color: #5F5A50;

    font-size: 28rpx;

    font-weight: 500;

    display: flex;

    align-items: center;

    justify-content: center;

    &:active {
        opacity: 0.85;

        transform: scale(0.98);
    }
}

.picker-btn-primary {
    color: #ffffff;

    font-weight: 600;
}

.region-picker-content {
    display: grid;

    grid-template-columns: repeat(3, minmax(0, 1fr));

    gap: 12rpx;

    padding: 20rpx 20rpx 12rpx;
}

.region-picker-col {
    min-width: 0;

    border: 1rpx solid #F8F7F2;

    border-radius: 20rpx;

    overflow: hidden;

    background: #F8F7F2;
}

.region-picker-col__title {
    padding: 16rpx 20rpx;

    font-size: 24rpx;

    font-weight: 600;

    color: #5F5A50;

    border-bottom: 1rpx solid #F8F7F2;

    background: #ffffff;
}

.region-picker-scroll {
    height: 480rpx;
}

.region-picker-item {
    padding: 20rpx;

    font-size: 24rpx;

    color: #5f5a50;

    border-bottom: 1rpx solid rgba(231, 226, 214, 0.72);

    transition: all 0.2s ease;

    &:last-child {
        border-bottom: none;
    }

    &:active {
        opacity: 0.82;
    }

    &.active {
        font-weight: 600;

        color: var(--color-primary);

        background: var(--color-primary-light-9);
    }
}
</style>

<style lang="scss" scoped>
.staff-detail {
    background: transparent;
}

.staff-detail__content {
    display: flex;

    flex-direction: column;

    gap: 24rpx;

    padding: 11rpx 37rpx calc(var(--wm-safe-bottom-action, 150rpx) + 37rpx);
}

.hero-card {
    position: relative;

    overflow: hidden;

    border-radius: var(--wm-radius-card-lg, 20rpx);

    background: linear-gradient(135deg, #D8C28A 0%, #D8C28A 100%);

    box-shadow: var(--wm-shadow-hero, 0 18rpx 42rpx rgba(17, 17, 17, 0.12));
}

.hero-card__banner {
    display: block;
}

.hero-card__banner :deep(.banner-container),
.hero-card__banner :deep(.banner-swiper),
.hero-card__banner :deep(.media-container),
.hero-card__banner :deep(.banner-media),
.hero-card__banner :deep(.banner-video) {
    border-radius: var(--wm-radius-card-lg, 20rpx);
}

.info-card__inner {
    display: flex;

    flex-direction: column;

    gap: 22rpx;

    padding: 34rpx;

    backdrop-filter: blur(16rpx);

    -webkit-backdrop-filter: blur(16rpx);
}

.info-card__header {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 16rpx;
}

.info-card__identity {
    flex: 1;

    min-width: 0;

    display: flex;

    flex-direction: column;

    gap: 12rpx;
}

.info-card__name {
    font-size: 40rpx;

    line-height: 1.1;

    font-weight: 700;

    color: #111111;
}

.info-card__summary {
    font-size: 24rpx;

    line-height: 1.6;

    color: var(--wm-text-secondary, #56524a);
}

.info-card__favorite {
    flex-shrink: 0;

    display: inline-flex;

    align-items: center;

    gap: 6rpx;

    min-height: 68rpx;

    padding: 0 18rpx;

    border-radius: 999rpx;

    background: #FFFFFF;

    border: 1rpx solid rgba(11, 11, 11, 0.12);
    min-width: 88rpx;
}

.info-card__favorite-text {
    font-size: 22rpx;

    line-height: 1.2;

    font-weight: 600;

    color: var(--wm-text-tertiary, #8e887d);
}

.info-card__favorite-text--active {
    color: #0b0b0b;
}

.info-card__badge-list {
    display: flex;

    flex-wrap: wrap;

    gap: 12rpx;
}

.info-card__badge {
    padding: 8rpx 16rpx;

    border-radius: 999rpx;

    background: var(--wm-color-bg-soft, #f6f5f2);

    border: 1rpx solid rgba(11, 11, 11, 0.12);
}

.info-card__badge-text {
    font-size: 22rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #9f7a2e;
}

.info-card__metric-row {
    display: grid;

    grid-template-columns: repeat(3, minmax(0, 1fr));

    gap: 12rpx;
}

.info-card__metric {
    display: flex;

    flex-direction: column;

    gap: 6rpx;

    padding: 20rpx 16rpx;

    border-radius: 20rpx;

    background: linear-gradient(180deg, #FFFFFF 0%, #FFFFFF 100%);
}

.info-card__metric-value {
    font-size: 32rpx;

    line-height: 1.1;

    font-weight: 700;

    color: #111111;
}

.info-card__metric-label {
    font-size: 22rpx;

    line-height: 1.3;

    color: #9a9388;
}

.info-card__price-row {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 16rpx;

    padding-top: 4rpx;
}

.info-card__price-label {
    font-size: 24rpx;

    line-height: 1.3;

    color: var(--wm-text-secondary, #56524a);
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
    font-size: 42rpx;

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
    padding: 24rpx 24rpx 26rpx;
}

.booking-brief-card__head {
    display: flex;

    flex-direction: column;

    gap: 8rpx;
}

.booking-brief-card__eyebrow {
    font-size: 22rpx;

    font-weight: 600;

    letter-spacing: 0;

    color: #0b0b0b;
}

.booking-brief-card__title {
    display: block;

    font-size: 30rpx;

    font-weight: 700;

    color: #111111;
}

.booking-brief-card__grid {
    display: flex;

    gap: 12rpx;

    margin-top: 16rpx;
}

.booking-brief-card__item {
    flex: 1;

    min-width: 0;

    padding: 24rpx 22rpx;

    border-radius: var(--wm-radius-card, 16rpx);

    background: #ffffff;

    border: 1rpx solid var(--wm-color-border, #e2ded5);
}

.booking-brief-card__label {
    display: block;

    font-size: 22rpx;

    color: #9A9388;
}

.booking-brief-card__value {
    display: block;

    margin-top: 10rpx;

    font-size: 28rpx;

    font-weight: 600;

    line-height: 1.5;

    color: #111111;
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
    padding: 4rpx;

    border-radius: 37rpx;

    background: rgba(255, 255, 255, 0.84);

    border: 1rpx solid rgba(231, 226, 214, 0.96);

    backdrop-filter: blur(18rpx);

    -webkit-backdrop-filter: blur(18rpx);
}

.tabs-wrapper {
    display: flex;

    align-items: center;

    gap: 6rpx;
}

.tab-item {
    flex: 1;

    min-width: 0;

    height: 82rpx;

    padding: 0 22rpx;

    border-radius: 34rpx;

    display: flex;

    align-items: center;

    justify-content: center;
}

.tab-item--active {
    background: #0b0b0b;

    box-shadow: 0 10rpx 22rpx rgba(11, 11, 11, 0.18);
}

.tab-text {
    font-size: 24rpx;

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

    gap: 22rpx;
}

.detail-stream-shell {
    overflow: hidden;

    border-radius: 36rpx;

    box-shadow: 0 16rpx 34rpx rgba(17, 17, 17, 0.1);
}

.soft-card {
    display: flex;

    flex-direction: column;

    gap: 22rpx;

    padding: 30rpx 34rpx;

    border-radius: 45rpx;

    background: rgba(255, 255, 255, 0.84);

    border: 1rpx solid rgba(231, 226, 214, 0.96);

    backdrop-filter: blur(18rpx);

    -webkit-backdrop-filter: blur(18rpx);
}

.soft-card__title {
    font-size: 28rpx;

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

    gap: 12rpx;
}

.soft-tag {
    padding: 10rpx 18rpx;

    border-radius: 999rpx;

    background: #f8f7f2;

    border: 1rpx solid rgba(11, 11, 11, 0.14);
}

.soft-tag__text {
    font-size: 22rpx;

    line-height: 1.2;

    font-weight: 600;

    color: #9f7a2e;
}

.works-grid {
    display: grid;

    grid-template-columns: repeat(2, minmax(0, 1fr));

    gap: 16rpx;
}

.work-item {
    position: relative;

    overflow: hidden;

    border-radius: 37rpx;

    background: linear-gradient(135deg, #F7F0DF 0%, #D8D3C7 100%);

    box-shadow: 0 14rpx 30rpx rgba(17, 17, 17, 0.12);
}

.work-image {
    width: 100%;

    height: 224rpx;
}

.work-overlay {
    position: absolute;

    inset: auto 0 0 0;

    padding: 16rpx;

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

    gap: 12rpx;
}

.review-summary-card {
    display: flex;

    flex-direction: column;

    gap: 8rpx;

    padding: 30rpx 22rpx;

    border-radius: 37rpx;

    background: rgba(255, 255, 255, 0.84);

    border: 1rpx solid rgba(231, 226, 214, 0.96);
}

.review-summary-value {
    font-size: 32rpx;

    line-height: 1.1;

    font-weight: 700;

    color: #111111;
}

.review-summary-label {
    font-size: 22rpx;

    line-height: 1.3;

    color: #9a9388;
}

.review-filter-row {
    display: flex;

    flex-wrap: wrap;

    gap: 12rpx;
}

.review-filter-item {
    padding: 10rpx 18rpx;

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

    gap: 16rpx;
}

.review-card {
    padding: 30rpx 34rpx;

    border-radius: 45rpx;

    background: rgba(255, 255, 255, 0.84);

    border: 1rpx solid rgba(231, 226, 214, 0.96);
}

.review-card-header {
    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 16rpx;
}

.review-user {
    flex: 1;

    min-width: 0;

    display: flex;

    align-items: center;

    gap: 14rpx;
}

.review-user-avatar {
    width: 72rpx;

    height: 72rpx;

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
    font-size: 22rpx;

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

    margin-top: 18rpx;

    font-size: 25rpx;

    line-height: 1.75;

    color: #5F5A50;
}

.review-tag-list {
    display: flex;

    flex-wrap: wrap;

    gap: 12rpx;

    margin-top: 16rpx;
}

.review-tag {
    padding: 8rpx 14rpx;

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

    gap: 12rpx;

    margin-top: 18rpx;
}

.review-image {
    width: calc((100% - 24rpx) / 3);

    height: 184rpx;

    border-radius: 37rpx;

    background: #F8F7F2;
}

.review-reply-list {
    display: flex;

    flex-direction: column;

    gap: 12rpx;

    margin-top: 18rpx;
}

.review-reply-item {
    padding: 22rpx;

    border-radius: 34rpx;

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

    gap: 14rpx;
}

.cert-item {
    display: inline-flex;

    flex-direction: column;

    gap: 12rpx;

    width: 216rpx;
}

.cert-image {
    width: 216rpx;

    height: 144rpx;

    border-radius: 37rpx;

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

    border-radius: 40rpx;

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

    min-height: 220rpx;

    padding: 30rpx;

    border-radius: 45rpx;

    background: rgba(255, 255, 255, 0.84);

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
    min-height: 220rpx;

    border-radius: var(--wm-radius-card, 16rpx);

    background: rgba(255, 255, 255, 0.72);
}

.staff-detail__action-bar {
    display: flex;

    gap: 19rpx;

    width: 100%;
}

.action-button {
    position: relative;

    flex: 1;

    min-height: 90rpx;

    border-radius: var(--wm-radius-action, 999rpx);

    display: flex;

    align-items: center;

    justify-content: center;

    background: rgba(255, 255, 255, 0.9);

    border: 1rpx solid rgba(226, 222, 213, 0.96);

    box-shadow: var(--wm-shadow-soft, 0 8rpx 20rpx rgba(17, 17, 17, 0.05));

    overflow: hidden;
}

.action-button--primary {
    background: #0b0b0b;

    border-color: #0b0b0b;

    box-shadow: 0 12rpx 24rpx rgba(11, 11, 11, 0.18);
}

.action-button__text {
    font-size: 26rpx;

    line-height: 1.2;

    font-weight: 700;

    color: #111111;
}

.action-button__text--primary {
    color: #ffffff;
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
    .tab-text {
        font-size: 22rpx;
    }

    .action-button__text {
        font-size: 24rpx;
    }
}
</style>
