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

$this->title = $model-><?= $generator->getNameAttribute() ?> . ' | <?= $localName ?> | <?= $generator->generateString('Control panel') ?> | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = ['label' => '<?= $localName ?>', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model-><?= $generator->getNameAttribute() ?>;
$this->params['title'] = "<?= $generator->generateString('Edit') ?> <?= mb_strtolower($generator->getLocalName(Generator::LOCAL_ADD)) ?> <?= $generator->getNameAttribute() == 'id' ? '#$model->id' : "'\$model->" . $generator->getNameAttribute() . "'" ?>";
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-update">

    <p>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('View') ?>', ['view', <?= $urlParams ?>], ['class' => 'btn btn-flat btn-primary glyphicon-eye-open']) ?>
        <?= "<?= " ?>Html::a(
            '<?= $generator->generateString('Delete') ?>',
            ['delete', <?= $urlParams ?>],
            [
                'class' => 'btn btn-flat btn-danger glyphicon-trash',
                'data' => [
                    'confirm' => '<?= $generator->generateString('Are you sure you want to delete this item?') ?>',
                    'method' => 'post',
                ],
            ]
        ) ?>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('All') ?> <?= mb_strtolower($localName) ?>', ['index'], ['class' => 'btn btn-flat btn-info btn-right glyphicon-list']) ?>
        <?= "<?= " ?>Html::a('<?= $generator->generateString('Add') ?>', ['create'], ['class' => 'btn btn-flat btn-success btn-right glyphicon-plus']) ?>
    </p>

    <?= "<?= " ?>$this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
