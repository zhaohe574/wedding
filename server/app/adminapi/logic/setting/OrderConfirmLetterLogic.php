<?php
declare(strict_types=1);

namespace app\adminapi\logic\setting;

use app\common\logic\BaseLogic;
use app\common\service\OrderConfirmLetterService;

class OrderConfirmLetterLogic extends BaseLogic
{
    public static function getConfig(): array
    {
        return OrderConfirmLetterService::getTemplateConfig();
    }

    public static function setConfig(array $params): void
    {
        OrderConfirmLetterService::setTemplateConfig($params);
    }
}
