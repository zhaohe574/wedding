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
    const userType = Number(item.user_type || 1)
    const isFavorite = Boolean(item.is_favorite)

    if (userType === 2) {
        const staffId = Number(item.staff_id || item.user?.id || 0)
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

    const authorId = Number(item.user_id || item.user?.id || 0)
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
    const dynamicType = Number(item.dynamic_type || 1)

    let displayImages: string[] = []
    if (item.video_url || item.video) {
        if (item.video_cover) {
            displayImages = [item.video_cover]
        }
    } else if (Array.isArray(item.images) && item.images.length > 0) {
        displayImages = item.images
    }

    return {
        id: item.id,
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
        viewCount: item.view_count || 0,
        likeCount: item.like_count || 0,
        commentCount: item.comment_count || 0,
        isLiked: item.is_liked || false,
        createTime: item.create_time || item.created_at || '',
        dynamicType,
        dynamicTypeLabel: getDynamicTypeLabel(dynamicType)
    }
}
