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
    /** 单个敏感词最大长度，与 la_sensitive_word.word varchar(100) 对齐 */
    private const WORD_MAX_LENGTH = 100;

    /** 批量删除单次上限 */
    private const BATCH_DELETE_MAX = 500;

    /** 单次导入最大行数 */
    private const IMPORT_MAX_LINES = 2000;

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
        $id = (int)$this->request->get('id', 0);
        if ($id <= 0) {
            return $this->fail('敏感词ID不能为空');
        }

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

        $word = $this->normalizeWord($params['word'] ?? '');
        if ($word === '') {
            return $this->fail('敏感词不能为空');
        }
        if (mb_strlen($word) > self::WORD_MAX_LENGTH) {
            return $this->fail('敏感词长度不能超过' . self::WORD_MAX_LENGTH . '个字符');
        }

        $replaceWord = $this->normalizeWord($params['replace_word'] ?? '***');
        if (mb_strlen($replaceWord) > self::WORD_MAX_LENGTH) {
            return $this->fail('替换词长度不能超过' . self::WORD_MAX_LENGTH . '个字符');
        }

        $type = $this->normalizeEnum($params['type'] ?? SensitiveWord::TYPE_OTHER, array_keys(SensitiveWord::getTypeDesc()));
        if ($type === null) {
            return $this->fail('敏感词类型不正确');
        }

        $level = $this->normalizeEnum($params['level'] ?? SensitiveWord::LEVEL_WARN, array_keys(SensitiveWord::getLevelDesc()));
        if ($level === null) {
            return $this->fail('敏感词级别不正确');
        }

        $status = $this->normalizeEnum($params['status'] ?? 1, [0, 1]);
        if ($status === null) {
            return $this->fail('敏感词状态不正确');
        }

        if (SensitiveWord::where('word', $word)->find()) {
            return $this->fail('该敏感词已存在');
        }

        SensitiveWord::create([
            'word' => $word,
            'replace_word' => $replaceWord,
            'type' => $type,
            'level' => $level,
            'status' => $status,
            'create_time' => time(),
            'update_time' => time(),
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

        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            return $this->fail('敏感词ID不能为空');
        }

        $word = SensitiveWord::find($id);
        if (!$word) {
            return $this->fail('敏感词不存在');
        }

        $nextWord = $word->word;
        if (array_key_exists('word', $params)) {
            $nextWord = $this->normalizeWord($params['word']);
            if ($nextWord === '') {
                return $this->fail('敏感词不能为空');
            }
        }
        if (mb_strlen((string)$nextWord) > self::WORD_MAX_LENGTH) {
            return $this->fail('敏感词长度不能超过' . self::WORD_MAX_LENGTH . '个字符');
        }

        $nextReplace = $word->replace_word;
        if (array_key_exists('replace_word', $params)) {
            $nextReplace = $this->normalizeWord($params['replace_word']);
        }
        if (mb_strlen((string)$nextReplace) > self::WORD_MAX_LENGTH) {
            return $this->fail('替换词长度不能超过' . self::WORD_MAX_LENGTH . '个字符');
        }

        $nextType = $word->type;
        if (array_key_exists('type', $params)) {
            $nextType = $this->normalizeEnum($params['type'], array_keys(SensitiveWord::getTypeDesc()));
            if ($nextType === null) {
                return $this->fail('敏感词类型不正确');
            }
        }

        $nextLevel = $word->level;
        if (array_key_exists('level', $params)) {
            $nextLevel = $this->normalizeEnum($params['level'], array_keys(SensitiveWord::getLevelDesc()));
            if ($nextLevel === null) {
                return $this->fail('敏感词级别不正确');
            }
        }

        $nextStatus = $word->status;
        if (array_key_exists('status', $params)) {
            $nextStatus = $this->normalizeEnum($params['status'], [0, 1]);
            if ($nextStatus === null) {
                return $this->fail('敏感词状态不正确');
            }
        }

        if ($nextWord !== $word->word) {
            $exists = SensitiveWord::where('word', $nextWord)->where('id', '<>', $id)->find();
            if ($exists) {
                return $this->fail('该敏感词已存在');
            }
        }

        $word->save([
            'word' => $nextWord,
            'replace_word' => $nextReplace,
            'type' => $nextType,
            'level' => $nextLevel,
            'status' => $nextStatus,
            'update_time' => time(),
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
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) {
            return $this->fail('敏感词ID不能为空');
        }

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
        $ids = $this->normalizeIds($this->request->post('ids'));
        if (empty($ids)) {
            return $this->fail('请选择要删除的敏感词');
        }
        if (count($ids) > self::BATCH_DELETE_MAX) {
            return $this->fail('单次最多删除' . self::BATCH_DELETE_MAX . '个敏感词');
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
        $id = (int)$this->request->post('id', 0);
        if ($id <= 0) {
            return $this->fail('敏感词ID不能为空');
        }

        $word = SensitiveWord::find($id);
        if (!$word) {
            return $this->fail('敏感词不存在');
        }
        $word->save(['status' => $word->status ? 0 : 1, 'update_time' => time()]);
        SensitiveWord::clearCache();
        return $this->success('操作成功');
    }

    /**
     * @notes 批量导入
     * @return \think\response\Json
     */
    public function import()
    {
        $content = (string)$this->request->post('content', '');

        $type = $this->normalizeEnum($this->request->post('type', SensitiveWord::TYPE_OTHER), array_keys(SensitiveWord::getTypeDesc()));
        if ($type === null) {
            return $this->fail('敏感词类型不正确');
        }

        $level = $this->normalizeEnum($this->request->post('level', SensitiveWord::LEVEL_WARN), array_keys(SensitiveWord::getLevelDesc()));
        if ($level === null) {
            return $this->fail('敏感词级别不正确');
        }

        if (trim($content) === '') {
            return $this->fail('导入内容不能为空');
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        if (count($lines) > self::IMPORT_MAX_LINES) {
            return $this->fail('单次最多导入' . self::IMPORT_MAX_LINES . '行，请分批导入');
        }

        $words = [];
        foreach ($lines as $line) {
            $line = $this->normalizeWord($line);
            if ($line === '' || mb_strlen($line) > self::WORD_MAX_LENGTH) {
                continue;
            }
            $words[] = $line;
        }
        $words = array_values(array_unique($words));
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
        $content = (string)$this->request->post('content', '');
        if (trim($content) === '') {
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

    /**
     * @notes 清洗敏感词文本
     */
    private function normalizeWord($value): string
    {
        if (!is_scalar($value)) {
            return '';
        }

        // 去掉首尾空白与不可见字符，避免“空格绕过”与脏数据
        return trim(preg_replace('/[\x00-\x1F\x7F]+/u', '', (string)$value) ?? '');
    }

    /**
     * @notes 校验枚举值，非法返回 null
     */
    private function normalizeEnum($value, array $allowed): ?int
    {
        if (!is_scalar($value) || !is_numeric($value)) {
            return null;
        }

        $value = (int)$value;

        return in_array($value, array_map('intval', $allowed), true) ? $value : null;
    }

    /**
     * @notes 规整批量操作ID
     * @return array<int>
     */
    private function normalizeIds($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $id) {
            if (!is_scalar($id) || !is_numeric($id)) {
                continue;
            }
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
