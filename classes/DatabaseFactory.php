<?php
declare(strict_types=1);

class DatabaseFactory
{
    public const DRIVERS = [
        'mysql' => 'MySQL',
        'mariadb' => 'MariaDB',
        'pgsql' => 'PostgreSQL',
        'postgres' => 'PostgreSQL',
        'firebird' => 'Firebird',
        'sqlite' => 'SQLite',
    ];

    public static function create(string $type, string $host, string $user = '', string $pass = '', ?string $database = null, ?int $port = null): DatabaseConnection
    {
        return new DatabaseConnection($type, $host, $user, $pass, $database, $port);
    }
}

class DatabaseConnection
{
    private PDO $pdo;
    private string $type;
    private string $host;
    private ?string $database;

    public function __construct(string $type, string $host, string $user = '', string $pass = '', ?string $database = null, ?int $port = null)
    {
        $this->type = $type === 'mariadb' ? 'mysql' : ($type === 'postgres' ? 'pgsql' : $type);
        $this->host = $host;
        $this->database = $database;

        $dsn = match ($this->type) {
            'sqlite' => 'sqlite:' . ($database ?: $host),
            'pgsql' => 'pgsql:host=' . $host . ($port ? ';port=' . $port : '') . ($database ? ';dbname=' . $database : ''),
            'firebird' => 'firebird:dbname=' . $host . ($port ? '/' . $port : '') . ($database ? ':' . $database : ''),
            default => 'mysql:host=' . $host . ($port ? ';port=' . $port : '') . ($database ? ';dbname=' . $database : '') . ';charset=utf8mb4',
        };

        $this->pdo = new PDO($dsn, $this->type === 'sqlite' ? null : $user, $this->type === 'sqlite' ? null : $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function disconnect(): void
    {
    }

    public function rawQuery(string $sql, array $params = []): Generator
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function getAllDatabases(): array
    {
        if ($this->type === 'sqlite') {
            return [$this->host];
        }
        if ($this->type === 'pgsql') {
            return $this->pdo->query("SELECT datname FROM pg_database WHERE datistemplate = false ORDER BY datname")->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($this->type === 'firebird') {
            return [$this->database ?: $this->host];
        }
        return $this->pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAllTables(?string $database = null): array
    {
        if ($this->type === 'sqlite') {
            return $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($this->type === 'pgsql') {
            return $this->pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($this->type === 'firebird') {
            return $this->pdo->query("SELECT TRIM(rdb\$relation_name) AS name FROM rdb\$relations WHERE rdb\$view_blr IS NULL AND (rdb\$system_flag IS NULL OR rdb\$system_flag = 0) ORDER BY rdb\$relation_name")->fetchAll(PDO::FETCH_COLUMN);
        }
        $db = $database ?: $this->database;
        $stmt = $this->pdo->query('SHOW TABLES FROM ' . $this->quoteIdent((string) $db));
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getTableSchema(string $table, ?string $database = null): array
    {
        if ($this->type === 'sqlite') {
            $rows = $this->pdo->query('PRAGMA table_info(' . $this->quoteIdent($table) . ')')->fetchAll();
            return array_map(fn (array $row): array => [
                'name' => $row['name'],
                'type' => $row['type'],
                'nullable' => !(bool) $row['notnull'],
                'key' => ((int) $row['pk']) > 0 ? 'PRI' : '',
                'default' => $row['dflt_value'],
                'extra' => '',
            ], $rows);
        }

        if ($this->type === 'pgsql') {
            $stmt = $this->pdo->prepare("SELECT column_name, data_type, is_nullable, column_default FROM information_schema.columns WHERE table_schema='public' AND table_name=? ORDER BY ordinal_position");
            $stmt->execute([$table]);
            $primary = $this->getPrimaryKey($table, $database);
            return array_map(fn (array $row): array => [
                'name' => $row['column_name'],
                'type' => $row['data_type'],
                'nullable' => $row['is_nullable'] === 'YES',
                'key' => in_array($row['column_name'], $primary, true) ? 'PRI' : '',
                'default' => $row['column_default'],
                'extra' => '',
            ], $stmt->fetchAll());
        }

        if ($this->type === 'firebird') {
            return [];
        }

        $db = $database ?: $this->database;
        $stmt = $this->pdo->query('SHOW COLUMNS FROM ' . $this->quoteIdent((string) $db) . '.' . $this->quoteIdent($table));
        return array_map(fn (array $row): array => [
            'name' => $row['Field'],
            'type' => $row['Type'],
            'nullable' => $row['Null'] === 'YES',
            'key' => $row['Key'] ?? '',
            'default' => $row['Default'] ?? null,
            'extra' => $row['Extra'] ?? '',
        ], $stmt->fetchAll());
    }

    public function getPrimaryKey(string $table, ?string $database = null): array
    {
        return array_values(array_map(
            fn (array $col): string => $col['name'],
            array_filter($this->getTableSchema($table, $database), fn (array $col): bool => ($col['key'] ?? '') === 'PRI')
        ));
    }

    public function countRows(string $table, ?string $database = null): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM ' . $this->tableName($table, $database))->fetchColumn();
    }

    public function getTableData(string $table, ?string $database = null, int $limit = 50, int $offset = 0): Generator
    {
        yield from $this->rawQuery('SELECT * FROM ' . $this->tableName($table, $database) . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
    }

    public function searchTable(string $table, ?string $database, string $search, int $limit = 50, int $offset = 0): Generator
    {
        $schema = $this->getTableSchema($table, $database);
        $clauses = array_map(fn (array $col): string => 'CAST(' . $this->quoteIdent($col['name']) . ' AS CHAR) LIKE ?', $schema);
        $params = array_fill(0, count($clauses), '%' . $search . '%');
        yield from $this->rawQuery('SELECT * FROM ' . $this->tableName($table, $database) . ' WHERE ' . implode(' OR ', $clauses) . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset, $params);
    }

    public function insert(string $table, array $data, ?string $database = null): string
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO ' . $this->tableName($table, $database) . ' (' . implode(', ', array_map([$this, 'quoteIdent'], $cols)) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
        $this->pdo->prepare($sql)->execute(array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $pk, ?string $database = null): int
    {
        $sets = array_map(fn (string $col): string => $this->quoteIdent($col) . ' = ?', array_keys($data));
        $where = array_map(fn (string $col): string => $this->quoteIdent($col) . ' = ?', array_keys($pk));
        $stmt = $this->pdo->prepare('UPDATE ' . $this->tableName($table, $database) . ' SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $where));
        $stmt->execute(array_merge(array_values($data), array_values($pk)));
        return $stmt->rowCount();
    }

    public function delete(string $table, array $pk, ?string $database = null): int
    {
        $where = array_map(fn (string $col): string => $this->quoteIdent($col) . ' = ?', array_keys($pk));
        $stmt = $this->pdo->prepare('DELETE FROM ' . $this->tableName($table, $database) . ' WHERE ' . implode(' AND ', $where));
        $stmt->execute(array_values($pk));
        return $stmt->rowCount();
    }

    private function tableName(string $table, ?string $database = null): string
    {
        if ($this->type === 'mysql' && ($database ?: $this->database)) {
            return $this->quoteIdent((string) ($database ?: $this->database)) . '.' . $this->quoteIdent($table);
        }
        return $this->quoteIdent($table);
    }

    private function quoteIdent(string $name): string
    {
        $quote = $this->type === 'mysql' ? '`' : '"';
        return $quote . str_replace($quote, $quote . $quote, $name) . $quote;
    }
}
