<?php

namespace frontend\exceptions;

use yii\base\InvalidCallException;
use yii\base\UnknownPropertyException;
use yii\helpers\StringHelper;
use yii\web\HttpException;

class NoteException extends HttpException
{
    /**
     * Error code types depend on class name.
     *
     * Array structure:
     * <code>
     * 'ExceptionClassName'=>  // Exception class name @see get_class()
     *   [
     *      10000,                  // @see $errorCode
     *      'Exception error text'  // @see $errorText
     *   ]
     * </code>
     *
     * @var array Systems error_code and error_text associated with Exception class name.
     */
    private static $error_codes = [
        'Exception' => [10000, 'Internal Server Error'],
        'BadRequestHttpException' => [10001, 'Bad Request'],
        'NotFoundHttpException' => [10007, 'Not Found'],
        'ServerErrorHttpException' => [10000, 'Internal Server Error'],
        'ValidationErrorHttpException' => [10012, 'Validation error. Please enter valid parameter values. Parameter:'],
    ];
    /**
     * @var integer HTTP status code, such as 404, 500, etc. Default status code.
     */
    public $statusCode = 500;
    /**
     * @var integer Internal error code.
     */
    private $errorCode = null;
    /**
     * @var string Error text.
     */
    private $errorText = null;
    /**
     * @var string Url string description about error.
     */
    private $errorUrl = null;

    /**
     * Constructor.
     * @param integer $status HTTP status code, such as 404, 500, etc.
     * @param integer $error_code Internal error code
     * @param string $message error message @see $error_text
     * @param integer $code error code
     * @param Exception $previous The previous exception used for the exception chaining.
     */
    public function  __construct($status = null, $error_code = null, $message = null, $code = 0, Exception $previous = null)
    {
        if ($status !== null) {
            $this->statusCode = $status;
        }
        if ($error_code === null) {
            $this->errorCode = self::$error_codes['Exception'][0]; //Set Default error code.
        }
        if ($message === null) {
            $this->errorText = self::$error_codes['Exception'][1]; //Set Default error text.
        }
        parent::__construct($this->errorText, $code, $previous);
    }

    /**
     * Returns the value of a component property.
     * This method will check
     *  - a property defined by a getter: return the getter result
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$value = $component->property;`.
     * @param string $name the property name
     * @return mixed the property value.
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is write-only.
     * @see __set()
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        }
        if (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Sets the value of a component property.
     * This method will check and act accordingly:
     *
     *  - a property defined by a setter: set the property value
     *
     * Do not call this method directly as it is a PHP magic method that
     * will be implicitly called when executing `$component->property = $value;`.
     * @param string $name the property name or the event name
     * @param mixed $value the property value
     * @throws UnknownPropertyException if the property is not defined
     * @throws InvalidCallException if the property is read-only.
     * @see __get()
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            // set property
            $this->$setter($value);
            return;
        }
        if (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        } else {
            throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
        }
    }

    /**
     * Resolve the Systems $error_code, accordingly Exception class name @see get_class().
     *
     * @return integer error_code associated Exception class name @see $error_codes[]
     */
    public function getErrorCode()
    {
        if ($this->errorCode !== null) {
            return $this->errorCode;
        } else {
            $class_name = StringHelper::basename(get_class($this));
            if (array_key_exists($class_name, self::$error_codes)) {
                $this->errorCode = self::$error_codes[$class_name][0];
                return $this->errorCode;
            }
        }
        return null;
    }

    /**
     * Resolve the Systems $error_text, accordingly Exception class name @see get_class().
     *
     * @return string error_text associated Exception class name @see $error_codes[]
     */
    public function getErrorText()
    {
        if ($this->errorText !== null) {
            return $this->errorText;
        } else {
            $class_name = StringHelper::basename(get_class($this));
            if (array_key_exists($class_name, self::$error_codes)) {
                $this->errorText = self::$error_codes[$class_name][1];
                return $this->errorText;
            }
        }
        return null;
    }

    /**
     * Setter for $error_text param
     * @param string $message
     */
    public function setErrorText($message)
    {
        $this->errorText = $message;
    }

    /**
     * Setter for $error_url param
     * @param string $url
     */
    public function setErrorUrl($url)
    {
        $this->errorUrl = $url;
    }
}
