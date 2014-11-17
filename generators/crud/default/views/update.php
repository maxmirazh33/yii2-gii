<?php
/**
 * @var yii\web\View $this
 * @var Generator $generator
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use maxmirazh33\gii\generators\crud\Generator;

$urlParams = $generator->generateUrlParams();
$russianName = $generator->getRussianName(Generator::RUSSIAN_INDEX);

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $model
 */

$this->title = $model-><?= $generator->getNameAttribute() ?> . ' | <?= $russianName ?> | Панель управления | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = ['label' => '<?= $russianName ?>', 'url' => ['index']];
$this->params['breadcrumbs'][] = $model-><?= $generator->getNameAttribute() ?>;
$this->params['title'] = "Редактировать <?= mb_strtolower($generator->getRussianName(Generator::RUSSIAN_VIEW)) ?> <?= $generator->getNameAttribute() == 'id' ? '#$model->id' : "'\$model->" . $generator->getNameAttribute() . "'" ?>";
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-update">

    <?= "<?= " ?>$this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
