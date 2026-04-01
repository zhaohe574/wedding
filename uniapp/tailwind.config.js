/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./index.html', './src/**/*.{html,js,ts,jsx,tsx,vue}'],
    theme: {
        colors: {
            // 中性色彩
            white: 'var(--color-white, #ffffff)',
            black: 'var(--color-black, #000000)',
            main: 'var(--color-main, #1E2432)',
            content: 'var(--color-content, #7F7B78)',
            muted: 'var(--color-muted, #B4ACA8)',
            page: 'var(--color-page, #FCFBF9)',
            light: 'var(--color-light, #EFE6E1)',
            disabled: 'var(--color-disabled, #D8D0CB)',

            // 主题色系统
            minor: 'var(--color-minor, #C99B73)',
            'btn-text': 'var(--color-btn-text, white)',

            // 主色（珊瑚红）
            primary: {
                DEFAULT: 'var(--color-primary, #E85A4F)',
                'light-3': 'var(--color-primary-light-3, #EE8C84)',
                'light-5': 'var(--color-primary-light-5, #F3AEA8)',
                'light-7': 'var(--color-primary-light-7, #F8D6D2)',
                'light-9': 'var(--color-primary-light-9, #FFF1EE)',
                'dark-2': 'var(--color-primary-dark-2, #D84D43)'
            },

            // 辅助色（香槟金）
            secondary: {
                DEFAULT: 'var(--color-secondary, #C99B73)',
                'light-3': 'var(--color-secondary-light-3, #D7B392)',
                'light-5': 'var(--color-secondary-light-5, #E4CAB0)',
                'light-7': 'var(--color-secondary-light-7, #F0E1D0)',
                'light-9': 'var(--color-secondary-light-9, #F8EFE7)',
                'dark-2': 'var(--color-secondary-dark-2, #B58660)'
            },

            // CTA色（沿用主色，避免多主题分裂）
            cta: {
                DEFAULT: 'var(--color-cta, #E85A4F)',
                'light-3': 'var(--color-cta-light-3, #EE8C84)',
                'light-5': 'var(--color-cta-light-5, #F3AEA8)',
                'light-7': 'var(--color-cta-light-7, #F8D6D2)',
                'light-9': 'var(--color-cta-light-9, #FFF1EE)',
                'dark-2': 'var(--color-cta-dark-2, #D84D43)'
            },

            // 点缀色（金香槟）
            accent: {
                DEFAULT: 'var(--color-accent, #C99B73)',
                'light-3': 'var(--color-accent-light-3, #D7B392)',
                'light-5': 'var(--color-accent-light-5, #E4CAB0)',
                'light-7': 'var(--color-accent-light-7, #F0E1D0)',
                'light-9': 'var(--color-accent-light-9, #F8EFE7)',
                'dark-2': 'var(--color-accent-dark-2, #B58660)'
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
            'bg-primary': 'var(--color-bg-primary, #FFF1EE)',
            'bg-secondary': 'var(--color-bg-secondary, #F8EFE7)',
            'bg-card': 'var(--color-bg-card, #FFFFFF)',
            'bg-overlay': 'var(--color-bg-overlay, rgba(30, 36, 50, 0.46))',

            // 订单状态色彩
            'order-pending': '#FF9900',
            'order-unpaid': '#F97316',
            'order-paid': '#10B981',
            'order-completed': '#6B7280',
            'order-cancelled': '#EF4444'
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
            sans: ['PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', 'sans-serif'],
            heading: ['SF Pro Display', 'PingFang SC', 'Microsoft YaHei', 'sans-serif']
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
                card: '24rpx',
                'card-large': '28rpx',
                button: '999rpx',
                input: '18rpx',
                badge: '20rpx'
            },
            boxShadow: {
                card: '0 14rpx 32rpx rgba(214, 185, 167, 0.16)',
                'card-hover': '0 18rpx 36rpx rgba(214, 185, 167, 0.2)',
                'card-glass':
                    '0 18rpx 36rpx rgba(214, 185, 167, 0.18), 0 8rpx 16rpx rgba(177, 108, 95, 0.08)',
                'button-primary': '0 14rpx 28rpx rgba(232, 90, 79, 0.22)',
                'button-cta': '0 14rpx 28rpx rgba(232, 90, 79, 0.22)',
                'input-focus': '0 0 0 6rpx rgba(232, 90, 79, 0.12)'
            },
            transitionDuration: {
                fast: '150ms',
                normal: '200ms',
                slow: '300ms'
            },
            backdropBlur: {
                glass: '20rpx'
            }
        }
    },
    plugins: [],
    corePlugins: {
        preflight: false
    }
}
