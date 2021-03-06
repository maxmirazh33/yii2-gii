<?php
/**
 * @var yii\web\View $this
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $model yii\db\ActiveRecord */
$model = new $generator->modelClass();
$safeAttributes = $model->safeAttributes();
if (empty($safeAttributes)) {
    $safeAttributes = $model->attributes();
}

echo "<?php\n";
?>
/**
 * @var yii\web\View $this
 * @var <?= ltrim($generator->backendModelClass, '\\') ?> $model
 * @var yii\widgets\ActiveForm $form
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
<?php if ($generator->useImperavi()): ?>
use backend\widgets\ImperaviWidget;
<?php endif; ?>
<?php if ($generator->useDatePicker()): ?>
use kartik\date\DatePicker;
<?php endif; ?>
<?php if ($generator->useTimePicker()): ?>
use kartik\time\TimePicker;
<?php endif; ?>
<?php if ($generator->useDateTimePicker()): ?>
use kartik\datetime\DateTimePicker;
<?php endif; ?>
<?php if ($generator->useImageWidget()): ?>
use maxmirazh33\image\Widget as ImageWidget;
<?php endif; ?>
<?php if ($generator->issetFiles()): ?>
use maxmirazh33\file\Widget as FileWidget;
<?php endif; ?>

?>

<div class="<?= Inflector::camel2id(StringHelper::basename($generator->modelClass)) ?>-form">

    <?= "<?php " ?>$form = ActiveForm::begin(<?= $generator->issetFiles() ? "['options' => ['enctype' => 'multipart/form-data']]" : '' ?>); ?>

    <div class="errorSummary">
        <?= "<?= \$form->errorSummary([\$model]) ?>\n" ?>
    </div>

<?php foreach ($generator->getTableSchema()->columns as $column) {
    if (in_array($column->name, $safeAttributes) && !$column->autoIncrement) {
        echo "    <?= " . $generator->generateActiveField($column->name) . " ?>\n\n";
    }
} ?>
<?php foreach ($generator->generateManyManyRelations() as $rel) {
        echo "    <?= \$form->field(\$model, '" . mb_strtolower($rel['className']) . "List')->dropDownList(\$model->get{$rel['relationName']}ForDropdown(), ['multiple' => true]) ?>\n\n";
} ?>
    <div class="form-group">
        <?= "<?= " ?>Html::submitButton($model->isNewRecord ? '<?= $generator->generateString('Add') ?>' : '<?= $generator->generateString('Save') ?>', ['class' => $model->isNewRecord ? 'btn btn-flat btn-success glyphicon-ok' : 'btn btn-flat btn-primary glyphicon-ok']) ?>
    </div>

    <?= "<?php " ?>ActiveForm::end(); ?>

</div>
