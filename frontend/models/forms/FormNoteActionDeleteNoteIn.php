<?php

namespace frontend\models\forms;

use common\components\validators\SIDValidator;
use frontend\models\ar\Note;

class FormNoteActionDeleteNoteIn
{
    /**
     * Unique note identifier.
     *
     * @required true
     * @var string
     */
    public $noteSID;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['noteSID', 'required'],
            ['noteSID', SIDValidator::class, 'prefix' => Note::UUID_PREFIX],
        ];
    }
}