<?php
/**
 * @var yii\web\View $this
 * @var Generator $generator
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use maxmirazh33\gii\generators\crud\Generator;

$urlParams = $generator->generateUrlParams();
$nameAttribute = $generator->getNameAttribute();
$localName = $generator->getLocalName(Generator::LOCAL_INDEX);

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $model
 * @var $dataProvider yii\data\ActiveDataProvider
 */

use yii\helpers\Html;
use <?= $generator->indexWidgetType === 'grid' ? "yii\\grid\\GridView" : "yii\\widgets\\ListView" ?>;

$this->title = '<?= $localName ?> | <?= $generator->generateString('Control panel') ?> | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = '<?= $localName ?>';
$this->params['title'] = '<?= $localName ?>';
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">

    <p>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('Add') ?>', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= "<?= " ?>GridView::widget([
        'dataProvider' => $dataProvider,
        <?= !empty($generator->searchModelClass) ? "'filterModel' => \$model,\n        'columns' => [\n" : "'columns' => [\n"; ?>
            ['class' => 'yii\grid\SerialColumn'],
<?php
$count = 0;
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        if (++$count < 6) {
            echo "            '" . $name . "',\n";
        } else {
            echo "            //'" . $name . "',\n";
        }
    }
} else {
    foreach ($tableSchema->columns as $column) {
        $format = $generator->generateColumnFormat($column);
        if ($format === 'text') {
            $col = "'$column->name'";
        } elseif ($format === 'boolean') {
            $col = "['class' => 'yii\\grid\\DataColumn', 'attribute' => '$column->name', 'format' => 'boolean', 'filter' => [0 => '{$generator->generateString('No')}', 1 => '{$generator->generateString('Yes')}']]";
        } else {
            $col = "'$column->name:$format'";
        }
        if (++$count < 6 && $column->type !== 'text') {
            echo "            " . $col . ",\n";
        } else {
            if ($column->type === 'text') {
                $count--;
            }
            echo "            //" . $col . ",\n";
        }
    }
}
?>
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
