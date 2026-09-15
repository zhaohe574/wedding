//菜单主题类型
export enum ThemeEnum {
    LIGHT = 'light',
    DARK = 'dark'
}

// 客户端
export enum ClientEnum {
    MP_WEIXIN = 1 // 微信小程序
}

export enum SMSEnum {
    LOGIN = 'YZMDL',
    BIND_MOBILE = 'BDSJHM',
    CHANGE_MOBILE = 'BGSJHM',
    FIND_PASSWORD = 'ZHDLMM'
}

export enum SearchTypeEnum {
    HISTORY = 'history'
}

// 用户资料
export enum FieldType {
    NONE = '',
    AVATAR = 'avatar',
    USERNAME = 'account',
    NICKNAME = 'nickname',
    SEX = 'sex',
    REAL_NAME = 'real_name'
}

// 支付结果
export enum PayStatusEnum {
    SUCCESS = 'success',
    FAIL = 'fail',
    PENDING = 'pending'
}

// 页面状态
export enum PageStatusEnum {
    LOADING = 'loading', // 加载中
    NORMAL = 'normal', // 正常
    ERROR = 'error', // 异常
    EMPTY = 'empty' // 为空
}
