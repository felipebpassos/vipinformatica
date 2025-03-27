<?php
class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true, // Ativando conexões persistentes (pooling)
                PDO::ATTR_TIMEOUT => 5, // Timeout de 5 segundos para conexão
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci' // Melhor suporte Unicode
            ];

            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=utf8mb4",
                $_ENV['DB_HOST'],
                $_ENV['DB_NAME']
            );

            $this->connection = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                $options
            );

            // Configuração adicional de reconexão
            $this->connection->setAttribute(PDO::ATTR_PERSISTENT, true);
        } catch (PDOException $e) {
            // Log detalhado do erro
            error_log("Falha na conexão com o banco de dados: " . $e->getMessage());
            throw new Exception("Não foi possível conectar ao banco de dados. Tente novamente mais tarde.");
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }

    // Método adicional para verificar e reestabelecer conexão
    public function reconnect()
    {
        try {
            if (!$this->connection || !$this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS)) {
                $this->connection = null;
                self::$instance = null;
                return self::getInstance();
            }
            return $this->connection;
        } catch (Exception $e) {
            error_log("Erro ao reconectar: " . $e->getMessage());
            throw $e;
        }
    }
}