import { confirmModal, showToast } from '@/utils/feedback'

export interface MiniProgramUpdateConfig {
    version?: string
    force_update?: number | string | boolean
    update_title?: string
    update_content?: string
}

let updatePromptVisible = false
let updateReadyHandled = false
let activeConfig: MiniProgramUpdateConfig = {}

const getUpdateText = (value: unknown, fallback: string) => {
    return typeof value === 'string' && value.trim() ? value.trim() : fallback
}

const isForceUpdate = (value: MiniProgramUpdateConfig['force_update']) => {
    return value === true || value === 1 || value === '1'
}

export const updateMiniProgramUpdateConfig = (config: MiniProgramUpdateConfig = {}) => {
    activeConfig = {
        ...activeConfig,
        ...config
    }
}

export const setupMiniProgramUpdate = (config: MiniProgramUpdateConfig = {}) => {
    if (config) {
        updateMiniProgramUpdateConfig(config)
    }

    const getManager = () => {
        if (typeof uni !== 'undefined' && typeof uni.getUpdateManager === 'function') {
            return uni.getUpdateManager()
        }
        // #ifdef MP-WEIXIN
        // @ts-ignore
        if (typeof wx !== 'undefined' && typeof wx.getUpdateManager === 'function') {
            // @ts-ignore
            return wx.getUpdateManager()
        }
        // #endif
        return null
    }

    const updateManager = getManager()
    if (!updateManager) {
        return
    }

    updateManager.onCheckForUpdate((res: any) => {
        console.log(
            '[UpdateManager] 检查更新结果:',
            res?.hasUpdate
                ? '发现新版本，微信客户端正在后台静默下载'
                : '未检测到新版本（或当前已是最新版）'
        )
    })

    updateManager.onUpdateReady(async () => {
        console.log('[UpdateManager] 新版本已下载完毕，准备提示用户更新')
        if (updatePromptVisible || updateReadyHandled) {
            return
        }

        updatePromptVisible = true
        updateReadyHandled = true

        const forceUpdate = isForceUpdate(activeConfig.force_update)
        const confirmed = await confirmModal({
            title: getUpdateText(activeConfig.update_title, '新版本已准备好'),
            content: getUpdateText(
                activeConfig.update_content,
                '为保证支付、订单和档期功能正常，请重启后继续使用。'
            ),
            showCancel: !forceUpdate,
            confirmText: '立即更新',
            cancelText: '稍后再说',
            confirmColor: '#111111'
        })

        updatePromptVisible = false

        if (confirmed || forceUpdate) {
            updateManager.applyUpdate()
        }
    })

    updateManager.onUpdateFailed(() => {
        console.error('[UpdateManager] 新版本下载失败')
        showToast('新版本下载失败，请稍后重新打开小程序')
    })
}

