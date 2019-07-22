<?php
namespace common\behaviors;

use frontend\controllers\DefaultController;
use ReflectionProperty;
use ValidationErrorHttpException;
use Yii;
use yii\base\Behavior;
use yii\helpers\VarDumper;

class ValidationActionController extends Behavior
{
    const BACKSLASH = '\\';
    /**
     * @type string put for form models
     */
    public $putForForm;

    public function events()
    {
        return [
            DefaultController::EVENT_BEFORE_ACTION => 'BeforeActionValidate',
        ];
    }

    /**
     * Form the name of the class
     *
     * @return string file name form model in.
     */
    protected function getNameFormFileIn()
    {
        $formName = str_replace(' ', '', ucwords(str_replace('-', ' ', $this->owner->id)));
        return "Form" . $formName . ucfirst($this->owner->action->actionMethod) . 'In';
    }

    /**
     * Form the name of the class
     *
     * @return string file name form model out.
     */
    protected function getNameFormFileOut()
    {
        return "Form" . ucfirst($this->owner->id) . ucfirst($this->owner->action->actionMethod) . 'Out';
    }

    /**
     * Form a path to the directory file
     *
     * @param string $nameFiLe  Files name form model
     *
     * @return string Full path form model
     */
    protected function getFullPathFormFile($nameFiLe)
    {
        return $this->putForForm . self::BACKSLASH . $nameFiLe;
    }

    /**
     * Validated form data.
     *
     * @param mixed $validatedFormData
     */
    protected function setValidatedFormData($validatedFormData)
    {
        $this->owner->action->controller->ValidatedFormData = $validatedFormData;
    }

    /**
     * Validation input data for form data.
     * validation happen before every action.
     *
     * @param $event
     * @return object
     * @throws ValidationErrorHttpException
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     */
    public function BeforeActionValidate($event)
    {
        $reflector = new \ReflectionClass($this->getFullPathFormFile($this->getNameFormFileIn()));
        $reflector->getProperties(ReflectionProperty::IS_PUBLIC);
        $modelFormObj = $reflector->newInstanceArgs();

        $modelFormObj->load(Yii::$app->getRequest()->getBodyParams(), '');

        if (!$modelFormObj->validate()) {
            //save validation errors to log file
            $message = 'Validation error. Given parameters: ' . VarDumper::export($modelFormObj->getAttributes());
            $message = $message . ' Errors: ' . VarDumper::export($modelFormObj->getErrors());
            \Yii::error($message, __METHOD__);
            //response validation errors through exception.
            throw new ValidationErrorHttpException($modelFormObj);
        }
        $this->setValidatedFormData($modelFormObj);
        return $modelFormObj;
    }
}