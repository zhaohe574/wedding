type ToastIcon = 'success' | 'error' | 'loading' | 'none'

interface ToastOptions {
    title: string
    icon?: ToastIcon
    duration?: number
    mask?: boolean
}

interface ConfirmOptions {
    title?: string
    content: string
    showCancel?: boolean
    confirmText?: string
    cancelText?: string
    confirmColor?: string
    cancelColor?: string
}

const normalizeMessage = (message: unknown, fallback = '操作失败') => {
    if (typeof message === 'string' && message.trim()) {
        return message.trim()
    }
    if (message && typeof message === 'object') {
        const value = (message as { msg?: unknown; message?: unknown }).msg ?? (message as { message?: unknown }).message
        if (typeof value === 'string' && value.trim()) {
            return value.trim()
        }
    }
    return fallback
}

export const showToast = (message: unknown, options: Partial<ToastOptions> = {}) => {
    uni.showToast({
        title: normalizeMessage(message, options.title || '操作失败'),
        icon: options.icon || 'none',
        duration: options.duration || 1800,
        mask: options.mask || false
    })
}

export const showSuccess = (message: unknown, options: Partial<ToastOptions> = {}) => {
    showToast(message, {
        ...options,
        title: options.title || '操作成功',
        icon: 'success'
    })
}

export const showError = (message: unknown, fallback = '操作失败') => {
    showToast(normalizeMessage(message, fallback), { icon: 'none' })
}

export const confirmModal = (options: ConfirmOptions) => {
    return new Promise<boolean>((resolve) => {
        uni.showModal({
            title: options.title || '提示',
            content: options.content,
            showCancel: options.showCancel ?? true,
            confirmText: options.confirmText || '确定',
            cancelText: options.cancelText || '取消',
            confirmColor: options.confirmColor || '#111111',
            cancelColor: options.cancelColor || '#9a9388',
            success: (result) => resolve(!!result.confirm),
            fail: () => resolve(false)
        })
    })
}
