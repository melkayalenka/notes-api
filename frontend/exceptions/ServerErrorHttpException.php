<?php


namespace frontend\exceptions;


class ServerErrorHttpException extends  NoteException
{
    /**
     * Constructor.
     * @param string $message error message
     * @param integer $code error code
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        if ($message !== null) {
            $this->errorText = $message;
        }
        parent::__construct(500, $this->errorCode, $this->errorText, $code, $previous);
    }

}