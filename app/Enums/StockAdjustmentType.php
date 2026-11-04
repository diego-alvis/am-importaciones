<?php

namespace App\Enums;

enum StockAdjustmentType: string
{
    case Add = 'add';
    case Subtract = 'subtract';
    case Set = 'set';

    public function label(): string
    {
        return match ($this) {
            self::Add => 'Agregar Stock (+)',
            self::Subtract => 'Disminuir Stock (-)',
            self::Set => 'Ajuste Fijo (=)',
        };
    }
}
