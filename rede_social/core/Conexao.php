<?php
class Conexao {
    private static $pdo;

    public static function getPdo() {
        if (!self::$pdo) {
            $host = 'sql206.infinityfree.com';
            $db   = 'if0_40621847_rede';
            $user = 'if0_40621847';
            $pass = 'Lu1sGusav0';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (\PDOException $e) {
                throw new \PDOException("Erro na conexão com o banco de dados: " . $e->getMessage(), (int)$e->getCode());
            }
        }
        return self::$pdo;
    }
}
?>