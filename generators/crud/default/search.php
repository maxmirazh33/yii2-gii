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
if ($modelClass === $searchModelClass) {
    $modelAlias = $modelClass . 'Model';
}
$searchRules = $generator->generateSearchRules();
$rules = $generator->generateRules();
$searchConditions = $generator->generateSearchConditions();
$relations = $generator->generateRelations();
$manyManyRelations = $generator->generateManyManyRelations();
$issetManyMany = $generator->issetManyMany();
$manyManyProperties = [];
foreach ($manyManyRelations as $rel) {
    $manyManyProperties[mb_strtolower($rel['className']) . 'List'] = $rel['relationName'];
}

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
<?php if ($generator->issetFiles()): ?>
use maxmirazh33\file\Behavior as FileBehavior;
<?php endif; ?>
<?php foreach ($relations as $rel): ?>
use common\models\<?= $rel['className'] ?>;
<?php endforeach; ?>
<?php if ($issetManyMany): ?>
<?php foreach ($generator->generateManyManyRelations() as $rel): ?>
use common\models\<?= $rel['className'] ?>;
<?php endforeach; ?>
use voskobovich\behaviors\ManyToManyBehavior;
use yii\helpers\Url;
use yii\helpers\Html;
<?php endif; ?>

class <?= $searchModelClass ?> extends \<?= $generator->modelClass . "\n" ?>
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->scenario != 'search') {
<?php foreach ($generator->getTableSchema()->columns as $column): ?>
<?php if ($column->dbType == 'date'): ?>
            $this-><?= $column->name ?> = date('Y-m-d');
<?php elseif ($column->dbType == 'time'): ?>
            $this-><?= $column->name ?> = date('H:i:s');
<?php elseif ($column->dbType == 'datetime' || $column->dbType == 'timestamp'): ?>
            $this-><?= $column->name ?> = date('Y-m-d H:i:s');
<?php elseif ($column->dbType == 'year(4)'): ?>
            $this-><?= $column->name ?> = date('Y');
<?php endif; ?>
<?php endforeach; ?>
            $this->loadDefaultValues();
        }
    }

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
            ['search' => ['<?= implode("', '", ArrayHelper::merge($generator->getColumnNames(), array_keys($manyManyProperties))) ?>']],
            ['update' => ['<?= implode("', '", ArrayHelper::merge($generator->getColumnNames(), array_keys($manyManyProperties))) ?>']]
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
                    '<?= $column ?>',
<?php endif; ?>
<?php endforeach; ?>
                ],
            ],
<?php endif; ?>
<?php if ($generator->issetFiles()): ?>
            [
                'class' => FileBehavior::className(),
                'attributes' => [
<?php foreach ($generator->getTableSchema()->columnNames as $column): ?>
<?php if ($generator->isFile($column)): ?>
                    '<?= $column ?>',
<?php endif; ?>
<?php endforeach; ?>
                ],
            ],
<?php endif; ?>
<?php if ($issetManyMany): ?>
            [
                'class' => ManyToManyBehavior::className(),
                'relations' => [
<?php foreach ($manyManyProperties as $prop => $rel): ?>
                    '<?= $prop ?>' => '<?= mb_strtolower($rel) ?>',
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
<?php foreach ($manyManyProperties as $prop => $rel): ?>
                '<?= $prop ?>' => '<?= $rel ?>',
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
<?php
    $join = '';
    if ($relations > 0) {
        $join = '->joinWith([';
        foreach ($relations as $rel) {
            $join .= "'" . mb_strtolower($rel['className']) . "',";
        }
        $join .= ']);';
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
<?php foreach ($manyManyRelations as $rel): ?>

    /**
     * @return array many-many attribute for <?= $rel['className'] ?> models for DetailView widget
     */
    public function get<?= $rel['relationName'] ?>ForDetailView()
    {
        if ($this->get<?= $rel['relationName'] ?>()->count() <= 15) {
            return [
                [
                    'attribute' => '<?= $rel['className'] ?>List',
                    'value' => implode('<br>', array_map(function ($el) {
                        return Html::a($el->name, Url::toRoute(['/<?= mb_strtolower($rel['className']) ?>/view', 'id' => $el->id])); }, $this->get<?= $rel['relationName'] ?>()->all())
                    ),
                    'format' => 'raw',
                ],
            ];
        }
        return [];
    }
<?php endforeach; ?>
<?php foreach (ArrayHelper::merge($relations, $manyManyRelations) as $rel): ?>

    /**
     * @return array as <?= $rel['idAttr'] ?> => <?= $rel['titleAttr'] ?> for <?= $rel['className'] ?> relation models
     */
    public static function get<?= $rel['relationName'] ?>ForDropdown()
    {
        return ArrayHelper::map(<?= $rel['className'] ?>::find()->all(), '<?= $rel['idAttr'] ?>', '<?= $rel['titleAttr'] ?>');
    }
<?php endforeach; ?>
}
