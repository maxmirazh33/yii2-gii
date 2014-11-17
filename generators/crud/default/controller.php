<?php
/**
 * This is the template for generating a CRUD controller class file.
 *
 * @var yii\web\View $this
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

use yii\helpers\StringHelper;

$controllerClass = StringHelper::basename($generator->controllerClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->controllerClass, '\\')) ?>;

use <?= ltrim($generator->baseControllerClass, '\\') ?>;

/**
 * <?= $controllerClass ?> implements the CRUD actions for <?= $searchModelClass ?> model.
 */
class <?= $controllerClass ?> extends <?= StringHelper::basename($generator->baseControllerClass) . "\n" ?>
{
    /**
    * @var \<?= ltrim($generator->searchModelClass, '\\') ?> name of model class for this controller
    */
    public $modelClass = '\<?= ltrim($generator->searchModelClass, '\\') ?>';
}
