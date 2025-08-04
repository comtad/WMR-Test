<?php

namespace App\Enums;

class MediaStatus
{
    public const LOADED = 'Загружен';
    public const PROCESSING = 'В обработке';
    public const ERROR = 'Ошибка';
    public const COMPLETED = 'Завершён';

    public const COLORS = [
        self::LOADED => 'text-blue-500',
        self::PROCESSING => 'text-yellow-500',
        self::ERROR => 'text-red-600',
        self::COMPLETED => 'text-green-600',
    ];

    public static function all(): array
    {
        return [
            self::LOADED,
            self::PROCESSING,
            self::ERROR,
            self::COMPLETED,
        ];
    }

    public static function getColor(string $status): string
    {
        return self::COLORS[$status] ?? 'text-gray-500';
    }
}
