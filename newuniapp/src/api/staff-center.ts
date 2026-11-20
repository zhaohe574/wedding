import request from '@/request'

/**
 * 服务人员工作台。
 *
 * P0 已开放四类写能力：资料（updateProfile）/ 动态 / 作品 / 证书的增删改，
 * 全部走审核流 —— 提交后内容为待审核态，前端按返回的 tag_action 区分
 * 「保存成功」与「已提交审核」，不把审核态说成即时生效。
 * 订单履约（接单、结案、结算）仍未开放：成交后的改动一旦在手机上误触，
 * 影响的是已收款订单，代价远高于便利性，继续留在后台完成。
 * 入口受 feature_switch.staff_center 与用户 is_staff 双重控制（见 pages/user/user.vue）。
 */

/** @endpoint staff_center.dashboard */
export function getStaffDashboard() {
    return request.get<any>('/staff_center/dashboard', {}, { auth: true, silent: true })
}

/** @endpoint staff_center.profile */
export function getStaffCenterProfile() {
    return request.get<any>('/staff_center/profile', {}, { auth: true, silent: true })
}

/** @endpoint staff_center.certificateLists */
export function getStaffCertificateList(params: {
    page?: number
    page_size?: number
    verify_status?: number | ''
    name?: string
}) {
    return request.get<any>('/staff_center/certificateLists', params, { auth: true, silent: true })
}

/** @endpoint staff_center.workLists */
export function getStaffWorkList(params: {
    page?: number
    page_size?: number
    audit_status?: number | ''
}) {
    return request.get<any>('/staff_center/workLists', params, { auth: true, silent: true })
}

/** @endpoint staff_center.packageLists */
export function getStaffPackageList(params: {
    page?: number
    page_size?: number
    is_show?: number | ''
}) {
    return request.get<any>('/staff_center/packageLists', params, { auth: true, silent: true })
}

/** @endpoint staff_center.addonLists */
export function getStaffAddonList(params: {
    page?: number
    page_size?: number
    is_show?: number | ''
}) {
    return request.get<any>('/staff_center/addonLists', params, { auth: true, silent: true })
}

/** @endpoint staff_center.dynamicLists */
export function getStaffDynamicList(params: {
    page?: number
    page_size?: number
    status?: number | ''
}) {
    return request.get<any>('/staff_center/dynamicLists', params, { auth: true, silent: true })
}

/**
 * @endpoint dashboard.access
 * 看板入口显隐：小程序用户没有后台 token，由服务端按「绑定管理员 + RBAC 节点」判定
 */
export function getDashboardAccess() {
    return request.get<any>('/dashboard/access', {}, { auth: true, silent: true })
}

/** @endpoint dashboard.overview */
export function getDashboardOverview(params?: { start_date?: string; end_date?: string }) {
    return request.get<any>('/dashboard/overview', params || {}, { auth: true, silent: true })
}

/** @endpoint dashboard.teamOverview */
export function getDashboardTeamOverview() {
    return request.get<any>('/dashboard/teamOverview', {}, { auth: true, silent: true })
}

/* ==================== 写能力（P0 开放） ====================
 * 三条纪律（团队约定，改前先读 request/index.ts 的注释）：
 *   1. 写接口一律 dedupe: false —— dedupe 的语义是「后来者复用先来者的 Promise」，
 *      对提交是错的（两次点击要各自拿到各自的成败，不能共享同一个 Promise）；
 *   2. silent: true —— 成功/失败提示由页面按业务语义自己发：
 *      updateProfile 要按 tag_action 区分「保存成功 / 标签已提交审核」，
 *      Add/Edit 要区分「新增 / 重新提交审核」，请求层的通用 toast 说不了这么细；
 *   3. 不开 retry —— 请求层明令「任何写接口都不许开 retry>0」。
 */

/** updateProfile 入参（只传本次要改的字段；后端按字段合并） */
export interface StaffProfileUpdateParams {
    /** 必填，1-50 字 */
    name?: string
    avatar?: string
    /** 变更手机号时传新号码（11 位） */
    mobile?: string
    /** 仅变更手机号时必传：短信验证码，4-8 位数字 */
    code?: string
    /** 必填；改分类会联动已发布套餐与附加项的分类，前端提交前弹确认 */
    category_id?: number
    experience_years?: number
    /** 个人简介，≤500 字 */
    profile?: string
    /** 服务说明，≤1000 字 */
    service_desc?: string
    /** 详情长文（纯文本）；后端允许 6 万字，前端按 2000 字上限约束 */
    long_detail?: string
    /** 标签 id 集合；变更走审核（tag_action=pending） */
    tag_ids?: number[]
    /** 月度汇报材料（URL 数组）。编辑页暂未提供 UI，契约字段先保留 */
    monthly_report_material?: string[]
}

/** updateProfile 出参：标签改动是否进入审核 */
export interface StaffProfileUpdateResult {
    /** applied = 即时生效；pending = 标签变更进入审核 */
    tag_action?: 'applied' | 'pending'
    tag_message?: string
}

/**
 * 修改服务人员资料。
 * @endpoint staff_center.updateProfile
 */
export function updateStaffProfile(data: StaffProfileUpdateParams) {
    return request.post<StaffProfileUpdateResult>('/staff_center/updateProfile', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/* ---------------- 动态 ---------------- */

/** 动态新增/编辑入参（Edit 时必带 id） */
export interface StaffDynamicSaveParams {
    /** 1=图文 2=视频 */
    dynamic_type?: number
    /** 必填 */
    title?: string
    /** 必填 */
    content?: string
    /** 图文图片，≤9 张 */
    images?: string[]
    video_url?: string
    video_cover?: string
    location?: string
    /** 标签（逗号分隔字符串，原样提交） */
    tags?: string
    /** 是否允许评论：1 开 / 0 关 */
    allow_comment?: number
    /** 关联订单（选填） */
    order_id?: number
}

/** @endpoint staff_center.dynamicAdd */
export function staffDynamicAdd(data: Omit<StaffDynamicSaveParams, 'id'>) {
    return request.post<null>('/staff_center/dynamicAdd', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.dynamicEdit */
export function staffDynamicEdit(data: StaffDynamicSaveParams & { id: number }) {
    return request.post<null>('/staff_center/dynamicEdit', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.dynamicDelete */
export function staffDynamicDelete(params: { id: number }) {
    return request.post<null>('/staff_center/dynamicDelete', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/**
 * 动态详情（编辑页回填用）。
 * @endpoint staff_center.dynamicDetail
 */
export function getStaffDynamicDetail(params: { id: number }) {
    return request.get<any>('/staff_center/dynamicDetail', params, { auth: true, silent: true })
}

/* ---------------- 作品 ---------------- */

/** 作品新增/编辑入参（Edit 时必带 id） */
export interface StaffWorkSaveParams {
    /** 必填 */
    title?: string
    /** 必填，单图 */
    cover?: string
    /** 作品图集，≤9 张 */
    images?: string[]
    video?: string
    description?: string
    /** 拍摄/服务日期 YYYY-MM-DD */
    shoot_date?: string
    location?: string
    /** 排序权重，数字越小越靠前 */
    sort?: number
    /** 是否展示：1 展示 / 0 隐藏 */
    is_show?: number
}

/** @endpoint staff_center.workAdd */
export function staffWorkAdd(data: Omit<StaffWorkSaveParams, 'id'>) {
    return request.post<null>('/staff_center/workAdd', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.workEdit */
export function staffWorkEdit(data: StaffWorkSaveParams & { id: number }) {
    return request.post<null>('/staff_center/workEdit', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.workDelete */
export function staffWorkDelete(params: { id: number }) {
    return request.post<null>('/staff_center/workDelete', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/**
 * 作品详情（编辑页回填用）。
 * @endpoint staff_center.workDetail
 */
export function getStaffWorkDetail(params: { id: number }) {
    return request.get<any>('/staff_center/workDetail', params, { auth: true, silent: true })
}

/* ---------------- 证书 ---------------- */

/** 证书新增/编辑入参（Edit 时必带 id） */
export interface StaffCertificateSaveParams {
    /** 必填 */
    name?: string
    /** 证书类型，≤50 字 */
    type?: string
    /** 证书编号，≤100 字 */
    sn?: string
    /** 证书照片，单图 */
    image?: string
    issue_org?: string
    /** 发证日期 YYYY-MM-DD */
    issue_date?: string
    /** 有效期至 YYYY-MM-DD */
    expire_date?: string
}

/** @endpoint staff_center.certificateAdd */
export function staffCertificateAdd(data: Omit<StaffCertificateSaveParams, 'id'>) {
    return request.post<null>('/staff_center/certificateAdd', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.certificateEdit */
export function staffCertificateEdit(data: StaffCertificateSaveParams & { id: number }) {
    return request.post<null>('/staff_center/certificateEdit', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.certificateDelete */
export function staffCertificateDelete(params: { id: number }) {
    return request.post<null>('/staff_center/certificateDelete', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}
