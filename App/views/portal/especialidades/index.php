<?php
/**
 * Gestor de especialidades médicas — orquestador.
 *
 * Divide la interfaz en header con cuatro pestañas (Resumen, Especialidades,
 * Médicos y Servicio médico), la pestaña Resumen con tarjetas de estadísticas
 * y los listados filtrables con sus modales de edición, asignación de
 * especialidades a médicos, registro de bloqueos y detalle de bloqueos.
 */
$tab = (string) ($tab ?? 'resumen');
$estadisticas = $estadisticas ?? [];
$topMedicos = $topMedicos ?? [];
$serviciosRecientes = $serviciosRecientes ?? [];
$bloqueosRecientes = $bloqueosRecientes ?? [];
$catalogoEspecialidades = $catalogoEspecialidades ?? [];
$catalogoMedicos = $catalogoMedicos ?? [];
$acreditacionesPorMedico = $acreditacionesPorMedico ?? [];

$especialidades = $especialidades ?? [];
$totalEsp = (int) ($totalEsp ?? 0);
$paginaEsp = (int) ($paginaEsp ?? 1);
$paginasEsp = (int) ($paginasEsp ?? 1);
$porPaginaEsp = (int) ($porPaginaEsp ?? 10);
$numerosPaginaEsp = $numerosPaginaEsp ?? [];
$filtroNombre = (string) ($filtroNombre ?? '');
$filtroEstado = (string) ($filtroEstado ?? '');

$medicos = $medicos ?? [];
$totalMed = (int) ($totalMed ?? 0);
$paginaMed = (int) ($paginaMed ?? 1);
$paginasMed = (int) ($paginasMed ?? 1);
$porPaginaMed = (int) ($porPaginaMed ?? 10);
$numerosPaginaMed = $numerosPaginaMed ?? [];
$filtroTextoMed = (string) ($filtroTextoMed ?? '');
$filtroAcredit = (string) ($filtroAcredit ?? '');

$servicios = $servicios ?? [];
$totalServ = (int) ($totalServ ?? 0);
$paginaServ = (int) ($paginaServ ?? 1);
$paginasServ = (int) ($paginasServ ?? 1);
$porPaginaServ = (int) ($porPaginaServ ?? 10);
$numerosPaginaServ = $numerosPaginaServ ?? [];
$filtroEspecialidad = (string) ($filtroEspecialidad ?? '');
$filtroEstadoServ = (string) ($filtroEstadoServ ?? '');
$filtroMedico = (string) ($filtroMedico ?? '');

$tabContent = match ($tab) {
    'especialidades' => \Lib\Core\View::render('portal.especialidades.datos', [
        'especialidades' => $especialidades,
        'total' => $totalEsp,
        'pagina' => $paginaEsp,
        'paginas' => $paginasEsp,
        'porPagina' => $porPaginaEsp,
        'numerosPagina' => $numerosPaginaEsp,
        'filtroNombre' => $filtroNombre,
        'filtroEstado' => $filtroEstado,
    ]),
    'medicos' => \Lib\Core\View::render('portal.especialidades.medicos', [
        'medicos' => $medicos,
        'total' => $totalMed,
        'pagina' => $paginaMed,
        'paginas' => $paginasMed,
        'porPagina' => $porPaginaMed,
        'numerosPagina' => $numerosPaginaMed,
        'filtroTextoMed' => $filtroTextoMed,
        'filtroAcredit' => $filtroAcredit,
        'catalogoEspecialidades' => $catalogoEspecialidades,
        'catalogoMedicos' => $catalogoMedicos,
        'estadisticas' => $estadisticas,
    ]),
    'servicio' => \Lib\Core\View::render('portal.especialidades.servicio', [
        'servicios' => $servicios,
        'total' => $totalServ,
        'pagina' => $paginaServ,
        'paginas' => $paginasServ,
        'porPagina' => $porPaginaServ,
        'numerosPagina' => $numerosPaginaServ,
        'catalogoEspecialidades' => $catalogoEspecialidades,
        'catalogoMedicos' => $catalogoMedicos,
        'acreditacionesPorMedico' => $acreditacionesPorMedico,
        'filtroEspecialidad' => $filtroEspecialidad,
        'filtroEstado' => $filtroEstadoServ,
        'filtroMedico' => $filtroMedico,
    ]),
    default => \Lib\Core\View::render('portal.especialidades.resumen', [
        'estadisticas' => $estadisticas,
        'topMedicos' => $topMedicos,
        'serviciosRecientes' => $serviciosRecientes,
        'bloqueosRecientes' => $bloqueosRecientes,
    ]),
};
?>

<style>
    .es-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 18px;
    }

    .es-tabs {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 6px 8px;
        margin-bottom: 20px;
    }

    .es-tabs .nav-link {
        color: #44545c;
        font-weight: 600;
        font-size: 0.92rem;
        border-radius: 10px;
        padding: 10px 18px;
    }

    .es-tabs .nav-link.active {
        background: var(--portal-primary);
        color: #fff;
    }

    .es-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
        margin-bottom: 20px;
    }

    .es-stat {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .es-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        flex-shrink: 0;
    }

    .es-stat-icon.total {
        background: #e8f4f3;
        color: var(--portal-primary);
    }

    .es-stat-icon.activas {
        background: #e7f7ed;
        color: #1e8e3e;
    }

    .es-stat-icon.inactivas {
        background: #fdecea;
        color: #c0392b;
    }

    .es-stat-icon.acred {
        background: #e8f0fe;
        color: #1a73e8;
    }

    .es-stat-icon.medicos {
        background: #e6f7f6;
        color: #0f8b84;
    }

    .es-stat-icon.servicios {
        background: #fef7e0;
        color: #b8860b;
    }

    .es-stat-icon.bloqueos {
        background: #f3e8fd;
        color: #7b1fa2;
    }

    .es-stat-num {
        font-size: 1.6rem;
        font-weight: 800;
        color: #0b2a38;
        line-height: 1.1;
    }

    .es-stat-label {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7c83;
    }

    .es-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
        align-items: start;
    }

    .es-card {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }

    .es-card:last-child {
        margin-bottom: 0;
    }

    .es-card h5 {
        font-weight: 700;
        color: #0b2a38;
        font-size: 1rem;
        margin-bottom: 18px;
    }

    .es-fila {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #eef2f1;
    }

    .es-fila:last-child {
        border-bottom: 0;
    }

    .es-fila-main {
        min-width: 0;
        flex: 1 1 auto;
    }

    .es-fila-main strong {
        display: block;
        color: #0b2a38;
        line-height: 1.25;
        word-break: break-word;
    }

    .es-fila-main small {
        display: block;
        color: #6b7c83;
        font-size: 0.82rem;
    }

    .es-barra {
        height: 8px;
        border-radius: 6px;
        background: #eef2f1;
        overflow: hidden;
        margin-top: 6px;
    }

    .es-barra span {
        display: block;
        height: 100%;
        border-radius: 6px;
        background: var(--portal-primary);
    }

    .es-filtros {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 20px;
    }

    .es-filtros .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    .es-tabla {
        background: #fff;
        border: 1px solid #e4e9e7;
        border-radius: 16px;
        overflow: hidden;
    }

    .es-tabla table {
        margin-bottom: 0;
    }

    .es-tabla thead th {
        background: #f4f7f6;
        border-bottom: 1px solid #e4e9e7;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #44545c;
        white-space: nowrap;
    }

    .es-tabla td {
        vertical-align: middle;
        border-color: #eef2f1;
        font-size: 0.92rem;
    }

    .es-nombre {
        font-weight: 600;
        color: #0b2a38;
        line-height: 1.25;
    }

    .es-nombre small {
        display: block;
        font-weight: 500;
        color: #6b7c83;
        font-size: 0.8rem;
    }

    .es-fecha {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        color: #0b2a38;
    }

    .es-paginacion {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
    }

    .es-paginacion .page-link {
        color: #44545c;
        border-color: #e0e6e3;
    }

    .es-paginacion .page-item.active .page-link {
        background: var(--portal-primary);
        border-color: var(--portal-primary);
    }

    .es-form .form-control,
    .es-form .form-control:focus {
        background: #f7f9f8;
        border-color: #e0e6e3;
        box-shadow: none;
    }

    .es-form label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #44545c;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .es-alerta-error {
        display: none;
    }

    .es-ayuda {
        background: #f4f9ff;
        border: 1px dashed #b9d4f3;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 0.86rem;
        color: #3d6a8f;
    }

    .es-bloqueo-item {
        background: #f7faf9;
        border: 1px solid #eef2f1;
        border-left: 4px solid var(--portal-primary);
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 10px;
    }

    .es-bloqueo-item.anulado {
        border-left-color: #d9534f;
        opacity: 0.75;
    }

    .es-bloqueo-item strong {
        color: #0b2a38;
        display: block;
        font-size: 0.92rem;
    }

    .es-bloqueo-item small {
        color: #6b7c83;
        font-size: 0.82rem;
    }

    @media (max-width: 575.98px) {
        .es-filtros {
            padding: 16px;
        }

        .es-tabla thead {
            display: none;
        }

        .es-tabla,
        .es-tabla tbody,
        .es-tabla tr,
        .es-tabla td {
            display: block;
            width: 100%;
        }

        .es-tabla tr {
            border-bottom: 1px solid #eef2f1;
            padding: 10px 0;
        }

        .es-tabla td {
            border: 0;
            padding: 4px 16px;
        }
    }
</style>

<?= \Lib\Core\View::render('portal.especialidades.header', [
    'tab' => $tab,
    'statTotal' => (int) ($estadisticas['total'] ?? 0),
    'statMedicos' => (int) ($estadisticas['medicos'] ?? 0),
]) ?>

<?= $tabContent ?>
