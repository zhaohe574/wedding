/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./index.html', './src/**/*.{html,js,ts,jsx,tsx,vue}'],
    theme: {
        colors: {
            // 中性色彩
            white: 'var(--wm-color-bg-card, #FFFFFF)',
            black: 'var(--wm-color-primary-strong, #0D0C0B)',
            main: 'var(--wm-color-primary, #1A1816)',
            content: 'var(--wm-text-secondary, #686259)',
            muted: 'var(--wm-text-tertiary, #9E9689)',
            page: 'var(--wm-color-bg-page, #FAF8F5)',
            light: 'var(--wm-color-border, #E8DFD1)',
            disabled: 'var(--wm-color-bg-subtle, #ECE6DC)',

            // 主题色系统
            minor: 'var(--wm-color-secondary, #C5A46D)',
            'btn-text': 'var(--wm-text-inverse, #FCFAF7)',

            // 主色（黑曜石）
            primary: {
                DEFAULT: 'var(--wm-color-primary, #1A1816)',
                'light-3': '#332F2C',
                'light-5': '#5A544F',
                'light-7': '#8A827B',
                'light-9': 'var(--wm-color-primary-soft, #F6EFE3)',
                'dark-2': 'var(--wm-color-primary-strong, #0D0C0B)'
            },

            // 辅助色（香槟金）
            secondary: {
                DEFAULT: 'var(--wm-color-secondary, #C5A46D)',
                'light-3': '#D4B888',
                'light-5': '#E2CDA7',
                'light-7': '#EFE2C9',
                'light-9': 'var(--wm-color-secondary-soft, #F7EFE3)',
                'dark-2': 'var(--wm-color-secondary-strong, #8A6932)'
            },

            // CTA色（黑曜石）
            cta: {
                DEFAULT: 'var(--wm-color-cta, #1A1816)',
                'light-3': '#332F2C',
                'light-5': '#5A544F',
                'light-7': '#8A827B',
                'light-9': 'var(--wm-color-primary-soft, #F6EFE3)',
                'dark-2': 'var(--wm-color-primary-strong, #0D0C0B)'
            },

            // 点缀色（香槟金）
            accent: {
                DEFAULT: 'var(--wm-color-champagne, #C5A46D)',
                'light-3': '#D4B888',
                'light-5': '#E2CDA7',
                'light-7': '#EFE2C9',
                'light-9': 'var(--wm-color-champagne-soft, #FAF4EB)',
                'dark-2': 'var(--wm-color-gold, #C5A46D)'
            },

            // 功能色彩
            success: {
                DEFAULT: 'var(--wm-color-success, #5D7A68)',
                'light-3': '#7D9586',
                'light-5': '#A0B2A6',
                'light-7': '#C6D2CB',
                'light-9': 'var(--wm-color-success-soft, #EEF4F0)',
                'dark-2': '#42594B'
            },
            warning: {
                DEFAULT: 'var(--wm-color-warning, #C5A46D)',
                'light-3': '#D4B888',
                'light-5': '#E2CDA7',
                'light-7': '#EFE2C9',
                'light-9': 'var(--wm-color-warning-soft, #F7EFE3)',
                'dark-2': '#7E5B23'
            },
            error: {
                DEFAULT: 'var(--wm-color-danger, #B45347)',
                'light-3': '#C67166',
                'light-5': '#D9938B',
                'light-7': '#ECB8B2',
                'light-9': 'var(--wm-color-danger-soft, #FAECE9)',
                'dark-2': '#873B32'
            },
            info: {
                DEFAULT: 'var(--wm-color-info, #6D7E99)',
                'light-3': '#8A99AF',
                'light-5': '#ABB6C6',
                'light-7': '#CCD4DE',
                'light-9': 'var(--wm-color-info-soft, #EFF3F8)',
                'dark-2': '#4A586E'
            },

            // 背景色系统
            'bg-primary': 'var(--wm-color-bg-page, #FAF8F5)',
            'bg-secondary': 'var(--wm-color-bg-soft, #F5F1EB)',
            'bg-card': 'var(--wm-color-bg-card, #FFFFFF)',
            'bg-overlay': 'var(--wm-color-bg-mask, rgba(26, 24, 22, 0.62))',

            // 订单状态色彩
            'order-pending': '#C5A46D',
            'order-unpaid': '#B45347',
            'order-paid': '#5D7A68',
            'order-completed': '#6D7E99',
            'order-cancelled': '#9E9689'
        },
        fontSize: {
            xs: '24rpx', // 说明文本
            sm: '26rpx', // 辅助文本
            base: '28rpx', // 正文
            lg: '30rpx',
            xl: '32rpx', // 三级标题
            '2xl': '34rpx', // 二级标题
            '3xl': '38rpx',
            '4xl': '40rpx', // 一级标题
            '5xl': '44rpx' // 大标题
        },
        fontFamily: {
            sans: ['Inter', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', 'sans-serif'],
            heading: ['Playfair Display', 'PingFang SC', 'Microsoft YaHei', 'sans-serif']
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
            xs: '8rpx', // 极小间距
            sm: '16rpx', // 小间距
            md: '24rpx', // 中等间距
            lg: '32rpx', // 大间距
            xl: '48rpx', // 超大间距
            '2xl': '64rpx' // 特大间距
        },
        extend: {
            borderRadius: {
                card: 'var(--wm-radius-card, 24rpx)',
                'card-soft': 'var(--wm-radius-card-soft, 20rpx)',
                'card-large': 'var(--wm-radius-card-lg, 32rpx)',
                button: 'var(--wm-radius-action, 999rpx)',
                input: 'var(--wm-radius-control, 24rpx)',
                badge: 'var(--wm-radius-pill, 999rpx)'
            },
            boxShadow: {
                card: 'var(--wm-shadow-soft, 0 8rpx 24rpx rgba(28, 24, 20, 0.05))',
                'card-hover': 'var(--wm-shadow-card, 0 12rpx 32rpx rgba(28, 24, 20, 0.08))',
                'card-glass':
                    '0 12rpx 32rpx rgba(28, 24, 20, 0.06), 0 2rpx 6rpx rgba(197, 164, 109, 0.15)',
                'button-primary': 'var(--wm-shadow-action, 0 16rpx 36rpx rgba(197, 164, 109, 0.28))',
                'button-cta': 'var(--wm-shadow-action, 0 16rpx 36rpx rgba(26, 24, 22, 0.22))',
                'input-focus': '0 0 0 4rpx rgba(197, 164, 109, 0.25)'
            },
            transitionDuration: {
                fast: '150ms',
                normal: '220ms',
                slow: '280ms'
            },
            backdropBlur: {
                glass: '18rpx'
            }
        }
    },
    plugins: [],
    corePlugins: {
        preflight: false
    }
}
