<?php
namespace frontend\models\forms;

use yii\base\Model;
use common\components\validators\SIDValidator;
use frontend\models\ar\Note;

class FormNoteActionViewNoteIn extends Model
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
