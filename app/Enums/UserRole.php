<?php

namespace App\Enums;

enum UserRole: string
{
    case Participant = 'participant';
    case Reviewer = 'reviewer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Participant => 'Peserta',
            self::Reviewer => 'Reviewer/Juri',
            self::Admin => 'Admin',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Participant => 'portal.dashboard',
            self::Reviewer => 'reviewer.dashboard',
            self::Admin => 'admin.dashboard',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
