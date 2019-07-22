<?php

namespace frontend\models\forms;

use common\components\validators\SIDValidator;
use frontend\models\ar\Note;
use yii\base\Model;

class FormNoteActionUpdateNoteIn extends Model
{
    /**
     * Unique note identifier.
     *
     * @required true
     * @var string
     */
    public $noteSID;
    /**
     * Title of the note
     *
     * @required false
     * @var string
     */
    public $title;

    /**
     * Body of the note
     *
     * @required false
     * @var string
     */
    public $text;

    /**
     * Author
     *
     * @required false
     * @var string
     */
    public $author;

    public function rules()
    {
        return [
            ['noteSID', 'required'],
            ['noteSID', SIDValidator::class, 'prefix' => Note::UUID_PREFIX],
            [['title', 'text', 'author'], 'filter', 'filter' => 'trim'],
            ['title', 'string', 'min' => 1, 'max' => 255],
            ['text', 'string', 'min' => 1, 'max' => 65535],
            [['title', 'text', 'author'], 'filter', 'filter' => [$this, 'normalizeText']],
        ];
    }

    public function normalizeText($value) {
        return htmlspecialchars($value);
    }
}