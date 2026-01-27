# 设计文档 - 动态评论审核功能

## 概述

本设计文档描述了为动态评论系统添加审核功能的技术实现方案。该功能允许管理员通过后台配置控制是否启用评论审核，并提供专门的审核管理界面来审核或拒绝用户发表的评论。当审核功能开启时，用户发表的评论需要经过管理员审核通过后才能公开显示。

## 架构

### 系统层次结构

```
┌─────────────────────────────────────────────────────────────────┐
│                      前端层 (Frontend)                           │
├──────────────────────┬──────────────────────────────────────────┤
│   Admin Panel        │      Mobile Client (UniApp)              │
│   - 配置页面         │      - 评论发表                          │
│   - 审核列表         │      - 我的评论                          │
│   - 审核操作         │      - 审核状态显示                      │
└──────────────────────┴──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                    API 层 (Controllers)                          │
├──────────────────────┬──────────────────────────────────────────┤
│  AdminAPI            │      API                                 │
│  - reviewList()      │      - commentAdd()                      │
│  - approve()         │      - commentList()                     │
│  - reject()          │      - myComments()                      │
│  - batchApprove()    │                                          │
│  - batchReject()     │                                          │
└──────────────────────┴──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                  业务逻辑层 (Logic)                              │
├──────────────────────┬──────────────────────────────────────────┤
│  DynamicCommentLogic │      ConfigService                       │
│  - getReviewList()   │      - get('dynamic', 'comment_review')  │
│  - approve()         │      - set('dynamic', 'comment_review')  │
│  - reject()          │                                          │
│  - batchApprove()    │                                          │
│  - batchReject()     │                                          │
└──────────────────────┴──────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                   数据模型层 (Model)                             │
│                  DynamicComment Model                            │
│                  - review_status (新增字段)                      │
│                  - review_admin_id (新增字段)                    │
│                  - review_time (新增字段)                        │
│                  - review_remark (新增字段)                      │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                   数据库层 (Database)                            │
│                  ls_dynamic_comment 表                           │
│                  + review_status TINYINT(1) DEFAULT 0           │
│                  + review_admin_id INT DEFAULT 0                │
│                  + review_time INT DEFAULT 0                    │
│                  + review_remark VARCHAR(255) DEFAULT ''        │
└─────────────────────────────────────────────────────────────────┘
```


## 组件和接口

### 1. 数据库设计

#### 1.1 表结构变更

**表名**: `ls_dynamic_comment`

**新增字段**:
```sql
ALTER TABLE `ls_dynamic_comment` 
ADD COLUMN `review_status` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核状态：0=待审核，1=已通过，2=已拒绝' 
AFTER `status`;

ALTER TABLE `ls_dynamic_comment` 
ADD COLUMN `review_admin_id` INT(11) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核管理员ID' 
AFTER `review_status`;

ALTER TABLE `ls_dynamic_comment` 
ADD COLUMN `review_time` INT(11) UNSIGNED NOT NULL DEFAULT 0 
COMMENT '审核时间' 
AFTER `review_admin_id`;

ALTER TABLE `ls_dynamic_comment` 
ADD COLUMN `review_remark` VARCHAR(255) NOT NULL DEFAULT '' 
COMMENT '审核备注（拒绝原因）' 
AFTER `review_time`;
```

**字段说明**:
- `review_status`: TINYINT(1)，默认值 0
  - 0: 待审核
  - 1: 已通过
  - 2: 已拒绝
- `review_admin_id`: INT(11)，默认值 0，存储审核管理员 ID
- `review_time`: INT(11)，默认值 0，存储审核时间戳
- `review_remark`: VARCHAR(255)，默认值空字符串，存储审核备注（拒绝原因）

#### 1.2 数据迁移

对于已存在的评论记录，默认设置 `review_status = 1`（已通过），保持向后兼容。


### 2. 系统配置设计

#### 2.1 配置项定义

**配置分组**: `dynamic`
**配置键名**: `comment_review_enabled`
**配置值**: 0（关闭审核）或 1（开启审核）
**默认值**: 0（关闭审核）

**配置存储位置**: `ls_dev_config` 表

**配置项结构**:
```php
[
    'type' => 'switch',
    'key' => 'comment_review_enabled',
    'value' => 0,
    'name' => '评论审核',
    'tips' => '开启后，用户发表的评论需要管理员审核通过后才能显示',
    'group' => 'dynamic',
]
```

#### 2.2 配置读取方式

```php
// 获取评论审核配置
$reviewEnabled = ConfigService::get('dynamic', 'comment_review_enabled', 0);

// 设置评论审核配置
ConfigService::set('dynamic', 'comment_review_enabled', 1);
```


### 3. 后端模型层

#### 3.1 DynamicComment 模型扩展

**文件**: `server/app/common/model/dynamic/DynamicComment.php`

**新增常量**:
```php
// 审核状态
const REVIEW_STATUS_PENDING = 0;   // 待审核
const REVIEW_STATUS_APPROVED = 1;  // 已通过
const REVIEW_STATUS_REJECTED = 2;  // 已拒绝
```

**修改 addComment() 方法**:
```php
public static function addComment(int $dynamicId, int $userId, string $content, int $parentId = 0, int $replyUserId = 0, array $images = []): array
{
    // ... 现有验证逻辑 ...

    // 获取审核配置
    $reviewEnabled = ConfigService::get('dynamic', 'comment_review_enabled', 0);
    $reviewStatus = $reviewEnabled ? self::REVIEW_STATUS_PENDING : self::REVIEW_STATUS_APPROVED;

    try {
        $comment = self::create([
            'dynamic_id' => $dynamicId,
            'user_id' => $userId,
            'parent_id' => $parentId,
            'reply_user_id' => $replyUserId,
            'content' => $content,
            'images' => json_encode($images, JSON_UNESCAPED_UNICODE),
            'status' => self::STATUS_NORMAL,
            'review_status' => $reviewStatus, // 根据配置设置审核状态
            'ip' => request()->ip(),
            'create_time' => time(),
            'update_time' => time(),
        ]);

        // 只有审核通过的评论才更新计数
        if ($reviewStatus == self::REVIEW_STATUS_APPROVED) {
            Dynamic::where('id', $dynamicId)->inc('comment_count')->update();
            if ($parentId > 0) {
                self::where('id', $parentId)->inc('reply_count')->update();
            }
        }

        $message = $reviewEnabled ? '评论成功，等待审核' : '评论成功';
        return [true, $message, $comment];
    } catch (\Exception $e) {
        return [false, '评论失败：' . $e->getMessage(), null];
    }
}
```

**新增审核方法**:
```php
/**
 * @notes 审核通过评论
 * @param int $commentId
 * @param int $adminId
 * @return array [bool $success, string $message]
 */
public static function approveComment(int $commentId, int $adminId): array
{
    $comment = self::find($commentId);
    if (!$comment) {
        return [false, '评论不存在'];
    }

    if ($comment->review_status != self::REVIEW_STATUS_PENDING) {
        return [false, '该评论已审核'];
    }

    $comment->review_status = self::REVIEW_STATUS_APPROVED;
    $comment->review_admin_id = $adminId;
    $comment->review_time = time();
    $comment->save();

    // 更新动态评论数
    Dynamic::where('id', $comment->dynamic_id)->inc('comment_count')->update();

    // 更新父评论回复数
    if ($comment->parent_id > 0) {
        self::where('id', $comment->parent_id)->inc('reply_count')->update();
    }

    // TODO: 发送通知给用户

    return [true, '审核通过'];
}

/**
 * @notes 拒绝评论
 * @param int $commentId
 * @param int $adminId
 * @param string $remark
 * @return array [bool $success, string $message]
 */
public static function rejectComment(int $commentId, int $adminId, string $remark = ''): array
{
    $comment = self::find($commentId);
    if (!$comment) {
        return [false, '评论不存在'];
    }

    if ($comment->review_status != self::REVIEW_STATUS_PENDING) {
        return [false, '该评论已审核'];
    }

    $comment->review_status = self::REVIEW_STATUS_REJECTED;
    $comment->review_admin_id = $adminId;
    $comment->review_time = time();
    $comment->review_remark = $remark;
    $comment->save();

    // TODO: 发送通知给用户

    return [true, '已拒绝'];
}
```

**新增获取器方法**:
```php
/**
 * @notes 审核状态描述获取器
 * @param $value
 * @param $data
 * @return string
 */
public function getReviewStatusDescAttr($value, $data): string
{
    $map = [
        self::REVIEW_STATUS_PENDING => '待审核',
        self::REVIEW_STATUS_APPROVED => '已通过',
        self::REVIEW_STATUS_REJECTED => '已拒绝',
    ];
    return $map[$data['review_status']] ?? '未知';
}
```

**修改 getCommentList() 方法**:
```php
public static function getCommentList(int $dynamicId, int $userId = 0, array $params = []): array
{
    $query = self::where('dynamic_id', $dynamicId)
        ->where('parent_id', 0)
        ->where('status', self::STATUS_NORMAL)
        ->where('review_status', self::REVIEW_STATUS_APPROVED); // 只显示已通过审核的评论

    // ... 其余逻辑保持不变 ...
}
```


### 4. 后端业务逻辑层

#### 4.1 DynamicCommentLogic 新增

**文件**: `server/app/adminapi/logic/dynamic/DynamicCommentLogic.php`

```php
<?php
declare(strict_types=1);

namespace app\adminapi\logic\dynamic;

use app\common\logic\BaseLogic;
use app\common\model\dynamic\DynamicComment;
use app\common\service\ConfigService;

/**
 * 动态评论审核逻辑
 */
class DynamicCommentLogic extends BaseLogic
{
    /**
     * @notes 获取待审核评论列表
     * @param array $params
     * @return array
     */
    public static function getReviewList(array $params): array
    {
        $query = DynamicComment::with(['user', 'dynamic'])
            ->where('review_status', DynamicComment::REVIEW_STATUS_PENDING)
            ->order('create_time', 'desc');

        $list = $query->paginate([
            'list_rows' => $params['limit'] ?? 20,
            'page' => $params['page'] ?? 1,
        ])->toArray();

        return $list;
    }

    /**
     * @notes 审核通过评论
     * @param int $commentId
     * @param int $adminId
     * @return bool
     */
    public static function approve(int $commentId, int $adminId): bool
    {
        [$success, $message] = DynamicComment::approveComment($commentId, $adminId);
        if (!$success) {
            self::setError($message);
        }
        return $success;
    }

    /**
     * @notes 拒绝评论
     * @param int $commentId
     * @param int $adminId
     * @param string $remark
     * @return bool
     */
    public static function reject(int $commentId, int $adminId, string $remark): bool
    {
        [$success, $message] = DynamicComment::rejectComment($commentId, $adminId, $remark);
        if (!$success) {
            self::setError($message);
        }
        return $success;
    }

    /**
     * @notes 批量审核通过
     * @param array $commentIds
     * @param int $adminId
     * @return array
     */
    public static function batchApprove(array $commentIds, int $adminId): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($commentIds as $commentId) {
            [$success] = DynamicComment::approveComment($commentId, $adminId);
            if ($success) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
    }

    /**
     * @notes 批量拒绝
     * @param array $commentIds
     * @param int $adminId
     * @param string $remark
     * @return array
     */
    public static function batchReject(array $commentIds, int $adminId, string $remark): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($commentIds as $commentId) {
            [$success] = DynamicComment::rejectComment($commentId, $adminId, $remark);
            if ($success) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
    }

    /**
     * @notes 获取评论审核配置
     * @return int
     */
    public static function getReviewConfig(): int
    {
        return (int)ConfigService::get('dynamic', 'comment_review_enabled', 0);
    }

    /**
     * @notes 设置评论审核配置
     * @param int $enabled
     * @return bool
     */
    public static function setReviewConfig(int $enabled): bool
    {
        try {
            ConfigService::set('dynamic', 'comment_review_enabled', $enabled);
            return true;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return false;
        }
    }
}
```


### 5. 后端控制器层

#### 5.1 DynamicCommentController 新增

**文件**: `server/app/adminapi/controller/dynamic/DynamicCommentController.php`

```php
<?php
declare(strict_types=1);

namespace app\adminapi\controller\dynamic;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\logic\dynamic\DynamicCommentLogic;
use app\adminapi\validate\dynamic\DynamicCommentValidate;

/**
 * 动态评论审核控制器
 */
class DynamicCommentController extends BaseAdminController
{
    /**
     * @notes 获取待审核评论列表
     * @return \think\response\Json
     */
    public function reviewList()
    {
        $params = $this->request->param();
        $list = DynamicCommentLogic::getReviewList($params);
        return $this->data($list);
    }

    /**
     * @notes 审核通过评论
     * @return \think\response\Json
     */
    public function approve()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('approve');
        $result = DynamicCommentLogic::approve($params['id'], $this->adminId);
        if ($result) {
            return $this->success('审核通过');
        }
        return $this->fail(DynamicCommentLogic::getError());
    }

    /**
     * @notes 拒绝评论
     * @return \think\response\Json
     */
    public function reject()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('reject');
        $result = DynamicCommentLogic::reject($params['id'], $this->adminId, $params['remark'] ?? '');
        if ($result) {
            return $this->success('已拒绝');
        }
        return $this->fail(DynamicCommentLogic::getError());
    }

    /**
     * @notes 批量审核通过
     * @return \think\response\Json
     */
    public function batchApprove()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('batchApprove');
        $result = DynamicCommentLogic::batchApprove($params['ids'], $this->adminId);
        return $this->success('批量审核完成', $result);
    }

    /**
     * @notes 批量拒绝
     * @return \think\response\Json
     */
    public function batchReject()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('batchReject');
        $result = DynamicCommentLogic::batchReject($params['ids'], $this->adminId, $params['remark'] ?? '');
        return $this->success('批量拒绝完成', $result);
    }

    /**
     * @notes 获取评论审核配置
     * @return \think\response\Json
     */
    public function getReviewConfig()
    {
        $config = DynamicCommentLogic::getReviewConfig();
        return $this->data(['enabled' => $config]);
    }

    /**
     * @notes 设置评论审核配置
     * @return \think\response\Json
     */
    public function setReviewConfig()
    {
        $params = (new DynamicCommentValidate())->post()->goCheck('setConfig');
        $result = DynamicCommentLogic::setReviewConfig($params['enabled']);
        if ($result) {
            return $this->success('设置成功');
        }
        return $this->fail(DynamicCommentLogic::getError());
    }
}
```


### 6. 后端验证器

#### 6.1 DynamicCommentValidate 新增

**文件**: `server/app/adminapi/validate/dynamic/DynamicCommentValidate.php`

```php
<?php
declare(strict_types=1);

namespace app\adminapi\validate\dynamic;

use app\common\validate\BaseValidate;

/**
 * 动态评论审核验证器
 */
class DynamicCommentValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|integer',
        'ids' => 'require|array',
        'remark' => 'max:255',
        'enabled' => 'require|integer|in:0,1',
    ];

    protected $message = [
        'id.require' => '请选择评论',
        'id.integer' => '评论ID格式错误',
        'ids.require' => '请选择评论',
        'ids.array' => '评论ID格式错误',
        'remark.max' => '备注不能超过255个字符',
        'enabled.require' => '请选择配置值',
        'enabled.integer' => '配置值格式错误',
        'enabled.in' => '配置值参数错误',
    ];

    /**
     * @notes 审核通过场景
     * @return DynamicCommentValidate
     */
    public function sceneApprove()
    {
        return $this->only(['id']);
    }

    /**
     * @notes 拒绝场景
     * @return DynamicCommentValidate
     */
    public function sceneReject()
    {
        return $this->only(['id', 'remark']);
    }

    /**
     * @notes 批量审核通过场景
     * @return DynamicCommentValidate
     */
    public function sceneBatchApprove()
    {
        return $this->only(['ids']);
    }

    /**
     * @notes 批量拒绝场景
     * @return DynamicCommentValidate
     */
    public function sceneBatchReject()
    {
        return $this->only(['ids', 'remark']);
    }

    /**
     * @notes 设置配置场景
     * @return DynamicCommentValidate
     */
    public function sceneSetConfig()
    {
        return $this->only(['enabled']);
    }
}
```


### 7. 路由配置

**文件**: `server/app/adminapi/config/route.php`

```php
// 动态评论审核路由
Route::group('dynamic/comment', function () {
    Route::get('reviewList', 'dynamic.DynamicComment/reviewList');
    Route::post('approve', 'dynamic.DynamicComment/approve');
    Route::post('reject', 'dynamic.DynamicComment/reject');
    Route::post('batchApprove', 'dynamic.DynamicComment/batchApprove');
    Route::post('batchReject', 'dynamic.DynamicComment/batchReject');
    Route::get('getReviewConfig', 'dynamic.DynamicComment/getReviewConfig');
    Route::post('setReviewConfig', 'dynamic.DynamicComment/setReviewConfig');
})->middleware([
    app\adminapi\http\middleware\InitMiddleware::class,
    app\adminapi\http\middleware\LoginMiddleware::class,
    app\adminapi\http\middleware\AuthMiddleware::class,
]);
```


### 8. 前端 - 后台管理界面

#### 8.1 配置页面

**文件**: `admin/src/views/dynamic/config/index.vue`

```vue
<template>
    <div class="dynamic-config-page">
        <el-card class="!border-none" shadow="never">
            <el-form ref="formRef" :model="formData" label-width="120px">
                <el-form-item label="评论审核">
                    <div>
                        <el-switch
                            v-model="formData.comment_review_enabled"
                            :active-value="1"
                            :inactive-value="0"
                            @change="handleSaveConfig"
                        />
                        <div class="form-tips mt-2">
                            开启后，用户发表的评论需要管理员审核通过后才能显示
                        </div>
                    </div>
                </el-form-item>
            </el-form>
        </el-card>
    </div>
</template>

<script lang="ts" setup name="dynamicConfig">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { apiGetReviewConfig, apiSetReviewConfig } from '@/api/dynamic'

const formData = ref({
    comment_review_enabled: 0
})

// 获取配置
const getConfig = async () => {
    try {
        const res = await apiGetReviewConfig()
        formData.value.comment_review_enabled = res.enabled
    } catch (error) {
        console.error('获取配置失败', error)
    }
}

// 保存配置
const handleSaveConfig = async () => {
    try {
        await apiSetReviewConfig({
            enabled: formData.value.comment_review_enabled
        })
        ElMessage.success('设置成功')
    } catch (error) {
        ElMessage.error('设置失败')
        // 恢复原值
        formData.value.comment_review_enabled = formData.value.comment_review_enabled === 1 ? 0 : 1
    }
}

onMounted(() => {
    getConfig()
})
</script>

<style lang="scss" scoped>
.form-tips {
    font-size: 12px;
    color: #999;
    line-height: 1.5;
}
</style>
```


#### 8.2 审核列表页面

**文件**: `admin/src/views/dynamic/comment/review.vue`

```vue
<template>
    <div class="comment-review-page">
        <el-card class="!border-none" shadow="never">
            <!-- 工具栏 -->
            <div class="mb-4 flex items-center justify-between">
                <div class="text-lg font-medium">评论审核</div>
                <div class="flex gap-2">
                    <el-button
                        v-if="selectedIds.length > 0"
                        type="success"
                        @click="handleBatchApprove"
                    >
                        批量通过 ({{ selectedIds.length }})
                    </el-button>
                    <el-button
                        v-if="selectedIds.length > 0"
                        type="danger"
                        @click="handleBatchReject"
                    >
                        批量拒绝 ({{ selectedIds.length }})
                    </el-button>
                </div>
            </div>

            <!-- 列表 -->
            <el-table
                v-loading="loading"
                :data="tableData"
                @selection-change="handleSelectionChange"
            >
                <el-table-column type="selection" width="55" />
                <el-table-column label="评论内容" prop="content" min-width="200" />
                <el-table-column label="发表用户" prop="user.nickname" width="120" />
                <el-table-column label="所属动态" prop="dynamic.title" width="150" show-overflow-tooltip />
                <el-table-column label="发表时间" prop="create_time" width="160">
                    <template #default="{ row }">
                        {{ formatTime(row.create_time) }}
                    </template>
                </el-table-column>
                <el-table-column label="操作" width="180" fixed="right">
                    <template #default="{ row }">
                        <el-button type="success" link @click="handleApprove(row.id)">
                            通过
                        </el-button>
                        <el-button type="danger" link @click="handleReject(row.id)">
                            拒绝
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>

            <!-- 分页 -->
            <div class="mt-4 flex justify-end">
                <pagination
                    v-model="pager"
                    @change="getList"
                />
            </div>
        </el-card>

        <!-- 拒绝原因弹窗 -->
        <el-dialog v-model="rejectDialogVisible" title="拒绝原因" width="500px">
            <el-form :model="rejectForm" label-width="80px">
                <el-form-item label="拒绝原因">
                    <el-input
                        v-model="rejectForm.remark"
                        type="textarea"
                        :rows="4"
                        placeholder="请输入拒绝原因"
                        maxlength="255"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>
            <template #footer>
                <el-button @click="rejectDialogVisible = false">取消</el-button>
                <el-button type="primary" @click="confirmReject">确定</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup name="commentReview">
import { ref, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { usePaging } from '@/hooks/usePaging'
import { 
    apiGetReviewList, 
    apiApproveComment, 
    apiRejectComment,
    apiBatchApproveComment,
    apiBatchRejectComment
} from '@/api/dynamic'
import { formatTime } from '@/utils/util'

const loading = ref(false)
const selectedIds = ref<number[]>([])
const rejectDialogVisible = ref(false)
const rejectForm = reactive({
    id: 0,
    ids: [] as number[],
    remark: '',
    isBatch: false
})

const { pager, tableData, getList } = usePaging({
    fetchFun: apiGetReviewList,
    params: {}
})

// 选择变化
const handleSelectionChange = (selection: any[]) => {
    selectedIds.value = selection.map(item => item.id)
}

// 审核通过
const handleApprove = async (id: number) => {
    try {
        await ElMessageBox.confirm('确认通过该评论？', '提示', {
            type: 'warning'
        })
        await apiApproveComment({ id })
        ElMessage.success('审核通过')
        getList()
    } catch (error) {
        // 用户取消
    }
}

// 拒绝
const handleReject = (id: number) => {
    rejectForm.id = id
    rejectForm.ids = []
    rejectForm.remark = ''
    rejectForm.isBatch = false
    rejectDialogVisible.value = true
}

// 确认拒绝
const confirmReject = async () => {
    if (!rejectForm.remark.trim()) {
        ElMessage.warning('请输入拒绝原因')
        return
    }

    try {
        if (rejectForm.isBatch) {
            await apiBatchRejectComment({
                ids: rejectForm.ids,
                remark: rejectForm.remark
            })
        } else {
            await apiRejectComment({
                id: rejectForm.id,
                remark: rejectForm.remark
            })
        }
        ElMessage.success('已拒绝')
        rejectDialogVisible.value = false
        getList()
    } catch (error) {
        ElMessage.error('操作失败')
    }
}

// 批量通过
const handleBatchApprove = async () => {
    try {
        await ElMessageBox.confirm(`确认通过选中的 ${selectedIds.value.length} 条评论？`, '提示', {
            type: 'warning'
        })
        const res = await apiBatchApproveComment({ ids: selectedIds.value })
        ElMessage.success(`批量审核完成：成功 ${res.success_count} 条，失败 ${res.fail_count} 条`)
        getList()
    } catch (error) {
        // 用户取消
    }
}

// 批量拒绝
const handleBatchReject = () => {
    rejectForm.id = 0
    rejectForm.ids = selectedIds.value
    rejectForm.remark = ''
    rejectForm.isBatch = true
    rejectDialogVisible.value = true
}
</script>
```


#### 8.3 API 接口定义

**文件**: `admin/src/api/dynamic.ts`

```typescript
// 获取评论审核配置
export const apiGetReviewConfig = () => {
    return request.get({ url: '/dynamic/comment/getReviewConfig' })
}

// 设置评论审核配置
export const apiSetReviewConfig = (params: { enabled: number }) => {
    return request.post({ url: '/dynamic/comment/setReviewConfig', params })
}

// 获取待审核评论列表
export const apiGetReviewList = (params: any) => {
    return request.get({ url: '/dynamic/comment/reviewList', params })
}

// 审核通过评论
export const apiApproveComment = (params: { id: number }) => {
    return request.post({ url: '/dynamic/comment/approve', params })
}

// 拒绝评论
export const apiRejectComment = (params: { id: number; remark: string }) => {
    return request.post({ url: '/dynamic/comment/reject', params })
}

// 批量审核通过
export const apiBatchApproveComment = (params: { ids: number[] }) => {
    return request.post({ url: '/dynamic/comment/batchApprove', params })
}

// 批量拒绝
export const apiBatchRejectComment = (params: { ids: number[]; remark: string }) => {
    return request.post({ url: '/dynamic/comment/batchReject', params })
}
```


### 9. 前端 - 移动端界面

#### 9.1 评论发表提示

**文件**: `uniapp/src/pages/dynamic_detail/dynamic_detail.vue`

修改评论发表成功后的提示逻辑：

```typescript
const handleSendComment = async () => {
    // ... 现有验证逻辑 ...

    try {
        const res = await apiAddComment({
            dynamic_id: dynamicId.value,
            content: commentContent.value,
            parent_id: replyTo.value?.id || 0,
            reply_user_id: replyTo.value?.user_id || 0,
        })

        // 根据返回消息判断是否需要审核
        if (res.message.includes('审核')) {
            uni.showToast({
                title: '评论成功，等待审核',
                icon: 'none',
                duration: 2000
            })
        } else {
            uni.showToast({
                title: '评论成功',
                icon: 'success'
            })
        }

        commentContent.value = ''
        replyTo.value = null
        
        // 刷新评论列表
        getCommentList()
    } catch (error) {
        uni.showToast({
            title: error.message || '评论失败',
            icon: 'none'
        })
    }
}
```


#### 9.2 我的评论页面

**文件**: `uniapp/src/pages/user/my_comments.vue`

```vue
<template>
    <view class="my-comments-page">
        <tn-tabs v-model="activeTab" @change="handleTabChange">
            <tn-tabs-item name="all" title="全部" />
            <tn-tabs-item name="pending" title="审核中" />
            <tn-tabs-item name="approved" title="已发布" />
            <tn-tabs-item name="rejected" title="未通过" />
        </tn-tabs>

        <view class="comment-list">
            <view
                v-for="item in commentList"
                :key="item.id"
                class="comment-item"
            >
                <view class="comment-content">{{ item.content }}</view>
                <view class="comment-meta">
                    <text class="time">{{ formatTime(item.create_time) }}</text>
                    <tn-tag
                        :type="getStatusType(item.review_status)"
                        size="sm"
                    >
                        {{ getStatusText(item.review_status) }}
                    </tn-tag>
                </view>
                <view
                    v-if="item.review_status === 2 && item.review_remark"
                    class="reject-reason"
                >
                    <text class="label">拒绝原因：</text>
                    <text class="reason">{{ item.review_remark }}</text>
                </view>
            </view>

            <view v-if="commentList.length === 0" class="empty-state">
                <tn-empty description="暂无评论" />
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiGetMyComments } from '@/api/dynamic'
import { formatTime } from '@/utils/util'

const activeTab = ref('all')
const commentList = ref([])

// 获取评论列表
const getCommentList = async () => {
    try {
        const res = await apiGetMyComments({
            review_status: activeTab.value === 'all' ? '' : getStatusValue(activeTab.value)
        })
        commentList.value = res.data
    } catch (error) {
        console.error('获取评论列表失败', error)
    }
}

// 标签切换
const handleTabChange = () => {
    getCommentList()
}

// 获取状态值
const getStatusValue = (tab: string) => {
    const map = {
        pending: 0,
        approved: 1,
        rejected: 2
    }
    return map[tab] || ''
}

// 获取状态文本
const getStatusText = (status: number) => {
    const map = {
        0: '审核中',
        1: '已发布',
        2: '未通过'
    }
    return map[status] || '未知'
}

// 获取状态类型
const getStatusType = (status: number) => {
    const map = {
        0: 'warning',
        1: 'success',
        2: 'danger'
    }
    return map[status] || 'default'
}

onMounted(() => {
    getCommentList()
})
</script>

<style lang="scss" scoped>
.my-comments-page {
    min-height: 100vh;
    background: #f5f5f5;
}

.comment-list {
    padding: 20rpx;
}

.comment-item {
    background: white;
    border-radius: 16rpx;
    padding: 24rpx;
    margin-bottom: 20rpx;
}

.comment-content {
    font-size: 28rpx;
    color: #333;
    line-height: 1.6;
    margin-bottom: 16rpx;
}

.comment-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.time {
    font-size: 24rpx;
    color: #999;
}

.reject-reason {
    margin-top: 16rpx;
    padding: 16rpx;
    background: #fff3f3;
    border-radius: 8rpx;
    font-size: 24rpx;
}

.label {
    color: #f56c6c;
    font-weight: 500;
}

.reason {
    color: #666;
}

.empty-state {
    padding: 100rpx 0;
}
</style>
```


## 数据模型

### DynamicComment 实体

```typescript
interface DynamicComment {
    id: number
    dynamic_id: number
    user_id: number
    parent_id: number
    reply_user_id: number
    content: string
    images: string[]
    like_count: number
    reply_count: number
    is_top: number
    status: number  // 0=待审核, 1=正常, 2=已删除, 3=已拒绝
    review_status: number  // 新增：0=待审核, 1=已通过, 2=已拒绝
    review_admin_id: number  // 新增：审核管理员ID
    review_time: number  // 新增：审核时间
    review_remark: string  // 新增：审核备注（拒绝原因）
    ip: string
    create_time: number
    update_time: number
    delete_time: number
}
```

### 审核配置

```typescript
interface ReviewConfig {
    comment_review_enabled: number  // 0=关闭审核, 1=开启审核
}
```


## 正确性属性

*属性是一个特征或行为，应该在系统的所有有效执行中保持为真——本质上是关于系统应该做什么的正式声明。属性作为人类可读规范和机器可验证正确性保证之间的桥梁。*

### 属性 1: 审核状态根据配置自动设置

*对于任何* 用户发表的评论，当审核功能关闭时，评论的 `review_status` 应该被设置为 1（已通过）；当审核功能开启时，评论的 `review_status` 应该被设置为 0（待审核）

**验证: 需求 3.1, 3.2, 13.5**

### 属性 2: 待审核评论不在列表中显示

*对于任何* 评论列表查询，返回的结果中不应该包含 `review_status = 0`（待审核）的评论

**验证: 需求 3.4, 8.1**

### 属性 3: 已通过评论正常显示

*对于任何* 评论列表查询，所有 `review_status = 1`（已通过）的评论都应该在结果中显示

**验证: 需求 3.5, 8.1**

### 属性 4: 审核通过更新状态

*对于任何* 待审核评论（`review_status = 0`），当管理员执行审核通过操作时，该评论的 `review_status` 应该被更新为 1（已通过）

**验证: 需求 5.1**

### 属性 5: 审核拒绝更新状态和原因

*对于任何* 待审核评论（`review_status = 0`），当管理员执行审核拒绝操作时，该评论的 `review_status` 应该被更新为 2（已拒绝），且 `review_remark` 应该保存拒绝原因

**验证: 需求 5.3, 5.6**

### 属性 6: 审核操作记录审核信息

*对于任何* 审核操作（通过或拒绝），系统应该记录 `review_admin_id`（审核管理员ID）和 `review_time`（审核时间戳）

**验证: 需求 5.4, 5.5**

### 属性 7: 评论计数只计算已通过评论

*对于任何* 动态，其 `comment_count` 字段应该只计算 `review_status = 1`（已通过）的评论数量。当评论被审核通过时增加计数，当评论被拒绝或待审核时不增加计数

**验证: 需求 10.1, 10.2, 10.3, 10.4, 10.5**

### 属性 8: 审核完成发送通知

*对于任何* 审核操作（通过或拒绝），系统应该向评论作者发送站内通知，通知内容应该包含审核结果和相关信息

**验证: 需求 11.1, 11.3, 11.4, 11.5**

### 属性 9: 批量审核通过更新所有选中评论

*对于任何* 批量审核通过操作，所有选中的待审核评论的 `review_status` 都应该被更新为 1（已通过）

**验证: 需求 12.4**

### 属性 10: 批量拒绝应用相同原因

*对于任何* 批量拒绝操作，所有选中的待审核评论的 `review_status` 都应该被更新为 2（已拒绝），且所有评论的 `review_remark` 都应该保存相同的拒绝原因

**验证: 需求 12.5**

### 属性 11: 审核权限验证

*对于任何* 审核API调用，系统应该验证调用者是否具有审核权限。如果没有权限，应该返回权限错误提示

**验证: 需求 14.2, 14.3**

### 属性 12: 配置变更持久化

*对于任何* 评论审核配置的变更操作，新的配置值应该被正确保存到数据库，并在后续读取时返回更新后的值

**验证: 需求 2.4**


## 错误处理

### 1. 数据库层错误

| 错误场景 | 处理方式 | 返回信息 |
|---------|---------|---------|
| 字段添加失败 | 回滚迁移，记录错误日志 | "数据库迁移失败" |
| 字段类型不匹配 | 验证失败，拒绝保存 | "数据格式错误" |
| 数据迁移失败 | 回滚迁移，记录错误日志 | "数据迁移失败" |

### 2. 业务逻辑层错误

| 错误场景 | 处理方式 | 返回信息 |
|---------|---------|---------|
| 评论不存在 | 返回 false，设置错误消息 | "评论不存在" |
| 评论已审核 | 返回 false，设置错误消息 | "该评论已审核" |
| 配置保存失败 | 返回 false，设置错误消息 | "配置保存失败" |
| 权限验证失败 | 返回错误响应 | "无权限执行此操作" |
| 参数验证失败 | 返回验证错误 | "参数错误：{具体错误}" |

### 3. 前端界面错误

| 错误场景 | 处理方式 | 用户提示 |
|---------|---------|---------|
| API 请求失败 | 显示 Toast 提示 | "操作失败，请重试" |
| 网络超时 | 显示错误提示 | "网络连接超时" |
| 未输入拒绝原因 | 显示警告提示 | "请输入拒绝原因" |
| 未选择评论 | 显示警告提示 | "请选择要操作的评论" |

### 4. 边界情况处理

- **空值处理**: `review_status` 为 null 时，默认视为 0（待审核）
- **非法值处理**: `review_status` 不为 0、1 或 2 时，默认视为 0
- **并发处理**: 使用数据库事务确保审核状态变更的原子性
- **重复审核**: 已审核的评论不能再次审核，返回错误提示
- **配置缓存**: 配置变更后清除缓存，确保新配置立即生效


## 测试策略

### 单元测试

**后端模型测试**:
- 测试 `review_status` 字段的默认值设置
- 测试 `approveComment()` 方法的状态更新逻辑
- 测试 `rejectComment()` 方法的状态更新和原因保存
- 测试评论列表过滤逻辑（只返回已通过评论）
- 测试评论计数更新逻辑

**后端逻辑测试**:
- 测试审核配置的读取和保存
- 测试审核通过逻辑
- 测试审核拒绝逻辑
- 测试批量审核逻辑
- 测试权限验证逻辑

**前端组件测试**:
- 测试配置开关组件的状态切换
- 测试审核列表的数据加载和显示
- 测试审核操作按钮的点击事件
- 测试拒绝原因弹窗的显示和提交
- 测试批量操作的选择和执行

### 集成测试

**API 集成测试**:
- 测试完整的评论发表流程（包含审核状态设置）
- 测试完整的审核通过流程
- 测试完整的审核拒绝流程
- 测试批量审核流程
- 测试配置变更流程

**端到端测试**:
- 测试管理员在后台开启审核功能
- 测试用户发表评论进入待审核状态
- 测试管理员审核评论
- 测试用户查看自己评论的审核状态
- 测试评论计数的正确性

### 属性测试配置

- **测试框架**: PHPUnit (后端), Vitest (前端)
- **最小迭代次数**: 100 次
- **测试标签格式**: `Feature: dynamic-comment-review, Property {number}: {property_text}`

### 测试用例示例

**测试用例 1: 审核状态根据配置自动设置**
```php
// Feature: dynamic-comment-review, Property 1: 审核状态根据配置自动设置
public function testReviewStatusSetByConfig()
{
    // 关闭审核
    ConfigService::set('dynamic', 'comment_review_enabled', 0);
    [$success, $message, $comment1] = DynamicComment::addComment(1, 1, '测试评论1');
    $this->assertEquals(DynamicComment::REVIEW_STATUS_APPROVED, $comment1->review_status);
    
    // 开启审核
    ConfigService::set('dynamic', 'comment_review_enabled', 1);
    [$success, $message, $comment2] = DynamicComment::addComment(1, 1, '测试评论2');
    $this->assertEquals(DynamicComment::REVIEW_STATUS_PENDING, $comment2->review_status);
}
```

**测试用例 2: 待审核评论不在列表中显示**
```php
// Feature: dynamic-comment-review, Property 2: 待审核评论不在列表中显示
public function testPendingCommentsNotInList()
{
    // 创建待审核评论
    $comment = DynamicComment::create([
        'dynamic_id' => 1,
        'user_id' => 1,
        'content' => '待审核评论',
        'review_status' => DynamicComment::REVIEW_STATUS_PENDING,
        'status' => DynamicComment::STATUS_NORMAL,
    ]);
    
    // 获取评论列表
    $list = DynamicComment::getCommentList(1);
    
    // 验证待审核评论不在列表中
    $ids = array_column($list['data'], 'id');
    $this->assertNotContains($comment->id, $ids);
}
```

**测试用例 3: 评论计数只计算已通过评论**
```php
// Feature: dynamic-comment-review, Property 7: 评论计数只计算已通过评论
public function testCommentCountOnlyApproved()
{
    $dynamic = Dynamic::create([
        'user_id' => 1,
        'content' => '测试动态',
        'status' => Dynamic::STATUS_PUBLISHED,
        'comment_count' => 0,
    ]);
    
    // 创建待审核评论（不应增加计数）
    ConfigService::set('dynamic', 'comment_review_enabled', 1);
    DynamicComment::addComment($dynamic->id, 1, '待审核评论');
    $dynamic->refresh();
    $this->assertEquals(0, $dynamic->comment_count);
    
    // 审核通过（应增加计数）
    $comment = DynamicComment::where('dynamic_id', $dynamic->id)->find();
    DynamicComment::approveComment($comment->id, 1);
    $dynamic->refresh();
    $this->assertEquals(1, $dynamic->comment_count);
}
```

**测试用例 4: 批量审核通过更新所有选中评论**
```php
// Feature: dynamic-comment-review, Property 9: 批量审核通过更新所有选中评论
public function testBatchApproveUpdatesAll()
{
    // 创建多条待审核评论
    $commentIds = [];
    for ($i = 0; $i < 5; $i++) {
        $comment = DynamicComment::create([
            'dynamic_id' => 1,
            'user_id' => 1,
            'content' => "测试评论{$i}",
            'review_status' => DynamicComment::REVIEW_STATUS_PENDING,
            'status' => DynamicComment::STATUS_NORMAL,
        ]);
        $commentIds[] = $comment->id;
    }
    
    // 批量审核通过
    DynamicCommentLogic::batchApprove($commentIds, 1);
    
    // 验证所有评论都已通过
    $comments = DynamicComment::whereIn('id', $commentIds)->select();
    foreach ($comments as $comment) {
        $this->assertEquals(DynamicComment::REVIEW_STATUS_APPROVED, $comment->review_status);
    }
}
```

