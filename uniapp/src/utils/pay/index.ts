import { Pay } from './pay'
import { Wechat } from './wechat'

enum PayWayEnum { WECHAT = 2 }
Pay.inject('WECHAT', new Wechat())
const pay = new Pay()
export { pay, PayWayEnum }
