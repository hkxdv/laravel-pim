<?php

declare(strict_types=1);

namespace Modules\Assistant\App\Events;

final class McpToolDebug
{
    public function __construct(
        public string $tool,
        public string $phase,
        public mixed $data = []
    ) {}
}
