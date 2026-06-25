<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 单量月报控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\ops;

use app\adminapi\controller\BaseAdminController;
use app\common\service\MonthlyReportService;

class MonthlyReportController extends BaseAdminController
{
    public function materialLists()
    {
        return $this->success('获取成功', MonthlyReportService::materialLists($this->request->get()));
    }

    public function materialSave()
    {
        try {
            return $this->success('保存成功', MonthlyReportService::materialSave($this->request->post()));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function materialDelete()
    {
        try {
            MonthlyReportService::materialDelete((int)$this->request->post('id', 0));
            return $this->success('删除成功');
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function staffOptions()
    {
        return $this->success('获取成功', MonthlyReportService::staffOptions($this->request->get()));
    }

    public function categoryOptions()
    {
        return $this->success('获取成功', MonthlyReportService::categoryOptions());
    }

    public function templateConfig()
    {
        try {
            return $this->success('获取成功', MonthlyReportService::templateConfig(
                (string)$this->request->get('template_type', 'addition'),
                (int)$this->request->get('template_id', 0),
                (int)$this->request->get('include_disabled', 1) === 1
            ));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function templateSave()
    {
        try {
            return $this->success('保存成功', MonthlyReportService::templateSave($this->request->post()));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function templateCopy()
    {
        try {
            return $this->success('复制成功', MonthlyReportService::templateCopy(
                (int)$this->request->post('template_id', 0),
                (string)$this->request->post('template_name', '')
            ));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function templateSetDefault()
    {
        try {
            return $this->success('设置成功', MonthlyReportService::templateSetDefault((int)$this->request->post('template_id', 0)));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function templateDisable()
    {
        try {
            return $this->success('停用成功', MonthlyReportService::templateDisable((int)$this->request->post('template_id', 0)));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function templatePreview()
    {
        try {
            return $this->success('预览成功', MonthlyReportService::templatePreview($this->request->post()));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function preview()
    {
        try {
            return $this->success('预览成功', MonthlyReportService::preview($this->request->post()));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function confirm()
    {
        try {
            return $this->success('确认成功', MonthlyReportService::confirm($this->request->post(), $this->adminId));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }

    public function latest()
    {
        return $this->success('获取成功', MonthlyReportService::latest((string)$this->request->get('report_month', '')) ?: []);
    }

    public function history()
    {
        return $this->success('获取成功', MonthlyReportService::history($this->request->get()));
    }

    public function detail()
    {
        return $this->success('获取成功', MonthlyReportService::detail((int)$this->request->get('id', 0)) ?: []);
    }

    public function regenerateAssets()
    {
        try {
            return $this->success('图片已更新', MonthlyReportService::regenerateAssets(
                (int)$this->request->post('id', 0),
                (string)$this->request->post('snapshot_hash', '')
            ));
        } catch (\Throwable $e) {
            return $this->fail(MonthlyReportService::normalizeErrorMessage($e->getMessage()));
        }
    }
}
