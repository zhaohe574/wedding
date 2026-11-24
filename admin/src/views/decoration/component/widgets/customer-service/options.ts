import { CUSTOMER_SERVICE_DEFAULTS } from '@shared/contracts/decorate'
import { cloneDeep } from 'lodash-es'

export default () => ({
    title: '客服设置',
    name: 'customer-service',
    pageScope: ['service'],
    content: cloneDeep(CUSTOMER_SERVICE_DEFAULTS),
    styles: {}
})
