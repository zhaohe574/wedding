/**
 * 客户端通用状态文案 SSOT（体验规范 §4.5；主理人裁决 O13/O14）。
 * 来源：docs/redesign/03-组件样式清单与验收.md §4.2 / §4.3
 * 边界：
 *  - 业务错误文案 SSOT = shared/contracts/error-codes.ts 的 ERROR_CODES[code].msg（后端可覆盖）。
 *  - 本文件只负责「客户端通用状态文案」：loading / empty / 动作词 / PAY_* 结果。
 *  - 二者不重复、不覆盖；ERR_DUPLICATE / ERR_RATE_LIMIT 由 §4.8 脚本校验与 ERROR_CODES 逐字一致。
 *  - 页面 / 组件 / 请求层一律读 FB.*，不得写中文状态字面量。
 */
export const FB = {
    // 加载 / 提交
    LOADING: '加载中',
    LOADING_MORE: '加载中',
    NO_MORE: '没有更多了',
    LOAD_MORE_ERROR: '加载失败，点击重试',
    SUBMITTING: '正在提交',
    TOAST_SUCCESS: '已完成',
    SAVE_SUCCESS: '已保存',

    // 错误（客户端通用）
    ERR_NETWORK: '网络异常，请稍后重试',
    ERR_NETWORK_HINT: '请检查网络连接后重试',
    ERR_SERVER: '服务暂时不可用，请稍后重试',
    ERR_RELOGIN: '登录已失效，请重新登录',
    ERR_PERMISSION: '没有查看权限',
    ERR_PERMISSION_HINT: '如需帮助请联系客服',
    ERR_BUSINESS: '当前状态不支持此操作',
    ERR_PARAM_MISSING: '预约信息不完整',
    ERR_DUPLICATE: '请勿重复提交',
    ERR_RATE_LIMIT: '操作过于频繁，请稍后再试',

    // 空态（语境化）
    EMPTY_DEFAULT: '暂无数据',
    EMPTY_ORDER: '还没有相关订单',
    EMPTY_MESSAGE: '暂无消息',
    EMPTY_STAFF: '没有符合条件的人员',
    EMPTY_FILTERED: '没有找到相关内容',
    EMPTY_FAVORITE: '还没有收藏',
    EMPTY_LOGIN: '登录后即可查看',
    EMPTY_PERMISSION: '暂无查看权限',
    EMPTY_REMOVED: '内容已下架或不存在',

    // 支付结果（O13 白名单：支付/轮询/资金类结果一律以本组为准）
    PAY_SUCCESS: '支付成功',
    PAY_SUCCESS_DESC: '订单已进入服务流程，可在订单详情查看进度',
    PAY_PENDING_TITLE: '确认中',
    PAY_PENDING_DESC: '微信支付结果可能有延迟，请稍候',
    PAY_POLLING: '正在确认支付结果（{n}/{max}）',
    PAY_UNCONFIRMED_TITLE: '支付结果待确认',
    PAY_UNCONFIRMED_DESC: '暂未查到支付结果，请勿重复支付；如已扣款请联系客服',
    PAY_RECHECK_UNKNOWN: '暂时无法确认支付状态，请稍后再试',
    PAY_ALREADY_PAID: '该订单已支付成功',

    // 动作词（动词按体验规范 §4.4 三态选取，不可重命名）
    ACTION_RETRY: '重新加载',
    ACTION_REFRESH: '刷新',
    ACTION_RESELECT: '重新选择日期',
    ACTION_LOGIN: '去登录',
    ACTION_BACK: '返回上一页',
    ACTION_HOME: '返回首页',
    ACTION_CONTACT: '联系客服',

    // 兜底
    NOTICE_NO_TARGET: '该消息暂无可跳转页面',

    // O14 追加（主理人裁决；不改动上方既有值）
    ACTION_VIEW_ORDER: '查看订单',
    ACTION_RECHECK_RESULT: '刷新结果'
} as const

export type FeedbackKey = keyof typeof FB

/** 参数化文案：仅 PAY_POLLING 需要（n/max 由页面传，不虚构百分比） */
export function fbPollText(current: number, max: number): string {
    return FB.PAY_POLLING.replace('{n}', String(current)).replace('{max}', String(max))
}
