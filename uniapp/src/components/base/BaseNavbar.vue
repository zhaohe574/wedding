<template>
  <view class="base-navbar-wrapper">
    <tn-navbar
      :back-icon="backIcon"
      :fixed="fixed"
      :bg-color="computedBgColor"
      :text-color="computedTextColor"
      :title="title"
      @back="handleBack"
    >
      <template v-if="$slots.left" #left>
        <slot name="left" />
      </template>
      <template v-if="$slots.right" #right>
        <slot name="right" />
      </template>
    </tn-navbar>
  </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'

interface Props {
  title?: string
  backIcon?: boolean
  fixed?: boolean
  bgColor?: string
  textColor?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  backIcon: true,
  fixed: true,
  bgColor: '',
  textColor: ''
})

const emit = defineEmits<{
  back: []
}>()

const themeStore = useThemeStore()

// 计算背景色，优先使用props，否则使用主题配置
const computedBgColor = computed(() => {
  return props.bgColor || themeStore.navBgColor
})

// 计算文字色，优先使用props，否则使用主题配置
const computedTextColor = computed(() => {
  return props.textColor || themeStore.navColor
})

// 处理返回事件
const handleBack = () => {
  emit('back')
  // 如果没有监听back事件，执行默认返回
  uni.navigateBack()
}
</script>

<script lang="ts">
export default {
  name: 'BaseNavbar',
  options: {
    virtualHost: true
  }
}
</script>

<style lang="scss" scoped>
.base-navbar-wrapper {
  // 确保导航栏在小程序中正确显示
  position: relative;
  z-index: 999;
}
</style>
