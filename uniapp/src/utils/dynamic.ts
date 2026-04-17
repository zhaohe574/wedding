export interface DynamicCardData {
    id: number
    user: {
        id: number
        nickname: string
        avatar: string
        staffId: number
        isFavorite: boolean
        roleLabel: string
        canFavorite: boolean
    }
    content: string
    images: string[]
    topics: Array<{
        id: number
        name: string
    }>
    location?: {
        name: string
        lat: number
        lng: number
    }
    viewCount: number
    likeCount: number
    commentCount: number
    isLiked: boolean
    createTime: string
    dynamicType: number
    dynamicTypeLabel: string
}

const DYNAMIC_TYPE_LABEL_MAP: Record<number, string> = {
    1: '图文',
    2: '视频',
    3: '案例',
    4: '活动'
}

const toNumber = (value: any): number => {
    const normalized = Number(value ?? 0)
    return Number.isFinite(normalized) ? normalized : 0
}

const toBoolean = (value: any): boolean => {
    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase()
        if (['0', 'false', 'off', 'no', ''].includes(normalized)) {
            return false
        }
        if (['1', 'true', 'on', 'yes'].includes(normalized)) {
            return true
        }
    }
    return Boolean(value)
}

const normalizeImageList = (images: any): string[] => {
    if (!images) {
        return []
    }
    if (Array.isArray(images)) {
        return images.map((item) => String(item || '').trim()).filter(Boolean)
    }
    if (typeof images === 'string') {
        const value = images.trim()
        if (!value) {
            return []
        }

        try {
            const parsed = JSON.parse(value)
            if (Array.isArray(parsed)) {
                return parsed.map((item) => String(item || '').trim()).filter(Boolean)
            }
        } catch (error) {
            // ignore parse error and fallback to comma-separated parsing
        }

        return value
            .split(',')
            .map((item) => item.trim())
            .filter(Boolean)
    }
    return []
}

const normalizeTags = (tags: any): string[] => {
    if (!tags) {
        return []
    }
    if (Array.isArray(tags)) {
        return tags.filter((tag) => typeof tag === 'string' && tag.trim())
    }
    if (typeof tags === 'string') {
        return tags
            .split(',')
            .map((tag) => tag.trim())
            .filter(Boolean)
    }
    return []
}

const getDynamicTypeLabel = (dynamicType: any): string => {
    const type = Number(dynamicType || 1)
    return DYNAMIC_TYPE_LABEL_MAP[type] || '动态'
}

const resolveUserMeta = (item: any) => {
    const userType = toNumber(item.user_type || 1)
    const isFavorite = toBoolean(item.is_favorite)

    if (userType === 2) {
        const staffId = toNumber(item.staff_id || item.user?.id || 0)
        return {
            id: staffId,
            nickname: item.user?.nickname || '服务人员',
            avatar: item.user?.avatar || '',
            staffId,
            isFavorite,
            roleLabel: '服务人员',
            canFavorite: staffId > 0
        }
    }

    if (userType === 3) {
        return {
            id: 0,
            nickname: item.user?.nickname || '官方',
            avatar: item.user?.avatar || '',
            staffId: 0,
            isFavorite: false,
            roleLabel: '官方',
            canFavorite: false
        }
    }

    const authorId = toNumber(item.user_id || item.user?.id || 0)
    return {
        id: authorId,
        nickname: item.user?.nickname || '匿名用户',
        avatar: item.user?.avatar || '',
        staffId: 0,
        isFavorite: false,
        roleLabel: '',
        canFavorite: false
    }
}

export const mapDynamicItem = (item: any): DynamicCardData => {
    const tags = normalizeTags(item.tags)
    const dynamicType = toNumber(item.dynamic_type || 1)
    const imageList = normalizeImageList(item.images)

    let displayImages: string[] = []
    if (item.video_url || item.video) {
        if (item.video_cover) {
            displayImages = [item.video_cover]
        }
    } else if (imageList.length > 0) {
        displayImages = imageList
    }

    return {
        id: toNumber(item.id),
        user: resolveUserMeta(item),
        content: item.content || '',
        images: displayImages,
        topics: tags.map((tag, index) => ({
            id: index,
            name: tag
        })),
        location: item.location
            ? {
                  name: item.location,
                  lat: 0,
                  lng: 0
              }
            : undefined,
        viewCount: toNumber(item.view_count),
        likeCount: toNumber(item.like_count),
        commentCount: toNumber(item.comment_count),
        isLiked: toBoolean(item.is_liked),
        createTime: item.create_time || item.created_at || '',
        dynamicType,
        dynamicTypeLabel: getDynamicTypeLabel(dynamicType)
    }
}
