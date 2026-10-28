<?php
/**
 * Reporte PDF del gestor de especialidades médicas (dompdf).
 *
 * Se genera desde PortalEspecidadesMedicasController::pdf() con ?tipo=
 * especialidades (catálogo) o tipo=servicio (acreditaciones), aplicando
 * los mismos filtros que el listado en pantalla y sin paginación.
 * Estilo sobrio con DejaVu Sans (acentos y símbolos garantizados).
 */
$tipo = (string) ($tipo ?? 'especialidades');
$registros = $registros ?? [];
$filtros = $filtros ?? [];
$total = (int) ($total ?? 0);
$hoy = (string) ($hoy ?? date('d/m/Y'));
$hora = (string) ($hora ?? date('H:i'));
$config = $config ?? [];

$esEspecialidades = $tipo !== 'servicio';

$clinica = (string) ($config['clinica_nombre'] ?? 'Clínica Vida Salud');
$telefono = (string) ($config['clinica_telefono'] ?? '');
$direccion = (string) ($config['clinica_direccion'] ?? '');
$correo = (string) ($config['clinica_correo'] ?? '');
$colorPrimario = preg_match('/^#[a-fA-F0-9]{6}$/', (string) ($config['tema_color_primario'] ?? ''))
    ? (string) $config['tema_color_primario']
    : '#0f8b84';
$colorOscuro = '#0b2a38';

/** Descripción legible de los filtros aplicados. */
$etiquetasFiltros = [];

if ($esEspecialidades) {
    $mapa = [
        'nombre' => 'Nombre',
        'estado' => 'Estado',
    ];
} else {
    $mapa = [
        'especialidad' => 'Especialidad',
        'estado_serv' => 'Estado',
        'medico' => 'Médico',
    ];
}

foreach ($mapa as $clave => $etiqueta) {
    $valor = trim((string) ($filtros[$clave] ?? ''));

    if ($valor === '') {
        continue;
    }

    if ($clave === 'estado' && in_array($valor, ['1', '0'], true)) {
        $valor = $valor === '1' ? 'Activa' : 'Inactiva';
    }

    $etiquetasFiltros[$etiqueta] = $valor;
}

if ($etiquetasFiltros === []) {
    $etiquetasFiltros['Filtros'] = $esEspecialidades
        ? 'Sin filtros (todas las especialidades)'
        : 'Sin filtros (todos los servicios médicos)';
}

/** Resumen de conteos para las tarjetas del reporte. */
$conteoActivos = 0;
$conteoInactivos = 0;
$conteoBloqueos = 0;

foreach ($registros as $registro) {
    if ($esEspecialidades) {
        if ((bool) ($registro['estado'] ?? false)) {
            $conteoActivos++;
        } else {
            $conteoInactivos++;
        }
    } else {
        if ((string) ($registro['estado'] ?? '') === 'ACTIVO') {
            $conteoActivos++;
        } else {
            $conteoInactivos++;
        }

        $conteoBloqueos += (int) ($registro['bloqueos_activos'] ?? 0);
    }
}

$tituloReporte = $esEspecialidades
    ? 'Reporte de especialidades médicas'
    : 'Reporte de servicio médico';
$subtituloReporte = $esEspecialidades
    ? 'Catálogo de especialidades de la clínica con sus conteos de médicos y servicios activos.'
    : 'Acreditaciones de los médicos en servicio con su vigencia y bloqueos de agenda.';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= e($tituloReporte) ?> · <?= e($clinica) ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #2c3e45;
            margin: 0;
            padding: 0;
        }

        .cabecera {
            border-bottom: 3px solid <?= e($colorPrimario) ?>;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .cabecera-top {
            display: table;
            width: 100%;
        }

        .cabecera-izq,
        .cabecera-der {
            display: table-cell;
            vertical-align: bottom;
        }

        .cabecera-der {
            text-align: right;
        }

        .clinica-nombre {
            font-size: 19px;
            font-weight: bold;
            color: <?= e($colorPrimario) ?>;
            margin: 0;
            letter-spacing: -0.3px;
        }

        .clinica-datos {
            font-size: 8.5px;
            color: #7f8c8d;
            margin-top: 4px;
            line-height: 1.5;
        }

        .reporte-etiqueta {
            display: inline-block;
            background: <?= e($colorPrimario) ?>;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px 14px;
            border-radius: 3px;
        }

        .reporte-fecha {
            font-size: 9px;
            color: #7f8c8d;
            margin-top: 6px;
        }

        .reporte-titulo {
            font-size: 15px;
            font-weight: bold;
            color: <?= e($colorOscuro) ?>;
            margin: 14px 0 4px 0;
        }

        .reporte-subtitulo {
            font-size: 10px;
            color: #7f8c8d;
            margin: 0 0 14px 0;
        }

        .filtros {
            background: #f4f9f8;
            border: 1px solid #d9e6e3;
            border-left: 4px solid <?= e($colorPrimario) ?>;
            border-radius: 4px;
            padding: 9px 12px;
            margin-bottom: 14px;
        }

        .filtros-titulo {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: <?= e($colorPrimario) ?>;
            margin-bottom: 5px;
        }

        .filtros-lista {
            margin: 0;
            font-size: 9.5px;
            line-height: 1.6;
        }

        .filtros-lista strong {
            color: <?= e($colorOscuro) ?>;
        }

        .resumen {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }

        .resumen-celda {
            display: table-cell;
            width: 33%;
            background: #ffffff;
            border: 1px solid #e0e6e3;
            border-top: 3px solid <?= e($colorPrimario) ?>;
            border-radius: 4px;
            padding: 8px 10px;
            text-align: center;
        }

        .resumen-valor {
            font-size: 17px;
            font-weight: bold;
            color: <?= e($colorOscuro) ?>;
            line-height: 1.2;
        }

        .resumen-etiqueta {
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #7f8c8d;
            margin-top: 2px;
        }

        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        table.datos thead th {
            background: <?= e($colorOscuro) ?>;
            color: #ffffff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 8px 7px;
            text-align: left;
            border: none;
        }

        table.datos thead th.num {
            text-align: center;
        }

        table.datos tbody td {
            padding: 6px 7px;
            border-bottom: 1px solid #e8eeec;
            font-size: 9.5px;
            vertical-align: middle;
        }

        table.datos tbody tr:nth-child(even) td {
            background: #f7faf9;
        }

        .celda-num {
            text-align: center;
            color: #7f8c8d;
            font-size: 9px;
        }

        .celda-nombre {
            font-weight: bold;
            color: <?= e($colorOscuro) ?> !important;
        }

        .celda-nombre small {
            display: block;
            font-weight: normal;
            font-size: 8px;
            color: #7f8c8d;
        }

        .badge-estado {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #ffffff;
            padding: 2px 7px;
            border-radius: 3px;
        }

        .badge-activo {
            background: #2fae60;
        }

        .badge-inactivo {
            background: #d9534f;
        }

        .badge-suspendido {
            background: #e0a800;
        }

        .badge-finalizado {
            background: #7f8c8d;
        }

        .sin-registros {
            text-align: center;
            padding: 18px;
            color: #7f8c8d;
            font-size: 10px;
            border: 1px dashed #d9e6e3;
            border-radius: 4px;
        }

        .pie {
            margin-top: 16px;
            padding-top: 8px;
            border-top: 1px solid #e0e6e3;
            font-size: 8.5px;
            color: #7f8c8d;
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <div class="cabecera">
        <div class="cabecera-top">
            <div class="cabecera-izq">
                <p class="clinica-nombre"><?= e($clinica) ?></p>
                <div class="clinica-datos">
                    <?php $datosClinica = array_filter([$direccion, $telefono, $correo]); ?>
                    <?= e(implode(' · ', $datosClinica)) ?>
                </div>
            </div>
            <div class="cabecera-der">
                <span class="reporte-etiqueta"><?= $esEspecialidades ? 'Especialidades' : 'Servicio médico' ?></span>
                <div class="reporte-fecha">Generado el <?= e($hoy) ?> a las <?= e($hora) ?></div>
            </div>
        </div>

        <h1 class="reporte-titulo"><?= e($tituloReporte) ?></h1>
        <p class="reporte-subtitulo"><?= e($subtituloReporte) ?></p>
    </div>

    <div class="filtros">
        <div class="filtros-titulo">Filtros aplicados</div>
        <p class="filtros-lista">
            <?php $primero = true; ?>
            <?php foreach ($etiquetasFiltros as $etiqueta => $valor) { ?>
                <?php if (!$primero) { ?> · <?php } ?><?php $primero = false; ?>
                <strong><?= e($etiqueta) ?>:</strong> <?= e($valor) ?>
            <?php } ?>
        </p>
    </div>

    <div class="resumen">
        <div class="resumen-celda">
            <div class="resumen-valor"><?= $total ?></div>
            <div class="resumen-etiqueta"><?= $esEspecialidades ? 'Especialidades' : 'Servicios' ?></div>
        </div>
        <div class="resumen-celda">
            <div class="resumen-valor"><?= $conteoActivos ?></div>
            <div class="resumen-etiqueta"><?= $esEspecialidades ? 'Activas' : 'Activos' ?></div>
        </div>
        <div class="resumen-celda">
            <div class="resumen-valor"><?= $esEspecialidades ? $conteoInactivos : $conteoBloqueos ?></div>
            <div class="resumen-etiqueta"><?= $esEspecialidades ? 'Inactivas' : 'Bloqueos activos' ?></div>
        </div>
    </div>

    <?php if ($registros === []) { ?>
        <div class="sin-registros">
            No hay registros que coincidan con los filtros aplicados.
        </div>
    <?php } elseif ($esEspecialidades) { ?>
        <table class="datos">
            <thead>
                <tr>
                    <th style="width:34%;">Especialidad</th>
                    <th style="width:36%;">Descripción</th>
                    <th class="num" style="width:10%;">Médicos</th>
                    <th class="num" style="width:10%;">Servicios</th>
                    <th style="width:10%;">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $registro) {
                    $descripcion = trim((string) ($registro['descripcion'] ?? ''));
                    $activo = (bool) ($registro['estado'] ?? false);
                ?>
                    <tr>
                        <td class="celda-nombre"><?= e((string) ($registro['nombre'] ?? '')) ?></td>
                        <td><?= $descripcion !== '' ? e($descripcion) : '—' ?></td>
                        <td class="celda-num"><?= (int) ($registro['total_medicos'] ?? 0) ?></td>
                        <td class="celda-num"><?= (int) ($registro['servicios_activos'] ?? 0) ?></td>
                        <td>
                            <span class="badge-estado <?= $activo ? 'badge-activo' : 'badge-inactivo' ?>">
                                <?= $activo ? 'Activa' : 'Inactiva' ?>
                            </span>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <table class="datos">
            <thead>
                <tr>
                    <th style="width:26%;">Médico</th>
                    <th style="width:22%;">Especialidad</th>
                    <th style="width:20%;">Vigencia</th>
                    <th style="width:14%;">Estado</th>
                    <th class="num" style="width:18%;">Bloqueos activos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $registro) {
                    $estado = (string) ($registro['estado'] ?? '');
                    $badgeClase = match ($estado) {
                        'ACTIVO' => 'badge-activo',
                        'SUSPENDIDO' => 'badge-suspendido',
                        default => 'badge-finalizado',
                    };
                    $vigencia = (string) ($registro['fecha_inicio'] ?? '');
                    if (trim((string) ($registro['fecha_fin'] ?? '')) !== '') {
                        $vigencia .= ' → ' . (string) $registro['fecha_fin'];
                    }
                ?>
                    <tr>
                        <td class="celda-nombre">
                            <?= e((string) ($registro['nombre_completo'] ?? '')) ?>
                            <small>
                                CI <?= e((string) ($registro['ci'] ?? '')) ?>
                                · Colegiado <?= e((string) ($registro['nro_colegiado'] ?? '') !== '' ? (string) $registro['nro_colegiado'] : '—') ?>
                            </small>
                        </td>
                        <td><?= e((string) ($registro['especialidad'] ?? '')) ?></td>
                        <td><?= e($vigencia !== '' ? $vigencia : '—') ?></td>
                        <td>
                            <span class="badge-estado <?= $badgeClase ?>"><?= e($estado) ?></span>
                        </td>
                        <td class="celda-num"><?= (int) ($registro['bloqueos_activos'] ?? 0) ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>

    <div class="pie">
        Documento generado desde el panel administrativo de <?= e($clinica) ?> ·
        Gestor de especialidades médicas · <?= e($hoy) ?> <?= e($hora) ?>
    </div>
</body>

</html>
