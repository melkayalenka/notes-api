<?php
/**
 * Created by PhpStorm.
 * User: KSemibratov
 * Date: 30.09.2015
 * Time: 13:43
 */

namespace common\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Controller;

class GetUrlParamsToBodyJson extends Behavior
{
    /**
     * @return array
     */
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'parserGetParamsToJsonBody',
        ];
    }

    /**
     * Get migrate params from the body of the request
     *
     * @param $event
     * @throws \yii\base\InvalidConfigException
     */
    public function parserGetParamsToJsonBody($event)
    {
        if (Yii::$app->getRequest()->isGet) {
            if(is_array(Yii::$app->getRequest()->getQueryParams())){
                if(is_array(Yii::$app->getRequest()->getBodyParams())){
                    Yii::$app->getRequest()->setBodyParams(array_merge(Yii::$app->getRequest()->getBodyParams(), Yii::$app->getRequest()->getBodyParams()));
                }else{
                    Yii::$app->getRequest()->setBodyParams(Yii::$app->getRequest()->getQueryParams());
                }
            }
        }
    }
}
