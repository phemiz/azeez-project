<?php
namespace App\Core;

/**
 * Base Repository Pattern Interface
 * Implements reusable CRUD methods using the QueryBuilder
 */
abstract class Repository {
    protected string $table;

    public function __construct() {
        if (empty($this->table)) {
            throw new \RuntimeException("Table name must be defined in subclass Repository.");
        }
    }

    protected function query(): QueryBuilder {
        $qb = new QueryBuilder();
        return $qb->table($this->table);
    }

    /**
     * Fetch a single row by Primary Key.
     */
    public function find(int $id): ?array {
        return $this->query()->where('id', '=', $id)->first();
    }

    /**
     * Fetch all rows from the table.
     */
    public function all(): array {
        return $this->query()->get();
    }

    /**
     * Create a new record and return the generated ID.
     */
    public function create(array $data): int {
        return $this->query()->insert($data);
    }

    /**
     * Update an existing record.
     */
    public function update(int $id, array $data): bool {
        return $this->query()->where('id', '=', $id)->update($data);
    }

    /**
     * Delete a record by ID.
     */
    public function delete(int $id): bool {
        return $this->query()->where('id', '=', $id)->delete();
    }
}
