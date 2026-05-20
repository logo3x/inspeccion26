<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('vehicles:import-photos {--path= : Ruta relativa al disco local (default: photos-import)} {--disk=local : Disco origen (local|public)} {--recursive : Recorrer subdirectorios} {--dry : Solo simula, no asocia archivos} {--keep : No borrar las fotos importadas del folder source} {--limit=0 : Procesar solo N archivos por corrida (0 = todos)}')]
#[Description('Asocia fotos a vehículos por el nombre del archivo (placa). Lee de storage/app/{disk}/{path}.')]
class ImportPhotosFromFolder extends Command
{
    /**
     * @return int
     */
    public function handle()
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1G');

        $dry = (bool) $this->option('dry');
        $keep = (bool) $this->option('keep');
        $limit = (int) $this->option('limit');
        $diskName = $this->option('disk') ?: 'local';
        $recursive = (bool) $this->option('recursive');

        $path = $this->option('path');
        if ($path === null || $path === '') {
            $path = $diskName === 'public' ? '.' : 'photos-import';
        }

        $disk = Storage::disk($diskName);
        if (! $disk->exists($path)) {
            $this->error("La carpeta '{$path}' no existe en el disco '{$diskName}'.");

            return self::FAILURE;
        }

        $absoluteDir = $disk->path($path);
        $orphansDir = $absoluteDir.'/orphans';
        if (! $dry && ! is_dir($orphansDir)) {
            @mkdir($orphansDir, 0755, true);
        }

        $files = $recursive
            ? $this->listImagesRecursive($absoluteDir, $orphansDir)
            : $this->listImages($absoluteDir);
        $totalAvailable = count($files);

        if ($limit > 0) {
            $files = array_slice($files, 0, $limit);
        }

        $this->info(sprintf('Disponibles: %d. Procesando en esta corrida: %d. Carpeta: %s', $totalAvailable, count($files), $absoluteDir));
        if ($dry) {
            $this->warn('Modo --dry: NO se asocian archivos.');
        }

        $associated = 0;
        $orphans = [];
        $errors = [];
        $placaToVehicle = []; // cache placa => vehicle id (o null si no existe)

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $file) {
            $filename = basename($file);
            $candidates = $this->extractPlateCandidates($filename);

            if (empty($candidates)) {
                $orphans[] = "{$filename} (sin placa detectable)";
                if (! $dry) {
                    @rename($file, $orphansDir.'/'.$filename);
                }
                $bar->advance();

                continue;
            }

            $vehicle = null;
            foreach ($candidates as $candidate) {
                if (! array_key_exists($candidate, $placaToVehicle)) {
                    $placaToVehicle[$candidate] = Vehicle::query()->where('placa', $candidate)->first();
                }
                if ($placaToVehicle[$candidate] !== null) {
                    $vehicle = $placaToVehicle[$candidate];
                    break;
                }
            }

            if ($vehicle === null) {
                $tried = implode(',', $candidates);
                $orphans[] = "{$filename} (placas probadas: {$tried})";
                if (! $dry) {
                    @rename($file, $orphansDir.'/'.$filename);
                }
                $bar->advance();

                continue;
            }

            if ($dry) {
                $associated++;
                $bar->advance();

                continue;
            }

            try {
                $vehicle->addMedia($file)
                    ->preservingOriginal($keep)
                    ->toMediaCollection(Vehicle::PHOTOS_COLLECTION);
                $associated++;
            } catch (\Throwable $e) {
                $errors[] = "{$filename} → {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Procesados: '.count($files));
        $this->info("Asociados : {$associated}");
        $this->warn('Huérfanos : '.count($orphans));
        $this->error('Errores   : '.count($errors));

        if (! empty($orphans)) {
            $this->newLine();
            $this->warn('Huérfanos (primeros 20):');
            foreach (array_slice($orphans, 0, 20) as $o) {
                $this->line("  - {$o}");
            }
            if (count($orphans) > 20) {
                $this->line('  ... y '.(count($orphans) - 20).' más');
            }
        }

        if (! empty($errors)) {
            $this->newLine();
            $this->error('Errores:');
            foreach (array_slice($errors, 0, 20) as $e) {
                $this->line("  - {$e}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Walk subdirectories. Skips the orphans/ subdir to avoid re-processing.
     *
     * @return array<int, string>
     */
    private function listImagesRecursive(string $dir, string $skipDir): array
    {
        $files = [];
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        $skipPrefix = rtrim($skipDir, '/').'/';
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }
            $path = $fileInfo->getPathname();
            if (str_starts_with($path, $skipPrefix)) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions, true)) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function listImages(string $dir): array
    {
        $files = [];
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        // Solo archivos en el directorio raíz (no subdirs como orphans/)
        $entries = scandir($dir) ?: [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            if (! is_file($path)) {
                continue;
            }
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions, true)) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Genera placas candidatas a partir del nombre del archivo.
     * Devuelve varias interpretaciones ordenadas por especificidad,
     * para que el caller pruebe cada una contra la BD hasta encontrar match.
     *
     * Ejemplos:
     *   ADD81_2.jpg   → [ADD81]
     *   ADD811.jpg    → [ADD811, ADD81]
     *   AIJ 60C_.jpg  → [AIJ60C]
     *   ABH10B__2.jpg → [ABH10B]
     *
     * @return array<int, string>
     */
    private function extractPlateCandidates(string $filename): array
    {
        $stem = strtoupper(pathinfo($filename, PATHINFO_FILENAME));
        $parts = array_values(array_filter(
            preg_split('/[\s\-_().]+/', $stem) ?: [],
            fn ($p) => $p !== ''
        ));

        if (empty($parts)) {
            return [];
        }

        $candidates = [];

        // Caso 1: primera parte ES la placa completa (anchored start+end)
        if (preg_match('/^([A-Z]{3}\d{2,3}[A-Z]?)$/', $parts[0], $m)) {
            $candidates[] = $m[1];
        }

        // Caso 2: placa partida por separador interno (ej "AIJ 60C")
        if (count($parts) >= 2
            && preg_match('/^[A-Z]{3}$/', $parts[0])
            && preg_match('/^(\d{2,3})([A-Z])?$/', $parts[1], $m)) {
            $candidates[] = $parts[0].$m[1].($m[2] ?? '');
        }

        // Caso 3: prefijo de primera parte (placa + counter pegado sin separador).
        // Probar en orden: más especifico (con letra final) → 3+3 → 3+2.
        if (preg_match('/^([A-Z]{3}\d{2,3}[A-Z])/', $parts[0], $m)) {
            $candidates[] = $m[1];
        }
        if (preg_match('/^([A-Z]{3}\d{3})/', $parts[0], $m)) {
            $candidates[] = $m[1];
        }
        if (preg_match('/^([A-Z]{3}\d{2})/', $parts[0], $m)) {
            $candidates[] = $m[1];
        }

        return array_values(array_unique($candidates));
    }
}
