<?php
/**
 * @var yii\web\View $this
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $model \yii\db\ActiveRecord */
$model = new $generator->modelClass();
$safeAttributes = $model->safeAttributes();
if (empty($safeAttributes)) {
    $safeAttributes = $model->attributes();
}

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->searchModelClass, '\\') ?> $model
 * @var yii\widgets\ActiveForm $form
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
<?php if ($generator->useImperavi()): ?>
use backend\components\ImperaviWidget;
<?php endif; ?>
<?php if ($generator->useDatePicker()): ?>
use kartik\date\DatePicker;
<?php endif; ?>
?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-form">

    <?= "<?php " ?>$form = ActiveForm::begin(); ?>

<?php foreach ($generator->getColumnNames() as $attribute) {
    if (in_array($attribute, $safeAttributes)) {
        echo "    <?= " . $generator->generateActiveField($attribute) . " ?>\n\n";
    }
} ?>
    <div class="form-group">
        <?= "<?= " ?>Html::submitButton($model->isNewRecord ? <?= $generator->generateString('Добавить') ?> : <?= $generator->generateString('Сохранить') ?>, ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?= "<?php " ?>ActiveForm::end(); ?>

</div>
