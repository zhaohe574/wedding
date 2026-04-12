export type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

export interface StatusMeta {
    label: string
    tone: BadgeTone
    summary: string
}

export interface TypeMeta {
    label: string
    tone: BadgeTone
}

const safeText = (value: unknown) => (typeof value === 'string' ? value.trim() : '')

const changeStatusMap: Record<number, StatusMeta> = {
    0: { label: '待审核', tone: 'warning', summary: '平台正在审核本次变更申请。' },
    1: { label: '待执行', tone: 'info', summary: '申请已通过，平台将按确认结果执行。' },
    2: { label: '未通过', tone: 'danger', summary: '申请未通过审核，请查看处理说明。' },
    3: { label: '已完成', tone: 'success', summary: '本次变更已执行完成。' },
    4: { label: '已取消', tone: 'neutral', summary: '当前申请已取消，不再继续处理。' }
}

const pauseStatusMap: Record<number, StatusMeta> = {
    0: { label: '待审核', tone: 'warning', summary: '平台正在审核本次暂停申请。' },
    1: { label: '暂停中', tone: 'info', summary: '订单当前处于暂停状态，请留意恢复时间。' },
    2: { label: '已恢复', tone: 'success', summary: '订单已恢复正常履约状态。' },
    3: { label: '未通过', tone: 'danger', summary: '暂停申请未通过审核，请查看原因。' },
    4: { label: '已取消', tone: 'neutral', summary: '当前暂停申请已取消。' }
}

const changeTypeMap: Record<number, TypeMeta> = {
    1: { label: '改期申请', tone: 'info' },
    2: { label: '换人申请', tone: 'warning' },
    3: { label: '加项申请', tone: 'success' },
    4: { label: '附加服务变更', tone: 'neutral' }
}

const pauseTypeMap: Record<number, TypeMeta> = {
    1: { label: '疫情原因', tone: 'danger' },
    2: { label: '突发事件', tone: 'warning' },
    3: { label: '个人原因', tone: 'info' },
    4: { label: '其他原因', tone: 'neutral' }
}

export const orderChangeOfflineCopy = {
    title: '附加服务变更已下线',
    description:
        '旧版附加服务变更申请能力已关闭，当前页面仅保留状态说明与回退入口，后续如重新开放会以新版流程重新接入。'
}

export const getChangeStatusMeta = (status: number) =>
    changeStatusMap[Number(status)] || changeStatusMap[0]

export const getPauseStatusMeta = (status: number) =>
    pauseStatusMap[Number(status)] || pauseStatusMap[0]

export const getChangeTypeMeta = (type: number) => changeTypeMap[Number(type)] || changeTypeMap[1]

export const getPauseTypeMeta = (type: number) => pauseTypeMap[Number(type)] || pauseTypeMap[4]

export const getValueText = (value: unknown, fallback = '待补充') => {
    const text = safeText(value)
    return text || fallback
}

export const normalizeImageList = (value: unknown): string[] => {
    if (!Array.isArray(value)) {
        return []
    }

    return value
        .map((item) => {
            if (typeof item === 'string') {
                return item.trim()
            }

            if (item && typeof item === 'object') {
                return safeText((item as any).url || (item as any).uri || (item as any).value)
            }

            return ''
        })
        .filter(Boolean)
}

export const openImagePreview = (images: string[], index = 0) => {
    const list = normalizeImageList(images)
    if (!list.length) {
        return
    }

    uni.previewImage({
        urls: list,
        current: Math.max(0, index)
    })
}

export const formatCurrency = (value: unknown) => {
    const amount = Number(value || 0)
    if (!Number.isFinite(amount)) {
        return '0'
    }

    return amount.toFixed(2).replace(/\.00$/, '')
}

export const getPageStyleWithPopupLock = (pageStyle: string, locked: boolean) => {
    const base = pageStyle.trim()
    const separator = !base || base.endsWith(';') ? '' : ';'
    return `${base}${separator}overflow:${locked ? 'hidden' : 'visible'};`
}

export const getRemainDays = (endDateText: unknown) => {
    const text = safeText(endDateText)
    if (!text) {
        return -1
    }

    const endDate = new Date(text.replace(/-/g, '/'))
    if (Number.isNaN(endDate.getTime())) {
        return -1
    }

    const today = new Date()
    today.setHours(0, 0, 0, 0)
    return Math.ceil((endDate.getTime() - today.getTime()) / (24 * 60 * 60 * 1000))
}

export const getOrderChangeListUrl = (type: 'change' | 'pause' = 'change') =>
    `/packages/pages/order_change/list?type=${type}`
