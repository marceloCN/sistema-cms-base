<?php
$rolClave = $rolClave ?? '';
$nombre = $nombre ?? 'Usuario';
?>

<div class="portal-welcome">
    <h2>¡Hola, <?= e((string) $nombre) ?>!</h2>
    <p>Bienvenido a tu portal. Aquí tienes un resumen de lo que puedes hacer según tu perfil.</p>
</div>

<div class="portal-stats">
    <div class="portal-stat">
        <div class="portal-stat-icon"><i class="fas fa-stethoscope"></i></div>
        <div><strong>5</strong><span>Especialidades</span></div>
    </div>
    <div class="portal-stat">
        <div class="portal-stat-icon"><i class="fas fa-user-md"></i></div>
        <div><strong>+40</strong><span>Médicos</span></div>
    </div>
    <div class="portal-stat">
        <div class="portal-stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div><strong>24/7</strong><span>Atención</span></div>
    </div>
</div>

<?php if ($rolClave === 'ADMIN') { ?>
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-primary"><i class="fas fa-users text-white"></i></div>
                <h4>Usuarios</h4>
                <p>Gestiona pacientes, médicos y personal administrativo del sistema.</p>
                <a href="#" class="btn btn-outline-primary">Administrar usuarios</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-info"><i class="fas fa-stethoscope text-white"></i></div>
                <h4>Especialidades</h4>
                <p>Administra las especialidades médicas que ofrece la clínica.</p>
                <a href="<?= URL ?>/#especialidades" class="btn btn-outline-info">Ver especialidades</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-success"><i class="fas fa-user-md text-white"></i></div>
                <h4>Médicos</h4>
                <p>Consulta y gestiona el directorio médico de la clínica.</p>
                <a href="<?= URL ?>/#medicos" class="btn btn-outline-success">Ver médicos</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-warning"><i class="fas fa-chart-bar text-white"></i></div>
                <h4>Reportes</h4>
                <p>Genera reportes de citas, médicos, especialidades y estados.</p>
                <a href="#" class="btn btn-outline-warning">Ver reportes</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-secondary"><i class="fas fa-cogs text-white"></i></div>
                <h4>Configuración</h4>
                <p>Personaliza colores, datos de la clínica y página de inicio.</p>
                <a href="<?= route('portal_configuracion') ?>" class="btn btn-outline-secondary">Configurar</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-dark"><i class="fas fa-clipboard-list text-white"></i></div>
                <h4>Bitácora de accesos</h4>
                <p>Consulta los accesos al sistema: inicio de sesión, intentos fallidos y cierres.</p>
                <a href="<?= route('portal_bitacora_acceso') ?>" class="btn btn-outline-dark">Ver bitácora</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-dark"><i class="fas fa-clipboard-check text-white"></i></div>
                <h4>Bitácora de acciones</h4>
                <p>Revisa las acciones realizadas sobre los datos y el permiso que las habilitó.</p>
                <a href="<?= route('portal_bitacora_accion') ?>" class="btn btn-outline-dark">Ver acciones</a>
            </div>
        </div>
    </div>
<?php } elseif ($rolClave === 'RECEP') { ?>
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-primary"><i class="fas fa-user-plus text-white"></i></div>
                <h4>Pacientes</h4>
                <p>Registra nuevos pacientes y busca pacientes existentes.</p>
                <a href="#" class="btn btn-outline-primary">Gestionar pacientes</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-info"><i class="fas fa-calendar-check text-white"></i></div>
                <h4>Citas</h4>
                <p>Programa, reprograma y cancela citas médicas.</p>
                <a href="#" class="btn btn-outline-info">Gestionar citas</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-success"><i class="fas fa-clock text-white"></i></div>
                <h4>Agenda</h4>
                <p>Consulta disponibilidad de médicos y horarios.</p>
                <a href="<?= URL ?>/#medicos" class="btn btn-outline-success">Ver médicos</a>
            </div>
        </div>
    </div>
<?php } elseif ($rolClave === 'MEDICO') { ?>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-primary"><i class="fas fa-calendar-day text-white"></i></div>
                <h4>Mi agenda</h4>
                <p>Revisa las citas programadas para hoy y los próximos días.</p>
                <a href="#" class="btn btn-outline-primary">Ver mi agenda</a>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-info"><i class="fas fa-procedures text-white"></i></div>
                <h4>Pacientes atendidos</h4>
                <p>Consulta el historial de pacientes y citas atendidas.</p>
                <a href="#" class="btn btn-outline-info">Ver historial</a>
            </div>
        </div>
    </div>
<?php } else { ?>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-primary"><i class="fas fa-calendar-plus text-white"></i></div>
                <h4>Reservar cita</h4>
                <p>Agenda una nueva cita con el especialista que necesitas.</p>
                <a href="<?= URL ?>/#especialidades" class="btn btn-outline-primary">Reservar cita</a>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-info"><i class="fas fa-history text-white"></i></div>
                <h4>Mis citas</h4>
                <p>Consulta el estado de tus citas programadas y pasadas.</p>
                <a href="#" class="btn btn-outline-info">Ver mis citas</a>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="portal-card">
                <div class="portal-card-icon bg-secondary"><i class="fas fa-id-card text-white"></i></div>
                <h4>Mi perfil</h4>
                <p>Actualiza tus datos personales, tu usuario, tu correo y tu contraseña.</p>
                <a href="<?= route('portal_perfil') ?>" class="btn btn-outline-secondary">Editar mi perfil</a>
            </div>
        </div>
    </div>
<?php } ?>

<style>
    .portal-welcome {
        background: linear-gradient(135deg, var(--portal-primary) 0%, var(--portal-primary-dark) 100%);
        color: #fff;
        border-radius: 20px;
        padding: 32px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .portal-welcome::after {
        content: '';
        position: absolute;
        top: -40px;
        right: -40px;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }

    .portal-welcome h2 {
        font-weight: 800;
        margin-bottom: 8px;
    }

    .portal-welcome p { margin: 0; opacity: 0.9; }

    .portal-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .portal-stat {
        background: #fff;
        border-radius: 16px;
        padding: 22px;
        box-shadow: 0 4px 18px rgba(11,42,56,0.05);
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .portal-stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: rgba(15,139,132,0.1);
        color: var(--portal-primary);
        display: grid;
        place-items: center;
        font-size: 1.2rem;
    }

    .portal-stat strong {
        display: block;
        font-size: 1.4rem;
        color: #0b2a38;
    }

    .portal-stat span {
        font-size: 0.85rem;
        color: #5d7078;
    }

    .portal-card-icon {
        width: 54px;
        height: 54px;
        border-radius: 14px;
        display: grid;
        place-items: center;
        font-size: 1.4rem;
        margin-bottom: 18px;
    }

    .portal-card h4 {
        font-weight: 700;
        color: #0b2a38;
        margin-bottom: 8px;
    }

    .portal-card p {
        color: #5d7078;
        font-size: 0.95rem;
        flex-grow: 1;
    }

    .portal-card .btn {
        margin-top: auto;
        border-radius: 50px;
        font-weight: 600;
    }
</style>
