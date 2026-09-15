<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 服务人员档期确认函海报渲染
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

class StaffScheduleConfirmLetterRenderer
{
    public static function render(array $snapshot, array $options = []): string
    {
        $fontOptions = $options['font_options'] ?? [];
        $fontFamily = self::escapeAttr((string)($fontOptions['font_family'] ?? 'Microsoft YaHei, PingFang SC, sans-serif'));
        $design = is_array($snapshot['design_config'] ?? null) ? $snapshot['design_config'] : [];
        if (!empty($design['layers']) && is_array($design['layers'])) {
            return self::renderDesigner($snapshot, $design, $fontFamily);
        }

        return self::renderLegacy($snapshot, $fontFamily);
    }

    protected static function renderDesigner(array $snapshot, array $design, string $fontFamily): string
    {
        $canvas = is_array($design['canvas'] ?? null) ? $design['canvas'] : [];
        $width = self::clampInt((int)($canvas['width'] ?? 1080), 320, 2160);
        $height = self::clampInt((int)($canvas['height'] ?? 1920), 480, 3840);
        $background = is_array($design['background'] ?? null) ? $design['background'] : [];
        $backgroundType = (string)($background['type'] ?? 'color');
        $backgroundColor = self::normalizeColor((string)($background['color'] ?? '#191713'), '#191713');
        $backgroundImage = trim((string)($background['image'] ?? ''));
        $backgroundFit = (string)($background['fit'] ?? 'cover') === 'contain' ? 'xMidYMid meet' : 'xMidYMid slice';
        $backgroundOpacity = self::normalizeOpacity($background['opacity'] ?? 1);

        $layers = array_values(array_filter($design['layers'], static fn($layer) => is_array($layer)));
        usort($layers, static fn($a, $b) => ((int)($a['z'] ?? 0)) <=> ((int)($b['z'] ?? 0)));

        $layerSvg = '';
        foreach ($layers as $layer) {
            if ((int)($layer['visible'] ?? 1) !== 1) {
                continue;
            }
            $layerSvg .= self::renderLayer($layer, $snapshot, $fontFamily);
        }

        $backgroundSvg = sprintf('<rect width="%d" height="%d" fill="%s"/>', $width, $height, $backgroundColor);
        if ($backgroundType === 'image' && $backgroundImage !== '') {
            $backgroundSvg .= sprintf(
                '<image href="%s" x="0" y="0" width="%d" height="%d" preserveAspectRatio="%s" opacity="%s"/>',
                self::escapeAttr($backgroundImage),
                $width,
                $height,
                $backgroundFit,
                self::formatFloat($backgroundOpacity)
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">%s%s</svg>',
            $width,
            $height,
            $width,
            $height,
            $backgroundSvg,
            $layerSvg
        );
    }

    protected static function renderLayer(array $layer, array $snapshot, string $fontFamily): string
    {
        $type = (string)($layer['type'] ?? 'text');
        $x = self::clampInt((int)($layer['x'] ?? 0), -2160, 4320);
        $y = self::clampInt((int)($layer['y'] ?? 0), -3840, 7680);
        $w = self::clampInt((int)($layer['w'] ?? 100), 1, 2160);
        $h = $type === 'line'
            ? self::clampInt((int)($layer['h'] ?? 0), 0, 3840)
            : self::clampInt((int)($layer['h'] ?? 100), 1, 3840);
        $opacity = $type === 'qrcode' ? 1.0 : self::normalizeOpacity($layer['opacity'] ?? 1);
        $rotate = self::clampFloat((float)($layer['rotate'] ?? 0), -360, 360);
        $transform = $rotate !== 0.0
            ? sprintf(' transform="rotate(%s %s %s)"', self::formatFloat($rotate), self::formatFloat($x + $w / 2), self::formatFloat($y + $h / 2))
            : '';

        return match ($type) {
            'image' => self::renderImageLayer($layer, $x, $y, $w, $h, $opacity, $transform),
            'qrcode' => self::renderQrcodeLayer($layer, $snapshot, $x, $y, $w, $h, $opacity, $transform),
            'rect' => self::renderRectLayer($layer, $x, $y, $w, $h, $opacity, $transform),
            'line' => self::renderLineLayer($layer, $x, $y, $w, $h, $opacity, $transform),
            default => self::renderTextLayer($layer, $snapshot, $fontFamily, $x, $y, $w, $h, $opacity, $transform),
        };
    }

    protected static function renderTextLayer(array $layer, array $snapshot, string $fontFamily, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $text = self::renderTemplate((string)($layer['text'] ?? ''), $snapshot);
        $fontSize = self::clampInt((int)($layer['fontSize'] ?? 42), 12, 400);
        $lineHeight = self::clampFloat((float)($layer['lineHeight'] ?? 1.35), 0.8, 3);
        $color = self::normalizeColor((string)($layer['color'] ?? '#FFF7E6'), '#FFF7E6');
        $rawFontWeight = (string)($layer['fontWeight'] ?? '400');
        $fontWeight = in_array($rawFontWeight, ['300', '400', '500', '600', '700', '800', '900'], true)
            ? $rawFontWeight
            : '400';
        $align = in_array((string)($layer['align'] ?? 'center'), ['left', 'center', 'right'], true)
            ? (string)$layer['align']
            : 'center';
        $anchor = $align === 'left' ? 'start' : ($align === 'right' ? 'end' : 'middle');
        $textX = $align === 'left' ? $x : ($align === 'right' ? $x + $w : $x + $w / 2);
        $maxLines = max(1, (int)floor($h / max(1, $fontSize * $lineHeight)));
        $lines = self::wrapTextByWidth($text, $w, $fontSize, $maxLines, (float)($layer['letterSpacing'] ?? 0));
        if (empty($lines)) {
            return '';
        }
        $baseline = $y + $fontSize;
        $scaleX = self::clampFloat((float)($layer['scaleX'] ?? 1), 0.2, 3);
        $scaleY = self::clampFloat((float)($layer['scaleY'] ?? 1), 0.2, 3);
        $letterSpacing = self::clampFloat((float)($layer['letterSpacing'] ?? 0), -20, 80);
        $rawFillType = (string)($layer['fillType'] ?? 'solid');
        $fillType = in_array($rawFillType, ['solid', 'linear', 'image'], true) ? $rawFillType : 'solid';
        $strokeColor = self::normalizeColor((string)($layer['textStrokeColor'] ?? '#000000'), '#000000');
        $strokeWidth = self::clampFloat((float)($layer['textStrokeWidth'] ?? 0), 0, 24);
        $strokeOpacity = self::clampFloat((float)($layer['textStrokeOpacity'] ?? 1), 0, 1);
        $shadowBlur = self::clampFloat((float)($layer['shadowBlur'] ?? 0), 0, 80);
        $shadowOffsetX = self::clampFloat((float)($layer['shadowOffsetX'] ?? 0), -120, 120);
        $shadowOffsetY = self::clampFloat((float)($layer['shadowOffsetY'] ?? 0), -120, 120);
        $shadowOpacity = self::clampFloat((float)($layer['shadowOpacity'] ?? 0.35), 0, 1);
        $shadowColor = self::normalizeColor((string)($layer['shadowColor'] ?? '#000000'), '#000000');

        $defs = '';
        $fill = $color;
        $imageFillSvg = '';
        if ($fillType === 'linear') {
            $gradientId = 'txt_grad_' . substr(md5(json_encode($layer, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $x . $y), 0, 12);
            $defs .= self::textGradientDef(
                $gradientId,
                self::normalizeColor((string)($layer['gradientFrom'] ?? $color), $color),
                self::normalizeColor((string)($layer['gradientTo'] ?? $color), $color),
                self::clampInt((int)($layer['gradientAngle'] ?? 90), 0, 360)
            );
            $fill = 'url(#' . $gradientId . ')';
        } elseif ($fillType === 'image') {
            $imageFillSvg = self::renderImageTextFill($layer, $lines, $fontFamily, $fontSize, $fontWeight, $anchor, $textX, $baseline, $lineHeight, $letterSpacing, $x, $y, $w, $h);
            if ($imageFillSvg !== '') {
                $fill = 'transparent';
            }
        }

        $filterAttr = '';
        if ($shadowBlur > 0 || $shadowOffsetX !== 0.0 || $shadowOffsetY !== 0.0) {
            $filterId = 'txt_shadow_' . substr(md5(json_encode($layer, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . $x . $y . 'shadow'), 0, 12);
            $defs .= self::textShadowFilterDef($filterId, $shadowColor, $shadowOpacity, $shadowBlur, $shadowOffsetX, $shadowOffsetY);
            $filterAttr = sprintf(' filter="url(#%s)"', $filterId);
        }

        $scaleTransform = ($scaleX !== 1.0 || $scaleY !== 1.0)
            ? sprintf(
                ' translate(%s %s) scale(%s %s) translate(%s %s)',
                self::formatFloat($x),
                self::formatFloat($y),
                self::formatFloat($scaleX),
                self::formatFloat($scaleY),
                self::formatFloat(-$x),
                self::formatFloat(-$y)
            )
            : '';
        $rotateTransform = '';
        if ($transform !== '' && preg_match('/transform="([^"]+)"/', $transform, $matches)) {
            $rotateTransform = $matches[1];
        }
        $groupTransform = trim($rotateTransform . $scaleTransform);
        $groupTransformAttr = $groupTransform !== '' ? sprintf(' transform="%s"', self::escapeAttr($groupTransform)) : '';
        $strokeAttrs = $strokeWidth > 0
            ? sprintf(' stroke="%s" stroke-width="%s" stroke-opacity="%s" paint-order="stroke fill" stroke-linejoin="round"', $strokeColor, self::formatFloat($strokeWidth), self::formatFloat($strokeOpacity))
            : '';
        $spacingAttr = $letterSpacing !== 0.0 ? sprintf(' letter-spacing="%s"', self::formatFloat($letterSpacing)) : '';

        $svg = $defs !== '' ? '<defs>' . $defs . '</defs>' : '';
        $svg .= sprintf('<g opacity="%s"%s%s>', self::formatFloat($opacity), $groupTransformAttr, $filterAttr);
        if ($imageFillSvg !== '') {
            $svg .= $imageFillSvg;
        }
        foreach ($lines as $index => $line) {
            $svg .= sprintf(
                '<text x="%s" y="%s" text-anchor="%s" font-family="%s" font-size="%d" font-weight="%s" fill="%s"%s%s>%s</text>',
                self::formatFloat($textX),
                self::formatFloat($baseline + $index * $fontSize * $lineHeight),
                $anchor,
                $fontFamily,
                $fontSize,
                $fontWeight,
                $fill,
                $spacingAttr,
                $strokeAttrs,
                self::escapeText($line)
            );
        }
        return $svg . '</g>';
    }

    protected static function renderImageTextFill(array $layer, array $lines, string $fontFamily, int $fontSize, string $fontWeight, string $anchor, float $textX, int $baseline, float $lineHeight, float $letterSpacing, int $x, int $y, int $w, int $h): string
    {
        $src = trim((string)($layer['fillImage'] ?? $layer['fillImageUrl'] ?? ''));
        if ($src === '') {
            return '';
        }

        $maskId = 'txt_mask_' . substr(md5($src . json_encode($lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . $x . $y . $w . $h), 0, 12);
        $spacingAttr = $letterSpacing !== 0.0 ? sprintf(' letter-spacing="%s"', self::formatFloat($letterSpacing)) : '';
        $maskText = '';
        foreach ($lines as $index => $line) {
            $maskText .= sprintf(
                '<text x="%s" y="%s" text-anchor="%s" font-family="%s" font-size="%d" font-weight="%s" fill="#FFFFFF"%s>%s</text>',
                self::formatFloat($textX),
                self::formatFloat($baseline + $index * $fontSize * $lineHeight),
                $anchor,
                $fontFamily,
                $fontSize,
                $fontWeight,
                $spacingAttr,
                self::escapeText($line)
            );
        }

        $fit = match ((string)($layer['fillImageFit'] ?? 'cover')) {
            'contain' => 'xMidYMid meet',
            'stretch' => 'none',
            default => 'xMidYMid slice',
        };

        return sprintf(
            '<defs><mask id="%s" maskUnits="userSpaceOnUse" x="%d" y="%d" width="%d" height="%d">%s</mask></defs><image href="%s" x="%d" y="%d" width="%d" height="%d" preserveAspectRatio="%s" mask="url(#%s)"/>',
            $maskId,
            $x,
            $y,
            $w,
            $h,
            $maskText,
            self::escapeAttr($src),
            $x,
            $y,
            $w,
            $h,
            $fit,
            $maskId
        );
    }

    protected static function textGradientDef(string $id, string $from, string $to, int $angle): string
    {
        $radians = deg2rad($angle);
        $x = cos($radians);
        $y = sin($radians);
        $x1 = 50 - $x * 50;
        $y1 = 50 - $y * 50;
        $x2 = 50 + $x * 50;
        $y2 = 50 + $y * 50;
        return sprintf(
            '<linearGradient id="%s" x1="%s%%" y1="%s%%" x2="%s%%" y2="%s%%"><stop offset="0%%" stop-color="%s"/><stop offset="100%%" stop-color="%s"/></linearGradient>',
            self::escapeAttr($id),
            self::formatFloat($x1),
            self::formatFloat($y1),
            self::formatFloat($x2),
            self::formatFloat($y2),
            $from,
            $to
        );
    }

    protected static function textShadowFilterDef(string $id, string $color, float $opacity, float $blur, float $offsetX, float $offsetY): string
    {
        return sprintf(
            '<filter id="%s" x="-50%%" y="-50%%" width="200%%" height="200%%"><feDropShadow dx="%s" dy="%s" stdDeviation="%s" flood-color="%s" flood-opacity="%s"/></filter>',
            self::escapeAttr($id),
            self::formatFloat($offsetX),
            self::formatFloat($offsetY),
            self::formatFloat($blur / 2),
            $color,
            self::formatFloat($opacity)
        );
    }

    protected static function renderImageLayer(array $layer, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $src = trim((string)($layer['src'] ?? ''));
        if ($src === '') {
            return '';
        }
        $fit = (string)($layer['fit'] ?? 'cover') === 'contain' ? 'xMidYMid meet' : 'xMidYMid slice';
        $radius = self::clampInt((int)($layer['radius'] ?? 0), 0, 240);
        $backgroundFill = self::normalizeOptionalColor((string)($layer['backgroundFill'] ?? ''));
        $clipId = $radius > 0 ? 'clip_' . substr(md5($src . $x . $y . $w . $h . $radius . $backgroundFill), 0, 12) : '';
        $clip = $radius > 0
            ? sprintf('<clipPath id="%s" clipPathUnits="userSpaceOnUse"><rect x="%d" y="%d" width="%d" height="%d" rx="%d" ry="%d"/></clipPath>', $clipId, $x, $y, $w, $h, $radius, $radius)
            : '';
        $background = $backgroundFill !== ''
            ? sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="%d" ry="%d" fill="%s"/>', $x, $y, $w, $h, $radius, $radius, $backgroundFill)
            : '';
        $image = sprintf(
            '<image href="%s" x="%d" y="%d" width="%d" height="%d" preserveAspectRatio="%s"/>',
            self::escapeAttr($src),
            $x,
            $y,
            $w,
            $h,
            $fit
        );
        if ($radius > 0) {
            return sprintf(
                '%s<g opacity="%s"%s clip-path="url(#%s)">%s</g>',
                $clip,
                self::formatFloat($opacity),
                $transform,
                $clipId,
                $background . $image
            );
        }
        return sprintf(
            '<g opacity="%s"%s>%s</g>',
            self::formatFloat($opacity),
            $transform,
            $background . $image
        );
    }

    protected static function renderQrcodeLayer(array $layer, array $snapshot, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $src = trim((string)($layer['src'] ?? ''));
        if ($src === '') {
            $src = trim((string)($snapshot['qrcode_image'] ?? ''));
        }
        if ($src === '') {
            return '';
        }
        $w = max(StaffScheduleConfirmLetterService::QRCODE_MIN_SIZE, $w);
        $h = max(StaffScheduleConfirmLetterService::QRCODE_MIN_SIZE, $h);
        $padding = self::clampInt((int)($layer['padding'] ?? 18), 0, 80);
        $radius = self::clampInt((int)($layer['radius'] ?? 18), 0, 120);
        $innerX = $x + $padding;
        $innerY = $y + $padding;
        $innerW = max(1, $w - $padding * 2);
        $innerH = max(1, $h - $padding * 2);
        return sprintf(
            '<g opacity="%s"%s><rect x="%d" y="%d" width="%d" height="%d" rx="%d" fill="#FFFFFF"/><image href="%s" x="%d" y="%d" width="%d" height="%d" preserveAspectRatio="xMidYMid meet"/></g>',
            self::formatFloat($opacity),
            $transform,
            $x,
            $y,
            $w,
            $h,
            $radius,
            self::escapeAttr($src),
            $innerX,
            $innerY,
            $innerW,
            $innerH
        );
    }

    protected static function renderRectLayer(array $layer, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $fill = self::normalizeColor((string)($layer['fill'] ?? '#FFFFFF'), '#FFFFFF');
        $stroke = self::normalizeColor((string)($layer['stroke'] ?? ''), '');
        $strokeWidth = self::clampInt((int)($layer['strokeWidth'] ?? 0), 0, 40);
        $radius = self::clampInt((int)($layer['radius'] ?? 0), 0, 240);
        $strokeAttrs = $stroke !== '' && $strokeWidth > 0 ? sprintf(' stroke="%s" stroke-width="%d"', $stroke, $strokeWidth) : '';
        return sprintf(
            '<rect x="%d" y="%d" width="%d" height="%d" rx="%d" fill="%s" opacity="%s"%s%s/>',
            $x,
            $y,
            $w,
            $h,
            $radius,
            $fill,
            self::formatFloat($opacity),
            $strokeAttrs,
            $transform
        );
    }

    protected static function renderLineLayer(array $layer, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $stroke = self::normalizeColor((string)($layer['stroke'] ?? '#D8C08B'), '#D8C08B');
        $strokeWidth = self::clampInt((int)($layer['strokeWidth'] ?? 2), 1, 40);
        return sprintf(
            '<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="%d" opacity="%s"%s/>',
            $x,
            $y,
            $x + $w,
            $y + $h,
            $stroke,
            $strokeWidth,
            self::formatFloat($opacity),
            $transform
        );
    }

    protected static function renderTemplate(string $template, array $snapshot): string
    {
        $vars = is_array($snapshot['variables'] ?? null) ? $snapshot['variables'] : [];
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string)$value, $template);
        }
        return trim($template);
    }

    protected static function renderLegacy(array $snapshot, string $fontFamily): string
    {
        $textTheme = (string)($snapshot['text_theme'] ?? 'light');
        $primaryColor = $textTheme === 'dark' ? '#1F1B16' : '#FFF7E6';
        $secondaryColor = $textTheme === 'dark' ? '#6F624F' : '#D8C08B';
        $mutedColor = $textTheme === 'dark' ? '#8E806C' : '#B7A27A';
        $cardFill = $textTheme === 'dark' ? 'rgba(255, 255, 255, 0.78)' : 'rgba(18, 16, 13, 0.38)';
        $backgroundColor = self::normalizeColor((string)($snapshot['background_color'] ?? '#191713'), '#191713');
        $backgroundImage = trim((string)($snapshot['background_image'] ?? ''));
        $showQrcode = (int)($snapshot['show_qrcode'] ?? 0) === 1 && trim((string)($snapshot['qrcode_image'] ?? '')) !== '';

        $contentLines = self::wrapText((string)($snapshot['content_text'] ?? ''), 20, 5);
        $footerLines = self::wrapText((string)($snapshot['footer_note'] ?? ''), 18, 3);
        $metaLines = array_values(array_filter([
            (string)($snapshot['service_date_label'] ?? ''),
            (string)($snapshot['service_name'] ?? ''),
            (string)($snapshot['city_label'] ?? ''),
        ], static fn($item) => trim($item) !== ''));

        $imageSvg = '';
        if ($backgroundImage !== '') {
            $imageSvg = sprintf(
                '<image href="%s" x="0" y="0" width="1080" height="1920" preserveAspectRatio="xMidYMid slice" opacity="0.78"/>',
                self::escapeAttr($backgroundImage)
            );
        }

        $contentSvg = '';
        $y = 760;
        foreach ($contentLines as $line) {
            $contentSvg .= sprintf(
                '<text x="540" y="%d" text-anchor="middle" font-family="%s" font-size="54" fill="%s">%s</text>',
                $y,
                $fontFamily,
                $primaryColor,
                self::escapeText($line)
            );
            $y += 76;
        }

        $metaSvg = '';
        $metaY = 1120;
        foreach ($metaLines as $line) {
            $metaSvg .= sprintf(
                '<text x="540" y="%d" text-anchor="middle" font-family="%s" font-size="34" fill="%s">%s</text>',
                $metaY,
                $fontFamily,
                $secondaryColor,
                self::escapeText($line)
            );
            $metaY += 56;
        }

        $footerSvg = '';
        $footerY = 1660;
        foreach ($footerLines as $line) {
            $footerSvg .= sprintf(
                '<text x="540" y="%d" text-anchor="middle" font-family="%s" font-size="30" fill="%s">%s</text>',
                $footerY,
                $fontFamily,
                $mutedColor,
                self::escapeText($line)
            );
            $footerY += 44;
        }

        $qrcodeSvg = '';
        if ($showQrcode) {
            $qrcodeSvg = sprintf(
                '<rect x="438" y="1398" width="204" height="204" rx="18" fill="#FFFFFF" opacity="0.92"/><image href="%s" x="462" y="1422" width="156" height="156" preserveAspectRatio="xMidYMid meet"/>',
                self::escapeAttr((string)$snapshot['qrcode_image'])
            );
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="1080" height="1920" viewBox="0 0 1080 1920">
                <rect width="1080" height="1920" fill="%s"/>
                %s
                <rect width="1080" height="1920" fill="rgba(0,0,0,0.16)"/>
                <rect x="86" y="148" width="908" height="1624" rx="42" fill="%s" stroke="%s" stroke-width="2"/>
                <text x="540" y="380" text-anchor="middle" font-family="%s" font-size="96" font-weight="700" fill="%s">%s</text>
                <text x="540" y="468" text-anchor="middle" font-family="%s" font-size="30" letter-spacing="6" fill="%s">%s</text>
                <line x1="270" y1="560" x2="810" y2="560" stroke="%s" stroke-width="2" opacity="0.72"/>
                %s
                %s
                %s
                %s
                <text x="540" y="1796" text-anchor="middle" font-family="%s" font-size="28" fill="%s">%s</text>
            </svg>',
            $backgroundColor,
            $imageSvg,
            $cardFill,
            $secondaryColor,
            $fontFamily,
            $primaryColor,
            self::escapeText((string)($snapshot['title'] ?? '档期已定')),
            $fontFamily,
            $secondaryColor,
            self::escapeText((string)($snapshot['subtitle'] ?? 'SCHEDULE RESERVED')),
            $secondaryColor,
            $contentSvg,
            $metaSvg,
            $qrcodeSvg,
            $footerSvg,
            $fontFamily,
            $mutedColor,
            self::escapeText((string)($snapshot['staff_name'] ?? ''))
        );
    }

    protected static function wrapText(string $text, int $maxChars, int $maxLines): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?: '');
        if ($text === '') {
            return [];
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lines = [];
        $current = '';
        foreach ($chars as $char) {
            $current .= $char;
            if (mb_strlen($current, 'UTF-8') >= $maxChars) {
                $lines[] = $current;
                $current = '';
                if (count($lines) >= $maxLines) {
                    break;
                }
            }
        }
        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }
        return $lines;
    }

    public static function wrapTextByWidth(string $text, int $maxWidth, int $fontSize, int $maxLines, float $letterSpacing = 0): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));
        if ($text === '') { return []; }
        $lines = [];
        foreach (explode("\n", $text) as $paragraph) {
            $current = '';
            $width = 0.0;
            foreach (preg_split('//u', $paragraph, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
                // ponytail: 编辑器与服务端统一使用保守字宽；需要精确字距时改为同字体度量。
                $charWidth = $fontSize * (preg_match('/[\x{0020}-\x{007E}]/u', $char) ? (str_contains('MW@%', $char) ? 1 : 0.65) : 1);
                $spacing = $current === '' ? 0 : max(0, $letterSpacing);
                if ($current !== '' && $width + $spacing + $charWidth > $maxWidth) {
                    $lines[] = $current;
                    $current = '';
                    $width = 0;
                    $spacing = 0;
                }
                if ($charWidth > $maxWidth) { throw new \RuntimeException('海报文字超出图层宽度，请缩小字号或加宽图层'); }
                $current .= $char;
                $width += $spacing + $charWidth;
            }
            $lines[] = $current;
        }
        if (count($lines) > $maxLines) {
            throw new \RuntimeException('海报文字超出图层高度，请缩小字号或增高图层');
        }
        return $lines;
    }

    protected static function normalizeColor(string $color, string $fallback): string
    {
        $color = trim($color);
        if ($color === '' && $fallback === '') {
            return '';
        }

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color) === 1) {
            return strtoupper($color);
        }

        if (preg_match('/^rgba?\(\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})\s*,\s*([0-9]{1,3})(?:\s*,\s*([01](?:\.\d+)?|\.\d+))?\s*\)$/i', $color, $matches) === 1) {
            $red = self::clampInt((int)$matches[1], 0, 255);
            $green = self::clampInt((int)$matches[2], 0, 255);
            $blue = self::clampInt((int)$matches[3], 0, 255);
            if (isset($matches[4]) && $matches[4] !== '') {
                $alpha = self::clampFloat((float)$matches[4], 0.0, 1.0);
                return sprintf('rgba(%d, %d, %d, %s)', $red, $green, $blue, self::formatFloat($alpha));
            }

            return sprintf('rgb(%d, %d, %d)', $red, $green, $blue);
        }

        return $fallback === $color ? $fallback : self::normalizeColor($fallback, '');
    }

    protected static function normalizeOptionalColor(string $color): string
    {
        $color = trim($color);
        return $color === '' ? '' : self::normalizeColor($color, '');
    }

    protected static function normalizeOpacity($value): float
    {
        return self::clampFloat((float)$value, 0, 1);
    }

    protected static function clampInt(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    protected static function clampFloat(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    protected static function formatFloat(float $value): string
    {
        return rtrim(rtrim(sprintf('%.4F', $value), '0'), '.');
    }

    protected static function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    protected static function escapeText(string $value): string
    {
        return htmlspecialchars($value, ENT_NOQUOTES | ENT_XML1, 'UTF-8');
    }
}
