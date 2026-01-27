// 优惠券领取组件数据项接口
export interface CouponReceiveItem {
    coupon_id: number // 优惠券ID（引用）
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    sort?: number // 排序
    // 以下字段由后端动态填充，前端不保存
    name?: string // 优惠券名称
    coupon_type?: number // 优惠券类型
    discount_amount?: number // 折扣金额
    threshold_amount?: number // 使用门槛
    valid_type?: number // 有效期类型 1=固定日期 2=领取后N天
    valid_days?: number // 有效天数（valid_type=2时使用）
    valid_start_time?: number // 开始时间
    valid_end_time?: number // 结束时间
    total_count?: number // 总数量
    receive_count?: number // 已领取数量
    is_received?: boolean // 当前用户是否已领取
}

export default () => ({
    title: '优惠券领取',
    name: 'coupon-receive',
    pageScope: ['home', 'user'], // 首页和个人中心页面可用
    content: {
        enabled: 1,
        title: '领取优惠券',
        show_more: 1, // 是否显示"查看更多"
        more_link: { path: '/pages/coupon/list', type: 'shop' },
        style: 1, // 1=横向滑动, 2=纵向列表
        show_count: 3, // 显示数量
        // 优惠券列表：只存储引用ID和控制信息，业务数据由后端动态填充
        data: [] as CouponReceiveItem[]
    },
    styles: {}
})
