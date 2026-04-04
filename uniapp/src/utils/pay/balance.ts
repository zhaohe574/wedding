import { PayStatusEnum } from '@/enums/appEnums'

export class Balance {
    init(name: string, pay: any) {
        pay[name] = this
    }

    async run() {
        return Promise.resolve(PayStatusEnum.SUCCESS)
    }
}
