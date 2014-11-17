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
$russianName = $generator->getRussianName(Generator::RUSSIAN_INDEX);

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $model
 * @var $dataProvider yii\data\ActiveDataProvider
 */

use yii\helpers\Html;
use <?= $generator->indexWidgetType === 'grid' ? "yii\\grid\\GridView" : "yii\\widgets\\ListView" ?>;

$this->title = '<?= $russianName ?> | Панель управления | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = '<?= $russianName ?>';
$this->params['title'] = '<?= $russianName ?>';
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">

    <p>
        <?= "<?= " ?>Html::a('Добавить', ['create'], ['class' => 'btn btn-success']) ?>
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
            echo "                '" . $name . "',\n";
        } else {
            echo "                // '" . $name . "',\n";
        }
    }
} else {
    foreach ($tableSchema->columns as $column) {
        $format = $generator->generateColumnFormat($column);
        if (++$count < 6) {
            echo "                '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
        } else {
            echo "                // '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
        }
    }
}
?>
                ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
