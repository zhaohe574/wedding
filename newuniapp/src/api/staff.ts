import request from '@/request'

/**
 * 服务人员中心（订单、履约、档期、结算与候补）。
 *
 * 开放订单履约（确认接单 / 开始履约 / 完成服务）、档期与线下档期管理、结算收款与平台补交、候补释放通知；
 * 入口受 feature_switch.staff_center 与用户 is_staff 双重控制。
 */

/** @endpoint staff_center.orderLists */
export function getStaffOrderList(params: { page?: number; page_size?: number; status?: number }) {
    return request.get<any>('/staff_center/orderLists', params, { auth: true, silent: true })
}

/** @endpoint staff_center.orderDetail */
export function getStaffOrderDetail(params: { id: number }) {
    return request.get<any>('/staff_center/orderDetail', params, { auth: true, silent: true })
}

/** @endpoint staff_center.orderConfirm */
export function staffOrderConfirm(params: { id: number }) {
    return request.post<null>('/staff_center/orderConfirm', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.orderStartService */
export function staffOrderStartService(params: { id: number }) {
    return request.post<null>('/staff_center/orderStartService', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.orderComplete */
export function staffOrderComplete(params: { id: number }) {
    return request.post<null>('/staff_center/orderComplete', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.orderCancel 服务人员拒绝接单 / 取消订单 */
export function staffOrderCancel(params: { id: number; reason: string }) {
    return request.post<any>('/staff_center/orderCancel', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.scheduleMonth */
export function getStaffScheduleMonth(params: { year: number; month: number }) {
    return request.get<any>('/staff_center/scheduleMonth', params, { auth: true, silent: true })
}

export interface StaffScheduleSetStatusParams {
    date: string
    status: 0 | 1
    version: number
    remark?: string
}

/** @endpoint staff_center.scheduleSetStatus */
export function staffScheduleSetStatus(data: StaffScheduleSetStatusParams) {
    return request.post<null>('/staff_center/scheduleSetStatus', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

export interface StaffManualSchedulePayload {
    date: string
    service_name: string
    customer_name?: string
    customer_mobile?: string
    region_name?: string
    service_address?: string
    remark?: string
}

export type StaffManualScheduleAddParams = StaffManualSchedulePayload & { request_id: string }

export type StaffManualScheduleEditParams = StaffManualSchedulePayload & {
    manual_schedule_id: number
    version: number
}

export interface StaffManualScheduleActionParams {
    manual_schedule_id: number
    version: number
}

/** @endpoint staff_center.manualScheduleAdd */
export function staffManualScheduleAdd(data: StaffManualScheduleAddParams) {
    return request.post<any>('/staff_center/manualScheduleAdd', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.manualScheduleEdit */
export function staffManualScheduleEdit(data: StaffManualScheduleEditParams) {
    return request.post<any>('/staff_center/manualScheduleEdit', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.manualScheduleCancel */
export function staffManualScheduleCancel(data: StaffManualScheduleActionParams) {
    return request.post<null>('/staff_center/manualScheduleCancel', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.manualScheduleComplete */
export function staffManualScheduleComplete(data: StaffManualScheduleActionParams) {
    return request.post<null>('/staff_center/manualScheduleComplete', data, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.profile */
export function getStaffProfile() {
    return request.get<any>('/staff_center/profile', {}, { auth: true, silent: true })
}

/** @endpoint staff_center.settlementLists */
export function getStaffSettlementList(params?: {
    status?: number
    page_no?: number
    page_size?: number
}) {
    const query: Record<string, any> = {
        page_no: params?.page_no ?? 1,
        page_size: params?.page_size ?? 15
    }
    if (params?.status !== undefined) {
        query.status = params.status
    }
    return request.get<any>('/staff_center/settlementLists', query, { auth: true, silent: true })
}

/** @endpoint staff_center.settlementReceive */
export function staffSettlementReceive(params: { id: number }) {
    return request.post<any>('/staff_center/settlementReceive', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.settlementSync */
export function staffSettlementSync(params: { id: number }) {
    return request.post<any>('/staff_center/settlementSync', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.waitlistLists */
export function getStaffWaitlistList(params?: {
    notify_status?: number
    page_no?: number
    page_size?: number
}) {
    const query: Record<string, any> = {
        page_no: params?.page_no ?? 1,
        page_size: params?.page_size ?? 15
    }
    if (params?.notify_status !== undefined) {
        query.notify_status = params.notify_status
    }
    return request.get<any>('/staff_center/waitlistLists', query, { auth: true, silent: true })
}

/** @endpoint staff_center.waitlistNotify */
export function staffWaitlistNotify(params: { id: number }) {
    return request.post<null>('/staff_center/waitlistNotify', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

export interface StaffServiceDayUpcomingDate {
    date: string
    platform_count: number
    manual_count: number
    total_count: number
    is_today: boolean
}

export interface StaffServiceDayOrderTeammate {
    staff_id: number
    staff_name: string
    category_name: string
    package_name: string
    confirm_status: number
}

export interface StaffServiceDayQuestionnaireHighlight {
    category: string
    title: string
    answer: string
}

export interface StaffServiceDayConfirmLetterBrief {
    letter_id: number
    version: number
    full_image_url: string
    thumb_image_url: string
}

export interface StaffServiceDayOrderItem {
    order_id: number
    order_sn: string
    order_status: number
    order_status_desc: string
    pay_status_desc: string
    current_pay_stage_desc: string
    service_date: string
    contact_name: string
    contact_mobile: string
    contact_mobile_masked: string
    service_address: string
    remark: string
    start_service_time_text: string
    has_pending_change: boolean
    can_staff_confirm: boolean
    can_staff_start: boolean
    can_staff_complete: boolean
    items: Array<{
        id: number
        package_name: string
        package_description: string
        quantity: number
        confirm_status: number
        addons: Array<{ id: number; name: string; quantity: number }>
    }>
    teammates: StaffServiceDayOrderTeammate[]
    questionnaire: null | {
        task_id: number
        status: number
        status_desc: string
        send_status: number
        submitted_time_text: string
        answer_count: number
        highlights: StaffServiceDayQuestionnaireHighlight[]
    }
    confirm_letter: StaffServiceDayConfirmLetterBrief | null
}

export interface StaffServiceDayManualSchedule {
    manual_schedule_id: number
    id: number
    schedule_date: string
    service_name: string
    customer_name: string
    customer_mobile: string
    customer_mobile_masked: string
    region_name: string
    service_address: string
    remark: string
    status: number
    status_desc: string
    version: number
    can_edit: boolean
    can_cancel: boolean
    can_complete: boolean
    confirm_letter: StaffServiceDayConfirmLetterBrief | null
}

export interface StaffServiceDayPanel {
    date: string
    today: string
    weekday_label: string
    upcoming_dates: StaffServiceDayUpcomingDate[]
    summary: {
        total_count: number
        platform_count: number
        manual_count: number
        pending_confirm_count: number
        pending_service_count: number
        in_service_count: number
        completed_count: number
    }
    orders: StaffServiceDayOrderItem[]
    manual_schedules: StaffServiceDayManualSchedule[]
}

/** @endpoint staff_center.serviceDayPanel */
export function getStaffServiceDayPanel(params?: { date?: string; range_days?: number }) {
    return request.get<StaffServiceDayPanel>('/staff_center/serviceDayPanel', params, {
        auth: true,
        silent: true
    })
}

export interface StaffBusinessCard {
    staff: {
        id: number
        name: string
        avatar: string
        cover: string
        category_name: string
        experience_years: number
        rating: number | string
        order_count: number
        price_text: string
        has_price: boolean
        city: string
        tags: string[]
        intro: string
        service_desc: string
        mobile_masked: string
    }
    works: Array<{ id: number; title: string; cover: string }>
    packages: Array<{ id: number; name: string; price: number | string; duration_desc: string }>
    upcoming_availability: Array<{
        date: string
        md_label: string
        weekday: string
        available: boolean
    }>
    available_days_14d: number
    qrcode_image_url: string
    share_path: string
}

/** @endpoint staff_center.businessCard */
export function getStaffBusinessCard() {
    return request.get<StaffBusinessCard>(
        '/staff_center/businessCard',
        {},
        { auth: true, silent: true }
    )
}

export interface StaffConfirmLetterVersion {
    config_id: number
    template_name?: string
    template_version?: number
    is_default?: number
    is_current?: boolean
}

export interface StaffConfirmLetterConfig {
    config_id: number
    template_name?: string
    template_version?: number
    is_default?: number
    versions?: StaffConfirmLetterVersion[]
    editable_fields?: string[]
    title: string
    subtitle: string
    content_template: string
    footer_note: string
    background_type: 'color' | 'image'
    background_image: string
    background_image_url: string
    background_color: string
    text_theme?: 'light' | 'dark'
    qrcode_image?: string
    qrcode_image_url?: string
    color_presets?: Array<{ label: string; value: string }>
    [key: string]: unknown
}

/** @endpoint staff_center.scheduleConfirmLetterConfig */
export function getStaffConfirmLetterConfig(params?: { config_id?: number }) {
    return request.get<StaffConfirmLetterConfig>(
        '/staff_center/scheduleConfirmLetterConfig',
        params,
        { auth: true, silent: true }
    )
}

export interface StaffConfirmLetterPreviewPayload {
    config_id: number
    title?: string
    subtitle?: string
    content_template?: string
    footer_note?: string
    background_type?: 'color' | 'image'
    background_image?: string
    background_color?: string
    text_theme?: 'light' | 'dark'
}

export interface StaffConfirmLetterPreviewResult {
    config: StaffConfirmLetterConfig
    preview: {
        render_spec_version: string
        rendered_snapshot?: Record<string, unknown>
        snapshot_hash?: string
        image_data_url: string
    }
}

/** @endpoint staff_center.scheduleConfirmLetterPreview */
export function previewStaffConfirmLetter(params: StaffConfirmLetterPreviewPayload) {
    return request.post<StaffConfirmLetterPreviewResult>(
        '/staff_center/scheduleConfirmLetterPreview',
        params,
        { auth: true, dedupe: false, silent: true }
    )
}

/** @endpoint staff_center.scheduleConfirmLetterSaveConfig */
export function saveStaffConfirmLetterConfig(params: StaffConfirmLetterPreviewPayload) {
    return request.post<StaffConfirmLetterConfig>(
        '/staff_center/scheduleConfirmLetterSaveConfig',
        params,
        { auth: true, dedupe: false, silent: true }
    )
}

export interface StaffConfirmLetterRecord {
    letter_id: number
    order_id?: number
    manual_schedule_id?: number
    version: number
    config_name?: string
    confirm_date?: string
    is_current?: number | boolean
    full_image_url: string
    thumb_image_url: string
    create_time_text?: string
    rendered_snapshot?: Record<string, unknown>
}

/** @endpoint staff_center.orderConfirmLetterGenerate */
export function generateStaffConfirmLetter(params: {
    order_id?: number
    manual_schedule_id?: number
    config_id?: number
}) {
    return request.post<StaffConfirmLetterRecord>(
        '/staff_center/orderConfirmLetterGenerate',
        params,
        { auth: true, dedupe: false, silent: true }
    )
}

/** @endpoint staff_center.orderConfirmLetterHistory */
export function getStaffConfirmLetterHistory(params: {
    order_id?: number
    manual_schedule_id?: number
}) {
    return request.get<StaffConfirmLetterRecord[]>(
        '/staff_center/orderConfirmLetterHistory',
        params,
        { auth: true, silent: true }
    )
}

/** @endpoint staff_center.orderConfirmLetterDetail */
export function getStaffConfirmLetterDetail(params: { letter_id: number }) {
    return request.get<StaffConfirmLetterRecord>(
        '/staff_center/orderConfirmLetterDetail',
        params,
        { auth: true, silent: true }
    )
}

export interface StaffCopyTemplatePreset {
    id: number
    preset_key: string
    is_system: true
    scene: string
    scene_label: string
    title: string
    content: string
}

export interface StaffCopyTemplateCustom {
    id: number
    is_system: false
    scene: string
    scene_label: string
    title: string
    content: string
    sort: number
    use_count: number
    update_time_text: string
}

export interface StaffCopyTemplateTarget {
    target_key: string
    source: string
    order_id: number
    manual_schedule_id: number
    title: string
    customer_name: string
    service_date: string
    service_address: string
    package_name: string
}

export interface StaffCopyTemplateListResponse {
    scenes: Array<{ value: string; label: string }>
    presets: StaffCopyTemplatePreset[]
    customs: StaffCopyTemplateCustom[]
    staff_context: {
        staff_name: string
        category_name: string
        price_text: string
    }
    recent_targets: StaffCopyTemplateTarget[]
}

/** @endpoint staff_center.copyTemplateLists */
export function getStaffCopyTemplateList(params?: { scene?: string }) {
    return request.get<StaffCopyTemplateListResponse>(
        '/staff_center/copyTemplateLists',
        params,
        { auth: true, silent: true }
    )
}

/** @endpoint staff_center.copyTemplateSave */
export function saveStaffCopyTemplate(params: {
    id?: number
    scene: string
    title: string
    content: string
    sort?: number
}) {
    return request.post<StaffCopyTemplateCustom>('/staff_center/copyTemplateSave', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.copyTemplateDelete */
export function deleteStaffCopyTemplate(params: { id: number }) {
    return request.post<null>('/staff_center/copyTemplateDelete', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.copyTemplateUse */
export function markStaffCopyTemplateUsed(params: { id: number }) {
    return request.post<null>('/staff_center/copyTemplateUse', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

export interface StaffCoupleSummaryItem {
    target_key: string
    source_type: 'order' | 'manual'
    source_label: string
    target_id: number
    order_id: number
    manual_schedule_id: number
    order_sn: string
    order_status: number
    status_desc: string
    customer_name: string
    customer_mobile: string
    customer_mobile_masked: string
    service_date: string
    countdown_days: number | null
    countdown_text: string
    is_completed: boolean
    is_upcoming: boolean
    service_address: string
    package_name: string
    questionnaire_status: number
    questionnaire_status_desc: string
    checklist_done: number
    checklist_total: number
    note_count: number
    pinned_note_preview: string
    pending_followup_count: number
    overdue_followup_count: number
    next_remind_date: string
}

export interface StaffCoupleListResponse {
    summary: {
        total_count: number
        upcoming_count: number
        pending_followup_count: number
        overdue_followup_count: number
        completed_count: number
    }
    lists: StaffCoupleSummaryItem[]
}

export interface StaffServiceNoteItem {
    id: number
    source_type: 'order' | 'manual'
    target_id: number
    category: string
    category_label: string
    content: string
    images: string[]
    image_items: Array<{ uri: string; url: string }>
    is_pinned: number
    update_time_text: string
}

export interface StaffFollowupReminderItem {
    id: number
    source_type: 'order' | 'manual' | 'general'
    target_id: number
    remind_type: string
    remind_type_label: string
    title: string
    remind_date: string
    remind_time: string
    remark: string
    status: number
    status_desc: string
    is_overdue: boolean
    is_today: boolean
    customer_name: string
    customer_mobile: string
    customer_mobile_masked: string
    service_date: string
    service_address: string
    package_name: string
}

export interface StaffChecklistItem {
    id: number
    stage: string
    stage_label: string
    title: string
    remark: string
    is_done: number
    done_time_text: string
    is_preset: number
    sort: number
}

export interface StaffChecklistBlock {
    stages: Array<{ value: string; label: string }>
    items: StaffChecklistItem[]
    done_count: number
    total_count: number
    progress_percent: number
}

export interface StaffCoupleDetailResponse {
    profile: {
        target_key: string
        source_type: 'order' | 'manual'
        source_label: string
        target_id: number
        order_id: number
        manual_schedule_id: number
        order_sn: string
        order_status: number
        status_desc: string
        customer_name: string
        customer_mobile: string
        customer_mobile_masked: string
        service_date: string
        countdown_days: number | null
        countdown_text: string
        service_address: string
        package_name: string
        addons_summary: string
        remark: string
        teammates: StaffServiceDayOrderTeammate[]
        can_send_questionnaire: boolean
        questionnaire: null | {
            task_id: number
            status: number
            status_desc: string
            send_status: number
            submitted_time_text: string
            answer_count: number
            answers: Array<{
                category: string
                title: string
                answer: string
                is_taboo: boolean
            }>
        }
    }
    checklist: StaffChecklistBlock
    notes: StaffServiceNoteItem[]
    followups: StaffFollowupReminderItem[]
    note_categories: Array<{ value: string; label: string }>
    remind_types: Array<{ value: string; label: string }>
}

/** @endpoint staff_center.coupleLists */
export function getStaffCoupleList(params?: { filter?: string; keyword?: string }) {
    return request.get<StaffCoupleListResponse>('/staff_center/coupleLists', params, {
        auth: true,
        silent: true
    })
}

/** @endpoint staff_center.coupleDetail */
export function getStaffCoupleDetail(params: {
    source_type: 'order' | 'manual'
    target_id: number
}) {
    return request.get<StaffCoupleDetailResponse>('/staff_center/coupleDetail', params, {
        auth: true,
        silent: true
    })
}

/** @endpoint staff_center.coupleSendQuestionnaire */
export function sendStaffCoupleQuestionnaire(params: { order_id: number }) {
    return request.post<any>('/staff_center/coupleSendQuestionnaire', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.serviceNoteSave */
export function saveStaffServiceNote(params: {
    id?: number
    source_type: 'order' | 'manual'
    target_id: number
    category: string
    content: string
    images?: string[]
    is_pinned?: number
}) {
    return request.post<StaffServiceNoteItem>('/staff_center/serviceNoteSave', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.serviceNoteTogglePin */
export function toggleStaffServiceNotePin(params: { id: number }) {
    return request.post<StaffServiceNoteItem>('/staff_center/serviceNoteTogglePin', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.serviceNoteDelete */
export function deleteStaffServiceNote(params: { id: number }) {
    return request.post<null>('/staff_center/serviceNoteDelete', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

export interface StaffFollowupTargetOption {
    target_key: string
    source_type: 'order' | 'manual'
    target_id: number
    title: string
    service_date: string
    customer_name: string
}

export interface StaffFollowupListResponse {
    today: string
    summary: {
        pending_total: number
        overdue_count: number
        today_count: number
        week_count: number
        done_count: number
    }
    remind_types: Array<{ value: string; label: string }>
    recent_targets: StaffFollowupTargetOption[]
    lists: StaffFollowupReminderItem[]
}

/** @endpoint staff_center.followupLists */
export function getStaffFollowupList(params?: { scope?: string; keyword?: string }) {
    return request.get<StaffFollowupListResponse>('/staff_center/followupLists', params, {
        auth: true,
        silent: true
    })
}

/** @endpoint staff_center.followupSave */
export function saveStaffFollowup(params: {
    id?: number
    source_type: 'order' | 'manual' | 'general'
    target_id: number
    remind_type: string
    title: string
    remind_date: string
    remind_time?: string
    remark?: string
}) {
    return request.post<StaffFollowupReminderItem>('/staff_center/followupSave', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.followupAction */
export function actStaffFollowup(params: {
    id: number
    action: 'done' | 'reopen' | 'snooze' | 'ignore'
}) {
    return request.post<StaffFollowupReminderItem>('/staff_center/followupAction', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.followupDelete */
export function deleteStaffFollowup(params: { id: number }) {
    return request.post<null>('/staff_center/followupDelete', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.checklistInitPresets */
export function initStaffChecklistPresets(params: {
    source_type: 'order' | 'manual'
    target_id: number
}) {
    return request.post<StaffChecklistBlock>('/staff_center/checklistInitPresets', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.checklistSave */
export function saveStaffChecklistItem(params: {
    id?: number
    source_type: 'order' | 'manual'
    target_id: number
    stage: string
    title: string
    remark?: string
    sort?: number
}) {
    return request.post<StaffChecklistBlock>('/staff_center/checklistSave', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.checklistToggle */
export function toggleStaffChecklistItem(params: { id: number }) {
    return request.post<StaffChecklistBlock>('/staff_center/checklistToggle', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

/** @endpoint staff_center.checklistDelete */
export function deleteStaffChecklistItem(params: { id: number }) {
    return request.post<null>('/staff_center/checklistDelete', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}

export interface StaffTeamMemberItem {
    member_id: number
    staff_id: number
    is_leader: boolean
    name: string
    avatar: string
    category_name: string
    rating: number
    experience_years: number
    mobile: string
    mobile_masked: string
    status: number
    status_desc: string
    available_days_14d: number
    month_booked_days: number
    pending_confirm_count: number
    upcoming_14d: Array<{
        date: string
        md_label: string
        weekday: string
        available: boolean
    }>
    can_toggle: boolean
    can_remove: boolean
}

export interface StaffTeamOverviewResponse {
    role: 'leader' | 'member' | 'none'
    is_leader: boolean
    team: null | {
        id: number
        name: string
        remark: string
        leader_staff_id: number
        leader_name: string
    }
    summary: null | {
        total_members: number
        enabled_members: number
        month_order_count: number
        upcoming_7d_schedules: number
        pending_confirm_orders: number
    }
    members: StaffTeamMemberItem[]
}

export interface StaffTeamCandidateItem {
    staff_id: number
    name: string
    avatar: string
    category_name: string
    rating: number
    experience_years: number
    mobile_masked: string
}

/** @endpoint staff_center.teamOverview */
export function getStaffTeamOverview() {
    return request.get<StaffTeamOverviewResponse>(
        '/staff_center/teamOverview',
        {},
        { auth: true, silent: true }
    )
}

/** @endpoint staff_center.teamUpdateInfo */
export function updateStaffTeamInfo(params: { name: string; remark?: string }) {
    return request.post<{ id: number; name: string; remark: string }>(
        '/staff_center/teamUpdateInfo',
        params,
        { auth: true, dedupe: false, silent: true }
    )
}

/** @endpoint staff_center.teamMemberCandidates */
export function getStaffTeamMemberCandidates(params?: { keyword?: string }) {
    return request.get<{ lists: StaffTeamCandidateItem[] }>(
        '/staff_center/teamMemberCandidates',
        params,
        { auth: true, silent: true }
    )
}

/** @endpoint staff_center.teamMemberAdd */
export function addStaffTeamMember(params: { target_staff_id: number }) {
    return request.post<{ member_id: number; staff_id: number }>(
        '/staff_center/teamMemberAdd',
        params,
        { auth: true, dedupe: false, silent: true }
    )
}

/** @endpoint staff_center.teamMemberToggleStatus */
export function toggleStaffTeamMemberStatus(params: { member_id: number; status: number }) {
    return request.post<{ member_id: number; status: number }>(
        '/staff_center/teamMemberToggleStatus',
        params,
        { auth: true, dedupe: false, silent: true }
    )
}

/** @endpoint staff_center.teamMemberRemove */
export function removeStaffTeamMember(params: { member_id: number }) {
    return request.post<null>('/staff_center/teamMemberRemove', params, {
        auth: true,
        dedupe: false,
        silent: true
    })
}


