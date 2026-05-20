<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

/**
 * Setup endpoint for shared-hosting deployments WITHOUT SSH.
 *
 * SECURITY:
 *  - Protected by a static token. Change SETUP_TOKEN below and don't commit it.
 *  - DELETE THIS FILE once the deployment is stable.
 *
 * USAGE:
 *  https://your-domain.com/setup.php?token=YOUR_TOKEN
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

// label, command (null for env-check), args, style (danger|primary|warn|normal)
$actions = [
    // Diagnóstico
    'env-check' => ['Verificar .env y conexión DB', null, [], 'primary'],

    // Datos vehículos (flujo de re-import)
    'reset-vehicle-data' => ['🗑 Vaciar vehículos / propietarios / media / imports', 'inspeccion:reset-vehicle-data', ['--force' => true], 'danger'],
    'imports-process-pending' => ['▶ Procesar filas Pending del último import', 'imports:process-pending', ['--chunk' => 500], 'primary'],
    'reassociate-spatie-dry' => ['🔍 (Dry-run) Re-asociar fotos Spatie storage/app/public/', 'vehicles:import-photos', ['--disk' => 'public', '--recursive' => true, '--keep' => true, '--dry' => true], 'normal'],
    'reassociate-spatie' => ['🖼 Re-asociar fotos Spatie (recursivo, conserva originales)', 'vehicles:import-photos', ['--disk' => 'public', '--recursive' => true, '--keep' => true], 'primary'],

    // Caches — limpieza
    'optimize-clear' => ['Limpiar TODOS los caches', 'optimize:clear', [], 'normal'],
    'config-clear' => ['Limpiar cache config', 'config:clear', [], 'normal'],
    'cache-clear' => ['Limpiar cache aplicación', 'cache:clear', [], 'normal'],
    'view-clear' => ['Limpiar cache views', 'view:clear', [], 'normal'],
    'route-clear' => ['Limpiar cache rutas', 'route:clear', [], 'normal'],

    // Caches — producción
    'config-cache' => ['Cachear configuración (producción)', 'config:cache', [], 'normal'],
    'route-cache' => ['Cachear rutas (producción)', 'route:cache', [], 'normal'],
    'view-cache' => ['Compilar views (producción)', 'view:cache', [], 'normal'],
    'filament-cache' => ['Cachear componentes Filament', 'filament:cache-components', [], 'normal'],

    // Configuración inicial
    'key-generate' => ['Generar APP_KEY (si falta)', 'key:generate', ['--force' => true], 'normal'],
    'storage-link' => ['Crear symlink storage', 'storage:link', [], 'normal'],
    'migrate' => ['Correr migraciones pendientes', 'migrate', ['--force' => true], 'primary'],
    'migrate-fresh' => ['🗑 DROP ALL + remigrar (DESTRUCTIVO)', 'migrate:fresh', ['--force' => true], 'danger'],
    'seed-admin' => ['Crear usuario admin@local.test', 'db:seed', ['--class' => 'AdminUserSeeder', '--force' => true], 'normal'],
    'shield-generate' => ['Generar permisos Shield', 'shield:generate', ['--all' => true, '--panel' => 'admin'], 'normal'],
    'seed-roles' => ['Sembrar roles (Administrador, Operador, Visualizador, Descarga)', 'db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true], 'normal'],

    // Fotos avanzadas (otros paths)
    'reassociate-photos-dry' => ['🔍 (Dry-run) Fotos en photos-import/ (root)', 'vehicles:import-photos', ['--keep' => true, '--dry' => true], 'normal'],
    'reassociate-photos' => ['🖼 Fotos en photos-import/ (root, conserva originales)', 'vehicles:import-photos', ['--keep' => true], 'normal'],
    'reassociate-orphans-dry' => ['🔍 (Dry-run) Fotos en photos-import/orphans/', 'vehicles:import-photos', ['--path' => 'photos-import/orphans', '--keep' => true, '--dry' => true], 'normal'],
    'reassociate-orphans' => ['🖼 Fotos en photos-import/orphans/ (conserva originales)', 'vehicles:import-photos', ['--path' => 'photos-import/orphans', '--keep' => true], 'normal'],
];

$sections = [
    'Diagnóstico' => [
        'open' => true,
        'keys' => ['env-check'],
    ],
    'Re-importar datos de vehículos' => [
        'open' => true,
        'desc' => '1) Vaciar → 2) Subir Excel en Filament → 3) Procesar Pending → 4) Dry-run fotos → 5) Re-asociar fotos.',
        'keys' => ['reset-vehicle-data', 'imports-process-pending', 'reassociate-spatie-dry', 'reassociate-spatie'],
    ],
    'Caches — limpiar (en cambios)' => [
        'open' => false,
        'keys' => ['optimize-clear', 'config-clear', 'cache-clear', 'view-clear', 'route-clear'],
    ],
    'Caches — cachear para producción' => [
        'open' => false,
        'keys' => ['config-cache', 'route-cache', 'view-cache', 'filament-cache'],
    ],
    'Configuración inicial (primera vez)' => [
        'open' => false,
        'keys' => ['key-generate', 'storage-link', 'migrate', 'migrate-fresh', 'seed-admin', 'shield-generate', 'seed-roles'],
    ],
    'Fotos — otros orígenes (avanzado)' => [
        'open' => false,
        'desc' => 'Solo si necesitas leer de carpetas alternativas distintas a storage/app/public/.',
        'keys' => ['reassociate-photos-dry', 'reassociate-photos', 'reassociate-orphans-dry', 'reassociate-orphans'],
    ],
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

function setup_render_button(string $key, array $action, string $providedToken): string
{
    [$label, $cmd] = $action;
    $style = $action[3] ?? 'normal';
    $tokenSafe = htmlspecialchars($providedToken, ENT_QUOTES);
    $labelSafe = htmlspecialchars($label);
    $cmdSafe = $cmd ? htmlspecialchars($cmd) : '';

    return <<<HTML
<form method="post">
    <input type="hidden" name="token" value="{$tokenSafe}">
    <input type="hidden" name="action" value="{$key}">
    <button type="submit" class="{$style}">
        <strong>{$labelSafe}</strong>
        <span class="muted">{$cmdSafe}</span>
    </button>
</form>
HTML;
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Setup · Inspección26</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 1100px; margin: 1.5rem auto; padding: 0 1rem; line-height: 1.4; }
        h1 { margin: 0 0 .25rem; font-size: 1.4rem; }
        h2 { margin: 1rem 0 .25rem; font-size: 1rem; }
        .muted { color: #888; font-size: .8rem; display: block; margin-top: .15rem; }
        details { border: 1px solid #e4e4e7; border-radius: 10px; margin: .5rem 0; padding: 0 .75rem; background: #fafafa; }
        details[open] { background: #fff; }
        summary { padding: .65rem .25rem; cursor: pointer; font-weight: 600; font-size: .95rem; user-select: none; }
        summary::marker { color: #888; }
        .desc { color: #666; font-size: .8rem; margin: -.25rem 0 .5rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: .4rem; padding-bottom: .75rem; }
        form { margin: 0; }
        button { width: 100%; padding: .6rem .75rem; border: 1px solid #d4d4d8; background: #fff; color: #111; border-radius: 8px; cursor: pointer; text-align: left; font-size: .85rem; line-height: 1.25; }
        button:hover { background: #f4f4f5; }
        button.danger { border-color: #ef4444; color: #b91c1c; background: #fef2f2; }
        button.danger:hover { background: #fee2e2; }
        button.primary { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
        button.primary:hover { background: #dbeafe; }
        pre { background: #0f172a; color: #e2e8f0; padding: .9rem; border-radius: 8px; overflow: auto; max-height: 60vh; font-size: .8rem; }
        .err { background: #450a0a; color: #fecaca; }
        .ok { background: #052e16; color: #bbf7d0; }
        .ribbon { padding: .5rem .75rem; background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; border-radius: 8px; margin-bottom: 1rem; font-size: .85rem; }
        @media (prefers-color-scheme: dark) {
            body { background: #0a0a0a; color: #fafafa; }
            details { background: #18181b; border-color: #3f3f46; }
            details[open] { background: #111114; }
            .desc, summary::marker { color: #a1a1aa; }
            button { background: #18181b; color: #fafafa; border-color: #3f3f46; }
            button:hover { background: #27272a; }
            button.danger { background: #3f1818; color: #fecaca; }
            button.primary { background: #142353; color: #93c5fd; }
            .ribbon { background: #422006; color: #fde68a; border-color: #92400e; }
        }
    </style>
</head>
<body>
    <h1>Setup · Inspección26</h1>
    <div class="ribbon">🔒 Acceso autorizado con token. <strong>Borra <code>public/setup.php</code></strong> via FTP cuando termines.</div>

    <?php foreach ($sections as $title => $section) { ?>
        <details<?= !empty($section['open']) ? ' open' : '' ?>>
            <summary><?= htmlspecialchars($title) ?></summary>
            <?php if (!empty($section['desc'])) { ?>
                <p class="desc"><?= htmlspecialchars($section['desc']) ?></p>
            <?php } ?>
            <div class="grid">
                <?php foreach ($section['keys'] as $key) {
                    if (! isset($actions[$key])) { continue; }
                    echo setup_render_button($key, $actions[$key], $providedToken);
                } ?>
            </div>
        </details>
    <?php } ?>

    <?php if ($lastAction !== '') { ?>
        <h2>Resultado: <code><?= htmlspecialchars($actions[$lastAction][0] ?? $lastAction) ?></code></h2>
        <?php if ($error) { ?>
            <pre class="err"><?= htmlspecialchars($error) ?></pre>
        <?php } else { ?>
            <pre class="ok"><?= htmlspecialchars($output) ?></pre>
        <?php } ?>
    <?php } ?>
</body>
</html>
