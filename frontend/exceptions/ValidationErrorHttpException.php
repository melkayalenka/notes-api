<?php

use frontend\exceptions\NoteException;
use yii\base\Model;

class ValidationErrorHttpException extends NoteException
{
    /**
     * @var integer HTTP status code, such as 404, 500, etc.
     */
    public $statusCode = 400;

    /**
     * @var string Message text.
     */
    public $errorText = 'Validation error. Please enter valid parameter values.';

    /**
     * @var array Parameters with validation error.
     */
    public $parameters;

    /**
     * @var array Descriptions about errors with validation error.
     */
    public $errors = null;

    /**
     * @var string Url string description about error.
     */
    public $uri;

    /**
     * Constructor.
     *
     * @param object $model_error Model with validation errors.
     * @param integer $code error code
     * @param Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($model_error, $code = 0, Exception $previous = null)
    {
        if ($model_error instanceof Model && $model_error->hasErrors()) {
            foreach ($model_error->getFirstErrors() as $name => $message) {
                $this->parameters[] = $name;
            }
        }

        if ($model_error->hasErrors()) {
            $this->errors = $model_error->getErrors();
        }

        parent::__construct($this->statusCode, $this->errorCode, $this->errorText, $code, $previous);
    }

}