<?php
namespace maxmirazh33\gii\generators\crud;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ColumnSchema;
use yii\db\mysql\Schema;
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
     * @param int $plural required plural
     * @return string russian name
     */
    public function getRussianName($plural = self::RUSSIAN_VIEW)
    {
        $names = explode(' ', $this->russianNames);
        if (isset($names[$plural])) {
            return $names[$plural];
        } else {
            return $this->generateString(Inflector::pluralize(Inflector::camel2words(StringHelper::basename($this->modelClass))));
        }
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['russianNames'], 'safe'],
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
            'russianNames' => 'Russian Model Names'
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
            if (!$column->allowNull && $column->defaultValue === null) {
                $types['required'][] = $column->name;
            }
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
                    if ($column->size > 0) {
                        $lengths[$column->size][] = $column->name;
                    } else {
                        $types['string'][] = $column->name;
                    }
            }
        }
        $rules = [];
        foreach ($types as $type => $columns) {
            if ($type == 'date') {
                $rules[] = "[['" . implode("', '",
                        $columns) . "'], '$type', 'format' => 'php:Y-m-d', 'except' => ['search']]";
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
     * @return string the action view file path
     */
    public function getViewPath()
    {
        return Yii::getAlias('@backend') . '/views/' . $this->getControllerID();
    }

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
     * Generates code for active field
     * @param string $attribute
     * @return string
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
        if ($column->phpType === 'boolean' || $column->dbType === 'tinyint(1)') {
            return "\$form->field(\$model, '$attribute')->checkbox()";
        } elseif ($column->type === 'text') {
            return "\$form->field(\$model, '$attribute')->widget(ImperaviWidget::className())";
        } elseif ($this->useDatePicker($column) ) {
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
     * Generates code for active search field
     * @param string $attribute
     * @return string
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
     * Generates column format
     * @param \yii\db\ColumnSchema $column
     * @return string
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
     * Check need use ImperaviWidget
     * @return bool
     */
    public function useImperavi()
    {
        $tableSchema = $this->getTableSchema();
        foreach ($tableSchema->columns as $column) {
            if ($column->type === 'text') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check need use DatePicker
     * @param ColumnSchema|null $attribute
     * @return bool
     */
    public function useDatePicker($attribute = null){
        $use = function ($attrs) {
            foreach ($attrs as $attr) {
                if (
                    stripos($attr->name, 'date') !== false
                    || stripos($attr->name, 'created') !== false
                    || stripos($attr->name, 'updated') !== false
                ) {
                    return true;
                }
            }
            return false;
        };

        if ($attribute !== null) {
            return $use([$attribute]);
        } else {
            return $use($this->getTableSchema()->columns);
        }
    }
}
