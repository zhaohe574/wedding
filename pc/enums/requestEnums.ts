// 请求相关枚举统一由 shared/contracts/core.ts 提供，三端不再各自维护一份。
// 需要新增成员时请改 shared/contracts/core.ts，并保留历史命名别名（FAILED / TOKEN_INVALID）。
export {
    ContentTypeEnum,
    RequestMethodsEnum,
    RequestCodeEnum,
    RequestErrMsgEnum
} from '@shared/contracts/core'
