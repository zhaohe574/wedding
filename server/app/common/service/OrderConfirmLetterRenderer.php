<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 订单确认函 SVG 渲染器
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

class OrderConfirmLetterRenderer
{
    public const FONT_FAMILY_SANS = 'Noto Sans SC, PingFang SC, Microsoft YaHei, sans-serif';
    public const FONT_FAMILY_SERIF = 'Noto Serif SC, Georgia, Times New Roman, serif';
    protected static array $fontOptions = [];

    protected const DEFAULT_TITLE = '订单确认函';
    protected const DEFAULT_HERO_EYEBROW = 'ORDER CONFIRMATION LETTER';
    protected const DEFAULT_HERO_DESC = '为保证婚礼现场执行准确无误，系统已根据当前订单信息自动生成本次正式确认函。';
    protected const DEFAULT_BRAND_NAME = '喜遇婚礼服务';
    protected const DEFAULT_FOOTER_NOTE = '请保存此确认函图片，作为婚礼服务安排与付款确认的纸本凭证。';
    protected const V3_DEFAULT_HERO_EYEBROW = 'MAISON DE MARIAGE · CONFIRMATION';
    protected const V3_DEFAULT_SUBTITLE = 'Wedding Order Confirmation';
    protected const V3_DEFAULT_HERO_DESC = '以法式纸本礼仪的方式，确认本次婚礼档期、服务内容与付款安排。';
    protected const V3_FOOTER_KICKER = 'Avec amour et promesse.';
    protected const V4_DEFAULT_SUBTITLE = 'Wedding Order Confirmation';

    public static function render(array $snapshot, array $options = []): string
    {
        $renderSpecVersion = (string) ($options['render_spec_version'] ?? $options['renderSpecVersion'] ?? 'v1');
        $small = (bool) ($options['small'] ?? false);
        self::$fontOptions = is_array($options['font_options'] ?? null) ? $options['font_options'] : [];

        if (self::isV4Spec($renderSpecVersion)) {
            return self::renderV4OrderConfirmLetterSvg($snapshot, $small);
        }

        if (self::isV3Spec($renderSpecVersion)) {
            return self::renderV3OrderConfirmLetterSvg($snapshot, $small);
        }

        return self::isV2Spec($renderSpecVersion)
            ? self::renderV2OrderConfirmLetterSvg($snapshot, $small)
            : self::renderV1OrderConfirmLetterSvg($snapshot, $small);
    }

    protected static function isV3Spec(string $renderSpecVersion): bool
    {
        return str_starts_with(strtolower(trim($renderSpecVersion)), 'v3');
    }

    protected static function isV4Spec(string $renderSpecVersion): bool
    {
        return str_starts_with(strtolower(trim($renderSpecVersion)), 'v4');
    }

    protected static function isV2Spec(string $renderSpecVersion): bool
    {
        return str_starts_with(strtolower(trim($renderSpecVersion)), 'v2');
    }

    protected static function toText($value): string
    {
        return trim((string) ($value ?? ''));
    }

    protected static function toStringArray($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            $text = self::toText($item);
            if ($text !== '') {
                $result[] = $text;
            }
        }

        return $result;
    }

    protected static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    protected static function getTextFontAttr(?string $fontFamily = null): string
    {
        $resolvedFamily = self::resolveFontFamily($fontFamily ?: self::FONT_FAMILY_SANS);
        return ' font-family="' . self::escapeXml($resolvedFamily) . '"';
    }

    protected static function resolveFontFamily(string $fontFamily): string
    {
        $sansFamily = trim((string) (self::$fontOptions['sans_family'] ?? ''));
        $serifFamily = trim((string) (self::$fontOptions['serif_family'] ?? ''));
        if ($fontFamily === self::FONT_FAMILY_SANS && $sansFamily !== '') {
            return $sansFamily . ', ' . self::FONT_FAMILY_SANS;
        }
        if ($fontFamily === self::FONT_FAMILY_SERIF && $serifFamily !== '') {
            return $serifFamily . ', ' . self::FONT_FAMILY_SERIF;
        }
        return $fontFamily;
    }

    protected static function buildFontFaceDefs(): string
    {
        $defs = '';
        foreach ([
            'sans' => 'sans_path',
            'serif' => 'serif_path',
        ] as $type => $pathKey) {
            $family = trim((string) (self::$fontOptions[$type . '_family'] ?? ''));
            $path = trim((string) (self::$fontOptions[$pathKey] ?? ''));
            if ($family === '' || $path === '' || !is_file($path)) {
                continue;
            }
            $format = strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) === 'otf'
                ? 'opentype'
                : 'truetype';
            $defs .= sprintf(
                '<style type="text/css"><![CDATA[@font-face{font-family:"%s";src:url("%s") format("%s");font-weight:100 900;font-style:normal;}]]></style>',
                self::escapeXml($family),
                self::formatFontFileUri($path),
                $format
            );
        }
        return $defs;
    }

    protected static function formatFontFileUri(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);
        if (preg_match('/^[a-zA-Z]:\//', $normalized) === 1) {
            return 'file:///' . $normalized;
        }
        return 'file://' . $normalized;
    }

    protected static function strLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    protected static function strSlice(string $value, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return $length === null
                ? mb_substr($value, $start, null, 'UTF-8')
                : mb_substr($value, $start, $length, 'UTF-8');
        }

        return $length === null
            ? substr($value, $start)
            : substr($value, $start, $length);
    }

    protected static function wrapText(string $text, int $maxCharsPerLine, int $maxLines): array
    {
        $source = str_replace(["\r\n", "\r"], "\n", self::toText($text));
        if ($source === '') {
            return [''];
        }

        $lines = [];
        $truncated = false;

        foreach (explode("\n", $source) as $segment) {
            $remain = trim($segment);
            if ($remain === '') {
                $lines[] = '';
                if (count($lines) >= $maxLines) {
                    $truncated = true;
                    break;
                }
                continue;
            }

            while (self::strLength($remain) > $maxCharsPerLine) {
                $lines[] = self::strSlice($remain, 0, $maxCharsPerLine);
                $remain = self::strSlice($remain, $maxCharsPerLine);
                if (count($lines) >= $maxLines) {
                    $truncated = true;
                    break;
                }
            }

            if ($truncated) {
                break;
            }

            $lines[] = $remain;
            if (count($lines) >= $maxLines) {
                $truncated = true;
                break;
            }
        }

        $visibleLines = array_slice($lines, 0, $maxLines);

        return !empty($visibleLines) ? $visibleLines : [''];
    }

    protected static function fitTextLine(string $text, int $maxCharsPerLine, float $baseFontSize, float $minFontSize): array
    {
        $text = self::toText($text);
        $length = max(self::strLength($text), 1);
        if ($length <= $maxCharsPerLine) {
            return [
                'lines' => [$text],
                'fontSize' => $baseFontSize,
                'lineHeight' => $baseFontSize,
            ];
        }

        $fontSize = max($minFontSize, $baseFontSize * $maxCharsPerLine / $length);
        return [
            'lines' => [$text],
            'fontSize' => $fontSize,
            'lineHeight' => $baseFontSize,
        ];
    }

    protected static function drawTextBlock(array $options): array
    {
        $lines = $options['lines'] ?? [''];
        if (empty($lines)) {
            $lines = [''];
        }

        $fontWeight = $options['fontWeight'] ?? 500;
        $textAnchor = $options['textAnchor'] ?? 'start';
        $fontSize = (float) $options['fontSize'];
        $lineHeight = (float) $options['lineHeight'];
        $x = (float) $options['x'];
        $y = (float) $options['y'];
        $fill = (string) $options['fill'];
        $fontFamily = (string) ($options['fontFamily'] ?? self::FONT_FAMILY_SANS);
        $letterSpacing = $options['letterSpacing'] ?? null;
        $letterSpacingAttr = $letterSpacing !== null
            ? ' letter-spacing="' . self::escapeXml((string) $letterSpacing) . '"'
            : '';

        $svg = '';
        foreach (array_values($lines) as $index => $line) {
            $svg .= sprintf(
                '<text x="%s" y="%s" text-anchor="%s" font-size="%s" font-weight="%s" fill="%s"%s%s>%s</text>',
                $x,
                $y + $index * $lineHeight,
                $textAnchor,
                $fontSize,
                $fontWeight,
                $fill,
                self::getTextFontAttr($fontFamily),
                $letterSpacingAttr,
                self::escapeXml((string) $line)
            );
        }

        return [
            'svg' => $svg,
            'height' => count($lines) * $lineHeight,
        ];
    }

    protected static function shiftSvgY(string $svg, float $offset): string
    {
        if ($svg === '' || $offset === 0.0) {
            return $svg;
        }

        return (string) preg_replace_callback(
            '/( y=")(-?\d+(?:\.\d+)?)(")/',
            static function (array $matches) use ($offset) {
                return $matches[1] . ((float) $matches[2] + $offset) . $matches[3];
            },
            $svg
        );
    }

    protected static function drawInfoCard(array $options): array
    {
        $x = (float) $options['x'];
        $y = (float) $options['y'];
        $width = (float) $options['width'];
        $title = (string) $options['title'];
        $lines = $options['lines'] ?? [''];
        $fill = (string) $options['fill'];
        $stroke = (string) $options['stroke'];
        $titleColor = (string) $options['titleColor'];
        $bodyColor = (string) $options['bodyColor'];
        $titleSize = (float) $options['titleSize'];
        $bodySize = (float) $options['bodySize'];
        $lineHeight = (float) $options['lineHeight'];
        $paddingX = (float) $options['paddingX'];
        $paddingY = (float) $options['paddingY'];
        $gap = (float) $options['gap'];
        $radius = (float) $options['radius'];

        $innerX = $x + $paddingX;
        $innerY = $paddingY + $titleSize;
        $titleBlock = self::drawTextBlock([
            'x' => $innerX,
            'y' => $innerY,
            'lines' => [$title],
            'fontSize' => $titleSize,
            'lineHeight' => $titleSize,
            'fill' => $titleColor,
            'fontWeight' => 700,
        ]);
        $bodyY = $innerY + $titleBlock['height'] + $gap + $bodySize;
        $bodyBlock = self::drawTextBlock([
            'x' => $innerX,
            'y' => $bodyY,
            'lines' => $lines,
            'fontSize' => $bodySize,
            'lineHeight' => $lineHeight,
            'fill' => $bodyColor,
            'fontWeight' => 600,
        ]);
        $height = $paddingY * 2 + $titleBlock['height'] + $gap + $bodyBlock['height'];

        return [
            'svg' => sprintf(
                '<g transform="translate(0 %s)"><rect x="%s" y="0" width="%s" height="%s" rx="%s" fill="%s" stroke="%s" />%s%s</g>',
                $y,
                $x,
                $width,
                $height,
                $radius,
                $fill,
                $stroke,
                $titleBlock['svg'],
                $bodyBlock['svg']
            ),
            'height' => $height,
        ];
    }

    protected static function buildV1Rows(array $snapshot): array
    {
        $staffNames = self::toStringArray($snapshot['service_staff_names'] ?? []);

        return [
            '客户名称：' . self::toText($snapshot['customer_name'] ?? ''),
            '日期：' . self::toText($snapshot['service_date'] ?? ''),
            '地点：' . self::toText($snapshot['service_address'] ?? ''),
            '服务人员：' . implode('、', $staffNames),
            '订单总价：¥' . (self::toText($snapshot['order_total_amount'] ?? '') ?: '0.00'),
            (self::toText($snapshot['paid_label'] ?? '') ?: '已付定金') . '：¥' . (self::toText($snapshot['paid_amount'] ?? '') ?: '0.00'),
            '尾款剩余：¥' . (self::toText($snapshot['remain_amount'] ?? '') ?: '0.00'),
            '确认日期：' . self::toText($snapshot['confirm_date'] ?? ''),
            '联系电话：' . self::toText($snapshot['contact_mobile'] ?? ''),
        ];
    }

    protected static function renderV1OrderConfirmLetterSvg(array $snapshot, bool $small): string
    {
        $width = $small ? 540 : 1080;
        $height = $small ? 960 : 1920;
        $padding = $small ? 40 : 80;
        $titleSize = $small ? 28 : 56;
        $textSize = $small ? 18 : 36;
        $amountSize = $small ? 20 : 40;
        $lineHeight = $small ? 32 : 64;
        $y = $small ? 88 : 150;

        $rows = self::buildV1Rows($snapshot);
        $texts = [];
        $texts[] = sprintf(
            '<text x="%s" y="%s" text-anchor="middle" font-size="%s" font-weight="700" fill="#1e2432"%s>%s</text>',
            $width / 2,
            $y,
            $titleSize,
            self::getTextFontAttr(),
            self::escapeXml(self::toText($snapshot['title'] ?? '') ?: self::DEFAULT_TITLE)
        );
        $y += $small ? 56 : 100;

        foreach ($rows as $index => $row) {
            $lines = self::wrapText($row, $small ? 22 : 28, 2);
            $fontSize = ($index >= 4 && $index <= 6) ? $amountSize : $textSize;
            $fontWeight = ($index >= 4 && $index <= 6) ? 700 : 400;
            foreach ($lines as $line) {
                $texts[] = sprintf(
                    '<text x="%s" y="%s" font-size="%s" font-weight="%s" fill="#1e2432"%s>%s</text>',
                    $padding,
                    $y,
                    $fontSize,
                    $fontWeight,
                    self::getTextFontAttr(),
                    self::escapeXml($line)
                );
                $y += $lineHeight;
            }
            $y += $small ? 12 : 20;
        }

        $texts[] = sprintf(
            '<text x="%s" y="%s" font-size="%s" font-weight="600" fill="#1e2432"%s>备注：</text>',
            $padding,
            $y,
            $textSize,
            self::getTextFontAttr()
        );
        $y += $lineHeight;

        foreach (self::wrapText(self::toText($snapshot['remark_content'] ?? ''), $small ? 22 : 28, 6) as $line) {
            $texts[] = sprintf(
                '<text x="%s" y="%s" font-size="%s" fill="#5b6475"%s>%s</text>',
                $padding,
                $y,
                $textSize,
                self::getTextFontAttr(),
                self::escapeXml($line)
            );
            $y += $lineHeight;
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s"><defs>%s</defs><rect width="100%%" height="100%%" fill="#ffffff" rx="24" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
            self::buildFontFaceDefs(),
            implode('', $texts)
        );
    }

    protected static function renderV2OrderConfirmLetterSvg(array $snapshot, bool $small): string
    {
        $width = $small ? 540 : 1080;
        $height = $small ? 960 : 1920;
        $pagePadding = $small ? 24 : 60;
        $paperPaddingX = $small ? 22 : 54;
        $paperPaddingY = $small ? 24 : 54;
        $sectionGap = $small ? 12 : 26;
        $paperX = $pagePadding;
        $paperY = $pagePadding;
        $paperWidth = $width - $pagePadding * 2;
        $paperHeight = $height - $pagePadding * 2;
        $contentX = $paperX + $paperPaddingX;
        $contentWidth = $paperWidth - $paperPaddingX * 2;

        $heroPaddingX = $small ? 22 : 38;
        $heroPaddingY = $small ? 22 : 38;
        $heroEyebrowSize = $small ? 10 : 18;
        $heroTitleSize = $small ? 28 : 54;
        $heroDescSize = $small ? 13 : 24;
        $heroMetaSize = $small ? 12 : 21;
        $heroLineHeight = $small ? 18 : 34;
        $cardTitleSize = $small ? 15 : 22;
        $cardBodySize = $small ? 16 : 24;
        $cardLineHeight = $small ? 24 : 41;
        $cardPaddingX = $small ? 20 : 28;
        $cardPaddingY = $small ? 18 : 24;
        $cardGap = $small ? 10 : 12;
        $cardRadius = $small ? 22 : 28;
        $amountBigSize = $small ? 26 : 48;
        $amountBigLineHeight = $small ? 30 : 52;
        $footerBrandSize = $small ? 11 : 20;
        $footerNoteSize = $small ? 11 : 20;
        $footerNoteLineHeight = $small ? 16 : 30;

        $title = self::toText($snapshot['title'] ?? '') ?: self::DEFAULT_TITLE;
        $heroEyebrow = self::toText($snapshot['brand_tagline'] ?? '') ?: self::DEFAULT_HERO_EYEBROW;

        $heroMetaLines = [];
        if (self::toText($snapshot['order_sn'] ?? '') !== '') {
            $heroMetaLines[] = '订单编号：' . self::toText($snapshot['order_sn'] ?? '');
        }
        if (self::toText($snapshot['confirm_date'] ?? '') !== '') {
            $heroMetaLines[] = '确认日期：' . self::toText($snapshot['confirm_date'] ?? '');
        }

        $weddingLines = [];
        foreach ([
            '客户姓名：' . (self::toText($snapshot['customer_name'] ?? '') ?: '-'),
            '婚礼日期：' . (self::toText($snapshot['service_date_label'] ?? '') ?: (self::toText($snapshot['service_date'] ?? '') ?: '-')),
            '举办地点：' . (self::toText($snapshot['service_address'] ?? '') ?: '-'),
        ] as $line) {
            $weddingLines = array_merge($weddingLines, self::wrapText($line, $small ? 18 : 24, 2));
        }

        $serviceTeamLines = self::toStringArray($snapshot['service_team_lines'] ?? []);
        $staffNames = self::toStringArray($snapshot['service_staff_names'] ?? []);
        if (!empty($serviceTeamLines)) {
            $teamSourceLines = $serviceTeamLines;
        } elseif (!empty($staffNames)) {
            $teamSourceLines = ['服务人员：' . implode('、', $staffNames)];
        } else {
            $teamSourceLines = ['服务人员：待补充'];
        }

        $teamLines = [];
        foreach ($teamSourceLines as $line) {
            $teamLines = array_merge($teamLines, self::wrapText($line, $small ? 18 : 24, 2));
        }
        $teamLines = array_slice($teamLines, 0, $small ? 4 : 5);

        $amountDetailLines = [
            (self::toText($snapshot['paid_label'] ?? '') ?: '已付定金') . '：¥' . (self::toText($snapshot['paid_amount'] ?? '') ?: '0.00'),
            '待付尾款：¥' . (self::toText($snapshot['remain_amount'] ?? '') ?: '0.00'),
        ];

        $remarkLines = ['联系电话：' . (self::toText($snapshot['contact_mobile'] ?? '') ?: '-')];
        $remarkLines = array_merge(
            $remarkLines,
            self::wrapText(
                self::toText($snapshot['remark_content'] ?? '') ?: self::DEFAULT_FOOTER_NOTE,
                $small ? 18 : 24,
                $small ? 4 : 5
            )
        );

        $heroDescLines = self::wrapText(self::DEFAULT_HERO_DESC, $small ? 22 : 30, 2);
        $heroMetaWrappedLines = [];
        foreach (!empty($heroMetaLines) ? $heroMetaLines : ['确认信息待更新'] as $line) {
            $heroMetaWrappedLines = array_merge($heroMetaWrappedLines, self::wrapText($line, $small ? 20 : 30, 1));
        }

        $heroHeight =
            $heroPaddingY * 2 +
            $heroEyebrowSize +
            ($small ? 10 : 18) +
            $heroTitleSize +
            ($small ? 12 : 18) +
            count($heroDescLines) * $heroLineHeight +
            ($small ? 10 : 14) +
            count($heroMetaWrappedLines) * $heroLineHeight;

        $infoCardHeight = $cardPaddingY * 2 + $cardTitleSize + $cardGap + count($weddingLines) * $cardLineHeight;
        $teamCardHeight = $cardPaddingY * 2 + $cardTitleSize + $cardGap + count($teamLines) * $cardLineHeight;
        $remarkCardBodySize = $small ? 15 : 22;
        $remarkCardLineHeight = $small ? 22 : 36;
        $remarkCardHeight =
            $cardPaddingY * 2 + $cardTitleSize + $cardGap + count($remarkLines) * $remarkCardLineHeight;

        $amountTitleBlock = self::drawTextBlock([
            'x' => $contentX + $cardPaddingX,
            'y' => $cardPaddingY + $cardTitleSize,
            'lines' => ['费用确认'],
            'fontSize' => $cardTitleSize,
            'lineHeight' => $cardTitleSize,
            'fill' => '#7F7B78',
            'fontWeight' => 700,
        ]);
        $amountTotalY = $cardPaddingY + $amountTitleBlock['height'] + $cardGap + $amountBigSize;
        $amountTotalBlock = self::drawTextBlock([
            'x' => $contentX + $cardPaddingX,
            'y' => $amountTotalY,
            'lines' => ['合计 ¥' . (self::toText($snapshot['order_total_amount'] ?? '') ?: '0.00')],
            'fontSize' => $amountBigSize,
            'lineHeight' => $amountBigLineHeight,
            'fill' => '#E85A4F',
            'fontWeight' => 700,
        ]);
        $amountDetailsY = $amountTotalY + $amountTotalBlock['height'] + $cardGap + $cardBodySize;
        $amountDetailsBlock = self::drawTextBlock([
            'x' => $contentX + $cardPaddingX,
            'y' => $amountDetailsY,
            'lines' => $amountDetailLines,
            'fontSize' => $cardBodySize,
            'lineHeight' => $cardLineHeight,
            'fill' => '#1E2432',
            'fontWeight' => 600,
        ]);
        $amountCardHeight =
            $cardPaddingY * 2 +
            $amountTitleBlock['height'] +
            $cardGap +
            $amountTotalBlock['height'] +
            $cardGap +
            $amountDetailsBlock['height'];

        $currentY = $paperY + $paperPaddingY;
        $sections = [];
        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="url(#heroGradient)" stroke="#F4C7BF" /><circle cx="%s" cy="%s" r="%s" fill="#FFFFFF" fill-opacity="0.36" /><circle cx="%s" cy="%s" r="%s" fill="#FFFFFF" fill-opacity="0.24" />',
            $contentX,
            $currentY,
            $contentWidth,
            $heroHeight,
            $small ? 28 : 40,
            $contentX + $contentWidth - ($small ? 44 : 90),
            $currentY + ($small ? 42 : 78),
            $small ? 22 : 48,
            $contentX + $contentWidth - ($small ? 86 : 160),
            $currentY + ($small ? 86 : 146),
            $small ? 12 : 24
        );

        $heroTextX = $contentX + $heroPaddingX;
        $heroEyebrowY = $currentY + $heroPaddingY + $heroEyebrowSize;
        $sections[] = self::drawTextBlock([
            'x' => $heroTextX,
            'y' => $heroEyebrowY,
            'lines' => [$heroEyebrow],
            'fontSize' => $heroEyebrowSize,
            'lineHeight' => $heroEyebrowSize,
            'fill' => '#C99B73',
            'fontWeight' => 700,
        ])['svg'];
        $heroTitleY = $heroEyebrowY + ($small ? 18 : 36) + $heroTitleSize;
        $sections[] = self::drawTextBlock([
            'x' => $heroTextX,
            'y' => $heroTitleY,
            'lines' => [$title],
            'fontSize' => $heroTitleSize,
            'lineHeight' => $heroTitleSize,
            'fill' => '#1E2432',
            'fontWeight' => 700,
        ])['svg'];
        $heroDescY = $heroTitleY + ($small ? 16 : 28) + $heroDescSize;
        $sections[] = self::drawTextBlock([
            'x' => $heroTextX,
            'y' => $heroDescY,
            'lines' => $heroDescLines,
            'fontSize' => $heroDescSize,
            'lineHeight' => $heroLineHeight,
            'fill' => '#7F7B78',
            'fontWeight' => 500,
        ])['svg'];
        $heroMetaY = $heroDescY + count($heroDescLines) * $heroLineHeight + ($small ? 8 : 12) + $heroMetaSize;
        $sections[] = self::drawTextBlock([
            'x' => $heroTextX,
            'y' => $heroMetaY,
            'lines' => $heroMetaWrappedLines,
            'fontSize' => $heroMetaSize,
            'lineHeight' => $heroLineHeight,
            'fill' => '#E85A4F',
            'fontWeight' => 700,
        ])['svg'];

        $currentY += $heroHeight + $sectionGap;

        $sections[] = self::drawInfoCard([
            'x' => $contentX,
            'y' => $currentY,
            'width' => $contentWidth,
            'title' => '婚礼信息',
            'lines' => $weddingLines,
            'fill' => '#FFF8F5',
            'stroke' => '#EFE6E1',
            'titleColor' => '#7F7B78',
            'bodyColor' => '#1E2432',
            'titleSize' => $cardTitleSize,
            'bodySize' => $cardBodySize,
            'lineHeight' => $cardLineHeight,
            'paddingX' => $cardPaddingX,
            'paddingY' => $cardPaddingY,
            'gap' => $cardGap,
            'radius' => $cardRadius,
        ])['svg'];
        $currentY += $infoCardHeight + $sectionGap;

        $sections[] = self::drawInfoCard([
            'x' => $contentX,
            'y' => $currentY,
            'width' => $contentWidth,
            'title' => '服务团队',
            'lines' => $teamLines,
            'fill' => '#FFFFFF',
            'stroke' => '#EFE6E1',
            'titleColor' => '#7F7B78',
            'bodyColor' => '#1E2432',
            'titleSize' => $cardTitleSize,
            'bodySize' => $cardBodySize,
            'lineHeight' => $cardLineHeight,
            'paddingX' => $cardPaddingX,
            'paddingY' => $cardPaddingY,
            'gap' => $cardGap,
            'radius' => $cardRadius,
        ])['svg'];
        $currentY += $teamCardHeight + $sectionGap;

        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="#FFFFFF" stroke="#F4C7BF" /><rect x="%s" y="%s" width="%s" height="%s" rx="999" fill="#FFF1EE" /><text x="%s" y="%s" text-anchor="middle" font-size="%s" font-weight="700" fill="#E85A4F"%s>金额重点</text>%s%s%s',
            $contentX,
            $currentY,
            $contentWidth,
            $amountCardHeight,
            $small ? 24 : 32,
            $contentX + $contentWidth - ($small ? 90 : 148),
            $currentY + ($small ? 18 : 22),
            $small ? 62 : 104,
            $small ? 24 : 36,
            $contentX + $contentWidth - ($small ? 59 : 96),
            $currentY + ($small ? 34 : 47),
            $small ? 11 : 18,
            self::getTextFontAttr(),
            self::shiftSvgY((string) $amountTitleBlock['svg'], $currentY),
            self::shiftSvgY((string) $amountTotalBlock['svg'], $currentY),
            self::shiftSvgY((string) $amountDetailsBlock['svg'], $currentY)
        );
        $currentY += $amountCardHeight + $sectionGap;

        $sections[] = self::drawInfoCard([
            'x' => $contentX,
            'y' => $currentY,
            'width' => $contentWidth,
            'title' => '备注与联系',
            'lines' => $remarkLines,
            'fill' => '#FFF8F5',
            'stroke' => '#EFE6E1',
            'titleColor' => '#7F7B78',
            'bodyColor' => '#1E2432',
            'titleSize' => $cardTitleSize,
            'bodySize' => $remarkCardBodySize,
            'lineHeight' => $remarkCardLineHeight,
            'paddingX' => $cardPaddingX,
            'paddingY' => $cardPaddingY,
            'gap' => $cardGap,
            'radius' => $cardRadius,
        ])['svg'];
        $currentY += $remarkCardHeight + $sectionGap;

        $footerLineY = $currentY;
        $footerBrandY = $footerLineY + ($small ? 24 : 38);
        $footerNoteLines = self::wrapText(
            self::toText($snapshot['footer_note'] ?? '') ?: self::DEFAULT_FOOTER_NOTE,
            $small ? 24 : 30,
            2
        );
        $footerNoteBlock = self::drawTextBlock([
            'x' => $paperX + $paperWidth / 2,
            'y' => $footerBrandY + ($small ? 22 : 36),
            'lines' => $footerNoteLines,
            'fontSize' => $footerNoteSize,
            'lineHeight' => $footerNoteLineHeight,
            'fill' => '#7F7B78',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
        ]);

        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="1" fill="#F1E4DD" /><text x="%s" y="%s" text-anchor="middle" font-size="%s" font-weight="700" letter-spacing="%s" fill="#C99B73"%s>%s</text>%s',
            $contentX,
            $footerLineY,
            $contentWidth,
            $paperX + $paperWidth / 2,
            $footerBrandY,
            $footerBrandSize,
            $small ? 0.8 : 1.2,
            self::getTextFontAttr(),
            self::escapeXml(self::toText($snapshot['brand_name'] ?? '') ?: self::DEFAULT_BRAND_NAME),
            $footerNoteBlock['svg']
        );

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s"><defs>%s<linearGradient id="pageGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FCFBF9" /><stop offset="100%%" stop-color="#FFF4EF" /></linearGradient><linearGradient id="heroGradient" x1="0" y1="0" x2="1" y2="1"><stop offset="0%%" stop-color="#FFF7F4" /><stop offset="100%%" stop-color="#FDE9E2" /></linearGradient><filter id="paperShadow" x="-20%%" y="-20%%" width="140%%" height="160%%"><feDropShadow dx="0" dy="24" stdDeviation="18" flood-color="#DAB5A6" flood-opacity="0.18" /></filter></defs><rect width="100%%" height="100%%" fill="url(#pageGradient)" /><rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="#FFFDFB" stroke="#EFE6E1" stroke-width="2" filter="url(#paperShadow)" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
            self::buildFontFaceDefs(),
            $paperX,
            $paperY,
            $paperWidth,
            $paperHeight,
            $small ? 28 : 48,
            implode('', $sections)
        );
    }

    protected static function renderV3OrderConfirmLetterSvg(array $snapshot, bool $small): string
    {
        $width = $small ? 540 : 1080;
        $height = $small ? 960 : 1920;
        $pagePadding = $small ? 24 : 60;
        $paperX = $pagePadding;
        $paperY = $pagePadding;
        $paperWidth = $width - $pagePadding * 2;
        $paperHeight = $height - $pagePadding * 2;
        $paperPaddingX = $small ? 34 : 74;
        $paperPaddingY = $small ? 34 : 72;
        $contentX = $paperX + $paperPaddingX;
        $contentWidth = $paperWidth - $paperPaddingX * 2;
        $sectionGap = $small ? 16 : 34;

        $title = self::toText($snapshot['title'] ?? '') ?: self::DEFAULT_TITLE;
        $heroEyebrow = self::toText($snapshot['brand_tagline'] ?? '') ?: self::V3_DEFAULT_HERO_EYEBROW;
        $heroSubtitle = self::V3_DEFAULT_SUBTITLE;
        $heroDescLines = self::wrapText(
            self::toText($snapshot['hero_desc'] ?? '') ?: self::V3_DEFAULT_HERO_DESC,
            $small ? 24 : 38,
            2
        );

        $heroMetaPrimaryParts = array_values(array_filter([
            self::toText($snapshot['customer_name'] ?? ''),
            self::toText($snapshot['service_date_label'] ?? '') ?: self::toText($snapshot['service_date'] ?? ''),
            self::toText($snapshot['service_address'] ?? ''),
        ]));
        $heroMetaSecondaryParts = array_values(array_filter([
            self::toText($snapshot['order_sn'] ?? '') !== '' ? '订单编号：' . self::toText($snapshot['order_sn'] ?? '') : '',
            self::toText($snapshot['confirm_date'] ?? '') !== '' ? '确认日期：' . self::toText($snapshot['confirm_date'] ?? '') : '',
        ]));
        $heroMetaLines = [];
        if (!empty($heroMetaPrimaryParts)) {
            $heroMetaLines = array_merge(
                $heroMetaLines,
                self::wrapText(implode(' · ', $heroMetaPrimaryParts), $small ? 28 : 42, 1)
            );
        }
        if (!empty($heroMetaSecondaryParts)) {
            $heroMetaLines = array_merge(
                $heroMetaLines,
                self::wrapText(implode(' · ', $heroMetaSecondaryParts), $small ? 26 : 38, 1)
            );
        }
        if (empty($heroMetaLines)) {
            $heroMetaLines = ['确认信息待更新'];
        }

        $leftInfoLines = [];
        foreach ([
            '新人：' . (self::toText($snapshot['customer_name'] ?? '') ?: '-'),
            '婚礼日期：' . (self::toText($snapshot['service_date_label'] ?? '') ?: (self::toText($snapshot['service_date'] ?? '') ?: '-')),
            '举办地点：' . (self::toText($snapshot['service_address'] ?? '') ?: '-'),
        ] as $line) {
            $leftInfoLines = array_merge($leftInfoLines, self::wrapText($line, $small ? 16 : 23, 2));
        }

        $serviceTeamLines = self::toStringArray($snapshot['service_team_lines'] ?? []);
        $staffNames = self::toStringArray($snapshot['service_staff_names'] ?? []);
        $rightInfoSource = !empty($serviceTeamLines)
            ? array_slice($serviceTeamLines, 0, $small ? 3 : 4)
            : (!empty($staffNames) ? ['服务团队：' . implode('、', $staffNames)] : ['服务团队：待补充']);
        $rightInfoLines = [];
        foreach ($rightInfoSource as $line) {
            $rightInfoLines = array_merge($rightInfoLines, self::wrapText($line, $small ? 16 : 23, 2));
        }
        $rightInfoLines = array_slice($rightInfoLines, 0, $small ? 5 : 6);

        $amountDetailLines = [
            (self::toText($snapshot['paid_label'] ?? '') ?: '已付定金') . '：¥' . (self::toText($snapshot['paid_amount'] ?? '') ?: '0.00'),
            '待付尾款：¥' . (self::toText($snapshot['remain_amount'] ?? '') ?: '0.00'),
            '支付节点：婚礼前 3 日',
        ];

        $acknowledgementLines = self::wrapText(
            self::toText($snapshot['remark_content'] ?? '') ?: self::DEFAULT_FOOTER_NOTE,
            $small ? 18 : 28,
            $small ? 4 : 4
        );
        $contactLines = [
            '联系电话：' . (self::toText($snapshot['contact_mobile'] ?? '') ?: '-'),
            '确认日期：' . (self::toText($snapshot['confirm_date'] ?? '') ?: '-'),
            '当前版本：婚礼确认函',
        ];

        $heroPaddingTop = $small ? 18 : 24;
        $heroPaddingBottom = $small ? 20 : 26;
        $sealSize = $small ? 44 : 84;
        $sealInnerSize = $small ? 34 : 64;
        $heroEyebrowSize = $small ? 9 : 16;
        $heroTitleSize = $small ? 30 : 54;
        $heroSubtitleSize = $small ? 16 : 28;
        $heroDescSize = $small ? 13 : 22;
        $heroMetaSize = $small ? 11 : 20;
        $heroDescLineHeight = $small ? 18 : 32;
        $heroMetaLineHeight = $small ? 16 : 26;
        $heroHeight =
            $heroPaddingTop +
            $heroEyebrowSize +
            ($small ? 12 : 16) +
            $sealSize +
            ($small ? 12 : 16) +
            $heroTitleSize +
            ($small ? 8 : 10) +
            $heroSubtitleSize +
            ($small ? 10 : 14) +
            count($heroDescLines) * $heroDescLineHeight +
            ($small ? 10 : 14) +
            count($heroMetaLines) * $heroMetaLineHeight +
            $heroPaddingBottom;

        $sectionLabelSize = $small ? 9 : 16;
        $sectionLabelGap = $small ? 10 : 14;
        $sectionBodySize = $small ? 14 : 24;
        $sectionBodyLineHeight = $small ? 20 : 36;
        $infoSectionPaddingY = $small ? 16 : 26;
        $infoSectionHeight =
            $infoSectionPaddingY * 2 +
            $sectionLabelSize +
            $sectionLabelGap +
            max(count($leftInfoLines), count($rightInfoLines)) * $sectionBodyLineHeight;

        $amountPaddingY = $small ? 18 : 28;
        $amountLabelSize = $small ? 9 : 16;
        $amountValueSize = $small ? 32 : 58;
        $amountValueLineHeight = $small ? 34 : 60;
        $amountCaptionSize = $small ? 14 : 22;
        $amountBodySize = $small ? 13 : 22;
        $amountBodyLineHeight = $small ? 20 : 33;
        $amountSectionHeight =
            $amountPaddingY * 2 +
            $amountLabelSize +
            ($small ? 10 : 12) +
            $amountValueLineHeight +
            ($small ? 8 : 10) +
            $amountCaptionSize +
            max(($small ? 16 : 24), count($amountDetailLines) * $amountBodyLineHeight);

        $ackBodySize = $small ? 13 : 22;
        $ackBodyLineHeight = $small ? 20 : 34;
        $ackSectionPaddingY = $small ? 16 : 24;
        $ackSectionHeight =
            $ackSectionPaddingY * 2 +
            $sectionLabelSize +
            $sectionLabelGap +
            max(count($acknowledgementLines), count($contactLines)) * $ackBodyLineHeight;

        $signLineGap = $small ? 10 : 12;
        $signLabelSize = $small ? 12 : 19;
        $signSectionHeight = ($small ? 22 : 28) + $signLineGap + $signLabelSize;

        $footerKickerSize = $small ? 14 : 22;
        $footerBrandSize = $small ? 16 : 24;
        $footerNoteSize = $small ? 11 : 18;
        $footerNoteLineHeight = $small ? 16 : 28;
        $footerNoteLines = self::wrapText(
            self::toText($snapshot['footer_note'] ?? '') ?: self::DEFAULT_FOOTER_NOTE,
            $small ? 24 : 34,
            2
        );
        $footerHeight =
            1 +
            ($small ? 20 : 24) +
            $footerBrandSize +
            ($small ? 6 : 8) +
            $footerKickerSize +
            ($small ? 8 : 10) +
            count($footerNoteLines) * $footerNoteLineHeight;

        $sections = [];
        $currentY = $paperY + $paperPaddingY;
        $centerX = $paperX + $paperWidth / 2;

        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" fill="url(#v3HeroGradient)" stroke="#E2D4C3" />',
            $contentX,
            $currentY,
            $contentWidth,
            $heroHeight
        );
        $sections[] = sprintf(
            '<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="#F2DED1" fill-opacity="0.4" /><ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="#F8EEE4" fill-opacity="0.8" />',
            $contentX + $contentWidth - ($small ? 58 : 108),
            $currentY + ($small ? 24 : 38),
            $small ? 34 : 66,
            $small ? 28 : 54,
            $contentX + $contentWidth - ($small ? 42 : 72),
            $currentY + ($small ? 36 : 54),
            $small ? 22 : 46,
            $small ? 18 : 36
        );

        $heroEyebrowY = $currentY + $heroPaddingTop + $heroEyebrowSize;
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $heroEyebrowY,
            'lines' => [$heroEyebrow],
            'fontSize' => $heroEyebrowSize,
            'lineHeight' => $heroEyebrowSize,
            'fill' => '#A98A69',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
            'letterSpacing' => $small ? 1.4 : 2.2,
        ])['svg'];

        $sealY = $heroEyebrowY + ($small ? 12 : 16);
        $sealX = $centerX - $sealSize / 2;
        $sections[] = sprintf(
            '<circle cx="%s" cy="%s" r="%s" fill="none" stroke="#CDB08E" stroke-width="1" /><circle cx="%s" cy="%s" r="%s" fill="none" stroke="#E7D7C2" stroke-width="1" />',
            $centerX,
            $sealY + $sealSize / 2,
            $sealSize / 2,
            $centerX,
            $sealY + $sealSize / 2,
            $sealInnerSize / 2
        );
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $sealY + $sealSize / 2 + ($small ? 5 : 9),
            'lines' => ['LW'],
            'fontSize' => $small ? 16 : 28,
            'lineHeight' => $small ? 16 : 28,
            'fill' => '#A78663',
            'fontWeight' => 600,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SERIF,
        ])['svg'];

        $heroTitleY = $sealY + $sealSize + ($small ? 12 : 16) + $heroTitleSize;
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $heroTitleY,
            'lines' => [$title],
            'fontSize' => $heroTitleSize,
            'lineHeight' => $heroTitleSize,
            'fill' => '#3B322B',
            'fontWeight' => 700,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];

        $heroSubtitleY = $heroTitleY + ($small ? 8 : 10) + $heroSubtitleSize;
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $heroSubtitleY,
            'lines' => [$heroSubtitle],
            'fontSize' => $heroSubtitleSize,
            'lineHeight' => $heroSubtitleSize,
            'fill' => '#9F8467',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SERIF,
        ])['svg'];

        $heroDescY = $heroSubtitleY + ($small ? 10 : 14) + $heroDescSize;
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $heroDescY,
            'lines' => $heroDescLines,
            'fontSize' => $heroDescSize,
            'lineHeight' => $heroDescLineHeight,
            'fill' => '#6E6256',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];

        $heroMetaY = $heroDescY + count($heroDescLines) * $heroDescLineHeight + ($small ? 10 : 14) + $heroMetaSize;
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $heroMetaY,
            'lines' => $heroMetaLines,
            'fontSize' => $heroMetaSize,
            'lineHeight' => $heroMetaLineHeight,
            'fill' => '#B38E69',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];

        $currentY += $heroHeight + $sectionGap;

        $columnGap = $small ? 18 : 40;
        $columnWidth = ($contentWidth - $columnGap) / 2;
        $sectionBodyY = $currentY + $infoSectionPaddingY + $sectionLabelSize + $sectionLabelGap + $sectionBodySize;
        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="1" fill="#E6D9CB" /><rect x="%s" y="%s" width="%s" height="1" fill="#E6D9CB" />',
            $contentX,
            $currentY,
            $contentWidth,
            $contentX,
            $currentY + $infoSectionHeight,
            $contentWidth
        );
        $sections[] = self::drawTextBlock([
            'x' => $contentX,
            'y' => $currentY + $infoSectionPaddingY + $sectionLabelSize,
            'lines' => ['CEREMONY DETAILS'],
            'fontSize' => $sectionLabelSize,
            'lineHeight' => $sectionLabelSize,
            'fill' => '#A98A69',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
            'letterSpacing' => $small ? 1.4 : 2,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $contentX,
            'y' => $sectionBodyY,
            'lines' => $leftInfoLines,
            'fontSize' => $sectionBodySize,
            'lineHeight' => $sectionBodyLineHeight,
            'fill' => '#342E29',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $contentX + $columnWidth + $columnGap,
            'y' => $currentY + $infoSectionPaddingY + $sectionLabelSize,
            'lines' => ['ATELIER SERVICE'],
            'fontSize' => $sectionLabelSize,
            'lineHeight' => $sectionLabelSize,
            'fill' => '#A98A69',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
            'letterSpacing' => $small ? 1.4 : 2,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $contentX + $columnWidth + $columnGap,
            'y' => $sectionBodyY,
            'lines' => $rightInfoLines,
            'fontSize' => $sectionBodySize,
            'lineHeight' => $sectionBodyLineHeight,
            'fill' => '#342E29',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];

        $currentY += $infoSectionHeight + $sectionGap;

        $amountLabelY = $currentY + $amountPaddingY + $amountLabelSize;
        $amountValueY = $amountLabelY + ($small ? 10 : 12) + $amountValueSize;
        $amountCaptionY = $amountValueY + ($small ? 8 : 10) + $amountCaptionSize;
        $amountRightX = $contentX + $contentWidth - ($small ? 150 : 300);
        $amountRightY = $currentY + $amountPaddingY + ($small ? 4 : 8) + $amountBodySize;
        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" fill="url(#v3AmountGradient)" stroke="#D9C1A1" stroke-width="1" />',
            $contentX,
            $currentY,
            $contentWidth,
            $amountSectionHeight
        );
        $sections[] = self::drawTextBlock([
            'x' => $contentX + ($small ? 20 : 30),
            'y' => $amountLabelY,
            'lines' => ['FEE MEMO'],
            'fontSize' => $amountLabelSize,
            'lineHeight' => $amountLabelSize,
            'fill' => '#A98A69',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
            'letterSpacing' => $small ? 1.4 : 2,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $contentX + ($small ? 20 : 30),
            'y' => $amountValueY,
            'lines' => ['¥' . (self::toText($snapshot['order_total_amount'] ?? '') ?: '0.00')],
            'fontSize' => $amountValueSize,
            'lineHeight' => $amountValueLineHeight,
            'fill' => '#7C6146',
            'fontWeight' => 600,
            'fontFamily' => self::FONT_FAMILY_SERIF,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $contentX + ($small ? 20 : 30),
            'y' => $amountCaptionY,
            'lines' => ['合同合计金额'],
            'fontSize' => $amountCaptionSize,
            'lineHeight' => $amountCaptionSize,
            'fill' => '#6D6155',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $amountRightX,
            'y' => $amountRightY,
            'lines' => $amountDetailLines,
            'fontSize' => $amountBodySize,
            'lineHeight' => $amountBodyLineHeight,
            'fill' => '#433932',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];

        $currentY += $amountSectionHeight + $sectionGap;

        $ackBodyY = $currentY + $ackSectionPaddingY + $sectionLabelSize + $sectionLabelGap + $ackBodySize;
        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="1" fill="#E6D9CB" /><rect x="%s" y="%s" width="%s" height="1" fill="#E6D9CB" />',
            $contentX,
            $currentY,
            $contentWidth,
            $contentX,
            $currentY + $ackSectionHeight,
            $contentWidth
        );
        $sections[] = self::drawTextBlock([
            'x' => $contentX,
            'y' => $currentY + $ackSectionPaddingY + $sectionLabelSize,
            'lines' => ['ACKNOWLEDGEMENT'],
            'fontSize' => $sectionLabelSize,
            'lineHeight' => $sectionLabelSize,
            'fill' => '#A98A69',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
            'letterSpacing' => $small ? 1.4 : 2,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $contentX,
            'y' => $ackBodyY,
            'lines' => $acknowledgementLines,
            'fontSize' => $ackBodySize,
            'lineHeight' => $ackBodyLineHeight,
            'fill' => '#433932',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $contentX + $columnWidth + $columnGap,
            'y' => $currentY + $ackSectionPaddingY + $sectionLabelSize,
            'lines' => ['CONTACT ATELIER'],
            'fontSize' => $sectionLabelSize,
            'lineHeight' => $sectionLabelSize,
            'fill' => '#A98A69',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
            'letterSpacing' => $small ? 1.4 : 2,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $contentX + $columnWidth + $columnGap,
            'y' => $ackBodyY,
            'lines' => $contactLines,
            'fontSize' => $small ? 12 : 21,
            'lineHeight' => $ackBodyLineHeight,
            'fill' => '#433932',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];

        $currentY += $ackSectionHeight + $sectionGap;

        $signLineY = $currentY + ($small ? 10 : 14);
        $signLabelY = $signLineY + $signLineGap + $signLabelSize;
        $signRightX = $contentX + $columnWidth + $columnGap;
        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="1" fill="#D9C4A6" /><rect x="%s" y="%s" width="%s" height="1" fill="#D9C4A6" />',
            $contentX,
            $signLineY,
            $columnWidth,
            $signRightX,
            $signLineY,
            $columnWidth
        );
        $sections[] = self::drawTextBlock([
            'x' => $contentX,
            'y' => $signLabelY,
            'lines' => ['客户签名'],
            'fontSize' => $signLabelSize,
            'lineHeight' => $signLabelSize,
            'fill' => '#8B7762',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $signRightX,
            'y' => $signLabelY,
            'lines' => ['婚礼顾问签署'],
            'fontSize' => $signLabelSize,
            'lineHeight' => $signLabelSize,
            'fill' => '#8B7762',
            'fontWeight' => 500,
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];

        $currentY += $signSectionHeight + $sectionGap;

        $footerLineY = $currentY;
        $footerBrandY = $footerLineY + ($small ? 22 : 24) + $footerBrandSize;
        $footerKickerY = $footerBrandY + ($small ? 6 : 8) + $footerKickerSize;
        $footerNoteY = $footerKickerY + ($small ? 8 : 10) + $footerNoteSize;
        $sections[] = sprintf(
            '<rect x="%s" y="%s" width="%s" height="1" fill="#E6D9CB" />',
            $contentX,
            $footerLineY,
            $contentWidth
        );
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $footerBrandY,
            'lines' => [self::toText($snapshot['brand_name'] ?? '') ?: self::DEFAULT_BRAND_NAME],
            'fontSize' => $footerBrandSize,
            'lineHeight' => $footerBrandSize,
            'fill' => '#B08C68',
            'fontWeight' => 600,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $footerKickerY,
            'lines' => [self::V3_FOOTER_KICKER],
            'fontSize' => $footerKickerSize,
            'lineHeight' => $footerKickerSize,
            'fill' => '#9F8467',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SERIF,
        ])['svg'];
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => $footerNoteY,
            'lines' => $footerNoteLines,
            'fontSize' => $footerNoteSize,
            'lineHeight' => $footerNoteLineHeight,
            'fill' => '#87796B',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s"><defs>%s<linearGradient id="v3PageGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FAF5EF" /><stop offset="100%%" stop-color="#F3E8DC" /></linearGradient><linearGradient id="v3PaperGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FFFDFC" /><stop offset="100%%" stop-color="#FAF4EC" /></linearGradient><linearGradient id="v3HeroGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FBF6F0" /><stop offset="100%%" stop-color="#F7EEE3" /></linearGradient><linearGradient id="v3AmountGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FCF6EF" /><stop offset="100%%" stop-color="#F8EDE2" /></linearGradient><filter id="v3PaperShadow" x="-20%%" y="-20%%" width="140%%" height="160%%"><feDropShadow dx="0" dy="20" stdDeviation="14" flood-color="#C9B397" flood-opacity="0.12" /></filter></defs><rect width="100%%" height="100%%" fill="url(#v3PageGradient)" /><rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="url(#v3PaperGradient)" stroke="#D8C3A7" stroke-width="1" filter="url(#v3PaperShadow)" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
            self::buildFontFaceDefs(),
            $paperX,
            $paperY,
            $paperWidth,
            $paperHeight,
            $small ? 8 : 14,
            implode('', $sections)
        );
    }

    protected static function renderV4OrderConfirmLetterSvg(array $snapshot, bool $small): string
    {
        $width = $small ? 540 : 1080;
        $height = $small ? 960 : 1920;
        $scale = $small ? 0.5 : 1.0;
        $paperX = 62 * $scale;
        $paperY = 56 * $scale;
        $paperWidth = $width - $paperX * 2;
        $paperHeight = $height - $paperY * 2;
        $contentX = $paperX + 70 * $scale;
        $contentWidth = $paperWidth - 140 * $scale;
        $centerX = $width / 2;
        $gold = '#C99842';
        $goldDark = '#9F6F1F';
        $brown = '#3B2116';
        $muted = '#7C6756';

        $brandName = self::toText($snapshot['brand_name'] ?? '') ?: self::DEFAULT_BRAND_NAME;
        $brandTagline = self::toText($snapshot['brand_tagline'] ?? '') ?: self::V3_DEFAULT_HERO_EYEBROW;
        $brandInitial = self::strSlice($brandName, 0, min(max(self::strLength($brandName), 1), 4));
        $title = self::toText($snapshot['title'] ?? '') ?: self::DEFAULT_TITLE;
        $subtitle = self::V4_DEFAULT_SUBTITLE;
        $paymentNode = self::toText($snapshot['payment_node'] ?? '') ?: '婚礼前 3 日';
        $staffNames = self::toStringArray($snapshot['service_staff_names'] ?? []);
        $teamText = !empty($staffNames) ? implode('、', $staffNames) : '待确认';
        $serviceTeamLines = self::toStringArray($snapshot['service_team_lines'] ?? []);
        if (!empty($serviceTeamLines)) {
            $teamText = implode('、', array_slice($serviceTeamLines, 0, 3));
        }

        $infoRows = [
            ['icon' => 'clipboard', 'label' => '订单编号：', 'value' => self::toText($snapshot['order_sn'] ?? '') ?: '-'],
            ['icon' => 'user', 'label' => '新人姓名：', 'value' => self::toText($snapshot['customer_name'] ?? '') ?: '-'],
            ['icon' => 'calendar', 'label' => '婚礼日期：', 'value' => self::toText($snapshot['service_date_label'] ?? '') ?: (self::toText($snapshot['service_date'] ?? '') ?: '-')],
            ['icon' => 'pin', 'label' => '举办地点：', 'value' => self::toText($snapshot['service_address'] ?? '') ?: '-'],
            ['icon' => 'team', 'label' => '服务团队：', 'value' => $teamText],
            ['icon' => 'phone', 'label' => '联系电话：', 'value' => self::toText($snapshot['contact_mobile'] ?? '') ?: '-'],
            ['icon' => 'clock', 'label' => '确认日期：', 'value' => self::toText($snapshot['confirm_date'] ?? '') ?: '-'],
        ];
        $tipLines = self::wrapText(
            self::toText($snapshot['footer_note'] ?? '') ?: self::DEFAULT_FOOTER_NOTE,
            $small ? 24 : 34,
            2
        );

        $sections = [];
        $sections[] = self::drawV4Texture($width, $height, $scale);
        $sections[] = self::drawV4Seal($centerX, 112 * $scale, 122 * $scale, $brandName, $brandTagline, self::toText($snapshot['brand_logo_data_uri'] ?? ''), $brandInitial, $small);
        $sections[] = self::drawV4OrnamentLine($centerX, 328 * $scale, 260 * $scale, $gold, $scale);
        $sections[] = self::drawTextBlock([
            'x' => $centerX,
            'y' => 436 * $scale,
            'lines' => [$title],
            'fontSize' => 72 * $scale,
            'lineHeight' => 72 * $scale,
            'fill' => $brown,
            'fontWeight' => 700,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SERIF,
            'letterSpacing' => 10 * $scale,
        ])['svg'];
        $sections[] = self::drawV4TitleSubtitle($centerX, 532 * $scale, $subtitle, $scale);

        $infoY = 604 * $scale;
        $infoHeight = 538 * $scale;
        $sections[] = self::drawV4InfoPanel($contentX, $infoY, $contentWidth, $infoHeight, $infoRows, $scale);
        $sections[] = self::drawV4Leaf($contentX - 34 * $scale, $infoY - 22 * $scale, $scale, false);
        $sections[] = self::drawV4Leaf($contentX + $contentWidth - 92 * $scale, $infoY + $infoHeight - 132 * $scale, $scale, true);

        $amountY = 1192 * $scale;
        $amountHeight = 296 * $scale;
        $sections[] = self::drawV4AmountPanel(
            $contentX,
            $amountY,
            $contentWidth,
            $amountHeight,
            self::toText($snapshot['order_total_amount'] ?? '') ?: '0.00',
            self::toText($snapshot['paid_label'] ?? '') ?: '已付定金',
            self::toText($snapshot['paid_amount'] ?? '') ?: '0.00',
            self::toText($snapshot['remain_amount'] ?? '') ?: '0.00',
            $paymentNode,
            $scale
        );

        $tipY = 1532 * $scale;
        $sections[] = self::drawV4TipPanel($contentX, $tipY, $contentWidth, 104 * $scale, $tipLines, $scale);
        $sections[] = self::drawV4Signature($contentX + 32 * $scale, 1696 * $scale, ($contentWidth - 120 * $scale) / 2, '客户签名', $scale);
        $sections[] = self::drawV4Signature($centerX + 36 * $scale, 1696 * $scale, ($contentWidth - 120 * $scale) / 2, '婚礼顾问签署', $scale);
        $sections[] = self::drawV4BottomOrnament($centerX, 1848 * $scale, 330 * $scale, $gold, $scale);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s"><defs>%s<linearGradient id="v4Bg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%%" stop-color="#F3E1C9" /><stop offset="48%%" stop-color="#FFF6EA" /><stop offset="100%%" stop-color="#D7BE9E" /></linearGradient><linearGradient id="v4Paper" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FFFDF7" /><stop offset="100%%" stop-color="#FFF7E9" /></linearGradient><linearGradient id="v4Gold" x1="0" y1="0" x2="1" y2="1"><stop offset="0%%" stop-color="#E9C66A" /><stop offset="42%%" stop-color="#B57A23" /><stop offset="100%%" stop-color="#F0D27D" /></linearGradient><linearGradient id="v4Amount" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FFF8E9" /><stop offset="100%%" stop-color="#F9E8C4" /></linearGradient><filter id="v4Shadow" x="-20%%" y="-20%%" width="150%%" height="150%%"><feDropShadow dx="%s" dy="%s" stdDeviation="%s" flood-color="#7D5434" flood-opacity="0.22" /></filter></defs><rect width="100%%" height="100%%" fill="url(#v4Bg)" /><rect x="%s" y="%s" width="%s" height="%s" fill="url(#v4Paper)" filter="url(#v4Shadow)" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
            self::buildFontFaceDefs(),
            10 * $scale,
            24 * $scale,
            14 * $scale,
            $paperX,
            $paperY,
            $paperWidth,
            $paperHeight,
            implode('', $sections)
        );
    }

    protected static function drawV4Texture(float $width, float $height, float $scale): string
    {
        $lines = '';
        for ($i = 0; $i < 34; $i++) {
            $y = (28 + $i * 54) * $scale;
            $opacity = $i % 2 === 0 ? 0.1 : 0.06;
            $lines .= sprintf(
                '<path d="M%s %s C%s %s %s %s %s %s" fill="none" stroke="#D8B98E" stroke-width="%s" stroke-opacity="%s" />',
                0,
                $y,
                $width * 0.26,
                $y - 18 * $scale,
                $width * 0.72,
                $y + 18 * $scale,
                $width,
                $y,
                max(0.4, 0.8 * $scale),
                $opacity
            );
        }
        return $lines;
    }

    protected static function drawV4Seal(
        float $centerX,
        float $topY,
        float $size,
        string $brandName,
        string $brandTagline,
        string $logoDataUri,
        string $brandInitial,
        bool $small
    ): string {
        $centerY = $topY + $size / 2;
        $scale = $size / 122;
        $svg = sprintf(
            '<circle cx="%s" cy="%s" r="%s" fill="#FFF9EC" stroke="url(#v4Gold)" stroke-width="%s" /><circle cx="%s" cy="%s" r="%s" fill="none" stroke="#E8CD82" stroke-width="%s" /><path d="M%s %s C%s %s %s %s %s %s" fill="none" stroke="#D2A34B" stroke-width="%s" /><path d="M%s %s C%s %s %s %s %s %s" fill="none" stroke="#D2A34B" stroke-width="%s" />',
            $centerX,
            $centerY,
            $size / 2,
            max(1, 3 * $scale),
            $centerX,
            $centerY,
            $size / 2 - 10 * $scale,
            max(0.8, 1.4 * $scale),
            $centerX - 45 * $scale,
            $centerY + 26 * $scale,
            $centerX - 20 * $scale,
            $centerY + 50 * $scale,
            $centerX + 20 * $scale,
            $centerY + 50 * $scale,
            $centerX + 45 * $scale,
            $centerY + 26 * $scale,
            max(0.8, 1.4 * $scale),
            $centerX - 30 * $scale,
            $centerY - 40 * $scale,
            $centerX - 8 * $scale,
            $centerY - 54 * $scale,
            $centerX + 8 * $scale,
            $centerY - 54 * $scale,
            $centerX + 30 * $scale,
            $centerY - 40 * $scale,
            max(0.8, 1.2 * $scale)
        );

        if ($logoDataUri !== '') {
            $clipId = 'v4LogoClip' . md5($logoDataUri);
            $logoSize = 52 * $scale;
            $svg .= sprintf(
                '<defs><clipPath id="%s"><circle cx="%s" cy="%s" r="%s" /></clipPath></defs><image href="%s" x="%s" y="%s" width="%s" height="%s" preserveAspectRatio="xMidYMid meet" clip-path="url(#%s)" />',
                $clipId,
                $centerX,
                $centerY - 8 * $scale,
                $logoSize / 2,
                self::escapeXml($logoDataUri),
                $centerX - $logoSize / 2,
                $centerY - 8 * $scale - $logoSize / 2,
                $logoSize,
                $logoSize,
                $clipId
            );
        } else {
            $svg .= self::drawTextBlock([
                'x' => $centerX,
                'y' => $centerY - 8 * $scale + 9 * $scale,
                'lines' => [$brandInitial],
                'fontSize' => 26 * $scale,
                'lineHeight' => 26 * $scale,
                'fill' => '#A87023',
                'fontWeight' => 700,
                'textAnchor' => 'middle',
                'fontFamily' => self::FONT_FAMILY_SERIF,
            ])['svg'];
        }

        $svg .= self::drawTextBlock([
            'x' => $centerX,
            'y' => $centerY + 31 * $scale,
            'lines' => self::wrapText($brandName, $small ? 8 : 8, 1),
            'fontSize' => 15 * $scale,
            'lineHeight' => 15 * $scale,
            'fill' => '#B17A24',
            'fontWeight' => 700,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
            'letterSpacing' => 1.2 * $scale,
        ])['svg'];
        $svg .= self::drawTextBlock([
            'x' => $centerX,
            'y' => $centerY + 48 * $scale,
            'lines' => self::wrapText(strtoupper($brandTagline), $small ? 16 : 18, 1),
            'fontSize' => 8.5 * $scale,
            'lineHeight' => 8.5 * $scale,
            'fill' => '#C0923D',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SERIF,
        ])['svg'];
        return $svg;
    }

    protected static function drawV4OrnamentLine(float $centerX, float $y, float $width, string $color, float $scale): string
    {
        return sprintf(
            '<path d="M%s %s H%s" stroke="%s" stroke-width="%s" /><path d="M%s %s C%s %s %s %s %s %s C%s %s %s %s %s %s" fill="none" stroke="%s" stroke-width="%s" /><circle cx="%s" cy="%s" r="%s" fill="%s" />',
            $centerX - $width / 2,
            $y,
            $centerX - 34 * $scale,
            $color,
            max(0.8, 1 * $scale),
            $centerX + 34 * $scale,
            $y,
            $centerX + 52 * $scale,
            $y - 16 * $scale,
            $centerX + 74 * $scale,
            $y - 12 * $scale,
            $centerX + 86 * $scale,
            $y,
            $centerX + 74 * $scale,
            $y + 12 * $scale,
            $centerX + 52 * $scale,
            $y + 16 * $scale,
            $centerX + 34 * $scale,
            $y,
            $color,
            max(0.8, 1 * $scale),
            $centerX,
            $y,
            4 * $scale,
            $color
        ) . sprintf(
            '<path d="M%s %s H%s" stroke="%s" stroke-width="%s" />',
            $centerX + 34 * $scale,
            $y,
            $centerX + $width / 2,
            $color,
            max(0.8, 1 * $scale)
        );
    }

    protected static function drawV4TitleSubtitle(float $centerX, float $y, string $subtitle, float $scale): string
    {
        return sprintf(
            '<path d="M%s %s H%s" stroke="#C99842" stroke-width="%s" /><path d="M%s %s H%s" stroke="#C99842" stroke-width="%s" /><rect x="%s" y="%s" width="%s" height="%s" fill="#C99842" transform="rotate(45 %s %s)" /><rect x="%s" y="%s" width="%s" height="%s" fill="#C99842" transform="rotate(45 %s %s)" />',
            $centerX - 265 * $scale,
            $y - 9 * $scale,
            $centerX - 172 * $scale,
            max(0.8, 1 * $scale),
            $centerX + 172 * $scale,
            $y - 9 * $scale,
            $centerX + 265 * $scale,
            max(0.8, 1 * $scale),
            $centerX - 282 * $scale,
            $y - 13 * $scale,
            8 * $scale,
            8 * $scale,
            $centerX - 278 * $scale,
            $y - 9 * $scale,
            $centerX + 274 * $scale,
            $y - 13 * $scale,
            8 * $scale,
            8 * $scale,
            $centerX + 278 * $scale,
            $y - 9 * $scale
        ) . self::drawTextBlock([
            'x' => $centerX,
            'y' => $y,
            'lines' => [$subtitle],
            'fontSize' => 27 * $scale,
            'lineHeight' => 27 * $scale,
            'fill' => '#8D6F50',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SERIF,
        ])['svg'];
    }

    protected static function drawV4InfoPanel(float $x, float $y, float $width, float $height, array $rows, float $scale): string
    {
        $svg = sprintf(
            '<path d="M%s %s H%s Q%s %s %s %s V%s H%s Q%s %s %s %s V%s Z" fill="#FFFDF8" fill-opacity="0.78" stroke="#D6A04B" stroke-width="%s" />',
            $x,
            $y,
            $x + $width - 30 * $scale,
            $x + $width - 12 * $scale,
            $y,
            $x + $width - 12 * $scale,
            $y + 30 * $scale,
            $y + $height,
            $x + 24 * $scale,
            $x,
            $y + $height,
            $x,
            $y + $height - 24 * $scale,
            $y,
            max(1, 1.4 * $scale)
        );
        $rowHeight = $height / count($rows);
        foreach (array_values($rows) as $index => $row) {
            $rowY = $y + $index * $rowHeight;
            if ($index > 0) {
                $svg .= sprintf(
                    '<path d="M%s %s H%s" stroke="#D9BD87" stroke-width="%s" stroke-dasharray="%s %s" />',
                    $x + 86 * $scale,
                    $rowY,
                    $x + $width - 52 * $scale,
                    max(0.6, 0.8 * $scale),
                    5 * $scale,
                    5 * $scale
                );
            }
            $iconCenterX = $x + 58 * $scale;
            $iconCenterY = $rowY + $rowHeight / 2;
            $labelX = $x + 105 * $scale;
            $valueX = $x + 238 * $scale;
            $textY = $iconCenterY + 9 * $scale;
            $valueFit = self::fitTextLine(
                (string) ($row['value'] ?? '-'),
                $scale < 1 ? 16 : 24,
                24 * $scale,
                14 * $scale
            );
            $svg .= self::drawV4Icon((string) ($row['icon'] ?? ''), $iconCenterX, $iconCenterY, 18 * $scale);
            $svg .= self::drawTextBlock([
                'x' => $labelX,
                'y' => $textY,
                'lines' => [(string) ($row['label'] ?? '')],
                'fontSize' => 25 * $scale,
                'lineHeight' => 25 * $scale,
                'fill' => '#49372C',
                'fontWeight' => 500,
                'fontFamily' => self::FONT_FAMILY_SANS,
            ])['svg'];
            $svg .= self::drawTextBlock([
                'x' => $valueX,
                'y' => $textY,
                'lines' => $valueFit['lines'],
                'fontSize' => $valueFit['fontSize'],
                'lineHeight' => $valueFit['lineHeight'],
                'fill' => '#2E2A27',
                'fontWeight' => 500,
                'fontFamily' => self::FONT_FAMILY_SANS,
            ])['svg'];
        }
        return $svg;
    }

    protected static function drawV4Icon(string $type, float $x, float $y, float $size): string
    {
        $stroke = '#B9812B';
        $sw = max(1, $size / 10);
        return match ($type) {
            'user' => sprintf('<circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s" /><path d="M%s %s C%s %s %s %s %s %s" fill="none" stroke="%s" stroke-width="%s" />', $x, $y - $size * 0.22, $size * 0.23, $stroke, $sw, $x - $size * 0.48, $y + $size * 0.52, $x - $size * 0.32, $y + $size * 0.12, $x + $size * 0.32, $y + $size * 0.12, $x + $size * 0.48, $y + $size * 0.52, $stroke, $sw),
            'calendar' => sprintf('<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="none" stroke="%s" stroke-width="%s" /><path d="M%s %s H%s M%s %s V%s M%s %s V%s" stroke="%s" stroke-width="%s" />', $x - $size * 0.45, $y - $size * 0.38, $size * 0.9, $size * 0.82, $size * 0.08, $stroke, $sw, $x - $size * 0.45, $y - $size * 0.14, $x + $size * 0.45, $x - $size * 0.24, $y - $size * 0.52, $y - $size * 0.25, $x + $size * 0.24, $y - $size * 0.52, $y - $size * 0.25, $stroke, $sw),
            'pin' => sprintf('<path d="M%s %s C%s %s %s %s %s %s C%s %s %s %s %s %s Z" fill="none" stroke="%s" stroke-width="%s" /><circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s" /><path d="M%s %s H%s" stroke="%s" stroke-width="%s" />', $x, $y + $size * 0.55, $x - $size * 0.44, $y + $size * 0.05, $x - $size * 0.36, $y - $size * 0.48, $x, $y - $size * 0.48, $x + $size * 0.36, $y - $size * 0.48, $x + $size * 0.44, $y + $size * 0.05, $x, $y + $size * 0.55, $stroke, $sw, $x, $y - $size * 0.16, $size * 0.13, $stroke, $sw, $x - $size * 0.42, $y + $size * 0.64, $x + $size * 0.42, $stroke, $sw),
            'team' => sprintf('<circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s" /><circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s" /><circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s" /><path d="M%s %s H%s M%s %s C%s %s %s %s %s %s" fill="none" stroke="%s" stroke-width="%s" />', $x, $y - $size * 0.18, $size * 0.18, $stroke, $sw, $x - $size * 0.28, $y - $size * 0.04, $size * 0.14, $stroke, $sw, $x + $size * 0.28, $y - $size * 0.04, $size * 0.14, $stroke, $sw, $x - $size * 0.5, $y + $size * 0.52, $x + $size * 0.5, $x - $size * 0.34, $y + $size * 0.52, $x - $size * 0.2, $y + $size * 0.18, $x + $size * 0.2, $y + $size * 0.18, $x + $size * 0.34, $y + $size * 0.52, $stroke, $sw),
            'phone' => sprintf('<path d="M%s %s C%s %s %s %s %s %s L%s %s C%s %s %s %s %s %s C%s %s %s %s %s %s" fill="none" stroke="%s" stroke-width="%s" stroke-linecap="round" />', $x - $size * 0.36, $y - $size * 0.48, $x - $size * 0.52, $y - $size * 0.28, $x - $size * 0.34, $y + $size * 0.34, $x + $size * 0.18, $y + $size * 0.52, $x + $size * 0.34, $y + $size * 0.32, $x + $size * 0.42, $y + $size * 0.2, $x + $size * 0.18, $y + $size * 0.02, $x + $size * 0.06, $y + $size * 0.12, $x - $size * 0.08, $y - $size * 0.12, $x + $size * 0.04, $y - $size * 0.24, $x - $size * 0.18, $y - $size * 0.5, $stroke, $sw),
            'clock' => sprintf('<circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s" /><path d="M%s %s V%s H%s" fill="none" stroke="%s" stroke-width="%s" stroke-linecap="round" />', $x, $y, $size * 0.45, $stroke, $sw, $x, $y - $size * 0.24, $y, $x + $size * 0.22, $stroke, $sw),
            default => sprintf('<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="none" stroke="%s" stroke-width="%s" /><path d="M%s %s H%s M%s %s H%s M%s %s H%s" stroke="%s" stroke-width="%s" />', $x - $size * 0.36, $y - $size * 0.46, $size * 0.72, $size * 0.9, $size * 0.06, $stroke, $sw, $x - $size * 0.18, $y - $size * 0.18, $x + $size * 0.18, $x - $size * 0.18, $y + $size * 0.04, $x + $size * 0.18, $x - $size * 0.18, $y + $size * 0.26, $x + $size * 0.18, $stroke, $sw),
        };
    }

    protected static function drawV4AmountPanel(float $x, float $y, float $width, float $height, string $total, string $paidLabel, string $paidAmount, string $remainAmount, string $paymentNode, float $scale): string
    {
        $third = $width / 3;
        $svg = sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="url(#v4Amount)" stroke="#D6A04B" stroke-width="%s" /><path d="M%s %s H%s" stroke="#B88937" stroke-width="%s" /><path d="M%s %s V%s M%s %s V%s" stroke="#B88937" stroke-width="%s" stroke-opacity="0.65" />',
            $x,
            $y,
            $width,
            $height,
            10 * $scale,
            max(1, 1.4 * $scale),
            $x + 46 * $scale,
            $y + 168 * $scale,
            $x + $width - 46 * $scale,
            max(0.8, 1 * $scale),
            $x + $third,
            $y + 204 * $scale,
            $y + $height - 40 * $scale,
            $x + $third * 2,
            $y + 204 * $scale,
            $y + $height - 40 * $scale,
            max(0.8, 1 * $scale)
        );
        $svg .= self::drawV4OrnamentLine($x + $width / 2, $y + 30 * $scale, 170 * $scale, '#D1A24B', $scale);
        $svg .= self::drawTextBlock([
            'x' => $x + $width / 2,
            'y' => $y + 62 * $scale,
            'lines' => ['合同合计金额'],
            'fontSize' => 28 * $scale,
            'lineHeight' => 28 * $scale,
            'fill' => '#2E241D',
            'fontWeight' => 600,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];
        $svg .= self::drawTextBlock([
            'x' => $x + $width / 2,
            'y' => $y + 142 * $scale,
            'lines' => ['¥ ' . $total],
            'fontSize' => 72 * $scale,
            'lineHeight' => 72 * $scale,
            'fill' => '#B98226',
            'fontWeight' => 700,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SERIF,
        ])['svg'];
        foreach ([[$paidLabel, '¥ ' . $paidAmount], ['待付尾款', '¥ ' . $remainAmount], ['支付节点：', $paymentNode]] as $index => $item) {
            $cx = $x + $third * $index + $third / 2;
            $valueFit = self::fitTextLine((string) $item[1], $index === 2 ? 8 : 12, 23 * $scale, 15 * $scale);
            $svg .= self::drawTextBlock([
                'x' => $cx,
                'y' => $y + 230 * $scale,
                'lines' => [$item[0]],
                'fontSize' => 23 * $scale,
                'lineHeight' => 23 * $scale,
                'fill' => '#322820',
                'fontWeight' => 600,
                'textAnchor' => 'middle',
                'fontFamily' => self::FONT_FAMILY_SANS,
            ])['svg'];
            $svg .= self::drawTextBlock([
                'x' => $cx,
                'y' => $y + 266 * $scale,
                'lines' => $valueFit['lines'],
                'fontSize' => $valueFit['fontSize'],
                'lineHeight' => $valueFit['lineHeight'],
                'fill' => '#322820',
                'fontWeight' => 500,
                'textAnchor' => 'middle',
                'fontFamily' => self::FONT_FAMILY_SANS,
            ])['svg'];
        }
        return $svg;
    }

    protected static function drawV4TipPanel(float $x, float $y, float $width, float $height, array $lines, float $scale): string
    {
        $svg = sprintf(
            '<rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="#FFFDF8" fill-opacity="0.72" stroke="#D9B96E" stroke-width="%s" stroke-dasharray="%s %s" />',
            $x,
            $y,
            $width,
            $height,
            10 * $scale,
            max(0.8, 1 * $scale),
            5 * $scale,
            5 * $scale
        );
        $svg .= self::drawV4OrnamentLine($x + $width / 2, $y - 16 * $scale, 154 * $scale, '#D1A24B', $scale);
        $svg .= self::drawTextBlock([
            'x' => $x + $width / 2,
            'y' => $y + 22 * $scale,
            'lines' => ['温馨提示'],
            'fontSize' => 24 * $scale,
            'lineHeight' => 24 * $scale,
            'fill' => '#7C5C2B',
            'fontWeight' => 600,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];
        $svg .= self::drawTextBlock([
            'x' => $x + $width / 2,
            'y' => $y + 68 * $scale,
            'lines' => $lines,
            'fontSize' => 22 * $scale,
            'lineHeight' => 28 * $scale,
            'fill' => '#4C4035',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'];
        return $svg;
    }

    protected static function drawV4Signature(float $x, float $y, float $width, string $label, float $scale): string
    {
        return self::drawTextBlock([
            'x' => $x + $width / 2,
            'y' => $y,
            'lines' => [$label],
            'fontSize' => 24 * $scale,
            'lineHeight' => 24 * $scale,
            'fill' => '#342A23',
            'fontWeight' => 500,
            'textAnchor' => 'middle',
            'fontFamily' => self::FONT_FAMILY_SANS,
        ])['svg'] . sprintf(
            '<path d="M%s %s H%s" stroke="#927863" stroke-width="%s" /><path d="M%s %s C%s %s %s %s %s %s" fill="none" stroke="#D3A44B" stroke-width="%s" />',
            $x,
            $y + 64 * $scale,
            $x + $width,
            max(0.8, 1 * $scale),
            $x + $width / 2 - 36 * $scale,
            $y + 76 * $scale,
            $x + $width / 2 - 12 * $scale,
            $y + 88 * $scale,
            $x + $width / 2 + 12 * $scale,
            $y + 88 * $scale,
            $x + $width / 2 + 36 * $scale,
            $y + 76 * $scale,
            max(0.8, 1 * $scale)
        );
    }

    protected static function drawV4BottomOrnament(float $centerX, float $y, float $width, string $color, float $scale): string
    {
        return sprintf(
            '<path d="M%s %s H%s M%s %s H%s" stroke="%s" stroke-width="%s" /><path d="M%s %s C%s %s %s %s %s %s C%s %s %s %s %s %s" fill="none" stroke="%s" stroke-width="%s" /><circle cx="%s" cy="%s" r="%s" fill="none" stroke="%s" stroke-width="%s" />',
            $centerX - $width / 2,
            $y,
            $centerX - 68 * $scale,
            $centerX + 68 * $scale,
            $y,
            $centerX + $width / 2,
            $color,
            max(0.8, 1 * $scale),
            $centerX - 58 * $scale,
            $y,
            $centerX - 24 * $scale,
            $y - 34 * $scale,
            $centerX - 8 * $scale,
            $y - 18 * $scale,
            $centerX,
            $y,
            $centerX + 8 * $scale,
            $y - 18 * $scale,
            $centerX + 24 * $scale,
            $y - 34 * $scale,
            $centerX + 58 * $scale,
            $y,
            $color,
            max(0.8, 1 * $scale),
            $centerX,
            $y,
            5 * $scale,
            $color,
            max(0.8, 1 * $scale)
        );
    }

    protected static function drawV4Leaf(float $x, float $y, float $scale, bool $flip): string
    {
        $sign = $flip ? -1 : 1;
        $svg = sprintf(
            '<path d="M%s %s C%s %s %s %s %s %s" fill="none" stroke="#E9A690" stroke-width="%s" stroke-opacity="0.58" />',
            $x,
            $y + 120 * $scale,
            $x + $sign * 18 * $scale,
            $y + 70 * $scale,
            $x + $sign * 42 * $scale,
            $y + 30 * $scale,
            $x + $sign * 74 * $scale,
            $y,
            max(0.8, 1.2 * $scale)
        );
        for ($i = 0; $i < 7; $i++) {
            $leafY = $y + (104 - $i * 16) * $scale;
            $leafX = $x + $sign * (11 + $i * 8) * $scale;
            $svg .= sprintf(
                '<ellipse cx="%s" cy="%s" rx="%s" ry="%s" fill="#E9A690" fill-opacity="%s" transform="rotate(%s %s %s)" />',
                $leafX,
                $leafY,
                6 * $scale,
                17 * $scale,
                0.28 + $i * 0.025,
                $sign * (36 - $i * 3),
                $leafX,
                $leafY
            );
        }
        return $svg;
    }
}
