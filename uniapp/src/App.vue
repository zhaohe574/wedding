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
@import '@tuniao/tn-style/dist/uniapp/index.css';
@import '@tuniao/tn-icon/dist/index.css';

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
