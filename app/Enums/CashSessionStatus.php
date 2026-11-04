<?php

namespace App\Enums;

enum CashSessionStatus: string
{
    case Abierta = 'abierta';
    case Precierre = 'precierre';
    case Aprobada = 'aprobada';

    public function label(): string
    {
        return match ($this) {
            self::Abierta => 'Abierta',
            self::Precierre => 'En Precierre',
            self::Aprobada => 'Aprobada',
        };
    }
}
