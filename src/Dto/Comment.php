<?php

declare(strict_types=1);

namespace Kokov1ch\DromExampleClient\Dto;

final readonly class Comment
{
    public function __construct(
        public int $id,
        public string $name,
        public string $text,
    ) {
    }
}
