<template>
    <page-meta :page-style="$theme.pageStyle">
        <!-- #ifndef H5 -->
        <navigation-bar 
            title="关于我们"
            :front-color="$theme.navColor" 
            :background-color="$theme.navBgColor" 
        />
        <!-- #endif -->
    </page-meta>
    
    <view class="as-us-page">
        <!-- 顶部装饰背景 -->
        <view class="header-decoration" :style="headerStyle"></view>
        
        <!-- Logo 区域 -->
        <view class="logo-section">
            <view class="logo-wrapper" :style="logoWrapperStyle">
                <image 
                    :src="appStore.getWebsiteConfig.shop_logo" 
                    mode="aspectFill" 
                    class="logo-image"
                />
            </view>
            <text class="brand-name">{{ appStore.getWebsiteConfig.shop_name || '婚庆服务平台' }}</text>
            <text class="brand-slogan">让每一场婚礼都成为永恒的回忆</text>
        </view>
        
        <!-- 信息卡片区域 -->
        <view class="info-cards">
            <!-- 版本信息卡片 -->
            <view class="info-card glass-card">
                <view class="card-icon-wrapper" :style="iconWrapperStyle">
                    <tn-icon name="info" size="40" :color="$theme.primaryColor" />
                </view>
                <view class="card-content">
                    <text class="card-label">当前版本</text>
                    <text class="card-value" :style="{ color: $theme.primaryColor }">
                        v{{ appStore.config.version }}
                    </text>
                </view>
            </view>
            
            <!-- 联系方式卡片 -->
            <view class="info-card glass-card" v-if="appStore.getWebsiteConfig.contact_phone">
                <view class="card-icon-wrapper" :style="iconWrapperStyle">
                    <tn-icon name="phone" size="40" :color="$theme.primaryColor" />
                </view>
                <view class="card-content">
                    <text class="card-label">联系电话</text>
                    <text class="card-value" :style="{ color: $theme.primaryColor }">
                        {{ appStore.getWebsiteConfig.contact_phone }}
                    </text>
                </view>
            </view>
            
            <!-- 邮箱卡片 -->
            <view class="info-card glass-card" v-if="appStore.getWebsiteConfig.contact_email">
                <view class="card-icon-wrapper" :style="iconWrapperStyle">
                    <tn-icon name="mail" size="40" :color="$theme.primaryColor" />
                </view>
                <view class="card-content">
                    <text class="card-label">联系邮箱</text>
                    <text class="card-value" :style="{ color: $theme.primaryColor }">
                        {{ appStore.getWebsiteConfig.contact_email }}
                    </text>
                </view>
            </view>
            
            <!-- 地址卡片 -->
            <view class="info-card glass-card" v-if="appStore.getWebsiteConfig.company_address">
                <view class="card-icon-wrapper" :style="iconWrapperStyle">
                    <tn-icon name="location" size="40" :color="$theme.primaryColor" />
                </view>
                <view class="card-content">
                    <text class="card-label">公司地址</text>
                    <text class="card-value" :style="{ color: $theme.primaryColor }">
                        {{ appStore.getWebsiteConfig.company_address }}
                    </text>
                </view>
            </view>
        </view>
        
        <!-- 关于我们描述 -->
        <view class="about-section glass-card">
            <view class="section-header">
                <view class="header-line" :style="{ backgroundColor: $theme.primaryColor }"></view>
                <text class="section-title">关于我们</text>
                <view class="header-line" :style="{ backgroundColor: $theme.primaryColor }"></view>
            </view>
            <text class="about-text">
                {{ appStore.getWebsiteConfig.shop_intro || '专注于为新人提供专业、贴心的婚庆服务，让每一场婚礼都成为独一无二的美好回忆。我们拥有经验丰富的团队，致力于打造完美的婚礼体验。' }}
            </text>
        </view>
        
        <!-- 服务特色 -->
        <view class="features-section">
            <view class="section-header">
                <view class="header-line" :style="{ backgroundColor: $theme.primaryColor }"></view>
                <text class="section-title">服务特色</text>
                <view class="header-line" :style="{ backgroundColor: $theme.primaryColor }"></view>
            </view>
            
            <view class="features-grid">
                <view 
                    class="feature-item"
                    v-for="(feature, index) in features"
                    :key="index"
                >
                    <view class="feature-icon-wrapper" :style="getFeatureIconStyle(index)">
                        <tn-icon :name="feature.icon" size="48" color="#FFFFFF" />
                    </view>
                    <text class="feature-title">{{ feature.title }}</text>
                    <text class="feature-desc">{{ feature.desc }}</text>
                </view>
            </view>
        </view>
        
        <!-- 底部版权信息 -->
        <view class="footer">
            <text class="copyright">© 2024 {{ appStore.getWebsiteConfig.shop_name || '婚庆服务平台' }}</text>
            <text class="copyright-sub">All Rights Reserved</text>
        </view>
    </view>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'
import { useThemeStore } from '@/stores/theme'

const appStore = useAppStore()
const $theme = useThemeStore()

// 服务特色数据
const features = [
    {
        icon: 'star',
        title: '专业团队',
        desc: '经验丰富的婚礼策划师'
    },
    {
        icon: 'like-lack',
        title: '贴心服务',
        desc: '全程一对一专属服务'
    },
    {
        icon: 'trusty',
        title: '品质保障',
        desc: '严格的质量管控体系'
    },
    {
        icon: 'gift',
        title: '个性定制',
        desc: '量身打造专属婚礼'
    }
]

// 头部装饰样式
const headerStyle = computed(() => ({
    background: `linear-gradient(180deg, ${$theme.primaryColor} 0%, transparent 100%)`
}))

// Logo 包装器样式
const logoWrapperStyle = computed(() => ({
    borderColor: $theme.primaryColor,
    boxShadow: `0 8rpx 24rpx ${$theme.primaryColor}40`
}))

// 图标包装器样式
const iconWrapperStyle = computed(() => ({
    backgroundColor: `${$theme.primaryColor}15`
}))

// 特色图标样式（使用不同的主题色）
const getFeatureIconStyle = (index: number) => {
    const colors = [
        $theme.primaryColor,
        $theme.secondaryColor,
        $theme.ctaColor,
        $theme.accentColor
    ]
    return {
        background: `linear-gradient(135deg, ${colors[index]} 0%, ${colors[index]} 100%)`
    }
}
</script>

<style lang="scss" scoped>
.as-us-page {
    min-height: 100vh;
    background: linear-gradient(180deg, #F9FAFB 0%, #FFFFFF 100%);
    padding-bottom: 48rpx;
    position: relative;
}

// 顶部装饰背景
.header-decoration {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 400rpx;
    opacity: 0.1;
    z-index: 0;
}

// Logo 区域
.logo-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 80rpx 24rpx 48rpx;
    position: relative;
    z-index: 1;
    
    .logo-wrapper {
        width: 180rpx;
        height: 180rpx;
        border-radius: 40rpx;
        border: 4rpx solid;
        padding: 8rpx;
        background: #FFFFFF;
        margin-bottom: 32rpx;
        transition: all 0.3s ease;
        
        .logo-image {
            width: 100%;
            height: 100%;
            border-radius: 32rpx;
        }
    }
    
    .brand-name {
        font-size: 40rpx;
        font-weight: 700;
        color: #1F2937;
        margin-bottom: 16rpx;
        text-align: center;
    }
    
    .brand-slogan {
        font-size: 26rpx;
        color: #6B7280;
        text-align: center;
        line-height: 1.6;
    }
}

// 信息卡片区域
.info-cards {
    padding: 0 24rpx;
    margin-bottom: 32rpx;
    
    .info-card {
        display: flex;
        align-items: center;
        padding: 32rpx 24rpx;
        margin-bottom: 16rpx;
        transition: all 0.2s ease;
        
        &:active {
            transform: translateY(-2rpx);
            box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
        }
        
        .card-icon-wrapper {
            width: 88rpx;
            height: 88rpx;
            border-radius: 20rpx;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 24rpx;
            flex-shrink: 0;
        }
        
        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            
            .card-label {
                font-size: 24rpx;
                color: #9CA3AF;
                margin-bottom: 8rpx;
            }
            
            .card-value {
                font-size: 28rpx;
                font-weight: 600;
                line-height: 1.5;
            }
        }
    }
}

// 玻璃态卡片
.glass-card {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20rpx);
    border: 2rpx solid rgba(255, 255, 255, 0.3);
    border-radius: 24rpx;
    box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.08);
}

// 关于我们区域
.about-section {
    margin: 0 24rpx 32rpx;
    padding: 32rpx 24rpx;
    
    .about-text {
        font-size: 28rpx;
        color: #4B5563;
        line-height: 1.8;
        text-align: justify;
    }
}

// 服务特色区域
.features-section {
    padding: 0 24rpx;
    margin-bottom: 32rpx;
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16rpx;
        
        .feature-item {
            background: #FFFFFF;
            border-radius: 24rpx;
            padding: 32rpx 24rpx;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
            
            &:active {
                transform: translateY(-4rpx);
                box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.12);
            }
            
            .feature-icon-wrapper {
                width: 96rpx;
                height: 96rpx;
                border-radius: 24rpx;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 20rpx;
                box-shadow: 0 8rpx 24rpx rgba(0, 0, 0, 0.15);
            }
            
            .feature-title {
                font-size: 28rpx;
                font-weight: 600;
                color: #1F2937;
                margin-bottom: 12rpx;
                text-align: center;
            }
            
            .feature-desc {
                font-size: 24rpx;
                color: #6B7280;
                text-align: center;
                line-height: 1.5;
            }
        }
    }
}

// 区域标题
.section-header {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 32rpx;
    
    .header-line {
        width: 48rpx;
        height: 4rpx;
        border-radius: 2rpx;
    }
    
    .section-title {
        font-size: 32rpx;
        font-weight: 700;
        color: #1F2937;
        margin: 0 24rpx;
    }
}

// 底部版权
.footer {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 48rpx 24rpx 24rpx;
    
    .copyright {
        font-size: 24rpx;
        color: #9CA3AF;
        margin-bottom: 8rpx;
    }
    
    .copyright-sub {
        font-size: 22rpx;
        color: #D1D5DB;
    }
}
</style>
