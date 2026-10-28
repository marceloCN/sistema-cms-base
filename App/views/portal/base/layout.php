<?php

use App\Models\ConfiguracionSistema;
use Lib\Support\Session;

$configPortal = $configPortal ?? ConfiguracionSistema::publica(); // te manda la tabla el nombre de la clinica como title
$portalNombre = (string) ($configPortal['clinica_nombre'] ?? 'Clínica Vida Salud');
$portalPrimario = preg_match('/^#[a-fA-F0-9]{6}$/', (string) ($configPortal['tema_color_primario'] ?? ''))
    ? (string) $configPortal['tema_color_primario']
    : '#0f8b84';
$portalSecundario = preg_match('/^#[a-fA-F0-9]{6}$/', (string) ($configPortal['tema_color_secundario'] ?? ''))
    ? (string) $configPortal['tema_color_secundario']
    : '#0b2a38';

// El módulo Pacientes se muestra a ADMIN o a cualquier rol con el permiso 120.
$portalPermisos = (array) Session::get('portal_permisos', []);
$verPacientes = ($rolClave ?? '') === 'ADMIN' || in_array('120', $portalPermisos, true);
?>
<!DOCTYPE html>
<html lang="es"<?= ($configPortal['tema_modo'] ?? 'claro') === 'oscuro' ? ' data-tema="oscuro"' : '' ?>>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mi portal · <?= e($portalNombre) ?></title>
    <link rel="icon" href="<?= asset('favicon.ico') ?>">
    <link rel="stylesheet" href="<?= asset('code/dist/css/adminlte.purged.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('code/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('medicina/css/tema-oscuro.css') ?>">
    <!-- jQuery/Bootstrap van en el HEAD (sin defer): los scripts inline de cada
         módulo se emiten dentro del contenido y deben ejecutarse con $ disponible. -->
    <script src="<?= asset('code/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= asset('code/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <style>
        :root {
            --portal-primary: <?= e($portalPrimario) ?>;
            --portal-primary-dark: color-mix(in srgb, var(--portal-primary) 78%, black);
            --portal-sidebar: <?= e($portalSecundario) ?>;
            --portal-sidebar-hover: color-mix(in srgb, var(--portal-sidebar) 82%, white 6%);
            --portal-navbar-height: 64px;
            --portal-sidebar-width: 260px;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: #f4f6f9;
            overflow: hidden;
        }

        .portal-app {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        /* Navbar */
        .portal-navbar {
            background: #fff;
            border-bottom: 1px solid #e0e6e3;
            height: var(--portal-navbar-height);
            padding: 0 16px 0 20px;
            position: sticky;
            top: 0;
            z-index: 1050;
            flex-shrink: 0;
            box-shadow: 0 2px 10px rgba(11, 42, 56, 0.06);
        }

        .portal-navbar .container-fluid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }

        .portal-navbar-left,
        .portal-navbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .portal-sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #e0e6e3;
            border-radius: 10px;
            background: #fff;
            color: #0b2a38;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }

        .portal-sidebar-toggle:hover {
            background: #f4f6f9;
            border-color: var(--portal-primary);
            color: var(--portal-primary);
        }

        .portal-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--portal-primary);
            font-weight: 800;
            font-size: 1.2rem;
            text-decoration: none;
            white-space: nowrap;
        }

        .portal-brand i {
            font-size: 1.4rem;
        }

        .portal-user {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .portal-user-name {
            font-weight: 600;
            color: #0b2a38;
            white-space: nowrap;
        }

        /* Wrapper */
        .portal-wrapper {
            display: flex;
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        /* Sidebar - Drawer en todos los tamaños */
        .portal-sidebar {
            position: fixed;
            top: var(--portal-navbar-height);
            left: 0;
            bottom: 0;
            width: min(var(--portal-sidebar-width), 85vw);
            background: var(--portal-sidebar);
            color: rgba(255, 255, 255, 0.75);
            padding: 20px 0;
            overflow-y: auto;
            overflow-x: hidden;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1040;
            box-shadow: 4px 0 20px rgba(11, 42, 56, 0.2);
        }

        .portal-sidebar.open {
            transform: translateX(0);
        }

        .portal-sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .portal-sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 13px 22px;
            color: rgba(255, 255, 255, 0.8);
            transition: background 0.2s, color 0.2s;
            text-decoration: none;
            white-space: nowrap;
        }

        .portal-sidebar-nav li a:hover,
        .portal-sidebar-nav li a.active {
            background: var(--portal-sidebar-hover);
            color: #fff;
        }

        .portal-sidebar-nav li a i {
            width: 24px;
            text-align: center;
            flex-shrink: 0;
            font-size: 1.05rem;
        }

        /* Overlay */
        .portal-overlay {
            display: none;
            position: fixed;
            inset: var(--portal-navbar-height) 0 0 0;
            background: rgba(11, 42, 56, 0.45);
            z-index: 1035;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .portal-overlay.open {
            display: block;
            opacity: 1;
        }

        /* Main */
        .portal-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            overflow-x: hidden;
            scroll-behavior: smooth;
            position: relative;
        }

        .portal-content {
            flex: 1 0 auto;
            padding: 28px;
            padding-bottom: calc(28px + 70px);
        }

        /* Footer siempre al final de la vista */
        .portal-footer {
            position: sticky;
            bottom: 0;
            background: #f4f6f9;
            padding: 16px 28px;
            border-top: 1px solid #e0e6e3;
            color: #5d7078;
            font-size: 0.85rem;
            text-align: center;
            flex-shrink: 0;
            z-index: 1;
        }

        .portal-card {
            background: #fff;
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 4px 18px rgba(11, 42, 56, 0.05);
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }

        .portal-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 28px rgba(11, 42, 56, 0.1);
        }

        .tab-content>.tab-pane {
            display: none;
        }

        .tab-content>.tab-pane.active {
            display: block;
        }

        @media (max-width: 768px) {
            .portal-content {
                padding: 20px;
                padding-bottom: calc(20px + 70px);
            }

            .portal-footer {
                padding: 14px 20px;
            }

            .portal-user-name {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="portal-app">
        <nav class="portal-navbar">
            <div class="container-fluid">
                <div class="portal-navbar-left">
                    <button type="button" class="portal-sidebar-toggle" id="portalSidebarToggle" aria-label="Alternar menú" aria-expanded="false">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="<?= route('inicio') ?>" class="portal-brand">
                        <i class="fas fa-heartbeat"></i> <?= e($portalNombre) ?>
                    </a>
                </div>
                <div class="portal-navbar-right">
                    <div class="portal-user">
                        <span class="portal-user-name"><i class="fas fa-user-circle"></i> <?= e((string) ($nombre ?? 'Usuario')) ?></span>
                        <a href="<?= route('portal_perfil') ?>" class="btn btn-sm btn-outline-secondary d-md-none"
                            title="Mi perfil" aria-label="Mi perfil">
                            <i class="fas fa-id-card"></i>
                        </a>
                        <form action="<?= route('portal_logout') ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fas fa-sign-out-alt"></i> <span class="d-none d-sm-inline">Salir</span></button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <div class="portal-wrapper">
            <aside class="portal-sidebar" id="portalSidebar" aria-label="Menú principal">
                <ul class="portal-sidebar-nav">
                    <li><a href="<?= route('portal_dashboard') ?>" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
                    <li>
                        <a href="<?= route('dashboard_especialidades') ?>"
                            class="<?= ($active ?? '') === 'especialidades' ? 'active' : '' ?>">
                            <i class="fas fa-book-medical"></i> <span>Especialidades</span>
                        </a>
                    </li>

                    <!-- el admin, todo lo d econfiguracion, y las bitacoras, y falta los reportes -->
                    <?php if (($rolClave ?? '') === 'ADMIN') { ?>
                        <li>
                            <a href="<?= route('dashboard_usuarios') ?>"
                                class="<?= ($active ?? '') === 'usuarios' ? 'active' : '' ?>">
                                <i class="fas fa-user-tie"></i> <span>Empleados</span>
                            </a>
                        </li>
                        <li><a href="<?= route('portal_configuracion') ?>" class="<?= ($active ?? '') === 'configuracion' ? 'active' : '' ?>"><i class="fas fa-cogs"></i> <span>Configuración</span></a></li>
                        <li>
                            <a href="<?= route('dashboard_permisos') ?>"
                                class="<?= ($active ?? '') === 'permisos' ? 'active' : '' ?>">
                                <i class="fas fa-user-shield"></i> <span>Permisos</span>
                            </a>
                        </li>
                        
                        <li>
                            <a href="<?= route('portal_bitacora_acceso') ?>"
                                class="<?= ($active ?? '') === 'bitacora' ? 'active' : '' ?>">
                                <i class="fas fa-clipboard-list"></i> <span>Bitácora de accesos</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= route('portal_bitacora_accion') ?>"
                                class="<?= ($active ?? '') === 'bitacora_accion' ? 'active' : '' ?>">
                                <i class="fas fa-clipboard-check"></i> <span>Bitácora de acciones</span>
                            </a>
                        </li>
                        <li><a href="#"><i class="fas fa-chart-bar"></i> <span>Reportes</span></a></li>
                    <?php } ?>
                    <?php if ($verPacientes) { ?>
                        <li>
                            <a href="<?= route('dashboard_pacientes') ?>"
                                class="<?= ($active ?? '') === 'pacientes' ? 'active' : '' ?>">
                                <i class="fas fa-users"></i> <span>Pacientes</span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (($rolClave ?? '') === 'PAC') { ?>
                        <li><a href="#"><i class="fas fa-history"></i> <span>Mis citas</span></a></li>
                    <?php } ?>
                    <li>
                        <a href="<?= route('portal_perfil') ?>" class="<?= ($active ?? '') === 'perfil' ? 'active' : '' ?>">
                            <i class="fas fa-id-card"></i> <span>Mi perfil</span>
                        </a>
                    </li>

                </ul>
            </aside>

            <div class="portal-overlay" id="portalOverlay" aria-hidden="true"></div>

            <main class="portal-main">
                <div class="portal-content">
                    <?= $content ?? '' ?>
                </div>

                <div class="portal-footer">
                    &copy; <?= date('Y') ?> <?= e($portalNombre) ?>. Todos los derechos reservados.
                </div>
            </main>
        </div>
    </div>

    <script src="<?= asset('code/dist/js/adminlte.min.js') ?>" defer></script>
    <script src="<?= asset('code/js/notification.js') ?>" defer></script>
    <script>
        (function() {
            const sidebar = document.getElementById('portalSidebar');
            const toggle = document.getElementById('portalSidebarToggle');
            const overlay = document.getElementById('portalOverlay');
            if (!sidebar || !toggle) return;

            function openSidebar() {
                sidebar.classList.add('open');
                overlay && overlay.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('open');
                overlay && overlay.classList.remove('open');
                toggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }

            function toggleSidebar() {
                sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
            }

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSidebar();
            });

            overlay && overlay.addEventListener('click', closeSidebar);

            // Cerrar drawer al hacer clic en un enlace
            sidebar.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    closeSidebar();
                });
            });

            // Cerrar con la tecla Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                    closeSidebar();
                }
            });
        })();
    </script>
</body>

</html>