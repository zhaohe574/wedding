<template>
  <view class="custom-tabbar" :style="{ paddingBottom: safeAreaBottom + 'px' }">
    <view
      v-for="(item, index) in tabbarList"
      :key="index"
      class="tabbar-item"
      :class="{ 'tabbar-item--active': currentIndex === index }"
      @click="switchTab(item, index)"
    >
      <tn-icon
        :name="currentIndex === index ? item.selectedIcon : item.icon"
        :size="48"
        :color="currentIndex === index ? primaryColor : '#999999'"
        class="tabbar-item__icon"
      />
      <text class="tabbar-item__text" :style="{ color: currentIndex === index ? primaryColor : '#999999' }">
        {{ item.text }}
      </text>
    </view>
  </view>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useThemeStore } from '@/stores/theme'

interface TabbarItem {
  text: string
  icon: string
  selectedIcon: string
  pagePath: string
}

const themeStore = useThemeStore()
const currentIndex = ref(0)
const safeAreaBottom = ref(0)

// 获取主题色
const primaryColor = computed(() => themeStore.primaryColor || '#7C3AED')

// 标签栏配置
const tabbarList: TabbarItem[] = [
  {
    text: '首页',
    icon: 'home',
    selectedIcon: 'home-fill',
    pagePath: '/pages/index/index'
  },
  {
    text: '资讯',
    icon: 'news',
    selectedIcon: 'news-fill',
    pagePath: '/pages/news/news'
  },
  {
    text: '我的',
    icon: 'user',
    selectedIcon: 'user-fill',
    pagePath: '/pages/user/user'
  }
]

// 切换标签
const switchTab = (item: TabbarItem, index: number) => {
  if (currentIndex.value === index) return
  
  currentIndex.value = index
  uni.switchTab({
    url: item.pagePath
  })
}

// 获取当前页面索引
const getCurrentIndex = () => {
  const pages = getCurrentPages()
  if (pages.length === 0) return 0
  
  const currentPage = pages[pages.length - 1]
  const route = '/' + currentPage.route
  
  const index = tabbarList.findIndex(item => item.pagePath === route)
  return index >= 0 ? index : 0
}

// 获取安全区域高度
const getSafeAreaBottom = () => {
  const systemInfo = uni.getSystemInfoSync()
  const safeArea = systemInfo.safeArea
  if (safeArea) {
    safeAreaBottom.value = systemInfo.screenHeight - safeArea.bottom
  }
}

onMounted(() => {
  currentIndex.value = getCurrentIndex()
  getSafeAreaBottom()
})
</script>

<script lang="ts">
export default {
  name: 'CustomTabbar',
  options: {
    virtualHost: true
  }
}
</script>

<style lang="scss" scoped>
.custom-tabbar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  align-items: center;
  justify-content: space-around;
  height: 120rpx;
  background: #FFFFFF;
  border-top: 1rpx solid var(--color-light, #E5E5E5);
  z-index: 1000;
}

.tabbar-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  height: 100%;
  transition: all 0.2s ease;
  cursor: pointer;
  
  &__icon {
    margin-bottom: 4rpx;
  }
  
  &__text {
    font-size: 24rpx;
    transition: color 0.2s ease;
  }
  
  &:active {
    opacity: 0.8;
  }
  
  &--active {
    .tabbar-item__text {
      font-weight: 500;
    }
  }
}
</style>
