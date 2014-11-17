<?php
/**
 * This is the template for generating CRUD search class of the specified model.
 *
 * @var yii\web\View $this
 * @var maxmirazh33\gii\generators\crud\Generator $generator
 */

use yii\helpers\StringHelper;

$modelClass = StringHelper::basename($generator->modelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
    $modelAlias = $modelClass . 'Model';
}
$searchRules = $generator->generateSearchRules();
$rules = $generator->generateRules();
$searchConditions = $generator->generateSearchConditions();

echo "<?php\n";
?>
namespace <?= StringHelper::dirname(ltrim($generator->searchModelClass, '\\')) ?>;

use Yii;
use yii\helpers\ArrayHelper;
use yii\data\ActiveDataProvider;
use yii\base\Model;

class <?= $searchModelClass ?> extends \<?= $generator->modelClass . "\n" ?>
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            <?= implode(",\n            ", $rules) ?>,

            <?= implode(",\n            ", $searchRules) ?>,
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return ArrayHelper::merge(
            Model::scenarios(),
            ['search' => ['<?= implode("', '", $generator->getColumnNames()) ?>'],]
        );
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = static::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        <?= implode("\n        ", $searchConditions) ?>

        return $dataProvider;
    }
}
