<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class Cita extends Model
{
    protected string $table = 'cita';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'codigo',
        'id_paciente',
        'id_ficha_agenda',
        'estado',
        'motivo_consulta',
        'id_usuario_registro',
        'observaciones',
    ];

    protected bool $timestamps = false;
}
