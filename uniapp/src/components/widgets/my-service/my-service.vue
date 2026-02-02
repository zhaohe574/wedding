<template>
    <view class="my-service mx-[24rpx] mt-[24rpx]">
        <!-- 标题 -->
        <view v-if="content.title" class="title-section mb-[20rpx] px-[8rpx]">
            <text class="text-[32rpx] font-semibold text-[#1E293B]">{{ content.title }}</text>
        </view>
        
        <!-- 网格布局 -->
        <view v-if="content.style == 1" class="service-grid bg-white rounded-[16rpx] p-[24rpx]">
            <view class="grid grid-cols-4 gap-[32rpx]">
                <view
                    v-for="(item, index) in showList"
                    :key="index"
                    class="service-item flex flex-col items-center"
                    @click="handleClick(item.link)"
                >
                    <!-- 图标容器 -->
                    <view class="icon-wrapper mb-[16rpx] p-[20rpx] rounded-[20rpx]" :style="{ backgroundColor: getIconBg(index) }">
                        <image 
                            class="w-[56rpx] h-[56rpx]" 
                            :src="getImageUrl(item.image)" 
                            mode="aspectFit"
                        />
                    </view>
                    <!-- 文字 -->
                    <text class="text-[24rpx] text-[#334155] text-center">{{ item.name }}</text>
                </view>
            </view>
        </view>
        
        <!-- 列表布局 -->
        <view v-if="content.style == 2" class="service-list bg-white rounded-[16rpx] overflow-hidden">
            <view
                v-for="(item, index) in showList"
                :key="index"
                class="service-list-item flex items-center px-[32rpx] py-[28rpx] border-b border-[#F1F5F9] last:border-b-0"
                @click="handleClick(item.link)"
            >
                <!-- 图标 -->
                <view class="icon-wrapper p-[16rpx] rounded-[16rpx]" :style="{ backgroundColor: getIconBg(index) }">
                    <image 
                        class="w-[48rpx] h-[48rpx]" 
                        :src="getImageUrl(item.image)" 
                        mode="aspectFit"
                    />
                </view>
                <!-- 文字 -->
                <text class="flex-1 ml-[24rpx] text-[28rpx] text-[#1E293B]">{{ item.name }}</text>
                <!-- 箭头 -->
                <tn-icon name="right" size="32" color="#CBD5E1" />
            </view>
        </view>
    </view>
</template>
<script lang="ts" setup>
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'
import { useAdminStore } from '@/stores/admin'
import { useThemeStore } from '@/stores/theme'
import { navigateTo } from '@/utils/util'
import { computed } from 'vue'
import { storeToRefs } from 'pinia'

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

const appStore = useAppStore()
const { getImageUrl } = appStore
const userStore = useUserStore()
const adminStore = useAdminStore()
const $theme = useThemeStore()
const { userInfo } = storeToRefs(userStore)
const { isLogin: isAdminLogin } = storeToRefs(adminStore)

const handleClick = (link: any) => {
    navigateTo(link)
}

// 获取图标背景色（循环使用主题色浅色变体）
const getIconBg = (index: number) => {
    const colors = [
        $theme.primaryColor + '15',
        $theme.secondaryColor + '15',
        $theme.ctaColor + '15',
        $theme.accentColor + '15'
    ]
    return colors[index % colors.length]
}

const featureSwitch = computed(() => appStore.config?.feature_switch || {})

const extraItems = computed(() => {
    const items: any[] = []
    if (featureSwitch.value.staff_center === 1 && userInfo.value?.is_staff) {
        items.push({
            name: '服务人员中心',
            image: 'resource/image/adminapi/default/menu_role.png',
            link: {
                path: '/packages/pages/staff_center/staff_center',
                name: '服务人员中心',
                type: 'shop'
            },
            is_show: '1'
        })
    }
    if (featureSwitch.value.admin_dashboard === 1) {
        const path = isAdminLogin.value
            ? '/packages/pages/admin_dashboard/admin_dashboard'
            : '/packages/pages/admin_login/admin_login'
        items.push({
            name: '管理员看板',
            image: 'resource/image/adminapi/default/menu_admin.png',
            link: {
                path,
                name: '管理员看板',
                type: 'shop'
            },
            is_show: '1'
        })
    }
    return items
})

const showList = computed(() => {
    const base = props.content.data?.filter((item: any) => item.is_show == '1') || []
    return [...base, ...extraItems.value]
})
</script>

<style lang="scss" scoped>
.my-service {
    .service-grid,
    .service-list {
        box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.08);
        transition: all 0.2s ease;
    }
    
    .service-item {
        transition: all 0.2s ease;
        cursor: pointer;
        
        &:active {
            transform: scale(0.95);
            opacity: 0.8;
        }
        
        .icon-wrapper {
            transition: all 0.2s ease;
        }
    }
    
    .service-list-item {
        transition: all 0.2s ease;
        cursor: pointer;
        
        &:active {
            background-color: #F8FAFC;
        }
    }
}
</style>
