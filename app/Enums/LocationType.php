<?php

namespace App\Enums;

enum LocationType: string
{
    case Almacen = 'almacen';
    case Sede = 'sede';

    public function label(): string
    {
        return match ($this) {
            self::Almacen => 'Almacén Central',
            self::Sede => 'Sede Comercial',
        };
    }
}
