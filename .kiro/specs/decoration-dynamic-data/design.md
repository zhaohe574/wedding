# 设计文档：装修组件动态数据加载

## 概述

本设计实现装修组件的动态数据加载机制，将当前的"数据快照"模式改为"引用ID + 动态查询"模式。核心思想是：装修配置只存储引用ID和显示控制信息，在渲染时根据ID动态获取最新的业务数据。

### 设计目标

1. **数据实时性**：前端始终显示最新的业务数据（头像、名称、价格等）
2. **存储优化**：减少装修配置表的数据冗余
3. **向后兼容**：支持已有的装修配置数据
4. **性能保障**：通过批量查询和缓存确保性能不降低

## 架构设计

### 整体架构

```
┌─────────────────────────────────────────────────────────────┐
│                      前端展示层                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ 员工展示组件  │  │ 套餐展示组件  │  │ 其他装修组件  │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
│         │                  │                  │              │
│         └──────────────────┴──────────────────┘              │
│                            │                                 │
└────────────────────────────┼─────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    API 接口层                                │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  装修数据解析服务 (DecorateDataService)              │   │
│  │  - 解析装修配置                                       │   │
│  │  - 提取引用ID列表                                     │   │
│  │  - 批量查询业务数据                                   │   │
│  │  - 合并数据并返回                                     │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    业务逻辑层                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ StaffLogic   │  │ PackageLogic │  │ ServiceLogic │      │
│  │ 批量查询员工  │  │ 批量查询套餐  │  │ 批量查询服务  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    数据存储层                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ ls_staff     │  │ ls_package   │  │ ls_service   │      │
│  │ (员工表)     │  │ (套餐表)     │  │ (服务表)     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ ls_decorate_page (装修配置表)                         │   │
│  │ - 只存储引用ID和显示控制信息                          │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 数据流向

#### 1. 管理后台保存流程

```
管理员选择员工/套餐
    ↓
前端只提交引用ID和控制信息
    ↓
后端保存到 ls_decorate_page.data 字段
    ↓
数据格式：{ staff_id: 1, is_show: "1", sort: 0 }
```

#### 2. 前端渲染流程

```
前端请求装修数据
    ↓
后端读取 ls_decorate_page
    ↓
解析 data 字段，提取所有引用ID
    ↓
批量查询业务数据（员工、套餐等）
    ↓
合并引用ID和业务数据
    ↓
返回完整数据给前端
    ↓
前端渲染组件
```

## 组件设计

### 1. 装修数据服务 (DecorateDataService)

**职责**：解析装修配置，动态填充业务数据

**核心方法**：

```php
class DecorateDataService
{
    /**
     * 解析装修页面数据，动态填充引用数据
     * @param array $pageData 装修页面数据
     * @return array 填充后的数据
     */
    public static function parsePageData(array $pageData): array
    
    /**
     * 解析单个组件数据
     * @param array $widget 组件数据
     * @return array 填充后的组件数据
     */
    private static function parseWidget(array $widget): array
    
    /**
     * 批量获取员工数据
     * @param array $staffIds 员工ID列表
     * @return array 员工数据映射 [id => data]
     */
    private static function batchGetStaffData(array $staffIds): array
    
    /**
     * 批量获取套餐数据
     * @param array $packageIds 套餐ID列表
     * @return array 套餐数据映射 [id => data]
     */
    private static function batchGetPackageData(array $packageIds): array
    
    /**
     * 合并引用数据和业务数据
     * @param array $item 引用数据项
     * @param array $businessData 业务数据
     * @param string $type 数据类型 (staff/package)
     * @return array 合并后的数据
     */
    private static function mergeData(array $item, array $businessData, string $type): array
}
```

### 2. 员工业务逻辑扩展 (StaffLogic)

**新增方法**：

```php
class StaffLogic extends BaseLogic
{
    /**
     * 批量获取员工信息（用于装修组件）
     * @param array $ids 员工ID列表
     * @param array $fields 需要的字段
     * @return array 员工数据列表
     */
    public static function batchGetByIds(array $ids, array $fields = []): array
    {
        if (empty($ids)) {
            return [];
        }
        
        $defaultFields = [
            'id', 'name', 'avatar', 'sn', 'category_name',
            'rating', 'order_count', 'tag_names', 'status'
        ];
        
        $fields = empty($fields) ? $defaultFields : $fields;
        
        return Staff::whereIn('id', $ids)
            ->where('delete_time', 0)
            ->field($fields)
            ->select()
            ->toArray();
    }
}
```

### 3. 套餐业务逻辑扩展 (PackageLogic)

**新增方法**：

```php
class PackageLogic extends BaseLogic
{
    /**
     * 批量获取套餐信息（用于装修组件）
     * @param array $ids 套餐ID列表
     * @param array $fields 需要的字段
     * @return array 套餐数据列表
     */
    public static function batchGetByIds(array $ids, array $fields = []): array
    {
        if (empty($ids)) {
            return [];
        }
        
        $defaultFields = [
            'id', 'name', 'image', 'desc', 'price',
            'original_price', 'tag', 'services', 'status'
        ];
        
        $fields = empty($fields) ? $fields : $defaultFields;
        
        return Package::whereIn('id', $ids)
            ->where('delete_time', 0)
            ->field($fields)
            ->select()
            ->toArray();
    }
}
```

### 4. 装修页面逻辑修改 (DecoratePageLogic)

**修改 getDetail 方法**：

```php
public static function getDetail($id)
{
    $pageData = DecoratePage::findOrEmpty($id)->toArray();
    
    if (empty($pageData)) {
        return [];
    }
    
    // 解析并填充动态数据
    $pageData = DecorateDataService::parsePageData($pageData);
    
    return $pageData;
}
```

### 5. 前端 API 控制器修改 (IndexController)

**修改 decorate 方法**：

```php
public function decorate()
{
    $id = $this->request->get('id/d');
    $result = IndexLogic::getDecorate($id);
    
    // 动态填充业务数据
    if (!empty($result['data'])) {
        $result = DecorateDataService::parsePageData($result);
    }
    
    return $this->data($result);
}
```

## 数据模型设计

### 装修配置数据结构

#### 旧格式（数据快照模式）

```json
{
  "id": "staff-showcase-1",
  "type": "staff-showcase",
  "content": {
    "title": "推荐人员",
    "show_count": 4,
    "data": [
      {
        "staff_id": 1,
        "is_show": "1",
        "avatar": "https://example.com/avatar1.jpg",
        "name": "张三",
        "role": "高级技师",
        "rating": "5.0",
        "order_count": 120,
        "tags": ["专业", "细心"],
        "link": {...}
      }
    ]
  }
}
```

#### 新格式（引用ID模式）

```json
{
  "id": "staff-showcase-1",
  "type": "staff-showcase",
  "content": {
    "title": "推荐人员",
    "show_count": 4,
    "data": [
      {
        "staff_id": 1,
        "is_show": "1",
        "sort": 0
      },
      {
        "staff_id": 2,
        "is_show": "1",
        "sort": 1
      }
    ]
  }
}
```

#### 后端返回格式（动态填充后）

```json
{
  "id": "staff-showcase-1",
  "type": "staff-showcase",
  "content": {
    "title": "推荐人员",
    "show_count": 4,
    "data": [
      {
        "staff_id": 1,
        "is_show": "1",
        "sort": 0,
        "avatar": "https://example.com/avatar1.jpg",
        "name": "张三",
        "role": "高级技师",
        "rating": "5.0",
        "order_count": 120,
        "tags": ["专业", "细心"],
        "link": {
          "path": "/packages/pages/staff_detail/staff_detail",
          "query": {"id": 1},
          "type": "shop"
        }
      }
    ]
  }
}
```

### 组件类型映射

```php
// 组件类型与数据源的映射关系
const WIDGET_DATA_SOURCE_MAP = [
    'staff-showcase' => [
        'id_field' => 'staff_id',
        'logic_class' => 'StaffLogic',
        'method' => 'batchGetByIds'
    ],
    'service-packages' => [
        'id_field' => 'package_id',
        'logic_class' => 'PackageLogic',
        'method' => 'batchGetByIds'
    ],
    // 未来可扩展其他组件类型
];
```

## 接口设计

### 1. 批量查询员工信息

**接口路径**：`/api/staff/batchGet`

**请求方法**：POST

**请求参数**：

```json
{
  "ids": [1, 2, 3],
  "fields": ["id", "name", "avatar", "rating"]
}
```

**响应数据**：

```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "name": "张三",
      "avatar": "https://example.com/avatar1.jpg",
      "rating": "5.0"
    }
  ]
}
```

### 2. 批量查询套餐信息

**接口路径**：`/api/package/batchGet`

**请求方法**：POST

**请求参数**：

```json
{
  "ids": [1, 2, 3],
  "fields": ["id", "name", "image", "price"]
}
```

**响应数据**：

```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "name": "基础套餐",
      "image": "https://example.com/package1.jpg",
      "price": "299.00"
    }
  ]
}
```

### 3. 装修数据获取（已有接口，需修改）

**接口路径**：`/api/index/decorate`

**请求方法**：GET

**请求参数**：

```
id: 1  // 装修页面ID
```

**响应数据**：

```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "type": "index",
    "name": "首页",
    "data": [
      {
        "id": "staff-showcase-1",
        "type": "staff-showcase",
        "content": {
          "title": "推荐人员",
          "data": [
            {
              "staff_id": 1,
              "is_show": "1",
              "avatar": "...",
              "name": "张三",
              "..."
            }
          ]
        }
      }
    ]
  }
}
```

## 正确性属性

*属性是一种特征或行为，应该在系统的所有有效执行中保持为真——本质上是关于系统应该做什么的正式声明。属性作为人类可读规范和机器可验证正确性保证之间的桥梁。*

### 属性 1：引用完整性

*对于任何*装修配置数据，如果其中包含引用ID（staff_id、package_id），那么在动态加载时，系统应该能够正确处理引用的数据不存在的情况，不会导致系统崩溃或返回错误。

**验证：需求 4.1, 4.2**

### 属性 2：数据一致性

*对于任何*业务数据（员工、套餐），当其在业务表中被修改后，通过装修组件查询到的数据应该与业务表中的最新数据一致。

**验证：需求 2.1, 3.2**

### 属性 3：批量查询效率

*对于任何*装修页面，无论包含多少个组件和多少个引用ID，系统应该使用批量查询而不是N+1查询，即对于每种数据类型（员工、套餐）最多只执行一次数据库查询。

**验证：需求 5.1, 5.2**

### 属性 4：向后兼容性

*对于任何*旧格式的装修配置数据（包含完整数据快照），系统应该能够正确解析并返回数据，不会因为格式不匹配而失败。

**验证：需求 1.3, 8.1, 8.2**

### 属性 5：数据过滤正确性

*对于任何*引用ID列表，如果其中某些ID对应的数据已被删除或状态为禁用，那么返回的数据列表应该只包含有效的数据项，且顺序应该与原始引用ID列表的顺序一致（排除无效项后）。

**验证：需求 2.2, 2.3, 4.1**

### 属性 6：管理后台数据加载

*对于任何*装修配置，当管理员在装修后台打开配置时，系统应该自动加载所有引用项的最新数据，并在前端正确显示。

**验证：需求 6.1, 6.2**

### 属性 7：空数据处理

*对于任何*空的引用ID列表或无效的装修配置，系统应该返回空数组而不是抛出异常或返回错误。

**验证：需求 4.3, 7.4**

## 错误处理

### 1. 引用数据不存在

**场景**：装修配置中的 staff_id 或 package_id 对应的数据已被删除

**处理策略**：
- 后端：在批量查询时自动过滤不存在的ID，不返回该项
- 前端：正常渲染存在的数据项，不显示已删除的项
- 日志：记录数据不一致的情况，便于排查

**示例代码**：

```php
// 批量查询时自动过滤
$staffData = Staff::whereIn('id', $ids)
    ->where('delete_time', 0)  // 只查询未删除的数据
    ->select()
    ->toArray();

// 如果某些ID不存在，返回的数组会自动少于请求的ID数量
```

### 2. 数据库查询失败

**场景**：数据库连接失败或查询超时

**处理策略**：
- 捕获异常，返回空数组
- 记录错误日志
- 前端显示友好的错误提示

**示例代码**：

```php
try {
    $staffData = StaffLogic::batchGetByIds($ids);
} catch (\Exception $e) {
    Log::error('批量查询员工数据失败: ' . $e->getMessage());
    $staffData = [];
}
```

### 3. 数据格式不兼容

**场景**：旧格式的装修数据与新格式混合

**处理策略**：
- 检测数据格式，自动适配
- 如果包含完整数据快照，优先使用引用ID重新查询
- 如果没有引用ID，使用快照数据

**示例代码**：

```php
private static function parseWidget(array $widget): array
{
    // 检测是否为旧格式（包含完整数据快照）
    if (isset($widget['content']['data'][0]['name'])) {
        // 旧格式：提取引用ID，重新查询
        $ids = array_column($widget['content']['data'], 'staff_id');
        $widget['content']['data'] = self::fillStaffData($ids, $widget['content']['data']);
    } else {
        // 新格式：直接填充数据
        $ids = array_column($widget['content']['data'], 'staff_id');
        $widget['content']['data'] = self::fillStaffData($ids, $widget['content']['data']);
    }
    
    return $widget;
}
```

### 4. 批量查询性能问题

**场景**：单个页面包含大量引用ID（如100+个员工）

**处理策略**：
- 限制单次查询的最大数量（如50个）
- 分批查询并合并结果
- 使用缓存减少数据库压力

**示例代码**：

```php
private static function batchGetStaffData(array $staffIds): array
{
    if (empty($staffIds)) {
        return [];
    }
    
    // 限制单次查询数量
    $maxBatchSize = 50;
    $batches = array_chunk($staffIds, $maxBatchSize);
    
    $result = [];
    foreach ($batches as $batch) {
        $data = StaffLogic::batchGetByIds($batch);
        foreach ($data as $item) {
            $result[$item['id']] = $item;
        }
    }
    
    return $result;
}
```

## 测试策略

### 单元测试

**测试目标**：验证各个组件的独立功能

**测试用例**：

1. **DecorateDataService::parsePageData**
   - 测试空数据处理
   - 测试单个组件解析
   - 测试多个组件解析
   - 测试旧格式兼容

2. **StaffLogic::batchGetByIds**
   - 测试空ID列表
   - 测试单个ID
   - 测试多个ID
   - 测试不存在的ID
   - 测试已删除的数据

3. **PackageLogic::batchGetByIds**
   - 测试空ID列表
   - 测试单个ID
   - 测试多个ID
   - 测试不存在的ID

### 集成测试

**测试目标**：验证整个流程的正确性

**测试用例**：

1. **装修数据保存和读取**
   - 保存新格式数据
   - 读取并验证动态填充
   - 修改业务数据后再次读取
   - 验证数据已更新

2. **前端渲染测试**
   - 请求装修页面数据
   - 验证返回的数据格式
   - 验证数据完整性
   - 验证性能（响应时间）

### 性能测试

**测试目标**：确保动态加载不会降低性能

**测试指标**：

1. **响应时间**
   - 单个组件：< 100ms
   - 多个组件（5个）：< 200ms
   - 大量数据（50个引用）：< 500ms

2. **数据库查询次数**
   - 每种数据类型最多1次查询
   - 总查询次数 ≤ 组件类型数量

3. **内存使用**
   - 批量查询不应导致内存溢出
   - 缓存大小应该合理

### 兼容性测试

**测试目标**：确保新旧格式都能正常工作

**测试用例**：

1. **旧格式数据**
   - 读取包含完整快照的数据
   - 验证能够正确解析
   - 验证能够动态更新

2. **混合格式数据**
   - 部分组件使用新格式
   - 部分组件使用旧格式
   - 验证都能正常工作

## 实施计划

### 阶段 1：后端核心服务开发

1. 创建 DecorateDataService 服务类
2. 扩展 StaffLogic 和 PackageLogic
3. 修改 DecoratePageLogic 和 IndexLogic
4. 编写单元测试

### 阶段 2：API 接口开发

1. 创建批量查询接口
2. 修改现有装修数据接口
3. 编写接口文档
4. 进行接口测试

### 阶段 3：前端管理后台适配

1. 修改装修组件的数据保存逻辑
2. 只提交引用ID和控制信息
3. 修改预览逻辑，支持动态加载
4. 测试管理后台功能

### 阶段 4：前端展示页面适配

1. 修改 UniApp 端的数据渲染逻辑
2. 修改 PC 端的数据渲染逻辑
3. 测试前端展示效果
4. 性能优化

### 阶段 5：数据迁移和上线

1. 编写数据迁移脚本
2. 在测试环境验证
3. 灰度发布
4. 全量上线
5. 监控和优化

## 风险和缓解措施

### 风险 1：性能下降

**描述**：动态查询可能导致响应时间增加

**缓解措施**：
- 使用批量查询减少数据库访问
- 添加缓存层
- 限制单次查询的数量
- 监控性能指标

### 风险 2：数据不一致

**描述**：并发修改可能导致数据不一致

**缓解措施**：
- 使用事务保证数据一致性
- 添加乐观锁机制
- 记录详细日志便于排查

### 风险 3：兼容性问题

**描述**：新旧格式混合可能导致解析错误

**缓解措施**：
- 充分测试各种数据格式
- 提供数据迁移工具
- 保留旧格式的兼容代码
- 灰度发布，逐步切换

### 风险 4：前端渲染异常

**描述**：数据格式变化可能导致前端渲染失败

**缓解措施**：
- 保持 API 返回格式不变
- 在后端完成数据合并
- 前端添加容错处理
- 充分测试各种场景
