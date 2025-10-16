<?php

declare(strict_types=1);

namespace App\DTO;

use JsonSerializable;

/**
 * Value Object para representar una estadística enriquecida del panel.
 */
final readonly class EnhancedStat implements JsonSerializable
{
    public function __construct(
        public string $key,
        public string $title,
        public string $description,
        public string $icon,
        public int|float $value
    ) {}

    /**
     * Serializa la estadística para enviarla al frontend.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'key' => $this->key,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'value' => $this->value,
        ];
    }
}
