<?php
/**
 * ARCANA — Database Connection (PDO Singleton)
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                if (DB_DRIVER === 'sqlite') {
                    $dbDir = dirname(SQLITE_PATH);
                    if (!is_dir($dbDir)) {
                        mkdir($dbDir, 0777, true);
                    }
                    
                    $isNewDb = !file_exists(SQLITE_PATH) || filesize(SQLITE_PATH) === 0;
                    
                    self::$instance = new PDO('sqlite:' . SQLITE_PATH);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec('PRAGMA foreign_keys = ON;');

                    if ($isNewDb) {
                        self::initializeDatabase(self::$instance);
                    }
                } else {
                    // MySQL Connection
                    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
                    self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                }
            } catch (PDOException $e) {
                if (APP_ENV === 'development') {
                    die('Error de conexión a la base de datos: ' . $e->getMessage());
                } else {
                    error_log('Database Connection Error: ' . $e->getMessage());
                    die('Error al conectar con el servidor.');
                }
            }
        }
        return self::$instance;
    }

    private static function initializeDatabase(PDO $pdo): void {
        $schemaFile = APP_ROOT . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
        }
        
        $seederFile = APP_ROOT . '/database/seed.php';
        if (file_exists($seederFile)) {
            require_once $seederFile;
            if (function_exists('seedDatabase')) {
                seedDatabase($pdo);
            }
        }
    }
}
