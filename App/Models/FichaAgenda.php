<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class FichaAgenda extends Model
{
    protected string $table = 'ficha_agenda';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_horario',
        'fecha',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'capacidad',
        'estado',
    ];

    protected bool $timestamps = false;
}
