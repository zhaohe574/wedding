/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./index.html', './src/**/*.{html,js,ts,jsx,tsx,vue}'],
    theme: {
        colors: {
            // 中性色彩
            white: 'var(--color-white, #ffffff)',
            black: 'var(--color-black, #000000)',
            main: 'var(--color-main, #333333)',
            content: 'var(--color-content, #666666)',
            muted: 'var(--color-muted, #999999)',
            page: 'var(--color-bg, #f6f6f6)',
            light: 'var(--color-light, #e5e5e5)',
            disabled: 'var(--color-disabled, #c8c9cc)',
            
            // 主题色系统
            minor: 'var(--color-minor, #FFB814)',
            'btn-text': 'var(--color-btn-text, white)',
            
            // 主色（优雅紫）
            primary: {
                DEFAULT: 'var(--color-primary, #7C3AED)',
                'light-3': 'var(--color-primary-light-3, #A78BFA)',
                'light-5': 'var(--color-primary-light-5, #C4B5FD)',
                'light-7': 'var(--color-primary-light-7, #DDD6FE)',
                'light-9': 'var(--color-primary-light-9, #FAF5FF)',
                'dark-2': 'var(--color-primary-dark-2, #6D28D9)'
            },
            
            // 辅助色（玫瑰粉）
            secondary: {
                DEFAULT: 'var(--color-secondary, #EC4899)',
                'light-3': 'var(--color-secondary-light-3, #F472B6)',
                'light-5': 'var(--color-secondary-light-5, #F9A8D4)',
                'light-7': 'var(--color-secondary-light-7, #FBCFE8)',
                'light-9': 'var(--color-secondary-light-9, #FCE7F3)',
                'dark-2': 'var(--color-secondary-dark-2, #DB2777)'
            },
            
            // CTA色（活力橙）
            cta: {
                DEFAULT: 'var(--color-cta, #F97316)',
                'light-3': 'var(--color-cta-light-3, #FB923C)',
                'light-5': 'var(--color-cta-light-5, #FDBA74)',
                'light-7': 'var(--color-cta-light-7, #FED7AA)',
                'light-9': 'var(--color-cta-light-9, #FFEDD5)',
                'dark-2': 'var(--color-cta-dark-2, #EA580C)'
            },
            
            // 点缀色（金色）
            accent: {
                DEFAULT: 'var(--color-accent, #FFD700)',
                'light-3': 'var(--color-accent-light-3, #FFE033)',
                'light-5': 'var(--color-accent-light-5, #FFE966)',
                'light-7': 'var(--color-accent-light-7, #FFF199)',
                'light-9': 'var(--color-accent-light-9, #FFF9CC)',
                'dark-2': 'var(--color-accent-dark-2, #CCAC00)'
            },
            
            // 功能色彩
            success: {
                DEFAULT: 'var(--color-success, #19be6b)',
                'light-3': 'var(--color-success-light-3, #5ED297)',
                'light-5': 'var(--color-success-light-5, #8CDFB5)',
                'light-7': 'var(--color-success-light-7, #BAECD3)',
                'light-9': 'var(--color-success-light-9, #E8F9F0)',
                'dark-2': 'var(--color-success-dark-2, #149856)'
            },
            warning: {
                DEFAULT: 'var(--color-warning, #ff9900)',
                'light-3': 'var(--color-warning-light-3, #FFB84D)',
                'light-5': 'var(--color-warning-light-5, #FFCC80)',
                'light-7': 'var(--color-warning-light-7, #FFE0B3)',
                'light-9': 'var(--color-warning-light-9, #FFF5E6)',
                'dark-2': 'var(--color-warning-dark-2, #CC7A00)'
            },
            error: {
                DEFAULT: 'var(--color-error, #ff2c3c)',
                'light-3': 'var(--color-error-light-3, #FF6B77)',
                'light-5': 'var(--color-error-light-5, #FF969E)',
                'light-7': 'var(--color-error-light-7, #FFC0C5)',
                'light-9': 'var(--color-error-light-9, #FFEAEC)',
                'dark-2': 'var(--color-error-dark-2, #CC2330)'
            },
            info: {
                DEFAULT: 'var(--color-info, #909399)',
                'light-3': 'var(--color-info-light-3, #B1B3B8)',
                'light-5': 'var(--color-info-light-5, #C8C9CC)',
                'light-7': 'var(--color-info-light-7, #DEDFE0)',
                'light-9': 'var(--color-info-light-9, #F4F4F5)',
                'dark-2': 'var(--color-info-dark-2, #73767A)'
            },
            
            // 背景色系统
            'bg-primary': 'var(--color-bg-primary, #FAF5FF)',
            'bg-secondary': 'var(--color-bg-secondary, #ECFDF5)',
            'bg-card': 'var(--color-bg-card, #FFFFFF)',
            'bg-overlay': 'var(--color-bg-overlay, rgba(0, 0, 0, 0.5))',
            
            // 订单状态色彩
            'order-pending': '#FF9900',
            'order-unpaid': '#F97316',
            'order-paid': '#10B981',
            'order-completed': '#6B7280',
            'order-cancelled': '#EF4444'
        },
        fontSize: {
            xs: '24rpx',    // 说明文本
            sm: '26rpx',    // 辅助文本
            base: '28rpx',  // 正文
            lg: '30rpx',
            xl: '32rpx',    // 三级标题
            '2xl': '34rpx', // 二级标题
            '3xl': '38rpx',
            '4xl': '40rpx', // 一级标题
            '5xl': '44rpx'  // 大标题
        },
        fontFamily: {
            sans: ['Source Han Sans CN', 'Helvetica Neue', 'Arial', 'sans-serif'],
            heading: ['Source Han Sans CN', 'Helvetica Neue', 'Arial', 'sans-serif']
        },
        fontWeight: {
            normal: '400',
            medium: '500',
            semibold: '600',
            bold: '700'
        },
        lineHeight: {
            tight: '1.4',
            normal: '1.5',
            relaxed: '1.6'
        },
        spacing: {
            xs: '8rpx',    // 极小间距
            sm: '16rpx',   // 小间距
            md: '24rpx',   // 中等间距
            lg: '32rpx',   // 大间距
            xl: '48rpx',   // 超大间距
            '2xl': '64rpx' // 特大间距
        },
        extend: {
            borderRadius: {
                'card': '16rpx',
                'card-large': '32rpx',
                'button': '48rpx',
                'input': '16rpx',
                'badge': '12rpx'
            },
            boxShadow: {
                'card': '0 2rpx 12rpx rgba(0, 0, 0, 0.08)',
                'card-hover': '0 8rpx 24rpx rgba(0, 0, 0, 0.12)',
                'card-glass': '0 20rpx 60rpx rgba(124, 58, 237, 0.12), 0 8rpx 16rpx rgba(0, 0, 0, 0.04)',
                'button-primary': '0 8rpx 24rpx rgba(124, 58, 237, 0.3)',
                'button-cta': '0 8rpx 24rpx rgba(249, 115, 22, 0.3)',
                'input-focus': '0 0 0 6rpx rgba(124, 58, 237, 0.1)'
            },
            transitionDuration: {
                'fast': '150ms',
                'normal': '200ms',
                'slow': '300ms'
            },
            backdropBlur: {
                'glass': '20rpx'
            }
        }
    },
    plugins: [],
    corePlugins: {
        preflight: false
    }
}
