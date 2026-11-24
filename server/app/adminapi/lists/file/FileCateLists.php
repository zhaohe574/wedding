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

namespace app\adminapi\lists\file;


use app\adminapi\lists\BaseAdminDataLists;
use app\adminapi\logic\FileLogic;
use app\common\lists\ListsSearchInterface;
use app\common\model\file\FileCate;

/**
 * 文件分类列表
 * Class FileCateLists
 * @package app\adminapi\lists\file
 */
class FileCateLists extends BaseAdminDataLists implements ListsSearchInterface
{

    /**
     * @notes 文件分类搜素条件
     * @return \string[][]
     * @author 段誉
     * @date 2021/12/29 14:24
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type']
        ];
    }


    /**
     * @notes 素材空间范围与归属筛选参数
     */
    private function scopeParams(): array
    {
        return [
            (string)($this->params['scope'] ?? FileLogic::SCOPE_MINE),
            (string)($this->params['owner_admin_id'] ?? ''),
        ];
    }


    /**
     * @notes 获取文件分类列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2021/12/29 14:24
     */
    public function lists(): array
    {
        [$scope, $ownerAdminId] = $this->scopeParams();

        $query = (new FileCate())->field(['id,pid,type,name,is_public,admin_id'])
            ->where($this->searchWhere);

        FileLogic::applyCateVisibleScope($query, $this->adminId, $this->adminInfo, $scope, $ownerAdminId);

        $lists = $query->order('id desc')->select()->toArray();

        // 全部素材视图且未筛选特定账号时，标注分组归属
        if (FileLogic::normalizeScope($scope, $this->adminId, $this->adminInfo) === FileLogic::SCOPE_ALL
            && $ownerAdminId === '') {
            $ownerMap = [];
            foreach (FileLogic::getOwnerOptions() as $option) {
                $ownerMap[(int)$option['value']] = $option['label'];
            }

            foreach ($lists as &$item) {
                $isPublic = (int)($item['is_public'] ?? 0) === 1;
                $adminId = (int)($item['admin_id'] ?? 0);
                $item['owner_name'] = $isPublic
                    ? '公共素材'
                    : ($ownerMap[$adminId] ?? ('账号#' . $adminId));
            }
            unset($item);
        }

        return linear_to_tree($lists, 'children');
    }


    /**
     * @notes 获取文件分类数量
     * @return int
     * @author 段誉
     * @date 2021/12/29 14:24
     */
    public function count(): int
    {
        [$scope, $ownerAdminId] = $this->scopeParams();

        $query = (new FileCate())->where($this->searchWhere);
        FileLogic::applyCateVisibleScope($query, $this->adminId, $this->adminInfo, $scope, $ownerAdminId);

        return $query->count();
    }
}
