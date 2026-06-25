<?php
// +----------------------------------------------------------------------
// | 婚庆服务预约系统 - 单量月报海报渲染
// +----------------------------------------------------------------------

declare(strict_types=1);

namespace app\common\service;

class MonthlyReportRenderer
{
    public static function render(array $snapshot, array $options = []): string
    {
        $fontOptions = $options['font_options'] ?? [];
        $fontFamily = self::escapeAttr((string)($fontOptions['font_family'] ?? 'Microsoft YaHei, PingFang SC, sans-serif'));
        $design = is_array($snapshot['design_config'] ?? null) ? $snapshot['design_config'] : [];
        $canvas = is_array($design['canvas'] ?? null) ? $design['canvas'] : [];
        $width = self::clampInt((int)($canvas['width'] ?? 1080), 320, 2160);
        $height = self::clampInt((int)($canvas['height'] ?? 1920), 480, 5200);
        $background = is_array($design['background'] ?? null) ? $design['background'] : [];
        $backgroundColor = self::normalizeColor((string)($background['color'] ?? '#191713'), '#191713');
        $backgroundType = (string)($background['type'] ?? 'color');
        $backgroundImage = trim((string)($background['image'] ?? ''));
        $backgroundFit = (string)($background['fit'] ?? 'cover') === 'contain' ? 'xMidYMid meet' : 'xMidYMid slice';
        $backgroundOpacity = self::normalizeOpacity($background['opacity'] ?? 1);

        $layers = array_values(array_filter($design['layers'] ?? [], static fn($layer) => is_array($layer)));
        usort($layers, static fn($a, $b) => ((int)($a['z'] ?? 0)) <=> ((int)($b['z'] ?? 0)));

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

        $layerSvg = '';
        foreach ($layers as $layer) {
            if ((int)($layer['visible'] ?? 1) !== 1) {
                continue;
            }
            $layerSvg .= self::renderLayer($layer, $snapshot, $fontFamily);
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
        if ($type === 'repeater') {
            return self::renderRepeaterLayer($layer, $snapshot, $fontFamily);
        }

        $x = self::clampInt((int)($layer['x'] ?? 0), -2160, 4320);
        $y = self::clampInt((int)($layer['y'] ?? 0), -5200, 10400);
        $w = self::clampInt((int)($layer['w'] ?? 100), 1, 2160);
        $h = $type === 'line'
            ? self::clampInt((int)($layer['h'] ?? 0), 0, 5200)
            : self::clampInt((int)($layer['h'] ?? 100), 1, 5200);
        $opacity = $type === 'qrcode' ? 1.0 : self::normalizeOpacity($layer['opacity'] ?? 1);
        $rotate = self::clampFloat((float)($layer['rotate'] ?? 0), -360, 360);
        $transform = $rotate !== 0.0
            ? sprintf(' transform="rotate(%s %s %s)"', self::formatFloat($rotate), self::formatFloat($x + $w / 2), self::formatFloat($y + $h / 2))
            : '';

        return match ($type) {
            'image' => self::renderImageLayer($layer, $snapshot, [], $x, $y, $w, $h, $opacity, $transform),
            'qrcode' => self::renderQrcodeLayer($layer, $snapshot, $x, $y, $w, $h, $opacity, $transform),
            'rect' => self::renderRectLayer($layer, $x, $y, $w, $h, $opacity, $transform),
            'line' => self::renderLineLayer($layer, $x, $y, $w, $h, $opacity, $transform),
            default => self::renderTextLayer($layer, $snapshot, [], $fontFamily, $x, $y, $w, $h, $opacity, $transform),
        };
    }

    protected static function renderRepeaterLayer(array $layer, array $snapshot, string $fontFamily): string
    {
        $source = (string)($layer['source'] ?? 'ranking');
        $items = match ($source) {
            'top' => is_array($snapshot['top_staffs'] ?? null) ? $snapshot['top_staffs'] : [],
            default => is_array($snapshot['ranking_staffs'] ?? null) ? $snapshot['ranking_staffs'] : [],
        };
        if (empty($items)) {
            return '';
        }

        $x = self::clampInt((int)($layer['x'] ?? 0), -2160, 4320);
        $y = self::clampInt((int)($layer['y'] ?? 0), -5200, 10400);
        $columns = self::clampInt((int)($layer['columns'] ?? 3), 1, 6);
        $cardW = self::clampInt((int)($layer['card_width'] ?? 300), 80, 2160);
        $cardH = self::clampInt((int)($layer['card_height'] ?? 300), 80, 2160);
        $gapX = self::clampInt((int)($layer['gap_x'] ?? 24), 0, 320);
        $gapY = self::clampInt((int)($layer['gap_y'] ?? 24), 0, 320);
        $rawRepeaterAlign = (string)($layer['align'] ?? 'left');
        $align = in_array($rawRepeaterAlign, ['left', 'center', 'right'], true) ? $rawRepeaterAlign : 'left';
        $limit = self::clampInt((int)($layer['limit'] ?? 0), 0, 120);
        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }
        $cardLayers = array_values(array_filter($layer['card_layers'] ?? [], static fn($item) => is_array($item)));
        if (empty($cardLayers)) {
            $cardLayers = self::defaultCardLayers($cardW, $cardH);
        }

        $svg = '<g>';
        foreach (array_values($items) as $index => $item) {
            $col = $index % $columns;
            $row = intdiv($index, $columns);
            $rowStart = $row * $columns;
            $itemsInRow = min($columns, count($items) - $rowStart);
            $rowWidth = $itemsInRow * $cardW + max(0, $itemsInRow - 1) * $gapX;
            $fullWidth = $columns * $cardW + max(0, $columns - 1) * $gapX;
            $rowShift = match ($align) {
                'center' => max(0, ($fullWidth - $rowWidth) / 2),
                'right' => max(0, $fullWidth - $rowWidth),
                default => 0,
            };
            $offsetX = $x + (int)round($rowShift) + $col * ($cardW + $gapX);
            $offsetY = $y + $row * ($cardH + $gapY);
            $itemVars = is_array($item) ? $item : [];
            $itemVars['rank'] = (string)($itemVars['rank'] ?? ($index + 1));

            $svg .= sprintf('<g transform="translate(%d %d)">', $offsetX, $offsetY);
            foreach ($cardLayers as $cardLayer) {
                if ((int)($cardLayer['visible'] ?? 1) !== 1) {
                    continue;
                }
                $svg .= self::renderCardLayer($cardLayer, $snapshot, $itemVars, $fontFamily, $cardW, $cardH);
            }
            $svg .= '</g>';
        }
        return $svg . '</g>';
    }

    protected static function renderCardLayer(array $layer, array $snapshot, array $itemVars, string $fontFamily, int $cardW, int $cardH): string
    {
        $type = (string)($layer['type'] ?? 'text');
        $x = self::clampInt((int)($layer['x'] ?? 0), -2160, 4320);
        $y = self::clampInt((int)($layer['y'] ?? 0), -5200, 10400);
        $w = self::clampInt((int)($layer['w'] ?? $cardW), 1, 2160);
        $h = $type === 'line'
            ? self::clampInt((int)($layer['h'] ?? 0), 0, 5200)
            : self::clampInt((int)($layer['h'] ?? $cardH), 1, 5200);
        $opacity = self::normalizeOpacity($layer['opacity'] ?? 1);
        $rotate = self::clampFloat((float)($layer['rotate'] ?? 0), -360, 360);
        $transform = $rotate !== 0.0
            ? sprintf(' transform="rotate(%s %s %s)"', self::formatFloat($rotate), self::formatFloat($x + $w / 2), self::formatFloat($y + $h / 2))
            : '';

        return match ($type) {
            'image' => self::renderImageLayer($layer, $snapshot, $itemVars, $x, $y, $w, $h, $opacity, $transform),
            'rect' => self::renderRectLayer($layer, $x, $y, $w, $h, $opacity, $transform),
            'line' => self::renderLineLayer($layer, $x, $y, $w, $h, $opacity, $transform),
            default => self::renderTextLayer($layer, $snapshot, $itemVars, $fontFamily, $x, $y, $w, $h, $opacity, $transform),
        };
    }

    protected static function renderTextLayer(array $layer, array $snapshot, array $itemVars, string $fontFamily, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $text = self::renderTemplate((string)($layer['text'] ?? ''), $snapshot, $itemVars);
        $fontSize = self::clampInt((int)($layer['fontSize'] ?? 42), 10, 400);
        $lineHeight = self::clampFloat((float)($layer['lineHeight'] ?? 1.25), 0.8, 3);
        $color = self::normalizeColor((string)($layer['color'] ?? '#FFF7E6'), '#FFF7E6');
        $rawFontWeight = (string)($layer['fontWeight'] ?? '400');
        $fontWeight = in_array($rawFontWeight, ['300', '400', '500', '600', '700', '800', '900'], true) ? $rawFontWeight : '400';
        $rawAlign = (string)($layer['align'] ?? 'center');
        $align = in_array($rawAlign, ['left', 'center', 'right'], true) ? $rawAlign : 'center';
        $anchor = $align === 'left' ? 'start' : ($align === 'right' ? 'end' : 'middle');
        $textX = $align === 'left' ? $x : ($align === 'right' ? $x + $w : $x + $w / 2);
        $maxLines = max(1, (int)floor($h / max(1, $fontSize * $lineHeight)));
        $lines = self::wrapTextByWidth($text, $w, $fontSize, $maxLines);
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
        $groupTransform = trim(($transform !== '' ? trim(substr($transform, 11, -1)) : '') . $scaleTransform);
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

    protected static function renderImageLayer(array $layer, array $snapshot, array $itemVars, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $src = trim(self::renderTemplate((string)($layer['src'] ?? ''), $snapshot, $itemVars));
        if ($src === '') {
            return '';
        }
        $fit = (string)($layer['fit'] ?? 'cover') === 'contain' ? 'xMidYMid meet' : 'xMidYMid slice';
        $radius = self::clampInt((int)($layer['radius'] ?? 0), 0, 260);
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
            return sprintf('%s<g opacity="%s"%s clip-path="url(#%s)">%s</g>', $clip, self::formatFloat($opacity), $transform, $clipId, $background . $image);
        }
        return sprintf('<g opacity="%s"%s>%s</g>', self::formatFloat($opacity), $transform, $background . $image);
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
        $padding = self::clampInt((int)($layer['padding'] ?? 18), 0, 80);
        $radius = self::clampInt((int)($layer['radius'] ?? 18), 0, 120);
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
            $x + $padding,
            $y + $padding,
            max(1, $w - $padding * 2),
            max(1, $h - $padding * 2)
        );
    }

    protected static function renderRectLayer(array $layer, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $fill = self::normalizeColor((string)($layer['fill'] ?? '#FFFFFF'), '#FFFFFF');
        $stroke = self::normalizeColor((string)($layer['stroke'] ?? ''), '');
        $strokeWidth = self::clampInt((int)($layer['strokeWidth'] ?? 0), 0, 40);
        $radius = self::clampInt((int)($layer['radius'] ?? 0), 0, 260);
        $strokeAttrs = $stroke !== '' && $strokeWidth > 0 ? sprintf(' stroke="%s" stroke-width="%d"', $stroke, $strokeWidth) : '';
        return sprintf('<rect x="%d" y="%d" width="%d" height="%d" rx="%d" fill="%s" opacity="%s"%s%s/>', $x, $y, $w, $h, $radius, $fill, self::formatFloat($opacity), $strokeAttrs, $transform);
    }

    protected static function renderLineLayer(array $layer, int $x, int $y, int $w, int $h, float $opacity, string $transform): string
    {
        $stroke = self::normalizeColor((string)($layer['stroke'] ?? '#D8C08B'), '#D8C08B');
        $strokeWidth = self::clampInt((int)($layer['strokeWidth'] ?? 2), 1, 40);
        return sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="%d" opacity="%s"%s/>', $x, $y, $x + $w, $y + $h, $stroke, $strokeWidth, self::formatFloat($opacity), $transform);
    }

    protected static function defaultCardLayers(int $cardW, int $cardH): array
    {
        return [
            ['id' => 'photo', 'type' => 'image', 'x' => 0, 'y' => 0, 'w' => $cardW, 'h' => $cardH, 'src' => '{photo_url}', 'fit' => 'cover'],
            ['id' => 'english', 'type' => 'text', 'x' => 16, 'y' => 18, 'w' => $cardW - 32, 'h' => 36, 'text' => '{english_name}', 'fontSize' => 26, 'fontWeight' => '800', 'align' => 'left', 'color' => '#FFFFFF'],
            ['id' => 'name', 'type' => 'text', 'x' => 16, 'y' => 56, 'w' => $cardW - 32, 'h' => 32, 'text' => '*{chinese_name}', 'fontSize' => 24, 'fontWeight' => '500', 'align' => 'left', 'color' => '#FFFFFF'],
            ['id' => 'count', 'type' => 'text', 'x' => 16, 'y' => 95, 'w' => 110, 'h' => 80, 'text' => '{count}', 'fontSize' => 72, 'fontWeight' => '300', 'align' => 'left', 'color' => '#FFF9ED'],
        ];
    }

    protected static function renderTemplate(string $template, array $snapshot, array $itemVars = []): string
    {
        $vars = is_array($snapshot['variables'] ?? null) ? $snapshot['variables'] : [];
        $vars += $itemVars;
        foreach ($vars as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{' . $key . '}', (string)$value, $template);
            }
        }
        return trim($template);
    }

    protected static function wrapTextByWidth(string $text, int $maxWidth, int $fontSize, int $maxLines): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?: '');
        if ($text === '') {
            return [];
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $line = '';
        $lines = [];
        $maxChars = max(1, (int)floor($maxWidth / max(1, $fontSize * 0.58)));
        foreach ($chars as $char) {
            $line .= $char;
            if (mb_strlen($line, 'UTF-8') >= $maxChars) {
                $lines[] = $line;
                $line = '';
                if (count($lines) >= $maxLines) {
                    break;
                }
            }
        }
        if ($line !== '' && count($lines) < $maxLines) {
            $lines[] = $line;
        }
        return $lines;
    }

    protected static function normalizeColor(string $color, string $fallback): string
    {
        $color = trim($color);
        if ($color === '' && $fallback === '') {
            return '';
        }
        if (preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color)) {
            return $color;
        }
        if (preg_match('/^rgba?\([0-9.,\s]+\)$/i', $color)) {
            return $color;
        }
        return $fallback;
    }

    protected static function normalizeOptionalColor(string $color): string
    {
        return self::normalizeColor($color, '');
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
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    protected static function escapeAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    protected static function escapeText(string $value): string
    {
        return htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
