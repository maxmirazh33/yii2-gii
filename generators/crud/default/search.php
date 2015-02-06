<?php
/**
 * This is the template for generating CRUD search class of the specified model.
 *
 * @var yii\web\View $this
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

use yii\helpers\StringHelper;
use yii\helpers\ArrayHelper;

$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
    $modelAlias = $modelClass . 'Model';
}
$searchRules = $generator->generateSearchRules();
$rules = $generator->generateRules();
$searchConditions = $generator->generateSearchConditions();
$relations = $generator->generateRelations();
$manyManyRelations = $generator->generateManyManyRelations();
$issetManyMany = $generator->issetManyMany();

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->searchModelClass, '\\')) ?>;

use Yii;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\base\Model;
<?php if ($generator->useImageWidget()): ?>
use maxmirazh33\image\Behavior as ImageBehavior;
<?php endif; ?>
<?php foreach ($relations as $rel): ?>
use common\models\<?= $rel['className'] ?>;
<?php endforeach; ?>
<?php if ($issetManyMany): ?>
<?php foreach ($generator->generateManyManyRelations() as $rel): ?>
use common\models\<?= $rel['className'] ?>;
<?php endforeach; ?>
use voskobovich\behaviors\ManyToManyBehavior;
<?php endif; ?>

class <?= $searchModelClass ?> extends \<?= $generator->modelClass . "\n" ?>
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            <?= implode(",\n            ", $rules) ?>,

            <?= implode(",\n            ", $searchRules) ?>,
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return ArrayHelper::merge(
            Model::scenarios(),
            ['search' => ['<?= implode("', '", $generator->getColumnNames()) ?>']]
        );
    }

<?php if ($generator->useBehaviors()): ?>
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
<?php if ($generator->useImageWidget()): ?>
            [
                'class' => ImageBehavior::className(),
                'attributes' => [
<?php foreach ($generator->getTableSchema()->columnNames as $column): ?>
<?php if ($generator->isImage($column)): ?>
                    '<?= $column ?>' => [],
<?php endif; ?>
<?php endforeach; ?>
                ],
            ],
<?php endif; ?>
<?php if ($issetManyMany): ?>
            [
                'class' => ManyToManyBehavior::className(),
                'relations' => [
<?php foreach ($manyManyRelations as $rel): ?>
                    '<?= mb_strtolower($rel['className']) ?>_list' => '<?= mb_strtolower($rel['relationName']) ?>',
<?php endforeach; ?>
                ],
            ],
<?php endif; ?>
        ];
    }
<?php endif; ?>

<?php if ($issetManyMany): ?>
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ArrayHelper::merge(
            parent::attributeLabels(),
            [
<?php foreach ($manyManyRelations as $rel): ?>
                '<?= mb_strtolower($rel['className']) . '_list' ?>' => '<?= $rel['relationName'] ?>',
<?php endforeach; ?>
            ]
        );
    }
<?php endif; ?>

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = static::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        <?= implode("\n        ", $searchConditions) ?>

        return $dataProvider;
    }
<?php foreach (ArrayHelper::merge($relations, $manyManyRelations) as $rel): ?>

    /**
    * @return array as idAttribute => titleAttribute for <?= $rel['className'] ?> relation models
    */
    public static function get<?= $rel['relationName'] ?>ForDropdown()
    {
        return ArrayHelper::map(<?= $rel['className'] ?>::find()->all(), '<?= $rel['idAttr'] ?>', '<?= $rel['titleAttr'] ?>');
    }
<?php endforeach; ?>
}
