<?php

declare(strict_types=1);

namespace Camoo\Sms\Database;

use Camoo\Sms\Entity\DbConfig;
use Camoo\Sms\Interfaces\DriversInterface;
use Exception;
use mysqli;
use mysqli_result;

/**
 * Class MySQL
 */
class MySQL implements DriversInterface
{
    private const DEFAULT_DB_PORT = 3306;

    private mysqli|null $connection = null;

    public function __construct(private readonly ?DbConfig $dbConfig = null, private readonly ?mysqli $mysqli = null)
    {
    }

    public function __destruct()
    {
        $this->close();
    }

    public static function getInstance(?DbConfig $dbConfig = null): self
    {
        return new self($dbConfig);
    }

    public function getDB(): ?self
    {
        $this->connection = $this->dbConnect($this->dbConfig);

        if (!$this->connection) {
            return null;
        }

        return $this;
    }

    public function getDbConfig(): ?DbConfig
    {
        return $this->dbConfig;
    }

    public function escapeString(string $string): string
    {
        return $this->connection->escape_string(trim($string));
    }

    public function close(): bool
    {
        return (bool)$this->connection?->close();
    }

    public function query(string $query): ?mysqli_result
    {
        $result = $this->connection->query($query);

        if (!$result) {
            return null;
        }

        return $result;
    }

    public function insert(string $table, array $variables = []): bool
    {
        //Make sure the array isn't empty
        if (empty($variables)) {
            return false;
        }

        $sql = 'INSERT INTO ' . $this->escapeString($table);
        $fields = [];
        $values = [];
        foreach ($variables as $field => $value) {
            $fields[] = $field;
            $values[] = "'" . $this->escapeString($value) . "'";
        }
        $fields = ' (' . implode(', ', $fields) . ')';
        $values = '(' . implode(', ', $values) . ')';

        $sql .= $fields . ' VALUES ' . $values;
        $query = $this->query($sql);

        return (bool)$query;
    }

    public function getError(): string
    {
        return $this->connection?->error ?? '';
    }

    protected function dbConnect(?DbConfig $dbConfig): ?mysqli
    {
        try {
            $mysqlConnection = $this->mysqli ?? new mysqli(
                $dbConfig->host ?? null,
                $dbConfig->dbUser ?? null,
                $dbConfig->password ?? null,
                $dbConfig->dbName ?? null,
                $dbConfig->dbPort ?? self::DEFAULT_DB_PORT
            );
        } catch (Exception $exception) {
            echo 'Failed to connect to MySQL: ' . $exception->getMessage() . "\n";

            return null;
        }

        return $mysqlConnection;
    }
}
