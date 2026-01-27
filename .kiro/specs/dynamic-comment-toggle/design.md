# 设计文档 - 动态评论开关功能

## 概述

本设计文档描述了为动态系统添加评论开关功能的技术实现方案。该功能允许管理员在后台发布或编辑动态时控制是否允许用户评论，默认情况下所有动态允许评论。

## 架构

### 系统层次结构

```
┌─────────────────────────────────────────────────────────┐
│                    前端层 (Frontend)                      │
├──────────────────────┬──────────────────────────────────┤
│   Admin Panel        │      Mobile Client (UniApp)      │
│   (Vue 3 + Element)  │      (Vue 3 + TuniaoUI)         │
└──────────────────────┴──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│                    API 层 (Controllers)                  │
├──────────────────────┬──────────────────────────────────┤
│  AdminAPI Controller │      API Controller              │
│  - add()            │      - commentAdd()               │
│  - edit()           │      - commentList()              │
│  - detail()         │      - detail()                   │
└──────────────────────┴──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│                  业务逻辑层 (Logic)                       │
├──────────────────────┬──────────────────────────────────┤
│  DynamicLogic        │      DynamicComment              │
│  - add()            │      - addComment()               │
│  - edit()           │      - getCommentList()           │
│  - detail()         │                                   │
└──────────────────────┴──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│                   数据模型层 (Model)                      │
│                  Dynamic Model                           │
│                  - allow_comment (新增字段)              │
└─────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────┐
│                   数据库层 (Database)                     │
│                  ls_dynamic 表                           │
│                  + allow_comment TINYINT(1) DEFAULT 1   │
└─────────────────────────────────────────────────────────┘
```

## 组件和接口

### 1. 数据库设计

#### 1.1 表结构变更

**表名**: `ls_dynamic`

**新增字段**:
```sql
ALTER TABLE `ls_dynamic` 
ADD COLUMN `allow_comment` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 
COMMENT '是否允许评论：0=禁止，1=允许' 
AFTER `tags`;
```

**字段说明**:
- `allow_comment`: TINYINT(1)，默认值 1
- 取值范围: 0（禁止评论）、1（允许评论）
- 位置: 在 `tags` 字段之后

#### 1.2 数据迁移

对于已存在的动态记录，默认设置 `allow_comment = 1`，保持向后兼容。

### 2. 后端模型层

#### 2.1 Dynamic 模型扩展

**文件**: `server/app/common/model/dynamic/Dynamic.php`

**修改内容**:

```php
class Dynamic extends BaseModel
{
    // ... 现有代码 ...

    /**
     * @notes 发布动态（更新）
     * @param int $userId
     * @param int $userType
     * @param array $data
     * @return array [bool $success, string $message, Dynamic|null $dynamic]
     */
    public static function publish(int $userId, int $userType, array $data): array
    {
        try {
            $dynamic = self::create([
                'user_id' => $userId,
                'user_type' => $userType,
                'staff_id' => $data['staff_id'] ?? 0,
                'dynamic_type' => $data['dynamic_type'] ?? self::TYPE_IMAGE_TEXT,
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'images' => $data['images'] ?? [],
                'video_url' => $data['video_url'] ?? '',
                'video_cover' => $data['video_cover'] ?? '',
                'location' => $data['location'] ?? '',
                'latitude' => $data['latitude'] ?? 0,
                'longitude' => $data['longitude'] ?? 0,
                'tags' => is_array($data['tags'] ?? '') ? implode(',', $data['tags']) : ($data['tags'] ?? ''),
                'allow_comment' => $data['allow_comment'] ?? 1, // 新增：默认允许评论
                'order_id' => $data['order_id'] ?? 0,
                'status' => self::STATUS_PENDING,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            return [true, '发布成功，等待审核', $dynamic];
        } catch (\Exception $e) {
            return [false, '发布失败：' . $e->getMessage(), null];
        }
    }

    /**
     * @notes 获取评论开关描述
     * @param $value
     * @param $data
     * @return string
     */
    public function getAllowCommentDescAttr($value, $data): string
    {
        return $data['allow_comment'] == 1 ? '允许' : '禁止';
    }
}
```

#### 2.2 DynamicComment 模型更新

**文件**: `server/app/common/model/dynamic/DynamicComment.php`

**修改内容**:

```php
/**
 * @notes 发表评论（更新）
 * @param int $dynamicId
 * @param int $userId
 * @param string $content
 * @param int $parentId
 * @param int $replyUserId
 * @param array $images
 * @return array [bool $success, string $message, DynamicComment|null $comment]
 */
public static function addComment(int $dynamicId, int $userId, string $content, int $parentId = 0, int $replyUserId = 0, array $images = []): array
{
    $dynamic = Dynamic::find($dynamicId);
    if (!$dynamic) {
        return [false, '动态不存在', null];
    }

    if ($dynamic->status != Dynamic::STATUS_PUBLISHED) {
        return [false, '该动态不可评论', null];
    }

    // 新增：检查评论开关
    if ($dynamic->allow_comment != 1) {
        return [false, '该动态不允许评论', null];
    }

    // ... 其余代码保持不变 ...
}
```

### 3. 后端业务逻辑层

#### 3.1 DynamicLogic 更新

**文件**: `server/app/adminapi/logic/dynamic/DynamicLogic.php`

**修改内容**:

```php
/**
 * @notes 管理员发布动态（更新）
 * @param int $adminId
 * @param array $params
 * @return bool
 */
public static function add(int $adminId, array $params): bool
{
    try {
        $data = [
            'user_id' => $adminId,
            'user_type' => Dynamic::USER_TYPE_OFFICIAL,
            'dynamic_type' => $params['dynamic_type'] ?? Dynamic::TYPE_IMAGE_TEXT,
            'title' => $params['title'] ?? '',
            'content' => $params['content'],
            'images' => $params['images'] ?? [],
            'video_url' => $params['video'] ?? '',
            'video_cover' => $params['video_cover'] ?? '',
            'location' => $params['location'] ?? '',
            'latitude' => $params['latitude'] ?? 0,
            'longitude' => $params['longitude'] ?? 0,
            'tags' => is_array($params['tags'] ?? '') ? implode(',', $params['tags']) : ($params['tags'] ?? ''),
            'allow_comment' => $params['allow_comment'] ?? 1, // 新增
            'status' => Dynamic::STATUS_PUBLISHED,
            'create_time' => time(),
            'update_time' => time(),
        ];

        Dynamic::create($data);
        return true;
    } catch (\Exception $e) {
        self::setError($e->getMessage());
        return false;
    }
}

/**
 * @notes 管理员编辑动态（更新）
 * @param int $dynamicId
 * @param array $params
 * @return bool
 */
public static function edit(int $dynamicId, array $params): bool
{
    try {
        $dynamic = Dynamic::find($dynamicId);
        if (!$dynamic) {
            self::setError('动态不存在');
            return false;
        }

        $dynamic->dynamic_type = $params['dynamic_type'] ?? $dynamic->dynamic_type;
        $dynamic->title = $params['title'] ?? $dynamic->title;
        $dynamic->content = $params['content'] ?? $dynamic->content;
        $dynamic->images = $params['images'] ?? $dynamic->images;
        $dynamic->video_url = $params['video'] ?? $dynamic->video_url;
        $dynamic->video_cover = $params['video_cover'] ?? $dynamic->video_cover;
        $dynamic->location = $params['location'] ?? $dynamic->location;
        $dynamic->latitude = $params['latitude'] ?? $dynamic->latitude;
        $dynamic->longitude = $params['longitude'] ?? $dynamic->longitude;
        $dynamic->tags = is_array($params['tags'] ?? '') ? implode(',', $params['tags']) : ($params['tags'] ?? $dynamic->tags);
        $dynamic->allow_comment = $params['allow_comment'] ?? $dynamic->allow_comment; // 新增
        $dynamic->update_time = time();
        $dynamic->save();

        return true;
    } catch (\Exception $e) {
        self::setError($e->getMessage());
        return false;
    }
}
```

### 4. 后端验证器

#### 4.1 DynamicValidate 更新

**文件**: `server/app/adminapi/validate/dynamic/DynamicValidate.php`

**修改内容**:

```php
protected $rule = [
    'id' => 'require|integer',
    'dynamic_type' => 'require|integer|in:1,2,3,4',
    'content' => 'require',
    'images' => 'array',
    'video' => 'string',
    'video_cover' => 'string',
    'title' => 'string|max:100',
    'location' => 'string|max:100',
    'tags' => 'array',
    'allow_comment' => 'integer|in:0,1', // 新增
    // ... 其他规则 ...
];

protected $message = [
    'id.require' => '请选择动态',
    'id.integer' => '动态ID格式错误',
    'dynamic_type.require' => '请选择动态类型',
    'dynamic_type.in' => '动态类型参数错误',
    'content.require' => '请输入动态内容',
    'allow_comment.integer' => '评论开关格式错误', // 新增
    'allow_comment.in' => '评论开关参数错误', // 新增
    // ... 其他消息 ...
];
```

### 5. 前端 - 后台管理界面

#### 5.1 动态编辑表单

**文件**: `admin/src/views/dynamic/lists/edit.vue`

**修改内容**:

```vue
<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            :title="popupTitle"
            :async="true"
            width="800px"
            @confirm="handleSubmit"
            @close="handleClose"
        >
            <el-form ref="formRef" :model="formData" :rules="formRules" label-width="100px">
                <!-- ... 现有表单项 ... -->

                <!-- 新增：评论开关 -->
                <el-form-item label="允许评论" prop="allow_comment">
                    <el-switch
                        v-model="formData.allow_comment"
                        :active-value="1"
                        :inactive-value="0"
                        active-text="允许"
                        inactive-text="禁止"
                    />
                    <div class="form-tips">关闭后用户将无法对该动态发表新评论</div>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>

<script lang="ts" setup name="dynamicEdit">
// ... 现有代码 ...

const formData = reactive({
    id: 0,
    dynamic_type: 1,
    title: '',
    content: '',
    images: [] as string[],
    video: '',
    video_cover: '',
    location: '',
    tags: [] as string[],
    allow_comment: 1 // 新增：默认允许评论
})

// ... 其余代码保持不变 ...
</script>
```

#### 5.2 动态列表显示

**文件**: `admin/src/views/dynamic/lists/index.vue`

**修改内容**:

```vue
<template>
    <!-- ... 现有代码 ... -->
    <el-table-column label="评论状态" prop="allow_comment" min-width="100">
        <template #default="{ row }">
            <el-tag :type="row.allow_comment === 1 ? 'success' : 'danger'" size="small">
                {{ row.allow_comment === 1 ? '允许评论' : '禁止评论' }}
            </el-tag>
        </template>
    </el-table-column>
    <!-- ... 现有代码 ... -->
</template>
```

### 6. 前端 - 移动端界面

#### 6.1 动态详情页面

**文件**: `uniapp/src/pages/dynamic_detail/dynamic_detail.vue`

**修改内容**:

```vue
<template>
    <view class="dynamic-detail-page">
        <!-- ... 现有内容 ... -->

        <!-- 评论区域 -->
        <view class="comment-section">
            <view class="comment-header">
                <text class="comment-title">评论 {{ detail.comment_count || 0 }}</text>
            </view>

            <!-- 评论列表 -->
            <view v-if="comments.length > 0" class="comment-list">
                <!-- ... 评论列表内容 ... -->
            </view>

            <!-- 评论输入框 - 根据 allow_comment 控制显示 -->
            <view v-if="detail.allow_comment === 1" class="comment-input-wrapper">
                <view class="comment-input-box">
                    <input
                        v-model="commentContent"
                        class="comment-input"
                        placeholder="说点什么..."
                        @focus="handleCommentFocus"
                    />
                    <view class="comment-send-btn" @click="handleSendComment">
                        <text>发送</text>
                    </view>
                </view>
            </view>

            <!-- 评论已关闭提示 -->
            <view v-else class="comment-disabled-tip">
                <tn-icon name="info-circle" size="32" color="#999" />
                <text class="tip-text">该动态已关闭评论</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
// ... 现有代码 ...

const handleCommentFocus = () => {
    // 检查评论开关
    if (detail.value.allow_comment !== 1) {
        uni.showToast({
            title: '该动态不允许评论',
            icon: 'none'
        })
        return
    }
    
    // 检查登录状态
    if (!userStore.isLogin) {
        uni.navigateTo({ url: '/pages/login/login' })
        return
    }
}

const handleSendComment = async () => {
    // 检查评论开关
    if (detail.value.allow_comment !== 1) {
        uni.showToast({
            title: '该动态不允许评论',
            icon: 'none'
        })
        return
    }

    // ... 其余评论发送逻辑 ...
}
</script>

<style lang="scss" scoped>
/* 评论已关闭提示样式 */
.comment-disabled-tip {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48rpx 32rpx;
    background: #f5f5f5;
    border-radius: 16rpx;
    margin: 24rpx 0;
}

.tip-text {
    margin-left: 16rpx;
    font-size: 28rpx;
    color: #999999;
}
</style>
```

## 数据模型

### Dynamic 实体

```typescript
interface Dynamic {
    id: number
    user_id: number
    user_type: number  // 1=用户, 2=工作人员, 3=官方
    staff_id: number
    dynamic_type: number  // 1=图文, 2=视频, 3=案例, 4=活动
    title: string
    content: string
    images: string[]
    video_url: string
    video_cover: string
    location: string
    latitude: number
    longitude: number
    tags: string
    allow_comment: number  // 新增：0=禁止, 1=允许
    view_count: number
    like_count: number
    comment_count: number
    collect_count: number
    is_top: number
    is_hot: number
    status: number  // 0=待审核, 1=已发布, 2=已下架, 3=已拒绝
    audit_admin_id: number
    audit_time: number
    audit_remark: string
    create_time: number
    update_time: number
    delete_time: number
}
```

## 正确性属性

*属性是一个特征或行为，应该在系统的所有有效执行中保持为真——本质上是关于系统应该做什么的正式声明。属性作为人类可读规范和机器可验证正确性保证之间的桥梁。*

### 属性 1: 默认值一致性

*对于任何* 新创建的动态，如果未明确指定 `allow_comment` 值，则该字段应该默认为 1（允许评论）

**验证: 需求 1.3, 2.2, 8.2**

### 属性 2: 评论权限强制执行

*对于任何* 动态，当 `allow_comment = 0` 时，任何用户尝试发表新评论都应该被拒绝并返回错误消息"该动态不允许评论"

**验证: 需求 5.1, 5.2**

### 属性 3: 已有评论保留

*对于任何* 动态，当 `allow_comment` 从 1 改为 0 时，该动态的 `comment_count` 值应该保持不变，且已有评论应该继续可见

**验证: 需求 7.1, 7.2, 7.3**

### 属性 4: 表单状态同步

*对于任何* 动态编辑操作，表单中显示的 `allow_comment` 开关状态应该与数据库中该动态的实际 `allow_comment` 值一致

**验证: 需求 2.5**

### 属性 5: API 数据完整性

*对于任何* 动态详情或列表 API 响应，返回的数据中应该包含 `allow_comment` 字段

**验证: 需求 4.3, 4.4**

### 属性 6: 移动端 UI 状态一致性

*对于任何* 动态详情页面，当 `allow_comment = 0` 时，评论输入框应该被隐藏或禁用，且应该显示"该动态已关闭评论"提示

**验证: 需求 6.1, 6.2**

### 属性 7: 数据库迁移幂等性

*对于任何* 已存在的动态记录，执行数据库迁移后，其 `allow_comment` 字段应该被设置为 1

**验证: 需求 1.4**

## 错误处理

### 1. 数据库层错误

| 错误场景 | 处理方式 | 返回信息 |
|---------|---------|---------|
| 字段添加失败 | 回滚迁移，记录错误日志 | "数据库迁移失败" |
| 字段类型不匹配 | 验证失败，拒绝保存 | "数据格式错误" |

### 2. 业务逻辑层错误

| 错误场景 | 处理方式 | 返回信息 |
|---------|---------|---------|
| 动态不存在 | 返回 false，设置错误消息 | "动态不存在" |
| 评论被禁止 | 拒绝评论请求 | "该动态不允许评论" |
| 参数验证失败 | 返回验证错误 | "评论开关参数错误" |

### 3. 前端界面错误

| 错误场景 | 处理方式 | 用户提示 |
|---------|---------|---------|
| API 请求失败 | 显示 Toast 提示 | "操作失败，请重试" |
| 评论被拒绝 | 显示 Toast 提示 | "该动态不允许评论" |
| 网络超时 | 显示错误提示 | "网络连接超时" |

### 4. 边界情况处理

- **空值处理**: `allow_comment` 为 null 时，默认视为 1（允许评论）
- **非法值处理**: `allow_comment` 不为 0 或 1 时，默认视为 1
- **并发处理**: 使用数据库事务确保评论开关状态变更的原子性

## 测试策略

### 单元测试

**后端模型测试**:
- 测试 `allow_comment` 字段的默认值设置
- 测试 `allow_comment` 字段的获取器和设置器
- 测试评论权限检查逻辑

**后端逻辑测试**:
- 测试动态添加时 `allow_comment` 参数处理
- 测试动态编辑时 `allow_comment` 参数更新
- 测试评论发表时的权限验证

**前端组件测试**:
- 测试表单开关组件的状态切换
- 测试表单提交时 `allow_comment` 值的传递
- 测试移动端评论输入框的显示/隐藏逻辑

### 集成测试

**API 集成测试**:
- 测试完整的动态发布流程（包含 `allow_comment` 字段）
- 测试完整的动态编辑流程（包含 `allow_comment` 字段）
- 测试评论发表被拒绝的场景

**端到端测试**:
- 测试管理员在后台设置评论开关
- 测试用户在移动端看到评论状态提示
- 测试用户尝试评论被禁止的动态

### 属性测试配置

- **测试框架**: PHPUnit (后端), Vitest (前端)
- **最小迭代次数**: 100 次
- **测试标签格式**: `Feature: dynamic-comment-toggle, Property {number}: {property_text}`

### 测试用例示例

**测试用例 1: 默认值测试**
```php
// Feature: dynamic-comment-toggle, Property 1: 默认值一致性
public function testDefaultAllowCommentValue()
{
    $dynamic = Dynamic::create([
        'user_id' => 1,
        'user_type' => Dynamic::USER_TYPE_OFFICIAL,
        'content' => '测试动态内容',
        // 不设置 allow_comment
    ]);
    
    $this->assertEquals(1, $dynamic->allow_comment);
}
```

**测试用例 2: 评论权限测试**
```php
// Feature: dynamic-comment-toggle, Property 2: 评论权限强制执行
public function testCommentPermissionEnforcement()
{
    $dynamic = Dynamic::create([
        'user_id' => 1,
        'content' => '测试动态',
        'allow_comment' => 0,
        'status' => Dynamic::STATUS_PUBLISHED,
    ]);
    
    [$success, $message] = DynamicComment::addComment(
        $dynamic->id,
        2,
        '测试评论'
    );
    
    $this->assertFalse($success);
    $this->assertEquals('该动态不允许评论', $message);
}
```

**测试用例 3: 已有评论保留测试**
```php
// Feature: dynamic-comment-toggle, Property 3: 已有评论保留
public function testExistingCommentsPreserved()
{
    $dynamic = Dynamic::create([
        'user_id' => 1,
        'content' => '测试动态',
        'allow_comment' => 1,
        'comment_count' => 5,
        'status' => Dynamic::STATUS_PUBLISHED,
    ]);
    
    $originalCount = $dynamic->comment_count;
    
    $dynamic->allow_comment = 0;
    $dynamic->save();
    
    $this->assertEquals($originalCount, $dynamic->comment_count);
}
```
