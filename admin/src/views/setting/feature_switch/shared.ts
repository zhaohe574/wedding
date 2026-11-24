/**
 * 功能开关风险等级权限模型（批次 2 拆分后共享）。
 *
 * 「内容风控与审核」域的风险等级矩阵页与 feature_switch 兼容兜底页
 * 使用同一套类型与归一化逻辑，统一从这里取，避免重复定义。
 */

export type RiskAbilityOption = {
    code: string
    name: string
}

export type RiskRankPermission = {
    enabled_abilities: string[]
    comment_force_review: boolean
}

export type RiskRankPermissions = Record<number, RiskRankPermission>

export const defaultRiskAbilityOptions: RiskAbilityOption[] = [
    { code: 'profile', name: '资料' },
    { code: 'content_publish', name: '内容发布' },
    { code: 'comment', name: '发表评论' },
    { code: 'interaction', name: '互动' },
    { code: 'activity', name: '活动报名' },
    { code: 'schedule', name: '档期' },
    { code: 'order', name: '订单' },
    { code: 'payment', name: '支付' },
    { code: 'after_sale', name: '售后投诉' },
    { code: 'customer_service', name: '发起咨询' },
    { code: 'upload', name: '上传' },
    { code: 'notification', name: '通知' },
    { code: 'staff_center', name: '服务人员中心' }
]

export const riskRankRows = [
    { rank: 0, label: '0 正常' },
    { rank: 1, label: '1 低风险' },
    { rank: 2, label: '2 中风险' },
    { rank: 3, label: '3 高风险' },
    { rank: 4, label: '4 极高风险' }
]

export const defaultRiskAbilityCodes = defaultRiskAbilityOptions.map((item) => item.code)

export const createDefaultRiskPermissions = (): RiskRankPermissions => ({
    0: { enabled_abilities: [...defaultRiskAbilityCodes], comment_force_review: false },
    1: { enabled_abilities: [...defaultRiskAbilityCodes], comment_force_review: false },
    2: { enabled_abilities: [...defaultRiskAbilityCodes], comment_force_review: true },
    3: { enabled_abilities: [], comment_force_review: false },
    4: { enabled_abilities: [], comment_force_review: false }
})

export const normalizeBoolean = (value: unknown, fallback = false) => {
    if (typeof value === 'boolean') {
        return value
    }
    if (value === 1 || value === '1' || value === 'true') {
        return true
    }
    if (value === 0 || value === '0' || value === 'false') {
        return false
    }
    return fallback
}

export const normalizeRiskPermissions = (value: unknown): RiskRankPermissions => {
    const fallback = createDefaultRiskPermissions()
    if (!value || typeof value !== 'object') {
        return fallback
    }

    const source = value as Record<string, Partial<RiskRankPermission>>
    const result = {} as RiskRankPermissions
    riskRankRows.forEach(({ rank }) => {
        const rankConfig = source[String(rank)]
        if (!rankConfig || typeof rankConfig !== 'object') {
            result[rank] = {
                enabled_abilities: [...fallback[rank].enabled_abilities],
                comment_force_review: fallback[rank].comment_force_review
            }
            return
        }

        const enabledAbilities = Array.isArray(rankConfig.enabled_abilities)
            ? rankConfig.enabled_abilities.filter((code) => defaultRiskAbilityCodes.includes(code))
            : fallback[rank].enabled_abilities

        result[rank] = {
            enabled_abilities: Array.from(new Set(enabledAbilities)),
            comment_force_review: normalizeBoolean(rankConfig.comment_force_review, fallback[rank].comment_force_review)
        }
    })

    return result
}
