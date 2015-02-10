<?php

namespace maxmirazh33\gii\generators\crud;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ColumnSchema;
use yii\db\mysql\Schema;
use yii\db\TableSchema;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\VarDumper;

/**
 * @inheritdoc
 */
class Generator extends \yii\gii\generators\crud\Generator
{
    const LOCAL_VIEW = 0;
    const LOCAL_INDEX = 1;
    const LOCAL_ADD = 2;

    /**
     * @var string lists of local names of model class
     */
    public $localNames;
    /**
     * @var string db connection
     */
    public $db = 'db';
    /**
     * @var bool add this crud in menu
     */
    public $addInMenu = true;
    /**
     * @var bool add in form editable many-to-many relations
     */
    public $editManyMany = true;

    /**
     * @param int $plural required plural
     * @return string local name
     */
    public function getLocalName($plural = self::LOCAL_VIEW)
    {
        $names = explode(' ', $this->localNames);
        if (isset($names[$plural])) {
            return $names[$plural];
        } else {
            switch ($plural) {
                case self::LOCAL_INDEX:
                    return Inflector::pluralize(Inflector::camel2words(StringHelper::basename($this->modelClass)));
                default:
                    return Inflector::camel2words(StringHelper::basename($this->modelClass));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['localNames'], 'safe'],
            [['addInMenu', 'editManyMany'], 'boolean'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'modelClass' => 'Common Model Class',
            'searchModelClass' => 'Backend Model Class',
            'localNames' => 'Local Model Names',
            'addInMenu' => 'Add in menu',
            'editManyMany' => 'Add edit many-to-many',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'modelClass' => 'This is the ActiveRecord class associated with the table that CRUD will be built upon.
                You should provide a fully qualified class name, e.g., <code>common\models\Post</code>.',
            'searchModelClass' => 'This is the name of the backend model class to be generated. You should provide a fully
                qualified namespaced class name, e.g., <code>backend\models\Post</code>.',
            'localNames' => 'This is the local name of model, as <code>Статья Статьи Статью</code>',
            'addInMenu' => 'Add this controller in menu',
            'editManyMany' => 'Add edit many-to-many relations',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function generateSearchRules()
    {
        if (($table = $this->getTableSchema()) === false) {
            return ["[['" . implode("', '", $this->getColumnNames()) . "'], 'safe']"];
        }
        $types = [];
        foreach ($table->columns as $column) {
            switch ($column->type) {
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                default:
                    $types['safe'][] = $column->name;
                    break;
            }
        }

        $rules = [];
        foreach ($types as $type => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], '$type', 'on' => ['search']]";
        }

        return $rules;
    }

    /**
     * Generates validation rules.
     * @return array the generated validation rules
     */
    public function generateRules()
    {
        if (($table = $this->getTableSchema()) === false) {
            return ["[['" . implode("', '", $this->getColumnNames()) . "'], 'safe']"];
        }

        $types = [];
        $lengths = [];
        foreach ($table->columns as $column) {
            if ($column->autoIncrement) {
                continue;
            }
            if (!$column->allowNull && $column->defaultValue === null && !$this->isImage($column->name) && !$this->isFile($column->name)) {
                $types['required'][] = $column->name;
            }

            $relations = $this->generateRelations();
            $isRel = false;
            foreach ($relations as $rel) {
                if ($column->name == $rel['foreignKey']) {
                    $types['in'][] = [$rel['foreignKey'], $rel['relationName']];
                    $isRel = true;
                    break;
                }
            }
            if (!$isRel) {
                switch ($column->type) {
                    case Schema::TYPE_INTEGER:
                    case Schema::TYPE_BIGINT:
                        $types['integer'][] = $column->name;
                        break;
                    case Schema::TYPE_SMALLINT:
                    case Schema::TYPE_BOOLEAN:
                        $types['boolean'][] = $column->name;
                        break;
                    case Schema::TYPE_FLOAT:
                    case Schema::TYPE_DECIMAL:
                    case Schema::TYPE_MONEY:
                        $types['number'][] = $column->name;
                        break;
                    case Schema::TYPE_TIME:
                        $types['time'][] = $column->name;
                        break;
                    case Schema::TYPE_DATETIME:
                    case Schema::TYPE_TIMESTAMP:
                        $types['datetime'][] = $column->name;
                        break;
                    default: // strings
                        if ($this->isDefaultType($column)) {
                            if ($column->size > 0) {
                                $lengths[$column->size][] = $column->name;
                            } else {
                                $types['string'][] = $column->name;
                            }
                        }
                }
            }

            if ($this->isEnum($column)) {
                $types['enum'][] = $column->name;
            } elseif ($this->isImage($column->name)) {
                $types['image'][] = $column->name;
            } elseif ($this->isFile($column->name)) {
                $types['file'][] = $column->name;
            } elseif ($this->isEmail($column->name)) {
                $types['email'][] = $column->name;
            } elseif ($this->isUrl($column->name)) {
                $types['url'][] = $column->name;
            } elseif ($column->dbType == 'year(4)') {
                $types['year'][] = $column->name;
            } elseif ($column->dbType == 'date') {
                $types['date'][] = $column->name;
            }
        }

        foreach ($this->generateManyManyRelations() as $rel) {
            $types['safe'][] = mb_strtolower($rel['className']) . 'List';
        }

        $rules = [];
        foreach ($types as $type => $columns) {
            if ($type == 'enum') {
                foreach ($columns as $col) {
                    $rules[] = "[['" . $col . "'], 'in', 'range' => static::get" . Inflector::humanize(Inflector::variablize($col)) . "Enums(), 'except' => ['search']]";
                }
            } elseif ($type == 'date') {
                $rules[] = "[['" . implode("', '",
                        $columns) . "'], '$type', 'format' => 'php:Y-m-d', 'except' => ['search']]";
            } elseif ($type == 'time') {
                $rules[] = "[['" . implode("', '",
                        $columns) . "'], 'date', 'format' => 'php:H:i:s', 'except' => ['search']]";
            } elseif ($type == 'year') {
                $rules[] = "[['" . implode("', '",
                        $columns) . "'], 'date', 'format' => 'php:Y', 'except' => ['search']]";
            } elseif ($type == 'datetime') {
                $rules[] = "[['" . implode("', '",
                        $columns) . "'], 'date', 'format' => 'php:Y-m-d H:i:s', 'except' => ['search']]";
            } elseif ($type == 'in') {
                foreach ($columns as $col) {
                    $rules[] = "[['" . $col[0] . "'], 'in', 'range' => array_keys(static::get" . $col[1] . "ForDropdown()), 'except' => ['search']]";
                }
            } elseif ($type == 'image') {
                $rules[] = "[['" . implode("', '",
                        $columns) . "'], '$type', 'extensions' => ['jpg', 'jpeg', 'png', 'gif'], 'skipOnEmpty' => \$this->scenario == 'update', 'except' => ['search']]";
            } elseif ($type == 'file') {
                $rules[] = "[['" . implode("', '",
                        $columns) . "'], '$type', 'skipOnEmpty' => \$this->scenario == 'update', 'except' => ['search']]";
            } else {
                $rules[] = "[['" . implode("', '", $columns) . "'], '$type', 'except' => ['search']]";
            }
        }
        foreach ($lengths as $length => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], 'string', 'max' => $length, 'except' => ['search']]";
        }

        // Unique indexes rules
        try {
            $db = $this->getDbConnection();
            $uniqueIndexes = $db->getSchema()->findUniqueIndexes($table);
            foreach ($uniqueIndexes as $uniqueColumns) {
                // Avoid validating auto incremental columns
                if (!$this->isColumnAutoIncremental($table, $uniqueColumns)) {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount == 1) {
                        $rules[] = "[['" . $uniqueColumns[0] . "'], 'unique', 'except' => ['search']]";
                    } elseif ($attributesCount > 1) {
                        $labels = array_intersect_key($this->generateLabels($table), array_flip($uniqueColumns));
                        $lastLabel = array_pop($labels);
                        $columnsList = implode("', '", $uniqueColumns);
                        $rules[] = "[['" . $columnsList . "'], 'unique', 'targetAttribute' => ['" . $columnsList . "'], 'message' => 'The combination of " . implode(', ',
                                $labels) . " and " . $lastLabel . " has already been taken.', 'except' => ['search']]";
                    }
                }
            }
        } catch (NotSupportedException $e) {
            // doesn't support unique indexes information...do nothing
        }

        return $rules;
    }

    /**
     * @param ColumnSchema $column
     * @return bool
     */
    protected function isDefaultType($column)
    {
        return !$this->isFile($column->name)
        && !$this->isImage($column->name)
        && !$this->isEmail($column->name)
        && !$this->isUrl($column->name)
        && !$this->isEnum($column)
        && !$this->isDate($column);
    }

    /**
     * @param ColumnSchema $column
     * @return bool
     */
    public function isDate($column)
    {
        return in_array($column->dbType, ['date', 'datetime', 'year(4)', 'timestamp', 'time']);
    }

    /**
     * @param ColumnSchema $column
     * @return bool
     */
    public function isEnum($column)
    {
        return is_array($column->enumValues) && count($column->enumValues) > 0;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isImage($name)
    {
        if (preg_match('/(image|img|photo|avatar|logo)/i', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isFile($name)
    {
        if (preg_match('/(file|doc|attach|pdf|presentation)/i', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isUrl($name)
    {
        if (preg_match('/(link|url|vk|vkontakte|fb|facebook|google|twitter|instagram)/i', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isEmail($name)
    {
        if (preg_match('/(mail|email)/i', $name)) {
            return true;
        }

        return false;
    }

    /**
     * @return \yii\db\Connection the DB connection as specified by [[db]].
     */
    protected function getDbConnection()
    {
        return Yii::$app->get($this->db, false);
    }

    /**
     * Checks if any of the specified columns is auto incremental.
     * @param \yii\db\TableSchema $table the table schema
     * @param array $columns columns to check for autoIncrement property
     * @return boolean whether any of the specified columns is auto incremental.
     */
    protected function isColumnAutoIncremental($table, $columns)
    {
        foreach ($columns as $column) {
            if (isset($table->columns[$column]) && $table->columns[$column]->autoIncrement) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates the attribute labels for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated attribute labels (name => label)
     */
    public function generateLabels($table)
    {
        $labels = [];
        foreach ($table->columns as $column) {
            if (!strcasecmp($column->name, 'id')) {
                $labels[$column->name] = 'ID';
            } else {
                $label = Inflector::camel2words($column->name);
                if (!empty($label) && substr_compare($label, ' id', -3, 3, true) === 0) {
                    $label = substr($label, 0, -3) . ' ID';
                }
                $labels[$column->name] = $label;
            }
        }

        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function getViewPath()
    {
        return Yii::getAlias('@backend') . '/views/' . $this->getControllerID();
    }

    /**
     * @inheritdoc
     */
    public function generateActiveField($attribute)
    {
        $tableSchema = $this->getTableSchema();
        if ($tableSchema === false || !isset($tableSchema->columns[$attribute])) {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $attribute)) {
                return "\$form->field(\$model, '$attribute')->passwordInput()";
            } else {
                return "\$form->field(\$model, '$attribute')";
            }
        }
        $column = $tableSchema->columns[$attribute];
        $relations = $this->generateRelations();
        foreach ($relations as $rel) {
            if ($rel['foreignKey'] == $column->name) {
                return "\$form->field(\$model, '$attribute')->dropDownList(\$model->get{$rel['relationName']}ForDropDown(), ['prompt' => ''])";
            }
        }
        if ($this->isImage($column->name)) {
            return "\$form->field(\$model, '$attribute')->widget(ImageWidget::className())";
        } elseif ($this->isFile($column->name)) {
            return "\$form->field(\$model, '$attribute')->widget(FileWidget::className())";
        } elseif ($column->phpType === 'boolean' || $column->dbType === 'tinyint(1)') {
            return "\$form->field(\$model, '$attribute')->checkbox()";
        } elseif ($column->type === 'text') {
            return "\$form->field(\$model, '$attribute')->widget(ImperaviWidget::className())";
        } elseif ($column->dbType === 'date') {
            return "\$form->field(\$model, '$attribute')->widget(DatePicker::className(), ['pluginOptions' => ['format' => 'yyyy-mm-dd', 'weekStart' => 1, 'autoclose' => true]])";
        } elseif ($column->dbType == 'time') {
            return "\$form->field(\$model, '$attribute')->widget(TimePicker::className(), ['pluginOptions' => ['format' => 'hh:ii:ss', 'showSeconds' => true, 'showMeridian' => false, 'minuteStep' => 1, 'secondStep' => 1, 'autoclose' => true]])";
        } elseif ($column->dbType == 'datetime' || $column->dbType == 'timestamp') {
            return "\$form->field(\$model, '$attribute')->widget(DateTimePicker::className(), ['pluginOptions' => ['format' => 'yyyy-mm-dd hh:ii:ss', 'weekStart' => 1, 'minuteStep' => 1, 'autoclose' => true]])";
        } elseif ($column->type === 'string' && $column->size > 256) {
            return "\$form->field(\$model, '$attribute')->textArea(['rows' => 6])";
        } else {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                $input = 'passwordInput';
            } else {
                $input = 'textInput';
            }
            if (is_array($column->enumValues) && count($column->enumValues) > 0) {
                return "\$form->field(\$model, '$attribute')->dropDownList(\$model->get" . Inflector::humanize(Inflector::variablize($column->name)) . "Enums(), ['prompt' => ''])";
            } elseif ($column->phpType !== 'string' || $column->size === null) {
                return "\$form->field(\$model, '$attribute')->$input()";
            } else {
                return "\$form->field(\$model, '$attribute')->$input(['maxlength' => $column->size])";
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function generateActiveSearchField($attribute)
    {
        $tableSchema = $this->getTableSchema();
        if ($tableSchema === false) {
            return "\$form->field(\$model, '$attribute')";
        }
        $column = $tableSchema->columns[$attribute];
        if ($column->phpType === 'boolean' || $column->dbType === 'tinyint(1)') {
            return "\$form->field(\$model, '$attribute')->checkbox()";
        } else {
            return "\$form->field(\$model, '$attribute')";
        }
    }

    /**
     * @inheritdoc
     */
    public function generateColumnFormat($column)
    {
        if ($column->phpType === 'boolean' || $column->dbType === 'tinyint(1)') {
            return 'boolean';
        } elseif ($this->isUrl($column->name)) {
            return 'url';
        } elseif ($this->isEmail($column->name)) {
            return 'email';
        } elseif ($column->type == 'text') {
            return 'html';
        } elseif ($column->dbType == 'date') {
            return 'date';
        } elseif ($column->dbType == 'datetime' || $column->dbType == 'timestamp') {
            return 'datetime';
        } else {
            return parent::generateColumnFormat($column);
        }
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $files = parent::generate();

        if ($this->addInMenu) {
            $files[] = $this->addInMenu($this->getControllerID(), $this->getLocalName(self::LOCAL_INDEX));
        }

        return $files;
    }

    /**
     * Add item in menu layout
     * @param string $controller controller name
     * @param string $name menu item label
     * @return CodeFile
     */
    public function addInMenu($controller, $name)
    {
        $menuFile = Yii::getAlias('@backend/views/layouts/_menu.php');
        $content = file_get_contents($menuFile);
        $itemsPos = strpos($content, 'items');
        $bracketPos = strpos($content, '[', $itemsPos);
        $openBrackets = 1;
        $closedBrackets = 0;
        $penultimateBracketPos = 0;
        while ($openBrackets != $closedBrackets) {
            $char = substr($content, $bracketPos + 1, 1);
            if ($char === '[') {
                $openBrackets++;
            } elseif ($char === ']') {
                $closedBrackets++;
                $penultimateBracketPos = $bracketPos;
            }
            $bracketPos++;
        }
        $newItem = "     [\r\n            'label' => '"
            . $name
            . "',\r\n            'url' => ['/"
            . $controller
            . "'],\r\n            'icon' => 'fa-dashboard',\r\n            'active' => Yii::\$app->controller->id == '"
            . $controller
            . "',\r\n        ],\r\n    "
            . substr($content, $bracketPos);
        return new CodeFile($menuFile, substr_replace($content, $newItem, $penultimateBracketPos));
    }

    /**
     * @return array the generated relation declarations
     */
    public function generateRelations()
    {
        $relations = [];
        $table = $this->getTableSchema();
        $className = $this->generateClassName($table->name);
        foreach ($table->foreignKeys as $refs) {
            $hasMany = false;
            $fks = array_keys($refs);
            if (count($table->primaryKey) > count($fks)) {
                $hasMany = true;
            } else {
                foreach ($fks as $key) {
                    if (!in_array($key, $table->primaryKey, true)) {
                        $hasMany = true;
                        break;
                    }
                }
            }
            if ($hasMany) {
                $refTable = $refs[0];
                unset($refs[0]);
                $refClassName = $this->generateClassName($refTable);
                $relationName = $this->generateRelationName($relations, $className, $refTable, $refClassName, $hasMany);
                $class = 'common\models\\' . $refClassName;
                $idAttr = $class::getTableSchema()->primaryKey[0];
                $titleAttr = $this->getNameAttribute($class::getTableSchema());
                $relations[] = [
                    'className' => $refClassName,
                    'relationName' => $relationName,
                    'idAttr' => $idAttr,
                    'titleAttr' => $titleAttr,
                    'foreignKey' => $fks[1],
                ];
            }
        }

        return $relations;
    }

    /**
     * @param TableSchema|bool $schema
     * @return string name attribute
     */
    public function getNameAttribute($schema = false)
    {
        if (!($schema instanceof TableSchema)) {
            return parent::getNameAttribute();
        }
        foreach ($schema->columnNames as $name) {
            if (!strcasecmp($name, 'name') || !strcasecmp($name, 'title')) {
                return $name;
            }
        }
        return $schema->primaryKey[0];
    }

    /**
     * Checks if the given table is a junction table.
     * For simplicity, this method only deals with the case where the pivot contains two PK columns,
     * each referencing a column in a different table.
     * @param \yii\db\TableSchema the table being checked
     * @return array|boolean the relevant foreign key constraint information if the table is a junction table,
     * or false if the table is not a junction table.
     */
    protected function checkPivotTable($table)
    {
        $pk = $table->primaryKey;
        if (count($pk) !== 2) {
            return false;
        }
        $fks = [];
        foreach ($table->foreignKeys as $refs) {
            if (count($refs) === 2) {
                if (isset($refs[$pk[0]])) {
                    $fks[$pk[0]] = [$refs[0], $refs[$pk[0]]];
                } elseif (isset($refs[$pk[1]])) {
                    $fks[$pk[1]] = [$refs[0], $refs[$pk[1]]];
                }
            }
        }
        if (count($fks) === 2 && $fks[$pk[0]][0] !== $fks[$pk[1]][0]) {
            return $fks;
        } else {
            return false;
        }
    }

    /**
     * Generates the link parameter to be used in generating the relation declaration.
     * @param array $refs reference constraint
     * @return string the generated link parameter.
     */
    protected function generateRelationLink($refs)
    {
        $pairs = [];
        foreach ($refs as $a => $b) {
            $pairs[] = "'$a' => '$b'";
        }

        return '[' . implode(', ', $pairs) . ']';
    }

    /**
     * Generate a relation name for the specified table and a base name.
     * @param array $relations the relations being generated currently.
     * @param string $className the class name that will contain the relation declarations
     * @param \yii\db\TableSchema $table the table schema
     * @param string $key a base name that the relation name may be generated from
     * @param boolean $multiple whether this is a has-many relation
     * @return string the relation name
     */
    protected function generateRelationName($relations, $className, $table, $key, $multiple)
    {
        if (!empty($key) && substr_compare($key, 'id', -2, 2, true) === 0 && strcasecmp($key, 'id')) {
            $key = rtrim(substr($key, 0, -2), '_');
        }
        if ($multiple) {
            $key = Inflector::pluralize($key);
        }
        $name = $rawName = Inflector::id2camel($key, '_');
        $i = 0;
        while (isset($table->columns[lcfirst($name)])) {
            $name = $rawName . ($i++);
        }
        while (isset($relations[$className][lcfirst($name)])) {
            $name = $rawName . ($i++);
        }

        return $name;
    }

    /**
     * Generates a class name from the specified table name.
     * @param string $tableName the table name (which may contain schema prefix)
     * @return string the generated class name
     */
    protected function generateClassName($tableName)
    {

        if (($pos = strrpos($tableName, '.')) !== false) {
            $tableName = substr($tableName, $pos + 1);
        }

        $db = $this->getDbConnection();
        $patterns = [];
        $patterns[] = "/^{$db->tablePrefix}(.*?)$/";
        $patterns[] = "/^(.*?){$db->tablePrefix}$/";

        $className = $tableName;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $tableName, $matches)) {
                $className = $matches[1];
                break;
            }
        }

        return Inflector::id2camel($className, '_');
    }

    public function generateManyManyRelations()
    {
        if (!$this->editManyMany) {
            return [];
        }
        $db = $this->getDbConnection();
        $relations = [];
        $table = $this->getTableSchema();
        $className = $this->generateClassName($table->name);
        foreach ($db->getSchema()->getTableSchemas() as $otherTable) {
            if (($fks = $this->checkPivotTable($otherTable)) === false) {
                continue;
            }
            $table0 = $fks[$otherTable->primaryKey[0]][0];
            $table1 = $fks[$otherTable->primaryKey[1]][0];
            if ($table0 == $table->name) {
                $rel = $table1;
                $foreignKey = $otherTable->primaryKey[1];
            } elseif ($table1 == $table->name) {
                $rel = $table0;
                $foreignKey = $otherTable->primaryKey[0];
            } else {
                continue;
            }

            $refClassName = $this->generateClassName($rel);
            $relationName = $this->generateRelationName($relations, $className, $rel, $refClassName, true);
            $class = 'common\models\\' . $refClassName;
            $idAttr = $class::getTableSchema()->primaryKey[0];
            $titleAttr = $this->getNameAttribute($class::getTableSchema());
            $relations[] = [
                'className' => $refClassName,
                'relationName' => $relationName,
                'idAttr' => $idAttr,
                'titleAttr' => $titleAttr,
                'foreignKey' => $foreignKey,
            ];
        }

        return $relations;
    }

    /**
     * @inheritdoc
     */
    public function generateString($string = '', $placeholders = [])
    {
        $string = addslashes($string);
        if ($this->enableI18N) {
            // If there are placeholders, use them
            if (!empty($placeholders)) {
                $ph = ', ' . VarDumper::export($placeholders);
            } else {
                $ph = '';
            }
            $str = "Yii::t('" . $this->messageCategory . "', '" . $string . "'" . $ph . ")";
        } else {
            $string = Yii::t('maxmirazh33-gii/crud', $string);
            // No I18N, replace placeholders by real words, if any
            if (!empty($placeholders)) {
                $phKeys = array_map(function ($word) {
                    return '{' . $word . '}';
                }, array_keys($placeholders));
                $phValues = array_values($placeholders);
                $str = str_replace($phKeys, $phValues, $string);
            } else {
                // No placeholders, just the given string
                $str = $string;
            }
        }
        return $str;
    }

    /**
     * Check need use ImperaviWidget
     * @return bool
     */
    public function useImperavi()
    {
        return $this->issetType('text');
    }

    /**
     * Check need use DatePicker
     * @return bool
     */
    public function useDatePicker()
    {
        return ($this->issetType('date'));
    }

    /**
     * Check need use TimePicker
     * @return bool
     */
    public function useTimePicker()
    {
        return ($this->issetType('time'));
    }

    /**
     * Check need use DateTimePicker
     * @return bool
     */
    public function useDateTimePicker()
    {
        return ($this->issetType('datetime') || $this->issetType('timestamp'));
    }


    /**
     * Check need use maxmirazh33\image\Widget
     * @return bool
     */
    public function useImageWidget()
    {
        foreach ($this->getTableSchema()->columns as $column) {
            if ($this->isImage($column->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $type column type
     * @return bool
     */
    protected function issetType($type)
    {
        foreach ($this->getTableSchema()->columns as $column) {
            if ($column->type === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check isset many-to-many relations
     * @return bool
     */
    public function issetManyMany()
    {
        if (!$this->editManyMany) {
            return false;
        }
        return count($this->generateManyManyRelations()) > 0;
    }

    /**
     * Check isset files and need use multipart?form-data enctype
     * @return bool
     */
    public function issetFiles()
    {
        foreach ($this->getTableSchema()->columns as $column) {
            if ($this->isImage($column->name) || $this->isFile($column->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check need use behaviors
     * @return bool
     */
    public function useBehaviors()
    {
        return $this->useImageWidget() || $this->issetFiles() || ($this->issetManyMany() && $this->editManyMany);
    }
}
