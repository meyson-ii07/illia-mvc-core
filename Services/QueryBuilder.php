<?php


namespace app\core\Services;


use app\core\Application;
use PDO;

class QueryBuilder
{
    private static string $query;

    // SELECT ...
    protected array $_select;

    // DISTINCT
    protected $_distinct = FALSE;

    // FROM ...
    protected $_from = array();

    // JOIN ...
    protected $_join = array();

    // GROUP BY ...
    protected $_group_by = array();

    // HAVING ...
    protected $_having = array();

    // OFFSET ...
    protected $_offset = null;

    // UNION ...
    protected $_union = array();

    // The last JOIN statement created
    protected $_last_join;

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return self::$query;
    }

    /**
     * @param string $query
     */
    public function setQuery(string $query): void
    {
        self::$query = $query;
    }

    public function select(string $tableName, array $args = null)
    {
        static::$query = "SELECT ";
        if ($args === null) {
            static::$query .= "* FROM ".$tableName;
        } else {
            $selectArgs = null;
            foreach ($args as $key => $val) {
                if (is_array($val) && is_string(key($val))) {
                    $arg = key($val)." as".$val.($selectArgs ? ", " : " ");
                    $selectArgs .= $arg;
                } else if (is_string($val)) {
                    $selectArgs .= $val.($selectArgs ? ', ' : '');
                }
                $selectArgs = rtrim($selectArgs, ', ');
            }
            static::$query .= $selectArgs." FROM ".$tableName;
        }

        return $this;
    }
    public function where($field, string $op, $arg)
    {
        static::$query .= " WHERE $field $op $arg";
        return $this;
    }

    public function andWhere($field, string $op, $arg)
    {
        static::$query .= " and $field $op $arg";
        return $this;
    }

    public function groupBy(string $arg)
    {
        self::$query .= " GROUP BY $arg";
        return $this;
    }

    public function addGroupBy(string $arg)
    {
        static::$query .= ", $arg";
        return $this;
    }

    public function orderBy(string $arg)
    {
        static::$query .= " ORDER BY $arg";
        return $this;
    }

    public function addOrderBy(string $arg)
    {
        static::$query .= ", $arg";
        return $this;
    }

    public function setMaxResults(int $arg)
    {
        static::$query .= " LIMIT $arg";
        return $this;
    }

    public function setOffset(int $arg)
    {
        static::$query .= " OFFSET $arg";
        return $this;
    }

    private function fetchResult()
    {
        try {
            $statement = Application::$app->db->prepareQuarry(static::$query);
            $statement->setFetchMode(PDO::FETCH_ASSOC);

            return $statement->fetchAll();
        } catch (\Exception  $e) {
            die($e->getMessage());
        }
    }

    public function getResult()
    {
        return [ 'convert' => true, $this->fetchResult()];
    }

    public function getArrayResult()
    {
        return $this->fetchResult();
    }
}