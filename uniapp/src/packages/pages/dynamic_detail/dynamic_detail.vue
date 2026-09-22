<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer" has-safe-bottom>
        <BaseNavbar
            title="动态详情"
            variant="solid"
            title-align="center"
            bg-color="#181614"
            text-color="#FFFDF8"
            @back="handleBack"
        />

        <view v-if="detail" class="dynamic-detail">
            <scroll-view scroll-y class="dynamic-detail__scroll" :style="scrollStyle">
                <view class="dynamic-detail__content">
                    <!-- 顶部沉浸式多媒体展厅 (Hero Showcase) -->
                    <view v-if="hasVideo || hasImages" class="dynamic-detail__hero-showcase">
                        <!-- 视频与图集双模式胶囊切换 -->
                        <view
                            v-if="hasVideo && hasImages"
                            class="dynamic-detail__media-switch"
                        >
                            <view
                                class="dynamic-detail__media-switch-btn"
                                :class="{ 'is-active': mediaTab === 'video' }"
                                @click="mediaTab = 'video'"
                            >
                                <BaseIcon
                                    name="play"
                                    size="20"
                                    :color="mediaTab === 'video' ? '#181614' : '#FFFDF8'"
                                />
                                <text>视频</text>
                            </view>
                            <view
                                class="dynamic-detail__media-switch-btn"
                                :class="{ 'is-active': mediaTab === 'gallery' }"
                                @click="mediaTab = 'gallery'"
                            >
                                <BaseIcon
                                    name="picture"
                                    size="20"
                                    :color="mediaTab === 'gallery' ? '#181614' : '#FFFDF8'"
                                />
                                <text>图集 ({{ imageCount }})</text>
                            </view>
                        </view>

                        <!-- 视频播放模式 -->
                        <view
                            v-if="hasVideo && (mediaTab === 'video' || !hasImages)"
                            class="dynamic-detail__hero-video-box"
                        >
                            <video
                                :src="detail.video"
                                class="dynamic-detail__hero-video"
                                :poster="detail.video_cover"
                                controls
                                object-fit="cover"
                                :show-center-play-btn="true"
                                :enable-play-gesture="true"
                            />
                        </view>

                        <!-- 图片画廊轮播模式 -->
                        <view
                            v-else-if="hasImages"
                            class="dynamic-detail__hero-swiper-box"
                        >
                            <swiper
                                class="dynamic-detail__hero-swiper"
                                :circular="imageCount > 1"
                                :current="currentImageIndex"
                                @change="handleSwiperChange"
                            >
                                <swiper-item
                                    v-for="(img, idx) in previewImageUrls"
                                    :key="`${img}-${idx}`"
                                    class="dynamic-detail__hero-swiper-item"
                                >
                                    <image
                                        class="dynamic-detail__hero-swiper-image"
                                        :src="img"
                                        mode="aspectFill"
                                        @click="previewImage(idx)"
                                    />
                                </swiper-item>
                            </swiper>

                            <!-- 浮层页码与高清预览胶囊 -->
                            <view class="dynamic-detail__hero-overlay">
                                <view
                                    class="dynamic-detail__hero-indicator"
                                    @click="previewImage(currentImageIndex)"
                                >
                                    <BaseIcon name="tip" size="18" color="#D9BE82" />
                                    <text>全屏高清</text>
                                </view>
                                <view v-if="imageCount > 1" class="dynamic-detail__hero-counter">
                                    <text class="dynamic-detail__hero-current">
                                        {{ String(currentImageIndex + 1).padStart(2, '0') }}
                                    </text>
                                    <text class="dynamic-detail__hero-divider">/</text>
                                    <text class="dynamic-detail__hero-total">
                                        {{ String(imageCount).padStart(2, '0') }}
                                    </text>
                                </view>
                            </view>
                        </view>
                    </view>

                    <!-- 页面主要内容卡片区 (统一边距与间距) -->
                    <view class="dynamic-detail__body">
                        <!-- 作者与叙事主体卡片 -->
                        <BaseCard
                            variant="panel"
                            scene="consumer"
                            padding="32rpx 30rpx"
                            class="dynamic-detail__lead-card"
                        >
                            <view class="dynamic-detail__lead-body">
                                <view class="dynamic-detail__lead-head">
                                    <image
                                        :src="detail.user_avatar || '/static/images/user/default_avatar.png'"
                                        class="dynamic-detail__avatar"
                                        mode="aspectFill"
                                    />
                                    <view class="dynamic-detail__author-copy">
                                        <view class="dynamic-detail__author-row">
                                            <view class="dynamic-detail__author-title">
                                                <text class="dynamic-detail__author-name">
                                                    {{ detail.user_nickname }}
                                                </text>
                                            </view>
                                            <view class="dynamic-detail__author-badges">
                                                <StatusBadge
                                                    v-if="detail.user_type === 2"
                                                    tone="info"
                                                    size="xs"
                                                >
                                                    服务人员
                                                </StatusBadge>
                                                <StatusBadge
                                                    v-if="detail.user_type === 3"
                                                    tone="primary"
                                                    size="xs"
                                                >
                                                    官方
                                                </StatusBadge>
                                                <StatusBadge
                                                    v-if="detail.is_top === 1"
                                                    tone="warning"
                                                    size="xs"
                                                >
                                                    置顶
                                                </StatusBadge>
                                                <StatusBadge
                                                    v-if="detail.is_hot === 1"
                                                    tone="danger"
                                                    size="xs"
                                                >
                                                    热门
                                                </StatusBadge>
                                            </view>
                                        </view>
                                        <text class="dynamic-detail__author-meta">
                                            {{ authorMetaText }}
                                        </text>
                                    </view>

                                    <!-- 服务人员主页/预约转化入口 -->
                                    <view
                                        v-if="authorStaffId > 0"
                                        class="dynamic-detail__staff-action"
                                        @click="handleGoStaff"
                                    >
                                        <text class="dynamic-detail__staff-action-text">预约TA</text>
                                        <BaseIcon name="right" size="16" color="#C6A15B" />
                                    </view>
                                </view>

                                <!-- 正文内容 -->
                                <text class="dynamic-detail__content-text">{{ detail.content }}</text>

                                <!-- 分类、浏览量与话题胶囊行 -->
                                <view class="dynamic-detail__summary-row">
                                    <StatusBadge
                                        v-if="detail.dynamic_type && detail.dynamic_type !== 1"
                                        :tone="getTypeTone(detail.dynamic_type)"
                                        size="sm"
                                        strong
                                    >
                                        {{ getTypeText(detail.dynamic_type) }}
                                    </StatusBadge>

                                    <view class="dynamic-detail__detail-meta-item">
                                        <BaseIcon name="eye" size="22" color="#8C8273" />
                                        <text>浏览 {{ formatCount(detail.view_count) }}</text>
                                    </view>

                                    <view
                                        v-for="(tag, tagIdx) in detailTags"
                                        :key="`${tag}-${tagIdx}`"
                                        class="dynamic-detail__topic-tag"
                                        @click="handleTopicTagClick(tag)"
                                    >
                                        <text>#{{ tag }}</text>
                                    </view>
                                </view>
                            </view>
                        </BaseCard>

                        <!-- 活动专属邀请函卡片 (Dynamic Type 4) -->
                        <BaseCard
                            v-if="isActivity"
                            variant="panel"
                            scene="consumer"
                            padding="28rpx"
                            class="dynamic-detail__activity-card"
                        >
                            <view class="dynamic-detail__activity-body">
                                <view class="dynamic-detail__activity-head">
                                    <view class="dynamic-detail__activity-head-left">
                                        <view class="dynamic-detail__activity-stamp">
                                            <text class="dynamic-detail__activity-stamp-text">EVENT PASS</text>
                                        </view>
                                        <view>
                                            <text class="dynamic-detail__activity-title">沙龙活动报名</text>
                                            <text class="dynamic-detail__activity-subtitle">
                                                {{ activityStatusText }}
                                            </text>
                                        </view>
                                    </view>
                                    <StatusBadge tone="primary" size="md" strong>
                                        {{ activityPriceLabel }}
                                    </StatusBadge>
                                </view>

                                <view class="dynamic-detail__activity-meta-grid">
                                    <view
                                        v-for="item in activityMetaItems"
                                        :key="item.label"
                                        class="dynamic-detail__activity-meta-item"
                                    >
                                        <text class="dynamic-detail__activity-meta-label">
                                            {{ item.label }}
                                        </text>
                                        <text class="dynamic-detail__activity-meta-value">
                                            {{ item.value }}
                                        </text>
                                    </view>
                                </view>

                                <!-- 票种选择展示 -->
                                <view class="dynamic-detail__ticket-list">
                                    <view
                                        v-for="ticket in activityTickets"
                                        :key="ticket.id"
                                        class="dynamic-detail__ticket-item"
                                        :class="{ 'is-disabled': !isTicketBuyable(ticket) }"
                                    >
                                        <view class="dynamic-detail__ticket-copy">
                                            <text class="dynamic-detail__ticket-name">{{ ticket.name }}</text>
                                            <view class="dynamic-detail__ticket-tags">
                                                <text class="dynamic-detail__ticket-stock">
                                                    {{ getTicketStatusText(ticket) }}
                                                </text>
                                                <text
                                                    v-if="getTicketSaleCountdownText(ticket)"
                                                    class="dynamic-detail__ticket-sale-time"
                                                >
                                                    {{ getTicketSaleCountdownText(ticket) }}
                                                </text>
                                            </view>
                                        </view>
                                        <text class="dynamic-detail__ticket-price">
                                            {{ ticket.price_label }}
                                        </text>
                                    </view>
                                    <view
                                        v-if="activityTickets.length === 0"
                                        class="dynamic-detail__ticket-empty"
                                    >
                                        暂无可报名票种
                                    </view>
                                </view>
                            </view>
                        </BaseCard>

                        <!-- 互动评论与讨论区 -->
                        <BaseCard
                            v-if="shouldShowCommentSection"
                            variant="panel"
                            scene="consumer"
                            padding="30rpx 28rpx 24rpx"
                            class="dynamic-detail__comments-card"
                        >
                            <view class="dynamic-detail__comments-body">
                                <view class="dynamic-detail__comments-head">
                                    <view class="dynamic-detail__comments-title-box">
                                        <text class="dynamic-detail__comments-title">
                                            评论 ({{ formatCount(detail.comment_count) }})
                                        </text>
                                    </view>
                                    <view class="dynamic-detail__comments-head-right">
                                        <view class="dynamic-detail__comments-sort-segmented">
                                            <view
                                                class="dynamic-detail__sort-tab"
                                                :class="{ 'is-active': commentSort === 'hot' }"
                                                @click="changeCommentSort('hot')"
                                            >
                                                最热
                                            </view>
                                            <view
                                                class="dynamic-detail__sort-tab"
                                                :class="{ 'is-active': commentSort === 'new' }"
                                                @click="changeCommentSort('new')"
                                            >
                                                最新
                                            </view>
                                        </view>
                                        <view
                                            v-if="shouldShowCommentSection"
                                            class="dynamic-detail__comments-write-btn"
                                            @click="showCommentInput"
                                        >
                                            <BaseIcon name="edit" size="18" color="#9A6B35" />
                                            <text>回复</text>
                                        </view>
                                    </view>
                                </view>

                                <!-- 空评论状态 -->
                                <view v-if="comments.length === 0" class="dynamic-detail__comment-empty">
                                    <EmptyState
                                        title="还没有评论"
                                        description="留下第一条回复，和大家分享你的见解吧~"
                                        compact
                                    />
                                    <view
                                        v-if="shouldShowCommentSection"
                                        class="dynamic-detail__comment-empty-btn"
                                        @click="showCommentInput"
                                    >
                                        <BaseIcon name="edit" size="20" color="#9A6B35" />
                                        <text>发表第一条回复</text>
                                    </view>
                                </view>

                                <!-- 评论列表 -->
                                <view v-else class="dynamic-detail__comment-list">
                                    <view class="dynamic-detail__comment-stack">
                                        <view
                                            v-for="item in comments"
                                            :key="`comment-${item.id}`"
                                            class="dynamic-detail__comment-item"
                                        >
                                            <image
                                                class="dynamic-detail__comment-avatar"
                                                :src="item.avatar"
                                                mode="aspectFill"
                                            />
                                            <view class="dynamic-detail__comment-body">
                                                <view class="dynamic-detail__comment-main">
                                                    <view class="dynamic-detail__comment-meta">
                                                        <view class="dynamic-detail__comment-author-box">
                                                            <text class="dynamic-detail__comment-author">
                                                                {{ item.nickname }}
                                                            </text>
                                                            <StatusBadge
                                                                v-if="isCommentAuthor(item)"
                                                                tone="primary"
                                                                size="xs"
                                                            >
                                                                作者
                                                            </StatusBadge>
                                                        </view>
                                                        <view class="dynamic-detail__comment-meta-right">
                                                            <text class="dynamic-detail__comment-time">
                                                                {{ formatCommentTime(item.date) }}
                                                            </text>
                                                            <view
                                                                class="dynamic-detail__comment-like-pill"
                                                                :class="{ 'is-active': item.likeActive }"
                                                                @tap.stop="handleLikeComment(item.id)"
                                                            >
                                                                <BaseIcon
                                                                    :name="item.likeActive ? 'like-fill' : 'like'"
                                                                    size="18"
                                                                    :color="item.likeActive ? '#C6A15B' : '#8C8273'"
                                                                />
                                                                <text class="dynamic-detail__comment-like-count">
                                                                    {{ formatCommentLikeCount(item.likeCount) }}
                                                                </text>
                                                            </view>
                                                        </view>
                                                    </view>
                                                    <text class="dynamic-detail__comment-content">
                                                        {{ item.content }}
                                                    </text>
                                                    <view class="dynamic-detail__comment-actions">
                                                        <view
                                                            v-if="!miniProgramReviewMode"
                                                            class="dynamic-detail__comment-action"
                                                            @tap.stop="replyComment(item)"
                                                        >
                                                            <BaseIcon name="chat" size="18" color="#8C8273" />
                                                            <text>回复</text>
                                                        </view>
                                                        <view
                                                            v-if="item.allowDelete"
                                                            class="dynamic-detail__comment-action is-danger"
                                                            @tap.stop="deleteCommentItem(item.id)"
                                                        >
                                                            <BaseIcon name="delete" size="18" color="#B84A39" />
                                                            <text>删除</text>
                                                        </view>
                                                    </view>
                                                </view>

                                                <!-- 子回复列表 -->
                                                <view
                                                    v-if="item.comment && item.comment.length > 0 && item.replyExpanded"
                                                    class="dynamic-detail__reply-list"
                                                >
                                                    <view
                                                        v-for="reply in item.comment"
                                                        :key="`reply-${reply.id}`"
                                                        class="dynamic-detail__reply-item"
                                                    >
                                                        <image
                                                            class="dynamic-detail__comment-avatar dynamic-detail__comment-avatar--reply"
                                                            :src="reply.avatar"
                                                            mode="aspectFill"
                                                        />
                                                        <view class="dynamic-detail__comment-main">
                                                            <view class="dynamic-detail__comment-meta">
                                                                <view class="dynamic-detail__comment-author-box">
                                                                    <text class="dynamic-detail__comment-author">
                                                                        {{ reply.nickname }}
                                                                    </text>
                                                                    <text
                                                                        v-if="reply.replyUserNickname"
                                                                        class="dynamic-detail__reply-target"
                                                                    >
                                                                        回复 @{{ reply.replyUserNickname }}
                                                                    </text>
                                                                    <StatusBadge
                                                                        v-if="isCommentAuthor(reply)"
                                                                        tone="primary"
                                                                        size="xs"
                                                                    >
                                                                        作者
                                                                    </StatusBadge>
                                                                </view>
                                                                <view class="dynamic-detail__comment-meta-right">
                                                                    <text class="dynamic-detail__comment-time">
                                                                        {{ formatCommentTime(reply.date) }}
                                                                    </text>
                                                                    <view
                                                                        class="dynamic-detail__comment-like-pill"
                                                                        :class="{ 'is-active': reply.likeActive }"
                                                                        @tap.stop="handleLikeComment(reply.id)"
                                                                    >
                                                                        <BaseIcon
                                                                            :name="reply.likeActive ? 'like-fill' : 'like'"
                                                                            size="18"
                                                                            :color="reply.likeActive ? '#C6A15B' : '#8C8273'"
                                                                        />
                                                                        <text class="dynamic-detail__comment-like-count">
                                                                            {{ formatCommentLikeCount(reply.likeCount) }}
                                                                        </text>
                                                                    </view>
                                                                </view>
                                                            </view>
                                                            <text class="dynamic-detail__comment-content">
                                                                {{ reply.content }}
                                                            </text>
                                                            <view class="dynamic-detail__comment-actions">
                                                                <view
                                                                    v-if="!miniProgramReviewMode"
                                                                    class="dynamic-detail__comment-action"
                                                                    @tap.stop="replyComment(reply)"
                                                                >
                                                                    <BaseIcon name="chat" size="18" color="#8C8273" />
                                                                    <text>回复</text>
                                                                </view>
                                                                <view
                                                                    v-if="reply.allowDelete"
                                                                    class="dynamic-detail__comment-action is-danger"
                                                                    @tap.stop="deleteCommentItem(reply.id)"
                                                                >
                                                                    <BaseIcon name="delete" size="18" color="#B84A39" />
                                                                    <text>删除</text>
                                                                </view>
                                                            </view>
                                                        </view>
                                                    </view>
                                                </view>

                                                <!-- 展开/收起回复切换 -->
                                                <view
                                                    v-if="item.commentCount > 0"
                                                    class="dynamic-detail__reply-toggle"
                                                    @tap.stop="toggleReplies(item)"
                                                >
                                                    <text>{{ getReplyToggleText(item) }}</text>
                                                    <BaseIcon
                                                        :name="item.replyExpanded ? 'up' : 'down'"
                                                        size="16"
                                                        color="#9A6B35"
                                                    />
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <!-- 点击加载更多评论 -->
                                <view
                                    v-if="commentHasMore && comments.length > 0"
                                    class="dynamic-detail__comment-more"
                                >
                                    <view v-if="commentLoading" class="dynamic-detail__loading-mini">
                                        <view class="dynamic-detail__mini-spinner"></view>
                                        <text>加载评论中...</text>
                                    </view>
                                    <view v-else class="dynamic-detail__more-pill" @click="loadMoreComments">
                                        <text>展开更多评论</text>
                                        <BaseIcon name="down" size="16" color="#8C8273" />
                                    </view>
                                </view>
                            </view>
                        </BaseCard>
                    </view>
                </view>
            </scroll-view>

            <!-- 底部悬浮操作底栏 -->
            <ActionArea
                sticky
                layout="split"
                tone="solid"
                class="dynamic-detail__bottom-action"
            >
                <!-- 活动类型底栏 -->
                <template v-if="isActivity">
                    <view class="dynamic-detail__bottom-activity-bar">
                        <view class="dynamic-detail__bottom-activity-info">
                            <text class="dynamic-detail__bottom-activity-price">{{ activityPriceLabel }}</text>
                            <text class="dynamic-detail__bottom-activity-status">{{ activityStatusText }}</text>
                        </view>
                        <BaseButton
                            class="dynamic-detail__bottom-activity-btn"
                            :label="activityPrimaryLabel"
                            :variant="canRegisterActivity ? 'primary' : 'secondary'"
                            size="md"
                            height="76rpx"
                            :disabled="!canRegisterActivity && !hasActivityRegistration"
                            @click="handleActivityPrimaryAction"
                        />
                        <view class="dynamic-detail__bottom-mini-cluster">
                            <view
                                v-if="shouldShowCommentSection"
                                class="dynamic-detail__bottom-mini-btn"
                                @click="showCommentInput"
                            >
                                <BaseIcon name="chat" size="24" color="#5E564B" />
                            </view>
                            <view
                                class="dynamic-detail__bottom-mini-btn"
                                :class="{ 'is-active': detail.is_liked }"
                                @click="handleLike"
                            >
                                <BaseIcon
                                    :name="detail.is_liked ? 'like-fill' : 'like'"
                                    size="24"
                                    :color="detail.is_liked ? '#C6A15B' : '#5E564B'"
                                />
                            </view>
                            <button class="dynamic-detail__bottom-mini-btn" hover-class="none" open-type="share">
                                <BaseIcon name="share" size="24" color="#5E564B" />
                            </button>
                        </view>
                    </view>
                </template>

                <!-- 常规动态类型底栏 (图文、视频) -->
                <template v-else>
                    <view class="dynamic-detail__bottom-bar">
                        <!-- 快速评论输入框触发器 -->
                        <view
                            v-if="shouldShowCommentSection"
                            class="dynamic-detail__bottom-input-trigger"
                            @click="showCommentInput"
                        >
                            <BaseIcon name="edit" size="22" color="#8C8273" />
                            <text class="dynamic-detail__bottom-input-placeholder">
                                回复动态，分享你的想法...
                            </text>
                        </view>

                        <!-- 互动指标胶囊群 -->
                        <view class="dynamic-detail__bottom-cluster">
                            <view
                                class="dynamic-detail__bottom-cluster-item"
                                :class="{ 'is-active': detail.is_liked }"
                                @click="handleLike"
                            >
                                <BaseIcon
                                    :name="detail.is_liked ? 'like-fill' : 'like'"
                                    size="24"
                                    :color="detail.is_liked ? '#C6A15B' : '#5E564B'"
                                />
                                <text class="dynamic-detail__bottom-cluster-text">
                                    {{ formatCount(detail.like_count) }}
                                </text>
                            </view>

                            <view
                                v-if="shouldShowCommentSection"
                                class="dynamic-detail__bottom-cluster-item"
                                @click="showCommentInput"
                            >
                                <BaseIcon name="chat" size="24" color="#5E564B" />
                                <text class="dynamic-detail__bottom-cluster-text">
                                    {{ formatCount(detail.comment_count) }}
                                </text>
                            </view>

                            <button
                                class="dynamic-detail__bottom-cluster-item dynamic-detail__bottom-cluster-share"
                                hover-class="none"
                                open-type="share"
                            >
                                <BaseIcon name="share" size="24" color="#5E564B" />
                                <text class="dynamic-detail__bottom-cluster-text">分享</text>
                            </button>
                        </view>
                    </view>
                </template>
            </ActionArea>

            <!-- 活动报名抽屉 -->
            <BaseOverlayMask
                :show="showActivityRegister"
                :z-index="activityPopupMaskZIndex"
                :background="$theme.maskColor || 'rgba(24, 22, 20, 0.58)'"
                @close="showActivityRegister = false"
            />

            <TnPopup
                v-model="showActivityRegister"
                open-direction="bottom"
                :overlay="false"
                :safe-area-inset-bottom="true"
                :radius="32"
                height="74%"
                :z-index="activityPopupZIndex"
            >
                <view class="dynamic-detail__activity-popup">
                    <view class="dynamic-detail__popup-head">
                        <view>
                            <text class="dynamic-detail__popup-title">活动报名</text>
                            <text class="dynamic-detail__popup-subtitle">请填写参会联系人信息</text>
                        </view>
                        <view class="dynamic-detail__popup-close" @click="showActivityRegister = false">
                            <BaseIcon name="close" size="24" color="#8C8273" />
                        </view>
                    </view>
                    <scroll-view scroll-y class="dynamic-detail__activity-popup-body">
                        <view class="dynamic-detail__ticket-select-list">
                            <view
                                v-for="ticket in activityTickets"
                                :key="ticket.id"
                                class="dynamic-detail__ticket-select"
                                :class="{
                                    'is-active': selectedTicketId === Number(ticket.id),
                                    'is-disabled': !isTicketBuyable(ticket)
                                }"
                                @click="selectActivityTicket(ticket)"
                            >
                                <view class="dynamic-detail__ticket-copy">
                                    <text class="dynamic-detail__ticket-name">{{ ticket.name }}</text>
                                    <view class="dynamic-detail__ticket-tags">
                                        <text class="dynamic-detail__ticket-stock">
                                            {{ getTicketStatusText(ticket) }}
                                        </text>
                                        <text class="dynamic-detail__ticket-sale-time">
                                            {{ getTicketSaleCountdownText(ticket) }}
                                        </text>
                                    </view>
                                </view>
                                <text class="dynamic-detail__ticket-price">{{ ticket.price_label }}</text>
                            </view>
                        </view>

                        <view class="dynamic-detail__activity-form">
                            <view class="dynamic-detail__activity-field">
                                <text class="dynamic-detail__activity-field-label">联系人姓名</text>
                                <input
                                    v-model="activityForm.contact_name"
                                    class="dynamic-detail__activity-input"
                                    placeholder="请输入联系人姓名"
                                />
                            </view>
                            <view class="dynamic-detail__activity-field">
                                <text class="dynamic-detail__activity-field-label">手机号码</text>
                                <input
                                    v-model="activityForm.contact_mobile"
                                    class="dynamic-detail__activity-input"
                                    type="number"
                                    placeholder="请输入手机号"
                                />
                            </view>
                            <view class="dynamic-detail__activity-field">
                                <text class="dynamic-detail__activity-field-label">特别备注 (选填)</text>
                                <textarea
                                    v-model="activityForm.remark"
                                    class="dynamic-detail__activity-textarea"
                                    placeholder="请输入特别需求或备注"
                                    maxlength="120"
                                />
                            </view>
                        </view>
                    </scroll-view>
                    <ActionArea>
                        <BaseButton block size="lg" :loading="activitySubmitting" @click="submitActivityRegister">
                            {{ selectedTicketPayLabel }}
                        </BaseButton>
                    </ActionArea>
                </view>
            </TnPopup>

            <payment
                v-if="activityPayRegistrationId > 0"
                v-model:show="showActivityPay"
                v-model:show-check="showActivityPayCheck"
                :order-id="activityPayRegistrationId"
                from="activity_registration"
                redirect="/packages/pages/activity_registration/detail"
                @success="handleActivityPaySuccess"
                @fail="handleActivityPayFail"
            />

            <!-- 评论输入抽屉 -->
            <BaseOverlayMask
                :show="showComment"
                :z-index="commentPopupMaskZIndex"
                :background="$theme.maskColor || 'rgba(24, 22, 20, 0.58)'"
                @close="closeCommentPopup"
            />

            <TnPopup
                v-model="showComment"
                open-direction="bottom"
                :overlay="false"
                :safe-area-inset-bottom="true"
                :radius="32"
                height="66%"
                :z-index="commentPopupZIndex"
            >
                <view class="dynamic-detail__comment-drawer">
                    <view class="dynamic-detail__popup-head">
                        <text class="dynamic-detail__popup-title">
                            {{ replyTo ? `回复 @${replyTo.user_nickname}` : '发表评论' }}
                        </text>
                        <view class="dynamic-detail__popup-close" @click="closeCommentPopup">
                            <BaseIcon name="close" size="24" color="#8C8273" />
                        </view>
                    </view>
                    <view class="dynamic-detail__popup-body">
                        <view class="dynamic-detail__textarea-panel">
                            <textarea
                                v-model="commentContent"
                                class="dynamic-detail__textarea"
                                :placeholder="
                                    replyTo ? `回复 @${replyTo.user_nickname}` : '友善表达，分享美好看法...'
                                "
                                :maxlength="commentMaxLength"
                                :focus="commentFocused && showComment"
                                :selection-start="commentSelectionStart"
                                :selection-end="commentSelectionEnd"
                                cursor-spacing="120"
                                fixed
                                placeholder-class="dynamic-detail__textarea-placeholder"
                                @focus="handleCommentFocus"
                                @blur="handleCommentBlur"
                                @input="handleCommentInput"
                            />
                        </view>
                    </view>
                    <view
                        class="dynamic-detail__popup-footer"
                        :class="{ 'is-emoji-open': showEmojiPanel }"
                    >
                        <view class="dynamic-detail__popup-actions">
                            <text class="dynamic-detail__char-count">
                                {{ commentDisplayLength }}/{{ commentMaxLength }}
                            </text>
                            <view class="dynamic-detail__composer-actions">
                                <button
                                    class="dynamic-detail__emoji-btn"
                                    :class="{ 'is-active': showEmojiPanel }"
                                    @click="toggleEmojiPanel"
                                >
                                    😊 表情
                                </button>
                                <button
                                    class="dynamic-detail__submit-btn"
                                    :class="{ 'is-disabled': !canSubmitComment }"
                                    :disabled="!canSubmitComment"
                                    @click="submitComment"
                                >
                                    发送
                                </button>
                            </view>
                        </view>
                        <view
                            v-if="showEmojiPanel"
                            class="dynamic-detail__emoji-panel"
                            @tap.stop="() => {}"
                        >
                            <view
                                v-for="emoji in emojiList"
                                :key="emoji"
                                class="dynamic-detail__emoji-item"
                                @tap.stop="insertEmoji(emoji)"
                            >
                                <text class="dynamic-detail__emoji-char">{{ emoji }}</text>
                            </view>
                        </view>
                    </view>
                </view>
            </TnPopup>
        </view>

        <!-- 骨架加载中状态 -->
        <view v-else class="dynamic-detail__loading-view">
            <BaseCard variant="panel" scene="consumer" class="dynamic-detail__loading-card">
                <LoadingState text="正在探索动态大片..." tone="wedding" />
            </BaseCard>
        </view>
    </PageShell>
</template>

<script setup lang="ts">
import { remindBeforeOaAction } from '@/utils/oa-reminder'
import { computed, nextTick, ref, watch } from 'vue'
import { onLoad, onShareAppMessage, onUnload } from '@dcloudio/uni-app'
import TnPopup from '@tuniao/tnui-vue3-uniapp/components/popup/src/popup.vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'
import ActionArea from '@/components/base/ActionArea.vue'
import BaseButton from '@/components/base/BaseButton.vue'
import BaseCard from '@/components/base/BaseCard.vue'
import BaseIcon from '@/components/base/BaseIcon.vue'
import BaseNavbar from '@/components/base/BaseNavbar.vue'
import BaseOverlayMask from '@/components/base/BaseOverlayMask.vue'
import EmptyState from '@/components/base/EmptyState.vue'
import LoadingState from '@/components/base/LoadingState.vue'
import PageShell from '@/components/base/PageShell.vue'
import StatusBadge from '@/components/base/StatusBadge.vue'
import Payment from '@/components/payment/payment.vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'
import { useUserStore } from '@/stores/user'
import { DYNAMIC_LIST_REFRESH_KEY } from '@/enums/constantEnums'
import {
    getDynamicDetail,
    likeDynamic,
    getCommentList,
    addComment,
    deleteComment,
    likeComment,
    submitActivityRegistration
} from '@/api/dynamic'
import cache from '@/utils/cache'
import { confirmModal, showError, showSuccess } from '@/utils/feedback'
import {
    ensureMiniProgramReviewModeConfig,
    isMiniProgramReviewMode
} from '@/utils/miniProgramReviewMode'

const $theme = useThemeStore()
const appStore = useAppStore()
const userStore = useUserStore()
const navBarMetrics = useNavBarMetrics()
const commentPopupMaskZIndex = 20118
const commentPopupZIndex = 20120
const activityPopupMaskZIndex = 20128
const activityPopupZIndex = 20130
const userId = computed(() => userStore.userInfo?.id)
const miniProgramReviewMode = computed(() => isMiniProgramReviewMode())

type DynamicReplyItem = {
    id: string | number
    avatar: string
    nickname: string
    date: string
    content: string
    likeActive: boolean
    likeCount: number
    allowDelete: boolean
    replyUserNickname?: string
    user_id?: string | number
}

type DynamicCommentItem = DynamicReplyItem & {
    commentCount: number
    comment: DynamicReplyItem[]
    replyExpanded: boolean
    replyLoading: boolean
}

const dynamicId = ref(0)
const detail = ref<any>(null)
const mediaTab = ref<'video' | 'gallery'>('gallery')
const currentImageIndex = ref(0)
const showActivityRegister = ref(false)
const activitySubmitting = ref(false)
const selectedTicketId = ref(0)
const activityPayRegistrationId = ref(0)
const showActivityPay = ref(false)
const showActivityPayCheck = ref(false)
const activityNow = ref(Math.floor(Date.now() / 1000))
const activityForm = ref({
    contact_name: '',
    contact_mobile: '',
    remark: ''
})
const comments = ref<DynamicCommentItem[]>([])
const commentSort = ref('hot')
const commentPage = ref(1)
const commentHasMore = ref(true)
const commentLoading = ref(false)

const showComment = ref(false)
const commentContent = ref('')
const commentFocused = ref(false)
const showEmojiPanel = ref(false)
const commentSelectionStart = ref(0)
const commentSelectionEnd = ref(0)
const replyTo = ref<any>(null)
const parentComment = ref<any>(null)
const commentMaxLength = 500
let activityNowTimer: ReturnType<typeof setInterval> | null = null

const emojiList = [
    '😀', '😄', '😊', '😍', '😘', '🤗',
    '🤔', '😅', '😭', '😡', '😎', '🥳',
    '😴', '👍', '👏', '🙏', '😇', '🤍',
    '💖', '🔥', '✨', '🎉', '💐', '🌹'
]

const scrollStyle = computed(() => ({
    height: `calc(100vh - ${navBarMetrics.navBarHeight}px - 146rpx - env(safe-area-inset-bottom))`
}))

const commentDisplayLength = computed(() => Array.from(commentContent.value).length)
const canSubmitComment = computed(() => Boolean(commentContent.value.trim()))
const detailCommentCount = computed(() => Math.max(0, Number(detail.value?.comment_count || 0)))
const shouldShowCommentSection = computed(() => {
    if (!detail.value) return false
    if (miniProgramReviewMode.value) return false

    return Number(detail.value.allow_comment || 0) === 1 || detailCommentCount.value > 0
})

const detailTags = computed(() => {
    return Array.isArray(detail.value?.tags) ? detail.value.tags : []
})

const isActivity = computed(() => Number(detail.value?.dynamic_type || 0) === 4)
const activityInfo = computed(() => detail.value?.activity || {})
const activityTickets = computed(() => {
    const tickets = activityInfo.value?.tickets || []
    return Array.isArray(tickets) ? tickets : []
})
const hasActivityRegistration = computed(() => Number(activityInfo.value?.activity_has_registered || 0) === 1)
const canRegisterActivity = computed(() => Number(activityInfo.value?.activity_can_register || 0) === 1)
const activityPriceLabel = computed(() => String(activityInfo.value?.activity_price_label || '免费'))
const activityStatusText = computed(() => {
    if (hasActivityRegistration.value) {
        return String(activityInfo.value?.activity_registration_status_text || '已报名')
    }
    if (!canRegisterActivity.value) {
        return String(activityInfo.value?.activity_disabled_reason || '暂不可报名')
    }
    return '可报名'
})
const selectedTicket = computed(() =>
    activityTickets.value.find((item: any) => Number(item.id) === selectedTicketId.value)
)
const selectedTicketPayLabel = computed(() => {
    const ticket = selectedTicket.value
    if (!ticket) {
        return '选择票种'
    }
    if (!isTicketBuyable(ticket)) {
        return getTicketStatusText(ticket)
    }
    const price = Number(ticket.price || 0)
    return price > 0 ? `支付报名 ¥${price.toFixed(2)}` : '免费报名'
})
const activityPrimaryLabel = computed(() => {
    if (hasActivityRegistration.value) {
        return '查看我的活动'
    }
    if (!canRegisterActivity.value) {
        return activityStatusText.value
    }
    return '立即报名'
})
const activityMetaItems = computed(() => [
    {
        label: '开始时间',
        value: formatTimestamp(activityInfo.value?.activity_start_time)
    },
    {
        label: '报名截止',
        value: formatTimestamp(activityInfo.value?.activity_signup_deadline)
    },
    {
        label: '剩余名额',
        value:
            Number(activityInfo.value?.activity_total_quota || 0) > 0
                ? `${activityInfo.value?.activity_remaining_count || 0}/${activityInfo.value?.activity_total_quota}`
                : `${activityInfo.value?.activity_remaining_count || 0}`
    },
    {
        label: '报名状态',
        value: activityStatusText.value
    }
])

const isTicketBuyable = (ticket: any) => {
    return Number(ticket?.can_buy || 0) === 1 && Number(ticket?.remaining_count || 0) > 0
}

const getTicketStatusText = (ticket: any) => {
    const remaining = Number(ticket?.remaining_count || 0)
    if (remaining <= 0) {
        return '已售罄'
    }
    if (Number(ticket?.can_buy || 0) !== 1) {
        return String(ticket?.buy_disabled_reason || '暂不可购买')
    }
    return `余量 ${remaining}`
}

const formatDurationText = (seconds: number) => {
    const safeSeconds = Math.max(0, Math.floor(seconds))
    const days = Math.floor(safeSeconds / 86400)
    const hours = Math.floor((safeSeconds % 86400) / 3600)
    const minutes = Math.floor((safeSeconds % 3600) / 60)

    if (days > 0) {
        return `${days}天${hours > 0 ? `${hours}小时` : ''}`
    }
    if (hours > 0) {
        return `${hours}小时${minutes > 0 ? `${minutes}分钟` : ''}`
    }
    return `${Math.max(minutes, 1)}分钟`
}

const getTicketSaleCountdownText = (ticket: any) => {
    const now = activityNow.value
    const saleStartTime = Number(ticket?.sale_start_time || 0)
    const ticketSaleEndTime = Number(ticket?.sale_end_time || 0)
    const activityDeadline = Number(activityInfo.value?.activity_signup_deadline || 0)
    const saleEndTime = ticketSaleEndTime > 0 ? ticketSaleEndTime : activityDeadline

    if (saleStartTime > now) {
        return `还有 ${formatDurationText(saleStartTime - now)} 开售`
    }
    if (saleEndTime > now) {
        return `还有 ${formatDurationText(saleEndTime - now)} 截止`
    }
    if (saleEndTime > 0 && saleEndTime <= now) {
        return '已截止'
    }
    return ''
}

const normalizeTextValue = (value: unknown) => String(value ?? '').trim()

const normalizeMediaUrl = (value: unknown) => {
    const text = normalizeTextValue(value)
    if (!text) {
        return ''
    }
    if (/^(https?:)?\/\//.test(text) || text.startsWith('wxfile://')) {
        return text
    }
    return appStore.getImageUrl(text)
}

const pickImageUrl = (item: unknown) => {
    if (typeof item === 'string' || typeof item === 'number') {
        return normalizeMediaUrl(item)
    }

    if (item && typeof item === 'object') {
        const raw = item as Record<string, unknown>
        return normalizeMediaUrl(raw.url || raw.uri || raw.src || raw.value || raw.path)
    }

    return ''
}

const normalizeImageList = (images: any): string[] => {
    if (Array.isArray(images)) {
        return images.map((item) => pickImageUrl(item)).filter(Boolean)
    }

    if (typeof images === 'string') {
        const value = images.trim()
        if (!value) return []

        try {
            const parsed = JSON.parse(value)
            if (Array.isArray(parsed)) {
                return parsed.map((item) => pickImageUrl(item)).filter(Boolean)
            }
        } catch (error) {
            // ignore
        }

        return value
            .split(',')
            .map((item) => normalizeMediaUrl(item))
            .filter(Boolean)
    }

    return []
}

const previewImageUrls = computed(() => normalizeImageList(detail.value?.images))
const hasVideo = computed(() => Boolean(detail.value?.video))
const hasImages = computed(() => previewImageUrls.value.length > 0)
const imageCount = computed(() => previewImageUrls.value.length)

const authorStaffId = computed(() => {
    if (!detail.value) return 0
    if (detail.value.user_type === 2) {
        return Number(detail.value.staff_id || detail.value.user?.staff_id || detail.value.user_id || 0)
    }
    return 0
})

const handleGoStaff = () => {
    if (authorStaffId.value > 0) {
        uni.navigateTo({
            url: `/packages/pages/staff_detail/staff_detail?id=${authorStaffId.value}`
        })
    }
}

const handleSwiperChange = (event: any) => {
    currentImageIndex.value = Number(event?.detail?.current || 0)
}

const toNumber = (value: any) => Number(value || 0)

const authorMetaText = computed(() => {
    const parts: string[] = []
    if (detail.value?.create_time) {
        parts.push(`发布于 ${detail.value.create_time}`)
    }
    if (detail.value?.location) {
        parts.push(detail.value.location)
    }
    return parts.join(' · ') || '发布于刚刚'
})

const markDynamicListShouldRefresh = () => {
    cache.set(DYNAMIC_LIST_REFRESH_KEY, 1)
}

const getTypeText = (type: number) => {
    const texts: Record<number, string> = {
        1: '图文',
        2: '视频',
        3: '案例',
        4: '活动'
    }
    return texts[type] || ''
}

const getTypeTone = (type: number) => {
    const tones: Record<number, 'neutral' | 'info' | 'warning' | 'primary'> = {
        1: 'neutral',
        2: 'info',
        3: 'warning',
        4: 'primary'
    }
    return tones[type] || 'neutral'
}

const formatCount = (count: number) => {
    if (!count) return '0'
    if (count >= 10000) {
        return `${(count / 10000).toFixed(count >= 100000 ? 0 : 1).replace(/\.0$/, '')}万`
    }
    if (count >= 1000) {
        return `${(count / 1000).toFixed(1).replace(/\.0$/, '')}k`
    }
    return `${count}`
}

const padTimeUnit = (value: number) => `${value}`.padStart(2, '0')

const formatTimestamp = (value: unknown) => {
    const timestamp = Number(value || 0)
    if (!timestamp) return '-'
    const date = new Date(timestamp * 1000)
    return `${date.getFullYear()}-${padTimeUnit(date.getMonth() + 1)}-${padTimeUnit(
        date.getDate()
    )} ${padTimeUnit(date.getHours())}:${padTimeUnit(date.getMinutes())}`
}

const formatCommentTime = (time: string) => {
    const value = String(time || '').trim()
    if (!value) return '刚刚'

    const normalizedValue = value.includes('T') ? value : value.replace(' ', 'T')
    const timestamp = new Date(normalizedValue).getTime()

    if (Number.isNaN(timestamp)) {
        const [dateText = '', timeText = ''] = value.split(' ')
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateText)) {
            return `${dateText.slice(5)}${timeText ? ` ${timeText.slice(0, 5)}` : ''}`
        }
        return value
    }

    const diff = Date.now() - timestamp
    const minute = 60 * 1000
    const hour = 60 * minute
    const day = 24 * hour

    if (diff < minute) return '刚刚'
    if (diff < hour) return `${Math.floor(diff / minute)}分钟前`
    if (diff < day) return `${Math.floor(diff / hour)}小时前`
    if (diff < 7 * day) return `${Math.floor(diff / day)}天前`

    const date = new Date(timestamp)
    return `${padTimeUnit(date.getMonth() + 1)}-${padTimeUnit(date.getDate())} ${padTimeUnit(
        date.getHours()
    )}:${padTimeUnit(date.getMinutes())}`
}

const formatCommentLikeCount = (count: number) => `${Number(count || 0)}`

const getCommentAuthorText = (item: DynamicReplyItem) => {
    return item.replyUserNickname ? `${item.nickname} 回复` : item.nickname
}

const isCommentAuthor = (item: DynamicReplyItem) => {
    return Boolean(detail.value && item.user_id && detail.value.user_id === item.user_id)
}

const createCommentItem = (item: any): DynamicCommentItem => ({
    id: item.id,
    avatar: item.user?.avatar || '/static/images/user/default_avatar.png',
    nickname: item.user?.nickname || '匿名用户',
    date: item.create_time || '',
    content: item.content || '',
    likeActive: Boolean(item.is_liked),
    likeCount: toNumber(item.like_count),
    allowDelete: item.user_id === userId.value,
    commentCount: toNumber(item.reply_count),
    comment: [],
    replyExpanded: false,
    replyLoading: false,
    user_id: item.user_id
})

const createReplyItem = (reply: any): DynamicReplyItem => ({
    id: reply.id,
    avatar: reply.user_avatar || '/static/images/user/default_avatar.png',
    nickname: reply.user_nickname || '匿名用户',
    date: reply.create_time || '',
    content: reply.content || '',
    likeActive: Boolean(reply.is_liked),
    likeCount: toNumber(reply.like_count),
    allowDelete: reply.user_id === userId.value,
    replyUserNickname: reply.reply_user_nickname || '',
    user_id: reply.user_id
})

const findCommentLocation = (commentId: string | number) => {
    for (let commentIndex = 0; commentIndex < comments.value.length; commentIndex += 1) {
        const item = comments.value[commentIndex]
        if (item.id === commentId) {
            return { commentIndex, replyIndex: -1 }
        }
        const replyIndex = item.comment.findIndex((reply) => reply.id === commentId)
        if (replyIndex !== -1) {
            return { commentIndex, replyIndex }
        }
    }
    return null
}

const findCommentItem = (commentId: string | number) => {
    const location = findCommentLocation(commentId)
    if (!location) return null

    if (location.replyIndex === -1) {
        return comments.value[location.commentIndex]
    }

    return comments.value[location.commentIndex].comment[location.replyIndex]
}

const toggleLocalCommentLike = (commentId: string | number) => {
    const target = findCommentItem(commentId)
    if (!target) return

    target.likeActive = !target.likeActive
    target.likeCount = Math.max(0, Number(target.likeCount || 0) + (target.likeActive ? 1 : -1))
}

const getReplyToggleText = (item: DynamicCommentItem) => {
    if (item.replyLoading) return '加载中...'
    if (!item.replyExpanded) {
        const visibleCount = item.comment.length > 0 ? item.comment.length : item.commentCount
        return `查看 ${visibleCount} 条回复`
    }
    if (item.comment.length < item.commentCount) {
        return '加载更多回复'
    }
    return '收起回复'
}

const handleBack = () => {
    const pages = getCurrentPages()
    if (pages.length > 1) {
        uni.navigateBack()
        return
    }

    uni.switchTab({ url: '/pages/dynamic/dynamic' })
}

const handleTopicTagClick = (tag: string) => {
    uni.navigateTo({
        url: `/pages/dynamic/dynamic?tag=${encodeURIComponent(tag)}`
    })
}

const fetchDetail = async () => {
    try {
        const res = await getDynamicDetail({ id: dynamicId.value })

        let tags: string[] = []
        if (res.tags) {
            if (typeof res.tags === 'string') {
                tags = res.tags
                    .split(',')
                    .map((tag: string) => tag.trim())
                    .filter(Boolean)
            } else if (Array.isArray(res.tags)) {
                tags = res.tags
            }
        }

        detail.value = {
            ...res,
            images: normalizeImageList(res.images),
            tags,
            is_liked: Boolean(res.is_liked),
            like_count: toNumber(res.like_count),
            comment_count: toNumber(res.comment_count),
            view_count: toNumber(res.view_count),
            video: res.video_url || res.video || '',
            video_cover: res.video_cover || ''
        }

        if (detail.value?.video) {
            mediaTab.value = 'video'
        } else {
            mediaTab.value = 'gallery'
        }
    } catch (error: any) {
        showError(error, '加载失败')
        setTimeout(() => {
            handleBack()
        }, 1500)
    }
}

const fetchComments = async (refresh = false) => {
    if (commentLoading.value) return
    commentLoading.value = true

    try {
        if (refresh) {
            commentPage.value = 1
            comments.value = []
        }

        const res = await getCommentList({
            dynamic_id: dynamicId.value,
            page: commentPage.value,
            page_size: 20,
            sort: commentSort.value
        })

        const list = (res.data || []).map(createCommentItem)

        if (refresh) {
            comments.value = list
        } else {
            comments.value.push(...list)
        }

        commentHasMore.value = list.length === 20
    } catch (error) {
        console.error(error)
    } finally {
        commentLoading.value = false
    }
}

const changeCommentSort = (sort: string) => {
    if (commentSort.value === sort) return
    commentSort.value = sort
    fetchComments(true)
}

const loadMoreComments = () => {
    if (commentHasMore.value && !commentLoading.value) {
        commentPage.value += 1
        fetchComments()
    }
}

const loadMoreReplies = async (item: DynamicCommentItem) => {
    if (item.replyLoading) return
    item.replyLoading = true

    try {
        const res = await getCommentList({
            dynamic_id: dynamicId.value,
            parent_id: item.id,
            page: Math.floor(item.comment.length / 20) + 1,
            page_size: 20
        })

        const replyList = (res.data || []).map(createReplyItem)
        item.comment.push(...replyList)
        item.replyExpanded = true
    } catch (error: any) {
        showError(error, '加载失败')
    } finally {
        item.replyLoading = false
    }
}

const toggleReplies = (item: DynamicCommentItem) => {
    if (item.replyLoading) return

    if (!item.replyExpanded) {
        if (item.comment.length === 0) {
            loadMoreReplies(item)
            return
        }
        item.replyExpanded = true
        return
    }

    if (item.comment.length < item.commentCount) {
        loadMoreReplies(item)
        return
    }

    item.replyExpanded = false
}

const handleLike = async () => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeDynamic({ id: dynamicId.value })
        detail.value.is_liked = !detail.value.is_liked
        detail.value.like_count += detail.value.is_liked ? 1 : -1
        markDynamicListShouldRefresh()
    } catch (error: any) {
        showError(error)
    }
}

const handleLikeComment = async (id: string | number) => {
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    try {
        await likeComment({ id })
        toggleLocalCommentLike(id)
    } catch (error: any) {
        showError(error)
    }
}

const handleActivityPrimaryAction = () => {
    if (hasActivityRegistration.value) {
        const registrationId = Number(activityInfo.value?.activity_registration_id || 0)
        uni.navigateTo({
            url: registrationId > 0
                ? `/packages/pages/activity_registration/detail?id=${registrationId}`
                : '/packages/pages/my_activity/my_activity'
        })
        return
    }
    if (!canRegisterActivity.value) {
        showError(activityStatusText.value || '暂不可报名')
        return
    }
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
    const firstAvailableTicket = activityTickets.value.find((item: any) => isTicketBuyable(item))
    selectedTicketId.value = firstAvailableTicket ? Number(firstAvailableTicket.id) : 0
    activityForm.value.contact_name = userStore.userInfo?.nickname || ''
    activityForm.value.contact_mobile = userStore.userInfo?.mobile || ''
    activityForm.value.remark = ''
    showActivityRegister.value = true
}

const selectActivityTicket = (ticket: any) => {
    if (!isTicketBuyable(ticket)) {
        showError(getTicketStatusText(ticket))
        return
    }
    selectedTicketId.value = Number(ticket.id)
}

const submitActivityRegister = async () => {
    if (activitySubmitting.value) return
    if (!selectedTicketId.value) {
        showError('请选择票种')
        return
    }
    if (!isTicketBuyable(selectedTicket.value)) {
        showError(getTicketStatusText(selectedTicket.value))
        return
    }
    if (!activityForm.value.contact_name.trim()) {
        showError('请填写联系人')
        return
    }
    if (!activityForm.value.contact_mobile.trim()) {
        showError('请填写手机号')
        return
    }

    activitySubmitting.value = true
    try {
        if (!await remindBeforeOaAction()) return
        const res = await submitActivityRegistration({
            dynamic_id: dynamicId.value,
            ticket_id: selectedTicketId.value,
            ...activityForm.value
        })
        showActivityRegister.value = false
        await fetchDetail()
        markDynamicListShouldRefresh()
        const registrationId = Number(res?.registration_id || 0)
        if (Number(res?.need_pay || 0) === 1 && registrationId > 0) {
            activityPayRegistrationId.value = registrationId
            showActivityPay.value = true
            return
        }
        showSuccess('报名成功')
        uni.navigateTo({ url: '/packages/pages/my_activity/my_activity' })
    } catch (error: any) {
        showError(error, '报名失败')
    } finally {
        activitySubmitting.value = false
    }
}

const handleActivityPaySuccess = async () => {
    showActivityPay.value = false
    showActivityPayCheck.value = false
    await fetchDetail()
    markDynamicListShouldRefresh()
    uni.navigateTo({ url: `/packages/pages/activity_registration/detail?id=${activityPayRegistrationId.value}` })
}

const handleActivityPayFail = () => {
    showActivityPay.value = false
    showActivityPayCheck.value = false
    showError('支付未完成，可在我的活动继续支付')
}

const resetCommentSelection = (value = commentContent.value) => {
    const end = value.length
    commentSelectionStart.value = end
    commentSelectionEnd.value = end
}

const syncCommentSelection = (detail?: Record<string, any>) => {
    const valueLength = commentContent.value.length
    const fallbackCursor =
        typeof detail?.cursor === 'number' ? detail.cursor : commentSelectionEnd.value
    const start =
        typeof detail?.selectionStart === 'number' ? detail.selectionStart : fallbackCursor
    const end = typeof detail?.selectionEnd === 'number' ? detail.selectionEnd : fallbackCursor

    commentSelectionStart.value = Math.max(0, Math.min(start, valueLength))
    commentSelectionEnd.value = Math.max(0, Math.min(end, valueLength))
}

const focusCommentInput = () => {
    showEmojiPanel.value = false
    commentFocused.value = false
    nextTick(() => {
        commentFocused.value = true
    })
}

const closeCommentPopup = () => {
    commentFocused.value = false
    uni.hideKeyboard()
    showComment.value = false
}

const handleCommentFocus = (event: any) => {
    commentFocused.value = true
    showEmojiPanel.value = false
    syncCommentSelection(event?.detail)
}

const handleCommentBlur = (event: any) => {
    commentFocused.value = false
    syncCommentSelection(event?.detail)
}

const handleCommentInput = (event: any) => {
    commentContent.value = event?.detail?.value ?? ''
    syncCommentSelection(event?.detail)
}

const toggleEmojiPanel = () => {
    if (showEmojiPanel.value) {
        focusCommentInput()
        return
    }

    commentFocused.value = false
    uni.hideKeyboard()
    setTimeout(() => {
        if (showComment.value) {
            showEmojiPanel.value = true
        }
    }, 80)
}

const insertEmoji = (emoji: string) => {
    const start = Math.max(0, Math.min(commentSelectionStart.value, commentContent.value.length))
    const end = Math.max(start, Math.min(commentSelectionEnd.value, commentContent.value.length))
    const nextValue = `${commentContent.value.slice(0, start)}${emoji}${commentContent.value.slice(
        end
    )}`

    if (Array.from(nextValue).length > commentMaxLength) {
        showError(`评论内容最多 ${commentMaxLength} 个字符`)
        return
    }

    commentContent.value = nextValue
    const nextCursor = start + emoji.length
    commentSelectionStart.value = nextCursor
    commentSelectionEnd.value = nextCursor
}

const showCommentInput = () => {
    if (miniProgramReviewMode.value) {
        return
    }

    if (detail.value.allow_comment !== 1) {
        showError('该动态不允许评论')
        return
    }

    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }

    replyTo.value = null
    parentComment.value = null
    commentContent.value = ''
    showEmojiPanel.value = false
    commentFocused.value = false
    resetCommentSelection('')
    showComment.value = true
}

const replyComment = (comment: DynamicCommentItem | DynamicReplyItem) => {
    if (miniProgramReviewMode.value) {
        return
    }

    if (detail.value.allow_comment !== 1) {
        showError('该动态不允许评论')
        return
    }

    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }

    replyTo.value = { id: comment.id, user_nickname: comment.nickname }
    parentComment.value = comment
    commentContent.value = ''
    showEmojiPanel.value = false
    commentFocused.value = false
    resetCommentSelection('')
    showComment.value = true
}

const submitComment = async () => {
    if (miniProgramReviewMode.value) {
        return
    }

    const submittedContent = commentContent.value.trim()
    if (!submittedContent) return

    try {
        const params: any = {
            dynamic_id: dynamicId.value,
            content: submittedContent
        }

        if (replyTo.value) {
            params.parent_id = parentComment.value.id
            params.reply_user_id = replyTo.value.id
        }

        const res = await addComment(params)

        showSuccess('评论成功')

        if (replyTo.value) {
            const location = findCommentLocation(parentComment.value.id)
            const newReplyData: DynamicReplyItem = {
                id: res.comment_id || Date.now(),
                avatar: userStore.userInfo?.avatar || '/static/images/user/default_avatar.png',
                nickname: userStore.userInfo?.nickname || '我',
                date: '刚刚',
                content: submittedContent,
                likeActive: false,
                likeCount: 0,
                allowDelete: true,
                replyUserNickname: replyTo.value.user_nickname,
                user_id: userId.value
            }

            if (location) {
                const parent = comments.value[location.commentIndex]
                parent.replyExpanded = true

                if (location.replyIndex === -1) {
                    parent.comment.unshift(newReplyData)
                } else {
                    parent.comment.splice(location.replyIndex + 1, 0, newReplyData)
                }

                parent.commentCount += 1
            } else {
                fetchComments(true)
            }
        } else {
            const newCommentData: DynamicCommentItem = {
                id: res.comment_id || Date.now(),
                avatar: userStore.userInfo?.avatar || '/static/images/user/default_avatar.png',
                nickname: userStore.userInfo?.nickname || '我',
                date: '刚刚',
                content: submittedContent,
                likeActive: false,
                likeCount: 0,
                allowDelete: true,
                commentCount: 0,
                comment: [],
                replyExpanded: false,
                replyLoading: false,
                user_id: userId.value
            }

            comments.value.unshift(newCommentData)
        }

        commentContent.value = ''
        resetCommentSelection('')
        showComment.value = false
        replyTo.value = null
        parentComment.value = null
        detail.value.comment_count += 1
        markDynamicListShouldRefresh()
    } catch (error: any) {
        showError(error, '评论失败')
    }
}

const deleteCommentItem = async (id: string | number) => {
    const confirmed = await confirmModal({
        title: '提示',
        content: '确定要删除该评论吗？'
    })
    if (confirmed) {
        try {
            await deleteComment({ comment_id: id })
            showSuccess('删除成功')

            const location = findCommentLocation(id)
            if (location) {
                if (location.replyIndex === -1) {
                    const [removedComment] = comments.value.splice(location.commentIndex, 1)
                    const removedCount = 1 + Number(removedComment?.commentCount || 0)
                    detail.value.comment_count = Math.max(
                        0,
                        Number(detail.value.comment_count || 0) - removedCount
                    )
                } else {
                    const parent = comments.value[location.commentIndex]
                    parent.comment.splice(location.replyIndex, 1)
                    parent.commentCount = Math.max(0, Number(parent.commentCount || 0) - 1)
                    detail.value.comment_count = Math.max(
                        0,
                        Number(detail.value.comment_count || 0) - 1
                    )
                }
            }

            markDynamicListShouldRefresh()
        } catch (error: any) {
            showError(error, '删除失败')
        }
    }
}

const previewImage = (currentImageOrIndex: string | number) => {
    const previewImages = previewImageUrls.value
    if (previewImages.length === 0) {
        return
    }
    let currentUrl = ''
    if (typeof currentImageOrIndex === 'number') {
        currentUrl = previewImages[currentImageOrIndex] || previewImages[0]
    } else {
        currentUrl = normalizeMediaUrl(currentImageOrIndex)
    }

    uni.previewImage({
        urls: previewImages,
        current: currentUrl && previewImages.includes(currentUrl) ? currentUrl : previewImages[0],
        fail: () => {
            showError('图片预览失败')
        }
    })
}

onLoad((options: any) => {
    ensureMiniProgramReviewModeConfig()
    activityNow.value = Math.floor(Date.now() / 1000)
    if (!activityNowTimer) {
        activityNowTimer = setInterval(() => {
            activityNow.value = Math.floor(Date.now() / 1000)
        }, 60 * 1000)
    }
    if (options.id) {
        dynamicId.value = Number(options.id)
        fetchDetail()
        fetchComments(true)
    }
    if (options.action === 'comment') {
        setTimeout(() => {
            showCommentInput()
        }, 500)
    }
})

onShareAppMessage(() => ({
    title: detail.value?.content?.slice(0, 30) || '精彩动态',
    path: `/packages/pages/dynamic_detail/dynamic_detail?id=${dynamicId.value}`
}))

onUnload(() => {
    if (activityNowTimer) {
        clearInterval(activityNowTimer)
        activityNowTimer = null
    }
})

watch(showComment, (visible) => {
    if (visible) {
        showEmojiPanel.value = false
        nextTick(() => {
            resetCommentSelection()
            commentFocused.value = true
        })
        return
    }

    showEmojiPanel.value = false
    commentFocused.value = false
    commentContent.value = ''
    replyTo.value = null
    parentComment.value = null
    resetCommentSelection('')
})
</script>

<style lang="scss" scoped>
@import '../../../styles/dynamic.scss';

.dynamic-detail {
    background: transparent;

    &__scroll {
        width: 100%;
    }

    &__content {
        display: flex;
        flex-direction: column;
        width: 100%;
        box-sizing: border-box;
    }

    /* 页面卡片主体区：提供统一规范的四周留白与卡片间距 */
    &__body {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 24rpx;
        padding: 24rpx var(--wm-space-page-x, 28rpx) 40rpx;
        box-sizing: border-box;
    }

    /* 顶部沉浸式多媒体展厅 */
    &__hero-showcase {
        position: relative;
        width: 100%;
        background: #181614;
        overflow: hidden;
    }

    &__media-switch {
        position: absolute;
        top: 24rpx;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        display: flex;
        align-items: center;
        gap: 6rpx;
        padding: 6rpx 8rpx;
        border-radius: 999rpx;
        background: rgba(24, 22, 20, 0.65);
        border: 1rpx solid rgba(217, 190, 130, 0.45);
        backdrop-filter: blur(12rpx);
        -webkit-backdrop-filter: blur(12rpx);
        box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.35);

        &-btn {
            display: inline-flex;
            align-items: center;
            gap: 6rpx;
            padding: 8rpx 20rpx;
            border-radius: 999rpx;
            font-size: 22rpx;
            font-weight: 500;
            color: #FFFDF8;
            transition: all 0.22s ease;

            &.is-active {
                background: #D9BE82;
                color: #181614;
                font-weight: 700;
            }
        }
    }

    &__hero-video-box {
        width: 100%;
        height: 520rpx;
        background: #000000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &__hero-video {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__hero-swiper-box {
        position: relative;
        width: 100%;
        height: 640rpx;
        background: #181614;
    }

    &__hero-swiper {
        width: 100%;
        height: 100%;
    }

    &__hero-swiper-item {
        width: 100%;
        height: 100%;
    }

    &__hero-swiper-image {
        width: 100%;
        height: 100%;
        display: block;
    }

    &__hero-overlay {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 120rpx;
        background: linear-gradient(180deg, transparent 0%, rgba(24, 22, 20, 0.68) 100%);
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        padding: 0 32rpx 24rpx;
        box-sizing: border-box;
        pointer-events: none;
    }

    &__hero-indicator {
        pointer-events: auto;
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 8rpx 20rpx;
        border-radius: 999rpx;
        background: rgba(24, 22, 20, 0.6);
        border: 1rpx solid rgba(217, 190, 130, 0.5);
        color: #FFFDF8;
        font-size: 21rpx;
        font-weight: 500;
        backdrop-filter: blur(8rpx);
        -webkit-backdrop-filter: blur(8rpx);

        &:active {
            opacity: 0.85;
        }
    }

    &__hero-counter {
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        padding: 6rpx 18rpx;
        border-radius: 999rpx;
        background: rgba(24, 22, 20, 0.6);
        border: 1rpx solid rgba(255, 255, 255, 0.2);
        color: #FFFDF8;
        font-size: 22rpx;
        font-weight: 600;
        backdrop-filter: blur(8rpx);
        -webkit-backdrop-filter: blur(8rpx);
    }

    &__hero-current {
        color: #D9BE82;
        font-weight: 700;
    }

    &__hero-divider {
        color: rgba(255, 255, 255, 0.4);
        font-size: 20rpx;
    }

    &__hero-total {
        color: #FFFDF8;
    }

    /* 主体叙事卡片 */
    &__lead-card,
    &__activity-card,
    &__comments-card {
        width: 100%;
        border-radius: 32rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        background: #FFFFFF;
        box-shadow: 0 16rpx 36rpx rgba(74, 43, 24, 0.06);
        box-sizing: border-box;
    }

    &__lead-card {
        width: 100%;
    }

    &__lead-body {
        display: flex;
        flex-direction: column;
        gap: 22rpx;
        width: 100%;
    }

    &__lead-head {
        display: flex;
        align-items: center;
        gap: 20rpx;
    }

    &__avatar {
        width: 88rpx;
        height: 88rpx;
        flex-shrink: 0;
        border-radius: 50%;
        background: #FAF6EE;
        border: 2rpx solid #D9BE82;
        box-shadow: 0 6rpx 16rpx rgba(74, 43, 24, 0.1);
    }

    &__author-copy {
        flex: 1;
        min-width: 0;
    }

    &__author-row {
        display: flex;
        align-items: center;
        gap: 12rpx;
        flex-wrap: wrap;
    }

    &__author-title {
        min-width: 0;
        display: flex;
        align-items: center;
    }

    &__author-name {
        max-width: 100%;
        font-size: 32rpx;
        line-height: 1.35;
        font-weight: 700;
        color: #181614;
        @include dynamic-line-clamp(1);
    }

    &__author-badges {
        display: flex;
        align-items: center;
        gap: 8rpx;
        flex-wrap: wrap;
    }

    &__author-meta {
        display: block;
        margin-top: 6rpx;
        font-size: 22rpx;
        line-height: 1.4;
        color: #8C8273;
        @include dynamic-line-clamp(1);
    }

    &__staff-action {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 4rpx;
        height: 56rpx;
        padding: 0 20rpx;
        border-radius: 999rpx;
        background: #FDF8ED;
        border: 1rpx solid #D9BE82;
        box-shadow: 0 4rpx 12rpx rgba(217, 190, 130, 0.15);
        transition: all 0.2s ease;

        &:active {
            transform: scale(0.95);
            background: #F8EDD8;
        }

        &-text {
            font-size: 22rpx;
            font-weight: 700;
            color: #9A6B35;
            line-height: 1;
        }
    }

    &__content-text {
        display: block;
        font-size: 30rpx;
        line-height: 1.74;
        font-weight: 400;
        color: #2C261E;
        white-space: pre-wrap;
        word-break: break-word;
    }

    &__summary-row {
        display: flex;
        align-items: center;
        gap: 12rpx;
        flex-wrap: wrap;
        padding-top: 6rpx;
    }

    &__detail-meta-item {
        min-height: 48rpx;
        padding: 0 18rpx;
        border-radius: 999rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.8);
        background: #FAF8F5;
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        color: #8C8273;
        font-size: 22rpx;
        font-weight: 500;
        line-height: 1;
    }

    &__topic-tag {
        display: inline-flex;
        align-items: center;
        min-height: 48rpx;
        padding: 0 20rpx;
        border-radius: 999rpx;
        background: #FAF6EE;
        border: 1rpx solid rgba(217, 190, 130, 0.6);
        color: #9A6B35;
        font-size: 22rpx;
        font-weight: 500;
        line-height: 1;
        transition: all 0.2s ease;

        &:active {
            background: #F1E5C8;
        }
    }

    /* 活动专属邀请函卡片 */
    &__activity-card {
        width: 100%;
    }

    &__activity-body {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
        width: 100%;
    }

    &__activity-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16rpx;

        &-left {
            display: flex;
            align-items: flex-start;
            gap: 16rpx;
        }
    }

    &__activity-stamp {
        padding: 4rpx 12rpx;
        border-radius: 8rpx;
        background: #181614;
        border: 1rpx solid #D9BE82;

        &-text {
            font-size: 18rpx;
            font-weight: 800;
            color: #D9BE82;
            letter-spacing: 1rpx;
        }
    }

    &__activity-title {
        display: block;
        font-size: 30rpx;
        line-height: 1.35;
        font-weight: 700;
        color: #181614;
    }

    &__activity-subtitle {
        display: block;
        margin-top: 6rpx;
        font-size: 22rpx;
        color: #8C8273;
    }

    &__activity-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12rpx;
        margin-top: 4rpx;
    }

    &__activity-meta-item {
        padding: 16rpx 20rpx;
        border-radius: 18rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.7);
        background: #FAF8F5;
        display: flex;
        flex-direction: column;
        gap: 6rpx;
    }

    &__activity-meta-label {
        font-size: 21rpx;
        color: #8C8273;
    }

    &__activity-meta-value {
        font-size: 24rpx;
        line-height: 1.35;
        font-weight: 700;
        color: #181614;
    }

    &__ticket-list,
    &__ticket-select-list {
        display: flex;
        flex-direction: column;
        gap: 14rpx;
        margin-top: 8rpx;
    }

    &__ticket-item,
    &__ticket-select {
        padding: 20rpx 24rpx;
        border-radius: 20rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        background: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
        box-sizing: border-box;
        transition: all 0.2s ease;
    }

    &__ticket-select.is-active {
        border-color: #D9BE82;
        background: #FDF9F2;
        box-shadow: 0 8rpx 20rpx rgba(217, 190, 130, 0.12);
    }

    &__ticket-item.is-disabled,
    &__ticket-select.is-disabled {
        opacity: 0.52;
    }

    &__ticket-copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8rpx;
    }

    &__ticket-name {
        font-size: 28rpx;
        line-height: 1.35;
        font-weight: 700;
        color: #181614;
    }

    &__ticket-tags {
        display: flex;
        align-items: center;
        gap: 10rpx;
        flex-wrap: wrap;
    }

    &__ticket-stock {
        font-size: 21rpx;
        color: #8C8273;
    }

    &__ticket-sale-time {
        padding: 4rpx 12rpx;
        border-radius: 999rpx;
        background: #FAF6EE;
        border: 1rpx solid rgba(217, 190, 130, 0.4);
        font-size: 20rpx;
        color: #9A6B35;
    }

    &__ticket-price {
        flex-shrink: 0;
        font-size: 30rpx;
        font-weight: 700;
        color: #181614;
    }

    &__ticket-empty {
        padding: 24rpx;
        border-radius: 20rpx;
        border: 1rpx dashed rgba(231, 224, 211, 0.85);
        background: #FAF8F5;
        color: #8C8273;
        font-size: 24rpx;
        text-align: center;
    }

    /* 评论卡片 */
    &__comments-card {
        width: 100%;
    }

    &__comments-body {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    &__comments-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 18rpx;
        border-bottom: 1rpx solid rgba(231, 224, 211, 0.7);
    }

    &__comments-title {
        font-size: 30rpx;
        font-weight: 700;
        color: #181614;
    }

    &__comments-head-right {
        display: flex;
        align-items: center;
        gap: 16rpx;
    }

    &__comments-write-btn {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        height: 48rpx;
        padding: 0 20rpx;
        border-radius: 999rpx;
        background: #FAF6EE;
        border: 1rpx solid rgba(217, 190, 130, 0.75);
        color: #9A6B35;
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1;
        box-shadow: 0 2rpx 8rpx rgba(217, 190, 130, 0.15);
        transition: all 0.2s ease;

        &:active {
            background: #F4E8CF;
            transform: scale(0.96);
        }
    }

    &__comments-sort-segmented {
        display: inline-flex;
        align-items: center;
        background: #FAF6EE;
        border: 1rpx solid rgba(231, 224, 211, 0.8);
        border-radius: 999rpx;
        padding: 4rpx;
    }

    &__sort-tab {
        padding: 6rpx 20rpx;
        border-radius: 999rpx;
        font-size: 22rpx;
        font-weight: 500;
        color: #8C8273;
        transition: all 0.2s ease;

        &.is-active {
            background: #FFFFFF;
            color: #181614;
            font-weight: 700;
            box-shadow: 0 4rpx 10rpx rgba(74, 43, 24, 0.06);
        }
    }

    &__comment-empty {
        padding: 24rpx 0 16rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20rpx;
    }

    &__comment-empty-btn {
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        height: 64rpx;
        padding: 0 32rpx;
        border-radius: 999rpx;
        background: #FAF6EE;
        border: 1rpx solid #D9BE82;
        color: #9A6B35;
        font-size: 24rpx;
        font-weight: 700;
        box-shadow: 0 6rpx 16rpx rgba(217, 190, 130, 0.16);
        transition: all 0.2s ease;

        &:active {
            background: #F1E5C8;
            transform: scale(0.96);
        }
    }

    &__comment-stack {
        display: flex;
        flex-direction: column;
    }

    &__comment-item {
        display: flex;
        align-items: flex-start;
        gap: 18rpx;
        padding: 24rpx 0 20rpx;

        & + & {
            border-top: 1rpx solid rgba(231, 224, 211, 0.55);
        }
    }

    &__comment-avatar {
        width: 68rpx;
        height: 68rpx;
        flex-shrink: 0;
        border-radius: 50%;
        background: #FAF6EE;
        border: 1.5rpx solid #D9BE82;

        &--reply {
            width: 52rpx;
            height: 52rpx;
        }
    }

    &__comment-body {
        flex: 1;
        min-width: 0;
    }

    &__comment-main {
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__comment-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__comment-author-box {
        display: flex;
        align-items: center;
        gap: 8rpx;
        min-width: 0;
        flex: 1;
    }

    &__comment-author {
        font-size: 26rpx;
        font-weight: 700;
        color: #181614;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    &__reply-target {
        font-size: 22rpx;
        color: #8C8273;
    }

    &__comment-meta-right {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        flex-shrink: 0;
    }

    &__comment-time {
        font-size: 21rpx;
        color: #8C8273;
    }

    &__comment-like-pill {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 4rpx 12rpx;
        border-radius: 999rpx;
        background: #FAF8F5;
        border: 1rpx solid rgba(231, 224, 211, 0.6);
        color: #8C8273;
        font-size: 20rpx;
        line-height: 1;
        transition: all 0.2s ease;

        &.is-active {
            color: #C6A15B;
            border-color: rgba(217, 190, 130, 0.6);
            background: #FDF9F2;
        }
    }

    &__comment-content {
        font-size: 26rpx;
        line-height: 1.68;
        color: #2C261E;
        white-space: pre-wrap;
        word-break: break-word;
    }

    &__comment-actions {
        display: inline-flex;
        align-items: center;
        gap: 18rpx;
        margin-top: 8rpx;
    }

    &__comment-action {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 6rpx 18rpx;
        border-radius: 999rpx;
        background: #FAF8F5;
        border: 1rpx solid rgba(231, 224, 211, 0.8);
        font-size: 22rpx;
        color: #5E564B;
        font-weight: 600;
        transition: all 0.2s ease;

        &:active {
            color: #181614;
            background: #F2ECE1;
            transform: scale(0.96);
        }

        &.is-danger {
            color: #B84A39;
            border-color: rgba(184, 74, 57, 0.25);
            background: #FDF5F4;
        }
    }

    &__reply-list {
        margin-top: 16rpx;
        padding: 16rpx 20rpx;
        border-radius: 20rpx;
        background: #FAF8F5;
        border: 1rpx solid rgba(231, 224, 211, 0.65);
        display: flex;
        flex-direction: column;
        gap: 16rpx;
    }

    &__reply-item {
        display: flex;
        align-items: flex-start;
        gap: 14rpx;
    }

    &__reply-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        margin-top: 14rpx;
        font-size: 22rpx;
        font-weight: 600;
        color: #9A6B35;
    }

    &__comment-more {
        padding: 24rpx 0 8rpx;
        display: flex;
        justify-content: center;
    }

    &__more-pill {
        display: inline-flex;
        align-items: center;
        gap: 6rpx;
        padding: 10rpx 28rpx;
        border-radius: 999rpx;
        background: #FAF8F5;
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        font-size: 22rpx;
        color: #5E564B;
        font-weight: 500;
    }

    &__loading-mini {
        display: inline-flex;
        align-items: center;
        gap: 10rpx;
        font-size: 22rpx;
        color: #8C8273;
    }

    &__mini-spinner {
        width: 20rpx;
        height: 20rpx;
        border: 2.5rpx solid rgba(217, 190, 130, 0.3);
        border-top-color: #C6A15B;
        border-radius: 50%;
        animation: miniRotate 0.8s linear infinite;
    }

    @keyframes miniRotate {
        to {
            transform: rotate(360deg);
        }
    }

    /* 底部悬浮操作栏 */
    &__bottom-action {
        z-index: 90;
        padding: 14rpx 24rpx calc(14rpx + env(safe-area-inset-bottom));
        background: rgba(255, 255, 255, 0.96);
        border-top: 1rpx solid rgba(231, 224, 211, 0.85);
        backdrop-filter: blur(20rpx);
        -webkit-backdrop-filter: blur(20rpx);
        box-shadow: 0 -8rpx 24rpx rgba(74, 43, 24, 0.05);
    }

    &__bottom-bar {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20rpx;
    }

    &__bottom-input-trigger {
        flex: 1;
        min-width: 0;
        height: 72rpx;
        padding: 0 24rpx;
        border-radius: 999rpx;
        background: #FAF8F5;
        border: 1rpx solid rgba(231, 224, 211, 0.9);
        display: flex;
        align-items: center;
        gap: 10rpx;
        transition: all 0.2s ease;

        &:active {
            background: #F2ECE1;
        }

        &-placeholder {
            font-size: 24rpx;
            color: #8C8273;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    &__bottom-cluster {
        display: inline-flex;
        align-items: center;
        gap: 16rpx;
        flex-shrink: 0;

        &-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2rpx;
            min-width: 64rpx;
            transition: all 0.2s ease;

            &:active {
                transform: scale(0.92);
            }

            &.is-active {
                :deep(.base-icon) {
                    @include dynamic-heart-pulse;
                }
                .dynamic-detail__bottom-cluster-text {
                    color: #C6A15B;
                    font-weight: 700;
                }
            }
        }

        &-text {
            font-size: 20rpx;
            line-height: 1;
            color: #5E564B;
        }

        &-share {
            padding: 0;
            margin: 0;
            background: transparent;
            border: none;
            line-height: normal;

            &::after {
                display: none;
            }
        }
    }

    /* 活动专属底栏 */
    &__bottom-activity-bar {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__bottom-activity-info {
        display: flex;
        flex-direction: column;
        gap: 4rpx;
    }

    &__bottom-activity-price {
        font-size: 34rpx;
        font-weight: 800;
        color: #181614;
        line-height: 1.1;
    }

    &__bottom-activity-status {
        font-size: 20rpx;
        color: #8C8273;
    }

    &__bottom-activity-btn {
        flex: 1;
        min-width: 0;
    }

    &__bottom-mini-cluster {
        display: inline-flex;
        align-items: center;
        gap: 10rpx;
        flex-shrink: 0;
    }

    &__bottom-mini-btn {
        width: 68rpx;
        height: 68rpx;
        border-radius: 50%;
        background: #FAF8F5;
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        margin: 0;

        &::after {
            display: none;
        }

        &:active {
            background: #F2ECE1;
            transform: scale(0.92);
        }

        &.is-active {
            border-color: rgba(217, 190, 130, 0.8);
            background: #FDF9F2;
        }
    }

    /* 抽屉弹窗公用 */
    &__popup-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: 32rpx 32rpx 20rpx;
        border-bottom: 1rpx solid rgba(231, 224, 211, 0.7);
    }

    &__popup-title {
        font-size: 32rpx;
        font-weight: 700;
        color: #181614;
        display: block;
    }

    &__popup-subtitle {
        font-size: 22rpx;
        color: #8C8273;
        margin-top: 6rpx;
        display: block;
    }

    &__popup-close {
        width: 56rpx;
        height: 56rpx;
        border-radius: 50%;
        background: #FAF6EE;
        display: flex;
        align-items: center;
        justify-content: center;

        &:active {
            background: #F2ECE1;
        }
    }

    /* 活动报名抽屉 */
    &__activity-popup {
        height: 100%;
        background: #FFFFFF;
        display: flex;
        flex-direction: column;
    }

    &__activity-popup-body {
        flex: 1;
        min-height: 0;
        padding: 24rpx 32rpx;
        box-sizing: border-box;
    }

    &__activity-form {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
        margin-top: 24rpx;
    }

    &__activity-field {
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__activity-field-label {
        font-size: 24rpx;
        font-weight: 700;
        color: #181614;
    }

    &__activity-input,
    &__activity-textarea {
        width: 100%;
        border-radius: 20rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.9);
        background: #FAF8F5;
        color: #181614;
        font-size: 26rpx;
        box-sizing: border-box;
    }

    &__activity-input {
        height: 80rpx;
        padding: 0 24rpx;
    }

    &__activity-textarea {
        min-height: 140rpx;
        padding: 20rpx 24rpx;
        line-height: 1.5;
    }

    /* 评论抽屉 */
    &__comment-drawer {
        height: 100%;
        background: #FFFFFF;
        display: flex;
        flex-direction: column;
    }

    &__popup-body {
        flex: 1;
        min-height: 0;
        padding: 24rpx 32rpx 16rpx;
        display: flex;
        flex-direction: column;
    }

    &__textarea-panel {
        flex: 1;
        min-height: 0;
        border-radius: 24rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        background: #FAF8F5;
        overflow: hidden;
    }

    &__textarea {
        width: 100%;
        height: 100%;
        min-height: 260rpx;
        padding: 22rpx 24rpx;
        background: transparent;
        box-sizing: border-box;
        font-size: 28rpx;
        line-height: 1.68;
        color: #181614;
    }

    &__textarea-placeholder {
        color: #8C8273;
    }

    &__popup-footer {
        position: relative;
        z-index: 2;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 16rpx;
        padding: 16rpx 32rpx 24rpx;
        border-top: 1rpx solid rgba(231, 224, 211, 0.7);

        &.is-emoji-open {
            z-index: 3;
        }
    }

    &__popup-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16rpx;
    }

    &__char-count {
        font-size: 22rpx;
        color: #8C8273;
    }

    &__composer-actions {
        display: inline-flex;
        align-items: center;
        gap: 14rpx;
    }

    &__emoji-btn {
        height: 68rpx;
        padding: 0 24rpx;
        border-radius: 999rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.9);
        background: #FAF8F5;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #5E564B;
        font-size: 24rpx;
        font-weight: 500;
        line-height: 1;

        &::after {
            display: none;
        }

        &.is-active {
            background: #FDF9F2;
            border-color: #D9BE82;
            color: #C6A15B;
        }
    }

    &__submit-btn {
        min-width: 180rpx;
        height: 72rpx;
        padding: 0 32rpx;
        border-radius: 999rpx;
        border: none;
        background: #181614;
        color: #FFFDF8;
        font-size: 26rpx;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8rpx 20rpx rgba(24, 22, 20, 0.2);

        &::after {
            display: none;
        }

        &.is-disabled {
            opacity: 0.42;
            box-shadow: none;
        }
    }

    &__emoji-panel {
        position: absolute;
        left: 24rpx;
        right: 24rpx;
        bottom: calc(100% + 14rpx);
        height: 280rpx;
        padding: 18rpx 12rpx;
        border-radius: 24rpx;
        border: 1rpx solid rgba(231, 224, 211, 0.85);
        background: #FFFFFF;
        box-shadow: 0 12rpx 32rpx rgba(74, 43, 24, 0.12);
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12rpx;
        box-sizing: border-box;
        overflow-y: auto;
        z-index: 4;
    }

    &__emoji-item {
        height: 68rpx;
        border-radius: 16rpx;
        background: #FAF8F5;
        border: 1rpx solid rgba(231, 224, 211, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;

        &:active {
            background: #F2ECE1;
            transform: scale(0.92);
        }
    }

    &__emoji-char {
        font-size: 34rpx;
        line-height: 1;
    }

    /* 加载视图 */
    &__loading-view {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32rpx;
    }

    &__loading-card {
        width: 100%;
    }
}
</style>
