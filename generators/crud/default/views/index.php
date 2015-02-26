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
$relations = $generator->generateRelations();

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $searchModel
 * @var $dataProvider yii\data\ActiveDataProvider
 */

use yii\helpers\Html;
use <?= $generator->indexWidgetType === 'grid' ? "yii\\grid\\GridView" : "yii\\widgets\\ListView" ?>;
<?php if ($generator->useDatePicker()): ?>
use kartik\date\DatePicker;
<?php endif; ?>
<?php if (count($relations) > 0): ?>
use yii\helpers\Url;
<?php endif; ?>

$this->title = '<?= $localName ?> | <?= $generator->generateString('Control panel') ?> | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = '<?= $localName ?>';
$this->params['title'] = '<?= $localName ?>';
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-index">

    <p>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('Add') ?>', ['create'], ['class' => 'btn btn-flat btn-success glyphicon-plus']) ?>
    </p>

    <div class="box box-primary">

        <?= "<?= " ?>GridView::widget([
            'dataProvider' => $dataProvider,
            <?= !empty($generator->searchModelClass) ? "'filterModel' => \$searchModel,\n            'layout' => \"{items}\\n<div class='row'>{summary}{pager}</div>\",\n            'columns' => [\n" : "'columns' => [\n"; ?>
                ['class' => 'yii\grid\SerialColumn'],
<?php
$count = 0;
$tabs = '                ';
if (($tableSchema = $generator->getTableSchema()) === false) {
    foreach ($generator->getColumnNames() as $name) {
        if (++$count < 6) {
            echo "$tabs'" . $name . "',\n";
        } else {
            echo "$tabs//'" . $name . "',\n";
        }
    }
} else {
    foreach ($generator->getTableSchema()->columns as $column) {
        $skip = false;
        $manyRows = true;
        foreach ($relations as $rel) {
            if ($rel['foreignKey'] == $column->name) {
                $lowerClass = mb_strtolower($rel['className']);
                $col = "[\n$tabs    'class' => 'yii\\grid\\DataColumn',\n$tabs    'attribute' => '$column->name',\n$tabs    'value' => function (\$model) {\n$tabs        return Html::a(\$model->" . $lowerClass . "->{$rel['titleAttr']}, Url::toRoute(['/$lowerClass/view', 'id' => \$model->$column->name]));\n$tabs    },\n$tabs    'filter' => \$searchModel->get{$rel['relationName']}ForDropdown(),\n$tabs    'format' => 'raw',\n$tabs],";
                $skip = true;
                break;
            }
        }
        if (!$skip) {
            if ($generator->isImage($column->name)) {
                $col = "[\n$tabs    'class' => 'yii\\grid\\DataColumn',\n$tabs    'attribute' => '$column->name',\n$tabs    'value' => function (\$model) {\n$tabs        return Html::img(\$model->getImageUrl('$column->name'));\n$tabs    },\n$tabs    'format' => 'raw',\n$tabs],";
            } elseif ($generator->isFile($column->name)) {
                $col = "[\n$tabs    'class' => 'yii\\grid\\DataColumn',\n$tabs    'attribute' => '$column->name',\n$tabs    'value' => function (\$model) {\n$tabs        return Html::a(\$model->$column->name, \$model->getFileUrl('$column->name'));\n$tabs    },\n$tabs    'format' => 'raw',\n$tabs],";
            } else {
                $format = $generator->generateColumnFormat($column);
                if ($format == 'date') {
                    $col = "[\n$tabs    'class' => 'yii\\grid\\DataColumn',\n$tabs    'attribute' => '$column->name',\n$tabs    'filter' => DatePicker::widget(['model' => \$searchModel, 'attribute' => '$column->name', 'pluginOptions' => ['format' => 'yyyy-mm-dd', 'weekStart' => 1, 'autoclose' => true]]),\n$tabs    'format' => 'date',\n$tabs],";
                } elseif ($format == 'boolean') {
                    $col = "[\n$tabs    'class' => 'yii\\grid\\DataColumn',\n$tabs    'attribute' => '$column->name',\n$tabs    'filter' => [0 => '{$generator->generateString('No')}', 1 => '{$generator->generateString('Yes')}'],\n$tabs    'format' => 'boolean',\n$tabs],";
                } elseif ($generator->isEnum($column)) {
                    $col = "[\n$tabs    'class' => 'yii\\grid\\DataColumn',\n$tabs    'attribute' => '$column->name',\n$tabs    'filter' => \$searchModel->get" . Inflector::humanize(Inflector::variablize($column->name)) . "Enums(),\n$tabs],";
                } elseif ($format === 'text') {
                    $col = "'$column->name',";
                    $manyRows = false;
                } else {
                    $col = "'$column->name:$format',";
                    $manyRows = false;
                }
            }
        }
        if (++$count < 6 && $column->type !== 'text') {
            echo $tabs . $col . "\n";
        } else {
            if ($column->type === 'text') {
                $count--;
            }
            if ($manyRows) {
                echo "$tabs/*$col*/\n";
            } else {
                echo "$tabs//$col\n";
            }
        }
    }
}
?>
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

    </div>

</div>
