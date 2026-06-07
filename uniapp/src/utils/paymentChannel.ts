export const PAYMENT_CHANNEL_ONLINE = 1
export const PAYMENT_CHANNEL_OFFLINE = 2
export const PAY_WAY_OFFLINE = 4

const OFFLINE_COLLECTION_STAGES = ['full', 'balance']

const numberValue = (value: any) => Number(value || 0)
const normalizeStage = (value: any) => String(value || '').trim()

export const isOfflineCollectionStage = (target: any) => {
    if (!target) return false

    const stages = [
        normalizeStage(target?.need_pay),
        normalizeStage(target?.current_pay_stage),
        normalizeStage(target?.payment_stage)
    ]

    return stages.some((stage) => OFFLINE_COLLECTION_STAGES.includes(stage))
}

export const hasExplicitOfflineCollectionFlag = (target: any) => {
    return (
        numberValue(target?.offline_collection_required) === 1 ||
        numberValue(target?.is_offline_collection) === 1 ||
        numberValue(target?.offline_collection_available) === 1
    )
}

export const isOfflineCollectionEnabled = (target: any) => {
    if (!target) return false

    if (hasExplicitOfflineCollectionFlag(target)) {
        return true
    }

    return numberValue(target?.offline_collection_enabled) === 1 && isOfflineCollectionStage(target)
}

export const resolvePaymentChannel = (target: any) => {
    const paymentChannel = numberValue(target?.payment_channel)

    if (paymentChannel === PAYMENT_CHANNEL_OFFLINE) {
        return PAYMENT_CHANNEL_OFFLINE
    }

    if (
        numberValue(target?.pay_type) === PAY_WAY_OFFLINE ||
        numberValue(target?.pay_way) === PAY_WAY_OFFLINE ||
        !!target?.pay_voucher ||
        isOfflineCollectionEnabled(target)
    ) {
        return PAYMENT_CHANNEL_OFFLINE
    }

    if (paymentChannel === PAYMENT_CHANNEL_ONLINE) {
        return PAYMENT_CHANNEL_ONLINE
    }

    return PAYMENT_CHANNEL_ONLINE
}

export const shouldUseOfflineCollection = (target: any) => {
    if (!target) return false

    return resolvePaymentChannel(target) === PAYMENT_CHANNEL_OFFLINE || isOfflineCollectionEnabled(target)
}
