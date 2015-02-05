<?php
/**
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'searchModelClass');
echo $form->field($generator, 'controllerClass');
echo $form->field($generator, 'viewPath');
echo $form->field($generator, 'russianNames');
echo $form->field($generator, 'baseControllerClass');
echo $form->field($generator, 'addInMenu')->checkbox();
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');
