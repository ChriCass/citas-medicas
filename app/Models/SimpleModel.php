<?php

namespace App\Models;

use App\Core\SimpleDatabase;

abstract class SimpleModel
{
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $db;

    public function __construct()
    {
        $this->db = SimpleDatabase::getInstance();
    }

    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function findBy($column, $value)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
        return $this->db->fetchOne($sql, ['value' => $value]);
    }

    public function findAll($conditions = [], $orderBy = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $whereParts = [];
            foreach ($conditions as $column => $value) {
                $whereParts[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    public function create($data)
    {
        // Filtrar solo los campos fillable
        $filteredData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $filteredData[$key] = $value;
            }
        }

        return $this->db->insert($this->table, $filteredData);
    }

    public function update($id, $data)
    {
        // Filtrar solo los campos fillable
        $filteredData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $filteredData[$key] = $value;
            }
        }

        $this->db->update($this->table, $filteredData, "{$this->primaryKey} = :id", ['id' => $id]);
    }

    public function delete($id)
    {
        $this->db->delete($this->table, "{$this->primaryKey} = :id", ['id' => $id]);
    }

    public function query($sql, $params = [])
    {
        return $this->db->fetchAll($sql, $params);
    }

    public function queryOne($sql, $params = [])
    {
        return $this->db->fetchOne($sql, $params);
    }
}
