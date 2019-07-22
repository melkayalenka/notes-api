<?php

namespace common\components\validators;

use common\components\helpers\NoteHelper;
use yii\validators\Validator;

class SIDValidator extends Validator
{
    /** @var string|array SID prefix. */
    public $prefix = '';

    /**
     * Validates a value out of the context of a data model.
     *
     * @param mixed $value The data value to be validated.
     * @return array|null The error message and the parameters to be inserted into the error message.
     * Null should be returned if the data is valid.
     */
    protected function validateValue($value)
    {
        $result = null;

        $params = ['attribute' => $this->attributes, 'value' => $value];

        if (!is_string($value)) {
            return ['SID variable type is invalid.', $params];
        }

        if (!is_array($this->prefix)) {
            $this->prefix = [$this->prefix];
        }

        foreach ($this->prefix as $prefix) {
            $prefixLength = strlen($prefix);

            if (strlen($value) === $prefixLength + NoteHelper::UUID_LENGTH) {
                if (substr($value, 0, $prefixLength) === $prefix) {
                    return null;
                } else {
                    $result = ['SID prefix is invalid.', $params];
                }
            } else {
                $result = ['SID length is invalid.', $params];
            }
        }

        return $result;
    }
}