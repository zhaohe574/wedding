<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 评价标签管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\review;

use app\adminapi\controller\BaseAdminController;
use app\common\model\review\ReviewTag;

/**
 * 评价标签管理控制器
 * Class ReviewTagController
 * @package app\adminapi\controller\review
 */
class ReviewTagController extends BaseAdminController
{
    /**
     * @notes 标签列表
     * @return \think\response\Json
     */
    public function lists()
    {
        $params = $this->request->get();
        $where = [];
        
        if (isset($params['type']) && $params['type'] !== '') {
            $where['type'] = $params['type'];
        }
        if (isset($params['status']) && $params['status'] !== '') {
            $where['status'] = $params['status'];
        }
        if (!empty($params['name'])) {
            $where[] = ['name', 'like', '%' . $params['name'] . '%'];
        }

        $lists = ReviewTag::where($where)
            ->order('type asc, sort asc')
            ->select()
            ->toArray();

        foreach ($lists as &$item) {
            $item['type_text'] = ReviewTag::getTypeDesc($item['type']);
        }

        return $this->success('', ['lists' => $lists]);
    }

    /**
     * @notes 标签详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = $this->request->get('id');
        $tag = ReviewTag::find($id);
        if (!$tag) {
            return $this->fail('标签不存在');
        }
        return $this->success('', $tag->toArray());
    }

    /**
     * @notes 添加标签
     * @return \think\response\Json
     */
    public function add()
    {
        $params = $this->request->post();
        
        if (empty($params['name'])) {
            return $this->fail('标签名称不能为空');
        }

        $exists = ReviewTag::where('name', $params['name'])->find();
        if ($exists) {
            return $this->fail('标签名称已存在');
        }

        ReviewTag::create([
            'name' => $params['name'],
            'type' => $params['type'] ?? ReviewTag::TYPE_GOOD,
            'icon' => $params['icon'] ?? '',
            'color' => $params['color'] ?? '',
            'sort' => $params['sort'] ?? 0,
            'status' => $params['status'] ?? 1,
        ]);

        return $this->success('添加成功');
    }

    /**
     * @notes 编辑标签
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = $this->request->post();
        
        if (empty($params['id'])) {
            return $this->fail('标签ID不能为空');
        }

        $tag = ReviewTag::find($params['id']);
        if (!$tag) {
            return $this->fail('标签不存在');
        }

        if (!empty($params['name'])) {
            $exists = ReviewTag::where('name', $params['name'])
                ->where('id', '<>', $params['id'])
                ->find();
            if ($exists) {
                return $this->fail('标签名称已存在');
            }
        }

        $tag->save([
            'name' => $params['name'] ?? $tag->name,
            'type' => $params['type'] ?? $tag->type,
            'icon' => $params['icon'] ?? $tag->icon,
            'color' => $params['color'] ?? $tag->color,
            'sort' => $params['sort'] ?? $tag->sort,
            'status' => $params['status'] ?? $tag->status,
        ]);

        return $this->success('编辑成功');
    }

    /**
     * @notes 删除标签
     * @return \think\response\Json
     */
    public function delete()
    {
        $id = $this->request->post('id');
        $tag = ReviewTag::find($id);
        if (!$tag) {
            return $this->fail('标签不存在');
        }
        $tag->delete();
        return $this->success('删除成功');
    }

    /**
     * @notes 修改状态
     * @return \think\response\Json
     */
    public function status()
    {
        $id = $this->request->post('id');
        $tag = ReviewTag::find($id);
        if (!$tag) {
            return $this->fail('标签不存在');
        }
        $tag->save(['status' => $tag->status ? 0 : 1]);
        return $this->success('操作成功');
    }

    /**
     * @notes 获取分组标签
     * @return \think\response\Json
     */
    public function grouped()
    {
        $result = ReviewTag::getGroupedList();
        return $this->success('', $result);
    }

    /**
     * @notes 类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $options = [];
        foreach (ReviewTag::getTypeDesc() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $this->success('', $options);
    }
}
