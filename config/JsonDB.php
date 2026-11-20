<?php
/**
 * ARCANA — JSON Database Engine (JsonDB)
 * Persistencia en archivo JSON con soporte para el esquema relacional de 'arcanaestetica'.
 * Soporta CRUD, autoincremento de IDs, exportación, importación y reseteo.
 */

require_once __DIR__ . '/config.php';

class JsonDB {
    private static ?JsonDB $instance = null;
    private string $filePath;
    private array $data = [];
    private ?PDO $pdo = null;
    private bool $isMysql = false;
    private ?string $connectionError = null;

    private function __construct() {
        $this->filePath = defined('JSON_DB_PATH') ? JSON_DB_PATH : APP_ROOT . '/database/arcana_db.json';
        $this->isMysql = (defined('DB_DRIVER') && DB_DRIVER === 'mysql');

        if ($this->isMysql) {
            $pdoOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ];

            // Intentos ordenados: 1. Configuración exacta de .env, 2. localhost:3306, 3. 127.0.0.1:3306
            $connectionAttempts = [
                ['host' => DB_HOST, 'port' => DB_PORT, 'pass' => DB_PASS],
                ['host' => 'localhost', 'port' => '3306', 'pass' => DB_PASS],
                ['host' => '127.0.0.1', 'port' => '3306', 'pass' => DB_PASS],
            ];
            if (DB_PASS !== '') {
                $connectionAttempts[] = ['host' => DB_HOST, 'port' => DB_PORT, 'pass' => ''];
            }

            $connected = false;
            $lastException = null;

            foreach ($connectionAttempts as $attempt) {
                try {
                    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $attempt['host'], $attempt['port'], DB_NAME, DB_CHARSET);
                    $this->pdo = new PDO($dsn, DB_USER, $attempt['pass'], $pdoOptions);
                    $connected = true;
                    break;
                } catch (Exception $e) {
                    $lastException = $e;
                }
            }

            if (!$connected) {
                $this->pdo = null;
                $this->connectionError = $lastException ? $lastException->getMessage() : 'Desconocido';
                error_log('JsonDB: No se pudo conectar a MySQL: ' . $this->connectionError);
            } else {
                // Asegurar existencia de tabla verification_tokens
                try {
                    $this->pdo->exec("
                        CREATE TABLE IF NOT EXISTS `verification_tokens` (
                          `id` int NOT NULL AUTO_INCREMENT,
                          `id_usuario` int DEFAULT NULL,
                          `email` varchar(150) NOT NULL,
                          `token` varchar(100) NOT NULL,
                          `expira_en` datetime NOT NULL,
                          `utilizado` tinyint(1) NOT NULL DEFAULT '0',
                          `creado_en` datetime NOT NULL,
                          PRIMARY KEY (`id`),
                          KEY `token` (`token`),
                          KEY `email` (`email`)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                    ");
                } catch (Exception $eTok) {
                    // Ignorar si no hay permisos de DDL
                }
                // Asegurar columna email_verificado en usuario
                try {
                    $this->pdo->exec("ALTER TABLE `usuario` ADD COLUMN `email_verificado` tinyint(1) NOT NULL DEFAULT '0'");
                } catch (Exception $eCol) {
                    // Ignorar si ya existe
                }
            }

            // REGLA CRÍTICA:
            // Si el driver es MySQL, NUNCA cargar datos desde archivo JSON ni hacer fallback a arcana_db.json.
            // Si MySQL está desconectado, los datos permanecen vacíos para evitar mostrar datos fantasma.
            $this->data = [];
        } else {
            // Modo JSON explícito (DB_DRIVER === 'json')
            $this->load();
        }
    }

    public static function getInstance(): JsonDB {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function isConnectedToMysql(): bool {
        return $this->isMysql && $this->pdo !== null;
    }

    public function getPdo(): ?PDO {
        return $this->pdo;
    }

    public function getConnectionError(): ?string {
        return $this->connectionError;
    }

    /**
     * Carga el archivo JSON en memoria o lo inicializa si no existe
     */
    private function load(): void {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (!file_exists($this->filePath) || filesize($this->filePath) === 0) {
            $this->resetToDefault();
        } else {
            $content = file_get_contents($this->filePath);
            $decoded = json_decode($content, true);
            if (!is_array($decoded)) {
                $this->resetToDefault();
            } else {
                $this->data = $decoded;
            }
        }
    }

    /**
     * Guarda atómicamente los datos en el archivo JSON
     */
    public function save(): bool {
        if ($this->isMysql) return true;
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return (file_put_contents($this->filePath, $json, LOCK_EX) !== false);
    }

    /**
     * Resuelve alias de tablas comunes para compatibilidad total MySQL / JSON
     */
    public function resolveTableName(string $table): string {
        $aliases = [
            'cuentas_cobro' => 'payment_accounts',
            'cuentas' => 'payment_accounts',
            'historial_cambios' => 'cambios_dashboard',
            'auditoria_turnos' => 'cambios_dashboard',
            'usuarios' => 'usuario',
            'servicios' => 'servicio',
            'empleados' => 'empleado',
            'categorias' => 'categoria',
            'especialidades' => 'especialidad',
            'espacios' => 'espacio',
            'precios' => 'precio_servicio',
            'bloqueos' => 'bloqueo_dia'
        ];
        return $aliases[$table] ?? $table;
    }

    /**
     * Obtiene una tabla completa (array de registros) desde MySQL o JSON
     */
    public function getTable(string $table): array {
        $resolvedTable = $this->resolveTableName($table);
        if ($this->isMysql) {
            if (!$this->pdo) {
                // MySQL está configurado pero desconectado: NO devolver datos del JSON de prueba
                return [];
            }
            try {
                $stmt = $this->pdo->query("SELECT * FROM `$resolvedTable`");
                $rows = $stmt->fetchAll();
                
                // Normalización para compatibilidad
                if ($resolvedTable === 'payment_accounts') {
                    $rows = array_map(function($r) {
                        if (empty($r['nombre_referencia'])) {
                            $r['nombre_referencia'] = $r['titular'] ?? 'Cuenta de Cobro';
                        }
                        if (empty($r['proveedor'])) {
                            $tipo = strtolower($r['tipo_cuenta'] ?? '');
                            if (str_contains($tipo, 'mercado')) $r['proveedor'] = 'mercadopago';
                            elseif (str_contains($tipo, 'uala')) $r['proveedor'] = 'ualabis';
                            elseif (str_contains($tipo, 'efectivo')) $r['proveedor'] = 'efectivo';
                            else $r['proveedor'] = 'transferencia';
                        }
                        if (empty($r['banco_billetera']) && !empty($r['banco'])) {
                            $r['banco_billetera'] = $r['banco'];
                        }
                        return $r;
                    }, $rows);
                } elseif ($resolvedTable === 'cambios_dashboard') {
                    $rows = array_map(function($r) {
                        if (empty($r['fecha']) && !empty($r['fecha_hora'])) {
                            $r['fecha'] = date('d/m/Y H:i:s', strtotime($r['fecha_hora']));
                        }
                        if (empty($r['timestamp']) && !empty($r['fecha_hora'])) {
                            $r['timestamp'] = strtotime($r['fecha_hora']);
                        }
                        if (empty($r['usuario']) && !empty($r['usuario_nombre'])) {
                            $r['usuario'] = $r['usuario_nombre'];
                        }
                        if (empty($r['rol']) && !empty($r['usuario_rol'])) {
                            $r['rol'] = $r['usuario_rol'];
                        }
                        if (empty($r['detalle']) && !empty($r['detalles'])) {
                            $r['detalle'] = $r['detalles'];
                        }
                        return $r;
                    }, $rows);
                }

                return $rows;
            } catch (Exception $e) {
                error_log("Error en getTable MySQL ($resolvedTable): " . $e->getMessage());
                return [];
            }
        }
        
        $data = $this->data[$resolvedTable] ?? ($this->data[$table] ?? []);
        return $data;
    }

    /**
     * Obtiene un registro por su clave primaria
     */
    public function getById(string $table, string $idField, $id): ?array {
        $resolvedTable = $this->resolveTableName($table);
        if ($this->isMysql) {
            if (!$this->pdo) {
                return null;
            }
            try {
                $stmt = $this->pdo->prepare("SELECT * FROM `$resolvedTable` WHERE `$idField` = ? LIMIT 1");
                $stmt->execute([$id]);
                $res = $stmt->fetch();
                if ($res) {
                    if ($resolvedTable === 'payment_accounts') {
                        if (empty($res['nombre_referencia'])) $res['nombre_referencia'] = $res['titular'] ?? 'Cuenta de Cobro';
                        if (empty($res['proveedor'])) {
                            $tipo = strtolower($res['tipo_cuenta'] ?? '');
                            if (str_contains($tipo, 'mercado')) $res['proveedor'] = 'mercadopago';
                            elseif (str_contains($tipo, 'uala')) $res['proveedor'] = 'ualabis';
                            elseif (str_contains($tipo, 'efectivo')) $res['proveedor'] = 'efectivo';
                            else $res['proveedor'] = 'transferencia';
                        }
                        if (empty($res['banco_billetera']) && !empty($res['banco'])) $res['banco_billetera'] = $res['banco'];
                    }
                    return $res;
                }
                return null;
            } catch (Exception $e) {
                error_log("Error en getById MySQL ($resolvedTable): " . $e->getMessage());
                return null;
            }
        }

        $records = $this->getTable($resolvedTable);
        foreach ($records as $row) {
            if (isset($row[$idField]) && (string)$row[$idField] === (string)$id) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Inserta un nuevo registro con ID autoincremental
     */
    public function insert(string $table, array $record, string $idField): array {
        $resolvedTable = $this->resolveTableName($table);
        if ($this->isMysql) {
            if (!$this->pdo) {
                throw new RuntimeException("No se puede insertar el registro: la base de datos MySQL está desconectada.");
            }
            try {
                // Eliminar idField si viene nulo para dejar que MySQL use AUTO_INCREMENT
                if (isset($record[$idField]) && ($record[$idField] === null || $record[$idField] === 0 || $record[$idField] === '')) {
                    unset($record[$idField]);
                }

                // Normalización de campos para MySQL
                if ($resolvedTable === 'cambios_dashboard') {
                    if (empty($record['usuario_nombre']) && !empty($record['usuario'])) {
                        $record['usuario_nombre'] = $record['usuario'];
                    }
                    if (empty($record['usuario_rol']) && !empty($record['rol'])) {
                        $record['usuario_rol'] = $record['rol'];
                    }
                    if (empty($record['detalles']) && !empty($record['detalle'])) {
                        $record['detalles'] = $record['detalle'];
                    }
                    if (empty($record['id_usuario'])) {
                        $record['id_usuario'] = (int)($_SESSION['user_id'] ?? 1);
                    }
                    if (empty($record['usuario_nombre'])) {
                        $record['usuario_nombre'] = $_SESSION['user_name'] ?? 'Super Admin';
                    }
                    if (empty($record['usuario_rol'])) {
                        $record['usuario_rol'] = (isset($_SESSION['id_rol']) && (int)$_SESSION['id_rol'] === 1) ? 'Super Admin' : 'Administrador';
                    }
                    if (empty($record['fecha_hora'])) {
                        $record['fecha_hora'] = date('Y-m-d H:i:s');
                    }
                } elseif ($resolvedTable === 'payment_accounts') {
                    if (empty($record['tipo_cuenta']) && !empty($record['proveedor'])) {
                        $record['tipo_cuenta'] = $record['proveedor'];
                    }
                    if (empty($record['banco']) && !empty($record['banco_billetera'])) {
                        $record['banco'] = $record['banco_billetera'];
                    }
                }

                // Obtener columnas reales de la tabla en MySQL para no fallar con columnas inexistentes
                try {
                    $stmtCols = $this->pdo->query("SHOW COLUMNS FROM `$resolvedTable`");
                    $validCols = array_column($stmtCols->fetchAll(), 'Field');
                } catch (Exception $eCol) {
                    if ($resolvedTable === 'verification_tokens') {
                        $this->pdo->exec("
                            CREATE TABLE IF NOT EXISTS `verification_tokens` (
                              `id` int NOT NULL AUTO_INCREMENT,
                              `id_usuario` int DEFAULT NULL,
                              `email` varchar(150) NOT NULL,
                              `token` varchar(100) NOT NULL,
                              `expira_en` datetime NOT NULL,
                              `utilizado` tinyint(1) NOT NULL DEFAULT '0',
                              `creado_en` datetime NOT NULL,
                              PRIMARY KEY (`id`),
                              KEY `token` (`token`),
                              KEY `email` (`email`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                        ");
                        $stmtCols = $this->pdo->query("SHOW COLUMNS FROM `$resolvedTable`");
                        $validCols = array_column($stmtCols->fetchAll(), 'Field');
                    } else {
                        throw $eCol;
                    }
                }

                $filteredRecord = [];
                foreach ($record as $col => $val) {
                    if (in_array($col, $validCols)) {
                        $filteredRecord[$col] = $val;
                    }
                }

                if (empty($filteredRecord)) {
                    $filteredRecord = $record;
                }

                $cols = array_keys($filteredRecord);
                $escapedCols = array_map(function($c) { return "`$c`"; }, $cols);
                $placeholders = array_fill(0, count($cols), '?');

                $sql = sprintf(
                    "INSERT INTO `%s` (%s) VALUES (%s)",
                    $resolvedTable,
                    implode(', ', $escapedCols),
                    implode(', ', $placeholders)
                );

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(array_values($filteredRecord));
                $newId = (int)$this->pdo->lastInsertId();

                if ($newId > 0) {
                    $record[$idField] = $newId;
                }
                return $record;
            } catch (Exception $e) {
                error_log("Error en insert MySQL ($resolvedTable): " . $e->getMessage());
                throw $e;
            }
        }

        if (!isset($this->data[$resolvedTable])) {
            $this->data[$resolvedTable] = [];
        }

        $maxId = 0;
        foreach ($this->data[$resolvedTable] as $row) {
            if (isset($row[$idField]) && is_numeric($row[$idField])) {
                $maxId = max($maxId, (int)$row[$idField]);
            }
        }
        $newId = $maxId + 1;
        $record[$idField] = $newId;

        $this->data[$table][] = $record;
        $this->save();

        return $record;
    }

    /**
     * Actualiza un registro por ID
     */
    public function update(string $table, string $idField, $id, array $updates): ?array {
        $resolvedTable = $this->resolveTableName($table);
        if ($this->isMysql) {
            if (!$this->pdo) {
                throw new RuntimeException("No se puede actualizar el registro: la base de datos MySQL está desconectada.");
            }
            try {
                unset($updates[$idField]); // No modificar la clave primaria
                if (empty($updates)) {
                    return $this->getById($resolvedTable, $idField, $id);
                }

                // Filtrar solo columnas válidas existentes en MySQL
                $stmtCols = $this->pdo->query("SHOW COLUMNS FROM `$resolvedTable`");
                $validCols = array_column($stmtCols->fetchAll(), 'Field');

                $setParts = [];
                $params = [];
                foreach ($updates as $col => $val) {
                    if (in_array($col, $validCols)) {
                        $setParts[] = "`$col` = ?";
                        $params[] = $val;
                    }
                }

                if (!empty($setParts)) {
                    $params[] = $id;
                    $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` = ?", $resolvedTable, implode(', ', $setParts), $idField);
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);
                }

                return $this->getById($resolvedTable, $idField, $id);
            } catch (Exception $e) {
                error_log("Error en update MySQL ($resolvedTable): " . $e->getMessage());
                throw $e;
            }
        }

        if (!isset($this->data[$resolvedTable])) return null;

        foreach ($this->data[$resolvedTable] as $idx => $row) {
            if (isset($row[$idField]) && (string)$row[$idField] === (string)$id) {
                $updates[$idField] = $row[$idField];
                $this->data[$resolvedTable][$idx] = array_merge($row, $updates);
                $this->save();
                return $this->data[$resolvedTable][$idx];
            }
        }
        return null;
    }

    /**
     * Elimina un registro por ID
     */
    public function delete(string $table, string $idField, $id): bool {
        $resolvedTable = $this->resolveTableName($table);
        if ($this->isMysql) {
            if (!$this->pdo) {
                return false;
            }
            try {
                $stmt = $this->pdo->prepare("DELETE FROM `$resolvedTable` WHERE `$idField` = ?");
                $stmt->execute([$id]);
                return $stmt->rowCount() > 0;
            } catch (Exception $e) {
                error_log("Error en delete MySQL ($resolvedTable): " . $e->getMessage());
                return false;
            }
        }

        if (!isset($this->data[$resolvedTable])) return false;

        $initialCount = count($this->data[$resolvedTable]);
        $this->data[$resolvedTable] = array_values(array_filter($this->data[$resolvedTable], function($row) use ($idField, $id) {
            return !isset($row[$idField]) || (string)$row[$idField] !== (string)$id;
        }));

        if (count($this->data[$resolvedTable]) !== $initialCount) {
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * Elimina registros por condición
     */
    public function deleteWhere(string $table, string $field, $value): int {
        $resolvedTable = $this->resolveTableName($table);
        if ($this->isMysql) {
            if (!$this->pdo) {
                return 0;
            }
            try {
                $stmt = $this->pdo->prepare("DELETE FROM `$resolvedTable` WHERE `$field` = ?");
                $stmt->execute([$value]);
                return $stmt->rowCount();
            } catch (Exception $e) {
                error_log("Error en deleteWhere MySQL ($resolvedTable): " . $e->getMessage());
                return 0;
            }
        }

        if (!isset($this->data[$resolvedTable])) return 0;
        $initial = count($this->data[$resolvedTable]);
        $this->data[$resolvedTable] = array_values(array_filter($this->data[$resolvedTable], function($row) use ($field, $value) {
            return !isset($row[$field]) || (string)$row[$field] !== (string)$value;
        }));
        $deleted = $initial - count($this->data[$resolvedTable]);
        if ($deleted > 0) {
            $this->save();
        }
        return $deleted;
    }

    /**
     * Vacía una tabla completa
     */
    public function clearTable(string $table): void {
        $resolvedTable = $this->resolveTableName($table);
        if ($this->isMysql) {
            if (!$this->pdo) {
                return;
            }
            try {
                $this->pdo->exec("TRUNCATE TABLE `$resolvedTable`");
                return;
            } catch (Exception $e) {
                $this->pdo->exec("DELETE FROM `$resolvedTable`");
                return;
            }
        }
        $this->data[$resolvedTable] = [];
        $this->save();
    }

    /**
     * Busca registros que cumplan con una función de filtro
     */
    public function find(string $table, callable $filterFn): array {
        if ($this->isMysql && !$this->pdo) {
            return [];
        }
        $records = $this->getTable($table);
        return array_values(array_filter($records, $filterFn));
    }

    /**
     * Exporta toda la base de datos como string JSON
     */
    public function exportJson(): string {
        if ($this->isMysql) {
            if (!$this->pdo) {
                return json_encode([], JSON_PRETTY_PRINT);
            }
            return json_encode($this->getAllData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Importa una base de datos completa desde un string JSON o array
     */
    public function importJson($input): bool {
        if ($this->isMysql) {
            return false;
        }
        if (is_string($input)) {
            $decoded = json_decode($input, true);
        } elseif (is_array($input)) {
            $decoded = $input;
        } else {
            return false;
        }

        if (!is_array($decoded)) {
            return false;
        }

        // Validar tablas mínimas requeridas
        $requiredTables = ['usuario', 'servicio', 'empleado', 'citas', 'roles'];
        foreach ($requiredTables as $req) {
            if (!isset($decoded[$req])) {
                $decoded[$req] = [];
            }
        }

        $this->data = $decoded;
        return $this->save();
    }

    /**
     * Obtiene el estado crudo de los datos
     */
    public function getAllData(): array {
        if ($this->isMysql) {
            if (!$this->pdo) {
                return [];
            }
            $tables = ['usuario', 'roles', 'servicio', 'precio_servicio', 'empleado', 'especialidad', 'citas', 'espacio', 'payment_accounts', 'bloqueo_dia', 'cambios_dashboard'];
            $result = [];
            foreach ($tables as $t) {
                $result[$t] = $this->getTable($t);
            }
            return $result;
        }
        return $this->data;
    }

    /**
     * Restablece la base de datos a los datos de prueba por defecto
     */
    public function resetToDefault(): void {
        if ($this->isMysql) {
            return;
        }
        $defaultPasswordHash = password_hash('123456', PASSWORD_BCRYPT);
        $adminPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);

        $this->data = [
            'roles' => [
                ['id_rol' => 1, 'nombre_rol' => 'admin'],
                ['id_rol' => 2, 'nombre_rol' => 'usuario'],
                ['id_rol' => 3, 'nombre_rol' => 'empleado']
            ],
            'usuario' => [
                [
                    'id_usuario' => 1,
                    'nombre' => 'Anabela',
                    'apellido' => 'Acosta',
                    'email' => 'anaacostra00@gmail.com',
                    'contrasena' => $adminPasswordHash,
                    'fecha_registro' => '2026-01-10',
                    'activo' => 1,
                    'telefono' => '11-4567-8901',
                    'DNI' => '40123456',
                    'id_rol' => 1
                ],
                [
                    'id_usuario' => 2,
                    'nombre' => 'Lucía',
                    'apellido' => 'García',
                    'email' => 'garcia1996@gmail.com',
                    'contrasena' => $defaultPasswordHash,
                    'fecha_registro' => '2026-02-15',
                    'activo' => 1,
                    'telefono' => '11-9876-5432',
                    'DNI' => '39876543',
                    'id_rol' => 2
                ],
                [
                    'id_usuario' => 3,
                    'nombre' => 'Camila',
                    'apellido' => 'Rodríguez',
                    'email' => 'camila.rodriguez@gmail.com',
                    'contrasena' => $defaultPasswordHash,
                    'fecha_registro' => '2026-03-01',
                    'activo' => 1,
                    'telefono' => '11-5555-1234',
                    'DNI' => '42333444',
                    'id_rol' => 2
                ],
                [
                    'id_usuario' => 4,
                    'nombre' => 'Martina',
                    'apellido' => 'López',
                    'email' => 'martina.lopez@gmail.com',
                    'contrasena' => $defaultPasswordHash,
                    'fecha_registro' => '2026-03-12',
                    'activo' => 0,
                    'telefono' => '11-2222-3333',
                    'DNI' => '38111222',
                    'id_rol' => 2
                ]
            ],
            'categoria' => [
                ['id_categoria' => 1, 'nombre_categoria' => 'Manicura'],
                ['id_categoria' => 2, 'nombre_categoria' => 'Pedicura'],
                ['id_categoria' => 3, 'nombre_categoria' => 'Pestañas'],
                ['id_categoria' => 4, 'nombre_categoria' => 'Faciales'],
                ['id_categoria' => 5, 'nombre_categoria' => 'Masajes'],
                ['id_categoria' => 6, 'nombre_categoria' => 'Uñas'],
                ['id_categoria' => 7, 'nombre_categoria' => 'Cejas'],
                ['id_categoria' => 8, 'nombre_categoria' => 'Depilación']
            ],
            'especialidad' => [
                ['id_especialidad' => 1, 'nombre_especialidad' => 'Nail Art y Kapping'],
                ['id_especialidad' => 2, 'nombre_especialidad' => 'Esculpidas Acrílicas'],
                ['id_especialidad' => 3, 'nombre_especialidad' => 'Lifting y Extensiones'],
                ['id_especialidad' => 4, 'nombre_especialidad' => 'Cosmetología y Limpiezas'],
                ['id_especialidad' => 5, 'nombre_especialidad' => 'Masoterapia y Drenaje'],
                ['id_especialidad' => 6, 'nombre_especialidad' => 'Perfilado y Laminado']
            ],
            'espacio' => [
                ['id_espacio' => 1, 'nombre' => 'Cabina Principal A', 'tipo' => 'Cabina', 'estado' => 'Disponible'],
                ['id_espacio' => 2, 'nombre' => 'Cabina Relax B', 'tipo' => 'Cabina', 'estado' => 'Disponible'],
                ['id_espacio' => 3, 'nombre' => 'Puesto Manicura 1', 'tipo' => 'Mesa', 'estado' => 'Disponible'],
                ['id_espacio' => 4, 'nombre' => 'Puesto Manicura 2', 'tipo' => 'Mesa', 'estado' => 'Disponible'],
                ['id_espacio' => 5, 'nombre' => 'Box Pestañas', 'tipo' => 'Box', 'estado' => 'Disponible']
            ],
            'servicio' => [
                [
                    'id_servicio' => 1,
                    'nombre_servicio' => 'Kapping Gel + Esmaltado Semipermanente',
                    'descripcion' => 'Refuerzo de la uña natural con fina capa de gel estructurante y esmaltado de alta durabilidad.',
                    'categoria' => 'Manicura',
                    'disponibilidad' => 1,
                    'imagen_servicio' => 'assets/images/service-manicure.jpg',
                    'duracion_minutos' => 75
                ],
                [
                    'id_servicio' => 2,
                    'nombre_servicio' => 'Esculpidas en Gel Premium',
                    'descripcion' => 'Extensión artesanal de uñas con técnica de molde y acabado ultra natural.',
                    'categoria' => 'Uñas',
                    'disponibilidad' => 1,
                    'imagen_servicio' => 'assets/images/service-manicure.jpg',
                    'duracion_minutos' => 120
                ],
                [
                    'id_servicio' => 3,
                    'nombre_servicio' => 'Lifting de Pestañas + Tinte y Botox',
                    'descripcion' => 'Curvatura natural permanente con nutrición intensiva de queratina para una mirada profunda.',
                    'categoria' => 'Pestañas',
                    'disponibilidad' => 1,
                    'imagen_servicio' => 'assets/images/service-manicure.jpg',
                    'duracion_minutos' => 60
                ],
                [
                    'id_servicio' => 4,
                    'nombre_servicio' => 'Limpieza Facial Profunda con Punta de Diamante',
                    'descripcion' => 'Higiene facial con microdermoabrasión, extracción indolora, máscara descongestiva y serum.',
                    'categoria' => 'Faciales',
                    'disponibilidad' => 1,
                    'imagen_servicio' => 'assets/images/service-manicure.jpg',
                    'duracion_minutos' => 60
                ],
                [
                    'id_servicio' => 5,
                    'nombre_servicio' => 'Masaje Descontracturante con Piedras Calientes',
                    'descripcion' => 'Terapia corporal relajante y descontracturante para alivio de tensiones y contracturas.',
                    'categoria' => 'Masajes',
                    'disponibilidad' => 1,
                    'imagen_servicio' => 'assets/images/service-manicure.jpg',
                    'duracion_minutos' => 50
                ],
                [
                    'id_servicio' => 6,
                    'nombre_servicio' => 'Spa de Pies y Pedicura Rusa',
                    'descripcion' => 'Tratamiento integral de pies con exfoliación profunda, hidratación y esmaltado semipermanente.',
                    'categoria' => 'Pedicura',
                    'disponibilidad' => 0,
                    'imagen_servicio' => 'assets/images/service-manicure.jpg',
                    'duracion_minutos' => 60
                ]
            ],
            'categoria_servicio' => [
                ['id_categoria_servicio' => 1, 'id_categoria' => 1, 'id_servicio' => 1],
                ['id_categoria_servicio' => 2, 'id_categoria' => 6, 'id_servicio' => 2],
                ['id_categoria_servicio' => 3, 'id_categoria' => 3, 'id_servicio' => 3],
                ['id_categoria_servicio' => 4, 'id_categoria' => 4, 'id_servicio' => 4],
                ['id_categoria_servicio' => 5, 'id_categoria' => 5, 'id_servicio' => 5],
                ['id_categoria_servicio' => 6, 'id_categoria' => 2, 'id_servicio' => 6]
            ],
            'precio_servicio' => [
                ['id_precio_servicio' => 1, 'id_servicio' => 1, 'precio' => 15500.00, 'fecha_desde' => '2026-01-01', 'fecha_hasta' => null],
                ['id_precio_servicio' => 2, 'id_servicio' => 2, 'precio' => 24000.00, 'fecha_desde' => '2026-01-01', 'fecha_hasta' => null],
                ['id_precio_servicio' => 3, 'id_servicio' => 3, 'precio' => 18000.00, 'fecha_desde' => '2026-01-01', 'fecha_hasta' => null],
                ['id_precio_servicio' => 4, 'id_servicio' => 4, 'precio' => 21000.00, 'fecha_desde' => '2026-01-01', 'fecha_hasta' => null],
                ['id_precio_servicio' => 5, 'id_servicio' => 5, 'precio' => 26000.00, 'fecha_desde' => '2026-01-01', 'fecha_hasta' => null],
                ['id_precio_servicio' => 6, 'id_servicio' => 6, 'precio' => 16500.00, 'fecha_desde' => '2026-01-01', 'fecha_hasta' => null]
            ],
            'empleado' => [
                [
                    'id_empleado' => 1,
                    'nombre' => 'Valentina',
                    'apellido' => 'Pérez',
                    'telefono' => '11-3456-7890',
                    'email' => 'valentina.perez@arcana.com',
                    'imagenEmpleado' => 'assets/images/team-avatar.jpg'
                ],
                [
                    'id_empleado' => 2,
                    'nombre' => 'Sofía',
                    'apellido' => 'Mendoza',
                    'telefono' => '11-8765-4321',
                    'email' => 'sofia.mendoza@arcana.com',
                    'imagenEmpleado' => 'assets/images/team-avatar.jpg'
                ],
                [
                    'id_empleado' => 3,
                    'nombre' => 'Florencia',
                    'apellido' => 'Castro',
                    'telefono' => '11-6677-8899',
                    'email' => 'florencia.castro@arcana.com',
                    'imagenEmpleado' => 'assets/images/team-avatar.jpg'
                ]
            ],
            'empleado_especialidad' => [
                ['id_empleado_especialidad' => 1, 'id_empleado' => 1, 'id_especialidad' => 1],
                ['id_empleado_especialidad' => 2, 'id_empleado' => 1, 'id_especialidad' => 2],
                ['id_empleado_especialidad' => 3, 'id_empleado' => 2, 'id_especialidad' => 3],
                ['id_empleado_especialidad' => 4, 'id_empleado' => 2, 'id_especialidad' => 6],
                ['id_empleado_especialidad' => 5, 'id_empleado' => 3, 'id_especialidad' => 4],
                ['id_empleado_especialidad' => 6, 'id_empleado' => 3, 'id_especialidad' => 5]
            ],
            'servicio_empleado' => [
                ['id_servicio_empleado' => 1, 'id_servicio' => 1, 'id_empleado' => 1, 'modalidad_disponib' => 'Presencial', 'max_por_dia' => 6],
                ['id_servicio_empleado' => 2, 'id_servicio' => 2, 'id_empleado' => 1, 'modalidad_disponib' => 'Presencial', 'max_por_dia' => 4],
                ['id_servicio_empleado' => 3, 'id_servicio' => 3, 'id_empleado' => 2, 'modalidad_disponib' => 'Presencial', 'max_por_dia' => 7],
                ['id_servicio_empleado' => 4, 'id_servicio' => 4, 'id_empleado' => 3, 'modalidad_disponib' => 'Presencial', 'max_por_dia' => 5],
                ['id_servicio_empleado' => 5, 'id_servicio' => 5, 'id_empleado' => 3, 'modalidad_disponib' => 'Presencial', 'max_por_dia' => 4],
                ['id_servicio_empleado' => 6, 'id_servicio' => 6, 'id_empleado' => 1, 'modalidad_disponib' => 'Presencial', 'max_por_dia' => 5]
            ],
            'citas' => [
                [
                    'id_cita' => 1,
                    'id_usuario' => 2,
                    'id_espacio' => 3,
                    'fecha_hora' => '2026-09-10 14:00:00',
                    'estado' => 'confirmado',
                    'modalidad' => 'Presencial',
                    'observaciones' => 'Uñas en tono nude con brillo',
                    'creada_en' => '2026-09-01 10:30:00',
                    'modificada_en' => '2026-09-01 10:30:00'
                ],
                [
                    'id_cita' => 2,
                    'id_usuario' => 3,
                    'id_espacio' => 5,
                    'fecha_hora' => '2026-09-11 16:30:00',
                    'estado' => 'pendiente',
                    'modalidad' => 'Presencial',
                    'observaciones' => 'Primera sesión de lifting',
                    'creada_en' => '2026-09-02 11:15:00',
                    'modificada_en' => '2026-09-02 11:15:00'
                ],
                [
                    'id_cita' => 3,
                    'id_usuario' => 2,
                    'id_espacio' => 1,
                    'fecha_hora' => '2026-08-20 11:00:00',
                    'estado' => 'completado',
                    'modalidad' => 'Presencial',
                    'observaciones' => 'Limpieza facial finalizada con éxito',
                    'creada_en' => '2026-08-15 09:00:00',
                    'modificada_en' => '2026-08-20 12:05:00'
                ],
                [
                    'id_cita' => 4,
                    'id_usuario' => 4,
                    'id_espacio' => 2,
                    'fecha_hora' => '2026-08-25 18:00:00',
                    'estado' => 'cancelado',
                    'modalidad' => 'Presencial',
                    'observaciones' => 'Cancelado por el cliente con previo aviso',
                    'creada_en' => '2026-08-22 14:00:00',
                    'modificada_en' => '2026-08-24 16:30:00'
                ]
            ],
            'cita_servicio' => [
                ['id_cita_servicio' => 1, 'id_cita' => 1, 'id_servicio_empleado' => 1],
                ['id_cita_servicio' => 2, 'id_cita' => 2, 'id_servicio_empleado' => 3],
                ['id_cita_servicio' => 3, 'id_cita' => 3, 'id_servicio_empleado' => 4],
                ['id_cita_servicio' => 4, 'id_cita' => 4, 'id_servicio_empleado' => 5]
            ],
            'bloqueo_dia' => [
                [
                    'id_bloqueo_dia' => 1,
                    'id_usuario' => 1,
                    'fecha_desde' => '2026-12-24',
                    'fecha_hasta' => '2026-12-25',
                    'motivo' => 'Feriado de Navidad'
                ],
                [
                    'id_bloqueo_dia' => 2,
                    'id_usuario' => 1,
                    'fecha_desde' => '2026-12-31',
                    'fecha_hasta' => '2027-01-01',
                    'motivo' => 'Feriado de Fin de Año'
                ]
            ],
            'imagen_cita' => [],
            'reset_tokens' => [],
            'cuentas_cobro' => [
                [
                    'id_cuenta' => 1,
                    'nombre_referencia' => 'Mercado Pago (Superadmin Anabela)',
                    'proveedor' => 'mercadopago',
                    'titular' => 'Anabela Acosta',
                    'cuit_cuil' => '27-40123456-4',
                    'email_asociado' => 'anaacostra00@gmail.com',
                    'alias' => 'arcana.anabela.mp',
                    'cbu_cvu' => '0000003100098765432190',
                    'banco_billetera' => 'Mercado Pago',
                    'public_key' => 'TEST-4f56a89c-1234-5678-9abc-def012345678',
                    'access_token' => 'TEST-1234567890123456-123456-abcdef0123456789abcdef0123456789-123456789',
                    'modo' => 'sandbox',
                    'es_activa' => 1,
                    'estado' => 'activo',
                    'creado_en' => '2026-01-10 10:00:00'
                ],
                [
                    'id_cuenta' => 2,
                    'nombre_referencia' => 'Mercado Pago / Cuenta (Admin Fernando)',
                    'proveedor' => 'mercadopago',
                    'titular' => 'Fernando Cabral',
                    'cuit_cuil' => '20-37043233-8',
                    'email_asociado' => 'ferbralca2099@gmail.com',
                    'alias' => 'arcana.fernando.mp',
                    'cbu_cvu' => '0000003100037043233891',
                    'banco_billetera' => 'Mercado Pago / Banco',
                    'public_key' => 'TEST-7890bcde-1234-5678-9abc-def012345678',
                    'access_token' => 'TEST-9876543210987654-654321-fedcba9876543210fedcba9876543210-987654321',
                    'modo' => 'sandbox',
                    'es_activa' => 0,
                    'estado' => 'activo',
                    'creado_en' => '2026-02-15 11:30:00'
                ]
            ]
        ];

        $this->save();
    }
}
