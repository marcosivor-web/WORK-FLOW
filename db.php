<?php
// ============================================================
//  AdminFlow — Ligação e helpers da Base de Dados (PDO)
// ============================================================
require_once __DIR__ . '/config.php';

class DB {
    private static ?PDO $conn = null;

    // Devolve a conexão PDO (singleton)
    public static function get(): PDO {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode([
                    'ok'  => false,
                    'msg' => 'Erro de ligação à base de dados: ' . $e->getMessage()
                ]));
            }
        }
        return self::$conn;
    }

    // Executa SELECT e devolve array de rows
    public static function query(string $sql, array $params = []): array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Executa INSERT/UPDATE/DELETE — devolve lastInsertId ou rowCount
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $lid = (int) self::get()->lastInsertId();
        return $lid > 0 ? $lid : $stmt->rowCount();
    }

    // Devolve uma única row ou null
    public static function row(string $sql, array $params = []): ?array {
        $rows = self::query($sql, $params);
        return $rows[0] ?? null;
    }

    // Devolve um único valor escalar
    public static function scalar(string $sql, array $params = []): mixed {
        $row = self::row($sql, $params);
        return $row ? array_values($row)[0] : null;
    }
}
