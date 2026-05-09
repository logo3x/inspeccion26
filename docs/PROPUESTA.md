# Propuesta arquitectónica — Sistema de fichas técnicas vehiculares

> Documento de alineación previo a la implementación. Cualquier decisión marcada como **[DECIDIR]** requiere tu input antes de la Fase 1.

---

## 1. Stack final y desviaciones del spec original

| Componente | Spec original | Real instalado | Razón |
|---|---|---|---|
| Laravel | 12 | **13** | L13 ya estable, mismas APIs relevantes que L12 |
| Filament | 3 | **5.6** | Filament 4 y 5 ya GA. v3 está en mantenimiento. Sintaxis muy distinta a v5 — no merece la pena retroceder |
| Livewire | 3 | **4.3** | Arrastrado por Filament 5 |
| PHP | 8.2+ | **8.5** | Disponible en WAMP |

**Implicación**: el código que escribiremos usa la API de Filament 5 (Schemas, nuevos Forms, etc.), no la de v3.

### Paquetes ya instalados (commits previos)
- `filament/filament` v5.6.2
- `bezhansalleh/filament-shield` 4.2.0 (auth/policies)
- `stechstudio/filament-impersonate` 5.3.0
- `filament/spatie-laravel-media-library-plugin` 5.6.2
- `spatie/laravel-permission` 7.4.1
- `spatie/laravel-medialibrary` 11.22.1

### Paquetes a añadir por fase
| Fase | Paquete | Para |
|---|---|---|
| 2 | `phpoffice/phpword` | Generar DOCX desde plantilla |
| 3 | `maatwebsite/excel` | Importación Excel |
| 4 | (opcional) `gotenberg/gotenberg-php` | Conversión DOCX→PDF si decidimos PDF |
| 7 | `spatie/laravel-activitylog` | Auditoría |
| 7 | `spatie/laravel-backup` | Backups (opcional) |

---

## 2. Modelo de datos

```
vehicles                     owners (opcional)
─────────────────            ───────
id                           id
placa (UNIQUE, idx)          document_number
marca                        full_name
modelo                       phone
year                         email
color                        address
tipo (enum)                  ...
vin
motor
estado (enum)
observaciones
owner_id (FK, nullable)
created_by (FK users)
completion_percentage (int)  ← derivado, persistido
created_at, updated_at
deleted_at (soft)

import_batches               import_rows
─────────────                ───────────
id                           id
user_id                      batch_id (FK)
filename                     row_number
total_rows                   raw_data (JSON)
created_count                action (created|updated|failed|skipped)
updated_count                vehicle_id (FK, nullable)
failed_count                 error_message (text, nullable)
status (queued|processing|   processed_at
  completed|failed)
started_at                   ↑ índices: (batch_id, row_number),
finished_at                     (action), (batch_id, action)

signatures                   media (Spatie)
──────────                   ────
id                           model_type, model_id (morph)
user_id                      collection_name = 'photos'|'signature'
storage_path                 file_name, mime, ...
is_default
```

### Colecciones de medios por modelo
- `Vehicle` → colección `photos` (max 5, conversiones `thumb` 200x200, `web` 1024x1024)
- `Signature` → colección `signature` (single file)

### Estados (enum)
- `VehicleStatus`: `draft | pending_review | approved | archived`
- `ImportBatchStatus`: `queued | processing | completed | failed | partial`
- `ImportRowAction`: `created | updated | failed | skipped`

---

## 3. Estructura de carpetas

```
app/
├── Filament/Admin/
│   ├── Resources/
│   │   ├── Vehicles/{VehicleResource, Pages, RelationManagers}
│   │   ├── Users/...
│   │   ├── Roles/...                 ← Shield
│   │   └── ImportBatches/...
│   ├── Pages/
│   │   ├── ImportVehicles.php        ← dropzone + preview + run
│   │   └── BulkGenerateSheets.php
│   └── Widgets/
│       ├── VehiclesStats.php
│       ├── RegistrationsByMonth.php
│       ├── ImportsTimeline.php
│       └── RecentImportErrors.php
├── Domain/
│   ├── Vehicles/
│   │   ├── Actions/{CreateOrUpdateVehicle, CalculateCompletion}.php
│   │   ├── Data/VehicleData.php
│   │   ├── Enums/VehicleStatus.php
│   │   └── Observers/VehicleObserver.php
│   ├── Imports/
│   │   ├── Actions/{ParseExcelPreview, ProcessImportRow}.php
│   │   ├── Imports/VehiclesImport.php
│   │   ├── Jobs/{StartImportBatch, ProcessRow, FinalizeImportBatch}.php
│   │   └── Data/{ImportRowData, ImportPreviewData}.php
│   └── InspectionSheets/
│       ├── Actions/{GenerateSheet, GenerateBulkZip}.php
│       ├── Generators/DocxGenerator.php
│       ├── Jobs/{GenerateSheetJob, BuildZipJob}.php
│       └── Templates/ficha_tecnica.docx   ← gitignored, en storage
├── Models/{User, Vehicle, Owner, Signature, ImportBatch, ImportRow}.php
├── Policies/                         ← generadas por Shield
└── Providers/
```

**Por qué esta estructura**: la lógica de negocio vive en `Domain/`, completamente desacoplada de Filament. Filament solo orquesta UI → Action. Si mañana añades una API REST, reusas las mismas Actions.

---

## 4. Plan de fases

| Fase | Entregable | Días est. | Bloquea a |
|---|---|---|---|
| **0** | Filament + plugins instalados, admin user | ✓ hecho | — |
| **1** | CRUD Vehicle + fotos + roles base | 1.5 | 2,3,5 |
| **2** | Generación DOCX individual con plantilla | 1 | 4 |
| **3** | Importación Excel (preview + jobs por fila + tabla resultado) | 2.5 | 4,6 |
| **4** | Generación masiva (ZIP de DOCX) | 1.5 | — |
| **5** | Dashboard con widgets y charts | 1 | — |
| **6** | % completado + alertas visuales + activity log | 1 | — |
| **7** | Tests Pest + auditoría + hardening | 1 | — |

**Total estimado**: ~9.5 días para sistema completo.

### Hito intermedio sugerido
Después de **Fase 3** ya tienes lo crítico: CRUD + Excel + DOCX. Las fases 4-7 son refinamiento y pueden iterarse.

---

## 5. Decisiones técnicas con justificación

### 5.1 Excel: Maatwebsite/Excel
- Integración nativa con queues, batch reading, validación.
- OpenSpout es más rápido pero requiere más boilerplate.
- **Decisión**: Maatwebsite con `WithChunkReading` + `ShouldQueue`.

### 5.2 "Uno por uno" (requisito explícito tuyo)
- Un Job por fila → `ProcessRowJob`. Cola dedicada `imports`.
- Trazabilidad completa por fila, retry independiente, errores aislados.
- Coordinación con `Bus::batch()` para callback final cuando todas terminan.
- Trade-off: ~5-10 ms overhead de cola por fila vs lectura en chunk. Para volúmenes <1000 filas es irrelevante.

### 5.3 Word: PHPWord con `TemplateProcessor`
- Tú diseñas plantilla.docx con placeholders `${placa}`, `${marca}`, `${foto_1}`, `${firma}`.
- PHPWord rellena texto e imágenes en sitio.
- No requiere LibreOffice. 100% PHP.

### 5.4 PDF — **[DECIDIR]**
El spec dice "ZIP de Word **O** PDF (si es viable)". PDF requiere converter externo (LibreOffice/Gotenberg). Recomiendo:
- **MVP: solo DOCX**. Cero dependencias externas.
- **Si necesitas PDF**: añadir contenedor Gotenberg en docker-compose y usar `gotenberg-php`.

### 5.5 Procesamiento asíncrono
- Driver queue: **`database`** en local/dev, **Redis** en prod.
- Workers separados:
  - `imports` — alta prioridad, baja concurrencia (procesa orden estable)
  - `generation` — paralela
  - `default` — notificaciones, mails

### 5.6 Storage
- Fotos: Spatie media → `storage/app/public/{vehicle_uuid}/photos/`
- Plantilla DOCX: `storage/app/templates/ficha_tecnica.docx` (NO en public, NO en git)
- ZIPs generados: `storage/app/public/exports/{batch_id}/{batch_id}.zip` con TTL 24h
- `php artisan storage:link` requerido

### 5.7 % Completado
- Atributo persistido en columna `completion_percentage` (indexable, filtrable).
- Calculado por `CalculateCompletionAction` invocado desde Observer en `saving` y desde listener de eventos `media-library`.
- Pesos:
  - Datos básicos (placa, marca, modelo, año): 40%
  - Datos técnicos (vin, motor, color, tipo): 30%
  - Mínimo 1 foto: 20%
  - Firma asociada: 10%

### 5.8 Permisos por rol

| Permiso (Spatie) | Admin | Operador | Visualizador | Descarga |
|---|:-:|:-:|:-:|:-:|
| `view_vehicle` | ✓ | ✓ | ✓ | ✓ |
| `create_vehicle` | ✓ | ✓ | | |
| `update_vehicle` | ✓ | ✓ | | |
| `delete_vehicle` | ✓ | | | |
| `import_vehicles` | ✓ | ✓ | | |
| `download_sheet` | ✓ | ✓ | | ✓ |
| `bulk_generate` | ✓ | ✓ | | ✓ |
| `view_dashboard` | ✓ | ✓ | ✓ | ✓ |
| `manage_users` | ✓ | | | |
| `manage_roles` | ✓ | | | |
| `impersonate_users` | ✓ | | | |
| `view_audit_log` | ✓ | | | |

Roles creados por seeder; permisos generados por `shield:generate` cuando existan los Resources.

---

## 6. Seguridad y robustez

- **Validación de placa**: regex configurable (Colombia ABC-123 / ABC-12D / etc), unique en DB.
- **Mime/size de fotos**: max 5MB, solo `image/jpeg|png|webp`.
- **Plantilla DOCX**: fuera de `public/`, validar existencia al boot.
- **Path traversal**: nunca usar nombre de archivo del usuario; renombrar a `{placa}_{n}.{ext}` con sanitización.
- **CSRF + Filament policies**: Shield los maneja.
- **Rate limit** en `download_sheet` (10/min/usuario) y en endpoint de import (1 batch concurrente por usuario).
- **Activity log** (Fase 7): cada acción crítica auditada.

---

## 7. Performance

- Índices: `vehicles.placa` (unique), `vehicles(estado, created_at)`, `import_rows(batch_id, action)`.
- Eager loading en VehicleResource: `with(['media', 'owner', 'createdBy'])`.
- Cache de stats del dashboard: 60s TTL.
- Pagination Filament: 25 default.

---

## 8. Testing (Fase 7)

Cobertura mínima objetivo:
- `VehicleResourceTest` (CRUD + policies por rol)
- `ImportFlowTest` (parse → preview → process → result)
- `GenerateSheetTest` (placeholders rellenados, foto inserta)
- `CalculateCompletionTest` (cada peso)

---

## 9. Riesgos y mitigaciones

| Riesgo | Probabilidad | Mitigación |
|---|---|---|
| Imports >5000 filas timeout | Media | Bus::batch + jobs por fila + tabla `import_rows` |
| Fotos pesadas saturan disco | Media | Conversion `web` 1024px, original opcional |
| Plantilla DOCX rota | Baja | Validación al boot, fallback a plantilla minimal |
| Concurrencia: misma placa importada 2 veces | Media | unique index + try/catch específico → action='updated' |
| Bulk generation 100 fichas | Media | Bus::batch + zip stream + notificación on-complete |
| Usuario cierra navegador durante import | Alta | Polling Livewire reanuda; estado vive en DB, no en sesión |

---

## 10. Decisiones que necesito de ti antes de la Fase 1

| # | Pregunta | Default si no decides |
|---|---|---|
| D1 | ¿PDF en MVP o solo DOCX? | Solo DOCX |
| D2 | ¿Owner como entidad separada o string en vehicle? | Entidad separada (más limpio) |
| D3 | ¿Firma: una global por usuario, o el operador escoge de un pool? | Una por usuario, default = activa |
| D4 | ¿Tienes plantilla `.docx` ya, o la creamos juntos? | La creamos en Fase 2 |
| D5 | ¿Formato/columnas del Excel a importar? Idealmente comparte un archivo de muestra. | Usar columnas inferidas del modelo Vehicle |
| D6 | ¿Registro libre de usuarios o solo el admin los crea? | Solo admin |
| D7 | Formato de placa Colombia (`ABC123`/`ABC-123`/`ABC123D`)? | Regex: `^[A-Z]{3}[-]?\d{2,3}[A-Z]?$` |

---

## 11. Próximos pasos sugeridos

1. Tú revisas este doc y respondes D1–D7 (o aceptas defaults).
2. Yo arranco **Fase 1** (CRUD Vehicle + fotos + roles base) — ~1 jornada.
3. Hito de validación: tú creas 2-3 vehículos de prueba en `/admin/vehicles`.
4. Avanzamos a Fase 2 (DOCX) o saltamos a la que sea más urgente para ti.
