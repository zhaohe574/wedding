<script setup lang="ts">
import { onLaunch, onShow } from '@dcloudio/uni-app'
import { captureOaInvitation } from './utils/oa-invitation'
import { useAppStore } from './stores/app'
import { useUserStore } from './stores/user'
import { useThemeStore } from './stores/theme'
import { setupMiniProgramUpdate, updateMiniProgramUpdateConfig } from './utils/miniProgramUpdate'
const appStore = useAppStore()
const { getUser } = useUserStore()
const { getTheme } = useThemeStore()
onShow((options: any) => captureOaInvitation(options?.path || '', options?.query || {}))
onLaunch(async () => {
    // 1. 立即同步注册小程序更新监听（避免等待网络请求期间错过微信底层派发的 onUpdateReady 回调）
    setupMiniProgramUpdate()

    getTheme()
    const config = await appStore.getConfig()
    if (config?.app_update) {
        updateMiniProgramUpdateConfig(config.app_update)
    }

    await getUser()
})
</script>
<style lang="scss">
// tnui 图标字体（BaseIcon 依赖，113 处使用），必须保持全局引入
@import '@tuniao/tn-icon/dist/index.css';

/**
 * tnui 基础样式按需内联。
 *
 * 背景：原实现整包引入 `@tuniao/tn-style/dist/uniapp/index.css`（约 53KB 原子类），
 * 该文件 676 条规则中 252 条是 tn-w-* / tn-flex-* 等原子类，项目源码零使用，
 * 却全部被打进 app.wxss（小程序主包第 4 大文件）。
 *
 * 现只保留 tnui 组件运行时真正依赖的部分（已按编译产物 node-modules/*.wxss 实测确认）：
 *   1. --tn-* 设计变量（popup / empty / icon 等组件取色与字号）
 *   2. .tn-u-safe-area / .tn-u-safe-area--more（popup 底部安全区）
 *   3. .tn-u-btn-clear / .tn-u-btn-hover（button / modal / action-sheet / picker）
 * 其余原子类与 reset 由下方项目自有样式覆盖（box-sizing 等已在下方定义）。
 */
body,
page {
    --tn-color-white: #ffffff;
    --tn-color-black: #000000;
    --tn-color-primary-rgb: 1, 190, 255;
    --tn-color-success-rgb: 0, 216, 134;
    --tn-color-warning-rgb: 251, 189, 18;
    --tn-color-danger-rgb: 255, 68, 79;
    --tn-color-error-rgb: 255, 68, 79;
    --tn-color-info-rgb: 156, 156, 156;
    --tn-color-red-rgb: 232, 58, 48;
    --tn-color-purplered-rgb: 231, 47, 140;
    --tn-color-purple-rgb: 137, 47, 232;
    --tn-color-bluepurple-rgb: 95, 79, 217;
    --tn-color-aquablue-rgb: 54, 70, 255;
    --tn-color-blue-rgb: 61, 126, 255;
    --tn-color-indigo-rgb: 49, 201, 232;
    --tn-color-cyan-rgb: 45, 232, 189;
    --tn-color-teal-rgb: 36, 240, 131;
    --tn-color-green-rgb: 49, 231, 73;
    --tn-color-yellowgreen-rgb: 164, 232, 47;
    --tn-color-lime-rgb: 213, 235, 0;
    --tn-color-yellow-rgb: 255, 244, 32;
    --tn-color-orangeyellow-rgb: 255, 202, 40;
    --tn-color-orange-rgb: 255, 167, 38;
    --tn-color-orangered-rgb: 255, 112, 67;
    --tn-color-brown-rgb: 145, 79, 44;
    --tn-color-grey-rgb: 120, 144, 156;
    --tn-color-gray-rgb: 170, 170, 170;
    --tn-font-size-4xl: 64rpx;
    --tn-font-size-3xl: 52rpx;
    --tn-font-size-2xl: 40rpx;
    --tn-font-size-xl: 36rpx;
    --tn-font-size-lg: 32rpx;
    --tn-font-size: 28rpx;
    --tn-font-size-sm: 26rpx;
    --tn-font-size-xs: 24rpx;
    --tn-pm-size-xl: 50rpx;
    --tn-pm-size-lg: 40rpx;
    --tn-pm-size: 30rpx;
    --tn-pm-size-sm: 20rpx;
    --tn-pm-size-xs: 10rpx;
    --tn-pm-size-0: 0rpx;
    --tn-border-radius: 15rpx;
    --tn-border-radius-round: 5000rpx;
    --tn-border-width-bold: 6rpx;
    --tn-border-width: 2rpx;
}

.tn-u-safe-area {
    padding-bottom: 0;
    padding-bottom: constant(safe-area-inset-bottom) !important;
    padding-bottom: env(safe-area-inset-bottom) !important;
}

.tn-u-safe-area--more {
    padding-bottom: 20rpx;
    padding-bottom: calc(constant(safe-area-inset-bottom) + 20rpx) !important;
    padding-bottom: calc(env(safe-area-inset-bottom) + 20rpx) !important;
}

.tn-u-btn-clear {
    background-color: transparent;
    padding: 0;
    margin: 0;
    font-size: inherit;
    line-height: inherit;
    border-radius: inherit;
    color: inherit;
    border: none;
}

.tn-u-btn-clear::after {
    border: none;
}

.tn-u-btn-hover {
    opacity: 0.8;
    box-shadow: inset 10rpx 2rpx 40rpx 0rpx rgba(0, 0, 0, 0.05);
}

page {
    background-color: var(--wm-color-bg-page, #ffffff);
    font-family: var(--wm-font-family-body, 'PingFang SC'), 'Hiragino Sans GB', 'Microsoft YaHei',
        'Noto Sans SC', sans-serif;
    font-size: 28rpx;
    line-height: 1.6;
    color: var(--wm-text-primary, #111111);
    letter-spacing: 0;
}

page,
view,
text,
button,
input,
textarea,
navigator,
scroll-view,
swiper,
swiper-item,
image {
    box-sizing: border-box;
    font-family: inherit;
}

::-webkit-scrollbar {
    width: 8rpx;
    height: 8rpx;
}

::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.9);
}

::-webkit-scrollbar-thumb {
    background: rgba(200, 164, 93, 0.28);
    border-radius: 4rpx;

    &:hover {
        background: rgba(200, 164, 93, 0.42);
    }
}
</style>
