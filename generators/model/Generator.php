<?php
namespace maxmirazh33\gii\generators\model;

/**
 * @inheritdoc
 */
class Generator extends \yii\gii\generators\model\Generator
{
    /**
     * @var bool
     */
    public $generatePivotTablesRelations = false;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['generatePivotTablesRelations'], 'boolean'],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'generatePivotTablesRelations' => 'Generate relations for pivot tables',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'generatePivotTablesRelations' => 'Generate relations for pivot tables',
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function generateRelations()
    {
        if (!$this->generateRelations) {
            return [];
        }

        $db = $this->getDbConnection();

        if (($pos = strpos($this->tableName, '.')) !== false) {
            $schemaName = substr($this->tableName, 0, $pos);
        } else {
            $schemaName = '';
        }

        $relations = [];
        foreach ($db->getSchema()->getTableSchemas($schemaName) as $table) {
            $tableName = $table->name;
            $className = $this->generateClassName($tableName);
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
                    "return \$this->hasMany($className1::className(), $link)->viaTable('" . $this->generateTableName($table->name) . "', $viaLink);",
                    $className1,
                    true,
                ];

                $link = $this->generateRelationLink([$fks[$table->primaryKey[0]][1] => $table->primaryKey[0]]);
                $viaLink = $this->generateRelationLink([$table->primaryKey[1] => $fks[$table->primaryKey[1]][1]]);
                $relationName = $this->generateRelationName($relations, $className1, $db->getTableSchema($table1),
                    $table->primaryKey[0], true);
                $relations[$className1][$relationName] = [
                    "return \$this->hasMany($className0::className(), $link)->viaTable('" . $this->generateTableName($table->name) . "', $viaLink);",
                    $className0,
                    true,
                ];
            }

            if ($fks === false || $this->generatePivotTablesRelations) {
                foreach ($table->foreignKeys as $refs) {
                    $refTable = $refs[0];
                    unset($refs[0]);
                    $fks = array_keys($refs);
                    $refClassName = $this->generateClassName($refTable);

                    // Add relation for this table
                    $link = $this->generateRelationLink(array_flip($refs));
                    $relationName = $this->generateRelationName($relations, $className, $table, $fks[0], false);
                    $relations[$className][$relationName] = [
                        "return \$this->hasOne($refClassName::className(), $link);",
                        $refClassName,
                        false,
                    ];

                    // Add relation for the referenced table
                    $hasMany = false;
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
                    $link = $this->generateRelationLink($refs);
                    $relationName = $this->generateRelationName($relations, $refClassName, $refTable, $className,
                        $hasMany);
                    $relations[$refClassName][$relationName] = [
                        "return \$this->" . ($hasMany ? 'hasMany' : 'hasOne') . "($className::className(), $link);",
                        $className,
                        $hasMany,
                    ];
                }
            }
        }

        return $relations;
    }
}
