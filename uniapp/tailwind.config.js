/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./index.html', './src/**/*.{html,js,ts,jsx,tsx,vue}'],
    theme: {
        colors: {
            // 中性色彩
            white: 'var(--color-white, #ffffff)',
            black: 'var(--color-black, #000000)',
            main: 'var(--color-main, #111111)',
            content: 'var(--color-content, #5F5A50)',
            muted: 'var(--color-muted, #9A9388)',
            page: 'var(--color-page, #FFFFFF)',
            light: 'var(--color-light, #E7E2D6)',
            disabled: 'var(--color-disabled, #D8D3C7)',

            // 主题色系统
            minor: 'var(--color-minor, #C8A45D)',
            'btn-text': 'var(--color-btn-text, white)',

            // 主色（黑）
            primary: {
                DEFAULT: 'var(--color-primary, #0B0B0B)',
                'light-3': 'var(--color-primary-light-3, #4E4E4E)',
                'light-5': 'var(--color-primary-light-5, #858585)',
                'light-7': 'var(--color-primary-light-7, #C4C4C4)',
                'light-9': 'var(--color-primary-light-9, #F3F2EE)',
                'dark-2': 'var(--color-primary-dark-2, #000000)'
            },

            // 辅助色（香槟金）
            secondary: {
                DEFAULT: 'var(--color-secondary, #C8A45D)',
                'light-3': 'var(--color-secondary-light-3, #D8BE83)',
                'light-5': 'var(--color-secondary-light-5, #E4D1A9)',
                'light-7': 'var(--color-secondary-light-7, #EFE2C7)',
                'light-9': 'var(--color-secondary-light-9, #F7F0DF)',
                'dark-2': 'var(--color-secondary-dark-2, #9F7A2E)'
            },

            // CTA色（黑）
            cta: {
                DEFAULT: 'var(--color-cta, #0B0B0B)',
                'light-3': 'var(--color-cta-light-3, #4E4E4E)',
                'light-5': 'var(--color-cta-light-5, #858585)',
                'light-7': 'var(--color-cta-light-7, #C4C4C4)',
                'light-9': 'var(--color-cta-light-9, #F3F2EE)',
                'dark-2': 'var(--color-cta-dark-2, #000000)'
            },

            // 点缀色（香槟金）
            accent: {
                DEFAULT: 'var(--color-accent, #C8A45D)',
                'light-3': 'var(--color-accent-light-3, #D8BE83)',
                'light-5': 'var(--color-accent-light-5, #E4D1A9)',
                'light-7': 'var(--color-accent-light-7, #EFE2C7)',
                'light-9': 'var(--color-accent-light-9, #F7F0DF)',
                'dark-2': 'var(--color-accent-dark-2, #9F7A2E)'
            },

            // 功能色彩
            success: {
                DEFAULT: 'var(--color-success, #4D4A42)',
                'light-3': 'var(--color-success-light-3, #828078)',
                'light-5': 'var(--color-success-light-5, #A6A49D)',
                'light-7': 'var(--color-success-light-7, #CFCDC7)',
                'light-9': 'var(--color-success-light-9, #F3F2EE)',
                'dark-2': 'var(--color-success-dark-2, #34322C)'
            },
            warning: {
                DEFAULT: 'var(--color-warning, #9F7A2E)',
                'light-3': 'var(--color-warning-light-3, #B89B61)',
                'light-5': 'var(--color-warning-light-5, #D0BD8E)',
                'light-7': 'var(--color-warning-light-7, #E6DAB9)',
                'light-9': 'var(--color-warning-light-9, #F7F0DF)',
                'dark-2': 'var(--color-warning-dark-2, #7C5E20)'
            },
            error: {
                DEFAULT: 'var(--color-error, #5A4433)',
                'light-3': 'var(--color-error-light-3, #8C7867)',
                'light-5': 'var(--color-error-light-5, #AE9F92)',
                'light-7': 'var(--color-error-light-7, #D4CBC2)',
                'light-9': 'var(--color-error-light-9, #F3F2EE)',
                'dark-2': 'var(--color-error-dark-2, #3B2A20)'
            },
            info: {
                DEFAULT: 'var(--color-info, #6C665C)',
                'light-3': 'var(--color-info-light-3, #9A9388)',
                'light-5': 'var(--color-info-light-5, #BBB5AA)',
                'light-7': 'var(--color-info-light-7, #DDD8CF)',
                'light-9': 'var(--color-info-light-9, #F8F7F2)',
                'dark-2': 'var(--color-info-dark-2, #4D4941)'
            },

            // 背景色系统
            'bg-primary': 'var(--color-bg-primary, #F3F2EE)',
            'bg-secondary': 'var(--color-bg-secondary, #F7F0DF)',
            'bg-card': 'var(--color-bg-card, #FFFFFF)',
            'bg-overlay': 'var(--color-bg-overlay, rgba(11, 11, 11, 0.54))',

            // 订单状态色彩
            'order-pending': '#9F7A2E',
            'order-unpaid': '#0B0B0B',
            'order-paid': '#4D4A42',
            'order-completed': '#6C665C',
            'order-cancelled': '#9A9388'
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
                card: '0 14rpx 32rpx rgba(17, 17, 17, 0.06)',
                'card-hover': '0 18rpx 36rpx rgba(17, 17, 17, 0.09)',
                'card-glass':
                    '0 18rpx 36rpx rgba(17, 17, 17, 0.08), 0 8rpx 16rpx rgba(200, 164, 93, 0.08)',
                'button-primary': '0 14rpx 28rpx rgba(11, 11, 11, 0.18)',
                'button-cta': '0 14rpx 28rpx rgba(11, 11, 11, 0.18)',
                'input-focus': '0 0 0 6rpx rgba(200, 164, 93, 0.14)'
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
