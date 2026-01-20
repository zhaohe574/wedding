import request from '@/utils/request'

// ==================== 时间轴管理 ====================

// 时间轴任务列表
export function timelineLists(params?: any) {
    return request.get({ url: '/timeline.timeline/lists', params })
}

// 任务详情
export function timelineDetail(params: any) {
    return request.get({ url: '/timeline.timeline/detail', params })
}

// 获取订单时间轴
export function timelineOrderTimeline(params: any) {
    return request.get({ url: '/timeline.timeline/orderTimeline', params })
}

// 添加任务
export function timelineAdd(params: any) {
    return request.post({ url: '/timeline.timeline/add', params })
}

// 编辑任务
export function timelineEdit(params: any) {
    return request.post({ url: '/timeline.timeline/edit', params })
}

// 删除任务
export function timelineDelete(params: any) {
    return request.post({ url: '/timeline.timeline/delete', params })
}

// 完成任务
export function timelineComplete(params: any) {
    return request.post({ url: '/timeline.timeline/complete', params })
}

// 取消完成
export function timelineUncomplete(params: any) {
    return request.post({ url: '/timeline.timeline/uncomplete', params })
}

// 根据模板生成时间轴
export function timelineGenerate(params: any) {
    return request.post({ url: '/timeline.timeline/generate', params })
}

// 清除系统任务
export function timelineClearSystemTasks(params: any) {
    return request.post({ url: '/timeline.timeline/clearSystemTasks', params })
}

// 时间轴统计
export function timelineStats(params: any) {
    return request.get({ url: '/timeline.timeline/stats', params })
}

// 获取模板列表
export function timelineTemplates() {
    return request.get({ url: '/timeline.timeline/templates' })
}

// 任务类型选项
export function timelineTaskTypeOptions() {
    return [
        { value: 1, label: '准备物料' },
        { value: 2, label: '确认事项' },
        { value: 3, label: '沟通联系' },
        { value: 4, label: '现场安排' },
        { value: 5, label: '其他' }
    ]
}
