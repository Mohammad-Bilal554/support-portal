<?php
declare(strict_types=1);
namespace App\Core;

class Validator {
    private array $errors = [];
    private array $validated = [];
    private static array $messages = [
        'required'=>'The :field field is required.','email'=>'The :field must be a valid email.',
        'url'=>'The :field must be a valid URL.','min'=>'The :field must be at least :param.',
        'max'=>'The :field must not exceed :param.','min_length'=>'The :field must be at least :param characters.',
        'max_length'=>'The :field must not exceed :param characters.','integer'=>'The :field must be an integer.',
        'numeric'=>'The :field must be numeric.','string'=>'The :field must be a string.',
        'boolean'=>'The :field must be true or false.','in'=>'The selected :field is invalid.',
        'not_in'=>'The selected :field is invalid.','regex'=>'The :field format is invalid.',
        'confirmed'=>'The :field confirmation does not match.','unique'=>'The :field has already been taken.',
        'exists'=>'The selected :field is invalid.','date'=>'The :field must be a valid date.',
    ];
    public function __construct(private array $data, private array $rules) {}
    public function validate(): bool {
        foreach ($this->rules as $field => $ruleStr) {
            $rules    = is_array($ruleStr) ? $ruleStr : explode('|',$ruleStr);
            $value    = $this->data[$field] ?? null;
            $nullable = in_array('nullable',$rules);
            foreach ($rules as $rule) {
                if ($rule === 'nullable') continue;
                if ($nullable && ($value===null||$value==='')) continue;
                [$rName,$param] = str_contains($rule,':') ? explode(':',$rule,2) : [$rule,null];
                if (!$this->applyRule($field,$value,$rName,$param)) break;
            }
            if (!isset($this->errors[$field])) $this->validated[$field] = $value;
        }
        return empty($this->errors);
    }
    public function fails(): bool  { return !$this->validate(); }
    public function passes(): bool { return $this->validate(); }
    public function errors(): array { return $this->errors; }
    public function validated(): array { return $this->validated; }
    private function applyRule(string $field, mixed $value, string $rule, ?string $param): bool {
        $passed = match($rule) {
            'required'   => $value!==null && $value!=='' && !(is_array($value)&&empty($value)),
            'string'     => is_string($value),
            'integer'    => filter_var($value,FILTER_VALIDATE_INT)!==false,
            'numeric'    => is_numeric($value),
            'boolean'    => in_array($value,[true,false,0,1,'0','1','true','false'],true),
            'email'      => filter_var($value,FILTER_VALIDATE_EMAIL)!==false,
            'url'        => filter_var($value,FILTER_VALIDATE_URL)!==false,
            'date'       => (bool)strtotime((string)$value),
            'min'        => is_numeric($value)&&(float)$value>=(float)$param,
            'max'        => is_numeric($value)&&(float)$value<=(float)$param,
            'min_length' => strlen((string)$value)>=(int)$param,
            'max_length' => strlen((string)$value)<=(int)$param,
            'in'         => in_array((string)$value,explode(',',$param??''),true),
            'not_in'     => !in_array((string)$value,explode(',',$param??''),true),
            'regex'      => (bool)preg_match($param??'/.*/','(string)'.$value),
            'confirmed'  => isset($this->data[$field.'_confirmation'])&&$value===$this->data[$field.'_confirmation'],
            'unique'     => $this->checkUnique($field,$value,$param),
            'exists'     => $this->checkExists($value,$param),
            default      => true,
        };
        if (!$passed) {
            $msg = static::$messages[$rule] ?? "The :field failed the {$rule} rule.";
            $msg = str_replace([':field',':param'],[ucfirst(str_replace('_',' ',$field)),(string)($param??'')],$msg);
            $this->errors[$field][] = $msg;
        }
        return $passed;
    }
    private function checkUnique(string $field, mixed $value, ?string $param): bool {
        if (!$param) return true;
        $parts=$explode=explode(',',$param); $table=$parts[0]; $col=$parts[1]??$field; $ignore=$parts[2]??null;
        $sql="SELECT COUNT(*) FROM `{$table}` WHERE `{$col}` = ?"; $params=[$value];
        if ($ignore) { $sql.=' AND `id` != ?'; $params[]=$ignore; }
        return (int)Database::getInstance()->fetchColumn($sql,$params)===0;
    }
    private function checkExists(mixed $value, ?string $param): bool {
        if (!$param) return true;
        [$table,$col] = explode(',',$param.',id');
        return (int)Database::getInstance()->fetchColumn("SELECT COUNT(*) FROM `{$table}` WHERE `{$col}` = ?",[$value])>0;
    }
}
