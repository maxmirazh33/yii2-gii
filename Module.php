<?php
namespace maxmirazh33\gii;

use Yii;
use yii\base\BootstrapInterface;

class Module extends \yii\gii\Module implements BootstrapInterface
{
    /**
     * Returns the list of the core code generator configurations.
     * @return array the list of the core code generator configurations.
     */
    protected function coreGenerators()
    {
        return [
            'model' => ['class' => 'yii\gii\generators\model\Generator'],
            'crud' => ['class' => 'maxmirazh33\gii\generators\crud\Generator'],
            'controller' => ['class' => 'yii\gii\generators\controller\Generator'],
            'form' => ['class' => 'yii\gii\generators\form\Generator'],
            'module' => ['class' => 'yii\gii\generators\module\Generator'],
            'extension' => ['class' => 'yii\gii\generators\extension\Generator'],
        ];
    }
}
