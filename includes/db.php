<?php
/**
 * Conexión a la base de datos con PDO
 * Singleton pattern para una sola conexión por request
 */

class Database {
    private static ?PDO $instance = null;

    /**
     * Obtiene la instancia PDO (singleton)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $e) {
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    die('Error de conexión a la base de datos: ' . $e->getMessage());
                }
                die('Error de conexión a la base de datos. Por favor contacte al administrador.');
            }
        }
        return self::$instance;
    }

    /**
     * Prevenir clonación del singleton
     */
    private function __clone() {}
    private function __construct() {}
}

/**
 * Función helper para obtener la conexión PDO
 */
function db(): PDO {
    return Database::getInstance();
}

/**
 * Ejecuta una query y retorna todos los resultados
 */
function dbQuery(string $sql, array $params = []): array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ejecuta una query y retorna el primer resultado
 */
function dbQueryOne(string $sql, array $params = []): ?array {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Ejecuta una query de escritura (INSERT, UPDATE, DELETE)
 * Retorna el número de filas afectadas
 */
function dbExecute(string $sql, array $params = []): int {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Retorna el último ID insertado
 */
function dbLastId(): string {
    return db()->lastInsertId();
}
