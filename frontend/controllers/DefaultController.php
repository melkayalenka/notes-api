<?php

namespace frontend\controllers;


use yii\base\Model;
use yii\web\Controller;
use common\behaviors\ValidationActionController;
use common\behaviors\GetUrlParamsToBodyJson;

class DefaultController extends Controller
{
    public $enableCsrfValidation = false;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'getUrlParamsToBodyJson' => [
                'class' => GetUrlParamsToBodyJson::class,
            ],
            'validationActionController' => [
                'class' => ValidationActionController::class,
                'putForForm' => 'frontend\models\forms',
            ],
        ];
    }

    /**
     * Get a proven model.
     *
     * @return Model
     */
    public function getValidatedFormData()
    {
        return $this->validatedFormData;
    }

    /**
     * Set a proven model.
     *
     * @param Model
     */
    public function setValidatedFormData($validatedFormData)
    {
        $this->validatedFormData = $validatedFormData;
    }

}