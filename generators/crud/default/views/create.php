<?php
/**
 * @var yii\web\View $this
 * @var Generator $generator
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use maxmirazh33\gii\generators\crud\Generator;

$russianName = $generator->getRussianName(Generator::RUSSIAN_ADD);

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $model
 */

$this->title = 'Добавить <?= mb_strtolower($russianName) ?> | Панель управления | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = ['label' => '<?= $generator->getRussianName(Generator::RUSSIAN_INDEX) ?>', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Добавить';
$this->params['title'] = 'Добавить <?= mb_strtolower($russianName) ?>';
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-create">

    <?= "<?= " ?>$this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
