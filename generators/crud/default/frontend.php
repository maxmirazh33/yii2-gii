<?php
/**
 * This is the template for generating frontend model class of the specified model.
 *
 * @var yii\web\View $this
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

use yii\helpers\StringHelper;

$modelClass = StringHelper::basename($generator->modelClass);
$frontendModelClass = StringHelper::basename($generator->frontendModelClass);

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->frontendModelClass, '\\')) ?>;

class <?= $frontendModelClass ?> extends \<?= ltrim($generator->modelClass, '\\') . "\n" ?>
{
}
