<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

namespace app\adminapi\lists\tools;

use app\adminapi\lists\BaseAdminDataLists;
use think\facade\Db;


/**
 * 数据表列表
 * Class GeneratorLists
 * @package app\adminapi\lists\tools
 */
class DataTableLists extends BaseAdminDataLists
{

    /**
     * @notes 查询结果
     * 安全说明：SHOW TABLE STATUS 无法使用参数绑定，故改为「全量查询 + 内存过滤」，
     * 彻底消除把用户输入拼进 SQL 的注入面（表数量有限，全量返回成本可接受）。
     * @return mixed
     * @author 段誉
     * @date 2022/6/13 18:54
     */
    public function queryResult()
    {
        $tables = Db::query('SHOW TABLE STATUS');
        if (empty($tables)) {
            return [];
        }

        $nameKeyword = $this->normalizeKeyword($this->params['name'] ?? '');
        $commentKeyword = $this->normalizeKeyword($this->params['comment'] ?? '');

        if ($nameKeyword === '' && $commentKeyword === '') {
            return $tables;
        }

        return array_values(array_filter($tables, function ($table) use ($nameKeyword, $commentKeyword) {
            $name = strtolower((string)($table['Name'] ?? ''));
            $comment = (string)($table['Comment'] ?? '');

            if ($nameKeyword !== '' && mb_stripos($name, $nameKeyword) === false) {
                return false;
            }
            if ($commentKeyword !== '' && mb_stripos($comment, $commentKeyword) === false) {
                return false;
            }
            return true;
        }));
    }


    /**
     * @notes 归一化搜索关键字（去首尾空白，限制长度，剥离 SQL 元字符）
     * @param mixed $keyword
     * @return string
     */
    private function normalizeKeyword($keyword): string
    {
        $keyword = trim((string)$keyword);
        if ($keyword === '') {
            return '';
        }
        // 仅保留字母、数字、下划线、中文，过滤引号、百分号、注释符等 SQL 元字符
        $keyword = (string)preg_replace('/[^\w\x{4e00}-\x{9fa5}]/u', '', $keyword);
        return mb_substr($keyword, 0, 64);
    }


    /**
     * @notes 处理列表
     * @return array
     * @author 段誉
     * @date 2022/6/13 18:54
     */
    public function lists(): array
    {
        $lists = array_map("array_change_key_case", $this->queryResult());
        $offset = max(0, ($this->pageNo - 1) * $this->pageSize);
        $lists = array_slice($lists, $offset, $this->pageSize, true);
        return array_values($lists);
    }


    /**
     * @notes 获取数量
     * @return int
     * @author 段誉
     * @date 2022/6/13 18:54
     */
    public function count(): int
    {
        return count($this->queryResult());
    }

}