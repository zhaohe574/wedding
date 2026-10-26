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

namespace app\common\cache;



class ExportCache extends BaseCache
{

    protected $uniqid = '';

    public function __construct()
    {
        parent::__construct();
        //以微秒计的当前时间，生成一个唯一的 ID,以tagname为前缀
        $this->uniqid = md5(uniqid($this->tagName,true).mt_rand());
    }

    /**
     * @notes 获取excel缓存目录
     * @return string
     * @author 令狐冲
     * @date 2021/7/28 17:36
     */
    public function getSrc()
    {
        return app()->getRootPath() . 'runtime/file/export/'.date('Y-m').'/'.$this->uniqid.'/';
    }


    /**
     * @notes 设置文件路径缓存地址
     * @param $fileName 文件名
     * @param int $adminId 发起导出的管理员ID（用于下载时的越权校验）
     * @return string
     * @author 令狐冲
     * @date 2021/7/28 17:36
     */
    public function setFile($fileName, int $adminId = 0)
    {
        $src = $this->getSrc();
        // key 绑定管理员ID，避免不同管理员的导出记录互相覆盖/猜测
        $key = md5($src . $fileName . ':' . $adminId) . time();
        $this->set($key, ['src' => $src, 'name' => $fileName, 'admin_id' => $adminId], 300);
        return $key;
    }

    /**
     * @notes 获取文件缓存的路径
     * @param $key
     * @return mixed
     * @author 令狐冲
     * @date 2021/7/26 18:36
     */
    public function getFile($key)
    {
        return $this->get($key);
    }

}