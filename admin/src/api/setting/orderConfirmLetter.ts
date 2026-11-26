import request from '@/utils/request'

/**
 * 海报统一二维码配置（单量月报、档期确认函共用）。
 *
 * 字段：`schedule_qrcode_image`（图片地址）、`schedule_qrcode_image_url`（完整访问地址）。
 * 品牌与站点 Hub 的「海报资源」Tab 与旧路由 `/setting/order-confirm-letter` 共用。
 */
export function getOrderConfirmLetterConfig() {
    return request.get({ url: '/setting.order_confirm_letter/getConfig' })
}

/** 保存海报统一二维码配置；服务端会自动补全 `schedule_qrcode_image_url`。 */
export function setOrderConfirmLetterConfig(params: { schedule_qrcode_image?: string }) {
    return request.post({ url: '/setting.order_confirm_letter/setConfig', params })
}

/**
 * 字体资源清单。
 *
 * 返回结构：`{ config: { sans_file, serif_file }, fonts: FontItem[], limits, diagnostics }`。
 */
export function getOrderConfirmLetterFonts() {
    return request.get({ url: '/setting.order_confirm_letter/fontLists' })
}

/** 保存正文字体 / 衬线字体选择（sans_file / serif_file）。 */
export function setOrderConfirmLetterFont(params: { sans_file?: string; serif_file?: string }) {
    return request.post({ url: '/setting.order_confirm_letter/setFont', params })
}

/** 删除已上传字体文件（内置字体与当前正在使用的字体不可删除）。 */
export function deleteOrderConfirmLetterFont(params: { file: string }) {
    return request.post({ url: '/setting.order_confirm_letter/deleteFont', params })
}

/** 触发一次服务端字体与 Imagick 渲染诊断。 */
export function checkOrderConfirmLetterFont() {
    return request.get({ url: '/setting.order_confirm_letter/checkFont' })
}
