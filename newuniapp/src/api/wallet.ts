import request from '@/request'
import type { WalletFlowDetailVO, WalletFlowsQuery, WalletFlowsResponse } from '@/api/types/wallet'

/**
 * 我的钱包 - 对账流水接口（只读）。
 *
 * 两个端点都是 GET + 登录态：列表带汇总与可选月份，详情按 flow_key
 * （`order_pay:123` / `order_refund:456` / `activity_pay:789` / `activity_refund:1`）取单条。
 * 不提供任何资金变动类方法。
 */

/** 对账流水列表 @endpoint wallet.flows */
export function getWalletFlows(params?: WalletFlowsQuery) {
    return request.get<WalletFlowsResponse>('/wallet/flows', params ?? {}, {
        auth: true,
        silent: true
    })
}

/** 对账流水详情 @endpoint wallet.detail */
export function getWalletFlowDetail(flowKey: string) {
    return request.get<WalletFlowDetailVO>(
        '/wallet/detail',
        { flow_key: flowKey },
        { auth: true, silent: true }
    )
}
