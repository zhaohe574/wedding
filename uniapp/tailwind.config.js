/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./index.html', './src/**/*.{html,js,ts,jsx,tsx,vue}'],
    theme: {
        colors: {
            // 中性色彩
            white: 'var(--wm-color-bg-card, #FFFDF8)',
            black: 'var(--wm-color-primary-strong, #000000)',
            main: 'var(--wm-color-primary, #191713)',
            content: 'var(--wm-text-secondary, #665E52)',
            muted: 'var(--wm-text-tertiary, #8A806F)',
            page: 'var(--wm-color-bg-page, #F5F1E8)',
            light: 'var(--wm-color-border, #D8C9AD)',
            disabled: 'var(--wm-color-bg-subtle, #ECE4D6)',

            // 主题色系统
            minor: 'var(--wm-color-secondary, #B8954A)',
            'btn-text': 'var(--wm-text-inverse, #FFFDF8)',

            // 主色（黑）
            primary: {
                DEFAULT: 'var(--wm-color-primary, #191713)',
                'light-3': '#332F27',
                'light-5': '#5A5345',
                'light-7': '#8C826E',
                'light-9': 'var(--wm-color-primary-soft, #F1E5C8)',
                'dark-2': 'var(--wm-color-primary-strong, #000000)'
            },

            // 辅助色（香槟金）
            secondary: {
                DEFAULT: 'var(--wm-color-secondary, #B8954A)',
                'light-3': '#C9AC6B',
                'light-5': '#DAC38E',
                'light-7': '#EBDBB5',
                'light-9': 'var(--wm-color-secondary-soft, #F1E5C8)',
                'dark-2': 'var(--wm-color-secondary-strong, #7D4C35)'
            },

            // CTA色（黑）
            cta: {
                DEFAULT: 'var(--wm-color-cta, #191713)',
                'light-3': '#332F27',
                'light-5': '#5A5345',
                'light-7': '#8C826E',
                'light-9': 'var(--wm-color-primary-soft, #F1E5C8)',
                'dark-2': 'var(--wm-color-primary-strong, #000000)'
            },

            // 点缀色（香槟金）
            accent: {
                DEFAULT: 'var(--wm-color-champagne, #D9BE82)',
                'light-3': '#E2CC9C',
                'light-5': '#ECDBB7',
                'light-7': '#F5EAD4',
                'light-9': 'var(--wm-color-champagne-soft, #FFF7EC)',
                'dark-2': 'var(--wm-color-gold, #B8954A)'
            },

            // 功能色彩
            success: {
                DEFAULT: 'var(--wm-color-success, #71806F)',
                'light-3': '#8B988A',
                'light-5': '#AAB4A9',
                'light-7': '#CBD1CA',
                'light-9': 'var(--wm-color-success-soft, #E8EFE6)',
                'dark-2': '#4D6049'
            },
            warning: {
                DEFAULT: 'var(--wm-color-warning, #B8954A)',
                'light-3': '#C9AC6B',
                'light-5': '#DAC38E',
                'light-7': '#EBDBB5',
                'light-9': 'var(--wm-color-warning-soft, #F1E5C8)',
                'dark-2': '#6F521B'
            },
            error: {
                DEFAULT: 'var(--wm-color-danger, #9A6B35)',
                'light-3': '#B0824E',
                'light-5': '#C79D6B',
                'light-7': '#DEB98E',
                'light-9': 'var(--wm-color-danger-soft, #F2DDD5)',
                'dark-2': '#7A3F1F'
            },
            info: {
                DEFAULT: 'var(--wm-color-info, #8178B6)',
                'light-3': '#9B94C5',
                'light-5': '#B6B1D5',
                'light-7': '#D2CEE5',
                'light-9': 'var(--wm-color-info-soft, #E8E6F0)',
                'dark-2': '#4F4A82'
            },

            // 背景色系统
            'bg-primary': 'var(--wm-color-bg-page, #F5F1E8)',
            'bg-secondary': 'var(--wm-color-bg-soft, #FAF6EE)',
            'bg-card': 'var(--wm-color-bg-card, #FFFDF8)',
            'bg-overlay': 'var(--wm-color-bg-mask, rgba(26, 26, 26, 0.58))',

            // 订单状态色彩
            'order-pending': '#B8954A',
            'order-unpaid': '#191713',
            'order-paid': '#71806F',
            'order-completed': '#8178B6',
            'order-cancelled': '#8A806F'
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
                card: 'var(--wm-radius-card, 44rpx)',
                'card-soft': 'var(--wm-radius-card-soft, 32rpx)',
                'card-large': 'var(--wm-radius-card-lg, 60rpx)',
                button: 'var(--wm-radius-action, 56rpx)',
                input: 'var(--wm-radius-control, 44rpx)',
                badge: 'var(--wm-radius-pill, 999rpx)'
            },
            boxShadow: {
                card: 'var(--wm-shadow-soft, 0 16rpx 36rpx rgba(74, 43, 24, 0.07))',
                'card-hover': 'var(--wm-shadow-card, 0 20rpx 48rpx rgba(74, 43, 24, 0.10))',
                'card-glass':
                    '0 16rpx 36rpx rgba(74, 43, 24, 0.07), 0 4rpx 10rpx rgba(217, 190, 130, 0.12)',
                'button-primary': 'var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18))',
                'button-cta': 'var(--wm-shadow-action, 0 20rpx 44rpx rgba(74, 43, 24, 0.18))',
                'input-focus': '0 0 0 6rpx rgba(217, 190, 130, 0.28)'
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
