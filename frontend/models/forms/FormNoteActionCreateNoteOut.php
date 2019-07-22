<?php

namespace frontend\models\forms;


use yii\base\Model;

class FormNoteActionCreateNoteOut extends Model
{
    /**
     * Numeric code of status.
     *
     * @required true
     * @var integer
     */
    public $status_code;

    /**
     * Status description.
     *
     * @required true
     * @var string
     */
    public $status_message;

    /**
     *
     * @required false
     * @var string
     */
    public $uri;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status_code', 'status_message'], 'required'],
        ];
    }

}