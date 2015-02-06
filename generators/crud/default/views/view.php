<?php
/**
 * @var yii\web\View $this
 * @var Generator $generator
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use maxmirazh33\gii\generators\crud\Generator;

$urlParams = $generator->generateUrlParams();
$localName = $generator->getLocalName(Generator::LOCAL_INDEX);

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model-><?= $generator->getNameAttribute() ?> . ' | <?= $localName ?> | Панель управления | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = ['label' => '<?= $localName ?>', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model-><?= $generator->getNameAttribute() ?>;
$this->params['title'] = "<?= $generator->getLocalName(Generator::LOCAL_VIEW) ?> <?= $generator->getNameAttribute() == 'id' ? '#$model->id' : "'\$model->" . $generator->getNameAttribute() . "'" ?>";
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-view">

    <p>
        <?= "<?= " ?>Html::a(<?= $generator->generateString('Редактировать') ?>, ['update', <?= $urlParams ?>], ['class' => 'btn btn-primary']) ?>
        <?= "<?= " ?>Html::a(<?= $generator->generateString('Удалить') ?>, ['delete', <?= $urlParams ?>], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => <?= $generator->generateString('Вы уверены, что хотите удалить этот элемент?') ?>,
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= "<?= " ?>DetailView::widget([
        'model' => $model,
        'attributes' => [
<?php
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        echo "            '" . $name . "',\n";
    }
} else {
    foreach ($generator->getTableSchema()->columns as $column) {
        $format = $generator->generateColumnFormat($column);
        echo "            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
    }
}
?>
        ],
    ]) ?>

</div>
