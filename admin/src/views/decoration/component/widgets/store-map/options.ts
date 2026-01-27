// 门店地图组件数据项接口
export interface StoreMapItem {
    name: string // 门店名称
    address: string // 门店地址
    phone: string // 联系电话
    business_hours: string // 营业时间
    latitude: number // 纬度
    longitude: number // 经度
    is_show: string // 是否显示 '1'=显示 '0'=隐藏
    sort?: number // 排序
}

export default () => ({
    title: '门店地图',
    name: 'store-map',
    pageScope: ['home'], // 首页可用
    content: {
        enabled: 1,
        title: '门店位置',
        style: 1, // 1=地图+列表, 2=纯地图, 3=纯列表
        auto_sort: 0, // 是否根据距离自动排序
        // 门店列表
        data: [
            {
                name: '总店',
                address: '北京市朝阳区xxx街道xxx号',
                phone: '010-12345678',
                business_hours: '09:00-18:00',
                latitude: 39.9042,
                longitude: 116.4074,
                is_show: '1',
                sort: 1
            }
        ] as StoreMapItem[]
    },
    styles: {}
})
