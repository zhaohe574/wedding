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

namespace app\adminapi\controller;


use app\common\cache\ExportCache;
use app\common\service\JsonService;

class DownloadController extends BaseAdminController
{

    /**
     * 导出文件下载必须登录，且只能下载自己发起的导出记录。
     * 原实现将 export 放入 $notNeedLogin，导致导出文件可被匿名下载。
     */
    public array $notNeedLogin = [];

    /**
     * @notes 导出文件
     * @return \think\response\File|\think\response\Json
     * @author 段誉
     * @date 2022/11/24 16:10
     */
    public function export()
    {
        //获取文件缓存的key
        $fileKey = (string)request()->get('file', '');
        if ($fileKey === '') {
            return JsonService::fail('下载文件不存在');
        }

        //通过文件缓存的key获取文件储存的路径
        $exportCache = new ExportCache();
        $fileInfo = $exportCache->getFile($fileKey);

        if (empty($fileInfo)) {
            return JsonService::fail('下载文件不存在');
        }

        // 越权校验：导出记录必须由当前登录管理员发起
        $ownerAdminId = (int)($fileInfo['admin_id'] ?? 0);
        if ($ownerAdminId !== $this->adminId) {
            return JsonService::forbidden('无权下载该文件');
        }

        //下载前删除缓存
        $exportCache->delete($fileKey);

        return download($fileInfo['src'] . $fileInfo['name'], $fileInfo['name']);
    }
}
