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
$relations = $generator->generateRelations();
$manyManyRelations = $generator->generateManyManyRelations();

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;
<?php if (count($relations) > 0): ?>
use yii\helpers\Url;
<?php endif; ?>
<?php if (count($manyManyRelations) > 0): ?>
use yii\helpers\ArrayHelper;
<?php endif; ?>

$this->title = $model-><?= $generator->getNameAttribute() ?> . ' | <?= $localName ?> | <?= $generator->generateString('Control panel') ?> | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = ['label' => '<?= $localName ?>', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model-><?= $generator->getNameAttribute() ?>;
$this->params['title'] = "<?= $generator->getLocalName(Generator::LOCAL_VIEW) ?> <?= $generator->getNameAttribute() == 'id' ? '#$model->id' : "'\$model->" . $generator->getNameAttribute() . "'" ?>";
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-view">

    <p>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('Edit') ?>', ['update', <?= $urlParams ?>], ['class' => 'btn btn-primary glyphicon-pencil']) ?>
        <?= "<?= " ?>Html::a(
            '<?= $generator->generateString('Delete') ?>',
            ['delete', <?= $urlParams ?>],
            [
                'class' => 'btn btn-danger glyphicon-trash',
                'data' => [
                    'confirm' => '<?= $generator->generateString('Are you sure you want to delete this item?') ?>',
                    'method' => 'post',
                ],
            ]
        ) ?>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('All') ?> <?= mb_strtolower($localName) ?>', ['index'], ['class' => 'btn btn-info btn-right glyphicon-list']) ?>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('Add') ?>', ['create'], ['class' => 'btn btn-success btn-right glyphicon-plus']) ?>
    </p>

<?php $tabs = '            ' ?>
    <?= "<?= " ?>DetailView::widget([
        'model' => $model,
<?php if (count($manyManyRelations) > 0): ?>
        'attributes' => ArrayHelper::merge(
            [
<?php $tabs = '                '; ?>
<?php else: ?>
        'attributes' => [
<?php endif; ?>
<?php
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        echo $tabs . "'" . $name . "',\n";
    }
} else {
    foreach ($generator->getTableSchema()->columns as $column) {
        $skip = false;
        foreach ($relations as $rel) {
            if ($rel['foreignKey'] == $column->name) {
                $lowerClass = mb_strtolower($rel['className']);
                echo "{$tabs}[\n$tabs    'attribute' => '$column->name',\n$tabs    'value' => Html::a(\$model->" . $lowerClass . "->{$rel['titleAttr']}, Url::toRoute(['/$lowerClass/view', 'id' => \$model->$column->name])),\n$tabs    'format' => 'raw',\n$tabs],\n";
                $skip = true;
                break;
            }
        }
        if (!$skip) {
            if ($generator->isImage($column->name)) {
                echo "{$tabs}[\n$tabs    'attribute' => '$column->name',\n$tabs    'value' => Html::img(\$model->getImageUrl('$column->name')),\n$tabs    'format' => 'raw',\n$tabs],\n";
            } elseif ($generator->isFile($column->name)) {
                echo "{$tabs}[\n$tabs    'attribute' => '$column->name',\n$tabs    'value' => Html::a(\$model->$column->name, \$model->getFileUrl('$column->name')),\n$tabs    'format' => 'raw',\n$tabs],\n";
            } else {
                $format = $generator->generateColumnFormat($column);
                echo $tabs . "'" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
            }
        }
    }
    if (count($manyManyRelations) > 0) {
        echo '            ]';
        foreach ($manyManyRelations as $rel) {
            echo ",\n            \$model->get{$rel['relationName']}ForDetailView()";
        }
        echo "\n        ),\n";
    }
}
?>
<?php if (count($manyManyRelations) == 0): ?>
        ],
<?php endif; ?>
    ]) ?>

</div>
