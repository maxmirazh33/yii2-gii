<?php
/**
 * This is the template for generating CRUD search class of the specified model.
 *
 * @var yii\web\View $this
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

use yii\helpers\StringHelper;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
$searchRules = $generator->generateSearchRules();
$searchConditions = $generator->generateSearchConditions();
$relations = $generator->generateRelations();

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->searchModelClass, '\\')) ?>;

use Yii;
use yii\data\ActiveDataProvider;
<?php if (count($relations) > 0): ?>
use yii\helpers\ArrayHelper;
<?php endif; ?>
<?php foreach ($relations as $rel): ?>
use common\models\<?=$rel['className'] ?>;
<?php endforeach; ?>

class <?= $searchModelClass ?> extends \<?= ltrim($generator->modelClass, '\\') . "\n" ?>
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            <?= implode(",\n            ", $searchRules) ?>,
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
<?php
    $join = '';
    if ($relations > 0) {
        $join = '->joinWith([';
        $relByClass = ArrayHelper::getColumn($relations, 'className');
        foreach ($relByClass as &$rel) {
            $rel = "'" . mb_strtolower($rel) . "'";
        }
        $join .= implode(', ', $relByClass);
        $join .= '])';
    }
?>
        $query = static::find()<?= $join ?>;

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
<?php foreach ($relations as $rel): ?>

        $dataProvider->sort->attributes['<?= $rel['foreignKey'] ?>'] = [
            'asc' => ['<?= mb_strtolower($rel['className']) ?>.<?= $rel['titleAttr'] ?>' => SORT_ASC],
            'desc' => ['<?= mb_strtolower($rel['className']) ?>.<?= $rel['titleAttr'] ?>' => SORT_DESC],
        ];
<?php endforeach; ?>

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        <?= implode("\n        ", $searchConditions) ?>

        return $dataProvider;
    }
<?php foreach ($generator->getTableSchema()->columns as $column): ?>
<?php if (is_array($column->enumValues) && count($column->enumValues) > 0): ?>

    /**
     * @return array enum values for <?= $column->name ?> attribute
     */
    public function get<?= Inflector::humanize(Inflector::variablize($column->name)) ?>Enums()
    {
        $enums = static::getTableSchema()->getColumn('<?= $column->name ?>')->enumValues;
        return array_combine($enums, $enums);
    }
<?php endif; ?>
<?php endforeach; ?>
<?php foreach ($relations as $rel): ?>

    /**
     * @return array as <?= $rel['idAttr'] ?> => <?= $rel['titleAttr'] ?> for <?= $rel['className'] ?> relation models
     */
    public static function get<?= $rel['relationName'] ?>ForDropdown()
    {
        return ArrayHelper::map(<?= $rel['className'] ?>::find()->all(), '<?= $rel['idAttr'] ?>', '<?= $rel['titleAttr'] ?>');
    }
<?php endforeach; ?>
}
