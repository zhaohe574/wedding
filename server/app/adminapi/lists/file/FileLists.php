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
use app\common\lists\ListsExtendInterface;
use app\common\lists\ListsSearchInterface;
use app\common\model\file\File;
use app\common\model\file\FileCate;
use app\common\service\FileService;

/**
 * 文件列表
 * Class FileLists
 * @package app\adminapi\lists\file
 */
class FileLists extends BaseAdminDataLists implements ListsSearchInterface, ListsExtendInterface
{

    /**
     * @notes 文件搜索条件
     * @return \string[][]
     * @author 段誉
     * @date 2021/12/29 14:27
     */
    public function setSearch(): array
    {
        return [
            '=' => ['type', 'source'],
            '%like%' => ['name']
        ];
    }

    /**
     * @notes 额外查询处理
     * @return array
     * @author 段誉
     * @date 2024/2/7 10:26
     */
    public function queryWhere(): array
    {
        $where = [];

        if (array_key_exists('cid', $this->params) && $this->params['cid'] !== '') {
            $cid = (int)$this->params['cid'];
            if ($cid === 0) {
                $where[] = ['cid', '=', 0];
                return $where;
            }

            $cateChild = FileLogic::getCateIds($cid);
            array_push($cateChild, $cid);
            $where[] = ['cid', 'in', $cateChild];
        }

        return $where;
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
     * @notes 扩展信息：素材管理能力与归属筛选项
     * @return array
     */
    public function extend(): array
    {
        $isManager = FileLogic::isMaterialManager($this->adminId, $this->adminInfo);
        [$scope, $ownerAdminId] = $this->scopeParams();

        return [
            'can_manage_all' => $isManager,
            'can_manage_public' => $isManager,
            'current_scope' => FileLogic::normalizeScope($scope, $this->adminId, $this->adminInfo),
            'owner_options' => $isManager ? FileLogic::getOwnerOptions() : [],
        ];
    }


    /**
     * @notes 获取文件列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2021/12/29 14:27
     */
    public function lists(): array
    {
        [$scope, $ownerAdminId] = $this->scopeParams();

        $query = (new File())->field(['id,cid,type,name,uri,create_time,source,source_id'])
            ->order('id', 'desc')
            ->where($this->searchWhere)
            ->where($this->queryWhere());

        FileLogic::applyVisibleScope($query, $this->adminId, $this->adminInfo, $scope, $ownerAdminId);

        $lists = $query
            ->limit($this->limitOffset, $this->limitLength)
            ->select()
            ->toArray();

        $isManager = FileLogic::isMaterialManager($this->adminId, $this->adminInfo);
        $ownerMeta = FileLogic::resolveOwnerMeta($lists);

        foreach ($lists as &$item) {
            $item['url'] = FileService::getFileUrl($item['uri']);
            $meta = $ownerMeta[(int)$item['id']] ?? ['is_public' => false, 'owner_name' => ''];
            $item['is_public'] = $meta['is_public'];
            $item['owner_name'] = $meta['owner_name'];
            $item['can_manage'] = $isManager || $meta['is_public'] === false;
        }

        return $lists;
    }


    /**
     * @notes 获取文件数量
     * @return int
     * @author 段誉
     * @date 2021/12/29 14:29
     */
    public function count(): int
    {
        [$scope, $ownerAdminId] = $this->scopeParams();

        $query = (new File())->where($this->searchWhere)
            ->where($this->queryWhere());

        FileLogic::applyVisibleScope($query, $this->adminId, $this->adminInfo, $scope, $ownerAdminId);

        return $query->count();
    }
}
