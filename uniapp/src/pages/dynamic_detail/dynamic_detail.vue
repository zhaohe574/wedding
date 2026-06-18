<template>
    <page-meta :page-style="$theme.pageStyle" />
    <PageShell scene="consumer">
        <BaseNavbar
            title="动态详情"
            variant="solid"
            title-align="center"
            bg-color="#191713"
            text-color="#FFFDF8"
            @back="handleBack"
        />

        <view v-if="detail" class="dynamic-detail">
            <scroll-view scroll-y class="dynamic-detail__scroll" :style="scrollStyle">
                <view class="dynamic-detail__content">
                    <BaseCard
                        v-if="detail.video || heroImage"
                        variant="media"
                        scene="consumer"
                        padding="0"
                        class="dynamic-detail__hero-card"
                    >
                        <view
                            v-if="detail.video"
                            class="dynamic-detail__hero dynamic-detail__hero--video"
                        >
                            <video
                                :src="detail.video"
                                class="dynamic-detail__hero-video"
                                :poster="detail.video_cover"
                                controls
                                object-fit="cover"
                            />
                        </view>
                        <view v-else class="dynamic-detail__hero">
                            <image
                                :src="heroImage"
                                class="dynamic-detail__hero-image"
                                mode="aspectFill"
                                @tap.stop="previewImage(heroImage)"
                            />
                        </view>
                    </BaseCard>

                    <BaseCard
                        v-if="galleryImages.length > 0"
                        variant="list"
                        scene="consumer"
                        padding="18rpx"
                        class="dynamic-detail__gallery-card"
                    >
                        <view class="dynamic-detail__gallery">
                            <view
                                v-for="(img, idx) in galleryImages"
                                :key="`${img}-${idx}`"
                                class="dynamic-detail__gallery-item"
                                @tap.stop="previewImage(img)"
                            >
                                <image
                                    class="dynamic-detail__gallery-image"
                                    :src="img"
                                    mode="aspectFill"
                                />
                            </view>
                        </view>
                    </BaseCard>

                    <BaseCard
                        variant="panel"
                        scene="consumer"
                        padding="30rpx"
                        class="dynamic-detail__lead-card"
                    >
                        <view class="dynamic-detail__author-card">
                            <view class="dynamic-detail__author-main">
                                <image
                                    :src="
                                        detail.user_avatar ||
                                        '/static/images/user/default_avatar.png'
                                    "
                                    class="dynamic-detail__avatar"
                                    mode="aspectFill"
                                />
                                <view class="dynamic-detail__author-copy">
                                    <view class="dynamic-detail__author-row">
                                        <text class="dynamic-detail__author-name">
                                            {{ detail.user_nickname }}
                                        </text>
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
                                    <text class="dynamic-detail__author-meta">
                                        {{ authorMetaText }}
                                    </text>
                                </view>
                            </view>
                        </view>

                        <view v-if="showMetaTags" class="dynamic-detail__tag-row">
                            <StatusBadge
                                v-if="detail.dynamic_type && detail.dynamic_type !== 1"
                                :tone="getTypeTone(detail.dynamic_type)"
                                size="sm"
                                strong
                            >
                                {{ getTypeText(detail.dynamic_type) }}
                            </StatusBadge>
                            <view
                                v-for="(tag, tagIdx) in detailTags"
                                :key="`${tag}-${tagIdx}`"
                                class="dynamic-detail__topic-tag"
                            >
                                <text>#{{ tag }}</text>
                            </view>
                        </view>

                        <view class="dynamic-detail__detail-meta">
                            <view class="dynamic-detail__detail-meta-item">
                                <BaseIcon name="eye" size="24" color="#9A9388" />
                                <text>浏览 {{ formatCount(detail.view_count) }}</text>
                            </view>
                        </view>

                        <text class="dynamic-detail__content-text">{{ detail.content }}</text>
                    </BaseCard>

                    <BaseCard
                        v-if="isActivity"
                        variant="panel"
                        scene="consumer"
                        padding="24rpx"
                        class="dynamic-detail__activity-card"
                    >
                        <view class="dynamic-detail__activity-head">
                            <view>
                                <text class="dynamic-detail__activity-title">活动报名</text>
                                <text class="dynamic-detail__activity-subtitle">
                                    {{ activityStatusText }}
                                </text>
                            </view>
                            <StatusBadge tone="primary" size="sm" strong>
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

                        <view class="dynamic-detail__ticket-list">
                            <view
                                v-for="ticket in activityTickets"
                                :key="ticket.id"
                                class="dynamic-detail__ticket-item"
                                :class="{ 'is-disabled': !isTicketBuyable(ticket) }"
                            >
                                <view class="dynamic-detail__ticket-copy">
                                    <text class="dynamic-detail__ticket-name">{{ ticket.name }}</text>
                                    <text class="dynamic-detail__ticket-stock">
                                        {{ getTicketStatusText(ticket) }}
                                    </text>
                                    <text class="dynamic-detail__ticket-sale-time">
                                        {{ getTicketSaleCountdownText(ticket) }}
                                    </text>
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
                    </BaseCard>

                    <BaseCard
                        v-if="shouldShowCommentSection"
                        variant="panel"
                        scene="consumer"
                        padding="28rpx 26rpx 20rpx"
                        class="dynamic-detail__comments"
                    >
                        <view class="dynamic-detail__comments-head">
                            <text class="dynamic-detail__comments-title">
                                评论 {{ formatCount(detail.comment_count) }}
                            </text>
                            <view class="dynamic-detail__comments-sort">
                                <text
                                    class="dynamic-detail__sort-item"
                                    :class="{ 'is-active': commentSort === 'hot' }"
                                    @click="changeCommentSort('hot')"
                                >
                                    最热
                                </text>
                                <text class="dynamic-detail__sort-divider">|</text>
                                <text
                                    class="dynamic-detail__sort-item"
                                    :class="{ 'is-active': commentSort === 'new' }"
                                    @click="changeCommentSort('new')"
                                >
                                    最新
                                </text>
                            </view>
                        </view>

                        <view v-if="comments.length === 0" class="dynamic-detail__comment-empty">
                            <EmptyState title="暂无评论" compact />
                        </view>
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
                                                <text class="dynamic-detail__comment-author">
                                                    {{ getCommentAuthorText(item) }}
                                                </text>
                                                <view class="dynamic-detail__comment-meta-right">
                                                    <text class="dynamic-detail__comment-time">
                                                        {{ formatCommentTime(item.date) }}
                                                    </text>
                                                    <text class="dynamic-detail__comment-meta-dot"
                                                        >·</text
                                                    >
                                                    <text
                                                        class="dynamic-detail__comment-like-meta"
                                                        :class="{ 'is-active': item.likeActive }"
                                                        @tap.stop="handleLikeComment(item.id)"
                                                    >
                                                        赞
                                                        {{ formatCommentLikeCount(item.likeCount) }}
                                                    </text>
                                                </view>
                                            </view>
                                            <text class="dynamic-detail__comment-content">
                                                {{ item.content }}
                                            </text>
                                            <view class="dynamic-detail__comment-actions">
                                                <text
                                                    v-if="!miniProgramReviewMode"
                                                    class="dynamic-detail__comment-action"
                                                    @tap.stop="replyComment(item)"
                                                >
                                                    回复
                                                </text>
                                                <text
                                                    v-if="item.allowDelete"
                                                    class="dynamic-detail__comment-action is-danger"
                                                    @tap.stop="deleteCommentItem(item.id)"
                                                >
                                                    删除
                                                </text>
                                            </view>
                                        </view>

                                        <view
                                            v-if="item.replyExpanded && item.comment.length > 0"
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
                                                        <text class="dynamic-detail__comment-author">
                                                            {{ getCommentAuthorText(reply) }}
                                                        </text>
                                                        <view
                                                            class="dynamic-detail__comment-meta-right"
                                                        >
                                                            <text
                                                                class="dynamic-detail__comment-time"
                                                            >
                                                                {{ formatCommentTime(reply.date) }}
                                                            </text>
                                                            <text
                                                                class="dynamic-detail__comment-meta-dot"
                                                            >
                                                                ·
                                                            </text>
                                                            <text
                                                                class="dynamic-detail__comment-like-meta"
                                                                :class="{
                                                                    'is-active': reply.likeActive
                                                                }"
                                                                @tap.stop="
                                                                    handleLikeComment(reply.id)
                                                                "
                                                            >
                                                                赞
                                                                {{
                                                                    formatCommentLikeCount(
                                                                        reply.likeCount
                                                                    )
                                                                }}
                                                            </text>
                                                        </view>
                                                    </view>
                                                    <text class="dynamic-detail__comment-content">
                                                        {{ reply.content }}
                                                    </text>
                                                    <view class="dynamic-detail__comment-actions">
                                                        <text
                                                            v-if="!miniProgramReviewMode"
                                                            class="dynamic-detail__comment-action"
                                                            @tap.stop="replyComment(reply)"
                                                        >
                                                            回复
                                                        </text>
                                                        <text
                                                            v-if="reply.allowDelete"
                                                            class="dynamic-detail__comment-action is-danger"
                                                            @tap.stop="
                                                                deleteCommentItem(reply.id)
                                                            "
                                                        >
                                                            删除
                                                        </text>
                                                    </view>
                                                </view>
                                            </view>
                                        </view>

                                        <view
                                            v-if="item.commentCount > 0"
                                            class="dynamic-detail__reply-toggle"
                                            @tap.stop="toggleReplies(item)"
                                        >
                                            {{ getReplyToggleText(item) }}
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view
                            v-if="commentHasMore && comments.length > 0"
                            class="dynamic-detail__comment-more"
                        >
                            <text v-if="commentLoading">加载中...</text>
                            <text v-else @click="loadMoreComments">点击加载更多</text>
                        </view>
                    </BaseCard>
                </view>
            </scroll-view>

            <ActionArea
                sticky
                layout="split"
                tone="solid"
                class="dynamic-detail__bottom-action"
                :class="{ 'is-activity': isActivity }"
            >
                <BaseButton
                    v-if="isActivity"
                    class="dynamic-detail__bottom-register"
                    :label="activityPrimaryLabel"
                    :variant="canRegisterActivity ? 'primary' : 'secondary'"
                    size="sm"
                    height="72rpx"
                    :disabled="!canRegisterActivity && !hasActivityRegistration"
                    @click="handleActivityPrimaryAction"
                />
                <view
                    class="dynamic-detail__bottom-interactions"
                    :class="{ 'is-activity': isActivity }"
                >
                    <BaseButton
                        class="dynamic-detail__bottom-like"
                        :class="{ 'is-active': detail.is_liked }"
                        :label="`点赞 ${formatCount(detail.like_count)}`"
                        :icon="detail.is_liked ? 'like-fill' : 'like'"
                        :variant="detail.is_liked ? 'secondary' : 'dark'"
                        :size="isActivity ? 'mini' : 'sm'"
                        :height="isActivity ? '68rpx' : '78rpx'"
                        @click="handleLike"
                    />
                    <view class="dynamic-detail__bottom-tools">
                        <BaseButton
                            v-if="shouldShowCommentSection"
                            class="dynamic-detail__bottom-tool"
                            :label="`评论 ${formatCount(detail.comment_count)}`"
                            icon="comment"
                            variant="light"
                            size="mini"
                            height="68rpx"
                            @click="showCommentInput"
                        />
                        <button
                            class="dynamic-detail__bottom-share"
                            hover-class="none"
                            open-type="share"
                        >
                            <BaseIcon name="share" size="24" />
                            <text>分享</text>
                        </button>
                    </view>
                </view>
            </ActionArea>

            <BaseOverlayMask
                :show="showActivityRegister"
                :z-index="activityPopupMaskZIndex"
                :background="$theme.maskColor || 'rgba(11, 11, 11, 0.58)'"
                @close="showActivityRegister = false"
            />

            <TnPopup
                v-model="showActivityRegister"
                open-direction="bottom"
                :overlay="false"
                :safe-area-inset-bottom="true"
                :radius="28"
                height="72%"
                :z-index="activityPopupZIndex"
            >
                <view class="dynamic-detail__activity-popup">
                    <view class="dynamic-detail__popup-head">
                        <text class="dynamic-detail__popup-title">活动报名</text>
                        <view class="dynamic-detail__popup-close" @click="showActivityRegister = false">
                            <BaseIcon name="close" size="30" color="#9A9388" />
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
                                    <text class="dynamic-detail__ticket-stock">
                                        {{ getTicketStatusText(ticket) }}
                                    </text>
                                    <text class="dynamic-detail__ticket-sale-time">
                                        {{ getTicketSaleCountdownText(ticket) }}
                                    </text>
                                </view>
                                <text class="dynamic-detail__ticket-price">{{ ticket.price_label }}</text>
                            </view>
                        </view>

                        <view class="dynamic-detail__activity-form">
                            <view class="dynamic-detail__activity-field">
                                <text class="dynamic-detail__activity-field-label">联系人</text>
                                <input
                                    v-model="activityForm.contact_name"
                                    class="dynamic-detail__activity-input"
                                    placeholder="请输入联系人"
                                />
                            </view>
                            <view class="dynamic-detail__activity-field">
                                <text class="dynamic-detail__activity-field-label">手机号</text>
                                <input
                                    v-model="activityForm.contact_mobile"
                                    class="dynamic-detail__activity-input"
                                    type="number"
                                    placeholder="请输入手机号"
                                />
                            </view>
                            <view class="dynamic-detail__activity-field">
                                <text class="dynamic-detail__activity-field-label">备注</text>
                                <textarea
                                    v-model="activityForm.remark"
                                    class="dynamic-detail__activity-textarea"
                                    placeholder="选填"
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

            <BaseOverlayMask
                :show="showComment"
                :z-index="commentPopupMaskZIndex"
                :background="$theme.maskColor || 'rgba(11, 11, 11, 0.58)'"
                @close="closeCommentPopup"
            />

            <TnPopup
                v-model="showComment"
                open-direction="bottom"
                :overlay="false"
                :safe-area-inset-bottom="true"
                :radius="28"
                height="68%"
                :z-index="commentPopupZIndex"
            >
                <view class="dynamic-detail__popup">
                    <view class="dynamic-detail__popup-head">
                        <text class="dynamic-detail__popup-title">
                            {{ replyTo ? `回复 @${replyTo.user_nickname}` : '发表评论' }}
                        </text>
                        <view class="dynamic-detail__popup-close" @click="closeCommentPopup">
                            <BaseIcon name="close" size="30" color="#9A9388" />
                        </view>
                    </view>
                    <view class="dynamic-detail__popup-body">
                        <view class="dynamic-detail__textarea-panel">
                            <textarea
                                v-model="commentContent"
                                class="dynamic-detail__textarea"
                                :placeholder="
                                    replyTo ? `回复 @${replyTo.user_nickname}` : '说点什么...'
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
                                    表情
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

        <view v-else class="dynamic-detail__loading-view">
            <BaseCard variant="panel" scene="consumer" class="dynamic-detail__loading-card">
                <LoadingState text="正在加载动态详情..." tone="wedding" />
            </BaseCard>
        </view>
    </PageShell>
</template>
<script setup lang="ts">
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
    isMiniProgramReviewMode,
    showMiniProgramReviewModeTip
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
}

type DynamicCommentItem = DynamicReplyItem & {
    commentCount: number
    comment: DynamicReplyItem[]
    replyExpanded: boolean
    replyLoading: boolean
}

const dynamicId = ref(0)
const detail = ref<any>(null)
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
// 常用表情面板，避免引入额外资源依赖。
const emojiList = [
    '😀',
    '😄',
    '😊',
    '😍',
    '😘',
    '🤗',
    '🤔',
    '😅',
    '😭',
    '😡',
    '😎',
    '🥳',
    '😴',
    '👍',
    '👏',
    '🙏',
    '😇',
    '🤍',
    '💖',
    '🔥',
    '✨',
    '🎉',
    '💐',
    '🌹'
]

const scrollStyle = computed(() => ({
    height: `calc(100vh - ${navBarMetrics.navBarHeight}px - 166rpx - env(safe-area-inset-bottom))`
}))

const commentDisplayLength = computed(() => Array.from(commentContent.value).length)
const canSubmitComment = computed(() => Boolean(commentContent.value.trim()))
const detailCommentCount = computed(() => Math.max(0, Number(detail.value?.comment_count || 0)))
const shouldShowCommentSection = computed(() => {
    if (!detail.value) return false

    return Number(detail.value.allow_comment || 0) === 1 || detailCommentCount.value > 0
})

const detailTags = computed(() => {
    return Array.isArray(detail.value?.tags) ? detail.value.tags : []
})

const showMetaTags = computed(() => {
    return detailTags.value.length > 0 || Number(detail.value?.dynamic_type || 1) !== 1
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
    return '不限购买时限'
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
            // 解析失败时按单值或逗号分隔兜底处理。
        }

        return value
            .split(',')
            .map((item) => normalizeMediaUrl(item))
            .filter(Boolean)
    }

    return []
}

const previewImageUrls = computed(() => normalizeImageList(detail.value?.images))

const heroImage = computed(() => {
    if (detail.value?.video) {
        return normalizeMediaUrl(detail.value.video_cover || '')
    }
    return previewImageUrls.value[0] || ''
})

const galleryImages = computed(() => {
    if (detail.value?.video) {
        return []
    }
    return previewImageUrls.value.slice(1)
})

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
    replyLoading: false
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
    replyUserNickname: reply.reply_user_nickname || ''
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
        return `查看${visibleCount}条回复`
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
        showMiniProgramReviewModeTip('小程序送审模式已开启，暂不支持评论')
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
        showMiniProgramReviewModeTip('小程序送审模式已开启，暂不支持回复')
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
        showMiniProgramReviewModeTip('小程序送审模式已开启，暂不支持评论')
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
                replyUserNickname: replyTo.value.user_nickname
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
                replyLoading: false
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

const previewImage = (currentImage: string) => {
    const previewImages = previewImageUrls.value
    if (previewImages.length === 0) {
        return
    }
    const currentUrl = normalizeMediaUrl(currentImage)

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
})

onShareAppMessage(() => ({
    title: detail.value?.content?.slice(0, 30) || '精彩动态',
    path: `/pages/dynamic_detail/dynamic_detail?id=${dynamicId.value}`
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
@import '../../styles/dynamic.scss';

.dynamic-detail {
    background: transparent;

    &__scroll {
        width: 100%;
    }

    &__content {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
        padding: 24rpx var(--wm-space-page-x, 37rpx) 34rpx;
        box-sizing: border-box;
    }

    &__hero-card,
    &__gallery-card,
    &__lead-card,
    &__comments {
        position: relative;
        z-index: 1;
    }

    &__hero {
        overflow: hidden;
        border-radius: inherit;
        background: $dynamic-accent;
    }

    &__hero-image,
    &__hero-video {
        display: block;
        width: 100%;
        height: 468rpx;
    }

    &__gallery {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12rpx;
    }

    &__gallery-item {
        overflow: hidden;
        border-radius: 20rpx;
        background: $dynamic-surface-solid;
        border: 1rpx solid rgba(231, 226, 214, 0.72);
    }

    &__gallery-image {
        display: block;
        width: 100%;
        height: 184rpx;
    }

    &__author-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 22rpx;
    }

    &__author-main {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 20rpx;
    }

    &__avatar {
        width: 88rpx;
        height: 88rpx;
        flex-shrink: 0;
        border-radius: 50%;
        background: $dynamic-soft;
        border: 2rpx solid rgba(255, 255, 255, 0.92);
        box-shadow: 0 8rpx 18rpx rgba(17, 17, 17, 0.14);
    }

    &__author-copy {
        flex: 1;
        min-width: 0;
    }

    &__author-row {
        display: flex;
        align-items: center;
        gap: 8rpx;
        flex-wrap: wrap;
    }

    &__author-name {
        font-size: 30rpx;
        line-height: 1.25;
        font-weight: 900;
        color: $dynamic-text;
    }

    &__author-meta {
        display: block;
        margin-top: 10rpx;
        font-size: 23rpx;
        line-height: 1.6;
        color: $dynamic-text-muted;
        @include dynamic-line-clamp(2);
    }

    &__tag-row {
        display: flex;
        align-items: center;
        gap: 10rpx;
        flex-wrap: wrap;
        margin-top: 28rpx;
        margin-bottom: 18rpx;
    }

    &__topic-tag {
        @include dynamic-pill(rgba(247, 240, 223, 0.9), $dynamic-accent);
        min-height: 52rpx;
        padding: 0 18rpx;

        text {
            font-size: 22rpx;
            line-height: 1;
            font-weight: 500;
        }
    }

    &__detail-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10rpx;
        margin-bottom: 18rpx;
    }

    &__detail-meta-item {
        min-height: 46rpx;
        padding: 0 16rpx;
        border-radius: $dynamic-radius-pill;
        border: 1rpx solid rgba(216, 201, 173, 0.68);
        background: rgba(248, 247, 242, 0.72);
        display: inline-flex;
        align-items: center;
        gap: 8rpx;
        color: $dynamic-text-muted;
        font-size: 22rpx;
        font-weight: 700;
        line-height: 1;
    }

    &__content-text {
        font-size: 31rpx;
        line-height: 1.82;
        color: $dynamic-text;
        white-space: pre-wrap;
        word-break: break-word;
    }

    &__comments {
        overflow: hidden;
    }

    &__activity-card {
        display: flex;
        flex-direction: column;
        gap: 20rpx;
    }

    &__activity-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18rpx;
    }

    &__activity-title {
        display: block;
        font-size: 31rpx;
        line-height: 1.35;
        font-weight: 900;
        color: var(--wm-text-primary, #191713);
    }

    &__activity-subtitle {
        display: block;
        margin-top: 8rpx;
        font-size: 23rpx;
        color: var(--wm-text-secondary, #665E52);
    }

    &__activity-meta-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10rpx;
    }

    &__activity-meta-item {
        min-height: 96rpx;
        padding: 16rpx 18rpx;
        border-radius: 20rpx;
        border: 1rpx solid rgba(216, 201, 173, 0.82);
        background: rgba(250, 246, 238, 0.76);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 8rpx;
    }

    &__activity-meta-label {
        font-size: 22rpx;
        color: var(--wm-text-secondary, #665E52);
    }

    &__activity-meta-value {
        font-size: 25rpx;
        line-height: 1.35;
        font-weight: 800;
        color: var(--wm-text-primary, #191713);
    }

    &__ticket-list,
    &__ticket-select-list {
        display: flex;
        flex-direction: column;
        gap: 14rpx;
    }

    &__activity-meta-grid + &__ticket-list {
        margin-top: 18rpx;
    }

    &__ticket-item,
    &__ticket-select {
        min-height: 118rpx;
        padding: 20rpx 22rpx;
        border-radius: 22rpx;
        border: 1rpx solid rgba(216, 201, 173, 0.86);
        background: rgba(255, 253, 248, 0.96);
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18rpx;
        box-sizing: border-box;
    }

    &__ticket-select.is-active {
        border-color: var(--wm-color-champagne, #D9BE82);
        background: rgba(247, 240, 223, 0.92);
        box-shadow: 0 10rpx 24rpx rgba(74, 43, 24, 0.08);
    }

    &__ticket-item.is-disabled,
    &__ticket-select.is-disabled {
        opacity: 0.5;
    }

    &__ticket-empty {
        min-height: 80rpx;
        padding: 20rpx;
        border-radius: 24rpx;
        border: 1rpx dashed rgba(216, 201, 173, 0.86);
        background: rgba(250, 246, 238, 0.64);
        color: var(--wm-text-secondary, #665E52);
        font-size: 24rpx;
        text-align: center;
        box-sizing: border-box;
    }

    &__ticket-copy {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 7rpx;
    }

    &__ticket-name {
        display: block;
        width: 100%;
        font-size: 27rpx;
        line-height: 1.35;
        font-weight: 800;
        color: var(--wm-text-primary, #191713);
        word-break: break-word;
    }

    &__ticket-stock {
        display: block;
        font-size: 22rpx;
        line-height: 1.35;
        color: var(--wm-text-secondary, #665E52);
    }

    &__ticket-sale-time {
        display: inline-flex;
        padding: 5rpx 10rpx;
        border-radius: 999rpx;
        background: rgba(245, 234, 214, 0.82);
        font-size: 21rpx;
        line-height: 1.35;
        color: #8B6B32;
        box-sizing: border-box;
    }

    &__ticket-price {
        flex-shrink: 0;
        margin-top: 4rpx;
        max-width: 180rpx;
        font-size: 27rpx;
        line-height: 1.25;
        font-weight: 900;
        color: var(--wm-color-gold, #B8954A);
        text-align: right;
        word-break: keep-all;
    }

    &__bottom-register {
        flex: 0 0 280rpx;
        min-width: 0;
    }

    &__activity-popup {
        height: 100%;
        background: var(--wm-color-bg-card, #FFFDF8);
        display: flex;
        flex-direction: column;
    }

    &__activity-popup-body {
        flex: 1;
        min-height: 0;
        padding: 26rpx 28rpx 16rpx;
        box-sizing: border-box;
    }

    &__activity-form {
        display: flex;
        flex-direction: column;
        gap: 18rpx;
        margin-top: 24rpx;
    }

    &__activity-field {
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__activity-field-label {
        font-size: 24rpx;
        font-weight: 800;
        color: var(--wm-text-primary, #191713);
    }

    &__activity-input,
    &__activity-textarea {
        width: 100%;
        border-radius: 22rpx;
        border: 1rpx solid rgba(216, 201, 173, 0.86);
        background: rgba(255, 255, 255, 0.96);
        color: var(--wm-text-primary, #191713);
        font-size: 27rpx;
        box-sizing: border-box;
    }

    &__activity-input {
        height: 84rpx;
        padding: 0 22rpx;
    }

    &__activity-textarea {
        min-height: 150rpx;
        padding: 20rpx 22rpx;
        line-height: 1.5;
    }

    &__bottom-action {
        --wm-space-action-top: 18rpx;
        --wm-space-action-x: var(--wm-space-page-x, 37rpx);
        --wm-space-action-bottom: 20rpx;
        z-index: 90;
        box-sizing: border-box;

        &.is-activity {
            --wm-space-action-top: 16rpx;
            --wm-space-action-bottom: 18rpx;
            gap: 14rpx;
        }
    }

    &__bottom-interactions {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12rpx;

        &.is-activity {
            flex: 0 1 auto;
            justify-content: flex-end;
            gap: 10rpx;
        }

        &.is-activity .dynamic-detail__bottom-like {
            flex: 0 0 auto;
            max-width: 122rpx;
        }
    }

    &__bottom-like {
        flex: 1;
        min-width: 0;
    }

    &__bottom-tools {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        gap: 10rpx;
    }

    &__bottom-tool {
        flex-shrink: 0;
        max-width: 150rpx;
    }

    &__bottom-interactions.is-activity &__bottom-tool {
        max-width: 116rpx;
    }

    &__bottom-share {
        min-width: 104rpx;
        height: 68rpx;
        padding: 0 18rpx;
        margin: 0;
        border-radius: $dynamic-radius-pill;
        border: 1rpx solid rgba(216, 201, 173, 0.86);
        background: rgba(255, 253, 248, 0.98);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8rpx;
        color: $dynamic-text-secondary;
        font-size: 22rpx;
        font-weight: 900;
        line-height: 1;
        box-shadow: var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07));

        &::after {
            display: none;
        }
    }

    &__bottom-interactions.is-activity &__bottom-share {
        min-width: 100rpx;
        padding: 0 16rpx;
    }

    &__comments-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20rpx;
        padding-bottom: 18rpx;
    }

    &__comments-title {
        display: block;
        font-size: 30rpx;
        line-height: 1.3;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__comments-sort {
        display: inline-flex;
        align-items: center;
        gap: 12rpx;
        padding: 10rpx 18rpx;
        border-radius: $dynamic-radius-pill;
        background: rgba(248, 247, 242, 0.88);
        border: 1rpx solid rgba(231, 226, 214, 0.76);
        flex-shrink: 0;
    }

    &__sort-item {
        font-size: 22rpx;
        font-weight: 600;
        color: $dynamic-text-muted;

        &.is-active {
            color: $dynamic-accent;
        }
    }

    &__sort-divider {
        color: #D8D3C7;
        font-size: 20rpx;
    }

    &__comment-empty {
        padding: 18rpx 0 8rpx;
    }

    &__comment-list {
        padding: 0;
    }

    &__comment-stack {
        display: flex;
        flex-direction: column;
        gap: 10rpx;
    }

    &__comment-item {
        display: flex;
        align-items: flex-start;
        gap: 18rpx;
        padding: 24rpx 0 18rpx;

        & + & {
            border-top: 1rpx solid rgba(248, 247, 242, 0.92);
        }
    }

    &__comment-avatar {
        width: 62rpx;
        height: 62rpx;
        flex-shrink: 0;
        border-radius: 50%;
        background: $dynamic-soft;
        border: 2rpx solid rgba(255, 255, 255, 0.92);
        box-shadow: 0 8rpx 16rpx rgba(74, 43, 24, 0.08);

        &--reply {
            width: 48rpx;
            height: 48rpx;
        }
    }

    &__comment-body {
        flex: 1;
        min-width: 0;
    }

    &__comment-main {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 12rpx;
    }

    &__comment-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24rpx;
    }

    &__comment-author {
        flex: 1;
        min-width: 0;
        font-size: 25rpx;
        line-height: 1.35;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__comment-meta-right {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8rpx;
        font-size: 21rpx;
        line-height: 1.3;
        font-weight: 600;
        color: $dynamic-text-muted;
    }

    &__comment-time,
    &__comment-meta-dot {
        color: $dynamic-text-muted;
    }

    &__comment-like-meta {
        color: $dynamic-text-muted;

        &.is-active {
            color: $dynamic-accent;
        }
    }

    &__comment-content {
        font-size: 25rpx;
        line-height: 1.72;
        font-weight: 500;
        color: #5F5A50;
        white-space: pre-wrap;
        word-break: break-word;
    }

    &__comment-actions {
        display: inline-flex;
        align-items: center;
        gap: 20rpx;
    }

    &__comment-action {
        font-size: 22rpx;
        line-height: 1.3;
        font-weight: 600;
        color: #5F5A50;

        &.is-danger {
            color: #5A4433;
        }
    }

    &__reply-list {
        margin-top: 18rpx;
        padding: 16rpx 16rpx 16rpx 18rpx;
        border-radius: 24rpx;
        background: rgba(248, 247, 242, 0.72);
        border: 1rpx solid rgba(231, 226, 214, 0.68);
    }

    &__reply-item {
        display: flex;
        align-items: flex-start;
        gap: 14rpx;
        padding-top: 18rpx;

        &:first-child {
            padding-top: 0;
        }
    }

    &__reply-toggle {
        margin-top: 16rpx;
        font-size: 22rpx;
        line-height: 1.3;
        font-weight: 600;
        color: $dynamic-text-muted;
    }

    &__comment-more {
        padding: 22rpx 0 6rpx;
        text-align: center;
        font-size: 22rpx;
        color: $dynamic-text-muted;
    }

    &__popup {
        height: 100%;
        background: linear-gradient(
            180deg,
            rgba(255, 255, 255, 0.98) 0%,
            rgba(248, 247, 242, 1) 100%
        );
        display: flex;
        flex-direction: column;
    }

    &__popup-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 28rpx 28rpx 20rpx;
        border-bottom: 1rpx solid rgba(248, 247, 242, 0.92);
    }

    &__popup-title {
        font-size: 32rpx;
        font-weight: 700;
        color: $dynamic-text;
    }

    &__popup-close {
        width: 56rpx;
        height: 56rpx;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.94);
        border: 1rpx solid rgba(231, 226, 214, 0.78);
    }

    &__popup-body {
        flex: 1;
        min-height: 0;
        padding: 24rpx 24rpx 16rpx;
        display: flex;
        flex-direction: column;
    }

    &__textarea-panel {
        flex: 1;
        min-height: 0;
        border-radius: 26rpx;
        border: 1rpx solid rgba(231, 226, 214, 0.82);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: inset 0 1rpx 0 rgba(255, 255, 255, 0.72),
            0 10rpx 24rpx rgba(17, 17, 17, 0.06);
        overflow: hidden;
    }

    &__textarea {
        display: block;
        width: 100%;
        height: 100%;
        min-height: 320rpx;
        padding: 24rpx 24rpx 20rpx;
        border: none;
        background: transparent;
        box-sizing: border-box;
        font-size: 28rpx;
        line-height: 1.7;
        color: $dynamic-text;
    }

    &__textarea-placeholder {
        color: $dynamic-text-placeholder;
    }

    &__popup-footer {
        position: relative;
        z-index: 2;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 20rpx;
        padding: 18rpx 24rpx 24rpx;
        border-top: 1rpx solid rgba(248, 247, 242, 0.92);

        &.is-emoji-open {
            z-index: 3;
        }
    }

    &__popup-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20rpx;
    }

    &__char-count {
        flex: 1;
        min-width: 0;
        font-size: 22rpx;
        color: $dynamic-text-muted;
    }

    &__composer-actions {
        display: inline-flex;
        align-items: center;
        gap: 16rpx;
        flex-shrink: 0;
    }

    &__emoji-btn {
        min-width: 116rpx;
        height: 72rpx;
        padding: 0 28rpx;
        border-radius: $dynamic-radius-pill;
        border: 1rpx solid rgba(231, 226, 214, 0.82);
        background: rgba(255, 255, 255, 0.94);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: $dynamic-text-secondary;
        font-size: 24rpx;
        font-weight: 600;
        line-height: 1;

        &::after {
            display: none;
        }

        &.is-active {
            color: $dynamic-accent;
            background: $dynamic-accent-soft;
            border-color: rgba(11, 11, 11, 0.14);
        }
    }

    &__submit-btn {
        min-width: 228rpx;
        height: 84rpx;
        padding: 0 36rpx;
        border-radius: $dynamic-radius-pill;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: $dynamic-accent;
        box-shadow: $dynamic-shadow-accent;
        color: #ffffff;
        font-size: 28rpx;
        font-weight: 600;

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
        bottom: calc(100% + 16rpx);
        height: 296rpx;
        padding: 20rpx 8rpx 6rpx;
        border-radius: 28rpx;
        border: 1rpx solid rgba(231, 226, 214, 0.8);
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 12rpx 28rpx rgba(17, 17, 17, 0.12);
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14rpx 8rpx;
        box-sizing: border-box;
        overflow-y: auto;
        z-index: 4;
    }

    &__emoji-item {
        height: 72rpx;
        border-radius: 22rpx;
        border: 1rpx solid rgba(231, 226, 214, 0.72);
        background: rgba(255, 255, 255, 0.96);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8rpx 18rpx rgba(17, 17, 17, 0.08);
    }

    &__emoji-char {
        font-size: 38rpx;
        line-height: 1;
    }

    &__loading-view {
        min-height: 100vh;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24rpx var(--wm-space-page-x, 37rpx);
        box-sizing: border-box;
    }

    &__loading-card {
        width: 100%;
    }
}

.dynamic-detail__comment-empty :deep(.empty-state-block) {
    min-height: 260rpx;
    padding: 42rpx 24rpx;
    border: none;
    box-shadow: none;
    background: rgba(248, 247, 242, 0.58);
}

.dynamic-detail :deep(.tn-popup) {
    pointer-events: none;
}

.dynamic-detail :deep(.tn-popup__content) {
    pointer-events: auto;
}
</style>
