<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Encargado = 'encargado';
    case Vendedor = 'vendedor';
    case Almacenero = 'almacenero';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Encargado => 'Encargado de Sede',
            self::Vendedor => 'Vendedor',
            self::Almacenero => 'Almacenero',
        };
    }
}
