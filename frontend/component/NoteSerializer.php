<?php

use frontend\exceptions\BadRequestHttpException;
use frontend\exceptions\NoteException;
use yii\base\Arrayable;
use yii\data\DataProviderInterface;
use yii\rest\Serializer;
use Zend\Http\Response;


class NoteSerializer extends Serializer
{
    /**
     * Serializes the given data into a format that can be easily turned into other formats.
     * This method mainly converts the objects of recognized types into array representation.
     * It will not do conversion for unknown object types or non-object data.
     * The default implementation will handle [[Model]] and [[DataProviderInterface]].
     * You may override this method to support more object types.
     * @param mixed $data the data to be serialized.
     * @return mixed the converted data.
     */
    public function serialize($data)
    {
        if ($data instanceof ValidationErrorHttpException) {
            return $this->serializeModelErrors($data);
        } elseif ($data instanceof NoteException) {
            return $this->serializeNoteException($data);
        } elseif ($data instanceof BadRequestHttpException) {
            return $this->serializeBadRequestHttpException($data);
        } elseif ($data instanceof \Exception) {
            return $this->serializeException($data);
        } elseif ($data instanceof Arrayable) {
            return $this->serializeModel($data);
        } elseif ($data instanceof DataProviderInterface) {
            return $this->serializeDataProvider($data);
        } elseif ($data instanceof Response) {
            return $this->serializeZendHttpResponse($data);
        } else {
            return $data;
        }
    }

    /**
     * Serializes a Note Exceptions.
     * All exceptions with base class \frontend\modules\v2\exception\NoteException
     *
     * @param NoteException $noteException
     * @return array the array representation of the data provider.
     */
    protected function serializeNoteException($noteException)
    {
        $this->response->setStatusCode($noteException->statusCode, $noteException->errorText);
        $this->response->data = [
            'status_code' => $noteException->statusCode,
            'error_code' => $noteException->errorCode,
            'error_text' => $noteException->errorText,
            'uri' => $noteException->errorUrl,
        ];
    }

    /**
     * Serializes all NOT Note Exceptions instanceof \Exception.
     * Have to be response Note Internal Error \frontend\modules\v2\exception\NoteException.
     *
     * Example:
     * <code>
     *  {
     *    "status_code": 500,
     *    "error_code": 10000,
     *    "error_text": "Internal Server Error",
     *    "uri": "http://127.0.0.1:8145/docs/errors/10000"
     *  }
     * <code>
     * @param \frontend\exceptions\NoteException $exception
     */
    protected function serializeException($exception)
    {
        $exception = new \frontend\exceptions\NoteException();
        $this->response->setStatusCode($exception->statusCode, $exception->errorText);
        $this->response->data = [
            'status_code' => $exception->statusCode,
            'error_code' => $exception->errorCode,
            'error_text' => $exception->errorText,
            'uri' => $exception->errorUrl,
        ];
    }

    /**
     * Serializes ONLY for  NOT Note Exceptions instanceof \yii\web\BadRequestHttpException
     * Have to be response Note Internal Error \frontend\exceptions\BadRequestHttpException.
     *
     * Very useful for error "Invalid JSON data in request body: Syntax error."
     *
     * Example:
     * <code>
     * {
     *   "status_code": 400,
     *   "error_code": 10001,
     *   "error_text": "Bad Request",
     *   "uri": "http://127.0.0.1:8145/docs/errors/10001"
     * }
     * <code>
     * @param \frontend\exceptions\NoteException $exception
     */
    protected function serializeBadRequestHttpException($exception)
    {
        $exception = new \frontend\exceptions\BadRequestHttpException();
        $this->response->setStatusCode($exception->statusCode, $exception->errorText);
        $this->response->data = [
            'status_code' => $exception->statusCode,
            'error_code' => $exception->errorCode,
            'error_text' => $exception->errorText,
            'uri' => $exception->errorUrl,
        ];
    }

    /**
     * Serializes the validation errors in a model.
     * Example:
     * <code>
     *   {
     *     "status_code": 400,
     *     "error_code": 11211,
     *     "error_text": "Validation error. Please enter valid parameter values. Parameter: limit",
     *     "uri": "http://80.75.132.199:8145/docs/errors/11211"
     *   }
     * <code>
     *
     * @param ValidationErrorHttpException $validationException
     * @return array|void
     */
    protected function serializeModelErrors($validationException)
    {
        $this->response->setStatusCode($validationException->statusCode, 'Data Validation Failed.');
        $this->response->data = [
            'status_code' => $validationException->statusCode,
            'error_code' => $validationException->errorCode,
            'error_text' => $validationException->errorText,
            'parameters' => $validationException->parameters,
            'errors' => $validationException->errors,
            'uri' => $validationException->errorUrl,
        ];
    }


    /**
     * @param Response $data
     *
     * @return string
     */
    protected function serializeZendHttpResponse($data)
    {
        foreach ($data->getHeaders() as $header) {
            Yii::$app->getResponse()->getHeaders()->add($header->getFieldName(), $header->getFieldValue());
        }
        Yii::$app->getResponse()->format = yii\web\Response::FORMAT_RAW;
        return $data->getContent();
    }

}