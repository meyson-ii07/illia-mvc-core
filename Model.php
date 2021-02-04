<?php

namespace app\core;

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

    abstract public function tableName(): string;

    abstract public function attributes(): array;

    public function save()
    {
        $tableName = $this->tableName();
        $attributes = $this->attributes();

        $params = array_map(fn($attr) => ":$attr", $attributes);
        $SQL = "INSERT INTO $tableName (".implode(',', $attributes).") VALUES(".implode(',', $params).")";
        $statement = Application::$app->db->prepare($SQL);

        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }

        $statement->execute();
        return true;
    }
}