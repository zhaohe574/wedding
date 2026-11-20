// 跨端契约层入口。
//
// 三端统一从这里 import：
//   import { OrderStatus, ORDER_STATUS_NAME_MAP } from '@shared/contracts'
//
// 分层：
//   generated/status.ts  自动生成（PHP 常量 → TS），禁止手改
//   core.ts              手写 DTO + 请求信封，历史名称兼容
//   tone.ts              状态 → 语义色调（穷尽检查）
//   error-codes.ts       结构化错误码与恢复策略
//   widgets.ts           装修数据解析与白名单
//   dto.ts               v2 聚合接口响应类型

export * from './generated/status'
export * from './core'
export * from './tone'
export * from './error-codes'
export * from './widgets'
export * from './decorate'
export * from './dto'
