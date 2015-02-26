<?php
/**
 * This is the template for generating the model class of a specified table.
 */

/* @var $this yii\web\View */
/* @var $generator maxmirazh33\gii\generators\model\Generator */
/* @var $tableName string full table name */
/* @var $className string class name */
/* @var $tableSchema yii\db\TableSchema */
/* @var $labels string[] list of attribute labels (name => label) */
/* @var $rules string[] list of validation rules */
/* @var $relations array list of relations (name => relation declaration) */

use yii\helpers\StringHelper;

echo "<?php\n";
?>
namespace <?= $generator->ns ?>;

use Yii;
use <?= ltrim($generator->baseClass, '\\') ?>;
<?php if ($generator->useImageWidget()): ?>
use maxmirazh33\image\GetImageUrlTrait;
<?php endif; ?>
<?php if ($generator->useFileWidget()): ?>
use maxmirazh33\file\GetFileUrlTrait;
<?php endif; ?>

/**
 * This is the model class for table "<?= $generator->generateTableName($tableName) ?>".
 *
<?php foreach ($tableSchema->columns as $column): ?>
 * @property <?= "{$column->phpType} \${$column->name}\n" ?>
<?php endforeach; ?>
<?php if (!empty($relations)): ?>
 *
<?php foreach ($relations as $name => $relation): ?>
 * @property <?= $relation[1] . ($relation[2] ? '[]' : '') . ' $' . lcfirst($name) . "\n" ?>
<?php endforeach; ?>
<?php endif; ?>
 */
class <?= $className ?> extends <?= StringHelper::basename($generator->baseClass) . "\n" ?>
{
<?php if ($generator->useImageWidget()): ?>
    use GetImageUrlTrait;
<?php endif; ?>
<?php if ($generator->useFileWidget()): ?>
    use GetFileUrlTrait;
<?php endif; ?>
<?php if ($generator->useImageWidget() || $generator->useFileWidget()): ?>

<?php endif; ?>
<?php if ($generator->generateTableName($tableName) != $tableName): ?>
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '<?= $generator->generateTableName($tableName) ?>';
    }

<?php endif; ?>
<?php if ($generator->db !== 'db'): ?>
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('<?= $generator->db ?>');
    }

<?php endif; ?>
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
<?php foreach ($labels as $name => $label): ?>
            <?= "'$name' => " . $generator->generateString($label) . ",\n" ?>
<?php endforeach; ?>
        ];
    }
<?php foreach ($relations as $name => $relation): ?>

    /**
     * @return \yii\db\ActiveQuery
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] . "\n" ?>
    }
<?php endforeach; ?>
}
