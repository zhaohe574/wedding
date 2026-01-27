<template>
  <tn-graphic-card
    :avatar="dynamic.user.avatar"
    :title="dynamic.user.nickname"
    :description="formatTime(dynamic.createTime)"
    :tags="topicNames"
    :content="truncatedContent"
    :images="dynamic.images"
    :view-count="dynamic.viewCount"
    :comment-count="dynamic.commentCount"
    :like-count="dynamic.likeCount"
    :active-like="dynamic.isLiked"
    :show-view-user="false"
    :show-hot="true"
    :hot-count="dynamic.viewCount"
    :tag-bg-color="tagBgColor"
    :tag-text-color="tagTextColor"
    @click="handleCardClick"
    @avatar-view-click="handleUserClick"
    @more-click="handleMore"
    @comment-click="handleComment"
    @like-click="handleLike"
  >
    <!-- 顶部右边操作区域 -->
    <template #briefOperation>
      <follow-button
        :is-followed="dynamic.user.isFollowed"
        size="sm"
        @click="handleFollow"
      />
    </template>
  </tn-graphic-card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import TnGraphicCard from 'tnuiv3p-tn-graphic-card/index.vue'
import FollowButton from './FollowButton.vue'
import { useThemeStore } from '@/stores/theme'

const themeStore = useThemeStore()

interface DynamicData {
  id: number
  user: {
    id: number
    nickname: string
    avatar: string
    isFollowed: boolean
  }
  content: string
  images: string[]
  topics: Array<{
    id: number
    name: string
  }>
  location?: {
    name: string
    lat: number
    lng: number
  }
  viewCount: number
  likeCount: number
  commentCount: number
  isLiked: boolean
  createTime: string
}

interface Props {
  dynamic: DynamicData
}

const props = defineProps<Props>()

const emit = defineEmits<{
  click: [dynamic: DynamicData]
  userClick: [userId: number]
  follow: [userId: number]
  topicClick: [topic: { id: number; name: string }]
  imageClick: [index: number]
  like: [dynamic: DynamicData]
  comment: [dynamic: DynamicData]
  share: [dynamic: DynamicData]
}>()

// 从主题store获取标签颜色
const tagBgColor = computed(() => {
  // 使用主题色的浅色版本作为背景
  const primaryColor = themeStore.primaryColor || '#7C3AED'
  // 转换为rgba格式，透明度0.08
  return `${primaryColor}14` // 14是16进制的0.08透明度
})

const tagTextColor = computed(() => {
  // 使用主题色作为文字颜色
  return themeStore.primaryColor || '#7C3AED'
})

// 提取话题名称数组
const topicNames = computed(() => {
  return props.dynamic.topics?.map(topic => topic.name) || []
})

// 截断内容，最多显示3行
const truncatedContent = computed(() => {
  const content = props.dynamic.content || ''
  const maxLength = 150 // 最多显示150个字符
  if (content.length > maxLength) {
    return content.substring(0, maxLength) + '...'
  }
  return content
})

// 格式化时间（兼容 iOS）
const formatTime = (time: string): string => {
  // 将 "yyyy-MM-dd HH:mm:ss" 格式转换为 iOS 兼容的格式
  const iosCompatibleTime = time.replace(' ', 'T')
  
  const now = new Date().getTime()
  const createTime = new Date(iosCompatibleTime).getTime()
  const diff = now - createTime
  
  const minute = 60 * 1000
  const hour = 60 * minute
  const day = 24 * hour
  
  if (diff < minute) {
    return '刚刚'
  } else if (diff < hour) {
    return `${Math.floor(diff / minute)}分钟前`
  } else if (diff < day) {
    return `${Math.floor(diff / hour)}小时前`
  } else if (diff < 7 * day) {
    return `${Math.floor(diff / day)}天前`
  } else {
    return time.split(' ')[0]
  }
}

// 事件处理
const handleCardClick = () => {
  emit('click', props.dynamic)
}

const handleUserClick = () => {
  emit('userClick', props.dynamic.user.id)
}

const handleFollow = () => {
  emit('follow', props.dynamic.user.id)
}

const handleMore = () => {
  emit('share', props.dynamic)
}

const handleLike = () => {
  emit('like', props.dynamic)
}

const handleComment = () => {
  emit('comment', props.dynamic)
}
</script>

<script lang="ts">
export default {
  name: 'DynamicCard',
  options: {
    virtualHost: true
  },
  components: {
    TnGraphicCard
  }
}
</script>
