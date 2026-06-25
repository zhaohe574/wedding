<?php
// +----------------------------------------------------------------------
// | 素材清理验证器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\validate;

use app\common\validate\BaseValidate;

class MaterialCleanupValidate extends BaseValidate
{
    protected $rule = [
        'ids' => 'require|array',
        'source' => 'in:all,registered,orphan',
        'type' => 'in:0,10,20,30',
        'reference_state' => 'in:all,none,stale',
        'keyword' => 'max:100',
        'page_no' => 'number|egt:1',
        'page_size' => 'number|egt:1|elt:100',
    ];

    protected $message = [
        'ids.require' => '请选择要清理的素材',
        'ids.array' => '清理素材参数格式错误',
        'source.in' => '素材来源参数错误',
        'type.in' => '素材类型参数错误',
        'reference_state.in' => '引用状态参数错误',
        'keyword.max' => '关键词长度不能超过100个字符',
        'page_no.number' => '页码参数错误',
        'page_no.egt' => '页码参数错误',
        'page_size.number' => '分页数量参数错误',
        'page_size.egt' => '分页数量参数错误',
        'page_size.elt' => '分页数量不能超过100',
    ];

    public function sceneSummary(): MaterialCleanupValidate
    {
        return $this->only(['source', 'type', 'reference_state', 'keyword']);
    }

    public function sceneLists(): MaterialCleanupValidate
    {
        return $this->only(['source', 'type', 'reference_state', 'keyword', 'page_no', 'page_size']);
    }

    public function sceneDelete(): MaterialCleanupValidate
    {
        return $this->only(['ids']);
    }
}
