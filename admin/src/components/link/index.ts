import type { DecorateLink } from '@shared/contracts/decorate-links'

export enum MenuTypeEnum {
    'SHOP_PAGES' = 'shop',
    'BUSINESS_CONTENT' = 'business_content',
    'ACTION_TOOL' = 'action_tool',
    'OTHER_LINK' = 'other_link'
}

export enum LinkTypeEnum {
    'SHOP_PAGES' = 'shop',
    'STAFF_DETAIL' = 'staff',
    'DYNAMIC_LIST' = 'dynamic',
    'ACTION' = 'action',
    'CUSTOM_LINK' = 'custom',
    'MINI_PROGRAM' = 'mini_program'
}

export type Link = DecorateLink
