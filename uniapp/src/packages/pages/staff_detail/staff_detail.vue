<template>
    <page-meta :page-style="$theme.pageStyle">
        <navigation-bar
            title="服务人员详情"
            :front-color="$theme.navColor"
            :background-color="$theme.navBgColor"
        />
    </page-meta>

    <view class="staff-detail" :class="`style-${styleMode}`" v-if="staffInfo">
        <!-- 头图轮播 -->
        <view class="banner-section" :class="`banner-${styleMode}`">
            <staff-banner
                :banner-list="bannerList"
                :config="bannerConfig"
                :default-image="staffInfo.avatar || '/static/images/user/default_avatar.png'"
                @update:expanded="isExpanded = $event"
            />

            <!-- 沉浸视觉型：头图叠层信息 -->
            <view v-if="styleMode === 'immersive'" class="immersive-hero">
                <view class="hero-mask"></view>
                <view class="hero-content">
                    <image
                        class="hero-avatar"
                        :src="staffInfo.avatar || '/static/images/user/default_avatar.png'"
                        mode="aspectFill"
                    />
                    <view class="hero-info">
                        <view class="hero-name-row">
                            <text class="hero-name">{{ staffInfo.name }}</text>
                            <view class="hero-badges">
                                <text v-if="staffInfo.is_verified" class="hero-badge verified"
                                    >已认证</text
                                >
                                <text v-if="staffInfo.is_vip" class="hero-badge vip">VIP</text>
                                <text v-if="staffInfo.is_recommend" class="hero-badge recommend"
                                    >推荐</text
                                >
                            </view>
                        </view>
                        <view class="hero-meta-row">
                            <text class="hero-category">{{ staffInfo.category?.name }}</text>
                            <text v-if="staffInfo.experience_years" class="hero-experience">
                                {{ staffInfo.experience_years }}年经验
                            </text>
                        </view>
                        <view class="hero-stats">
                            <view class="hero-stat">
                                <tn-icon
                                    name="star-fill"
                                    size="28"
                                    :color="(($theme as any).accentColor || '#F59E0B') as string"
                                />
                                <text class="hero-stat-value">{{ staffInfo.rating }}</text>
                            </view>
                            <view class="hero-stat">
                                <text class="hero-stat-value">{{ staffInfo.order_count }}</text>
                                <text class="hero-stat-label">服务</text>
                            </view>
                            <view class="hero-stat">
                                <text class="hero-stat-value">{{ staffInfo.view_count || 0 }}</text>
                                <text class="hero-stat-label">浏览</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <!-- 人员信息卡片 -->
        <view
            class="info-card"
            :class="{ 'card-overlap': bannerConfig.banner_mode === 1 && !isExpanded }"
        >
            <view class="card-header">
                <image
                    class="staff-avatar"
                    :src="staffInfo.avatar || '/static/images/user/default_avatar.png'"
                    mode="aspectFill"
                />
                <view class="header-info">
                    <view class="name-row">
                        <text class="staff-name">{{ staffInfo.name }}</text>
                        <!-- 认证标识 -->
                        <view v-if="staffInfo.is_verified" class="verified-badge">
                            <tn-icon
                                name="check-circle-fill"
                                size="32"
                                :color="$theme.primaryColor"
                            />
                        </view>
                        <!-- VIP标识 -->
                        <view v-if="staffInfo.is_vip" class="vip-badge">
                            <tn-icon name="vip-fill" size="32" color="#FFD700" />
                        </view>
                        <!-- 推荐标识 -->
                        <view v-if="staffInfo.is_recommend" class="recommend-badge">
                            <text>推荐</text>
                        </view>
                    </view>

                    <view class="category-row">
                        <text class="category-text">{{ staffInfo.category?.name }}</text>
                        <text v-if="staffInfo.experience_years" class="experience-text">
                            {{ staffInfo.experience_years }}年经验
                        </text>
                    </view>
                </view>
            </view>

            <!-- 评分统计 -->
            <view class="stats-row">
                <view class="stat-item">
                    <view class="stat-value">
                        <tn-icon
                            name="star-fill"
                            size="32"
                            :color="(($theme as any).accentColor || '#F59E0B') as string"
                        />
                        <text
                            :style="{
                                color: (($theme as any).accentColor || '#F59E0B') as string
                            }"
                        >
                            {{ staffInfo.rating }}
                        </text>
                    </view>
                    <text class="stat-label">评分</text>
                </view>
                <view class="stat-divider"></view>
                <view class="stat-item">
                    <text class="stat-value">{{ staffInfo.order_count }}</text>
                    <text class="stat-label">服务次数</text>
                </view>
                <view class="stat-divider"></view>
                <view class="stat-item">
                    <text class="stat-value">{{ staffInfo.view_count || 0 }}</text>
                    <text class="stat-label">浏览量</text>
                </view>
            </view>

            <!-- 价格和收藏 -->
            <view class="price-row">
                <view class="price-wrapper">
                    <text class="price-label">服务价格</text>
                    <view class="price-amount">
                        <template
                            v-if="
                                staffInfo.has_price !== false &&
                                staffInfo.price !== null &&
                                staffInfo.price !== undefined
                            "
                        >
                            <text class="price-symbol">¥</text>
                            <text class="price-value">{{
                                staffInfo.price_text || staffInfo.price
                            }}</text>
                            <text class="price-unit">/次起</text>
                        </template>
                        <text v-else class="price-negotiable">面议</text>
                    </view>
                </view>
                <view class="favorite-btn" @click="handleToggleFavorite">
                    <tn-icon
                        :name="staffInfo.is_favorite ? 'star-fill' : 'star'"
                        size="48"
                        :color="
                            staffInfo.is_favorite
                                ? ((($theme as any).secondaryColor || $theme.primaryColor) as string)
                                : '#CCCCCC'
                        "
                    />
                </view>
            </view>
        </view>

        <!-- 沉浸视觉型：风格标签带 -->
        <view
            v-if="styleMode === 'immersive' && staffInfo.tags && staffInfo.tags.length"
            class="immersive-tags"
        >
            <view class="immersive-tags-title">擅长风格</view>
            <view class="immersive-tags-list">
                <view v-for="(tag, index) in staffInfo.tags.slice(0, 4)" :key="index" class="tag">
                    <text class="tag-text">{{ tag }}</text>
                </view>
            </view>
        </view>

        <!-- 高转化营销型：核心指标 -->
        <view v-if="styleMode === 'conversion'" class="conversion-highlights">
            <view class="highlight-card">
                <text class="highlight-label">口碑评分</text>
                <view class="highlight-value">
                    <tn-icon name="star-fill" size="28" color="#f97316" />
                    <text class="highlight-number">{{ staffInfo.rating }}</text>
                </view>
            </view>
            <view class="highlight-card">
                <text class="highlight-label">服务次数</text>
                <text class="highlight-number">{{ staffInfo.order_count }}</text>
            </view>
            <view class="highlight-card">
                <text class="highlight-label">浏览量</text>
                <text class="highlight-number">{{ staffInfo.view_count || 0 }}</text>
            </view>
        </view>

        <!-- 高转化营销型：风格标签 -->
        <view
            v-if="styleMode === 'conversion' && staffInfo.tags && staffInfo.tags.length"
            class="conversion-tags"
        >
            <text class="conversion-tags-title">擅长风格</text>
            <view class="conversion-tags-list">
                <view
                    v-for="(tag, index) in staffInfo.tags.slice(0, 4)"
                    :key="index"
                    class="tag"
                >
                    <text class="tag-text">{{ tag }}</text>
                </view>
            </view>
        </view>

        <!-- 标签页切换 -->
        <view class="tabs-section">
            <view class="tabs-wrapper">
                <view
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="tab-item"
                    :class="{ active: currentTab === tab.key }"
                    @click="currentTab = tab.key"
                >
                    <text
                        class="tab-text"
                        :style="currentTab === tab.key ? { color: $theme.primaryColor } : {}"
                    >
                        {{ tab.label }}
                    </text>
                    <view
                        v-if="currentTab === tab.key"
                        class="tab-indicator"
                        :style="{ background: $theme.primaryColor }"
                    ></view>
                </view>
            </view>
        </view>

        <!-- 标签页内容 -->
        <view class="tab-content">
            <!-- 简介标签页 -->
            <view v-show="currentTab === 'intro'" class="content-section">
                <!-- 擅长风格 -->
                <view v-if="staffInfo.tags && staffInfo.tags.length" class="content-block">
                    <view class="block-title">擅长风格</view>
                    <view class="tags-wrapper">
                        <view v-for="(tag, index) in staffInfo.tags" :key="index" class="tag-item">
                            <text class="tag-text">{{ tag }}</text>
                        </view>
                    </view>
                </view>

                <!-- 个人简介 -->
                <view class="content-block">
                    <view class="block-title">个人简介</view>
                    <text class="block-content">{{ staffInfo.profile || '暂无简介' }}</text>
                </view>

                <!-- 服务说明 -->
                <view v-if="staffInfo.service_desc" class="content-block">
                    <view class="block-title">服务说明</view>
                    <text class="block-content">{{ staffInfo.service_desc }}</text>
                </view>

            </view>

            <!-- 作品标签页 -->
            <view v-show="currentTab === 'works'" class="content-section">
                <!-- 加载状态 -->
                <view v-if="worksLoading" class="loading-state">
                    <tn-loading mode="circle" />
                </view>

                <!-- 作品列表 -->
                <view v-else-if="worksList.length" class="works-grid">
                    <view
                        v-for="work in worksList"
                        :key="work.id"
                        class="work-item"
                        @click="goWorkDetail(work)"
                    >
                        <image
                            :src="work.cover || work.images?.[0]"
                            mode="aspectFill"
                            class="work-image"
                            lazy-load
                        />
                        <view class="work-overlay">
                            <text class="work-title">{{ work.title }}</text>
                        </view>
                    </view>
                </view>

                <!-- 空状态 -->
                <view v-else class="empty-state">
                    <tn-icon name="image" size="120" color="#CCCCCC" />
                    <text class="empty-text">暂无作品</text>
                </view>
            </view>

            <!-- 评价标签页 -->
            <view v-show="currentTab === 'reviews'" class="content-section">
                <!-- 资质证书 -->
                <view
                    v-if="staffInfo.certificates && staffInfo.certificates.length"
                    class="content-block"
                >
                    <view class="block-title">资质证书</view>
                    <scroll-view scroll-x class="certs-scroll">
                        <view class="certs-wrapper">
                            <view
                                v-for="cert in staffInfo.certificates"
                                :key="cert.id"
                                class="cert-item"
                                @click="previewCert(cert.image)"
                            >
                                <image :src="cert.image" mode="aspectFill" class="cert-image" />
                                <text class="cert-name">{{ cert.name }}</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <view class="review-summary">
                    <view class="review-summary-card">
                        <text class="review-summary-value">{{ reviewStats.avg_score || '5.0' }}</text>
                        <text class="review-summary-label">综合评分</text>
                    </view>
                    <view class="review-summary-card">
                        <text class="review-summary-value">{{ reviewStats.total_count || 0 }}</text>
                        <text class="review-summary-label">全部评价</text>
                    </view>
                    <view class="review-summary-card">
                        <text class="review-summary-value">{{ reviewStats.good_rate || 0 }}%</text>
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

                <!-- 加载状态 -->
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
                                        {{ review.create_time_text || formatReviewTime(review.create_time) }}
                                    </text>
                                </view>
                            </view>
                            <view class="review-score">
                                <tn-icon
                                    v-for="star in 5"
                                    :key="`${review.id}-${star}`"
                                    :name="star <= Number(review.score || 0) ? 'star-fill' : 'star'"
                                    size="22"
                                    :color="star <= Number(review.score || 0) ? '#F59E0B' : '#D1D5DB'"
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
                                    {{ Number(reply.reply_type) === 1 ? '用户追评' : '商家回复' }}
                                </text>
                                <text class="review-reply-content">{{ reply.content }}</text>
                            </view>
                        </view>
                    </view>

                    <view v-if="reviewsHasMore" class="review-load-more">
                        <text
                            v-if="reviewsLoading"
                            class="review-load-more-text"
                        >
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

                <!-- 空状态 -->
                <view v-else class="empty-state">
                    <tn-icon name="chat" size="120" color="#CCCCCC" />
                    <text class="empty-text">暂无评价</text>
                </view>
            </view>
        </view>

        <!-- 底部占位 -->
        <view class="bottom-placeholder"></view>

        <!-- 底部操作栏 -->
        <view class="bottom-bar">
            <view class="action-btns">
                <view class="action-item" @click="handleContact">
                    <tn-icon name="chat" size="48" color="#666666" />
                    <text class="action-text">咨询</text>
                </view>
                <view class="action-item" @click="handleToggleFavorite">
                    <tn-icon
                        :name="staffInfo.is_favorite ? 'star-fill' : 'star'"
                        size="48"
                        :color="
                            staffInfo.is_favorite
                                ? ((($theme as any).secondaryColor || $theme.primaryColor) as string)
                                : '#666666'
                        "
                    />
                    <text class="action-text">{{ staffInfo.is_favorite ? '已收藏' : '收藏' }}</text>
                </view>
                <!-- #ifdef MP-WEIXIN -->
                <view class="action-item share-action-item">
                    <tn-icon name="share" size="48" color="#666666" class="share-action-icon" />
                    <text class="action-text">分享</text>
                    <button class="share-action-trigger" open-type="share" hover-class="none"></button>
                </view>
                <!-- #endif -->
            </view>
            <view
                class="book-btn"
                :style="
                    styleMode === 'conversion'
                        ? { background: 'linear-gradient(135deg, #f97316 0%, #fb923c 100%)' }
                        : styleMode === 'immersive'
                        ? { background: 'linear-gradient(135deg, #6d5dfc 0%, #7c3aed 100%)' }
                        : {
                              background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                          }
                "
                @click="handleBook"
            >
                <tn-icon name="calendar" size="32" color="#FFFFFF" />
                <text class="book-text">立即预约</text>
            </view>
        </view>

        <u-popup
            v-model="showBookingPopup"
            mode="bottom"
            :mask="true"
            :mask-close-able="true"
            :safe-area-inset-bottom="true"
            :border-radius="24"
        >
            <view class="booking-popup">
                <view class="booking-popup-handle"></view>
                <scroll-view scroll-y class="booking-popup-scroll">
                    <view class="booking-popup-body">
                        <view class="booking-popup-fields">
                            <view
                                class="booking-popup-field"
                                :class="{ 'booking-popup-field--empty': !hasSelectedRegion }"
                                @click="handleBookingRegionEdit"
                            >
                                <view class="booking-popup-field__label-row">
                                    <tn-icon
                                        name="location"
                                        size="22"
                                        :color="$theme.primaryColor"
                                    />
                                    <text class="booking-popup-field__label">地区</text>
                                </view>
                                <view class="booking-popup-field__content">
                                    <text
                                        class="booking-popup-field__value"
                                        :class="{
                                            'booking-popup-field__value--empty': !hasSelectedRegion
                                        }"
                                    >
                                        {{ hasSelectedRegion ? selectedRegionText : '请选择区县' }}
                                    </text>
                                    <tn-icon
                                        class="booking-popup-field__arrow"
                                        name="right"
                                        size="22"
                                        :color="hasSelectedRegion ? $theme.primaryColor : '#98A2B3'"
                                    />
                                </view>
                            </view>

                            <view
                                class="booking-popup-field"
                                :class="{ 'booking-popup-field--empty': !presetDate }"
                                @click="handleBookingDateEdit"
                            >
                                <view class="booking-popup-field__label-row">
                                    <tn-icon
                                        name="calendar"
                                        size="22"
                                        :color="$theme.primaryColor"
                                    />
                                    <text class="booking-popup-field__label">日期</text>
                                </view>
                                <view class="booking-popup-field__content">
                                    <text
                                        class="booking-popup-field__value"
                                        :class="{ 'booking-popup-field__value--empty': !presetDate }"
                                    >
                                        {{ presetDate || '请选择日期' }}
                                    </text>
                                    <tn-icon
                                        class="booking-popup-field__arrow"
                                        name="right"
                                        size="22"
                                        :color="presetDate ? $theme.primaryColor : '#98A2B3'"
                                    />
                                </view>
                            </view>
                        </view>

                        <view
                            v-if="presetDate && staffInfo.schedule_available === false"
                            class="booking-popup-status booking-popup-status--warning"
                        >
                            <tn-icon name="warning-fill" size="24" color="#F97316" />
                            <text>
                                {{
                                    staffInfo.schedule_message ||
                                    '所选日期当前不可预约，请重新选择预约日期'
                                }}
                            </text>
                        </view>

                        <view class="booking-popup-section">
                            <view class="booking-popup-section__header">
                                <text class="booking-popup-section__title">套餐</text>
                                <text class="booking-popup-section__hint">单选</text>
                            </view>
                            <view class="booking-popup-package-list">
                                <view
                                    v-for="pkg in displayPackages"
                                    :key="`popup-${getPackageId(pkg)}`"
                                    class="booking-popup-package"
                                    :class="{
                                        'booking-popup-package--active':
                                            selectedPackageId === getPackageId(pkg)
                                    }"
                                    :style="
                                        selectedPackageId === getPackageId(pkg)
                                            ? {
                                                  borderColor: $theme.primaryColor
                                              }
                                            : {}
                                    "
                                    @click="handleBookingPackageSelect(pkg)"
                                >
                                    <view class="booking-popup-package__main">
                                        <view class="booking-popup-package__title-group">
                                            <text class="booking-popup-package__title">
                                                {{ getPackageName(pkg) }}
                                            </text>
                                            <text
                                                v-if="isRecommendedPackage(pkg)"
                                                class="booking-popup-package__tag booking-popup-package__tag--recommend"
                                            >
                                                推荐
                                            </text>
                                        </view>
                                        <text
                                            v-if="getPackageDescription(pkg)"
                                            class="booking-popup-package__desc"
                                        >
                                            {{ getPackageDescription(pkg) }}
                                        </text>
                                    </view>
                                    <view class="booking-popup-package__side">
                                        <view class="booking-popup-package__price">
                                            <text
                                                v-if="getPackageOriginalPrice(pkg) !== null"
                                                class="booking-popup-package__price-original"
                                            >
                                                ¥{{ formatPrice(getPackageOriginalPrice(pkg)) }}
                                            </text>
                                            <text class="booking-popup-package__price-current">
                                                ¥{{ formatPrice(getPackagePrice(pkg)) }}
                                            </text>
                                        </view>
                                        <view
                                            class="booking-popup-package__radio"
                                            :style="
                                                selectedPackageId === getPackageId(pkg)
                                                    ? {
                                                          backgroundColor: $theme.primaryColor,
                                                          borderColor: $theme.primaryColor
                                                      }
                                                    : {}
                                            "
                                        >
                                            <tn-icon
                                                v-if="selectedPackageId === getPackageId(pkg)"
                                                name="check"
                                                size="16"
                                                color="#FFFFFF"
                                            >
                                            </tn-icon>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view class="booking-popup-section">
                            <view class="booking-popup-section__header">
                                <text class="booking-popup-section__title">附加服务</text>
                                <text class="booking-popup-section__hint">
                                    {{
                                        bookingAddons.length
                                            ? selectedAddonIds.length
                                                ? `多选 · 已选${selectedAddonIds.length}项`
                                                : '多选'
                                            : '当前套餐暂无附加服务'
                                    }}
                                </text>
                            </view>

                            <view v-if="addonLoading" class="booking-popup-loading">
                                <tn-loading mode="circle" />
                                <text class="booking-popup-loading__text">正在加载附加服务...</text>
                            </view>

                            <view v-else-if="bookingAddons.length" class="booking-addon-list">
                                <view
                                    v-for="addon in bookingAddons"
                                    :key="addon.id"
                                    class="booking-addon-card"
                                    :class="{
                                        'booking-addon-card--active': isBookingAddonSelected(addon.id)
                                    }"
                                    :style="
                                        isBookingAddonSelected(addon.id)
                                            ? {
                                                  borderColor: $theme.ctaColor
                                              }
                                            : {}
                                    "
                                    @click="toggleBookingAddon(addon)"
                                >
                                    <view class="booking-addon-card__main">
                                        <text class="booking-addon-card__title">{{ addon.name }}</text>
                                        <text
                                            v-if="addon.description"
                                            class="booking-addon-card__desc"
                                        >
                                            {{ addon.description }}
                                        </text>
                                    </view>
                                    <view class="booking-addon-card__side">
                                        <view class="booking-addon-card__price">
                                            <text class="booking-addon-card__price-current">
                                                +¥{{ formatPrice(addon.price) }}
                                            </text>
                                            <text
                                                v-if="
                                                    Number(addon.original_price || 0) >
                                                    Number(addon.price || 0)
                                                "
                                                class="booking-addon-card__price-original"
                                            >
                                                ¥{{ formatPrice(addon.original_price) }}
                                            </text>
                                        </view>
                                        <view
                                            class="booking-addon-card__check"
                                            :style="
                                                isBookingAddonSelected(addon.id)
                                                    ? {
                                                          backgroundColor: $theme.ctaColor,
                                                          borderColor: $theme.ctaColor
                                                      }
                                                    : {}
                                            "
                                        >
                                            <tn-icon
                                                v-if="isBookingAddonSelected(addon.id)"
                                                name="check"
                                                size="18"
                                                color="#FFFFFF"
                                            />
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view v-else class="booking-popup-empty">
                                <tn-icon name="gift" size="48" color="#cbd5e1" />
                                <text class="booking-popup-empty__text">当前套餐暂无附加服务</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>

                <view class="booking-popup-footer">
                    <view class="booking-popup-footer__price">
                        <text class="booking-popup-footer__label">合计</text>
                        <view class="booking-popup-footer__amount">
                            <text class="booking-popup-footer__symbol">¥</text>
                            <text class="booking-popup-footer__value">
                                {{ formatPrice(bookingTotalAmount) }}
                            </text>
                        </view>
                        <text class="booking-popup-footer__detail">
                            套餐 ¥{{ formatPrice(bookingServiceAmount) }}
                            <template v-if="bookingPreview.addon_amount > 0">
                                ，附加服务 ¥{{ formatPrice(bookingPreview.addon_amount) }}
                            </template>
                        </text>
                    </view>
                    <view
                        class="booking-popup-footer__action"
                        :class="{ 'booking-popup-footer__action--disabled': !bookingCanConfirm }"
                        :style="
                            bookingCanConfirm
                                ? {
                                      background: `linear-gradient(135deg, ${$theme.primaryColor} 0%, ${$theme.primaryColor} 100%)`
                                  }
                                : {}
                        "
                        @click="handleBookingConfirm"
                    >
                        <text>
                            {{ bookingLoading ? '价格计算中...' : '下一步填写信息' }}
                        </text>
                    </view>
                </view>
            </view>
        </u-popup>

        <u-popup
            v-model="showRegionPopup"
            mode="bottom"
            :mask="true"
            :mask-close-able="true"
            :safe-area-inset-bottom="true"
            :border-radius="24"
        >
            <view class="picker-container region-picker-container">
                <view class="picker-header">
                    <text class="picker-action" @click="closeRegionPicker">取消</text>
                    <text class="picker-title">选择服务地区</text>
                    <text class="picker-action picker-action-primary" @click="confirmRegionPicker">
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
                                :class="{ active: tempRegion.province_code === province.province_code }"
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
                                :class="{ active: tempRegion.district_code === district.district_code }"
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
        </u-popup>

        <u-popup
            v-model="showDatePopup"
            mode="bottom"
            :mask="true"
            :mask-close-able="true"
            :safe-area-inset-bottom="true"
            :border-radius="24"
        >
            <view class="picker-container">
                <view class="picker-header">
                    <text class="picker-action" @click="closeDatePicker">取消</text>
                    <text class="picker-title">选择预约日期</text>
                    <text class="picker-action picker-action-primary" @click="confirmDatePicker">
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
        </u-popup>
    </view>

    <!-- 加载状态 -->
    <view v-else class="loading-container">
        <tn-loading mode="circle" />
    </view>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { onLoad, onShow, onShareAppMessage, onShareTimeline } from '@dcloudio/uni-app'
import { previewOrder } from '@/api/order'
import { getStaffAddons, getStaffDetail, toggleStaffFavorite, getStaffWorks } from '@/api/staff'
import { getStaffReviews, getStaffReviewStats } from '@/api/review'
import { getServiceRegionTree } from '@/api/service'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import StaffBanner from '@/packages/components/staff-banner/staff-banner.vue'
import {
    buildServiceRegionQuery,
    formatServiceRegionText,
    hasServiceRegion,
    loadServiceRegionSelection,
    normalizeServiceRegion,
    saveServiceRegionSelection,
    toServiceRegionParams
} from '@/utils/service-region'

const staffId = ref<number>(0)
const staffInfo = ref<any>(null)
const currentTab = ref('intro')
const isExpanded = ref(false) // 轮播图展开状态
const presetDate = ref('') // 预设日期
const showDatePopup = ref(false)
const showRegionPopup = ref(false)
const datePickerValue = ref([0, 0, 0])
const openDatePickerRequested = ref(false)
const openBookingPopupRequested = ref(false)
const pendingBookingDateEdit = ref(false)
const selectedPackageId = ref<number>(0)
const selectedAddonIds = ref<number[]>([])
const showBookingPopup = ref(false)
const bookingAddons = ref<any[]>([])
const addonLoading = ref(false)
const bookingLoading = ref(false)
const bookingPreview = ref({
    items: [],
    service_amount: 0,
    addon_amount: 0,
    total_amount: 0,
    pay_amount: 0,
    deposit_amount: 0,
    balance_amount: 0
})
const regionTree = ref<any[]>([])
const selectedRegion = ref(normalizeServiceRegion(loadServiceRegionSelection()))
const tempRegion = ref(normalizeServiceRegion(selectedRegion.value))
const appStore = useAppStore()
const userStore = useUserStore()

type StaffDetailStyleMode = 'classic' | 'immersive' | 'conversion'
type BookingPickerSource = '' | 'booking'
const pickerSource = ref<BookingPickerSource>('')

const normalizeStyleMode = (styleMode: string): StaffDetailStyleMode => {
    const validStyleModes: StaffDetailStyleMode[] = ['classic', 'immersive', 'conversion']
    return validStyleModes.includes(styleMode as StaffDetailStyleMode)
        ? (styleMode as StaffDetailStyleMode)
        : 'classic'
}

const styleMode = computed<StaffDetailStyleMode>(() => {
    const detailStyle = String(staffInfo.value?.staff_detail_style || '')
    if (detailStyle) {
        return normalizeStyleMode(detailStyle)
    }

    const configStyle = String(appStore.config?.feature_switch?.staff_detail_style || 'classic')
    return normalizeStyleMode(configStyle)
})

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
    return formatServiceRegionText(selectedRegion.value)
})
const regionProvinces = computed(() => regionTree.value || [])
const regionCities = computed(() => {
    return (
        regionTree.value.find((item: any) => item.province_code === tempRegion.value.province_code)?.cities || []
    )
})
const regionDistricts = computed(() => {
    return (
        regionCities.value.find((item: any) => item.city_code === tempRegion.value.city_code)?.districts || []
    )
})

const getPackageId = (pkg: any) => Number(pkg?.package_id || pkg?.id || 0)
const getPackageName = (pkg: any) => pkg?.name || pkg?.package?.name || '未命名套餐'
const getPackageDescription = (pkg: any) =>
    pkg?.description || pkg?.package?.description || ''
const isRecommendedPackage = (pkg: any) =>
    Number(pkg?.is_recommend ?? pkg?.package?.is_recommend ?? 0) === 1
const getPackagePrice = (pkg: any) => {
    if (pkg?.price !== null && pkg?.price !== undefined && pkg?.price !== '') {
        return Number(pkg.price)
    }
    return Number(pkg?.package?.price || 0)
}
const getPackageOriginalPrice = (pkg: any) => {
    if (
        pkg?.original_price !== null &&
        pkg?.original_price !== undefined &&
        pkg?.original_price !== ''
    ) {
        return Number(pkg.original_price)
    }
    if (
        pkg?.package?.original_price !== null &&
        pkg?.package?.original_price !== undefined &&
        pkg?.package?.original_price !== ''
    ) {
        return Number(pkg.package.original_price)
    }
    return null
}
const formatPrice = (value: number | string | null | undefined) =>
    Number(value || 0).toFixed(2)
const parseAddonIds = (value: unknown): number[] => {
    if (Array.isArray(value)) {
        return value
            .map((item) => Number(item))
            .filter((item, index, list) => item > 0 && list.indexOf(item) === index)
    }

    if (typeof value !== 'string' || !value.trim()) {
        return []
    }

    return value
        .split(',')
        .map((item) => Number(item.trim()))
        .filter((item, index, list) => item > 0 && list.indexOf(item) === index)
}
const serializeAddonIds = (addonIds: number[]) => {
    return addonIds
        .map((item) => Number(item))
        .filter((item, index, list) => item > 0 && list.indexOf(item) === index)
        .join(',')
}

const displayPackages = computed(() => {
    const packages = Array.isArray(staffInfo.value?.packages) ? staffInfo.value.packages : []
    return packages
        .map((pkg: any, index: number) => ({ pkg, index }))
        .sort((left: { pkg: any; index: number }, right: { pkg: any; index: number }) => {
            const recommendDiff =
                Number(isRecommendedPackage(right.pkg)) - Number(isRecommendedPackage(left.pkg))
            if (recommendDiff !== 0) {
                return recommendDiff
            }
            return left.index - right.index
        })
        .map((item: { pkg: any; index: number }) => item.pkg)
})

const selectedPackage = computed(() => {
    const packages = Array.isArray(staffInfo.value?.packages) ? staffInfo.value.packages : []
    return packages.find((pkg: any) => getPackageId(pkg) === selectedPackageId.value) || null
})
const bookingCanConfirm = computed(
    () =>
        Boolean(selectedPackage.value) &&
        hasSelectedRegion.value &&
        Boolean(presetDate.value) &&
        staffInfo.value?.schedule_available !== false &&
        Array.isArray(bookingPreview.value.items) &&
        bookingPreview.value.items.length > 0 &&
        !bookingLoading.value &&
        !addonLoading.value &&
        Number(bookingPreview.value.pay_amount || 0) >= 0
)
const bookingServiceAmount = computed(() => {
    const amount = Number(bookingPreview.value.service_amount ?? -1)
    if (amount >= 0) {
        return amount
    }
    return getPackagePrice(selectedPackage.value)
})
const bookingTotalAmount = computed(() => {
    const payAmount = Number(bookingPreview.value.pay_amount ?? -1)
    if (payAmount >= 0) {
        return payAmount
    }
    return bookingServiceAmount.value
})

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

const resetBookingPreview = () => {
    bookingPreview.value = {
        items: [],
        service_amount: 0,
        addon_amount: 0,
        total_amount: 0,
        pay_amount: 0,
        deposit_amount: 0,
        balance_amount: 0
    }
}

const buildBookingSelectionParams = () => {
    const params: Record<string, any> = {
        staff_id: staffId.value,
        package_id: selectedPackageId.value,
        date: presetDate.value,
        ...toServiceRegionParams(selectedRegion.value)
    }

    if (selectedAddonIds.value.length) {
        params.addon_ids = [...selectedAddonIds.value]
    }

    return params
}

const fetchBookingAddons = async () => {
    if (!staffId.value || !selectedPackageId.value) {
        bookingAddons.value = []
        selectedAddonIds.value = []
        return
    }

    addonLoading.value = true
    try {
        const result = await getStaffAddons({
            staff_id: staffId.value,
            package_id: selectedPackageId.value
        })
        bookingAddons.value = Array.isArray(result) ? result : []
        const validAddonIds = new Set(bookingAddons.value.map((item: any) => Number(item.id)))
        const filteredAddonIds = selectedAddonIds.value.filter((id) => validAddonIds.has(Number(id)))

        if (filteredAddonIds.length !== selectedAddonIds.value.length) {
            uni.showToast({ title: '已移除不属于当前套餐的附加服务', icon: 'none' })
        }

        selectedAddonIds.value = filteredAddonIds
    } catch (error: any) {
        bookingAddons.value = []
        selectedAddonIds.value = []
        const errorMsg = typeof error === 'string' ? error : error?.msg || error?.message
        if (errorMsg) {
            uni.showToast({ title: errorMsg, icon: 'none' })
        }
    } finally {
        addonLoading.value = false
    }
}

const fetchBookingPreview = async () => {
    if (
        !staffId.value ||
        !selectedPackageId.value ||
        !presetDate.value ||
        !hasSelectedRegion.value
    ) {
        resetBookingPreview()
        return
    }

    bookingLoading.value = true
    try {
        const result = await previewOrder(buildBookingSelectionParams())
        bookingPreview.value = {
            items: result?.items || [],
            service_amount: result?.service_amount || 0,
            addon_amount: result?.addon_amount || 0,
            total_amount: result?.total_amount || 0,
            pay_amount: result?.pay_amount || 0,
            deposit_amount: result?.deposit_amount || 0,
            balance_amount: result?.balance_amount || 0
        }
    } catch (error: any) {
        resetBookingPreview()
        const errorMsg = typeof error === 'string' ? error : error?.msg || error?.message || '价格预览失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    } finally {
        bookingLoading.value = false
    }
}

const openBookingPopupPanel = async () => {
    if (!selectedPackage.value) {
        const hasPackages =
            Array.isArray(staffInfo.value?.packages) && staffInfo.value.packages.length > 0
        uni.showToast({
            title: hasPackages ? '请选择套餐' : '暂无可预约套餐',
            icon: 'none'
        })
        return
    }

    showBookingPopup.value = true
    await fetchBookingAddons()
    await fetchBookingPreview()
}

const openBookingPopupWithGuard = async (packageId = 0) => {
    if (packageId > 0) {
        selectedPackageId.value = packageId
    } else {
        syncSelectedPackage()
    }

    await openBookingPopupPanel()
}

const isBookingAddonSelected = (addonId: number) =>
    selectedAddonIds.value.includes(Number(addonId))

const handleBookingPackageSelect = async (pkg: any) => {
    const packageId = getPackageId(pkg)
    if (!packageId || packageId === selectedPackageId.value) {
        return
    }

    selectedPackageId.value = packageId
    await fetchBookingAddons()
    await fetchBookingPreview()
}

const toggleBookingAddon = async (addon: any) => {
    if (bookingLoading.value || addonLoading.value) {
        return
    }

    const addonId = Number(addon?.id || 0)
    if (!addonId) {
        return
    }

    if (isBookingAddonSelected(addonId)) {
        selectedAddonIds.value = selectedAddonIds.value.filter((id) => id !== addonId)
    } else {
        selectedAddonIds.value = [...selectedAddonIds.value, addonId]
    }

    await fetchBookingPreview()
}

const handleBookingRegionEdit = () => {
    pickerSource.value = 'booking'
    pendingBookingDateEdit.value = false
    openRegionPicker()
}

const handleBookingDateEdit = () => {
    pickerSource.value = 'booking'
    if (!hasSelectedRegion.value) {
        pendingBookingDateEdit.value = true
        uni.showToast({ title: '请先选择服务地区', icon: 'none' })
        openRegionPicker()
        return
    }

    pendingBookingDateEdit.value = false
    openDatePicker()
}

const handleBookingConfirm = () => {
    if (!bookingCanConfirm.value) {
        return
    }

    const queryParts = [
        `staff_id=${staffId.value}`,
        `package_id=${selectedPackageId.value}`,
        `date=${encodeURIComponent(presetDate.value)}`
    ]
    const regionQuery = buildServiceRegionQuery(selectedRegion.value)
    if (regionQuery) {
        queryParts.push(regionQuery)
    }
    const addonQuery = serializeAddonIds(selectedAddonIds.value)
    if (addonQuery) {
        queryParts.push(`addon_ids=${encodeURIComponent(addonQuery)}`)
    }
    uni.navigateTo({
        url: `/packages/pages/order_confirm/order_confirm?${queryParts.join('&')}`
    })
}

// 标签页配置
const tabs = [
    { key: 'intro', label: '简介' },
    { key: 'works', label: '作品' },
    { key: 'reviews', label: '评价' }
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

watch(showBookingPopup, (visible) => {
    if (!visible) {
        pendingBookingDateEdit.value = false
        pickerSource.value = ''
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
        staffInfo.value = data
        syncSelectedPackage()

        // 加载轮播图配置
        if (data.banner_mode !== undefined) {
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

        // 加载轮播图列表
        if (data.banners && Array.isArray(data.banners)) {
            bannerList.value = data.banners
        }

        if (currentTab.value === 'reviews') {
            if (!reviewStatsLoaded.value) {
                loadReviewStats()
            }
            if (!reviewsInitialized.value) {
                loadReviews(true)
            }
        }
    } catch (e: any) {
        const errorMsg = typeof e === 'string' ? e : e.msg || e.message || '获取详情失败'
        uni.showToast({ title: errorMsg, icon: 'none' })
    }
}

const getRegionTree = async () => {
    try {
        const data = await getServiceRegionTree()
        regionTree.value = Array.isArray(data) ? data : []
        syncTempRegion(selectedRegion.value)
    } catch (error) {
        console.error(error)
    }
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
    pendingBookingDateEdit.value = false
    pickerSource.value = ''
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

    const isFromBooking = pickerSource.value === 'booking'
    selectedRegion.value = normalizeServiceRegion(tempRegion.value)
    saveServiceRegionSelection(selectedRegion.value)
    hideRegionPicker()

    await getDetail()

    if (isFromBooking) {
        if (pendingBookingDateEdit.value) {
            pendingBookingDateEdit.value = false
            pickerSource.value = 'booking'
            setTimeout(() => openDatePicker(), 0)
            return
        }

        pickerSource.value = ''
        await fetchBookingPreview()
        return
    }

    pickerSource.value = ''
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
        if (pickerSource.value === 'booking') {
            pendingBookingDateEdit.value = true
        }
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
    pendingBookingDateEdit.value = false
    pickerSource.value = ''
}

const handleDatePickerChange = (event: any) => {
    datePickerValue.value = normalizeDatePickerValue(event.detail.value || [])
}

const confirmDatePicker = async () => {
    const year = datePickerYears.value[datePickerValue.value[0]]
    const month = String(datePickerMonths.value[datePickerValue.value[1]]).padStart(2, '0')
    const day = String(datePickerDays.value[datePickerValue.value[2]]).padStart(2, '0')
    const isFromBooking = pickerSource.value === 'booking'
    presetDate.value = `${year}-${month}-${day}`
    hideDatePicker()
    await getDetail()

    if (isFromBooking) {
        pickerSource.value = ''
        await fetchBookingPreview()
        return
    }

    pickerSource.value = ''
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

// 立即预约
const handleBook = () => {
    if (!staffId.value || staffId.value === 0) {
        uni.showToast({ title: '服务人员信息错误', icon: 'none' })
        return
    }

    openBookingPopupWithGuard()
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

// 预览证书
const previewCert = (url: string) => {
    uni.previewImage({
        urls: [url]
    })
}

const previewReviewImages = (
    images: Array<string | number>,
    index: number | string = 0
) => {
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
        path: `/packages/pages/staff_detail/staff_detail?${buildStaffDetailQuery()}`
    }

    const avatar = String(staffInfo.value?.avatar || '').trim()
    if (avatar) {
        payload.imageUrl = avatar
    }

    return payload
}

onLoad((options) => {
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
    if (options?.addon_ids) {
        selectedAddonIds.value = parseAddonIds(options.addon_ids)
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
})

onShow(async () => {
    // 微信分享直达时隐藏原生“返回首页”按钮
    // #ifdef MP-WEIXIN
    try {
        uni.hideHomeButton()
    } catch (error) {
        console.warn('隐藏首页按钮失败：', error)
    }
    // #endif

    await appStore.getConfig()
    await getRegionTree()
    if (staffId.value) {
        await getDetail()
        if (openBookingPopupRequested.value || openDatePickerRequested.value) {
            const shouldOpenDateEditor = openDatePickerRequested.value
            openBookingPopupRequested.value = false
            openDatePickerRequested.value = false
            await openBookingPopupPanel()
            if (shouldOpenDateEditor && showBookingPopup.value) {
                setTimeout(() => handleBookingDateEdit(), 0)
            }
        }
    }
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
        query: buildStaffDetailQuery()
    }

    if (sharePayload.imageUrl) {
        timelinePayload.imageUrl = sharePayload.imageUrl
    }

    return timelinePayload
})
// #endif
</script>

<style lang="scss" scoped>
.staff-detail {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--color-primary-light-9) 0%, #f5f5f5 100%);
    padding-bottom: env(safe-area-inset-bottom);
}

/* 头图区域 */
.banner-section {
    position: relative;
}

.immersive-hero {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    padding: 32rpx 24rpx;
    pointer-events: none;
    z-index: 5;

    .hero-mask {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 260rpx;
        background: linear-gradient(
            180deg,
            rgba(15, 23, 42, 0) 0%,
            rgba(15, 23, 42, 0.55) 55%,
            rgba(15, 23, 42, 0.9) 100%
        );
    }

    .hero-content {
        position: relative;
        display: flex;
        align-items: flex-end;
        gap: 20rpx;
        color: #ffffff;
    }

    .hero-avatar {
        width: 96rpx;
        height: 96rpx;
        border-radius: 24rpx;
        border: 3rpx solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 10rpx 26rpx rgba(0, 0, 0, 0.25);
    }

    .hero-info {
        flex: 1;
    }

    .hero-name-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        margin-bottom: 12rpx;
    }

    .hero-name {
        font-size: 40rpx;
        font-weight: 700;
    }

    .hero-badges {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    .hero-badge {
        padding: 4rpx 12rpx;
        border-radius: 12rpx;
        font-size: 20rpx;
        font-weight: 600;
    }

    .hero-badge.verified {
        background: rgba(59, 130, 246, 0.9);
    }

    .hero-badge.vip {
        background: rgba(251, 191, 36, 0.95);
        color: #4c1d95;
    }

    .hero-badge.recommend {
        background: rgba(244, 114, 182, 0.9);
    }

    .hero-meta-row {
        display: flex;
        align-items: center;
        gap: 12rpx;
        font-size: 24rpx;
        opacity: 0.9;
        margin-bottom: 16rpx;
    }

    .hero-experience::before {
        content: '|';
        margin-right: 10rpx;
        color: rgba(255, 255, 255, 0.6);
    }

    .hero-stats {
        display: flex;
        align-items: center;
        gap: 20rpx;
        font-size: 24rpx;
    }

    .hero-stat {
        display: flex;
        align-items: center;
        gap: 6rpx;
        background: rgba(15, 23, 42, 0.35);
        padding: 6rpx 12rpx;
        border-radius: 16rpx;
    }

    .hero-stat-label {
        font-size: 20rpx;
        opacity: 0.8;
    }
}

/* 沉浸视觉型标签带 */
.immersive-tags {
    margin: 0 24rpx 12rpx;
    padding: 24rpx;
    border-radius: 20rpx;
    background: #ffffff;
    box-shadow: 0 12rpx 30rpx rgba(41, 55, 147, 0.12);

    .immersive-tags-title {
        font-size: 28rpx;
        font-weight: 700;
        color: var(--color-main);
        margin-bottom: 16rpx;
    }

    .immersive-tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
    }

    .tag {
        padding: 8rpx 18rpx;
        border-radius: 14rpx;
        background: rgba(109, 93, 252, 0.12);
        border: 1rpx solid rgba(109, 93, 252, 0.3);

        .tag-text {
            font-size: 24rpx;
            color: #5b4bd6;
            font-weight: 600;
        }
    }
}

/* 高转化营销型高亮指标 */
.conversion-highlights {
    margin: 0 24rpx 12rpx;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16rpx;

    .highlight-card {
        background: #fff7ed;
        border: 1rpx solid #fed7aa;
        border-radius: 18rpx;
        padding: 20rpx;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
        box-shadow: 0 10rpx 22rpx rgba(249, 115, 22, 0.12);
    }

    .highlight-label {
        font-size: 22rpx;
        color: #c2410c;
    }

    .highlight-value {
        display: flex;
        align-items: center;
        gap: 8rpx;
    }

    .highlight-number {
        font-size: 30rpx;
        font-weight: 700;
        color: #f97316;
    }
}

/* 高转化营销型标签 */
.conversion-tags {
    margin: 0 24rpx 12rpx;
    padding: 22rpx 24rpx;
    border-radius: 18rpx;
    background: #ffffff;
    border: 1rpx solid rgba(249, 115, 22, 0.18);

    .conversion-tags-title {
        font-size: 26rpx;
        font-weight: 600;
        color: #9a3412;
        margin-bottom: 12rpx;
    }

    .conversion-tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 12rpx;
    }

    .tag {
        padding: 8rpx 16rpx;
        border-radius: 12rpx;
        background: rgba(249, 115, 22, 0.12);
        border: 1rpx solid rgba(249, 115, 22, 0.25);

        .tag-text {
            font-size: 24rpx;
            color: #c2410c;
            font-weight: 600;
        }
    }
}

/* 加载状态 */
.loading-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
}

/* 人员信息卡片 */
.info-card {
    margin: 0 24rpx 24rpx;
    background: #ffffff;
    border-radius: 24rpx;
    padding: 32rpx;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
    position: relative;
    z-index: 10;

    /* 小图模式：卡片压在轮播图上 */
    &.card-overlap {
        margin-top: -56rpx;
    }
}

.card-header {
    display: flex;
    align-items: flex-start;
    margin-bottom: 24rpx;

    .staff-avatar {
        width: 120rpx;
        height: 120rpx;
        border-radius: 16rpx;
        border: 4rpx solid #ffffff;
        box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
        flex-shrink: 0;
    }

    .header-info {
        flex: 1;
        margin-left: 20rpx;
    }
}

.name-row {
    display: flex;
    align-items: center;
    gap: 12rpx;
    margin-bottom: 12rpx;

    .staff-name {
        font-size: 36rpx;
        font-weight: 700;
        color: var(--color-main);
    }

    .verified-badge,
    .vip-badge {
        display: flex;
        align-items: center;
    }

    .recommend-badge {
        padding: 4rpx 12rpx;
        background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-secondary) 100%);
        border-radius: 12rpx;

        text {
            font-size: 20rpx;
            font-weight: 600;
            color: #ffffff;
        }
    }
}

.category-row {
    display: flex;
    align-items: center;
    gap: 12rpx;

    .category-text {
        font-size: 26rpx;
        color: var(--color-content);
    }

    .experience-text {
        font-size: 26rpx;
        color: var(--color-muted);

        &::before {
            content: '|';
            margin-right: 12rpx;
            color: var(--color-light);
        }
    }
}

/* 评分统计 */
.stats-row {
    display: flex;
    align-items: center;
    justify-content: space-around;
    padding: 24rpx 0;
    border-top: 1rpx solid #f0f0f0;
    border-bottom: 1rpx solid #f0f0f0;
    margin-bottom: 24rpx;

    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8rpx;

        .stat-value {
            display: flex;
            align-items: center;
            gap: 8rpx;
            font-size: 32rpx;
            font-weight: 700;
            color: var(--color-main);
        }

        .stat-label {
            font-size: 24rpx;
            color: var(--color-muted);
        }
    }

    .stat-divider {
        width: 1rpx;
        height: 48rpx;
        background: #e5e5e5;
    }
}

/* 价格行 */
.price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.price-wrapper {
    flex: 1;

    .price-label {
        font-size: 24rpx;
        color: var(--color-muted);
        margin-bottom: 8rpx;
    }

    .price-amount {
        display: flex;
        align-items: baseline;

        .price-symbol {
            font-size: 28rpx;
            font-weight: 600;
            color: var(--color-primary);
            margin-right: 4rpx;
        }

        .price-value {
            font-size: 48rpx;
            font-weight: 700;
            color: var(--color-primary);
        }

        .price-unit {
            font-size: 24rpx;
            color: var(--color-muted);
            margin-left: 8rpx;
        }

        .price-negotiable {
            font-size: 36rpx;
            font-weight: 700;
            color: var(--color-muted);
            line-height: 1.1;
        }
    }
}

.favorite-btn {
    width: 80rpx;
    height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f9fafb;
    border-radius: 40rpx;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.9);
        background: #f0f0f0;
    }
}

/* 标签页切换 */
.tabs-section {
    margin: 16rpx 24rpx 0;
    background: #ffffff;
    border-radius: 16rpx;
    padding: 0 24rpx;
}

.tabs-wrapper {
    display: flex;
    align-items: center;
    gap: 48rpx;
}

.tab-item {
    position: relative;
    padding: 24rpx 0;
    cursor: pointer;

    .tab-text {
        font-size: 28rpx;
        font-weight: 500;
        color: var(--color-content);
        transition: all 0.2s ease;
    }

    &.active .tab-text {
        font-weight: 700;
    }

    .tab-indicator {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4rpx;
        border-radius: 2rpx;
    }
}

/* 标签页内容 */
.tab-content {
    margin: 16rpx 24rpx 0;
}

.content-section {
    background: #ffffff;
    border-radius: 16rpx;
    padding: 32rpx;
}

.content-block {
    margin-bottom: 32rpx;

    &:last-child {
        margin-bottom: 0;
    }
}

.block-title {
    font-size: 30rpx;
    font-weight: 700;
    color: var(--color-main);
    margin-bottom: 20rpx;
}

.block-content {
    font-size: 28rpx;
    color: var(--color-content);
    line-height: 1.8;
}

.picker-container {
    background: #ffffff;
    width: 100vw;
    max-width: 100vw;
    margin: 0;
    border-radius: 24rpx 24rpx 0 0;
    box-shadow: 0 -12rpx 36rpx rgba(15, 23, 42, 0.1);
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;

    .picker-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22rpx 24rpx;
        border-bottom: 1rpx solid #eef1f5;
    }

    .picker-title {
        font-size: 30rpx;
        font-weight: 700;
        color: #1f2937;
    }

    .picker-action {
        min-width: 96rpx;
        font-size: 28rpx;
        color: #667085;
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
    padding: 12rpx 16rpx 24rpx;
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
    color: #111827;
}

.picker-footer {
    display: flex;
    gap: 16rpx;
    padding: 16rpx 24rpx 24rpx;
    border-top: 1rpx solid #eef1f5;
}

.picker-btn {
    flex: 1;
    height: 82rpx;
    border-radius: 16rpx;
    background: #f3f4f6;
    color: #475467;
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
    padding: 18rpx 18rpx 8rpx;
}

.region-picker-col {
    min-width: 0;
    border: 1rpx solid #eef1f5;
    border-radius: 18rpx;
    overflow: hidden;
    background: #f9fafc;
}

.region-picker-col__title {
    padding: 18rpx 20rpx 14rpx;
    font-size: 24rpx;
    font-weight: 600;
    color: #374151;
    border-bottom: 1rpx solid #eef1f5;
    background: #ffffff;
}

.region-picker-scroll {
    height: 480rpx;
}

.region-picker-item {
    padding: 20rpx;
    font-size: 24rpx;
    color: #4b5563;
    border-bottom: 1rpx solid rgba(229, 231, 235, 0.72);
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

.booking-popup {
    background: #ffffff;
    width: 100vw;
    max-width: 100vw;
    border-radius: 24rpx 24rpx 0 0;
    max-height: 84vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.booking-popup-handle {
    width: 72rpx;
    height: 8rpx;
    border-radius: 999rpx;
    background: #d9dee6;
    margin: 14rpx auto 6rpx;
    flex-shrink: 0;
}

.booking-popup-scroll {
    flex: 1;
    min-height: 0;
    padding: 8rpx 16rpx 0;
}

.booking-popup-body {
    padding-bottom: 8rpx;
}

.booking-popup-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12rpx;
}

.booking-popup-field {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
    min-width: 0;
    padding: 16rpx;
    border-radius: 16rpx;
    background: #f7f8fa;
    border: 1rpx solid #eceff4;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.995);
    }
}

.booking-popup-field--empty {
    border-style: dashed;
    background: #fcfcfd;
}

.booking-popup-field__label-row {
    display: flex;
    align-items: center;
    gap: 8rpx;
}

.booking-popup-field__label {
    font-size: 20rpx;
    color: #8b95a7;
    font-weight: 600;
}

.booking-popup-field__content {
    display: flex;
    align-items: center;
    gap: 8rpx;
    min-width: 0;
}

.booking-popup-field__value {
    flex: 1;
    min-width: 0;
    font-size: 26rpx;
    line-height: 1.3;
    font-weight: 600;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.booking-popup-field__value--empty {
    color: #98a2b3;
    font-weight: 500;
}

.booking-popup-field__arrow {
    flex-shrink: 0;
}

.booking-popup-status {
    margin-top: 10rpx;
    display: flex;
    align-items: flex-start;
    gap: 8rpx;
    padding: 12rpx 14rpx;
    border-radius: 14rpx;
    font-size: 22rpx;
    line-height: 1.5;
}

.booking-popup-status--warning {
    color: #c2410c;
    background: rgba(249, 115, 22, 0.08);
    border: 1rpx solid rgba(249, 115, 22, 0.16);
}

.booking-popup-section {
    margin-top: 14rpx;
    padding: 18rpx 16rpx;
    border-radius: 18rpx;
    background: #ffffff;
    border: 1rpx solid #edf0f3;
}

.booking-popup-section__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16rpx;
    margin-bottom: 14rpx;
}

.booking-popup-section__title {
    font-size: 26rpx;
    font-weight: 700;
    color: #111827;
}

.booking-popup-section__hint {
    font-size: 21rpx;
    color: #98a2b3;
}

.booking-popup-package-list,
.booking-addon-list {
    display: flex;
    flex-direction: column;
    gap: 10rpx;
}

.booking-popup-package,
.booking-addon-card {
    display: flex;
    align-items: center;
    gap: 14rpx;
    padding: 16rpx;
    border-radius: 16rpx;
    border: 1rpx solid #eceff4;
    background: #fbfbfc;
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.996);
    }
}

.booking-popup-package--active,
.booking-addon-card--active {
    background: #fff8f2;
}

.booking-popup-package__main,
.booking-addon-card__main {
    flex: 1;
    min-width: 0;
}

.booking-popup-package__title,
.booking-addon-card__title {
    flex: 1;
    min-width: 0;
    font-size: 25rpx;
    font-weight: 600;
    color: #111827;
    line-height: 1.4;
}

.booking-popup-package__title-group {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8rpx;
    flex: 1;
    min-width: 0;
}

.booking-popup-package__tag {
    padding: 2rpx 10rpx;
    border-radius: 999rpx;
    font-size: 18rpx;
    font-weight: 600;
}

.booking-popup-package__tag--recommend {
    color: #e67a3c;
    background: rgba(230, 122, 60, 0.1);
    border: 1rpx solid rgba(230, 122, 60, 0.16);
}

.booking-popup-package__side,
.booking-addon-card__side {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 14rpx;
}

.booking-popup-package__radio {
    width: 30rpx;
    height: 30rpx;
    border-radius: 50%;
    border: 2rpx solid #cfd6df;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #ffffff;
}

.booking-popup-package__desc,
.booking-addon-card__desc {
    display: block;
    margin-top: 6rpx;
    font-size: 22rpx;
    line-height: 1.5;
    color: #8b95a7;
}

.booking-popup-package__price,
.booking-addon-card__price {
    min-width: 120rpx;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4rpx;
    text-align: right;
}

.booking-popup-package__price-original,
.booking-addon-card__price-original {
    font-size: 20rpx;
    color: #b0b8c4;
    text-decoration: line-through;
}

.booking-popup-package__price-current,
.booking-addon-card__price-current {
    font-size: 26rpx;
    font-weight: 700;
    color: #d85c61;
}

.booking-addon-card__check {
    width: 30rpx;
    height: 30rpx;
    border-radius: 50%;
    border: 2rpx solid #cfd6df;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: #ffffff;
}

.booking-popup-loading,
.booking-popup-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10rpx;
    padding: 34rpx 20rpx;
    border-radius: 16rpx;
    background: #f8fafc;
    border: 1rpx dashed #d7dde6;
}

.booking-popup-loading__text,
.booking-popup-empty__text {
    font-size: 22rpx;
    color: #98a2b3;
}

.booking-popup-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14rpx;
    padding: 16rpx 18rpx;
    padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
    border-top: 1rpx solid #eef1f5;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 -6rpx 18rpx rgba(15, 23, 42, 0.05);
}

.booking-popup-footer__price {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 6rpx;
}

.booking-popup-footer__label {
    font-size: 20rpx;
    color: #667085;
}

.booking-popup-footer__amount {
    display: flex;
    align-items: baseline;
    gap: 4rpx;
}

.booking-popup-footer__symbol,
.booking-popup-footer__value {
    color: #d85c61;
    font-weight: 700;
}

.booking-popup-footer__symbol {
    font-size: 28rpx;
}

.booking-popup-footer__value {
    font-size: 36rpx;
}

.booking-popup-footer__detail {
    font-size: 20rpx;
    color: #98a2b3;
    line-height: 1.5;
}

.booking-popup-footer__action {
    min-width: 236rpx;
    padding: 20rpx 24rpx;
    border-radius: 999rpx;
    text-align: center;
    font-size: 26rpx;
    font-weight: 600;
    color: #ffffff;
    background: #cbd5e1;
}

.booking-popup-footer__action--disabled {
    opacity: 0.72;
}

/* 标签 */
.tags-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
}

.tag-item {
    padding: 8rpx 20rpx;
    background: var(--color-primary-light-9);
    border: 1rpx solid var(--color-primary-light-7);
    border-radius: 16rpx;

    .tag-text {
        font-size: 26rpx;
        font-weight: 500;
        color: var(--color-primary);
    }
}

/* 作品网格 */
.works-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16rpx;
}

.work-item {
    position: relative;
    border-radius: 12rpx;
    overflow: hidden;

    .work-image {
        width: 100%;
        height: 280rpx;
    }

    .work-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 16rpx;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.6), transparent);

        .work-title {
            font-size: 24rpx;
            color: #ffffff;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }
}

/* 资质证书 */
.certs-scroll {
    white-space: nowrap;
}

.certs-wrapper {
    display: inline-flex;
    gap: 16rpx;
}

.cert-item {
    display: inline-block;
    width: 240rpx;

    .cert-image {
        width: 240rpx;
        height: 160rpx;
        border-radius: 12rpx;
    }

    .cert-name {
        font-size: 24rpx;
        color: var(--color-content);
        margin-top: 12rpx;
        text-align: center;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

.review-summary {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16rpx;
    margin-bottom: 24rpx;
}

.review-summary-card {
    padding: 24rpx 18rpx;
    border-radius: 18rpx;
    background: #f8fafc;
    border: 1rpx solid #edf2f7;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10rpx;
}

.review-summary-value {
    font-size: 34rpx;
    font-weight: 700;
    color: var(--color-main);
}

.review-summary-label {
    font-size: 24rpx;
    color: var(--color-muted);
}

.review-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-bottom: 24rpx;
}

.review-filter-item {
    padding: 10rpx 16rpx;
    border-radius: 999rpx;
    background: var(--color-primary-light-9);
    border: 1rpx solid var(--color-primary-light-7);
    font-size: 24rpx;
    color: var(--color-primary);
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 18rpx;
}

.review-card {
    padding: 24rpx;
    border-radius: 20rpx;
    background: #f8fafc;
    border: 1rpx solid #edf2f7;
}

.review-card-header {
    display: flex;
    align-items: center;
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
    background: #f3f4f6;
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
    font-size: 28rpx;
    font-weight: 600;
    color: var(--color-main);
}

.review-time {
    font-size: 22rpx;
    color: var(--color-muted);
}

.review-score {
    display: inline-flex;
    align-items: center;
    gap: 4rpx;
}

.review-content {
    display: block;
    margin-top: 18rpx;
    font-size: 26rpx;
    line-height: 1.7;
    color: var(--color-content);
}

.review-tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10rpx;
    margin-top: 16rpx;
}

.review-tag {
    padding: 8rpx 16rpx;
    border-radius: 999rpx;
    background: rgba(124, 58, 237, 0.08);
    border: 1rpx solid rgba(124, 58, 237, 0.18);
    font-size: 22rpx;
    color: #7c3aed;
}

.review-image-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12rpx;
    margin-top: 18rpx;
}

.review-image {
    width: calc((100% - 24rpx) / 3);
    height: 180rpx;
    border-radius: 16rpx;
    background: #f3f4f6;
}

.review-reply-list {
    display: flex;
    flex-direction: column;
    gap: 12rpx;
    margin-top: 18rpx;
}

.review-reply-item {
    padding: 18rpx;
    border-radius: 16rpx;
    background: #ffffff;
    border: 1rpx solid #eef2f6;
}

.review-reply-type {
    display: block;
    font-size: 22rpx;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 8rpx;
}

.review-reply-content {
    display: block;
    font-size: 24rpx;
    line-height: 1.6;
    color: var(--color-content);
}

.review-load-more {
    padding-top: 8rpx;
    text-align: center;
}

.review-load-more-text {
    font-size: 24rpx;
    color: var(--color-muted);
}

.review-load-more-text--action {
    color: var(--color-primary);
    font-weight: 600;
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 120rpx 0;

    .empty-text {
        font-size: 28rpx;
        color: var(--color-muted);
        margin-top: 24rpx;
    }
}

/* 加载状态 */
.loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 120rpx 0;
}

/* 底部占位 */
.bottom-placeholder {
    height: 180rpx;
}

/* 底部操作栏 */
.bottom-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    align-items: center;
    gap: 16rpx;
    padding: 20rpx 24rpx;
    padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
    background: #ffffff;
    box-shadow: 0 -4rpx 16rpx rgba(0, 0, 0, 0.08);
    z-index: 100;
}

.action-btns {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: space-evenly;
}

.action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4rpx;
    width: 96rpx;
    min-height: 92rpx;
    flex-shrink: 0;

    .action-text {
        font-size: 22rpx;
        color: var(--color-content);
        line-height: 1.2;
        text-align: center;
        white-space: nowrap;
    }
}

.share-action-item {
    position: relative;
    overflow: hidden;
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
    border-radius: inherit;
    box-shadow: none;
    opacity: 0;
    line-height: normal;
    font-size: 0;
    color: transparent;
    appearance: none;
    -webkit-appearance: none;
    -webkit-tap-highlight-color: transparent;

    &::after {
        display: none;
    }
}

.share-action-icon {
    line-height: 38rpx;
}

.book-btn {
    flex: 0 0 232rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12rpx;
    height: 72rpx;
    border-radius: 32rpx;
    box-shadow: 0 6rpx 16rpx rgba(124, 58, 237, 0.22);
    transition: all 0.2s ease;

    &:active {
        transform: scale(0.98);
        box-shadow: 0 2rpx 8rpx rgba(124, 58, 237, 0.3);
    }

    .book-text {
        font-size: 32rpx;
        font-weight: 600;
        color: #ffffff;
    }
}

/* 沉浸视觉型 */
.staff-detail.style-immersive {
    background: linear-gradient(180deg, #f3f4ff 0%, #f7f8ff 24%, #f7f7f7 100%);

    .info-card {
        border-radius: 28rpx;
        border: 1rpx solid rgba(109, 93, 252, 0.16);
        box-shadow: 0 14rpx 40rpx rgba(45, 65, 175, 0.12);
    }

    .staff-name {
        font-size: 40rpx;
    }

    .tabs-section {
        margin-top: 16rpx;
        box-shadow: 0 10rpx 24rpx rgba(90, 75, 220, 0.08);
    }

    .book-btn {
        border-radius: 36rpx;
        box-shadow: 0 10rpx 22rpx rgba(90, 75, 220, 0.26);
    }
}

/* 高转化营销型 */
.staff-detail.style-conversion {
    background: linear-gradient(180deg, #fff6f2 0%, #fffdf8 18%, #f7f7f7 100%);

    .info-card {
        border: 2rpx solid rgba(250, 173, 20, 0.3);
        box-shadow: 0 10rpx 34rpx rgba(250, 173, 20, 0.12);
    }

    .tabs-section {
        border: 1rpx solid rgba(249, 115, 22, 0.18);
    }

    .content-section {
        border: 1rpx solid rgba(249, 115, 22, 0.12);
    }

    .price-row {
        align-items: flex-end;
        padding-bottom: 8rpx;
    }

    .price-label,
    .price-unit {
        color: #f59e0b;
    }

    .price-value {
        color: #f97316;
    }

    .price-symbol,
    .package-price {
        color: #f97316;
    }

    .book-btn {
        box-shadow: 0 10rpx 24rpx rgba(249, 115, 22, 0.28);
    }
}
</style>
