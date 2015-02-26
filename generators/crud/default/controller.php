<?php
/**
 * This is the template for generating a CRUD controller class file.
 *
 * @var yii\web\View $this
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

use yii\helpers\StringHelper;

$controllerClass = StringHelper::basename($generator->controllerClass);
$backendModelClass = StringHelper::basename($generator->backendModelClass);

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->controllerClass, '\\')) ?>;

use <?= ltrim($generator->baseControllerClass, '\\') ?>;

/**
 * <?= $controllerClass ?> implements the CRUD actions for <?= $backendModelClass ?> model.
 */
class <?= $controllerClass ?> extends <?= StringHelper::basename($generator->baseControllerClass) . "\n" ?>
{
}
