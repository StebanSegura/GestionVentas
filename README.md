# Sistema de Gestión de Ventas Masivas (ETL & Analytics)

## 1. Guía de Instalación y Ejecución

### Requisitos previos
- PHP 8.2 o superior, con las extensiones `pdo_mysql` y `mysqli` habilitadas
- Composer 2.x
- MySQL 5.7+ / 8.x (o PostgreSQL, ajustando `DB_CONNECTION` en el `.env`)
- Node.js (solo si vas a compilar assets con Vite; no es obligatorio para esta prueba)

No se requiere Redis: el sistema usa `QUEUE_CONNECTION=database` para las colas.

### Pasos de instalación

```bash
# 1. Crear el proyecto Laravel (si aún no lo tienes)
composer create-project laravel/laravel nombre-proyecto
cd nombre-proyecto

# 2. Copiar los archivos de este entregable dentro del proyecto,
#    FUSIONANDO con las carpetas existentes (no reemplazar app/, database/,
#    routes/ completos, ya que Laravel trae archivos base que deben conservarse):
#    - app/Models/Import.php, Sale.php, ImportError.php
#    - app/Jobs/ProcessImportJob.php
#    - app/Http/Controllers/ImportController.php, ReportController.php
#    - database/migrations/*_create_imports_table.php (y sales, import_errors)
#    - resources/views/imports/ (carpeta completa)
#    - El CONTENIDO de routes/web.php y routes/api.php debe integrarse con
#      lo que el proyecto ya trae (no sobrescribir el archivo completo)

# 3. Configurar el archivo .env
cp .env.example .env
php artisan key:generate

# Edita estas líneas del .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=ventas_etl
# DB_USERNAME=root
# DB_PASSWORD=
# QUEUE_CONNECTION=database

# 4. Crear la base de datos "ventas_etl" en MySQL antes de continuar
#    (con HeidiSQL, phpMyAdmin, o por línea de comandos):
mysql -u root -e "CREATE DATABASE ventas_etl"

# 5. Crear la tabla de colas y correr todas las migraciones
#    (imports, sales, import_errors, jobs, etc. en un solo paso)
php artisan queue:table
php artisan migrate

# 6. Levantar el worker de colas en una terminal dedicada.
#    IMPORTANTE: sin este comando corriendo, las importaciones se quedan
#    en estado "processing" para siempre, porque el Job nunca se ejecuta.
#    Déjala abierta mientras usas la aplicación.
php artisan queue:work

# 7. En OTRA terminal (sin cerrar la del worker), levantar el servidor
php artisan serve
```

Accede a:
- `http://localhost:8000/upload` → formulario de carga
- `http://localhost:8000/` → dashboard con historial
- `http://localhost:8000/imports/{id}` → reporte de una importación


### Endpoints de la API

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/imports` | Sube un CSV, responde 202 de inmediato |
| GET | `/api/imports` | Listado paginado de importaciones |
| GET | `/api/imports/{id}/errors` | Errores de una importación (paginado) |
| DELETE | `/api/imports/{id}` | Elimina importación + ventas + errores |
| GET | `/api/reports/summary?import_id=X` | Reporte agregado |

## 2. Decisiones Técnicas

### Procesamiento eficiente de archivos
- El endpoint `POST /api/imports` **no procesa el archivo en el request**. Solo lo guarda en disco, crea el registro `Import` en estado `processing`, y despacha un `Job` en cola (`ProcessImportJob`). Esto permite que el endpoint responda en milisegundos incluso con archivos de 100,000 filas.
- El `Job` lee el CSV con `fgetcsv()` **fila por fila** (streaming), nunca carga el archivo completo en memoria.
- Los `insert` a base de datos se hacen **en lotes de 1,000 filas** (`DB::table('sales')->insert($buffer)`), no fila por fila. Insertar 100,000 filas individualmente con Eloquent puede tardar varios minutos; en lotes de 1,000, se reduce a segundos.
- Las filas inválidas no detienen el proceso: se registran en `import_errors` con el número de fila y el motivo exacto del fallo (fecha inválida, precio negativo, cantidad en cero, campos vacíos).

### Rapidez en la generación de reportes
- Las agregaciones (`SUM`, `GROUP BY`) se calculan **con SQL directamente en la base de datos**, nunca trayendo los registros a PHP para sumarlos ahí.
- Se agregaron **índices compuestos** en `sales` sobre `(import_id, product_id)`, `(import_id, category)` y `(import_id, country)` — exactamente las combinaciones que usa el reporte — para que el motor de base de datos no tenga que escanear toda la tabla.
- El resultado del reporte se **cachea 10 minutos** (`Cache::remember`) por `import_id`, ya que los datos de una importación completada no cambian: evita recalcular las mismas agregaciones en cada visita.

### Propuesta de escalabilidad a millones de registros
Si en el futuro el volumen de datos crece mucho (archivos de millones de filas, o muchas cargas al mismo tiempo), estos serían los siguientes pasos a implementar:

1. **Dividir la tabla de ventas en partes más pequeñas por dentro** (particionamiento por fecha o por importación), para que el sistema no tenga que revisar toda la tabla cada vez que se hace una consulta o se borra una importación — es como tener varios archivadores organizados por año en vez de uno solo gigante.
2. **Repartir el trabajo de un mismo archivo entre varios procesos a la vez**, en lugar de que uno solo lea todo el CSV de principio a fin. Así, un archivo de millones de filas se procesa varias veces más rápido.
3. **Guardar los totales ya calculados de antemano**, en vez de sumarlos cada vez que alguien abre el reporte. Hoy el sistema calcula los totales al momento de pedir el reporte; con volúmenes muy grandes, conviene calcularlos una sola vez durante la importación y solo "leerlos" después, sin volver a sumar nada.
4. **Usar una forma de carga masiva más directa de MySQL**, que es considerablemente más rápida que insertar los datos desde el código, para archivos extremadamente grandes.
5. **Tener varios "trabajadores" procesando importaciones en paralelo**, en vez de uno solo, para que si llegan varios archivos al mismo tiempo no se forme una fila de espera.
6. Si con el tiempo también crece mucho la cantidad de gente consultando reportes, se puede tener una **copia de la base de datos dedicada solo a lectura**, para que las consultas de reportes no interfieran con las nuevas cargas de archivos.
