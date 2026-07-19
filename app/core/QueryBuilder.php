<?php
namespace App\Core;

/**
 * Enterprise Query Builder Class
 * Enables programmatically building secure SQL queries with parameterized bindings.
 * Automatically mitigates SQL Injection risks.
 */
class QueryBuilder {
    private Database $db;
    private string $table = '';
    private array $select = ['*'];
    private array $wheres = [];
    private array $joins = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $bindings = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Set target database table.
     */
    public function table(string $table): self {
        $this->table = "`{$table}`";
        return $this;
    }

    /**
     * Set columns to retrieve.
     */
    public function select(array $columns): self {
        $this->select = array_map(function($col) {
            if ($col === '*' || strpos($col, '(') !== false) {
                return $col;
            }
            // Handle table.column or table.* references
            if (strpos($col, '.') !== false) {
                $parts = explode('.', $col, 2);
                return "`{$parts[0]}`." . ($parts[1] === '*' ? '*' : "`{$parts[1]}`");
            }
            return "`{$col}`";
        }, $columns);
        return $this;
    }

    /**
     * Adds a where condition with binding parameterization.
     */
    public function where(string $column, string $operator, $value): self {
        $this->wheres[] = [
            'column'   => $this->quoteColumn($column),
            'operator' => $operator,
            'value'    => '?'
        ];
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Quotes a column reference safely, handling table.column dotted notation.
     */
    private function quoteColumn(string $col): string {
        if (strpos($col, '.') !== false) {
            $parts = explode('.', $col, 2);
            return "`{$parts[0]}`.`{$parts[1]}`";
        }
        return "`{$col}`";
    }

    /**
     * Adds an inner join condition.
     */
    public function join(string $table, string $first, string $operator, string $second): self {
        $this->joins[] = "INNER JOIN `{$table}` ON " . $this->quoteColumn($first) . " {$operator} " . $this->quoteColumn($second);
        return $this;
    }

    /**
     * Adds a left join condition.
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self {
        $this->joins[] = "LEFT JOIN `{$table}` ON " . $this->quoteColumn($first) . " {$operator} " . $this->quoteColumn($second);
        return $this;
    }

    /**
     * Set ordering rule.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "`{$column}` {$direction}";
        return $this;
    }

    /**
     * Limit count of returned rows.
     */
    public function limit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Offset the starting row index.
     */
    public function offset(int $offset): self {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Compile SELECT query.
     */
    public function toSQL(): string {
        $sql = "SELECT " . implode(', ', $this->select) . " FROM " . $this->table;

        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $whereClauses = [];
            foreach ($this->wheres as $w) {
                $whereClauses[] = "{$w['column']} {$w['operator']} {$w['value']}";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT " . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET " . $this->offset;
        }

        return $sql;
    }

    /**
     * Executes compile query and returns all matching rows.
     */
    public function get(): array {
        return $this->db->fetchAll($this->toSQL(), $this->bindings);
    }

    /**
     * Executes compile query and returns the first row.
     */
    public function first(): ?array {
        $this->limit(1);
        return $this->db->fetch($this->toSQL(), $this->bindings);
    }

    /**
     * Executes insert statement.
     */
    public function insert(array $data): int {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');

        $sql = "INSERT INTO " . $this->table . " (" . implode(', ', array_map(fn($c) => "`{$c}`", $columns)) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $this->db->query($sql, array_values($data));
        return (int)$this->db->lastInsertId();
    }

    /**
     * Executes update statement.
     */
    public function update(array $data): bool {
        $sets = [];
        $params = [];
        
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}` = ?";
            $params[] = $val;
        }

        $sql = "UPDATE " . $this->table . " SET " . implode(', ', $sets);

        if (!empty($this->wheres)) {
            $whereClauses = [];
            foreach ($this->wheres as $w) {
                $whereClauses[] = "{$w['column']} {$w['operator']} {$w['value']}";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        // Combine update values bindings with where bindings
        $allParams = array_merge($params, $this->bindings);
        $this->db->query($sql, $allParams);
        return true;
    }

    /**
     * Executes delete statement.
     */
    public function delete(): bool {
        $sql = "DELETE FROM " . $this->table;

        if (!empty($this->wheres)) {
            $whereClauses = [];
            foreach ($this->wheres as $w) {
                $whereClauses[] = "{$w['column']} {$w['operator']} {$w['value']}";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $this->db->query($sql, $this->bindings);
        return true;
    }
}
