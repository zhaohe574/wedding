export enum MenuTypeEnum {
    'SHOP_PAGES' = 'shop',
    'APPTOOL' = 'application_tool',
    'OTHER_LINK' = 'other_link'
}

export enum LinkTypeEnum {
    'SHOP_PAGES' = 'shop',
    'ARTICLE_LIST' = 'article',
    'DYNAMIC_LIST' = 'dynamic',
    'CUSTOM_LINK' = 'custom',
    'MINI_PROGRAM' = 'mini_program'
}

export interface Link {
    id?: number | string
    path: string
    name?: string
    type: string
    canTab?: boolean
    query?: Record<string, any>
}
