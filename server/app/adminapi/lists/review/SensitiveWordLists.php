<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 敏感词列表
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\lists\review;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\model\review\SensitiveWord;
use app\common\lists\ListsSearchInterface;

/**
 * 敏感词列表
 * Class SensitiveWordLists
 * @package app\adminapi\lists\review
 */
class SensitiveWordLists extends BaseAdminDataLists implements ListsSearchInterface
{
    /**
     * @notes 搜索条件
     * @return array
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'level', 'status'],
            '%like%' => ['word'],
        ];
    }

    /**
     * @notes 列表
     * @return array
     */
    public function lists(): array
    {
        $lists = SensitiveWord::where($this->searchWhere)
            ->order('create_time desc')
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            // PHP 8 类型转换
            $item['create_time'] = (int)$item['create_time'];
            $item['type'] = (int)$item['type'];
            $item['level'] = (int)$item['level'];
            
            $item['type_text'] = SensitiveWord::getTypeDesc($item['type']);
            $item['level_text'] = SensitiveWord::getLevelDesc($item['level']);
            $item['create_time_text'] = date('Y-m-d H:i:s', $item['create_time']);
        }

        return $lists;
    }

    /**
     * @notes 总数
     * @return int
     */
    public function count(): int
    {
        return SensitiveWord::where($this->searchWhere)->count();
    }
}
