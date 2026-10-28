<?php

declare(strict_types=1);

namespace App\Models;

use Lib\Core\Model;

class HorarioAtencion extends Model
{
    protected string $table = 'horario_atencion';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'id_servicio_medico',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'duracion_ficha',
        'fecha_desde',
        'fecha_hasta',
        'estado',
    ];

    protected array $casts = [
        'estado' => 'boolean',
    ];

    protected bool $timestamps = false;
}
