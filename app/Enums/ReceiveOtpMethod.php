<?php

namespace App\Enums;

enum ReceiveOtpMethod: int
{
    case Email = 1;
    case Sms = 2;
    case None = -1;

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'Sms',
            self::None => 'Khong co',
        };
    }

    public static function labelFrom(?int $code): string
    {
        return self::tryFrom((int) $code)?->label() ?? "Khong xac dinh ({$code})";
    }
}
