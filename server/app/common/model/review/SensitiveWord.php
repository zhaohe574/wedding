<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 敏感词模型
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\model\review;

use app\common\model\BaseModel;
use think\facade\Cache;

/**
 * 敏感词模型
 * Class SensitiveWord
 * @package app\common\model\review
 */
class SensitiveWord extends BaseModel
{
    protected $name = 'sensitive_word';

    // 敏感词类型
    const TYPE_AD = 1;          // 广告
    const TYPE_ILLEGAL = 2;     // 违法
    const TYPE_POLITICAL = 3;   // 政治
    const TYPE_PORN = 4;        // 色情
    const TYPE_OTHER = 5;       // 其他

    // 敏感词级别
    const LEVEL_WARN = 1;       // 警告
    const LEVEL_FORBID = 2;     // 禁止

    // 缓存键
    const CACHE_KEY = 'sensitive_words';
    const CACHE_TTL = 3600; // 缓存1小时

    /**
     * @notes 类型描述
     * @param bool $value
     * @return array|string
     */
    public static function getTypeDesc($value = true)
    {
        $data = [
            self::TYPE_AD => '广告',
            self::TYPE_ILLEGAL => '违法',
            self::TYPE_POLITICAL => '政治',
            self::TYPE_PORN => '色情',
            self::TYPE_OTHER => '其他',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 级别描述
     * @param bool $value
     * @return array|string
     */
    public static function getLevelDesc($value = true)
    {
        $data = [
            self::LEVEL_WARN => '警告',
            self::LEVEL_FORBID => '禁止',
        ];
        if ($value === true) {
            return $data;
        }
        return $data[$value] ?? '';
    }

    /**
     * @notes 类型文本获取器
     */
    public function getTypeTextAttr($value, $data)
    {
        return self::getTypeDesc($data['type'] ?? 1);
    }

    /**
     * @notes 级别文本获取器
     */
    public function getLevelTextAttr($value, $data)
    {
        return self::getLevelDesc($data['level'] ?? 1);
    }

    /**
     * @notes 获取所有敏感词列表（带缓存）
     * @return array
     */
    public static function getWordList(): array
    {
        $list = Cache::get(self::CACHE_KEY);
        if ($list === null) {
            $list = self::where('status', 1)
                ->field('word,replace_word,level')
                ->select()
                ->toArray();
            Cache::set(self::CACHE_KEY, $list, self::CACHE_TTL);
        }
        return $list;
    }

    /**
     * @notes 清除缓存
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::delete(self::CACHE_KEY);
    }

    /**
     * @notes 检测并过滤敏感词
     * @param string $content
     * @param bool $replace 是否替换敏感词
     * @return array ['has_sensitive' => bool, 'level' => int, 'words' => array, 'filtered' => string]
     */
    public static function filter(string $content, bool $replace = true): array
    {
        $result = [
            'has_sensitive' => false,
            'level' => 0,
            'words' => [],
            'filtered' => $content,
        ];

        $wordList = self::getWordList();
        if (empty($wordList)) {
            return $result;
        }

        $foundWords = [];
        $maxLevel = 0;
        $filtered = $content;

        foreach ($wordList as $item) {
            if (mb_stripos($content, $item['word']) !== false) {
                $foundWords[] = $item['word'];
                $maxLevel = max($maxLevel, $item['level']);
                if ($replace) {
                    $filtered = str_ireplace($item['word'], $item['replace_word'], $filtered);
                }
            }
        }

        if (!empty($foundWords)) {
            $result['has_sensitive'] = true;
            $result['level'] = $maxLevel;
            $result['words'] = $foundWords;
            $result['filtered'] = $filtered;
        }

        return $result;
    }

    /**
     * @notes 检测内容是否包含禁止级别的敏感词
     * @param string $content
     * @return bool
     */
    public static function hasForbiddenWord(string $content): bool
    {
        $result = self::filter($content, false);
        return $result['has_sensitive'] && $result['level'] >= self::LEVEL_FORBID;
    }

    /**
     * @notes 批量导入敏感词
     * @param array $words ['word' => 'xxx', 'type' => 1, 'level' => 1]
     * @return int 导入数量
     */
    public static function batchImport(array $words): int
    {
        $count = 0;
        foreach ($words as $word) {
            if (empty($word['word'])) {
                continue;
            }
            $exists = self::where('word', $word['word'])->find();
            if (!$exists) {
                self::create([
                    'word' => $word['word'],
                    'replace_word' => $word['replace_word'] ?? '***',
                    'type' => $word['type'] ?? self::TYPE_OTHER,
                    'level' => $word['level'] ?? self::LEVEL_WARN,
                    'status' => 1,
                ]);
                $count++;
            }
        }
        self::clearCache();
        return $count;
    }
}
