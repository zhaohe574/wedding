# 设计文档 - 服务分类重构

## 概述

本设计文档描述了将婚庆管理系统中的服务分类从多级树形结构简化为扁平化结构的技术方案。重构的核心目标是移除冗余的 `pid` 和 `level` 字段，简化相关业务逻辑，同时保持系统的向后兼容性和数据完整性。

## 架构

### 当前架构

```
┌─────────────────────────────────────────────────────────────┐
│                     前端层 (Admin/Uniapp)                    │
│  - 树形表格展示                                              │
│  - 父级分类选择器                                            │
│  - 树形数据结构处理                                          │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     控制器层 (Controller)                    │
│  - CategoryController::tree()  返回树形结构                  │
│  - CategoryController::all()   返回树形结构                  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     逻辑层 (Logic)                           │
│  - CategoryLogic::tree()       构建树形结构                  │
│  - CategoryLogic::buildTree()  递归构建树                    │
│  - CategoryLogic::add()        层级计算、父级检查            │
│  - CategoryLogic::edit()       层级计算、父子关系检查        │
│  - CategoryLogic::delete()     子分类检查                    │
│  - CategoryLogic::getAll()     构建树形结构                  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     模型层 (Model)                           │
│  - ServiceCategory::parent()      父级关联                   │
│  - ServiceCategory::children()    子级关联                   │
│  - ServiceCategory::buildTree()   静态树构建方法             │
│  - ServiceCategory::getCategoryTree() 获取树形结构           │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     数据库层 (Database)                      │
│  la_service_category 表                                      │
│  - id, pid, name, icon, image, level, sort, is_show         │
│  - 索引: idx_pid, idx_level                                  │
└─────────────────────────────────────────────────────────────┘
```

### 目标架构

```
┌─────────────────────────────────────────────────────────────┐
│                     前端层 (Admin/Uniapp)                    │
│  - 普通表格展示                                              │
│  - 下拉选择器                                                │
│  - 扁平数组数据结构                                          │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     控制器层 (Controller)                    │
│  - CategoryController::tree()  返回扁平列表(兼容)            │
│  - CategoryController::all()   返回扁平列表                  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     逻辑层 (Logic)                           │
│  - CategoryLogic::tree()       返回扁平列表(简化)            │
│  - CategoryLogic::add()        简化验证逻辑                  │
│  - CategoryLogic::edit()       简化验证逻辑                  │
│  - CategoryLogic::delete()     简化验证逻辑                  │
│  - CategoryLogic::getAll()     直接返回列表                  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     模型层 (Model)                           │
│  - ServiceCategory::staffs()      工作人员关联               │
│  - ServiceCategory::packages()    套餐关联                   │
│  - 移除: parent(), children(), buildTree()                   │
│  - 移除: getCategoryTree()                                   │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                     数据库层 (Database)                      │
│  la_service_category 表                                      │
│  - id, name, icon, image, sort, is_show                      │
│  - 移除: pid, level, idx_pid, idx_level                      │
└─────────────────────────────────────────────────────────────┘
```

## 组件和接口

### 1. 数据库层

#### 1.1 表结构变更

**当前表结构:**
```sql
CREATE TABLE `la_service_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `pid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '上级分类ID',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `icon` varchar(255) DEFAULT '' COMMENT '分类图标',
  `image` varchar(255) DEFAULT '' COMMENT '分类图片',
  `level` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '分类层级',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示:0-否,1-是',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`) USING BTREE,
  KEY `idx_level` (`level`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务分类表';
```

**目标表结构:**
```sql
CREATE TABLE `la_service_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `icon` varchar(255) DEFAULT '' COMMENT '分类图标',
  `image` varchar(255) DEFAULT '' COMMENT '分类图片',
  `sort` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `is_show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示:0-否,1-是',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='服务分类表';
```

**迁移脚本:**
```sql
-- 文件: server/sql/wedding/017_simplify_service_category.sql

-- 1. 移除 level 字段
ALTER TABLE `la_service_category` DROP COLUMN `level`;

-- 2. 移除 pid 字段
ALTER TABLE `la_service_category` DROP COLUMN `pid`;

-- 3. 移除相关索引
ALTER TABLE `la_service_category` DROP INDEX `idx_pid`;
ALTER TABLE `la_service_category` DROP INDEX `idx_level`;
```

### 2. 模型层 (ServiceCategory.php)

#### 2.1 移除的方法

```php
// 移除父级关联
public function parent()
{
    return $this->belongsTo(ServiceCategory::class, 'pid', 'id');
}

// 移除子级关联
public function children()
{
    return $this->hasMany(ServiceCategory::class, 'pid', 'id');
}

// 移除树形构建方法
protected static function buildTree(array $list, int $pid = 0): array
{
    // ...
}

// 移除获取树形结构方法
public static function getCategoryTree()
{
    // ...
}
```

#### 2.2 保留的方法

```php
/**
 * @notes 获取该分类下的工作人员数量
 * @return \think\model\relation\HasMany
 */
public function staffs()
{
    return $this->hasMany(\app\common\model\staff\Staff::class, 'category_id', 'id');
}

/**
 * @notes 获取该分类下的套餐
 * @return \think\model\relation\HasMany
 */
public function packages()
{
    return $this->hasMany(ServicePackage::class, 'category_id', 'id');
}

/**
 * @notes 图标获取器
 */
public function getIconAttr($value)
{
    return trim($value) ? \app\common\service\FileService::getFileUrl($value) : '';
}

/**
 * @notes 图标设置器
 */
public function setIconAttr($value)
{
    return trim($value) ? \app\common\service\FileService::setFileUrl($value) : '';
}

/**
 * @notes 状态描述获取器
 */
public function getIsShowDescAttr($value, $data)
{
    return $data['is_show'] ? '显示' : '隐藏';
}
```

#### 2.3 新增的方法

```php
/**
 * @notes 获取所有分类(扁平列表)
 * @return array
 */
public static function getCategoryList(): array
{
    return self::where('delete_time', null)
        ->where('is_show', 1)
        ->order('sort desc, id asc')
        ->select()
        ->toArray();
}
```

### 3. 逻辑层 (CategoryLogic.php)

#### 3.1 简化的 add() 方法

**当前实现:**
```php
public static function add(array $params): bool
{
    try {
        // 检查同级是否存在同名分类
        $exists = ServiceCategory::where('pid', $params['pid'] ?? 0)
            ->where('name', $params['name'])
            ->where('delete_time', null)
            ->find();
        if ($exists) {
            throw new \Exception('同级分类下已存在相同名称的分类');
        }

        // 计算层级
        $level = 1;
        if (!empty($params['pid'])) {
            $parent = ServiceCategory::find($params['pid']);
            if ($parent) {
                $level = $parent->level + 1;
                if ($level > 3) {
                    throw new \Exception('分类最多支持3级');
                }
            }
        }

        ServiceCategory::create([
            'pid' => $params['pid'] ?? 0,
            'name' => $params['name'],
            'icon' => $params['icon'] ?? '',
            'level' => $level,
            'sort' => $params['sort'] ?? 0,
            'is_show' => $params['is_show'] ?? 1,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    } catch (\Exception $e) {
        self::setError($e->getMessage());
        return false;
    }
}
```

**目标实现:**
```php
public static function add(array $params): bool
{
    try {
        // 检查是否存在同名分类
        $exists = ServiceCategory::where('name', $params['name'])
            ->where('delete_time', null)
            ->find();
        if ($exists) {
            throw new \Exception('已存在相同名称的分类');
        }

        ServiceCategory::create([
            'name' => $params['name'],
            'icon' => $params['icon'] ?? '',
            'image' => $params['image'] ?? '',
            'sort' => $params['sort'] ?? 0,
            'is_show' => $params['is_show'] ?? 1,
            'create_time' => time(),
            'update_time' => time(),
        ]);

        return true;
    } catch (\Exception $e) {
        self::setError($e->getMessage());
        return false;
    }
}
```

#### 3.2 简化的 edit() 方法

**当前实现:**
```php
public static function edit(array $params): bool
{
    try {
        $category = ServiceCategory::find($params['id']);
        if (!$category) {
            throw new \Exception('分类不存在');
        }

        // 检查同级是否存在同名分类
        $exists = ServiceCategory::where('pid', $params['pid'] ?? $category->pid)
            ->where('name', $params['name'])
            ->where('id', '<>', $params['id'])
            ->where('delete_time', null)
            ->find();
        if ($exists) {
            throw new \Exception('同级分类下已存在相同名称的分类');
        }

        // 不能将分类设置为自己的子分类
        if (isset($params['pid']) && $params['pid'] == $params['id']) {
            throw new \Exception('不能将分类设置为自己的子分类');
        }

        // 检查是否将分类移动到其子分类下
        if (isset($params['pid']) && $params['pid'] != $category->pid) {
            $childIds = self::getChildIds($params['id']);
            if (in_array($params['pid'], $childIds)) {
                throw new \Exception('不能将分类移动到其子分类下');
            }
        }

        // 计算层级
        $level = 1;
        $newPid = $params['pid'] ?? $category->pid;
        if ($newPid > 0) {
            $parent = ServiceCategory::find($newPid);
            if ($parent) {
                $level = $parent->level + 1;
                if ($level > 3) {
                    throw new \Exception('分类最多支持3级');
                }
            }
        }

        $category->save([
            'pid' => $newPid,
            'name' => $params['name'],
            'icon' => $params['icon'] ?? $category->icon,
            'level' => $level,
            'sort' => $params['sort'] ?? $category->sort,
            'is_show' => $params['is_show'] ?? $category->is_show,
            'update_time' => time(),
        ]);

        return true;
    } catch (\Exception $e) {
        self::setError($e->getMessage());
        return false;
    }
}
```

**目标实现:**
```php
public static function edit(array $params): bool
{
    try {
        $category = ServiceCategory::find($params['id']);
        if (!$category) {
            throw new \Exception('分类不存在');
        }

        // 检查是否存在同名分类
        $exists = ServiceCategory::where('name', $params['name'])
            ->where('id', '<>', $params['id'])
            ->where('delete_time', null)
            ->find();
        if ($exists) {
            throw new \Exception('已存在相同名称的分类');
        }

        $category->save([
            'name' => $params['name'],
            'icon' => $params['icon'] ?? $category->icon,
            'image' => $params['image'] ?? $category->image,
            'sort' => $params['sort'] ?? $category->sort,
            'is_show' => $params['is_show'] ?? $category->is_show,
            'update_time' => time(),
        ]);

        return true;
    } catch (\Exception $e) {
        self::setError($e->getMessage());
        return false;
    }
}
```

#### 3.3 简化的 delete() 方法

**当前实现:**
```php
public static function delete(array $params): bool
{
    try {
        $category = ServiceCategory::find($params['id']);
        if (!$category) {
            throw new \Exception('分类不存在');
        }

        // 检查是否有子分类
        $childCount = ServiceCategory::where('pid', $params['id'])
            ->where('delete_time', null)
            ->count();
        if ($childCount > 0) {
            throw new \Exception('该分类下存在子分类，无法删除');
        }

        // 检查是否有关联的工作人员
        $staffCount = Staff::where('category_id', $params['id'])
            ->where('delete_time', null)
            ->count();
        if ($staffCount > 0) {
            throw new \Exception('该分类下存在工作人员，无法删除');
        }

        ServiceCategory::destroy($params['id']);
        return true;
    } catch (\Exception $e) {
        self::setError($e->getMessage());
        return false;
    }
}
```

**目标实现:**
```php
public static function delete(array $params): bool
{
    try {
        $category = ServiceCategory::find($params['id']);
        if (!$category) {
            throw new \Exception('分类不存在');
        }

        // 检查是否有关联的工作人员
        $staffCount = Staff::where('category_id', $params['id'])
            ->where('delete_time', null)
            ->count();
        if ($staffCount > 0) {
            throw new \Exception('该分类下存在工作人员，无法删除');
        }

        ServiceCategory::destroy($params['id']);
        return true;
    } catch (\Exception $e) {
        self::setError($e->getMessage());
        return false;
    }
}
```

#### 3.4 简化的 getAll() 方法

**当前实现:**
```php
public static function getAll(): array
{
    $list = ServiceCategory::where('delete_time', null)
        ->where('is_show', 1)
        ->order('sort desc, id asc')
        ->field('id, pid, name, level')
        ->select()
        ->toArray();

    return self::buildTree($list);
}
```

**目标实现:**
```php
public static function getAll(): array
{
    return ServiceCategory::where('delete_time', null)
        ->where('is_show', 1)
        ->order('sort desc, id asc')
        ->field('id, name, icon, sort, is_show')
        ->select()
        ->toArray();
}
```

#### 3.5 简化的 tree() 方法

**当前实现:**
```php
public static function tree(): array
{
    $list = ServiceCategory::where('delete_time', null)
        ->order('sort desc, id asc')
        ->field('id, pid, name, icon, is_show, sort, create_time')
        ->select()
        ->toArray();

    return self::buildTree($list);
}
```

**目标实现 (保持兼容性):**
```php
public static function tree(): array
{
    // 为保持向后兼容，返回扁平列表
    return ServiceCategory::where('delete_time', null)
        ->order('sort desc, id asc')
        ->field('id, name, icon, is_show, sort, create_time')
        ->select()
        ->toArray();
}
```

#### 3.6 移除的方法

```php
// 移除 buildTree() 方法
protected static function buildTree(array $list, int $pid = 0): array
{
    // ...
}

// 移除 getChildIds() 方法
protected static function getChildIds(int $id): array
{
    // ...
}
```

#### 3.7 简化的 detail() 方法

**当前实现:**
```php
public static function detail(int $id): array
{
    $category = ServiceCategory::with(['parent'])->find($id);
    if (!$category) {
        return [];
    }
    return $category->toArray();
}
```

**目标实现:**
```php
public static function detail(int $id): array
{
    $category = ServiceCategory::find($id);
    if (!$category) {
        return [];
    }
    return $category->toArray();
}
```

### 4. 验证器层 (CategoryValidate.php)

#### 4.1 简化的验证规则

**当前规则:**
```php
protected $rule = [
    'id' => 'require|checkCategory',
    'pid' => 'integer|egt:0|checkParent',
    'name' => 'require|max:50',
    'icon' => 'max:255',
    'sort' => 'integer|egt:0',
    'is_show' => 'in:0,1',
];
```

**目标规则:**
```php
protected $rule = [
    'id' => 'require|checkCategory',
    'name' => 'require|max:50',
    'icon' => 'max:255',
    'image' => 'max:255',
    'sort' => 'integer|egt:0',
    'is_show' => 'in:0,1',
];
```

#### 4.2 简化的验证场景

**当前场景:**
```php
protected $scene = [
    'add' => ['pid', 'name', 'icon', 'sort', 'is_show'],
    'edit' => ['id', 'pid', 'name', 'icon', 'sort', 'is_show'],
    'detail' => ['id'],
    'delete' => ['id'],
    'status' => ['id', 'is_show'],
];
```

**目标场景:**
```php
protected $scene = [
    'add' => ['name', 'icon', 'image', 'sort', 'is_show'],
    'edit' => ['id', 'name', 'icon', 'image', 'sort', 'is_show'],
    'detail' => ['id'],
    'delete' => ['id'],
    'status' => ['id', 'is_show'],
];
```

#### 4.3 移除的验证方法

```php
// 移除父级分类验证
protected function checkParent($value, $rule, $data)
{
    // ...
}
```

### 5. 控制器层 (CategoryController.php)

控制器层保持不变，所有接口保持向后兼容：

```php
public function lists()      // 分类列表 - 不变
public function tree()       // 树形结构 - 返回扁平列表
public function detail()     // 分类详情 - 不变
public function add()        // 添加分类 - 不变
public function edit()       // 编辑分类 - 不变
public function delete()     // 删除分类 - 不变
public function changeStatus() // 修改状态 - 不变
public function all()        // 所有分类 - 返回扁平列表
```

## 数据模型

### 服务分类 (ServiceCategory)

**简化后的字段:**

| 字段名 | 类型 | 说明 | 约束 |
|--------|------|------|------|
| id | int(11) unsigned | 分类ID | 主键、自增 |
| name | varchar(50) | 分类名称 | 非空 |
| icon | varchar(255) | 分类图标 | 可空 |
| image | varchar(255) | 分类图片 | 可空 |
| sort | int(11) unsigned | 排序 | 默认0 |
| is_show | tinyint(1) unsigned | 是否显示 | 0-否,1-是 |
| create_time | int(11) unsigned | 创建时间 | 默认0 |
| update_time | int(11) unsigned | 更新时间 | 默认0 |
| delete_time | int(11) | 删除时间 | 可空(软删除) |

**关联关系:**

- `staffs()`: 一对多关联到 Staff 模型
- `packages()`: 一对多关联到 ServicePackage 模型

## 正确性属性

*属性是一个特征或行为，应该在系统的所有有效执行中保持为真。属性作为人类可读规范和机器可验证正确性保证之间的桥梁。*


### 属性 1: 扁平列表结构

*对于任何* 分类查询操作（列表查询、树形查询、全部查询），返回的数据结构应当是扁平数组，不包含嵌套的 children 字段，且每个分类对象不包含 pid 和 level 字段。

**验证需求: 1.4, 2.4, 3.1, 5.1**

### 属性 2: 分类详情完整性

*对于任何* 有效的分类ID，查询分类详情应当返回包含所有必要字段（id、name、icon、image、sort、is_show、create_time、update_time）的完整对象。

**验证需求: 5.2**

### 属性 3: 分类名称唯一性

*对于任何* 分类添加或编辑操作，如果提交的分类名称已存在（不包括当前编辑的分类），系统应当拒绝操作并返回错误信息。

**验证需求: 3.7, 5.3, 5.4**

### 属性 4: CRUD操作幂等性

*对于任何* 有效的分类数据：
- 添加操作应当创建新记录并返回成功
- 编辑操作应当更新指定记录并返回成功
- 删除操作应当移除指定记录（软删除）并返回成功
- 状态切换操作应当更新 is_show 字段并返回成功

**验证需求: 3.7, 5.3, 5.4, 5.5, 5.6**

### 属性 5: 删除前置条件

*对于任何* 分类删除操作，如果该分类下存在关联的工作人员，系统应当拒绝删除并返回明确的错误信息。

**验证需求: 3.7, 5.5**

### 属性 6: 表单验证正确性

*对于任何* 分类表单提交：
- 如果 name 字段为空或超过50个字符，验证应当失败
- 如果 sort 字段不是非负整数，验证应当失败
- 如果 is_show 字段不是0或1，验证应当失败
- 如果所有必填字段都有效，验证应当通过

**验证需求: 4.3**

### 属性 7: API响应格式一致性

*对于任何* API接口调用，响应格式应当遵循标准结构：
```json
{
  "code": 1,
  "msg": "成功/失败信息",
  "data": {},
  "show": 1
}
```
且重构后的响应格式应当与重构前保持一致（除了移除 pid 和 level 字段）。

**验证需求: 5.7, 8.5**

### 属性 8: 数据迁移完整性

*对于任何* 数据库迁移操作，迁移前后的分类记录数量应当相等，且每条记录的核心数据（id、name、icon、image、sort、is_show）应当保持不变。

**验证需求: 1.5, 7.4, 8.4**

### 属性 9: 关联功能兼容性

*对于任何* 使用分类的关联功能（优惠券、工作人员、服务套餐），应当能够正确加载分类列表，正确保存分类关联，且关联数据在重构前后保持一致。

**验证需求: 6.1, 6.2, 6.3**

### 属性 10: 查询性能要求

*对于任何* 分类查询操作，响应时间应当不超过100ms；*对于任何* 分类修改操作（添加、编辑、删除、状态切换），操作时间应当不超过200ms。

**验证需求: 10.1, 10.2, 10.3, 10.4**

## 错误处理

### 1. 数据库迁移错误

**场景:** 执行数据库迁移脚本时发生错误

**处理策略:**
- 在迁移前创建完整的数据库备份
- 使用事务包装迁移操作，确保原子性
- 如果迁移失败，自动回滚到迁移前状态
- 记录详细的错误日志，包括失败的SQL语句和错误信息
- 提供回滚脚本用于手动恢复

**错误信息示例:**
```
数据库迁移失败: 无法删除字段 'level'
原因: 字段不存在或被其他约束引用
建议: 请检查数据库表结构，确认字段存在且无外键约束
```

### 2. 分类名称重复错误

**场景:** 添加或编辑分类时，名称与现有分类重复

**处理策略:**
- 在保存前检查名称唯一性
- 返回明确的错误信息
- 前端显示友好的提示信息

**错误信息:**
```json
{
  "code": 0,
  "msg": "已存在相同名称的分类",
  "data": {},
  "show": 1
}
```

### 3. 分类删除限制错误

**场景:** 删除分类时，该分类下存在关联的工作人员

**处理策略:**
- 在删除前检查关联数据
- 返回明确的错误信息，说明无法删除的原因
- 建议用户先处理关联数据

**错误信息:**
```json
{
  "code": 0,
  "msg": "该分类下存在工作人员，无法删除",
  "data": {},
  "show": 1
}
```

### 4. 分类不存在错误

**场景:** 查询、编辑或删除不存在的分类

**处理策略:**
- 在操作前验证分类是否存在
- 返回404或明确的错误信息

**错误信息:**
```json
{
  "code": 0,
  "msg": "分类不存在",
  "data": {},
  "show": 1
}
```

### 5. 验证失败错误

**场景:** 表单数据验证失败

**处理策略:**
- 使用验证器统一处理验证逻辑
- 返回具体的验证错误信息
- 前端显示字段级别的错误提示

**错误信息示例:**
```json
{
  "code": 0,
  "msg": "请输入分类名称",
  "data": {},
  "show": 1
}
```

### 6. 数据库连接错误

**场景:** 数据库连接失败或查询超时

**处理策略:**
- 捕获数据库异常
- 记录详细的错误日志
- 返回通用的错误信息，不暴露敏感信息

**错误信息:**
```json
{
  "code": 0,
  "msg": "系统繁忙，请稍后重试",
  "data": {},
  "show": 1
}
```

## 测试策略

### 1. 单元测试

**目标:** 验证各个组件的独立功能

**测试范围:**

#### 1.1 模型层测试
- 测试 `getCategoryList()` 方法返回扁平列表
- 测试图标获取器和设置器
- 测试状态描述获取器
- 测试关联关系（staffs、packages）

#### 1.2 逻辑层测试
- 测试 `add()` 方法：
  - 正常添加分类
  - 名称重复时拒绝添加
  - 验证创建的数据结构
- 测试 `edit()` 方法：
  - 正常编辑分类
  - 名称重复时拒绝编辑
  - 验证更新的数据
- 测试 `delete()` 方法：
  - 正常删除分类
  - 存在关联工作人员时拒绝删除
- 测试 `changeStatus()` 方法
- 测试 `getAll()` 方法返回扁平列表
- 测试 `tree()` 方法返回扁平列表
- 测试 `detail()` 方法

#### 1.3 验证器测试
- 测试必填字段验证
- 测试字段长度验证
- 测试字段类型验证
- 测试分类存在性验证

### 2. 属性测试

**目标:** 验证系统的通用正确性属性

**测试配置:**
- 使用 PHPUnit 作为测试框架
- 每个属性测试至少运行 100 次迭代
- 使用随机数据生成器创建测试数据

**属性测试列表:**

#### 属性测试 1: 扁平列表结构
```php
/**
 * Feature: service-category-refactor, Property 1: 扁平列表结构
 * 对于任何分类查询操作，返回的数据结构应当是扁平数组
 */
public function testFlatListStructure()
{
    // 生成随机数量的分类
    // 调用各种查询方法
    // 验证返回的数组是扁平的
    // 验证不包含 pid 和 level 字段
}
```

#### 属性测试 2: 分类详情完整性
```php
/**
 * Feature: service-category-refactor, Property 2: 分类详情完整性
 * 对于任何有效的分类ID，查询应当返回完整的字段
 */
public function testCategoryDetailCompleteness()
{
    // 创建随机分类
    // 查询详情
    // 验证所有必要字段都存在
}
```

#### 属性测试 3: 分类名称唯一性
```php
/**
 * Feature: service-category-refactor, Property 3: 分类名称唯一性
 * 对于任何分类操作，名称应当保持唯一
 */
public function testCategoryNameUniqueness()
{
    // 创建随机分类
    // 尝试创建同名分类
    // 验证操作被拒绝
}
```

#### 属性测试 4: CRUD操作幂等性
```php
/**
 * Feature: service-category-refactor, Property 4: CRUD操作幂等性
 * 对于任何有效的分类数据，CRUD操作应当正确执行
 */
public function testCRUDOperationsIdempotency()
{
    // 测试添加操作
    // 测试编辑操作
    // 测试删除操作
    // 测试状态切换操作
}
```

#### 属性测试 5: 删除前置条件
```php
/**
 * Feature: service-category-refactor, Property 5: 删除前置条件
 * 对于任何有关联数据的分类，删除应当被拒绝
 */
public function testDeletePrecondition()
{
    // 创建分类和关联的工作人员
    // 尝试删除分类
    // 验证删除被拒绝
}
```

#### 属性测试 6: 表单验证正确性
```php
/**
 * Feature: service-category-refactor, Property 6: 表单验证正确性
 * 对于任何表单提交，验证应当正确工作
 */
public function testFormValidationCorrectness()
{
    // 生成有效和无效的表单数据
    // 验证验证器的行为
}
```

#### 属性测试 7: API响应格式一致性
```php
/**
 * Feature: service-category-refactor, Property 7: API响应格式一致性
 * 对于任何API调用，响应格式应当一致
 */
public function testAPIResponseFormatConsistency()
{
    // 调用各种API接口
    // 验证响应格式
}
```

#### 属性测试 8: 数据迁移完整性
```php
/**
 * Feature: service-category-refactor, Property 8: 数据迁移完整性
 * 对于任何数据迁移，数据应当保持完整
 */
public function testDataMigrationIntegrity()
{
    // 创建测试数据
    // 执行迁移
    // 验证数据完整性
}
```

#### 属性测试 9: 关联功能兼容性
```php
/**
 * Feature: service-category-refactor, Property 9: 关联功能兼容性
 * 对于任何关联功能，应当正确工作
 */
public function testRelatedFunctionsCompatibility()
{
    // 测试优惠券关联
    // 测试工作人员关联
    // 测试服务套餐关联
}
```

#### 属性测试 10: 查询性能要求
```php
/**
 * Feature: service-category-refactor, Property 10: 查询性能要求
 * 对于任何操作，响应时间应当满足要求
 */
public function testQueryPerformanceRequirements()
{
    // 创建大量测试数据
    // 测试查询操作的响应时间
    // 测试修改操作的响应时间
}
```

### 3. 集成测试

**目标:** 验证各个模块之间的协作

**测试场景:**

#### 3.1 优惠券-分类集成
- 创建分类
- 创建指定分类的优惠券
- 验证优惠券能正确关联分类
- 验证优惠券列表能正确显示分类信息

#### 3.2 工作人员-分类集成
- 创建分类
- 创建关联分类的工作人员
- 验证工作人员能正确关联分类
- 验证工作人员列表能正确显示分类信息
- 验证有关联工作人员的分类无法删除

#### 3.3 服务套餐-分类集成
- 创建分类
- 创建关联分类的服务套餐
- 验证服务套餐能正确关联分类
- 验证服务套餐列表能正确显示分类信息

### 4. 数据迁移测试

**目标:** 验证数据库迁移的安全性和正确性

**测试步骤:**

1. **准备测试环境**
   - 创建测试数据库
   - 导入当前表结构
   - 插入测试数据（包含各种边界情况）

2. **执行迁移**
   - 备份数据
   - 执行迁移脚本
   - 记录迁移日志

3. **验证迁移结果**
   - 验证字段已被移除
   - 验证索引已被移除
   - 验证数据完整性（记录数量、核心字段值）
   - 验证数据一致性（id、name、icon等字段未改变）

4. **测试回滚**
   - 执行回滚脚本
   - 验证表结构恢复
   - 验证数据恢复

### 5. 回归测试

**目标:** 确保重构不影响现有功能

**测试范围:**
- 所有分类相关的API接口
- 所有使用分类的功能模块
- 前端页面的分类选择器
- 前端页面的分类展示

**测试方法:**
- 对比重构前后的API响应
- 对比重构前后的功能行为
- 使用自动化测试脚本进行回归测试

### 6. 性能测试

**目标:** 验证重构后的性能改进

**测试指标:**
- 查询响应时间
- 添加操作时间
- 编辑操作时间
- 删除操作时间
- 数据库查询次数
- 内存使用情况

**测试数据量:**
- 小数据量: 10条分类
- 中数据量: 100条分类
- 大数据量: 1000条分类

**性能基准:**
- 查询操作: < 100ms
- 修改操作: < 200ms
- 数据库查询次数: 减少30%以上（移除树形构建逻辑）

## 实施计划

### 阶段 1: 代码重构（优先级：高）

**目标:** 简化业务逻辑，移除树形结构处理

**任务:**
1. 修改 `CategoryLogic.php`
   - 简化 `add()` 方法
   - 简化 `edit()` 方法
   - 简化 `delete()` 方法
   - 简化 `getAll()` 方法
   - 简化 `tree()` 方法
   - 简化 `detail()` 方法
   - 移除 `buildTree()` 方法
   - 移除 `getChildIds()` 方法

2. 修改 `ServiceCategory.php`
   - 移除 `parent()` 方法
   - 移除 `children()` 方法
   - 移除 `buildTree()` 方法
   - 移除 `getCategoryTree()` 方法
   - 添加 `getCategoryList()` 方法

3. 修改 `CategoryValidate.php`
   - 移除 `pid` 字段验证规则
   - 移除 `level` 字段验证规则
   - 移除 `checkParent()` 方法
   - 更新验证场景

4. 测试代码修改
   - 运行单元测试
   - 运行集成测试
   - 修复发现的问题

**预计时间:** 4-6小时

### 阶段 2: 数据库迁移（优先级：中）

**目标:** 移除冗余字段，优化数据库结构

**任务:**
1. 创建迁移脚本
   - 编写 `017_simplify_service_category.sql`
   - 编写回滚脚本

2. 测试迁移脚本
   - 在测试环境执行迁移
   - 验证数据完整性
   - 测试回滚功能

3. 生产环境迁移
   - 备份生产数据库
   - 执行迁移脚本
   - 验证迁移结果
   - 监控系统运行

**预计时间:** 2-3小时

### 阶段 3: 前端优化（优先级：低）

**目标:** 优化前端交互，移除树形组件

**任务:**
1. 检查所有使用分类的页面
2. 将树形选择器改为普通下拉选择
3. 将树形表格改为普通表格
4. 更新相关文档

**预计时间:** 3-4小时

### 阶段 4: 文档更新（优先级：低）

**目标:** 更新相关文档

**任务:**
1. 更新 API 文档
2. 更新数据库设计文档
3. 更新开发规范文档
4. 更新重构设计文档状态

**预计时间:** 1-2小时

## 风险评估

### 1. 数据丢失风险

**风险等级:** 高

**描述:** 数据库迁移过程中可能导致数据丢失

**缓解措施:**
- 在迁移前创建完整的数据库备份
- 使用事务包装迁移操作
- 在测试环境充分测试
- 准备回滚脚本

### 2. 功能兼容性风险

**风险等级:** 中

**描述:** 重构可能影响现有功能

**缓解措施:**
- 保持API接口向后兼容
- 进行充分的回归测试
- 分阶段实施，逐步验证

### 3. 性能风险

**风险等级:** 低

**描述:** 重构可能影响系统性能

**缓解措施:**
- 进行性能测试
- 监控系统运行指标
- 如有必要，添加缓存机制

### 4. 业务需求变更风险

**风险等级:** 低

**描述:** 未来可能需要多级分类功能

**缓解措施:**
- 与产品团队确认业务需求
- 保持代码的可扩展性
- 如需恢复，可以重新添加字段

## 回滚方案

### 代码回滚

**步骤:**
1. 使用 Git 回退到重构前的提交
2. 恢复备份的代码文件
3. 重新部署应用

**命令:**
```bash
git revert <commit-hash>
git push origin main
```

### 数据库回滚

**步骤:**
1. 停止应用服务
2. 执行回滚脚本
3. 验证数据恢复
4. 重启应用服务

**回滚脚本:**
```sql
-- 恢复 pid 字段
ALTER TABLE `la_service_category` 
ADD COLUMN `pid` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '上级分类ID' AFTER `name`;

-- 恢复 level 字段
ALTER TABLE `la_service_category` 
ADD COLUMN `level` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '分类层级' AFTER `pid`;

-- 恢复索引
ALTER TABLE `la_service_category` ADD KEY `idx_pid` (`pid`) USING BTREE;
ALTER TABLE `la_service_category` ADD KEY `idx_level` (`level`) USING BTREE;

-- 恢复数据
UPDATE `la_service_category` SET `pid` = 0, `level` = 1;
```

## 监控和验证

### 1. 迁移后验证

**验证项:**
- [ ] 数据库表结构正确
- [ ] 数据记录数量一致
- [ ] 核心字段数据一致
- [ ] API接口正常响应
- [ ] 前端页面正常显示
- [ ] 关联功能正常工作

### 2. 性能监控

**监控指标:**
- API响应时间
- 数据库查询时间
- 错误率
- 系统负载

**监控工具:**
- 应用日志
- 数据库慢查询日志
- 性能监控平台

### 3. 错误监控

**监控内容:**
- 应用错误日志
- 数据库错误日志
- 用户反馈

**告警机制:**
- 错误率超过阈值时发送告警
- 性能下降时发送告警

## 总结

本设计文档详细描述了服务分类重构的技术方案，包括：

1. **架构设计**: 从树形结构简化为扁平结构
2. **组件设计**: 各层代码的具体修改方案
3. **数据模型**: 简化后的表结构
4. **正确性属性**: 10个可测试的系统属性
5. **错误处理**: 6种错误场景的处理策略
6. **测试策略**: 单元测试、属性测试、集成测试、数据迁移测试、回归测试、性能测试
7. **实施计划**: 4个阶段的详细任务
8. **风险评估**: 4类风险及缓解措施
9. **回滚方案**: 代码和数据库的回滚步骤
10. **监控验证**: 迁移后的验证和监控方案

重构的核心目标是简化系统，移除不必要的复杂性，同时保持系统的稳定性和向后兼容性。通过分阶段实施和充分的测试，可以确保重构的安全性和成功率。
