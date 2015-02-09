<?php
/**
 * @var yii\web\View $this
 * @var Generator $generator
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use maxmirazh33\gii\generators\crud\Generator;

$localName = $generator->getLocalName(Generator::LOCAL_ADD);

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $model
 */

use yii\helpers\Html;

$this->title = '<?= $generator->generateString('Add') ?> <?= mb_strtolower($localName) ?> | <?= $generator->generateString('Control panel') ?> | ' . Yii::$app->name;
$this->params['breadcrumbs'][] = ['label' => '<?= $generator->getLocalName(Generator::LOCAL_INDEX) ?>', 'url' => ['index']];
$this->params['breadcrumbs'][] = '<?= $generator->generateString('Add') ?>';
$this->params['title'] = '<?= $generator->generateString('Add') ?> <?= mb_strtolower($localName) ?>';
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-create">

    <p class="clear">
        <?= "<?= Html::a('{$generator->generateString('All')} " . mb_strtolower($generator->getLocalName(Generator::LOCAL_INDEX)) . "', ['index'], ['class' => 'btn btn-info btn-right']) ?>\n" ?>
    </p>

    <?= "<?= " ?>$this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
