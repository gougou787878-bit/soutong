<?php

use Illuminate\Database\Query\Grammars\MySqlGrammar;

class MyGrammar extends MySqlGrammar
{


    public $tableName = '';

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param array $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', array_map(function ($column) {

            $column = $this->appendTableName($column);
            $column = $this->wrap($column);
            return $column;
        }, $columns));
    }


    protected function appendTableName($value)
    {
        if (strpos($value, '(') !== false || $value == '*') {
            return $value;
        }

        if (strpos($value, '.') === false && !empty($this->tableName)) {
            $value = $this->tableName . '.' . $value;
        }
        return $value;
    }


    /**
     * Get an array of all the where clauses for the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return array
     */
    protected function compileWheresToArray($query)
    {
        return collect($query->wheres)->map(function ($where) use ($query) {
            if (isset($where['column'])) {
                $where['column'] = $this->appendTableName($where['column']);
            }
            return $where['boolean'] . ' ' . $this->{"where{$where['type']}"}($query, $where);
        })->all();
    }


    /**
     * Compile the query orders to an array.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $orders
     * @return array
     */
    protected function compileOrdersToArray(\Illuminate\Database\Query\Builder $query, $orders)
    {
        return array_map(function ($order) {
            if (isset($order['column'])) {
                $order['column'] = $this->appendTableName($order['column']);
            }
            return $order['sql'] ?? $this->wrap($order['column']) . ' ' . $order['direction'];
        }, $orders);
    }

    /**
     * @param string $tableName
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

}