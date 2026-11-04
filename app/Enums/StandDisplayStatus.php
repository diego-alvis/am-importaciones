<?php

namespace App\Enums;

enum StandDisplayStatus: string
{
    case Abierta = 'abierta';
    case Precierre = 'precierre';
    case Cerrada = 'cerrada';

    public function label(): string
    {
        return match ($this) {
            self::Abierta => 'Caja Abierta',
            self::Precierre => 'En Precierre',
            self::Cerrada => 'Cerrada',
        };
    }
}
