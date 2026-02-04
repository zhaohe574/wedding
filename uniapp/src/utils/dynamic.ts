export interface DynamicCardData {
    id: number
    user: {
        id: number
        nickname: string
        avatar: string
        isFollowed: boolean
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

export const mapDynamicItem = (item: any): DynamicCardData => {
    const tags = normalizeTags(item.tags)

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
        user: {
            id: item.user_id || item.user?.id || 0,
            nickname: item.user?.nickname || '匿名用户',
            avatar: item.user?.avatar || '',
            isFollowed: item.is_followed || false
        },
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
        createTime: item.create_time || item.created_at || ''
    }
}
