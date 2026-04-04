<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

/**
 * 地区数据服务
 */
class RegionDataService
{
    private static bool $loaded = false;

    private static array $provinceMap = [];
    private static array $cityMap = [];
    private static array $districtMap = [];
    private static array $districtByCity = [];

    /**
     * @notes 获取全部城市选项
     * @return array
     */
    public static function getCityOptions(): array
    {
        self::load();

        $result = [];
        foreach (self::$cityMap as $city) {
            $result[] = [
                'province_code' => $city['province_code'],
                'province_name' => $city['province_name'],
                'city_code' => $city['city_code'],
                'city_name' => $city['city_name'],
                'label' => $city['province_name'] . ' / ' . $city['city_name'],
            ];
        }

        return array_values($result);
    }

    /**
     * @notes 获取指定城市下的区县选项
     * @param string $cityCode
     * @return array
     */
    public static function getDistrictOptions(string $cityCode): array
    {
        self::load();
        $cityCode = trim($cityCode);
        if ($cityCode === '') {
            return [];
        }

        return array_values(self::$districtByCity[$cityCode] ?? []);
    }

    /**
     * @notes 获取服务树
     * @param array $enabledCities
     * @return array
     */
    public static function getServiceTree(array $enabledCities): array
    {
        self::load();

        $tree = [];
        foreach ($enabledCities as $enabledCity) {
            $cityCode = trim((string)($enabledCity['city_code'] ?? ''));
            if ($cityCode === '' || !isset(self::$cityMap[$cityCode])) {
                continue;
            }

            $city = self::$cityMap[$cityCode];
            $provinceCode = $city['province_code'];
            if (!isset($tree[$provinceCode])) {
                $tree[$provinceCode] = [
                    'province_code' => $provinceCode,
                    'province_name' => $city['province_name'],
                    'cities' => [],
                ];
            }

            $tree[$provinceCode]['cities'][] = [
                'province_code' => $city['province_code'],
                'province_name' => $city['province_name'],
                'city_code' => $city['city_code'],
                'city_name' => $city['city_name'],
                'districts' => self::getDistrictOptions($cityCode),
            ];
        }

        return array_values($tree);
    }

    /**
     * @notes 补齐地区上下文
     * @param array $context
     * @return array
     */
    public static function fillRegionContext(array $context): array
    {
        self::load();

        $provinceCode = trim((string)($context['province_code'] ?? ''));
        $provinceName = trim((string)($context['province_name'] ?? ''));
        $cityCode = trim((string)($context['city_code'] ?? ''));
        $cityName = trim((string)($context['city_name'] ?? ''));
        $districtCode = trim((string)($context['district_code'] ?? ''));
        $districtName = trim((string)($context['district_name'] ?? ''));

        if ($cityCode !== '' && isset(self::$cityMap[$cityCode])) {
            $city = self::$cityMap[$cityCode];
            $provinceCode = $provinceCode ?: $city['province_code'];
            $provinceName = $provinceName ?: $city['province_name'];
            $cityName = $cityName ?: $city['city_name'];
        }

        if ($districtCode !== '' && isset(self::$districtMap[$districtCode])) {
            $district = self::$districtMap[$districtCode];
            $provinceCode = $provinceCode ?: $district['province_code'];
            $provinceName = $provinceName ?: $district['province_name'];
            $cityCode = $cityCode ?: $district['city_code'];
            $cityName = $cityName ?: $district['city_name'];
            $districtName = $districtName ?: $district['district_name'];
        }

        if ($provinceCode !== '' && $provinceName === '' && isset(self::$provinceMap[$provinceCode])) {
            $provinceName = self::$provinceMap[$provinceCode];
        }

        return [
            'province_code' => $provinceCode,
            'province_name' => $provinceName,
            'city_code' => $cityCode,
            'city_name' => $cityName,
            'district_code' => $districtCode,
            'district_name' => $districtName,
        ];
    }

    /**
     * @notes 判断区县是否属于城市
     * @param string $districtCode
     * @param string $cityCode
     * @return bool
     */
    public static function isDistrictInCity(string $districtCode, string $cityCode): bool
    {
        self::load();
        $districtCode = trim($districtCode);
        $cityCode = trim($cityCode);

        if ($districtCode === '' || $cityCode === '') {
            return false;
        }

        return isset(self::$districtMap[$districtCode])
            && (string)self::$districtMap[$districtCode]['city_code'] === $cityCode;
    }

    /**
     * @notes 获取城市信息
     * @param string $cityCode
     * @return array
     */
    public static function getCityInfo(string $cityCode): array
    {
        self::load();
        return self::$cityMap[trim($cityCode)] ?? [];
    }

    /**
     * @notes 获取区县信息
     * @param string $districtCode
     * @return array
     */
    public static function getDistrictInfo(string $districtCode): array
    {
        self::load();
        return self::$districtMap[trim($districtCode)] ?? [];
    }

    /**
     * @notes 加载地区数据
     * @return void
     */
    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }

        $addressDir = self::resolveAddressDir();

        $provinceList = self::readJsonFile($addressDir . DIRECTORY_SEPARATOR . 'provinces.json', '省份数据');
        $cityMatrix = self::readJsonFile($addressDir . DIRECTORY_SEPARATOR . 'citys.json', '城市数据');
        $districtMatrix = self::readJsonFile($addressDir . DIRECTORY_SEPARATOR . 'areas.json', '区县数据');

        foreach ($provinceList as $provinceIndex => $province) {
            $provinceCode = trim((string)($province['code'] ?? ''));
            $provinceName = trim((string)($province['name'] ?? ''));
            if ($provinceCode === '') {
                continue;
            }
            self::$provinceMap[$provinceCode] = $provinceName;

            $cityList = $cityMatrix[$provinceIndex] ?? [];
            foreach ($cityList as $cityIndex => $city) {
                $cityCode = trim((string)($city['code'] ?? ''));
                $cityName = trim((string)($city['name'] ?? ''));
                if ($cityCode === '') {
                    continue;
                }

                self::$cityMap[$cityCode] = [
                    'province_code' => $provinceCode,
                    'province_name' => $provinceName,
                    'city_code' => $cityCode,
                    'city_name' => $cityName,
                ];

                self::$districtByCity[$cityCode] = [];
                $districtList = $districtMatrix[$provinceIndex][$cityIndex] ?? [];
                foreach ($districtList as $district) {
                    $districtCode = trim((string)($district['code'] ?? ''));
                    $districtName = trim((string)($district['name'] ?? ''));
                    if ($districtCode === '') {
                        continue;
                    }

                    $districtInfo = [
                        'province_code' => $provinceCode,
                        'province_name' => $provinceName,
                        'city_code' => $cityCode,
                        'city_name' => $cityName,
                        'district_code' => $districtCode,
                        'district_name' => $districtName,
                    ];
                    self::$districtMap[$districtCode] = $districtInfo;
                    self::$districtByCity[$cityCode][] = $districtInfo;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * @notes 解析地区数据目录
     * @return string
     */
    private static function resolveAddressDir(): string
    {
        $requiredFiles = ['provinces.json', 'citys.json', 'areas.json'];
        $candidateDirs = [
            rtrim(public_path('static/address'), DIRECTORY_SEPARATOR),
            rtrim(root_path() . '../uniapp/src/uni_modules/vk-uview-ui/libs/address', DIRECTORY_SEPARATOR),
        ];

        $checkedDirs = [];
        foreach ($candidateDirs as $candidateDir) {
            $addressDir = realpath($candidateDir) ?: $candidateDir;
            if (!is_dir($addressDir)) {
                $checkedDirs[] = $addressDir . '（目录不存在）';
                continue;
            }

            $missingFiles = [];
            foreach ($requiredFiles as $fileName) {
                if (!is_file($addressDir . DIRECTORY_SEPARATOR . $fileName)) {
                    $missingFiles[] = $fileName;
                }
            }

            if ($missingFiles === []) {
                return $addressDir;
            }

            $checkedDirs[] = $addressDir . '（缺少：' . implode('、', $missingFiles) . '）';
        }

        throw new \RuntimeException(
            '地区数据文件不存在，请将 provinces.json、citys.json、areas.json 放到 public/static/address（项目路径：server/public/static/address）目录下。已检查：'
            . implode('；', $checkedDirs)
        );
    }

    /**
     * @notes 读取地区 JSON 文件
     * @param string $filePath
     * @param string $label
     * @return array
     */
    private static function readJsonFile(string $filePath, string $label): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException($label . '读取失败：' . $filePath);
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new \RuntimeException($label . '格式错误：' . $filePath);
        }

        return $data;
    }
}
