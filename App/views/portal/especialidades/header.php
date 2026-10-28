<?php
/**
 * Header del gestor de especialidades médicas: título, badge total y
 * las cuatro pestañas (Resumen, Especialidades, Médicos y Servicio
 * médico), más el modal de confirmación reutilizable por todo el módulo.
 *
 * Flujo de trabajo:
 *   Especialidades → se da de alta el catálogo;
 *   Médicos → se asignan las especialidades del médico (medico_especialidad);
 *   Servicio médico → con esa acreditación se incorpora al médico al trabajo
 *   (servicio_medico) y de ahí nacen horarios y fichas.
 */
$tab = (string) ($tab ?? 'resumen');
$statTotal = (int) ($statTotal ?? 0);
$statMedicos = (int) ($statMedicos ?? 0);

/** URL de pestaña sin arrastrar filtros ni paginación. */
$urlTab = static function (string $nuevaTab): string {
    $base = route('dashboard_especialidades') ?? '/portal/dashboard/Especialidades';

    return $nuevaTab === 'resumen' ? $base : $base . '?' . http_build_query(['tab' => $nuevaTab]);
};
?>

<div class="es-header">
    <div>
        <h3 class="m-0"><i class="fas fa-book-medical text-secondary"></i> Gestor de especialidades médicas</h3>
        <p class="text-muted mb-0">Catálogo de especialidades, especialidades de los médicos, servicios y bloqueos de agenda.</p>
    </div>
    <span class="badge badge-secondary align-self-center"><?= $statTotal ?> especialidad(es)</span>
</div>

<ul class="nav nav-tabs es-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'resumen' ? 'active' : '' ?>"
            href="<?= e($urlTab('resumen')) ?>">
            <i class="fas fa-chart-pie mr-1"></i> Resumen
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'especialidades' ? 'active' : '' ?>"
            href="<?= e($urlTab('especialidades')) ?>">
            <i class="fas fa-book-medical mr-1"></i> Especialidades
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'medicos' ? 'active' : '' ?>"
            href="<?= e($urlTab('medicos')) ?>">
            <i class="fas fa-user-md mr-1"></i> Médicos
            <?php if ($statMedicos > 0) { ?>
                <span class="badge badge-light ml-1"><?= $statMedicos ?></span>
            <?php } ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'servicio' ? 'active' : '' ?>"
            href="<?= e($urlTab('servicio')) ?>">
            <i class="fas fa-stethoscope mr-1"></i> Servicio médico
        </a>
    </li>
</ul>

<!-- Modal de confirmación compartido por todas las pestañas -->
<div class="modal fade" id="modal-confirmar" tabindex="-1" role="dialog" aria-labelledby="modal-confirmar-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" id="modal-confirmar-header" style="background:#c0392b;color:#fff;">
                <h5 class="modal-title" id="modal-confirmar-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <span id="confirmar-titulo">Confirmar acción</span>
                </h5>
                <button type="button" class="close" style="color:#fff;" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmar-mensaje" class="mb-0" style="white-space:pre-line;"></p>
                <div id="confirmar-ayuda" class="es-ayuda mt-3" style="display:none;">
                    <i class="fas fa-info-circle"></i> <span id="confirmar-ayuda-texto"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-accion">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        var alConfirmar = null;

        /**
         * Abre el modal de confirmación.
         * opciones: { titulo, mensaje, ayuda, btn, destructivo }
         * alConfirmar: función a ejecutar si el usuario confirma.
         */
        window.espConfirmar = function (opciones, alConfirmarCallback) {
            opciones = opciones || {};
            alConfirmar = typeof alConfirmarCallback === 'function' ? alConfirmarCallback : null;

            $('#confirmar-titulo').text(opciones.titulo || 'Confirmar acción');
            $('#confirmar-mensaje').text(opciones.mensaje || '');

            if (opciones.ayuda) {
                $('#confirmar-ayuda-texto').text(opciones.ayuda);
                $('#confirmar-ayuda').show();
            } else {
                $('#confirmar-ayuda').hide().find('span').text('');
            }

            var destructivo = opciones.destructivo !== false;

            $('#btn-confirmar-accion')
                .text(opciones.btn || (destructivo ? 'Sí, continuar' : 'Confirmar'))
                .removeClass('btn-danger btn-primary')
                .addClass(destructivo ? 'btn-danger' : 'btn-primary');

            $('#modal-confirmar-header').css('background', destructivo ? '#c0392b' : '#0f8b84');
            $('#modal-confirmar').modal('show');
        };

        $('#btn-confirmar-accion').on('click', function () {
            var callback = alConfirmar;
            alConfirmar = null;
            $('#modal-confirmar').modal('hide');

            if (callback) {
                callback();
            }
        });

        $('#modal-confirmar').on('hidden.bs.modal', function () {
            alConfirmar = null;
        });

        /** Notificación flotante (siempre disponible aunque falle notification.js). */
        window.espNotificar = function (tipo, mensaje) {
            if (typeof window.showNotification === 'function') {
                window.showNotification(tipo === 'success' ? 'success' : tipo === 'warning' ? 'warning' : 'error', mensaje);
                return;
            }

            alert(mensaje);
        };
    });
</script>
