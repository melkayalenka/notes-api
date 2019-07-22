<?php
namespace frontend\models\forms;

use yii\base\Model;

class FormNoteActionCreateNoteIn extends Model
{
    /**
     * Title of the note
     *
     * @required true
     * @var string
     */
    public $title;

    /**
     * Body of the note
     *
     * @required true
     * @var string
     */
    public $text;

    /**
     * Author
     *
     * @required true
     * @var string
     */
    public $author;

    public function rules()
    {
        return [
            [['title', 'text', 'author'], 'filter', 'filter' => 'trim'],
            [['title', 'text', 'author'], 'required'],

            ['title', 'string', 'min' => 1, 'max' => 255],
            ['text', 'string', 'min' => 1, 'max' => 65535],
            [['title', 'text', 'author'], 'filter', 'filter' => [$this, 'normalizeText']],
        ];
    }

    public function normalizeText($value) {
        return htmlspecialchars($value);
    }
}
