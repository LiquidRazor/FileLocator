<?php

declare(strict_types=1);

namespace LiquidRazor\FileLocator\Enum;

enum UnreadablePathMode: string
{
    case Skip = 'skip';
    case Fail = 'fail';
}
