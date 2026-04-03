<?php

declare(strict_types=1);

namespace LiquidRazor\ConfigLoader\Contract;

interface ConfigParserInterface
{
    /**
     * @return array<mixed>
     */
    public function parse(string $contents, string $source): array;
}
