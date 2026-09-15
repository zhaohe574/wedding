import { PayStatusEnum } from '@/enums/appEnums'

export class Wechat {
    init(name: string, pay: any) {
        pay[name] = this
    }

    run(options: any): Promise<PayStatusEnum> {
        return new Promise((resolve) => {
            uni.requestPayment({
                ...options,
                success: () => resolve(PayStatusEnum.SUCCESS),
                fail: () => resolve(PayStatusEnum.FAIL)
            })
        })
    }
}
