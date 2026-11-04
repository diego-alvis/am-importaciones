<?php

namespace App\Enums;

enum TransferStatus: string
{
    case Solicitado = 'solicitado';
    case EnTransito = 'en_transito';
    case Recibido = 'recibido';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::Solicitado => 'Solicitado',
            self::EnTransito => 'En Tránsito',
            self::Recibido => 'Recibido',
            self::Cancelado => 'Cancelado',
        };
    }
}
