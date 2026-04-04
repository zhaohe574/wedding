import request from '@/utils/request'

// H5渠道配置保存
export function setH5Config(params: any) {
    return request.post({ url: '/experience.channel.web_page_setting/setConfig', params })
}

// H5渠道配置详情
export function getH5Config() {
    return request.get({ url: '/experience.channel.web_page_setting/getConfig' })
}
