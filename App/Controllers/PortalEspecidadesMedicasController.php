<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Controller;
use App\Models\BloqueoAgenda;
use App\Models\Especialidad;
use App\Models\MedicoEspecialidad;
use App\Models\ServicioMedico;
use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Core\View;
use Lib\Support\Csrf;

/**
 * Gestor de especialidades médicas (dashboard administrativo).
 *
 * - El administrador crea y administra las especialidades (tabla especialidad);
 *   al registrarlas su estado arranca en TRUE.
 * - Cada médico suma sus especialidades en medico_especialidad y la acreditación
 *   para ejercer en la clínica queda en servicio_medico: esa rama identifica a
 *   los médicos incorporados al hospital y de ahí dependen las demás secciones
 *   (horarios y citas).
 * - Tres pestañas: Resumen (estadísticas), Especialidades (catálogo) y
 *   Servicio médico (médicos en relación a las especialidades).
 * - Por cada médico de servicio_medico se registra un bloqueo de agenda
 *   (bloqueo_agenda) que identifica si tiene algún impedimento para asistir
 *   al hospital dentro de su horario de trabajo.
 * - Cada listado ofrece el reporte PDF con los filtros aplicados.
 */
class PortalEspecidadesMedicasController extends Controller
{
    private const POR_PAGINA = 10;

    private const ESTADOS_SERVICIO = ['ACTIVO', 'SUSPENDIDO', 'FINALIZADO'];

    /**
     * Pantalla del gestor de especialidades (3 pestañas).
     */
    public function index(Request $request): Response
    {
        $tab = (string) $request->get('tab', 'resumen');

        if (!in_array($tab, ['resumen', 'especialidades', 'medicos', 'servicio'], true)) {
            $tab = 'resumen';
        }

        $paginaEsp = max(1, (int) $request->get('page_esp', 1));
        $paginaServ = max(1, (int) $request->get('page_serv', 1));
        $paginaMed = max(1, (int) $request->get('page_med', 1));

        $filtrosEsp = $this->filtrosEspecialidades($request);
        $filtrosServ = $this->filtrosServicio($request);
        $filtrosMed = $this->filtrosMedicos($request);

        $estadisticas = Especialidad::estadisticas();
        $topMedicos = Especialidad::topMedicos(6);
        $serviciosRecientes = ServicioMedico::recientes(8);
        $bloqueosRecientes = BloqueoAgenda::recientes(6);
        $catalogo = Especialidad::activas();
        $catalogoMedicos = ServicioMedico::medicos();
        $acreditacionesPorMedico = MedicoEspecialidad::acreditacionesPorMedico();

        $listadoEsp = Especialidad::listar($filtrosEsp, $paginaEsp, self::POR_PAGINA);
        $listadoServ = ServicioMedico::listar($filtrosServ, $paginaServ, self::POR_PAGINA);
        $listadoMed = MedicoEspecialidad::listarMedicos($filtrosMed, $paginaMed, self::POR_PAGINA);

        return $this->view('portal.base.layout', [
            'rolClave' => $this->rolClave(),
            'nombre' => $this->nombre(),
            'active' => 'especialidades',
            'content' => View::render('portal.especialidades.index', [
                'tab' => $tab,
                'estadisticas' => $estadisticas,
                'topMedicos' => $topMedicos,
                'serviciosRecientes' => $serviciosRecientes,
                'bloqueosRecientes' => $bloqueosRecientes,
                'catalogoEspecialidades' => $catalogo,
                'catalogoMedicos' => $catalogoMedicos,
                'acreditacionesPorMedico' => $acreditacionesPorMedico,

                'especialidades' => $listadoEsp['items'],
                'totalEsp' => $listadoEsp['total'],
                'paginaEsp' => $listadoEsp['pagina'],
                'paginasEsp' => $listadoEsp['paginas'],
                'porPaginaEsp' => $listadoEsp['porPagina'],
                'numerosPaginaEsp' => $this->numerosPagina($listadoEsp['pagina'], $listadoEsp['paginas']),
                'filtroNombre' => $filtrosEsp['nombre'],
                'filtroEstado' => $filtrosEsp['estado'],

                'medicos' => $listadoMed['items'],
                'totalMed' => $listadoMed['total'],
                'paginaMed' => $listadoMed['pagina'],
                'paginasMed' => $listadoMed['paginas'],
                'porPaginaMed' => $listadoMed['porPagina'],
                'numerosPaginaMed' => $this->numerosPagina($listadoMed['pagina'], $listadoMed['paginas']),
                'filtroTextoMed' => $filtrosMed['medico'],
                'filtroAcredit' => $filtrosMed['acreditaciones'],

                'servicios' => $listadoServ['items'],
                'totalServ' => $listadoServ['total'],
                'paginaServ' => $listadoServ['pagina'],
                'paginasServ' => $listadoServ['paginas'],
                'porPaginaServ' => $listadoServ['porPagina'],
                'numerosPaginaServ' => $this->numerosPagina($listadoServ['pagina'], $listadoServ['paginas']),
                'filtroEspecialidad' => $filtrosServ['especialidad'],
                'filtroEstadoServ' => $filtrosServ['estado'],
                'filtroMedico' => $filtrosServ['medico'],
            ]),
        ]);
    }

    /**
     * Detalle de una especialidad (modal de edición, carga AJAX).
     */
    public function detalleEspecialidad(Request $request, int $id): Response
    {
        $especialidad = Especialidad::detalle($id);

        if ($especialidad === null) {
            return $this->json(['success' => false, 'message' => 'La especialidad no existe.'], 404);
        }

        return $this->json(['success' => true, 'especialidad' => $especialidad]);
    }

    /**
     * Asigna una o varias especialidades a un médico (POST).
     *
     * Crea los registros de medico_especialidad que habilitan sumar al
     * médico a servicio_medico (y desde ahí a horarios y fichas).
     */
    public function asignarEspecialidades(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $idMedico = (int) $request->input('id_medico', 0);
        $seleccion = (array) $request->input('especialidades', []);

        if ($idMedico <= 0) {
            return $this->json(['success' => false, 'message' => 'Médico no válido.'], 404);
        }

        $medico = MedicoEspecialidad::medico($idMedico);

        if ($medico === null) {
            return $this->json(['success' => false, 'message' => 'El médico no existe o no está activo.'], 404);
        }

        $ids = [];

        foreach ($seleccion as $idEspecialidad) {
            $idEspecialidad = (int) $idEspecialidad;

            if ($idEspecialidad > 0) {
                $ids[$idEspecialidad] = $idEspecialidad;
            }
        }

        $ids = array_values($ids);

        if ($ids === []) {
            return $this->json([
                'success' => false,
                'message' => 'Selecciona al menos una especialidad.',
            ], 422);
        }

        // Solo el catálogo activo puede asignarse.
        $catalogo = [];

        foreach (Especialidad::activas() as $fila) {
            $catalogo[(int) ($fila['id'] ?? 0)] = (string) ($fila['nombre'] ?? '');
        }

        $noValidas = [];

        foreach ($ids as $idEspecialidad) {
            if (!isset($catalogo[$idEspecialidad])) {
                $noValidas[] = $idEspecialidad;
            }
        }

        if ($noValidas !== []) {
            return $this->json([
                'success' => false,
                'message' => 'Una o más especialidades seleccionadas no existen o están dadas de baja.',
            ], 422);
        }

        try {
            $resultado = MedicoEspecialidad::asignar($idMedico, $ids);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'medico_especialidad',
                'INSERT',
                $idMedico,
                [],
                ['id_medico' => $idMedico, 'id_especialidades' => $ids],
                'Asignación de especialidades al médico ' . $medico['nombre_completo']
            );

            $mensaje = 'Se asignaron ' . $resultado['creadas'] . ' especialidad(es) a '
                . $medico['nombre_completo'] . '.';

            if ($resultado['existentes'] > 0) {
                $mensaje .= ' ' . $resultado['existentes'] . ' ya estaban asignadas.';
            }

            $mensaje .= ' Ya puede sumarse a Servicio médico.';

            return $this->json([
                'success' => true,
                'message' => $mensaje,
                'creadas' => $resultado['creadas'],
                'existentes' => $resultado['existentes'],
            ]);
        } catch (\Throwable $e) {
            error_log('Error al asignar especialidades: ' . $e->getMessage());

            $mensaje = 'No se pudieron asignar las especialidades.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }
    }

    /**
     * Quita una especialidad al médico (POST).
     *
     * Bloqueada mientras existan servicios médicos: borrar la acreditación
     * en cascada borraría servicios, horarios y fichas.
     */
    public function quitarEspecialidad(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $idMedico = (int) $request->input('id_medico', 0);
        $idEspecialidad = (int) $request->input('id_especialidad', 0);

        if ($idMedico <= 0 || $idEspecialidad <= 0) {
            return $this->json(['success' => false, 'message' => 'Datos no válidos.'], 422);
        }

        $medico = MedicoEspecialidad::medico($idMedico);

        if ($medico === null) {
            return $this->json(['success' => false, 'message' => 'El médico no existe o no está activo.'], 404);
        }

        if (!MedicoEspecialidad::existe($idMedico, $idEspecialidad)) {
            return $this->json([
                'success' => false,
                'message' => 'Esa especialidad no está asignada al médico.',
            ], 404);
        }

        $servicios = MedicoEspecialidad::tieneServicios($idMedico, $idEspecialidad);

        if ($servicios > 0) {
            return $this->json([
                'success' => false,
                'message' => 'No se puede quitar la especialidad: el médico tiene '
                    . $servicios . ' servicio(s) médico(s) en ella. De ese servicio se generan '
                    . 'los horarios y las fichas; finalízalo primero desde Servicio médico.',
            ], 422);
        }

        $especialidad = Especialidad::detalle($idEspecialidad);

        try {
            MedicoEspecialidad::quitar($idMedico, $idEspecialidad);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'medico_especialidad',
                'DELETE',
                $idMedico,
                ['id_medico' => $idMedico, 'id_especialidad' => $idEspecialidad],
                [],
                'Retiro de especialidad al médico ' . $medico['nombre_completo']
            );

            return $this->json([
                'success' => true,
                'message' => 'Se quitó '
                    . ($especialidad !== null ? '"' . $especialidad['nombre'] . '"' : 'la especialidad')
                    . ' al médico ' . $medico['nombre_completo'] . '.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al quitar especialidad del médico: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo quitar la especialidad del médico.',
            ], 500);
        }
    }

    /**
     * Crea o actualiza una especialidad (POST).
     */
    public function guardar(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $id = (int) $request->input('id', 0);
        $nombre = trim((string) $request->input('nombre', ''));
        $descripcion = trim((string) $request->input('descripcion', ''));

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            return $this->json([
                'success' => false,
                'message' => 'El nombre es obligatorio (máx. 100 caracteres).',
            ], 422);
        }

        if (mb_strlen($descripcion) > 2000) {
            return $this->json([
                'success' => false,
                'message' => 'La descripción no puede superar los 2000 caracteres.',
            ], 422);
        }

        if (!Especialidad::nombreDisponible($nombre, $id > 0 ? $id : null)) {
            return $this->json([
                'success' => false,
                'message' => 'Ya existe una especialidad con ese nombre.',
            ], 422);
        }

        $datos = [
            'nombre' => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
        ];

        try {
            if ($id > 0) {
                $actual = Especialidad::detalle($id);

                if ($actual === null) {
                    return $this->json([
                        'success' => false,
                        'message' => 'La especialidad no existe.',
                    ], 404);
                }

                Especialidad::query()->update($datos, ['id' => $id]);

                $this->registrarBitacoraAccion(
                    $this->idPersona(),
                    'especialidad',
                    'UPDATE',
                    $id,
                    ['nombre' => $actual['nombre'], 'descripcion' => $actual['descripcion']],
                    $datos,
                    'Edición de especialidad médica desde el gestor'
                );

                return $this->json([
                    'success' => true,
                    'message' => 'Especialidad actualizada correctamente.',
                ]);
            }

            $datos['estado'] = true;
            $idNuevo = (int) Especialidad::query()->insert($datos);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'especialidad',
                'INSERT',
                $idNuevo,
                [],
                $datos,
                'Alta de especialidad médica desde el gestor'
            );

            return $this->json([
                'success' => true,
                'message' => 'Especialidad registrada correctamente.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al guardar especialidad: ' . $e->getMessage());

            $mensaje = 'No se pudo guardar la especialidad. Revisa los datos e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }
    }

    /**
     * Crea o actualiza un servicio médico (POST).
     *
     * Para crear se exige que el médico ya tenga la especialidad asignada
     * en medico_especialidad (pestaña Médicos): solo entonces puede sumarse
     * al servicio y recibir horarios y fichas.
     */
    public function guardarServicio(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $id = (int) $request->input('id', 0);
        $estado = strtoupper(trim((string) $request->input('estado', 'ACTIVO')));
        $observaciones = trim((string) $request->input('observaciones', ''));
        $fechaInicio = $this->normalizarFecha((string) $request->input('fecha_inicio', ''));
        $fechaFin = $this->normalizarFecha((string) $request->input('fecha_fin', ''));

        if (!in_array($estado, self::ESTADOS_SERVICIO, true)) {
            return $this->json([
                'success' => false,
                'message' => 'Estado no válido. Debe ser ACTIVO, SUSPENDIDO o FINALIZADO.',
            ], 422);
        }

        if ($fechaInicio === null) {
            return $this->json([
                'success' => false,
                'message' => 'La fecha de inicio es obligatoria.',
            ], 422);
        }

        if ($fechaFin !== null && $fechaFin < $fechaInicio) {
            return $this->json([
                'success' => false,
                'message' => 'La fecha de fin no puede ser anterior a la de inicio.',
            ], 422);
        }

        if (mb_strlen($observaciones) > 2000) {
            return $this->json([
                'success' => false,
                'message' => 'Las observaciones no pueden superar los 2000 caracteres.',
            ], 422);
        }

        $datos = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estado' => $estado,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ];

        try {
            if ($id > 0) {
                $servicio = ServicioMedico::detalle($id);

                if ($servicio === null) {
                    return $this->json([
                        'success' => false,
                        'message' => 'El servicio médico no existe.',
                    ], 404);
                }

                $acreditacion = (int) ($servicio['id_medico_especialidad'] ?? 0);

                if ($acreditacion > 0 && ServicioMedico::tieneServicioActivo($acreditacion, $id)) {
                    return $this->json([
                        'success' => false,
                        'message' => 'El médico ya tiene otro servicio ACTIVO en esta especialidad.',
                    ], 422);
                }

                ServicioMedico::query()->update($datos, ['id' => $id]);

                $this->registrarBitacoraAccion(
                    $this->idPersona(),
                    'servicio_medico',
                    'UPDATE',
                    $id,
                    [
                        'fecha_inicio' => $servicio['fecha_inicio'],
                        'fecha_fin' => $servicio['fecha_fin'],
                        'estado' => $servicio['estado'],
                    ],
                    $datos,
                    'Edición de servicio médico desde el gestor'
                );

                return $this->json([
                    'success' => true,
                    'message' => 'Servicio médico actualizado correctamente.',
                ]);
            }

            // Alta: la acreditación debe existir previamente (pestaña Médicos).
            $idMedico = (int) $request->input('id_medico', 0);
            $idEspecialidad = (int) $request->input('especialidad', 0);

            if ($idMedico <= 0 || $idEspecialidad <= 0) {
                return $this->json([
                    'success' => false,
                    'message' => 'Selecciona el médico y la especialidad.',
                ], 422);
            }

            $medico = MedicoEspecialidad::medico($idMedico);

            if ($medico === null) {
                return $this->json([
                    'success' => false,
                    'message' => 'El médico no existe o no está activo.',
                ], 404);
            }

            $especialidad = Especialidad::detalle($idEspecialidad);

            if ($especialidad === null) {
                return $this->json([
                    'success' => false,
                    'message' => 'La especialidad no existe.',
                ], 404);
            }

            if (!$especialidad['estado']) {
                return $this->json([
                    'success' => false,
                    'message' => 'La especialidad está dada de baja: actívala antes de asignar servicios.',
                ], 422);
            }

            $acreditacion = ServicioMedico::acreditacion($idMedico, $idEspecialidad);

            if ($acreditacion === null) {
                return $this->json([
                    'success' => false,
                    'message' => 'El médico no tiene asignada esa especialidad. '
                        . 'Asíginala primero en la pestaña Médicos.',
                ], 422);
            }

            if (!(bool) $acreditacion['estado']) {
                return $this->json([
                    'success' => false,
                    'message' => 'La acreditación del médico está inactiva. '
                        . 'Reasigna la especialidad desde la pestaña Médicos.',
                ], 422);
            }

            $idAcreditacion = (int) $acreditacion['id'];

            if (ServicioMedico::tieneServicioActivo($idAcreditacion)) {
                return $this->json([
                    'success' => false,
                    'message' => 'El médico ya tiene un servicio médico ACTIVO en esa especialidad.',
                ], 422);
            }

            $datos['id_medico_especialidad'] = $idAcreditacion;
            $idNuevo = (int) ServicioMedico::query()->insert($datos);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'servicio_medico',
                'INSERT',
                $idNuevo,
                [],
                [
                    'id_medico' => $idMedico,
                    'id_especialidad' => $idEspecialidad,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'estado' => $estado,
                ],
                'Alta de servicio médico para ' . $medico['nombre_completo']
            );

            return $this->json([
                'success' => true,
                'message' => $medico['nombre_completo'] . ' se sumó al servicio médico de '
                    . $especialidad['nombre'] . '.',
            ]);
        } catch (\Throwable $e) {
            error_log('Error al guardar servicio médico: ' . $e->getMessage());

            $mensaje = 'No se pudo guardar el servicio médico. Revisa los datos e intenta de nuevo.';

            if (config('app.debug')) {
                $mensaje .= ' [debug] ' . $e->getMessage();
            }

            return $this->json(['success' => false, 'message' => $mensaje], 500);
        }
    }

    /**
     * Activa o desactiva una especialidad (POST).
     */
    public function cambiarEstado(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $id = (int) $request->input('id', 0);
        $estado = (string) $request->input('estado', '') === '1';

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Especialidad no válida.'], 404);
        }

        $especialidad = Especialidad::detalle($id);

        if ($especialidad === null) {
            return $this->json(['success' => false, 'message' => 'La especialidad no existe.'], 404);
        }

        // Baja bloqueada: de servicio_medico dependen los horarios y las fichas.
        if (!$estado) {
            $vinculados = Especialidad::serviciosVinculados($id);

            if ($vinculados > 0) {
                $activos = Especialidad::serviciosActivosDe($id);

                return $this->json([
                    'success' => false,
                    'message' => 'No se puede dar de baja la especialidad: tiene '
                        . $vinculados . ' servicio(s) médico(s) registrado(s)'
                        . ($activos > 0 ? ' (' . $activos . ' activo(s))' : '')
                        . '. De cada servicio médico se generan los horarios de atención '
                        . 'y las fichas; finalízalos o suspéndelos antes de dar de baja.',
                ], 422);
            }
        }

        try {
            Especialidad::query()->update(['estado' => $estado], ['id' => $id]);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'especialidad',
                'UPDATE',
                $id,
                ['estado' => $especialidad['estado']],
                ['estado' => $estado],
                'Cambio de estado de la especialidad ' . $especialidad['nombre']
            );

            return $this->json([
                'success' => true,
                'message' => $estado
                    ? 'Especialidad activada correctamente.'
                    : 'Especialidad desactivada correctamente.',
                'estado' => $estado,
            ]);
        } catch (\Throwable $e) {
            error_log('Error al cambiar estado de especialidad: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo cambiar el estado de la especialidad.',
            ], 500);
        }
    }

    /**
     * Bloqueos de agenda de un servicio médico (modal, carga AJAX).
     */
    public function bloqueosServicio(Request $request, int $id): Response
    {
        $servicio = ServicioMedico::detalle($id);

        if ($servicio === null) {
            return $this->json(['success' => false, 'message' => 'El servicio médico no existe.'], 404);
        }

        return $this->json([
            'success' => true,
            'servicio' => $servicio,
            'bloqueos' => BloqueoAgenda::porServicio($id),
        ]);
    }

    /**
     * Registra un bloqueo de agenda para un médico (POST).
     */
    public function bloquearAgenda(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $idServicio = (int) $request->input('id_servicio', 0);
        $inicio = $this->normalizarFechaHora((string) $request->input('fecha_hora_inicio', ''));
        $fin = $this->normalizarFechaHora((string) $request->input('fecha_hora_fin', ''));
        $motivo = trim((string) $request->input('motivo', ''));

        if ($idServicio <= 0 || ServicioMedico::detalle($idServicio) === null) {
            return $this->json(['success' => false, 'message' => 'El servicio médico no existe.'], 404);
        }

        if ($inicio === null || $fin === null) {
            return $this->json([
                'success' => false,
                'message' => 'Indica la fecha y hora de inicio y de fin del bloqueo.',
            ], 422);
        }

        if ($fin <= $inicio) {
            return $this->json([
                'success' => false,
                'message' => 'La fecha y hora de fin deben ser posteriores al inicio.',
            ], 422);
        }

        if ($motivo !== '' && mb_strlen($motivo) > 200) {
            return $this->json([
                'success' => false,
                'message' => 'El motivo no puede superar los 200 caracteres.',
            ], 422);
        }

        if (BloqueoAgenda::solapado($idServicio, $inicio, $fin)) {
            return $this->json([
                'success' => false,
                'message' => 'El rango se solapa con otro bloqueo activo de ese médico.',
            ], 422);
        }

        try {
            $idBloqueo = (int) BloqueoAgenda::query()->insert([
                'id_servicio_medico' => $idServicio,
                'fecha_hora_inicio' => $inicio,
                'fecha_hora_fin' => $fin,
                'motivo' => $motivo !== '' ? $motivo : null,
                'id_usuario_registro' => $this->idPersona(),
            ]);

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'bloqueo_agenda',
                'INSERT',
                $idBloqueo,
                [],
                [
                    'id_servicio_medico' => $idServicio,
                    'fecha_hora_inicio' => $inicio,
                    'fecha_hora_fin' => $fin,
                    'motivo' => $motivo,
                ],
                'Bloqueo de agenda registrado desde el gestor de especialidades'
            );

            return $this->json([
                'success' => true,
                'message' => 'Bloqueo de agenda registrado correctamente.',
                'bloqueos_activos' => BloqueoAgenda::contarActivos($idServicio),
            ]);
        } catch (\Throwable $e) {
            error_log('Error al registrar bloqueo de agenda: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo registrar el bloqueo. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Anula un bloqueo de agenda registrado (POST).
     */
    public function anularBloqueo(Request $request): Response
    {
        if (!$this->csrfValido($request)) {
            return $this->json(['success' => false, 'message' => 'Sesión expirada. Recarga la página.'], 403);
        }

        $id = (int) $request->input('id', 0);

        if ($id <= 0) {
            return $this->json(['success' => false, 'message' => 'Bloqueo no válido.'], 404);
        }

        $filas = \Lib\Core\Database::raw(
            'SELECT id, id_servicio_medico, estado FROM bloqueo_agenda WHERE id = :id',
            ['id' => $id]
        );

        if (!is_array($filas) || $filas === []) {
            return $this->json(['success' => false, 'message' => 'El bloqueo no existe.'], 404);
        }

        $bloqueo = $filas[0];

        if ((string) $bloqueo['estado'] !== 'ACTIVO') {
            return $this->json([
                'success' => false,
                'message' => 'Ese bloqueo ya fue anulado.',
            ], 422);
        }

        try {
            \Lib\Core\Database::raw(
                "UPDATE bloqueo_agenda SET estado = 'ANULADO' WHERE id = :id",
                ['id' => $id]
            );

            $idServicio = (int) $bloqueo['id_servicio_medico'];

            $this->registrarBitacoraAccion(
                $this->idPersona(),
                'bloqueo_agenda',
                'UPDATE',
                $id,
                ['estado' => 'ACTIVO'],
                ['estado' => 'ANULADO'],
                'Anulación de bloqueo de agenda desde el gestor de especialidades'
            );

            return $this->json([
                'success' => true,
                'message' => 'Bloqueo anulado correctamente.',
                'bloqueos_activos' => BloqueoAgenda::contarActivos($idServicio),
            ]);
        } catch (\Throwable $e) {
            error_log('Error al anular bloqueo: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'No se pudo anular el bloqueo. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Reporte PDF con los filtros aplicados (?tipo=especialidades|servicio).
     */
    public function pdf(Request $request): Response
    {
        $tipo = (string) $request->get('tipo', 'especialidades');

        if (!in_array($tipo, ['especialidades', 'servicio'], true)) {
            $tipo = 'especialidades';
        }

        $filtros = $tipo === 'especialidades'
            ? $this->filtrosEspecialidades($request)
            : $this->filtrosServicio($request);

        $registros = $tipo === 'especialidades'
            ? Especialidad::listarTodas($filtros)
            : ServicioMedico::listarTodos($filtros);

        $config = \App\Models\ConfiguracionSistema::publica();

        $html = View::render('portal.especialidades.pdf', [
            'tipo' => $tipo,
            'registros' => $registros,
            'filtros' => $filtros,
            'total' => count($registros),
            'hoy' => date('d/m/Y'),
            'hora' => date('H:i'),
            'config' => $config,
        ]);

        require_once BASE_PATH . '/resources/dompdf/autoload.inc.php';

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', BASE_PATH);
        $options->set('dpi', 120);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $titulo = $tipo === 'especialidades' ? 'especialidades' : 'servicio-medico';

        $canvas = $dompdf->getCanvas();
        $canvas->page_text(40, 812, 'Reporte de ' . ($tipo === 'especialidades' ? 'especialidades' : 'servicio médico')
            . ' · ' . (string) ($config['clinica_nombre'] ?? 'Clínica Vida Salud'), null, 7, [0.45, 0.45, 0.45]);
        $canvas->page_text(430, 812, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, [0.45, 0.45, 0.45]);

        return Response::make($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $titulo . '-' . date('Ymd-His') . '.pdf"',
            'Cache-Control' => 'no-store',
        ]);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Valida el token CSRF para peticiones POST (body o header).
     */
    private function csrfValido(Request $request): bool
    {
        $token = (string) ($request->input('_token') ?? $request->header('X-CSRF-TOKEN', ''));

        return Csrf::validate($token);
    }

    /**
     * Normaliza un valor de datetime-local ('2026-05-10T08:00') o de la
     * base de datos ('2026-05-10 08:00:00-04') a 'Y-m-d H:i:s'.
     */
    private function normalizarFechaHora(string $valor): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        $limpio = str_replace('T', ' ', $valor);

        if (!preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})(?::\d{2})?(?:[+-]\d{2}(?::?\d{2})?)?$/', $limpio, $coincidencia)) {
            return null;
        }

        $fecha = \DateTime::createFromFormat('Y-m-d H:i', $coincidencia[1] . ' ' . $coincidencia[2]);

        if ($fecha === false) {
            return null;
        }

        return $fecha->format('Y-m-d H:i:s');
    }

    /**
     * Normaliza un valor de input date ('2026-05-10') a 'Y-m-d'.
     */
    private function normalizarFecha(string $valor): ?string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return null;
        }

        if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', $valor, $coincidencia)) {
            return null;
        }

        $fecha = \DateTime::createFromFormat('!Y-m-d', $coincidencia[1]);

        if ($fecha === false || $fecha->format('Y-m-d') !== $coincidencia[1]) {
            return null;
        }

        return $coincidencia[1];
    }

    /**
     * Filtros de la pestaña Especialidades.
     *
     * @return array{nombre: string, estado: string}
     */
    private function filtrosEspecialidades(Request $request): array
    {
        $nombre = trim((string) $request->get('nombre', ''));
        $estado = trim((string) $request->get('estado', ''));

        if ($nombre !== '') {
            $nombre = mb_substr($nombre, 0, 100);
        }

        if (!in_array($estado, ['1', '0'], true)) {
            $estado = '';
        }

        return ['nombre' => $nombre, 'estado' => $estado];
    }

    /**
     * Filtros de la pestaña Servicio médico.
     *
     * @return array{especialidad: string, estado: string, medico: string}
     */
    private function filtrosServicio(Request $request): array
    {
        $especialidad = trim((string) $request->get('especialidad', ''));
        $estado = strtoupper(trim((string) $request->get('estado_serv', (string) $request->get('estado', ''))));
        $medico = trim((string) $request->get('medico', ''));

        if ($especialidad !== '' && (int) $especialidad <= 0) {
            $especialidad = '';
        }

        if (!in_array($estado, self::ESTADOS_SERVICIO, true)) {
            $estado = '';
        }

        if ($medico !== '') {
            $medico = mb_substr($medico, 0, 100);
        }

        return [
            'especialidad' => $especialidad,
            'estado' => $estado,
            'medico' => $medico,
        ];
    }

    /**
     * Filtros de la pestaña Médicos.
     *
     * @return array{medico: string, acreditaciones: string}
     */
    private function filtrosMedicos(Request $request): array
    {
        $medico = trim((string) $request->get('medico', ''));
        $acreditaciones = (string) $request->get('acredit', '');

        if ($medico !== '') {
            $medico = mb_substr($medico, 0, 100);
        }

        if (!in_array($acreditaciones, ['con', 'sin'], true)) {
            $acreditaciones = '';
        }

        return [
            'medico' => $medico,
            'acreditaciones' => $acreditaciones,
        ];
    }

    private function nombre(): string
    {
        return (string) \Lib\Support\Session::get('portal_nombre', 'Usuario');
    }
}
