import request from '@/utils/request'

export function fileCateAdd(params: Record<string, any>) {
    return request.post({ url: '/file/addCate', params })
}

export function fileCateEdit(params: Record<string, any>) {
    return request.post({ url: '/file/editCate', params })
}

// 文件分类删除
export function fileCateDelete(params: Record<string, any>) {
    return request.post({ url: '/file/delCate', params })
}

// 文件分类列表
export function fileCateLists(params: Record<string, any>) {
    return request.get({ url: '/content.material/listCate', params })
}

// 文件列表
export function fileList(params: Record<string, any>) {
    return request.get(
        { url: '/file/lists', params },
        { ignoreCancelToken: true, isOpenRetry: false }
    )
}

// 文件删除
export function fileDelete(params: Record<string, any>) {
    return request.post({ url: '/file/delete', params })
}

// 文件移动
export function fileMove(params: Record<string, any>) {
    return request.post({ url: '/file/move', params })
}

// 文件重命名
export function fileRename(params: { id: number; name: string }) {
    return request.post({ url: '/file/rename', params })
}

// 素材清理统计
export function materialCleanupSummary(params?: Record<string, any>) {
    return request.get({ url: '/content.materialCleanup/summary', params })
}

// 素材清理候选列表
export function materialCleanupLists(params: Record<string, any>) {
    return request.get(
        { url: '/content.materialCleanup/lists', params },
        { ignoreCancelToken: true, isOpenRetry: false }
    )
}

// 素材清理确认删除
export function materialCleanupDelete(params: { ids: string[] }) {
    return request.post({ url: '/content.materialCleanup/delete', params })
}
