import request from '@/utils/request'

// 启动咨询并返回当前应联系的顾问/兜底客服
export function startConsult(params: {
    scene: 'home' | 'staff_detail' | 'order_detail' | 'aftersale' | 'package_detail'
    staff_id?: number | string
    order_id?: number | string
    category_id?: number | string
}) {
    return request.post({ url: '/customer_service/startConsult', params }, { isAuth: true })
}
