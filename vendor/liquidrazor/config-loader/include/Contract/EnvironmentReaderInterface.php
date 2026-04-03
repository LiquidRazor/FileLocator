<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Contract;

interface EnvironmentReaderInterface
{
    public function get(string $name): ?string;
}
