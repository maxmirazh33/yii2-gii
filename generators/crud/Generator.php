<?php
namespace maxmirazh33\gii\generators\crud;

use Yii;
use yii\base\NotSupportedException;
use yii\db\mysql\Schema;
use yii\gii\CodeFile;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\VarDumper;

/**
 * @inheritdoc
 */
class Generator extends \yii\gii\generators\crud\Generator
{
    const RUSSIAN_VIEW = 0;
    const RUSSIAN_INDEX = 1;
    const RUSSIAN_ADD = 2;

    /**
     * @var string lists of russian names of model class
     */
    public $russianNames;
    /**
     * @var string db connection
     */
    public $db = 'db';
    /**
     * @var bool add this crud in menu
     */
    public $addInMenu = true;

    /**
     * @param int $plural required plural
     * @return string russian name
     */
    public function getRussianName($plural = self::RUSSIAN_VIEW)
    {
        $names = explode(' ', $this->russianNames);
        if (isset($names[$plural])) {
            return $names[$plural];
        } else {
            switch ($plural) {
                case self::RUSSIAN_INDEX:
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
            [['russianNames'], 'safe'],
            [['addInMenu'], 'boolean'],
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
            'russianNames' => 'Russian Model Names',
            'addInMenu' => 'Add in menu',
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
            'russianNames' => 'This is the russian name of model, as <code>Статья Статьи Статью</code>',
            'addInMenu' => 'Add this controller in menu',
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
            if (!$column->allowNull && $column->defaultValue === null && !$this->isImage($column->name)) {
                $types['required'][] = $column->name;
            }

            $relations = $this->generateRelations();
            $isRel = false;
            foreach ($relations as $rel) {
                if ($column->name == $rel[4]) {
                    $types['in'][] = [$rel[4], $rel[1]];
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
                    case Schema::TYPE_DATE:
                    case Schema::TYPE_TIME:
                    case Schema::TYPE_DATETIME:
                    case Schema::TYPE_TIMESTAMP:
                        $types['date'][] = $column->name;
                        break;
                    default: // strings
                        if (!$this->isImage($column->name)) {
                            if ($column->size > 0) {
                                $lengths[$column->size][] = $column->name;
                            } else {
                                $types['string'][] = $column->name;
                            }
                        }
                }
            }
        }
        $rules = [];
        foreach ($types as $type => $columns) {
            if ($type == 'date') {
                $rules[] = "[['" . implode("', '",
                        $columns) . "'], '$type', 'format' => 'php:Y-m-d', 'except' => ['search']]";
            } elseif ($type == 'in') {
                foreach ($columns as $col) {
                    $rules[] = "[['" . $col[0] . "'], 'in', 'range' => array_keys(static::get" . $col[1] . "ForDropdown()), 'except' => ['search']]";
                }
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
     * @param string $name
     * @return bool
     */
    public function isImage($name)
    {
        if (preg_match('/^(image|img|photo|avatar|logo)$/i', $name)) {
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
    public function getNameAttribute()
    {
        foreach ($this->getColumnNames() as $name) {
            if (!strcasecmp($name, 'name') || !strcasecmp($name, 'title')) {
                return $name;
            }
        }
        /* @var $class \yii\db\ActiveRecord */
        $class = $this->modelClass;
        $pk = $class::primaryKey();

        return $pk[0];
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
            if ($rel[4] == $column->name) {
                return "\$form->field(\$model, '$attribute')->dropDownList(\$model->get{$rel[1]}ForDropDown())";
            }
        }
        if ($this->isImage($column->name)) {
            return "\$form->field(\$model, '$attribute')->widget(ImageWidget::className())";
        } elseif ($column->phpType === 'boolean' || $column->dbType === 'tinyint(1)') {
            return "\$form->field(\$model, '$attribute')->checkbox()";
        } elseif ($column->type === 'text') {
            return "\$form->field(\$model, '$attribute')->widget(ImperaviWidget::className())";
        } elseif ($column->dbType === 'date') {
            return "\$form->field(\$model, '$attribute')->widget(DatePicker::className(), ['pluginOptions' => ['format' => 'yyyy-mm-dd']])";
        } elseif ($column->type === 'string' && $column->size > 256) {
            return "\$form->field(\$model, '$attribute')->textArea(['rows' => 6])";
        } else {
            if (preg_match('/^(password|pass|passwd|passcode)$/i', $column->name)) {
                $input = 'passwordInput';
            } else {
                $input = 'textInput';
            }
            if (is_array($column->enumValues) && count($column->enumValues) > 0) {
                $dropDownOptions = [];
                foreach ($column->enumValues as $enumValue) {
                    $dropDownOptions[$enumValue] = Inflector::humanize($enumValue);
                }
                return "\$form->field(\$model, '$attribute')->dropDownList("
                . preg_replace("/\n\s*/", ' ', VarDumper::export($dropDownOptions)) . ", ['prompt' => ''])";
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
        } elseif ($column->type === 'text') {
            return 'ntext';
        } elseif (stripos($column->name, 'time') !== false && $column->phpType === 'integer') {
            return 'datetime';
        } elseif (stripos($column->name, 'email') !== false) {
            return 'email';
        } elseif (stripos($column->name, 'url') !== false) {
            return 'url';
        } else {
            return 'text';
        }
    }

    /**
     * @inheritdoc
     */
    public function generate()
    {
        $controllerFile = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->controllerClass, '\\')) . '.php');

        $files = [
            new CodeFile($controllerFile, $this->render('controller.php')),
        ];

        if (!empty($this->searchModelClass)) {
            $searchModel = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->searchModelClass, '\\') . '.php'));
            $files[] = new CodeFile($searchModel, $this->render('search.php'));
        }

        $viewPath = $this->getViewPath();
        $templatePath = $this->getTemplatePath() . '/views';
        foreach (scandir($templatePath) as $file) {
            if (empty($this->searchModelClass) && $file === '_search.php') {
                continue;
            }
            if (is_file($templatePath . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $files[] = new CodeFile("$viewPath/$file", $this->render("views/$file"));
            }
        }

        if ($this->addInMenu) {
            $files[] = $this->addInMenu($this->getControllerID(), $this->getRussianName(self::RUSSIAN_INDEX));
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
        $db = $this->getDbConnection();

        $relations = [];
        $table = $this->getTableSchema();
        $tableName = $table->name;
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
                $className = $this->generateClassName($table->name);
                $refClassName = $this->generateClassName($refTable);
                $relationName = $this->generateRelationName($relations, $className, $refTable, $refClassName, $hasMany);
                $class = 'common\models\\' . $refClassName;
                $idAttr = $class::getTableSchema()->primaryKey[0];
                $titleAttr = 'id';
                foreach($class::getTableSchema()->columns as $column) {
                    if (preg_match('/^(name|title)$/i', $column->name)) {
                        $titleAttr = $column->name;
                        break;
                    }
                }
                $relations[] = [
                    $refClassName,
                    $relationName,
                    $idAttr,
                    $titleAttr,
                    $fks[1],
                ];
            }
        }

        if (($fks = $this->checkPivotTable($table)) !== false) {
            $table0 = $fks[$table->primaryKey[0]][0];
            $table1 = $fks[$table->primaryKey[1]][0];
            $className0 = $this->generateClassName($table0);
            $className1 = $this->generateClassName($table1);

            $link = $this->generateRelationLink([$fks[$table->primaryKey[1]][1] => $table->primaryKey[1]]);
            $viaLink = $this->generateRelationLink([$table->primaryKey[0] => $fks[$table->primaryKey[0]][1]]);
            $relationName = $this->generateRelationName($relations, $className0, $db->getTableSchema($table0),
                $table->primaryKey[1], true);
            $relations[$className0][$relationName] = [
                "return \$this->hasMany($className1::className(), $link)->viaTable('" . $table->name . "', $viaLink);",
                $className1,
                true,
            ];

            $link = $this->generateRelationLink([$fks[$table->primaryKey[0]][1] => $table->primaryKey[0]]);
            $viaLink = $this->generateRelationLink([$table->primaryKey[1] => $fks[$table->primaryKey[1]][1]]);
            $relationName = $this->generateRelationName($relations, $className1, $db->getTableSchema($table1),
                $table->primaryKey[0], true);
            $relations[$className1][$relationName] = [
                "return \$this->hasMany($className0::className(), $link)->viaTable('" . $table->name . "', $viaLink);",
                $className0,
                true,
            ];
        }

        return $relations;
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

    /**
     * Check need use ImperaviWidget
     * @return bool
     */
    public function useImperavi()
    {
        foreach ($this->getTableSchema()->columns as $column) {
            if ($column->type === 'text') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check need use DatePicker
     * @return bool
     */
    public function useDatePicker()
    {

        foreach ($this->getTableSchema()->columns as $column) {
            if ($column->type === 'date') {
                return true;
            }
        }

        return false;
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
}
