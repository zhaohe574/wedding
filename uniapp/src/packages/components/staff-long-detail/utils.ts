export type LongDetailTextStyle = {
    align: 'left' | 'center' | 'right'
    fontSize: 'sm' | 'md' | 'lg'
    color: string
    bold: boolean
}

export type LongDetailImageBlock = {
    id: string
    type: 'image'
    images: string[]
}

export type LongDetailTextBlock = {
    id: string
    type: 'text'
    content: string
    style: LongDetailTextStyle
}

export type LongDetailBlock = LongDetailImageBlock | LongDetailTextBlock

const defaultTextStyle = (): LongDetailTextStyle => ({
    align: 'left',
    fontSize: 'md',
    color: '#1E2432',
    bold: false
})

export const createLongDetailId = () => `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`

export const createTextBlock = (): LongDetailTextBlock => ({
    id: createLongDetailId(),
    type: 'text',
    content: '',
    style: defaultTextStyle()
})

export const createImageBlock = (): LongDetailImageBlock => ({
    id: createLongDetailId(),
    type: 'image',
    images: []
})

const normalizeTextStyle = (style: any): LongDetailTextStyle => ({
    align: ['left', 'center', 'right'].includes(style?.align) ? style.align : 'left',
    fontSize: ['sm', 'md', 'lg'].includes(style?.fontSize) ? style.fontSize : 'md',
    color: String(style?.color || '#1E2432'),
    bold: Boolean(style?.bold)
})

export const parseLongDetailDraftContent = (value?: string): LongDetailBlock[] => {
    if (!value) {
        return []
    }

    try {
        const parsed = JSON.parse(value)
        if (!Array.isArray(parsed)) {
            return []
        }

        return parsed.reduce<LongDetailBlock[]>((acc, item) => {
            if (!item || typeof item !== 'object') {
                return acc
            }

            if (item.type === 'image') {
                const images = Array.isArray(item.images)
                    ? item.images
                          .map((image: unknown) => String(image || '').trim())
                          .filter(Boolean)
                    : []

                acc.push({
                    id: String(item.id || createLongDetailId()),
                    type: 'image',
                    images
                })
                return acc
            }

            if (item.type === 'text') {
                acc.push({
                    id: String(item.id || createLongDetailId()),
                    type: 'text',
                    content: String(item.content || ''),
                    style: normalizeTextStyle(item.style)
                })
            }

            return acc
        }, [])
    } catch (_error) {
        return []
    }
}

export const stringifyLongDetailDraftContent = (blocks: LongDetailBlock[]) => {
    if (!blocks.length) {
        return ''
    }

    return JSON.stringify(
        blocks.map((block) => {
            if (block.type === 'image') {
                return {
                    id: block.id,
                    type: 'image' as const,
                    images: block.images.map((item) => String(item || '').trim()).filter(Boolean)
                }
            }

            return {
                id: block.id,
                type: 'text' as const,
                content: String(block.content || ''),
                style: {
                    align: block.style.align,
                    fontSize: block.style.fontSize,
                    color: block.style.color || '#1E2432',
                    bold: Boolean(block.style.bold)
                }
            }
        })
    )
}

export const parseLongDetailContent = (value?: string): LongDetailBlock[] => {
    if (!value) {
        return []
    }

    try {
        const parsed = JSON.parse(value)
        if (!Array.isArray(parsed)) {
            return []
        }

        return parsed.reduce<LongDetailBlock[]>((acc, item) => {
            if (!item || typeof item !== 'object') {
                return acc
            }

            if (item.type === 'image') {
                const images = Array.isArray(item.images)
                    ? item.images
                          .map((image: unknown) => String(image || '').trim())
                          .filter(Boolean)
                    : []

                if (images.length) {
                    acc.push({
                        id: String(item.id || createLongDetailId()),
                        type: 'image',
                        images
                    })
                }
                return acc
            }

            if (item.type === 'text') {
                const content = String(item.content || '')
                if (!content.trim()) {
                    return acc
                }

                acc.push({
                    id: String(item.id || createLongDetailId()),
                    type: 'text',
                    content,
                    style: normalizeTextStyle(item.style)
                })
            }

            return acc
        }, [])
    } catch (_error) {
        return []
    }
}

export const stringifyLongDetailContent = (blocks: LongDetailBlock[]) => {
    const normalized = blocks.reduce<LongDetailBlock[]>((acc, block) => {
        if (block.type === 'image') {
            const images = block.images.map((item) => String(item || '').trim()).filter(Boolean)
            if (images.length) {
                acc.push({
                    id: block.id,
                    type: 'image',
                    images
                })
            }
            return acc
        }

        const content = String(block.content || '')
        if (content.trim()) {
            acc.push({
                id: block.id,
                type: 'text',
                content,
                style: {
                    align: block.style.align,
                    fontSize: block.style.fontSize,
                    color: block.style.color || '#1E2432',
                    bold: Boolean(block.style.bold)
                }
            })
        }

        return acc
    }, [])

    return normalized.length ? JSON.stringify(normalized) : ''
}

export const hasLongDetailContent = (value?: string) => parseLongDetailContent(value).length > 0

export const getLongDetailFontSize = (size: LongDetailTextStyle['fontSize']) => {
    if (size === 'sm') return '26rpx'
    if (size === 'lg') return '34rpx'
    return '30rpx'
}
