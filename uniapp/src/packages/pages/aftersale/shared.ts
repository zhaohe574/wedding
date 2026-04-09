export type BadgeTone = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

export interface OptionItem {
    label: string
    value: number
}

export interface OrderOption {
    value: number
    label: string
    raw: any
}

export interface StatusMeta {
    label: string
    tone: BadgeTone
    icon: string
    summary: string
}

export interface FilterTabItem {
    label: string
    value: number | string
    count?: number
}

export interface StatusMetricItem {
    label: string
    value: string
    tone?: BadgeTone
}

export type QuestionType = 'single' | 'multiple' | 'textarea'

export interface NormalizedQuestion {
    key: string
    title: string
    type: QuestionType
    options: string[]
    placeholder?: string
}

export const ticketTypeOptions: OptionItem[] = [
    { label: '投诉', value: 1 },
    { label: '咨询', value: 2 },
    { label: '售后', value: 3 },
    { label: '建议', value: 4 },
    { label: '其他', value: 5 }
]

export const complaintTypeOptions: OptionItem[] = [
    { label: '服务态度', value: 1 },
    { label: '履约偏差', value: 2 },
    { label: '沟通问题', value: 3 },
    { label: '执行落差', value: 4 },
    { label: '其他', value: 5 }
]

export const complaintLevelOptions: OptionItem[] = [
    { label: '一般', value: 1 },
    { label: '严重', value: 2 },
    { label: '紧急', value: 3 }
]

const ticketStatusMap: Record<number, StatusMeta> = {
    0: { label: '待处理', tone: 'warning', icon: 'clock', summary: '等待平台接单处理' },
    1: { label: '处理中', tone: 'info', icon: 'loading', summary: '平台正在跟进你的问题' },
    2: { label: '待确认', tone: 'neutral', icon: 'shield-check', summary: '处理方案已给出，等待你确认' },
    3: { label: '已解决', tone: 'success', icon: 'check-circle', summary: '本次工单已解决' },
    4: { label: '已关闭', tone: 'neutral', icon: 'close-circle', summary: '工单已结束' },
    5: { label: '已取消', tone: 'neutral', icon: 'close-circle', summary: '你已取消当前工单' }
}

const complaintStatusMap: Record<number, StatusMeta> = {
    0: { label: '待受理', tone: 'warning', icon: 'clock', summary: '平台待受理并开始处理投诉' },
    1: { label: '处理中', tone: 'info', icon: 'loading', summary: '平台正在核查并推进处理' },
    2: { label: '已处理', tone: 'success', icon: 'check-circle', summary: '处理结果已生成，可查看并评价' },
    3: { label: '已申诉', tone: 'danger', icon: 'warning-circle', summary: '当前投诉已进入申诉状态' },
    4: { label: '已关闭', tone: 'neutral', icon: 'close-circle', summary: '投诉流程已关闭' }
}

const complaintLevelMap: Record<number, StatusMeta> = {
    1: { label: '一般', tone: 'neutral', icon: 'info-circle', summary: '常规处理等级' },
    2: { label: '严重', tone: 'warning', icon: 'warning-circle', summary: '需要尽快处理' },
    3: { label: '紧急', tone: 'danger', icon: 'warning-circle-fill', summary: '需要优先处理' }
}

const callbackStatusMap: Record<number, StatusMeta> = {
    0: { label: '待填写', tone: 'warning', icon: 'edit', summary: '还有问卷待提交' },
    1: { label: '已完成', tone: 'success', icon: 'check-circle', summary: '问卷已提交' }
}

const safeText = (value: unknown) => (typeof value === 'string' ? value.trim() : '')

export const getTicketStatusMeta = (status: number) =>
    ticketStatusMap[Number(status)] || ticketStatusMap[0]

export const getComplaintStatusMeta = (status: number) =>
    complaintStatusMap[Number(status)] || complaintStatusMap[0]

export const getComplaintLevelMeta = (level: number) =>
    complaintLevelMap[Number(level)] || complaintLevelMap[1]

export const getCallbackStatusMeta = (status: number) =>
    callbackStatusMap[Number(status)] || callbackStatusMap[0]

export const normalizeMediaList = (value: unknown): string[] => {
    if (!Array.isArray(value)) {
        return []
    }

    return value
        .map((item) => {
            if (typeof item === 'string') {
                return item.trim()
            }

            if (item && typeof item === 'object') {
                return safeText((item as any).uri || (item as any).url || (item as any).value)
            }

            return ''
        })
        .filter(Boolean)
}

export const toOrderOptions = (list: any[]): OrderOption[] =>
    (Array.isArray(list) ? list : []).map((item) => ({
        value: Number(item?.id || item?.value || 0),
        label: safeText(item?.order_sn || item?.label || `订单 #${item?.id || ''}`),
        raw: item
    }))

export const pickOrderByPicker = (options: OrderOption[], event: any) => {
    const index = Number(event?.detail?.value ?? event?.[0] ?? 0)
    return options[index]
}

export const formatRelativeStamp = (value: unknown) => {
    const text = safeText(value)
    if (!text) {
        return ''
    }

    const date = new Date(text.replace(/-/g, '/'))
    if (Number.isNaN(date.getTime())) {
        return text
    }

    const now = Date.now()
    const diff = now - date.getTime()
    const minute = 60 * 1000
    const hour = 60 * minute
    const day = 24 * hour

    if (diff < hour) {
        const minutes = Math.max(1, Math.floor(diff / minute))
        return `${minutes} 分钟前`
    }

    if (diff < day) {
        return `${Math.max(1, Math.floor(diff / hour))} 小时前`
    }

    if (diff < day * 7) {
        return `${Math.max(1, Math.floor(diff / day))} 天前`
    }

    return text.slice(0, 16)
}

export const formatSubmitTimeLabel = (value: unknown) => {
    const text = safeText(value)
    if (!text) {
        return '待补充'
    }

    const date = new Date(text.replace(/-/g, '/'))
    if (Number.isNaN(date.getTime())) {
        return text
    }

    const now = new Date()
    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const startOfTarget = new Date(date.getFullYear(), date.getMonth(), date.getDate())
    const diffDays = Math.round(
        (startOfToday.getTime() - startOfTarget.getTime()) / (24 * 60 * 60 * 1000)
    )

    const hour = `${date.getHours()}`.padStart(2, '0')
    const minute = `${date.getMinutes()}`.padStart(2, '0')

    if (diffDays === 0) {
        return `今天 ${hour}:${minute}`
    }

    if (diffDays === 1) {
        return `昨天 ${hour}:${minute}`
    }

    const month = `${date.getMonth() + 1}`.padStart(2, '0')
    const day = `${date.getDate()}`.padStart(2, '0')
    return `${month}.${day} ${hour}:${minute}`
}

export const getValueText = (value: unknown, fallback = '暂未更新') => {
    const text = safeText(value)
    return text || fallback
}

export const getCountText = (value: unknown) => String(Number(value || 0))

export const getBadgeToneClass = (tone: BadgeTone) => `aftersale-tone--${tone}`

export const normalizeQuestionnaireQuestions = (questions: any[]): NormalizedQuestion[] => {
    if (!Array.isArray(questions)) {
        return []
    }

    return questions.map((question, index) => {
        const rawType = safeText(question?.type || question?.question_type).toLowerCase()
        const options = Array.isArray(question?.options)
            ? question.options
                  .map((item: any) =>
                      typeof item === 'string'
                          ? item
                          : safeText(item?.label || item?.text || item?.value)
                  )
                  .filter(Boolean)
            : []

        let type: QuestionType = 'single'
        if (rawType.includes('multi') || rawType.includes('checkbox')) {
            type = 'multiple'
        } else if (rawType.includes('text') || rawType.includes('textarea') || !options.length) {
            type = 'textarea'
        }

        return {
            key: String(question?.id || question?.key || index),
            title: safeText(question?.title || question?.question || `问题 ${index + 1}`),
            type,
            options,
            placeholder: safeText(question?.placeholder)
        }
    })
}

export const createQuestionAnswerMap = (questions: NormalizedQuestion[]) => {
    const map: Record<string, string | string[]> = {}
    questions.forEach((question) => {
        map[question.key] = question.type === 'multiple' ? [] : ''
    })
    return map
}

export const buildQuestionnaireAnswers = (
    questions: NormalizedQuestion[],
    answerMap: Record<string, string | string[]>
) =>
    questions.map((question) => ({
        key: question.key,
        title: question.title,
        value: answerMap[question.key]
    }))

export const openImagePreview = (images: string[], index = 0) => {
    const list = normalizeMediaList(images)
    if (!list.length) {
        return
    }

    uni.previewImage({
        urls: list,
        current: Math.max(0, index)
    })
}
