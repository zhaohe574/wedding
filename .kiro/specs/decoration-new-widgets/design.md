# 设计文档：装修组件扩展功能

## 概述

本设计文档描述了 9 个新增装修组件的技术实现方案，包括组件架构、数据结构、接口设计和前端实现细节。所有组件遵循现有装修系统的架构模式，采用动态数据加载机制，确保业务数据更新后组件自动显示最新内容。

## 架构设计

### 整体架构

```
┌─────────────────────────────────────────────────────────────┐
│                      管理后台 (Admin)                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  attr.vue    │  │ content.vue  │  │  options.ts  │      │
│  │  (配置界面)   │  │  (预览界面)   │  │  (默认配置)   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ 保存配置
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    后端服务 (Server)                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  DecorateDataService (装修数据解析服务)                │   │
│  │  - parsePageData(): 解析并填充动态数据                │   │
│  │  - batchGetCouponData(): 批量获取优惠券数据           │   │
│  │  - batchGetNoticeData(): 批量获取公告数据             │   │
│  │  - batchGetTopicData(): 批量获取话题数据              │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ 返回填充后的数据
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    前端展示 (UniApp)                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ quick-entry  │  │    coupon    │  │  statistics  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │     faq      │  │    process   │  │    notice    │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  hot-topics  │  │  store-map   │  │  countdown   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### 组件结构

每个组件包含三个核心文件：

1. **attr.vue** - 管理后台配置界面
2. **content.vue** - 管理后台预览界面
3. **options.ts** - 组件默认配置和类型定义


## 组件设计

### 1. 快捷入口组件 (quick-entry)

#### 数据结构

```typescript
// options.ts
export interface QuickEntryItem {
    icon: string          // 图标URL或图标名称
    title: string         // 入口标题
    link: Record<string, any>  // 跳转链接配置
    is_show: '0' | '1'   // 是否显示
}

export interface QuickEntryContent {
    enabled: 0 | 1       // 是否启用
    style: 1 | 2         // 展示样式：1=网格布局，2=横向滑动
    per_line: 4 | 5      // 每行显示数量
    data: QuickEntryItem[]  // 入口列表
}

export default () => ({
    title: '快捷入口',
    name: 'quick-entry',
    pageScope: ['home', 'user'],
    content: {
        enabled: 1,
        style: 1,
        per_line: 4,
        data: [
            {
                icon: '',
                title: '我的订单',
                link: { path: '/pages/order/order', type: 'page' },
                is_show: '1'
            },
            {
                icon: '',
                title: '我的优惠券',
                link: { path: '/pages/coupon/list', type: 'page' },
                is_show: '1'
            },
            {
                icon: '',
                title: '联系客服',
                link: { path: '/pages/customer_service/customer_service', type: 'page' },
                is_show: '1'
            },
            {
                icon: '',
                title: '预约档期',
                link: { path: '/packages/pages/schedule_calendar/schedule_calendar', type: 'page' },
                is_show: '1'
            }
        ]
    },
    styles: {}
})
```

#### 管理后台实现要点

**attr.vue**:
- 使用 `material-picker` 组件选择图标
- 使用 `link-picker` 组件配置跳转链接
- 使用 `draggable` 组件实现拖拽排序
- 限制最多添加 12 个入口

**content.vue**:
- 根据 `per_line` 计算网格布局
- 只显示 `is_show='1'` 的入口
- 使用 TuniaoUI 的图标组件

#### UniApp 前端实现要点

- 使用 `grid` 布局或 `scroll-view` 横向滑动
- 图标使用 `tn-icon` 组件
- 点击跳转使用 `uni.navigateTo` 或 `uni.switchTab`

---

### 2. 优惠券领取组件 (coupon-center)

#### 数据结构

```typescript
// options.ts
export interface CouponItem {
    coupon_id: number    // 优惠券ID
    is_show: string      // 是否显示
    sort?: number        // 排序
    // 以下字段由后端动态填充
    name?: string        // 优惠券名称
    type?: number        // 优惠券类型：1=满减券，2=折扣券
    discount?: string    // 折扣金额或折扣率
    condition?: string   // 使用条件
    start_time?: string  // 开始时间
    end_time?: string    // 结束时间
    total?: number       // 总数量
    received?: number    // 已领取数量
    is_received?: boolean // 当前用户是否已领取
}

export interface CouponContent {
    enabled: 0 | 1       // 是否启用
    title: string        // 组件标题
    style: 1 | 2 | 3     // 展示样式：1=卡片式，2=横向滑动，3=弹窗式
    show_count: number   // 显示数量
    show_more: 0 | 1     // 是否显示查看更多
    more_link: Record<string, any>  // 查看更多链接
    data: CouponItem[]   // 优惠券列表
}

export default () => ({
    title: '优惠券领取',
    name: 'coupon-center',
    pageScope: ['home', 'user'],
    content: {
        enabled: 1,
        title: '领券中心',
        style: 1,
        show_count: 3,
        show_more: 1,
        more_link: { path: '/pages/coupon/center', type: 'page' },
        data: [] as CouponItem[]
    },
    styles: {}
})
```

#### 后端动态数据加载

**DecorateDataService.php** 新增方法：

```php
/**
 * 批量获取优惠券数据
 */
private static function batchGetCouponData(array $couponIds): array
{
    if (empty($couponIds)) {
        return [];
    }
    
    // 限制查询数量
    $couponIds = array_slice($couponIds, 0, 50);
    
    $coupons = Coupon::whereIn('id', $couponIds)
        ->where('status', 1)
        ->field('id, name, type, discount, condition, start_time, end_time, total, received')
        ->select()
        ->toArray();
    
    // 转换为以ID为键的数组
    $couponMap = [];
    foreach ($coupons as $coupon) {
        $couponMap[$coupon['id']] = $coupon;
    }
    
    return $couponMap;
}
```

#### 管理后台实现要点

**attr.vue**:
- 提供优惠券选择器（从优惠券库选择）
- 配置展示样式和数量
- 只保存 `coupon_id`、`is_show`、`sort`

**content.vue**:
- 显示优惠券卡片预览
- 显示优惠券面额和使用条件

#### UniApp 前端实现要点

- 使用 TuniaoUI 的 `tn-coupon` 组件
- 领取按钮调用 `/api/coupon/receive` 接口
- 已领取状态显示"已领取"并禁用按钮
- 已抢光状态显示"已抢光"

---

### 3. 数据统计卡片组件 (statistics-cards)

#### 数据结构

```typescript
// options.ts
export interface StatisticsItem {
    icon: string         // 图标
    title: string        // 标题
    value: string        // 数值
    unit: string         // 单位
    description: string  // 描述文字
    is_show: '0' | '1'  // 是否显示
}

export interface StatisticsContent {
    enabled: 0 | 1       // 是否启用
    title: string        // 组件标题
    style: 1 | 2         // 展示样式：1=横向滑动，2=网格布局
    per_line: 2 | 3      // 每行显示数量（网格布局）
    show_animation: 0 | 1  // 是否显示数字滚动动画
    data: StatisticsItem[]  // 统计项列表
}

export default () => ({
    title: '数据统计',
    name: 'statistics-cards',
    pageScope: ['home'],
    content: {
        enabled: 1,
        title: '品牌实力',
        style: 1,
        per_line: 2,
        show_animation: 1,
        data: [
            {
                icon: '',
                title: '累计服务',
                value: '1000',
                unit: '对新人',
                description: '专业服务，值得信赖',
                is_show: '1'
            },
            {
                icon: '',
                title: '好评率',
                value: '99.8',
                unit: '%',
                description: '客户满意是我们的追求',
                is_show: '1'
            }
        ]
    },
    styles: {}
})
```


#### UniApp 前端实现要点

- 使用 CSS 动画实现数字滚动效果
- 使用 `tn-count-to` 组件（如果 TuniaoUI 提供）
- 卡片样式使用渐变背景和阴影

---

### 4. 常见问题（FAQ）组件 (faq)

#### 数据结构

```typescript
// options.ts
export interface FaqItem {
    question: string     // 问题标题
    answer: string       // 答案内容（支持富文本）
    category: string     // 问题分类
    is_show: '0' | '1'  // 是否显示
}

export interface FaqContent {
    enabled: 0 | 1       // 是否启用
    title: string        // 组件标题
    style: 1 | 2         // 展示样式：1=折叠面板，2=列表式
    show_search: 0 | 1   // 是否显示搜索框
    data: FaqItem[]      // 问题列表
}

export default () => ({
    title: '常见问题',
    name: 'faq',
    pageScope: ['home', 'user'],
    content: {
        enabled: 1,
        title: '常见问题',
        style: 1,
        show_search: 1,
        data: [
            {
                question: '如何预约服务？',
                answer: '您可以在首页浏览服务人员，选择合适的人员和档期后加入购物车，填写信息后提交订单即可。',
                category: '预约相关',
                is_show: '1'
            },
            {
                question: '支持哪些支付方式？',
                answer: '我们支持微信支付、余额支付和线下支付三种方式。',
                category: '支付相关',
                is_show: '1'
            }
        ]
    },
    styles: {}
})
```

#### 管理后台实现要点

**attr.vue**:
- 使用富文本编辑器（`editor` 组件）编辑答案
- 提供问题分类选择器
- 支持拖拽排序

**content.vue**:
- 折叠面板预览
- 只显示问题标题

#### UniApp 前端实现要点

- 使用 `tn-collapse` 组件实现折叠面板
- 搜索功能使用本地过滤
- 答案内容使用 `rich-text` 组件渲染

---

### 5. 服务流程组件 (service-process)

#### 数据结构

```typescript
// options.ts
export interface ProcessStep {
    icon: string         // 步骤图标
    title: string        // 步骤标题
    description: string  // 步骤描述
    is_show: '0' | '1'  // 是否显示
}

export interface ProcessContent {
    enabled: 0 | 1       // 是否启用
    title: string        // 组件标题
    style: 1 | 2 | 3     // 展示样式：1=时间轴，2=步骤卡片，3=横向流程图
    line_color: string   // 流程线颜色
    icon_style: 1 | 2    // 图标样式：1=圆形，2=方形
    data: ProcessStep[]  // 流程步骤列表
}

export default () => ({
    title: '服务流程',
    name: 'service-process',
    pageScope: ['home'],
    content: {
        enabled: 1,
        title: '服务流程',
        style: 1,
        line_color: '#A855F7',
        icon_style: 1,
        data: [
            {
                icon: '',
                title: '咨询了解',
                description: '浏览服务人员信息，了解服务内容',
                is_show: '1'
            },
            {
                icon: '',
                title: '预约下单',
                description: '选择档期，提交订单',
                is_show: '1'
            },
            {
                icon: '',
                title: '确认支付',
                description: '工作人员确认，完成支付',
                is_show: '1'
            },
            {
                icon: '',
                title: '服务完成',
                description: '享受专业服务',
                is_show: '1'
            },
            {
                icon: '',
                title: '评价反馈',
                description: '分享您的体验',
                is_show: '1'
            }
        ]
    },
    styles: {}
})
```

#### UniApp 前端实现要点

- 使用 `tn-steps` 组件实现时间轴
- 横向流程图使用 `scroll-view` 横向滚动
- 流程线使用 CSS 绘制

---

### 6. 公告通知组件 (notice-bar)

#### 数据结构

```typescript
// options.ts
export interface NoticeItem {
    notice_id: number    // 公告ID
    is_show: string      // 是否显示
    sort?: number        // 排序
    // 以下字段由后端动态填充
    title?: string       // 公告标题
    icon?: string        // 公告图标
    link?: Record<string, any>  // 跳转链接
    create_time?: string // 创建时间
}

export interface NoticeContent {
    enabled: 0 | 1       // 是否启用
    style: 1 | 2 | 3     // 展示样式：1=横向滚动，2=纵向滚动，3=静态展示
    show_count: number   // 显示数量
    scroll_speed: number // 滚动速度（毫秒）
    bg_color: string     // 背景颜色
    text_color: string   // 文字颜色
    data: NoticeItem[]   // 公告列表
}

export default () => ({
    title: '公告通知',
    name: 'notice-bar',
    pageScope: ['home', 'user', 'news'],
    content: {
        enabled: 1,
        style: 1,
        show_count: 3,
        scroll_speed: 3000,
        bg_color: '#FFF7ED',
        text_color: '#EA580C',
        data: [] as NoticeItem[]
    },
    styles: {}
})
```

#### 后端动态数据加载

**DecorateDataService.php** 新增方法：

```php
/**
 * 批量获取公告数据
 */
private static function batchGetNoticeData(array $noticeIds): array
{
    if (empty($noticeIds)) {
        return [];
    }
    
    $noticeIds = array_slice($noticeIds, 0, 50);
    
    $notices = Notice::whereIn('id', $noticeIds)
        ->where('status', 1)
        ->field('id, title, icon, link, create_time')
        ->select()
        ->toArray();
    
    $noticeMap = [];
    foreach ($notices as $notice) {
        $noticeMap[$notice['id']] = $notice;
    }
    
    return $noticeMap;
}
```

#### UniApp 前端实现要点

- 使用 `tn-notice-bar` 组件
- 横向滚动使用 `marquee` 动画
- 纵向滚动使用 `swiper` 组件垂直方向

---

### 7. 热门话题组件 (hot-topics)

#### 数据结构

```typescript
// options.ts
export interface TopicItem {
    topic_id: number     // 话题ID
    is_show: string      // 是否显示
    sort?: number        // 排序
    // 以下字段由后端动态填充
    name?: string        // 话题名称
    icon?: string        // 话题图标
    count?: number       // 参与人数
    link?: Record<string, any>  // 跳转链接
}

export interface TopicContent {
    enabled: 0 | 1       // 是否启用
    title: string        // 组件标题
    style: 1 | 2 | 3     // 展示样式：1=标签云，2=横向滑动，3=列表式
    show_count: number   // 显示数量
    source: 1 | 2        // 话题来源：1=手动选择，2=自动获取热门
    data: TopicItem[]    // 话题列表
}

export default () => ({
    title: '热门话题',
    name: 'hot-topics',
    pageScope: ['home', 'news'],
    content: {
        enabled: 1,
        title: '热门话题',
        style: 1,
        show_count: 10,
        source: 2,
        data: [] as TopicItem[]
    },
    styles: {}
})
```


#### 后端动态数据加载

**DecorateDataService.php** 新增方法：

```php
/**
 * 批量获取话题数据
 */
private static function batchGetTopicData(array $topicIds): array
{
    if (empty($topicIds)) {
        return [];
    }
    
    $topicIds = array_slice($topicIds, 0, 50);
    
    $topics = MomentTopic::whereIn('id', $topicIds)
        ->where('status', 1)
        ->field('id, name, icon, count')
        ->select()
        ->toArray();
    
    $topicMap = [];
    foreach ($topics as $topic) {
        $topic['link'] = [
            'path' => '/pages/dynamic/dynamic',
            'query' => ['topic_id' => $topic['id']],
            'type' => 'page'
        ];
        $topicMap[$topic['id']] = $topic;
    }
    
    return $topicMap;
}

/**
 * 获取热门话题（自动模式）
 */
public static function getHotTopics(int $limit = 10): array
{
    return MomentTopic::where('status', 1)
        ->order('count', 'desc')
        ->limit($limit)
        ->field('id, name, icon, count')
        ->select()
        ->toArray();
}
```

#### UniApp 前端实现要点

- 标签云使用 `tn-tag` 组件，随机大小和颜色
- 横向滑动使用 `scroll-view`
- 点击跳转到话题动态列表页

---

### 8. 门店地图组件 (store-map)

#### 数据结构

```typescript
// options.ts
export interface StoreItem {
    name: string         // 门店名称
    address: string      // 门店地址
    latitude: number     // 纬度
    longitude: number    // 经度
    phone: string        // 联系电话
    business_hours: string  // 营业时间
    is_show: '0' | '1'  // 是否显示
}

export interface StoreMapContent {
    enabled: 0 | 1       // 是否启用
    title: string        // 组件标题
    style: 1 | 2 | 3     // 展示样式：1=地图+列表，2=纯地图，3=纯列表
    auto_sort: 0 | 1     // 是否根据距离自动排序
    data: StoreItem[]    // 门店列表
}

export default () => ({
    title: '门店地图',
    name: 'store-map',
    pageScope: ['home'],
    content: {
        enabled: 1,
        title: '门店位置',
        style: 1,
        auto_sort: 1,
        data: [
            {
                name: '总店',
                address: '北京市朝阳区xxx路xxx号',
                latitude: 39.9042,
                longitude: 116.4074,
                phone: '010-12345678',
                business_hours: '09:00-18:00',
                is_show: '1'
            }
        ]
    },
    styles: {}
})
```

#### 管理后台实现要点

**attr.vue**:
- 使用地图选点组件选择门店位置
- 自动获取经纬度
- 配置门店详细信息

**content.vue**:
- 显示地图预览（使用静态地图图片）
- 显示门店列表

#### UniApp 前端实现要点

- 使用 `map` 组件显示地图
- 使用 `markers` 标注门店位置
- 点击标注显示门店信息
- 导航按钮调用 `uni.openLocation`
- 电话按钮调用 `uni.makePhoneCall`
- 使用 `uni.getLocation` 获取用户位置并计算距离

---

### 9. 婚礼倒计时组件 (wedding-countdown)

#### 数据结构

```typescript
// options.ts
export interface CountdownContent {
    enabled: 0 | 1       // 是否启用
    title: string        // 组件标题
    style: 1 | 2 | 3     // 展示样式：1=大字倒计时，2=卡片式，3=进度条式
    bg_image: string     // 背景图片
    text_color: string   // 文字颜色
    blessing: string     // 祝福语文案
}

export default () => ({
    title: '婚礼倒计时',
    name: 'wedding-countdown',
    pageScope: ['user'],
    content: {
        enabled: 1,
        title: '距离您的婚礼',
        style: 1,
        bg_image: '',
        text_color: '#FFFFFF',
        blessing: '愿你们幸福美满，白头偕老'
    },
    styles: {}
})
```

#### 后端接口设计

**新增接口**：`GET /api/user/weddingDate`

```php
/**
 * 获取用户婚期
 */
public function weddingDate()
{
    $userId = $this->userId;
    
    // 查询用户最近的已支付订单
    $order = Order::where('user_id', $userId)
        ->where('pay_status', 1)
        ->where('order_status', '<', 4)
        ->order('service_date', 'asc')
        ->field('service_date')
        ->find();
    
    if (!$order) {
        return $this->success('', [
            'has_order' => false
        ]);
    }
    
    return $this->success('', [
        'has_order' => true,
        'wedding_date' => $order['service_date'],
        'days_left' => ceil((strtotime($order['service_date']) - time()) / 86400)
    ]);
}
```

#### UniApp 前端实现要点

- 页面加载时调用 `/api/user/weddingDate` 获取婚期
- 使用 `setInterval` 实时更新倒计时
- 计算天、小时、分钟、秒
- 没有订单时隐藏组件或显示默认提示
- 使用渐变背景和动画效果

---

## 数据模型

### 装修页面数据表

**表名**：`la_decorate_page`

**字段说明**：
- `id`: 主键
- `type`: 页面类型（1=移动端，2=PC端）
- `name`: 页面名称（home/user/news）
- `data`: 装修配置数据（JSON格式）
- `meta`: 页面元数据（JSON格式）

**data 字段结构**：

```json
{
  "items": [
    {
      "id": "quick-entry-1",
      "type": "quick-entry",
      "content": {
        "enabled": 1,
        "style": 1,
        "per_line": 4,
        "data": [...]
      },
      "styles": {}
    },
    {
      "id": "coupon-center-1",
      "type": "coupon-center",
      "content": {
        "enabled": 1,
        "title": "领券中心",
        "style": 1,
        "show_count": 3,
        "data": [
          {
            "coupon_id": 1,
            "is_show": "1",
            "sort": 0
          }
        ]
      },
      "styles": {}
    }
  ]
}
```

### 优惠券表

**表名**：`la_coupon`

**关键字段**：
- `id`: 优惠券ID
- `name`: 优惠券名称
- `type`: 优惠券类型（1=满减券，2=折扣券）
- `discount`: 折扣金额或折扣率
- `condition`: 使用条件
- `start_time`: 开始时间
- `end_time`: 结束时间
- `total`: 总数量
- `received`: 已领取数量
- `status`: 状态（0=禁用，1=启用）

### 公告表

**表名**：`la_notice`

**关键字段**：
- `id`: 公告ID
- `title`: 公告标题
- `content`: 公告内容
- `icon`: 公告图标
- `link`: 跳转链接
- `status`: 状态（0=禁用，1=启用）
- `create_time`: 创建时间

### 话题表

**表名**：`la_moment_topic`

**关键字段**：
- `id`: 话题ID
- `name`: 话题名称
- `icon`: 话题图标
- `count`: 参与人数
- `status`: 状态（0=禁用，1=启用）


## 接口设计

### 管理后台接口

#### 1. 获取装修页面详情

**接口**：`GET /adminapi/decorate.page/detail`

**参数**：
- `id`: 页面ID

**返回**：
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 1,
    "type": 1,
    "name": "home",
    "data": {
      "items": [...]
    },
    "meta": {}
  }
}
```

**说明**：
- 返回的数据中，引用类型组件（优惠券、公告、话题）的业务数据已由 `DecorateDataService::parsePageData()` 动态填充

#### 2. 保存装修页面

**接口**：`POST /adminapi/decorate.page/save`

**参数**：
- `id`: 页面ID
- `data`: 装修配置数据

**说明**：
- 保存时只存储引用ID和控制信息，不存储完整的业务数据

#### 3. 获取优惠券列表（用于选择器）

**接口**：`GET /adminapi/coupon/lists`

**参数**：
- `page`: 页码
- `limit`: 每页数量
- `status`: 状态筛选

**返回**：
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "lists": [
      {
        "id": 1,
        "name": "新人专享券",
        "type": 1,
        "discount": "50",
        "condition": "满500可用"
      }
    ],
    "count": 10
  }
}
```

#### 4. 获取公告列表（用于选择器）

**接口**：`GET /adminapi/notice/lists`

**参数**：
- `page`: 页码
- `limit`: 每页数量
- `status`: 状态筛选

**返回**：
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "lists": [
      {
        "id": 1,
        "title": "春节放假通知",
        "create_time": "2026-01-20 10:00:00"
      }
    ],
    "count": 5
  }
}
```

#### 5. 获取话题列表（用于选择器）

**接口**：`GET /adminapi/moment/topicLists`

**参数**：
- `page`: 页码
- `limit`: 每页数量
- `status`: 状态筛选

**返回**：
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "lists": [
      {
        "id": 1,
        "name": "#婚礼现场",
        "count": 120
      }
    ],
    "count": 15
  }
}
```

### 前端展示接口

#### 1. 获取首页装修数据

**接口**：`GET /api/index/decorate`

**返回**：
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "page": {
      "items": [...]
    }
  }
}
```

**说明**：
- 返回的数据已由 `DecorateDataService::parsePageData()` 动态填充业务数据

#### 2. 领取优惠券

**接口**：`POST /api/coupon/receive`

**参数**：
- `coupon_id`: 优惠券ID

**返回**：
```json
{
  "code": 1,
  "msg": "领取成功"
}
```

#### 3. 获取用户婚期

**接口**：`GET /api/user/weddingDate`

**返回**：
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "has_order": true,
    "wedding_date": "2026-05-20",
    "days_left": 120
  }
}
```

---

## 组件注册

### 管理后台组件注册

**文件**：`admin/src/views/decoration/component/widgets/index.ts`

组件会自动通过 `import.meta.glob` 注册，无需手动修改。

### UniApp 组件注册

**文件**：`uniapp/src/pages.json`

在 `easycom` 配置中已包含自动注册规则：

```json
{
  "easycom": {
    "custom": {
      "^w-(.*)": "@/components/widgets/$1/$1.vue"
    }
  }
}
```

组件命名规则：
- 管理后台：`quick-entry` → `admin/src/views/decoration/component/widgets/quick-entry/`
- UniApp：`quick-entry` → `uniapp/src/components/widgets/quick-entry/quick-entry.vue`

---

## 性能优化

### 1. 批量查询优化

**DecorateDataService.php** 核心优化：

```php
public static function parsePageData(array $pageData): array
{
    // 1. 提取所有引用ID
    $couponIds = [];
    $noticeIds = [];
    $topicIds = [];
    
    foreach ($pageData['data']['items'] as $item) {
        if ($item['type'] === 'coupon-center') {
            foreach ($item['content']['data'] as $couponItem) {
                $couponIds[] = $couponItem['coupon_id'];
            }
        }
        // ... 其他组件类型
    }
    
    // 2. 批量查询（每种类型只查询一次）
    $couponMap = self::batchGetCouponData($couponIds);
    $noticeMap = self::batchGetNoticeData($noticeIds);
    $topicMap = self::batchGetTopicData($topicIds);
    
    // 3. 合并数据
    foreach ($pageData['data']['items'] as &$item) {
        if ($item['type'] === 'coupon-center') {
            foreach ($item['content']['data'] as &$couponItem) {
                if (isset($couponMap[$couponItem['coupon_id']])) {
                    $couponItem = array_merge($couponItem, $couponMap[$couponItem['coupon_id']]);
                }
            }
        }
        // ... 其他组件类型
    }
    
    return $pageData;
}
```

### 2. 前端性能优化

#### 图片懒加载

```vue
<template>
  <image 
    :src="item.image" 
    lazy-load 
    mode="aspectFill"
  />
</template>
```

#### 长列表虚拟滚动

对于 FAQ 组件，当问题数量超过 20 个时，使用虚拟滚动：

```vue
<template>
  <scroll-view 
    scroll-y 
    :style="{ height: '600rpx' }"
  >
    <view v-for="item in visibleItems" :key="item.id">
      <!-- 问题内容 -->
    </view>
  </scroll-view>
</template>
```

#### 组件缓存

使用 Pinia 缓存装修配置数据：

```typescript
// stores/decoration.ts
export const useDecorationStore = defineStore('decoration', {
  state: () => ({
    homePageData: null,
    userPageData: null,
    cacheTime: 0
  }),
  actions: {
    async getPageData(pageName: string) {
      // 缓存 5 分钟
      if (this.cacheTime && Date.now() - this.cacheTime < 300000) {
        return this[`${pageName}PageData`]
      }
      
      const res = await request.get({ url: '/index/decorate' })
      this[`${pageName}PageData`] = res.data
      this.cacheTime = Date.now()
      return res.data
    }
  }
})
```

---

## 错误处理

### 1. 数据不存在处理

当引用的业务数据被删除时：

```php
// 批量查询后过滤无效数据
foreach ($item['content']['data'] as $key => &$dataItem) {
    if (!isset($couponMap[$dataItem['coupon_id']])) {
        // 记录日志
        Log::warning("优惠券不存在: {$dataItem['coupon_id']}");
        // 从列表中移除
        unset($item['content']['data'][$key]);
    }
}
// 重新索引数组
$item['content']['data'] = array_values($item['content']['data']);
```

### 2. 接口异常处理

UniApp 前端统一错误处理：

```typescript
// utils/request.ts
export const request = {
  async get(options: RequestOptions) {
    try {
      const res = await uni.request({
        url: baseUrl + options.url,
        method: 'GET',
        data: options.params
      })
      
      if (res.data.code !== 1) {
        uni.showToast({
          title: res.data.msg || '请求失败',
          icon: 'none'
        })
        return Promise.reject(res.data)
      }
      
      return res.data
    } catch (error) {
      uni.showToast({
        title: '网络错误',
        icon: 'none'
      })
      return Promise.reject(error)
    }
  }
}
```

### 3. 组件降级处理

当组件数据加载失败时，显示占位内容：

```vue
<template>
  <view v-if="loading">
    <tn-loading />
  </view>
  <view v-else-if="error">
    <view class="error-placeholder">
      <text>加载失败，请稍后重试</text>
    </view>
  </view>
  <view v-else>
    <!-- 正常内容 -->
  </view>
</template>
```

---

## 测试策略

### 1. 单元测试

**测试范围**：
- 组件 options.ts 的默认配置
- 数据结构类型定义
- 工具函数

**测试工具**：Jest + Vue Test Utils

**示例**：

```typescript
// quick-entry/options.test.ts
import options from './options'

describe('QuickEntry Options', () => {
  it('should have correct default values', () => {
    const config = options()
    expect(config.name).toBe('quick-entry')
    expect(config.content.enabled).toBe(1)
    expect(config.content.per_line).toBe(4)
  })
  
  it('should have at least 4 default entries', () => {
    const config = options()
    expect(config.content.data.length).toBeGreaterThanOrEqual(4)
  })
})
```


### 2. 集成测试

**测试范围**：
- 管理后台配置保存和读取
- 前端组件渲染
- 动态数据加载

**测试场景**：

1. **配置保存测试**：
   - 添加组件并配置
   - 保存装修页面
   - 验证数据库中只保存引用ID

2. **动态数据加载测试**：
   - 修改优惠券信息
   - 刷新前端页面
   - 验证显示最新数据

3. **数据删除测试**：
   - 删除引用的业务数据
   - 刷新前端页面
   - 验证组件正常显示（过滤已删除项）

### 3. 性能测试

**测试指标**：
- 页面首屏加载时间 < 2秒
- 批量查询响应时间 < 500ms
- 组件配置保存时间 < 1秒

**测试工具**：
- 后端：Apache JMeter
- 前端：Chrome DevTools Performance

**测试场景**：
- 单页面包含所有 9 个组件
- 每个组件显示最大数量的数据项
- 模拟 100 个并发用户访问

### 4. 兼容性测试

**测试平台**：
- 微信小程序（iOS、Android）
- H5（Chrome、Safari、微信浏览器）
- 不同屏幕尺寸（375px、414px、768px）

**测试内容**：
- 组件布局是否正常
- 交互功能是否正常
- 样式是否一致

---

## 安全考虑

### 1. 输入验证

**管理后台**：

```typescript
// attr.vue 中的验证
const validateConfig = () => {
  // 验证入口数量
  if (content.data.length > 12) {
    feedback.msgError('最多添加12个入口')
    return false
  }
  
  // 验证必填字段
  for (const item of content.data) {
    if (!item.title) {
      feedback.msgError('请填写入口标题')
      return false
    }
    if (!item.link || !item.link.path) {
      feedback.msgError('请配置跳转链接')
      return false
    }
  }
  
  return true
}
```

**后端验证**：

```php
// DecoratePageLogic.php
public static function save($params)
{
    // 验证数据格式
    if (!isset($params['data']['items']) || !is_array($params['data']['items'])) {
        throw new Exception('数据格式错误');
    }
    
    // 验证组件数量
    if (count($params['data']['items']) > 20) {
        throw new Exception('组件数量不能超过20个');
    }
    
    // 验证每个组件的配置
    foreach ($params['data']['items'] as $item) {
        self::validateWidget($item);
    }
    
    // 保存数据
    // ...
}
```

### 2. XSS 防护

**富文本内容过滤**：

```php
// FAQ 组件答案内容过滤
use think\facade\Db;

public static function filterHtml($html)
{
    // 使用 HTMLPurifier 过滤
    $config = \HTMLPurifier_Config::createDefault();
    $purifier = new \HTMLPurifier($config);
    return $purifier->purify($html);
}
```

**前端渲染**：

```vue
<!-- 使用 rich-text 组件渲染，自动转义 -->
<rich-text :nodes="item.answer"></rich-text>
```

### 3. 链接白名单验证

```php
// 验证跳转链接
public static function validateLink($link)
{
    if (!isset($link['type']) || !isset($link['path'])) {
        throw new Exception('链接配置错误');
    }
    
    // 内部页面链接
    if ($link['type'] === 'page') {
        $allowedPages = [
            '/pages/index/index',
            '/pages/order/order',
            '/pages/coupon/list',
            // ... 其他允许的页面
        ];
        
        if (!in_array($link['path'], $allowedPages)) {
            throw new Exception('不允许跳转到该页面');
        }
    }
    
    // 外部链接
    if ($link['type'] === 'url') {
        // 验证域名白名单
        $allowedDomains = ['example.com', 'trusted-site.com'];
        $host = parse_url($link['path'], PHP_URL_HOST);
        
        if (!in_array($host, $allowedDomains)) {
            throw new Exception('不允许跳转到该域名');
        }
    }
}
```

### 4. 权限控制

```php
// 管理后台接口权限验证
class DecoratePageController extends BaseAdminController
{
    // 需要验证权限的方法
    protected $authExcept = [];
    
    public function save()
    {
        // 验证管理员权限
        $this->checkAuth('decorate.page/save');
        
        // 保存逻辑
        // ...
    }
}
```

---

## 部署方案

### 1. 数据库迁移

**新增字段**（如果需要）：

```sql
-- 优惠券表添加字段（如果不存在）
ALTER TABLE `la_coupon` ADD COLUMN `icon` VARCHAR(255) DEFAULT '' COMMENT '优惠券图标';

-- 公告表添加字段（如果不存在）
ALTER TABLE `la_notice` ADD COLUMN `icon` VARCHAR(255) DEFAULT '' COMMENT '公告图标';
ALTER TABLE `la_notice` ADD COLUMN `link` TEXT COMMENT '跳转链接';

-- 话题表添加字段（如果不存在）
ALTER TABLE `la_moment_topic` ADD COLUMN `icon` VARCHAR(255) DEFAULT '' COMMENT '话题图标';
```

### 2. 代码部署步骤

1. **后端部署**：
   ```bash
   # 1. 更新代码
   git pull origin main
   
   # 2. 安装依赖
   composer install
   
   # 3. 清除缓存
   php think clear
   
   # 4. 执行数据库迁移（如果有）
   php think migrate:run
   ```

2. **管理后台部署**：
   ```bash
   # 1. 更新代码
   git pull origin main
   
   # 2. 安装依赖
   cd admin
   npm install
   
   # 3. 构建生产版本
   npm run build
   
   # 4. 部署到服务器
   # 将 dist 目录内容复制到 server/public/admin
   ```

3. **UniApp 部署**：
   ```bash
   # 1. 更新代码
   git pull origin main
   
   # 2. 安装依赖
   cd uniapp
   npm install
   
   # 3. 构建小程序
   npm run build:mp-weixin
   
   # 4. 上传到微信小程序后台
   # 使用微信开发者工具上传 dist/build/mp-weixin
   ```

### 3. 灰度发布

**阶段 1**：内部测试（1-2 天）
- 在测试环境部署
- 内部团队测试所有功能
- 修复发现的问题

**阶段 2**：小范围灰度（3-5 天）
- 选择 10% 用户看到新组件
- 监控错误日志和性能指标
- 收集用户反馈

**阶段 3**：全量发布
- 所有用户可见新组件
- 持续监控系统稳定性

### 4. 回滚方案

如果发现严重问题，可以快速回滚：

1. **代码回滚**：
   ```bash
   git revert <commit-hash>
   git push origin main
   ```

2. **配置回滚**：
   - 在管理后台禁用新组件
   - 或删除新组件的配置

3. **数据库回滚**：
   - 如果有数据库变更，执行回滚脚本

---

## 监控与维护

### 1. 日志记录

**关键操作日志**：

```php
// 记录组件配置变更
Log::info('装修组件配置变更', [
    'admin_id' => $adminId,
    'page_name' => $pageName,
    'component_type' => $componentType,
    'action' => 'add/edit/delete'
]);

// 记录数据加载异常
Log::warning('组件数据加载失败', [
    'component_type' => $componentType,
    'reference_id' => $referenceId,
    'error' => $exception->getMessage()
]);
```

### 2. 性能监控

**关键指标**：
- 页面加载时间
- 接口响应时间
- 数据库查询次数
- 缓存命中率

**监控工具**：
- 后端：Laravel Telescope / Sentry
- 前端：微信小程序性能监控

### 3. 错误告警

**告警规则**：
- 接口错误率 > 5%
- 页面加载时间 > 3秒
- 数据库查询时间 > 1秒
- 批量查询失败

**告警方式**：
- 企业微信群通知
- 邮件通知
- 短信通知（严重问题）

### 4. 定期维护

**每周**：
- 检查错误日志
- 分析性能指标
- 优化慢查询

**每月**：
- 清理无效的装修配置
- 更新组件文档
- 收集用户反馈并优化

---

## 文档与培训

### 1. 技术文档

- **组件开发文档**：如何开发新的装修组件
- **API 文档**：所有接口的详细说明
- **数据库文档**：表结构和字段说明

### 2. 使用手册

- **管理员手册**：如何配置装修组件
- **常见问题**：配置过程中的常见问题和解决方案
- **最佳实践**：推荐的组件配置方案

### 3. 培训计划

**开发团队培训**：
- 装修系统架构讲解
- 动态数据加载机制
- 新组件开发流程

**运营团队培训**：
- 组件配置操作演示
- 常见问题处理
- 数据分析和优化建议

---

## 附录

### A. 组件目录结构

```
admin/src/views/decoration/component/widgets/
├── quick-entry/
│   ├── index.ts
│   ├── attr.vue
│   ├── content.vue
│   └── options.ts
├── coupon-center/
│   ├── index.ts
│   ├── attr.vue
│   ├── content.vue
│   └── options.ts
├── statistics-cards/
│   ├── index.ts
│   ├── attr.vue
│   ├── content.vue
│   └── options.ts
├── faq/
│   ├── index.ts
│   ├── attr.vue
│   ├── content.vue
│   └── options.ts
├── service-process/
│   ├── index.ts
│   ├── attr.vue
│   ├── content.vue
│   └── options.ts
├── notice-bar/
│   ├── index.ts
│   ├── attr.vue
│   ├── content.vue
│   └── options.ts
├── hot-topics/
│   ├── index.ts
│   ├── attr.vue
│   ├── content.vue
│   └── options.ts
├── store-map/
│   ├── index.ts
│   ├── attr.vue
│   ├── content.vue
│   └── options.ts
└── wedding-countdown/
    ├── index.ts
    ├── attr.vue
    ├── content.vue
    └── options.ts

uniapp/src/components/widgets/
├── quick-entry/
│   └── quick-entry.vue
├── coupon-center/
│   └── coupon-center.vue
├── statistics-cards/
│   └── statistics-cards.vue
├── faq/
│   └── faq.vue
├── service-process/
│   └── service-process.vue
├── notice-bar/
│   └── notice-bar.vue
├── hot-topics/
│   └── hot-topics.vue
├── store-map/
│   └── store-map.vue
└── wedding-countdown/
    └── wedding-countdown.vue
```

### B. 技术栈版本

- **后端**：PHP 8.0+, ThinkPHP 8.0
- **管理后台**：Vue 3.5, Element Plus 2.9, Vite
- **UniApp**：Vue 3, TuniaoUI
- **数据库**：MySQL 5.7+
- **缓存**：Redis 6.0+

### C. 参考资料

- [TuniaoUI 文档](https://vue3.tuniaokj.com)
- [UniApp 文档](https://uniapp.dcloud.net.cn/)
- [Element Plus 文档](https://element-plus.org/)
- [ThinkPHP 文档](https://www.kancloud.cn/manual/thinkphp8_0/)

---

**文档版本**：v1.0  
**创建日期**：2026-01-22  
**维护者**：开发团队  
**最后更新**：2026-01-22
