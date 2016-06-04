<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Tag;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;


/**
 * @var yii\web\View $this
 * @var wolfguard\video_gallery\models\VideoGallery $video_gallery
 * @var wolfguard\video_gallery\models\VideoGalleryItem $model
 */

$this->title = Yii::t('video_gallery', 'Update video');
$this->params['breadcrumbs'][] = ['label' => Yii::t('video_gallery', 'Galleries'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $video_gallery->name, 'url' => ['update', 'id' => $video_gallery->id]];
$this->params['breadcrumbs'][] = ['label' => Yii::t('video_gallery', 'Videos'), 'url' => ['videos', 'id' => $video_gallery->id]];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= $this->title . ': ' . Html::encode($model->id) ?></h1>

<?php echo $this->render('/admin/flash') ?>

<div class="panel panel-info">
    <div class="panel-heading"><?= Yii::t('video_gallery', 'Information') ?></div>
    <div class="panel-body">
        <?= Yii::t('video_gallery', 'Created at {0, date, MMMM dd, YYYY HH:mm}', [$model->created_at]) ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Html::encode($this->title) ?>
    </div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'url')->textInput(['maxlength' => 255, 'autofocus' => true]) ?>
        
        <?php 

        // The controller action that will render the list
        $url = \yii\helpers\Url::to(['/tag/taglist']);

        // Get the initial city description
        $cityDesc = empty($model->key_words) ? '' : Tag::findOne($model->key_words)->name;

        echo $form->field($model, 'key_words')->widget(Select2::classname(), [
            'initValueText' => $cityDesc, // set the initial display text
            'options' => ['placeholder' => 'ابحث عن كلمة مفتاحية ...'],
            'pluginOptions' => [
                'allowClear' => true,
                'minimumInputLength' => 3,
                'language' => [
                    'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                ],
                'ajax' => [
                    'url' => $url,
                    'dataType' => 'json',
                    'data' => new JsExpression('function(params) { return {q:params.term}; }')
                ],
                'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
                'templateResult' => new JsExpression('function(city) { return city.text; }'),
                'templateSelection' => new JsExpression('function (city) { return city.text; }'),
            ],
        ]);
        //echo $form->field($model, 'key_words')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'title')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'sort')->textInput(['maxlength' => 255]) ?>

        <?= $form->field($model, 'code')->textInput(['maxlength' => 255])->hint(Yii::t('video_gallery', 'Don\'t change this value, if you not sure for what it is for.')) ?>

        <?= $form->field($model, 'description')->textarea() ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('video_gallery', 'Save'), ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
