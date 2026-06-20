<?php
declare(strict_types=1);

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\OrderConfirmLetterFontService;

class OrderConfirmLetterLogic extends BaseLogic
{
    public static function getConfig(): array
    {
        return [
            'remark_template' => '',
            'payment_node' => '',
        ];
    }

    public static function setConfig(array $params): void
    {
        unset($params);
    }

    public static function fontLists(): array
    {
        return OrderConfirmLetterFontService::lists();
    }

    public static function uploadFont(): array
    {
        return OrderConfirmLetterFontService::upload();
    }

    public static function setFont(array $params): array
    {
        return OrderConfirmLetterFontService::setConfig($params);
    }

    public static function deleteFont(array $params): void
    {
        OrderConfirmLetterFontService::delete((string) ($params['file'] ?? ''));
    }

    public static function checkFont(): array
    {
        return OrderConfirmLetterFontService::diagnostics();
    }
}
