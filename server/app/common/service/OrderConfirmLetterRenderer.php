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

    protected const DEFAULT_TITLE = '订单确认函';
    protected const DEFAULT_HERO_EYEBROW = 'ORDER CONFIRMATION LETTER';
    protected const DEFAULT_HERO_DESC = '为保证婚礼现场执行准确无误，系统已根据当前订单信息自动生成本次正式确认函。';
    protected const DEFAULT_BRAND_NAME = '喜遇婚礼服务';
    protected const DEFAULT_FOOTER_NOTE = '请保存此确认函图片，作为婚礼服务安排与付款确认的纸本凭证。';
    protected const V3_DEFAULT_HERO_EYEBROW = 'MAISON DE MARIAGE · CONFIRMATION';
    protected const V3_DEFAULT_SUBTITLE = 'Wedding Order Confirmation';
    protected const V3_DEFAULT_HERO_DESC = '以法式纸本礼仪的方式，确认本次婚礼档期、服务内容与付款安排。';
    protected const V3_FOOTER_KICKER = 'Avec amour et promesse.';

    public static function render(array $snapshot, array $options = []): string
    {
        $renderSpecVersion = (string) ($options['render_spec_version'] ?? $options['renderSpecVersion'] ?? 'v1');
        $small = (bool) ($options['small'] ?? false);

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
        return ' font-family="' . self::escapeXml($fontFamily ?: self::FONT_FAMILY_SANS) . '"';
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
        if ($truncated && !empty($visibleLines)) {
            $lastIndex = count($visibleLines) - 1;
            $lastLine = (string) $visibleLines[$lastIndex];
            $visibleLines[$lastIndex] = self::strSlice($lastLine, 0, max($maxCharsPerLine - 3, 0)) . '...';
        }

        return !empty($visibleLines) ? $visibleLines : [''];
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
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s"><rect width="100%%" height="100%%" fill="#ffffff" rx="24" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
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
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s"><defs><linearGradient id="pageGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FCFBF9" /><stop offset="100%%" stop-color="#FFF4EF" /></linearGradient><linearGradient id="heroGradient" x1="0" y1="0" x2="1" y2="1"><stop offset="0%%" stop-color="#FFF7F4" /><stop offset="100%%" stop-color="#FDE9E2" /></linearGradient><filter id="paperShadow" x="-20%%" y="-20%%" width="140%%" height="160%%"><feDropShadow dx="0" dy="24" stdDeviation="18" flood-color="#DAB5A6" flood-opacity="0.18" /></filter></defs><rect width="100%%" height="100%%" fill="url(#pageGradient)" /><rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="#FFFDFB" stroke="#EFE6E1" stroke-width="2" filter="url(#paperShadow)" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
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
            '<svg xmlns="http://www.w3.org/2000/svg" width="%s" height="%s" viewBox="0 0 %s %s"><defs><linearGradient id="v3PageGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FAF5EF" /><stop offset="100%%" stop-color="#F3E8DC" /></linearGradient><linearGradient id="v3PaperGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FFFDFC" /><stop offset="100%%" stop-color="#FAF4EC" /></linearGradient><linearGradient id="v3HeroGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FBF6F0" /><stop offset="100%%" stop-color="#F7EEE3" /></linearGradient><linearGradient id="v3AmountGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%%" stop-color="#FCF6EF" /><stop offset="100%%" stop-color="#F8EDE2" /></linearGradient><filter id="v3PaperShadow" x="-20%%" y="-20%%" width="140%%" height="160%%"><feDropShadow dx="0" dy="20" stdDeviation="14" flood-color="#C9B397" flood-opacity="0.12" /></filter></defs><rect width="100%%" height="100%%" fill="url(#v3PageGradient)" /><rect x="%s" y="%s" width="%s" height="%s" rx="%s" fill="url(#v3PaperGradient)" stroke="#D8C3A7" stroke-width="1" filter="url(#v3PaperShadow)" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
            $paperX,
            $paperY,
            $paperWidth,
            $paperHeight,
            $small ? 8 : 14,
            implode('', $sections)
        );
    }
}
