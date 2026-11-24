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


namespace app\common\lists;


use app\common\enum\ExportEnum;
use app\common\service\JsonService;
use app\common\validate\ListsValidate;
use app\Request;
use think\facade\Config;

/**
 * 数据列表基类
 * Class BaseDataLists
 * @package app\common\lists
 */
abstract class BaseDataLists implements ListsInterface
{

    use ListsSearchTrait;
    use ListsSortTrait;
    use ListsExcelTrait;

    public Request $request; //请求对象

    public int $pageNo; //页码
    public int $pageSize; //每页数量
    public int $limitOffset;  //limit查询offset值
    public int $limitLength;  //limit查询数量
    public int $pageSizeMax;
    public int $pageType = 0; //默认类型：0-一般分页；1-不分页，获取最大所有数据


    protected string $orderBy;
    protected string $field;

    protected $startTime;
    protected $endTime;

    protected $start;
    protected $end;

    protected array $params;
    protected $sortOrder = [];

    public string $export;


    public function __construct()
    {
        //参数验证
        (new ListsValidate())->get()->goCheck();

        //请求参数设置
        $this->request = request();
        $this->params = $this->request->param();

        //分页初始化
        $this->initPage();

        //搜索初始化
        $this->initSearch();

        //排序初始化
        $this->initSort();

        //导出初始化
        $this->initExport();
    }


    /**
     * @notes 分页参数初始化
     * @author 令狐冲
     * @date 2021/7/30 23:55
     */
    private function initPage()
    {
        $this->pageSizeMax = (int)Config::get('project.lists.page_size_max');
        $this->pageSize = (int)Config::get('project.lists.page_size');
        $this->pageType = (int)$this->request->get('page_type', 1);

        if ($this->pageType == 1) {
            //分页
            $this->pageNo = (int)$this->request->get('page_no', 1);
            $pageSize = (int)$this->request->get('page_size', $this->pageSize);
            // 单页数量必须收敛在 [1, page_size_max]，避免一次请求拉全表造成内存与数据库压力
            $this->pageSize = $this->clampPageSize($pageSize);
        } else {
            //不分页
            $this->pageNo = 1;//强制到第一页
            $this->pageSize = $this->clampPageSize($this->pageSizeMax);
        }

        // 页码下界保护：page_no 为 0/负数时 offset 会变成负数，导致 SQL 报错或全表扫描
        if ($this->pageNo < 1) {
            $this->pageNo = 1;
        }

        //limit查询参数设置
        $this->limitOffset = ($this->pageNo - 1) * $this->pageSize;
        $this->limitLength = $this->pageSize;
    }

    /**
     * @notes 收敛每页数量：非数字/小于 1 回落到默认值，超过上限按上限截断
     */
    private function clampPageSize(int $pageSize): int
    {
        $default = max(1, (int)Config::get('project.lists.page_size', 25));
        $max = $this->pageSizeMax > 0 ? $this->pageSizeMax : $default;

        if ($pageSize < 1) {
            $pageSize = $default;
        }

        return min($pageSize, $max);
    }

    /**
     * @notes 初始化搜索
     * @return array
     * @author 令狐冲
     * @date 2021/7/31 00:00
     */
    private function initSearch()
    {
        if (!($this instanceof ListsSearchInterface)) {
            return [];
        }
        $startTime = $this->request->get('start_time');
        if ($startTime) {
            $this->startTime = strtotime($startTime);
        }

        $endTime = trim((string)$this->request->get('end_time', ''));
        if ($endTime !== '') {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $endTime)) {
                $this->endTime = strtotime($endTime . ' 23:59:59');
            } else {
                $this->endTime = strtotime($endTime);
            }
        }

        $this->start = $this->request->get('start');
        $this->end = $this->request->get('end');

        return $this->searchWhere = $this->createWhere($this->setSearch());
    }


    /**
     * @notes 初始化排序
     * @return array|string[]
     * @author 令狐冲
     * @date 2021/7/31 00:03
     */
    private function initSort()
    {
        if (!($this instanceof ListsSortInterface)) {
            return [];
        }

        $this->field = $this->request->get('field', '');
        $this->orderBy = $this->request->get('order_by', '');

        return $this->sortOrder = $this->createOrder($this->setSortFields(), $this->setDefaultOrder());
    }

    /**
     * @notes 导出初始化
     * @return false|\think\response\Json
     * @author 令狐冲
     * @date 2021/7/31 01:15
     */
    private function initExport()
    {
        $this->export = $this->request->get('export', '');

        //不做导出操作
        if ($this->export != ExportEnum::INFO && $this->export != ExportEnum::EXPORT) {
            return false;
        }

        //导出操作，但是没有实现导出接口
        if (!($this instanceof ListsExcelInterface)) {
            return JsonService::throw('该列表不支持导出');
        }

        $this->fileName = $this->request->get('file_name', '') ?: $this->setFileName();

        //不导出文件，不初始化一下参数
        if ($this->export != ExportEnum::EXPORT) {
            return false;
        }

        //导出文件名设置
        $this->fileName .= '-' . date('Y-m-d-His') . '.xlsx';

        //导出文件准备
        //指定导出范围（例：第2页到，第5页的数据）
        if ($this->pageType == 1) {
            $this->pageStart = $this->request->get('page_start', $this->pageStart);
            $this->pageEnd = $this->request->get('page_end', $this->pageEnd);
            //改变查询数量参数（例：第2页到，第5页的数据，查询->page(2,(5-2+1)*25)
            $this->limitOffset = ($this->pageStart - 1) * $this->pageSize;
            $this->limitLength = ($this->pageEnd - $this->pageStart + 1) * $this->pageSize;
        }

        $count = $this->count();

        //判断导出范围是否有数据
        if ($count == 0 || ceil($count / $this->pageSize) < $this->pageStart) {
            $msg = $this->pageType ? '第' . $this->pageStart . '页到第' . $this->pageEnd . '页没有数据，无法导出' : '没有数据,无法导出';
            return JsonService::throw($msg);
        }
    }

    /**
     * @notes 不需要分页，可以调用此方法，无需查询第二次
     * @return int
     * @author 令狐冲
     * @date 2021/7/6 00:34
     */
    public function defaultCount(): int
    {
        return count($this->lists());
    }


}