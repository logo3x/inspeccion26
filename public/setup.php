<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

/**
 * Setup endpoint for shared-hosting deployments WITHOUT SSH.
 *
 * SECURITY:
 *  - Protected by a static token. Change SETUP_TOKEN below and don't commit it.
 *  - Auto-disabled when APP_ENV=production AND APP_DEBUG=false UNLESS the token is provided.
 *  - DELETE THIS FILE once the deployment is stable.
 *
 * USAGE:
 *  https://your-domain.com/setup.php?token=YOUR_TOKEN
 *  Then click the buttons to run the desired commands.
 */
const SETUP_TOKEN = 'oySSZ--pXhRw3rB0UR89FiUxIv-9El9n';

$providedToken = $_REQUEST['token'] ?? '';
if (! hash_equals(SETUP_TOKEN, $providedToken)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Forbidden. Provide ?token=...';
    exit;
}

$autoloadPath = is_file(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/../vendor/autoload.php'
    : __DIR__.'/vendor/autoload.php';
$bootstrapPath = is_file(__DIR__.'/../bootstrap/app.php')
    ? __DIR__.'/../bootstrap/app.php'
    : __DIR__.'/bootstrap/app.php';
require $autoloadPath;
$app = require $bootstrapPath;

/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);

$actions = [
    'env-check' => ['Verificar .env y conexión DB', null],
    'storage-link' => ['Crear symlink storage', 'storage:link'],
    'key-generate' => ['Generar APP_KEY (si falta)', 'key:generate', ['--force' => true]],
    'config-clear' => ['Limpiar cache config', 'config:clear'],
    'cache-clear' => ['Limpiar cache aplicacion', 'cache:clear'],
    'view-clear' => ['Limpiar cache views', 'view:clear'],
    'route-clear' => ['Limpiar cache rutas', 'route:clear'],
    'optimize-clear' => ['Limpiar TODOS los caches', 'optimize:clear'],
    'migrate' => ['Correr migraciones pendientes', 'migrate', ['--force' => true]],
    'migrate-fresh' => ['DROP ALL + remigrar (DESTRUCTIVO)', 'migrate:fresh', ['--force' => true]],
    'reset-vehicle-data' => ['Vaciar vehículos, propietarios, media, imports (DESTRUCTIVO, mantiene users + roles)', 'inspeccion:reset-vehicle-data', ['--force' => true]],
    'imports-process-pending' => ['Procesar filas Pending del último import (chunk=500)', 'imports:process-pending', ['--chunk' => 500]],
    'reassociate-photos' => ['Re-asociar fotos a vehículos (lee storage/app/private/photos-import/, conserva originales)', 'vehicles:import-photos', ['--keep' => true]],
    'reassociate-photos-dry' => ['(Dry-run) Simular re-asociación de fotos sin tocar nada', 'vehicles:import-photos', ['--keep' => true, '--dry' => true]],
    'seed-admin' => ['Crear usuario admin@local.test', 'db:seed', ['--class' => 'AdminUserSeeder', '--force' => true]],
    'shield-generate' => ['Generar permisos Shield', 'shield:generate', ['--all' => true, '--panel' => 'admin']],
    'seed-roles' => ['Sembrar roles (Administrador, Operador, Visualizador, Descarga)', 'db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]],
    'filament-cache' => ['Cachear componentes Filament', 'filament:cache-components'],
    'config-cache' => ['Cachear configuración (producción)', 'config:cache'],
    'route-cache' => ['Cachear rutas (producción)', 'route:cache'],
    'view-cache' => ['Compilar views (producción)', 'view:cache'],
];

$output = '';
$error = '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action !== '' && isset($actions[$action])) {
    [$label, $command, $args] = array_pad($actions[$action], 3, []);

    try {
        if ($action === 'env-check') {
            $output = 'APP_ENV='.(env('APP_ENV') ?: '<empty>')."\n";
            $output .= 'APP_DEBUG='.(env('APP_DEBUG') ? 'true' : 'false')."\n";
            $output .= 'APP_URL='.(env('APP_URL') ?: '<empty>')."\n";
            $output .= 'DB_HOST='.(env('DB_HOST') ?: '<empty>')."\n";
            $output .= 'DB_DATABASE='.(env('DB_DATABASE') ?: '<empty>')."\n";
            $output .= 'DB_USERNAME='.(env('DB_USERNAME') ?: '<empty>')."\n";
            $output .= 'APP_KEY present: '.(env('APP_KEY') ? 'yes' : 'NO — run key-generate')."\n";

            try {
                $pdo = DB::connection()->getPdo();
                $output .= 'DB connection: OK ('.$pdo->getAttribute(PDO::ATTR_SERVER_VERSION).")\n";
            } catch (Throwable $e) {
                $output .= 'DB connection: FAILED — '.$e->getMessage()."\n";
            }
        } else {
            $kernel->call($command, $args);
            $output = $kernel->output() ?: '(sin salida)';
        }
    } catch (Throwable $e) {
        $error = $e::class.': '.$e->getMessage()."\n".$e->getTraceAsString();
    }
}

$lastAction = $action;
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup · Inspección26</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 980px; margin: 2rem auto; padding: 0 1rem; }
        h1 { margin: 0 0 .25rem; }
        .muted { color: #888; font-size: .9rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: .5rem; margin: 1rem 0; }
        button { padding: .65rem .75rem; border: 1px solid #d4d4d8; background: #fafafa; color: #111; border-radius: 8px; cursor: pointer; text-align: left; font-size: .85rem; }
        button:hover { background: #f4f4f5; }
        button.danger { border-color: #ef4444; color: #b91c1c; }
        button.primary { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
        pre { background: #0f172a; color: #e2e8f0; padding: 1rem; border-radius: 8px; overflow: auto; max-height: 500px; font-size: .8rem; }
        .err { background: #450a0a; color: #fecaca; }
        .ok { background: #052e16; color: #bbf7d0; }
        .ribbon { padding: .75rem; background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; border-radius: 8px; margin-bottom: 1rem; font-size: .9rem; }
        a { color: #2563eb; }
        @media (prefers-color-scheme: dark) {
            body { background: #0a0a0a; color: #fafafa; }
            button { background: #18181b; color: #fafafa; border-color: #3f3f46; }
            button:hover { background: #27272a; }
            .ribbon { background: #422006; color: #fde68a; border-color: #92400e; }
        }
    </style>
</head>
<body>
    <h1>Setup · Inspección26</h1>
    <p class="muted">Herramienta temporal de despliegue. <strong>Elimina este archivo</strong> (<code>public/setup.php</code>) cuando termines.</p>

    <div class="ribbon">
        🔒 Acceso autorizado con token. Borra este archivo después de configurar producción.
        Si necesitas re-ejecutar comandos, vuelve a subir el archivo, ajusta <code>SETUP_TOKEN</code> en código y úsalo.
    </div>

    <h2>Acciones</h2>
    <p class="muted">Cada botón ejecuta un <code>php artisan ...</code> con el token actual.</p>

    <div class="grid">
        <?php foreach ($actions as $key => [$label, $cmd]) { ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($providedToken) ?>">
                <input type="hidden" name="action" value="<?= $key ?>">
                <button type="submit" class="<?= ($key === 'migrate-fresh' || $key === 'reset-vehicle-data') ? 'danger' : ($key === 'migrate' || $key === 'seed-admin' ? 'primary' : '') ?>">
                    <strong><?= htmlspecialchars($label) ?></strong>
                    <?php if ($cmd) { ?><br><span class="muted"><?= htmlspecialchars($cmd) ?></span><?php } ?>
                </button>
            </form>
        <?php } ?>
    </div>

    <?php if ($lastAction !== '') { ?>
        <h2>Resultado: <code><?= htmlspecialchars($actions[$lastAction][0] ?? $lastAction) ?></code></h2>
        <?php if ($error) { ?>
            <pre class="err"><?= htmlspecialchars($error) ?></pre>
        <?php } else { ?>
            <pre class="ok"><?= htmlspecialchars($output) ?></pre>
        <?php } ?>
    <?php } ?>

    <h2>Recomendaciones</h2>
    <ol class="muted">
        <li>Primero: <strong>Verificar .env y conexión DB</strong> — confirma que APP_KEY y DB están bien.</li>
        <li>Si <code>APP_KEY</code> falta: <strong>Generar APP_KEY</strong>.</li>
        <li>Correr <strong>Migraciones</strong>.</li>
        <li>Generar permisos Shield (<strong>Generar permisos Shield</strong>).</li>
        <li>Sembrar roles (<strong>Sembrar roles</strong>) y crear admin (<strong>Crear usuario admin</strong>).</li>
        <li>Crear <strong>symlink storage</strong>.</li>
        <li>(Producción) Cachear config, rutas, views, filament.</li>
        <li><strong>Borra <code>public/setup.php</code></strong> via FTP.</li>
    </ol>
</body>
</html>
