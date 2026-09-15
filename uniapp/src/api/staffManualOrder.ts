import request from '@/utils/request'

export interface ReceiptDraft { pay_type: number; voucher: string; collection_owner: number }
export interface ReceiptPhase { value: number; label: string; amount: number }
export interface ManualQuote {
    quote_hash: string
    pay_amount: number
    deposit_amount: number
    balance_amount: number
    addon_amount: number
}
export const staffOrderCustomers = (mobile: string) => request.post({ url: '/staff_center/orderCustomerLookup', data: { mobile } }, { isAuth: true })
export const staffOrderOptions = (data: Record<string, any>) => request.post({ url: '/staff_center/manualOrderOptions', data }, { isAuth: true })
export const staffOrderPreview = (data: Record<string, any>): Promise<ManualQuote> => request.post({ url: '/staff_center/manualOrderPreview', data }, { isAuth: true })
export const staffOrderCreate = (data: Record<string, any>) => request.post({ url: '/staff_center/manualOrderCreate', data }, { isAuth: true })
export const staffOrderReceiptSubmit = (data: ReceiptDraft & { order_id: number; submit_key: string }) => request.post({ url: '/staff_center/orderReceiptSubmit', data }, { isAuth: true })
export const newStaffSubmitKey = () => `staff_${Date.now()}_${Math.random().toString(36).slice(2)}_${Math.random().toString(36).slice(2)}`
