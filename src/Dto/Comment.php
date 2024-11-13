<?php

declare(strict_types=1);

namespace kokov1ch\DromExampleClient\Dto;

final readonly class Comment
{
    public function __construct(
        public int $id,
        public string $name,
        public string $text,
    ) {
    }
}
