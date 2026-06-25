<?php
// +----------------------------------------------------------------------
// | 素材清理审计日志模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\file;

use app\common\model\BaseModel;

class FileCleanupLog extends BaseModel
{
    protected $name = 'file_cleanup_log';
    protected $updateTime = false;
}
