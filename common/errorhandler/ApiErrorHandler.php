<?php

namespace common\errorhandler;

use frontend\exceptions\NoteException;
use Yii;

use yii\base\ErrorException;
use yii\web\HttpException;
use yii\web\ErrorHandler;
use yii\web\Response;

/**
 * ErrorHandler handles uncaught PHP errors and exceptions.
 *
 * @package common\errorhandler
 */
class ApiErrorHandler extends ErrorHandler
{
    /**
     * @inheridoc
     */
    protected function renderException($exception)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
        } else {
            $response = new Response();
        }

        if ($exception instanceof HttpException) {
            if ($exception instanceof NoteException) {
                $response->setStatusCode($exception->statusCode, $exception->getMessage());
            } else {
                $response->setStatusCode($exception->statusCode);
            }
            $response->data = $this->convertResponseToArray($response);
        } else {
            Yii::error($this->convertExceptionLogToArray($exception), 'ApiError');
            $response->setStatusCode(500);
            $response->data = $this->convertResponseToArray($response);
        }

        $response->send();

        if (ErrorException::isFatalError(error_get_last())) {
            Yii::error([
                "url" => Yii::$app->getRequest()->getAbsoluteUrl() ,
                "apiRequest" =>  Yii::$app->getRequest()->getRawBody(),
                "apiResponse" => $response->content
            ], 'ApiRequests');
        }

    }

    /**
     * @param \yii\web\Response $response
     * @return array
     */
    protected function convertResponseToArray($response)
    {
        return [
            'status' => $response->getStatusCode(),
            'message' => $response->statusText,
        ];
    }

    /**
     * @param \Exception $exception
     * @return array
     */
    protected function convertExceptionLogToArray($exception)
    {
        return [
            //'name' => $exception->getName(),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'line' => $exception->getLine(),
            'file' => $exception->getFile(),
            'trace' => $exception->getTraceAsString()
        ];
    }
}
