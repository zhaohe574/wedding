import { confirmModal, showToast } from '@/utils/feedback'

interface MiniProgramUpdateConfig {
    version?: string
    force_update?: number | string | boolean
    update_title?: string
    update_content?: string
}

let updatePromptVisible = false
let updateReadyHandled = false

const getUpdateText = (value: unknown, fallback: string) => {
    return typeof value === 'string' && value.trim() ? value.trim() : fallback
}

const isForceUpdate = (value: MiniProgramUpdateConfig['force_update']) => {
    return value === true || value === 1 || value === '1'
}

export const setupMiniProgramUpdate = (config: MiniProgramUpdateConfig = {}) => {
    // #ifdef MP-WEIXIN
    if (typeof uni.getUpdateManager !== 'function') {
        return
    }

    const updateManager = uni.getUpdateManager()

    updateManager.onCheckForUpdate(() => {
        // 只在更新包下载完成后提示用户，避免检查阶段提前打扰。
    })

    updateManager.onUpdateReady(async () => {
        if (updatePromptVisible || updateReadyHandled) {
            return
        }

        updatePromptVisible = true
        updateReadyHandled = true

        const forceUpdate = isForceUpdate(config.force_update)
        const confirmed = await confirmModal({
            title: getUpdateText(config.update_title, '新版本已准备好'),
            content: getUpdateText(
                config.update_content,
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
        showToast('新版本下载失败，请稍后重新打开小程序')
    })
    // #endif
}
