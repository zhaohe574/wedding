<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 敏感词管理控制器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\adminapi\controller\review;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\review\SensitiveWordLists;
use app\common\model\review\SensitiveWord;

/**
 * 敏感词管理控制器
 * Class SensitiveWordController
 * @package app\adminapi\controller\review
 */
class SensitiveWordController extends BaseAdminController
{
    /**
     * @notes 敏感词列表
     * @return \think\response\Json
     */
    public function lists()
    {
        return $this->dataLists(new SensitiveWordLists());
    }

    /**
     * @notes 敏感词详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = $this->request->get('id');
        $word = SensitiveWord::find($id);
        if (!$word) {
            return $this->fail('敏感词不存在');
        }
        
        $data = $word->toArray();
        $data['type_text'] = SensitiveWord::getTypeDesc($data['type']);
        $data['level_text'] = SensitiveWord::getLevelDesc($data['level']);
        
        return $this->success('', $data);
    }

    /**
     * @notes 添加敏感词
     * @return \think\response\Json
     */
    public function add()
    {
        $params = $this->request->post();
        
        if (empty($params['word'])) {
            return $this->fail('敏感词不能为空');
        }

        $exists = SensitiveWord::where('word', $params['word'])->find();
        if ($exists) {
            return $this->fail('该敏感词已存在');
        }

        SensitiveWord::create([
            'word' => $params['word'],
            'replace_word' => $params['replace_word'] ?? '***',
            'type' => $params['type'] ?? SensitiveWord::TYPE_OTHER,
            'level' => $params['level'] ?? SensitiveWord::LEVEL_WARN,
            'status' => $params['status'] ?? 1,
        ]);

        SensitiveWord::clearCache();

        return $this->success('添加成功');
    }

    /**
     * @notes 编辑敏感词
     * @return \think\response\Json
     */
    public function edit()
    {
        $params = $this->request->post();
        
        if (empty($params['id'])) {
            return $this->fail('敏感词ID不能为空');
        }

        $word = SensitiveWord::find($params['id']);
        if (!$word) {
            return $this->fail('敏感词不存在');
        }

        if (!empty($params['word'])) {
            $exists = SensitiveWord::where('word', $params['word'])
                ->where('id', '<>', $params['id'])
                ->find();
            if ($exists) {
                return $this->fail('该敏感词已存在');
            }
        }

        $word->save([
            'word' => $params['word'] ?? $word->word,
            'replace_word' => $params['replace_word'] ?? $word->replace_word,
            'type' => $params['type'] ?? $word->type,
            'level' => $params['level'] ?? $word->level,
            'status' => $params['status'] ?? $word->status,
        ]);

        SensitiveWord::clearCache();

        return $this->success('编辑成功');
    }

    /**
     * @notes 删除敏感词
     * @return \think\response\Json
     */
    public function delete()
    {
        $id = $this->request->post('id');
        $word = SensitiveWord::find($id);
        if (!$word) {
            return $this->fail('敏感词不存在');
        }
        $word->delete();
        SensitiveWord::clearCache();
        return $this->success('删除成功');
    }

    /**
     * @notes 批量删除
     * @return \think\response\Json
     */
    public function batchDelete()
    {
        $ids = $this->request->post('ids');
        if (empty($ids) || !is_array($ids)) {
            return $this->fail('请选择要删除的敏感词');
        }
        SensitiveWord::whereIn('id', $ids)->delete();
        SensitiveWord::clearCache();
        return $this->success('删除成功');
    }

    /**
     * @notes 修改状态
     * @return \think\response\Json
     */
    public function status()
    {
        $id = $this->request->post('id');
        $word = SensitiveWord::find($id);
        if (!$word) {
            return $this->fail('敏感词不存在');
        }
        $word->save(['status' => $word->status ? 0 : 1]);
        SensitiveWord::clearCache();
        return $this->success('操作成功');
    }

    /**
     * @notes 批量导入
     * @return \think\response\Json
     */
    public function import()
    {
        $content = $this->request->post('content');
        $type = $this->request->post('type', SensitiveWord::TYPE_OTHER);
        $level = $this->request->post('level', SensitiveWord::LEVEL_WARN);
        
        if (empty($content)) {
            return $this->fail('导入内容不能为空');
        }

        // 按行分割
        $words = array_filter(array_map('trim', explode("\n", $content)));
        if (empty($words)) {
            return $this->fail('没有有效的敏感词');
        }

        $data = [];
        foreach ($words as $word) {
            $data[] = [
                'word' => $word,
                'type' => $type,
                'level' => $level,
            ];
        }

        $count = SensitiveWord::batchImport($data);
        
        return $this->success("成功导入 {$count} 个敏感词");
    }

    /**
     * @notes 检测内容
     * @return \think\response\Json
     */
    public function check()
    {
        $content = $this->request->post('content');
        if (empty($content)) {
            return $this->fail('内容不能为空');
        }

        $result = SensitiveWord::filter($content, true);
        
        return $this->success('', $result);
    }

    /**
     * @notes 类型选项
     * @return \think\response\Json
     */
    public function typeOptions()
    {
        $options = [];
        foreach (SensitiveWord::getTypeDesc() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $this->success('', $options);
    }

    /**
     * @notes 级别选项
     * @return \think\response\Json
     */
    public function levelOptions()
    {
        $options = [];
        foreach (SensitiveWord::getLevelDesc() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $this->success('', $options);
    }
}
