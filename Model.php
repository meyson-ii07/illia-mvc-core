<?php

namespace app\core;

use app\core\Services\QueryBuilder;
use PDO;

abstract class Model
{
    protected const RULE_REQUIRED = 'required';
    protected const RULE_EMAIL = 'email';
    protected const RULE_MIN = 'min';
    protected const RULE_MAX = 'max';

    private array $errors = [];

    private const MESSAGES = [
        self::RULE_REQUIRED => 'This field is required',
        self::RULE_MAX => 'This field should contain less then {max} symbols',
        self::RULE_MIN => 'This field should contain more then {min} symbols',
        self::RULE_EMAIL => 'This field should be a valid email address',
    ];

    /**
     * Sets given data for current model
     * @param $data
     */
    public function handleData($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Set of required validation rules
     * @return array
     */
    abstract public function rules(): array;

    /**
     * Validates current set of values
     */
    public function validate()
    {
        $this->errors = [];
        foreach ($this->rules() as $key => $rules) {
            $value = $this->{$key};
            foreach ($rules as $rule) {
                $ruleName = $rule;
                if(!is_string($ruleName)) {
                    $ruleName = $rule[0];
                }
                if ($ruleName === self::RULE_REQUIRED && !$value) {
                    $this->addError($key, $ruleName);
                }
                if ($ruleName === self::RULE_EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($key, $ruleName);
                }
                if ($ruleName === self::RULE_MIN && strlen($value) < $rule[1]) {
                    $this->addError($key, $ruleName, $rule);
                }
                if ($ruleName === self::RULE_MAX  && strlen($value) > $rule[1]) {
                    $this->addError($key, $ruleName, $rule);
                }
            }
        }
    }

    /**
     * Adds validation error for given attribute
     * @param $attribute
     * @param $ruleName
     * @param array $rules
     */
    protected function addError($attribute, $ruleName, $rules = [])
    {
        $message = self::MESSAGES[$ruleName] ?? '';
        if (!empty($rules)) {
            $message = str_replace("{{$rules[0]}}", $rules[1], $message);
        }
        $this->errors[$attribute][] = $message;
    }

    /**
     * Returns array of errors for current model
     * @return array
     */
    public function getErrors($attr) : array
    {
        return key_exists($attr, $this->errors) ? $this->errors[$attr] : [];
    }

    /**
     * Returns database table name for current model
     * @return string
     */
    abstract public static function tableName(): string;

    /**
     * Returns set of attributes of current model
     * @return array
     */
    abstract public function attributes(): array;

    /**
     * Saves data to database
     * @return bool
     */
    public function save()
    {
        $SQL = null;
        $id = $this->{"id"};
        $tableName = $this->tableName();
        $attributes = $this->attributes();
        $params = array_map(fn($attr) => ":$attr", $attributes);

        if (isset($id) && !empty($id)) {
            $values = array_combine($attributes, $params);
            $res = [];
            foreach ($values as $key => $value) {
                $res[] = $key."=".$value;
            }
            $SQL = "UPDATE $tableName 
            SET ".implode(',', $res)."
            WHERE id = $id";
        } else {
            $SQL = "INSERT INTO $tableName (".implode(',', $attributes).") VALUES(".implode(',', $params).")";
        }

        $statement = Application::$app->db->prepare($SQL);

        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }

        try {
            $statement->execute();
        } catch (\Exception  $e) {
            echo $e->getMessage().PHP_EOL;
        }

        return true;
    }

    /**
     * Deletes record from database
     * @param $id
     */
    public static function delete($id)
    {
        $tableName = static::tableName();
        if (isset($id)) {
            $SQL = "DELETE FROM $tableName
            WHERE id = $id";
            $statement = Application::$app->db->prepare($SQL);
            try {
                $statement->execute();
            } catch (\Exception  $e) {
                die($e->getMessage());
            }
        }
    }

    /**
     * Returns record from database with given id
     * @param int|null $id
     * @return mixed
     */
    public static function findOne(int $id = null)
    {
        $tableName = static::tableName();
        $result = null;
        if ($id !== null) {
            $SQL = "SELECT * FROM $tableName
            WHERE id = $id";

        }

        $statement = Application::$app->db->prepareQuarry($SQL);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        try {
            $result = $statement->fetch();
        } catch (\Exception  $e) {
            die($e->getMessage());
        }
        return $result;
    }

    /**
     * Returns records from database that match given options
     * @param array $where
     * @param int|null $limit
     * @param string|null $order
     * @return array
     */
    public static function find(array $where = [], int $limit = null, string $order = null)
    {

        $tableName = static::tableName();
        $result = null;
        $SQL = "SELECT * FROM $tableName";
        if (!empty($where)) {
            $res = [];
            foreach ($where as $key => $value) {
                $res[] = $key."=".$value;
            }

            $SQL .= " WHERE ".implode(',', $res);

            if ($limit != null) {
                $SQL .= " LIMIT $limit";
            }

            if ($order) {
                $SQL .= " ORDER BY $order";
            }
        }

        $statement = Application::$app->db->prepareQuarry($SQL);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        try {
            $result = $statement->fetchAll();
        } catch (\Exception  $e) {
            die($e->getMessage());
        }
        return $result;
    }

    public static function select(array $args = null): QueryBuilder
    {
        $qb = new QueryBuilder();
        $result = $qb->select(static::tableName(), $args);

//        if (is_array($result) && $result['convert']) {
//            $models = [];
//            foreach ($result as $item) {
//                $modelClass = static::class;
//                $model = new $modelClass;
//                $model->handleData($item);
//                $models[] = $model;
//            }
//
//        }
        return $result;
    }
}