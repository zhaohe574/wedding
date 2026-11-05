/**
 * 兼容层：历史 api 模块（account/app/news/pay/recharge/shop/user）仍从
 * `@/utils/request` 引入。真正的实现在 `@/request`。
 *
 * 新代码请直接使用：
 *   import request from '@/request'
 *
 * 本文件在 api/ 全部迁移完成后删除。
 */
export { default } from '@/request'
export * from '@/request'
