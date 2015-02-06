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

$this->title = $model-><?= $generator->getNameAttribute() ?> . ' | <?= $localName ?> | Панель управления | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = ['label' => '<?= $localName ?>', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model-><?= $generator->getNameAttribute() ?>;
$this->params['title'] = "Редактировать <?= mb_strtolower($generator->getLocalName(Generator::LOCAL_VIEW)) ?> <?= $generator->getNameAttribute() == 'id' ? '#$model->id' : "'\$model->" . $generator->getNameAttribute() . "'" ?>";
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-update">

    <?= "<?= " ?>$this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
