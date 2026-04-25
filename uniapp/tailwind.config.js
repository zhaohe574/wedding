/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['./index.html', './src/**/*.{html,js,ts,jsx,tsx,vue}'],
    theme: {
        colors: {
            // 中性色彩
            white: 'var(--color-white, #ffffff)',
            black: 'var(--color-black, #000000)',
            main: 'var(--color-main, #111111)',
            content: 'var(--color-content, #56524A)',
            muted: 'var(--color-muted, #8E887D)',
            page: 'var(--color-page, #FFFFFF)',
            light: 'var(--color-light, #E2DED5)',
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
                'light-9': 'var(--color-primary-light-9, #F2F1EC)',
                'dark-2': 'var(--color-primary-dark-2, #000000)'
            },

            // 辅助色（香槟金）
            secondary: {
                DEFAULT: 'var(--color-secondary, #C8A45D)',
                'light-3': 'var(--color-secondary-light-3, #D8BE83)',
                'light-5': 'var(--color-secondary-light-5, #E4D1A9)',
                'light-7': 'var(--color-secondary-light-7, #EFE2C7)',
                'light-9': 'var(--color-secondary-light-9, #F8F2E4)',
                'dark-2': 'var(--color-secondary-dark-2, #9F7A2E)'
            },

            // CTA色（黑）
            cta: {
                DEFAULT: 'var(--color-cta, #D0021B)',
                'light-3': 'var(--color-cta-light-3, #E05263)',
                'light-5': 'var(--color-cta-light-5, #EC8A96)',
                'light-7': 'var(--color-cta-light-7, #F6BDC5)',
                'light-9': 'var(--color-cta-light-9, #FBE8EB)',
                'dark-2': 'var(--color-cta-dark-2, #A90016)'
            },

            // 点缀色（香槟金）
            accent: {
                DEFAULT: 'var(--color-accent, #C8A45D)',
                'light-3': 'var(--color-accent-light-3, #D8BE83)',
                'light-5': 'var(--color-accent-light-5, #E4D1A9)',
                'light-7': 'var(--color-accent-light-7, #EFE2C7)',
                'light-9': 'var(--color-accent-light-9, #F8F2E4)',
                'dark-2': 'var(--color-accent-dark-2, #9F7A2E)'
            },

            // 功能色彩
            success: {
                DEFAULT: 'var(--color-success, #4F6F5A)',
                'light-3': 'var(--color-success-light-3, #7F9B86)',
                'light-5': 'var(--color-success-light-5, #A8BCA8)',
                'light-7': 'var(--color-success-light-7, #D5E0D3)',
                'light-9': 'var(--color-success-light-9, #EEF3EE)',
                'dark-2': 'var(--color-success-dark-2, #354B3D)'
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
                DEFAULT: 'var(--color-error, #8A4B45)',
                'light-3': 'var(--color-error-light-3, #AF766F)',
                'light-5': 'var(--color-error-light-5, #C9A09B)',
                'light-7': 'var(--color-error-light-7, #E5CFCC)',
                'light-9': 'var(--color-error-light-9, #F7ECEE)',
                'dark-2': 'var(--color-error-dark-2, #673632)'
            },
            info: {
                DEFAULT: 'var(--color-info, #596A7A)',
                'light-3': 'var(--color-info-light-3, #8696A4)',
                'light-5': 'var(--color-info-light-5, #AEB9C3)',
                'light-7': 'var(--color-info-light-7, #D8DFE6)',
                'light-9': 'var(--color-info-light-9, #F5F7F8)',
                'dark-2': 'var(--color-info-dark-2, #3F4C58)'
            },

            // 背景色系统
            'bg-primary': 'var(--color-bg-primary, #F6F5F2)',
            'bg-secondary': 'var(--color-bg-secondary, #F8F2E4)',
            'bg-card': 'var(--color-bg-card, #FFFFFF)',
            'bg-overlay': 'var(--color-bg-overlay, rgba(11, 11, 11, 0.54))',

            // 订单状态色彩
            'order-pending': '#9F7A2E',
            'order-unpaid': '#0B0B0B',
            'order-paid': '#4F6F5A',
            'order-completed': '#596A7A',
            'order-cancelled': '#8E887D'
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
                card: '16rpx',
                'card-large': '20rpx',
                button: '999rpx',
                input: '16rpx',
                badge: '999rpx'
            },
            boxShadow: {
                card: '0 8rpx 20rpx rgba(17, 17, 17, 0.05)',
                'card-hover': '0 12rpx 28rpx rgba(17, 17, 17, 0.07)',
                'card-glass':
                    '0 12rpx 28rpx rgba(17, 17, 17, 0.07), 0 4rpx 10rpx rgba(200, 164, 93, 0.06)',
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
