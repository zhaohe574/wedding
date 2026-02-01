<template>
    <view v-if="content.enabled && displayList.length" class="notice-bar">
        <!-- 横向滚动样式 -->
        <view v-if="content.style == 1">
            <!-- #ifdef H5 -->
            <view
                class="notice-h5"
                :style="{
                    backgroundColor: content.bg_color || '#FFF7ED',
                    color: content.text_color || '#EA580C'
                }"
            >
                <tn-icon
                    class="notice-h5__icon"
                    name="notice-fill"
                    :color="content.text_color || '#EA580C'"
                    size="28"
                />
                <u-notice-bar
                    class="notice-h5__bar"
                    :list="horizontalList"
                    mode="horizontal"
                    :speed="content.scroll_speed || 50"
                    :color="content.text_color || '#EA580C'"
                    :bg-color="'transparent'"
                    type="none"
                    :volume-icon="false"
                    :more-icon="false"
                    :close-icon="false"
                    :autoplay="true"
                    padding="0"
                    @click="handleClick(displayList[0])"
                />
            </view>
            <!-- #endif -->
            <!-- #ifndef H5 -->
            <tn-notice-bar
                :key="noticeKey"
                :data="horizontalList"
                :text-color="content.text_color || '#EA580C'"
                :bg-color="content.bg_color || '#FFF7ED'"
                :speed="content.scroll_speed || 50"
                direction="horizontal"
                :loop="true"
                left-icon="notice-fill"
                :left-icon-color="content.text_color || '#EA580C'"
                @click="handleClick(displayList[0])"
            />
            <!-- #endif -->
        </view>

        <!-- 纵向滚动样式 -->
        <view v-if="content.style == 2">
            <!-- #ifdef H5 -->
            <view
                class="notice-h5"
                :style="{
                    backgroundColor: content.bg_color || '#FFF7ED',
                    color: content.text_color || '#EA580C'
                }"
            >
                <tn-icon
                    class="notice-h5__icon"
                    name="notice-fill"
                    :color="content.text_color || '#EA580C'"
                    size="28"
                />
                <u-notice-bar
                    class="notice-h5__bar"
                    :list="verticalList"
                    mode="vertical"
                    :duration="3000"
                    :color="content.text_color || '#EA580C'"
                    :bg-color="'transparent'"
                    type="none"
                    :volume-icon="false"
                    :more-icon="false"
                    :close-icon="false"
                    :autoplay="true"
                    padding="0"
                    @click="handleVerticalClick"
                />
            </view>
            <!-- #endif -->
            <!-- #ifndef H5 -->
            <tn-notice-bar
                :key="noticeKey"
                :data="verticalList"
                :text-color="content.text_color || '#EA580C'"
                :bg-color="content.bg_color || '#FFF7ED'"
                direction="vertical"
                :speed="3000"
                left-icon="notice-fill"
                :left-icon-color="content.text_color || '#EA580C'"
                @click="handleVerticalClick"
            />
            <!-- #endif -->
        </view>

        <!-- 静态展示样式 -->
        <view v-if="content.style == 3" class="notice-static">
            <view
                v-for="(item, index) in displayList"
                :key="index"
                class="notice-item rounded-lg mb-[20rpx] overflow-hidden"
                @click="handleClick(item)"
            >
                <tn-notice-bar
                    :data="[formatNoticeText(item)]"
                    :text-color="content.text_color || '#EA580C'"
                    :bg-color="content.bg_color || '#FFF7ED'"
                    direction="horizontal"
                    :loop="false"
                    :auto-play="false"
                    left-icon="notice-fill"
                    :left-icon-color="content.text_color || '#EA580C'"
                    right-icon="right"
                    :right-icon-color="content.text_color || '#EA580C'"
                />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

const props = defineProps({
    content: {
        type: Object,
        default: () => ({})
    },
    styles: {
        type: Object,
        default: () => ({})
    }
})

const noticeList = ref<any[]>([])
const noticeKey = ref(0)

const visibleList = computed(() => {
    return noticeList.value
        .filter((item) => (item?.is_show ?? '1') !== '0')
        .slice()
        .sort((a, b) => Number(a?.sort ?? 0) - Number(b?.sort ?? 0))
})

// 显示的公告列表（限制数量）
const displayList = computed(() => {
    const count = props.content.show_count || 3
    return visibleList.value.slice(0, count)
})

// 将公告内容组合成一行文案（标题 + 内容摘要）
const formatNoticeText = (item: any) => {
    const title = (
        item?.title ??
        item?.notice_title ??
        item?.name ??
        item?.scene_name ??
        ''
    )
        .toString()
        .trim()
    const rawContent = (
        item?.content ??
        item?.content_preview ??
        item?.content_text ??
        item?.notice_content ??
        item?.message ??
        item?.desc ??
        item?.summary ??
        ''
    )
        .toString()
        .trim()

    // 兼容富文本/HTML内容：轻量去标签，避免首页出现大量标签
    const content = rawContent.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()

    const maxLen = Number(props.content?.content_max_length ?? 40)
    const short = content.length > maxLen ? content.slice(0, maxLen) + '…' : content

    if (title && short) return `${title}：${short}`
    return title || short || ''
}

// 横向滚动列表（uView 需要字符串数组）
const horizontalList = computed(() => {
    return displayList.value.map((item: any) => formatNoticeText(item)).filter(Boolean)
})

// 纵向滚动列表（uView 需要字符串数组）
const verticalList = computed(() => {
    return displayList.value.map((item: any) => formatNoticeText(item)).filter(Boolean)
})

// 处理横向和静态样式的点击
const handleClick = (item: any) => {
    if (!item) return
    
    if (item.link) {
        uni.navigateTo({
            url: item.link
        })
    } else {
        uni.navigateTo({
            url: `/pages/notification/index?id=${item.id}`
        })
    }
}

// 处理纵向滚动的点击（uView 返回索引）
const handleVerticalClick = (index: number) => {
    const item = displayList.value[index]
    if (item) {
        handleClick(item)
    }
}

// 监听数据变化，确保异步更新后重新触发滚动初始化
watch(
    () => props.content?.data,
    (data) => {
        noticeList.value = Array.isArray(data) ? data : []
        noticeKey.value += 1
    },
    { immediate: true, deep: true }
)
</script>

<style scoped lang="scss">
.notice-bar {
    .notice-h5 {
        display: flex;
        align-items: center;
        padding: 10rpx 20rpx;
        overflow: hidden;
        border-radius: 8rpx;
    }

    .notice-h5__icon {
        margin-right: 12rpx;
        flex-shrink: 0;
    }

    .notice-h5__bar {
        flex: 1;
        min-width: 0;
    }

    .notice-static {
        .notice-item {
            box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;

            &:active {
                opacity: 0.8;
            }
        }
    }
}
</style>
