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
<?php if (count($relations) > 0 || count($manyManyRelations) > 0): ?>
use yii\helpers\Url;
<?php endif; ?>

$this->title = $model-><?= $generator->getNameAttribute() ?> . ' | <?= $localName ?> | <?= $generator->generateString('Control panel') ?> | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = ['label' => '<?= $localName ?>', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model-><?= $generator->getNameAttribute() ?>;
$this->params['title'] = "<?= $generator->getLocalName(Generator::LOCAL_VIEW) ?> <?= $generator->getNameAttribute() == 'id' ? '#$model->id' : "'\$model->" . $generator->getNameAttribute() . "'" ?>";
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-view">

    <p>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('Edit') ?>', ['update', <?= $urlParams ?>], ['class' => 'btn btn-primary']) ?>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('Delete') ?>', ['delete', <?= $urlParams ?>], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => '<?= $generator->generateString('Are you sure you want to delete this item?') ?>',
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
        $skip = false;
        foreach ($relations as $rel) {
            if ($rel['foreignKey'] == $column->name) {
                $lowerClass = mb_strtolower($rel['className']);
                echo "            ['attribute' => '$column->name', 'value' => Html::a(\$model->" . $lowerClass . "->{$rel['titleAttr']}, Url::toRoute(['/$lowerClass/view', 'id' => \$model->$column->name])), 'format' => 'raw'],\n";
                $skip = true;
                break;
            }
        }
        if (!$skip) {
            if ($generator->isImage($column->name)) {
                echo "            ['attribute' => '$column->name', 'value' => Html::img(\$model->getImageUrl('$column->name'), ['style' => 'max-height: 150px;']), 'format' => 'raw'],\n";
            } elseif ($generator->isFile($column->name)) {
                echo "            ['attribute' => '$column->name', 'value' => Html::a(\$model->$column->name, \$model->getFileUrl('$column->name')), 'format' => 'raw'],\n";
            } else {
                $format = $generator->generateColumnFormat($column);
                echo "            '" . $column->name . ($format === 'text' ? "" : ":" . $format) . "',\n";
            }
        }
    }
    foreach ($manyManyRelations as $rel) {
        $lowerClass = mb_strtolower($rel['className']);
        echo "            ['attribute' => '{$lowerClass}List', 'value' => implode('<br>', array_map(function (\$el) { return Html::a(\$el->{$rel['titleAttr']}, Url::toRoute(['/$lowerClass/view', 'id' => \$el->id])); }, \$model->get{$rel['relationName']}()->all())), 'format' => 'raw'],\n";
    }
}
?>
        ],
    ]) ?>

</div>
