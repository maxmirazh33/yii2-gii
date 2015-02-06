<?php
namespace maxmirazh33\gii;

use Yii;
use yii\base\BootstrapInterface;

class Module extends \yii\gii\Module implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    protected function coreGenerators()
    {
        return [
            'model' => ['class' => 'maxmirazh33\gii\generators\model\Generator'],
            'crud' => ['class' => 'maxmirazh33\gii\generators\crud\Generator'],
            'controller' => ['class' => 'yii\gii\generators\controller\Generator'],
            'form' => ['class' => 'yii\gii\generators\form\Generator'],
            'module' => ['class' => 'yii\gii\generators\module\Generator'],
            'extension' => ['class' => 'yii\gii\generators\extension\Generator'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getViewPath()
    {
        return Yii::getAlias('@yii/gii') . DIRECTORY_SEPARATOR . 'views';
    }
}
