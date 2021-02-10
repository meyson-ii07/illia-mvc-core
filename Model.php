<?php

namespace app\core;

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

    public function handleData($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    abstract public function rules(): array;

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
    protected function addError($attribute, $ruleName, $rules = [])
    {
        $message = self::MESSAGES[$ruleName] ?? '';
        if (!empty($rules)) {
            $message = str_replace("{{$rules[0]}}", $rules[1], $message);
        }
        $this->errors[$attribute][] = $message;
    }

    /**
     * @return array
     */
    public function getErrors($attr) : array
    {
        return key_exists($attr, $this->errors) ? $this->errors[$attr] : [];
    }

    abstract public static function tableName(): string;

    abstract public function attributes(): array;

    public function save()
    {
        $SQL = null;
        $id = $this->{"id"};
        $tableName = $this->tableName();
        $attributes = $this->attributes();
        $params = array_map(fn($attr) => ":$attr", $attributes);

        if (isset($id)) {
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
            echo $e->getMessage();
        }

        return true;
    }

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
}