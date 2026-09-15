import { ClientEnum } from '@/enums/appEnums'

// 项目业务仅发布到微信小程序。
export const client = ClientEnum.MP_WEIXIN
export const getClient = () => client
