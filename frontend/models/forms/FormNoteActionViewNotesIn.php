<?php

namespace frontend\models\forms;


use yii\base\Model;
use common\components\validators\SIDValidator;
use frontend\models\ar\Note;

/**
 * Class FormCallActionViewNotesIn.
 * This is the model class for validate input
 *
 * @package frontend\models\forms
 *
 * @property string $note_sid
 * @property string $created_after
 * @property string $created_before
 * @property string $title
 * @property string $text
 * @property string $author
 * @property string $limit
 */

class FormNoteActionViewNotesIn extends Model
{
    /**
     * Number of returned entries (default and maximum 20).
     *
     * @default 20
     * @required false
     * @var integer
     */
    public $limit;

    /**
     * Note identifier.
     *
     * @required false
     * @var string
     */
    public $note_sid;

    /**
     * Notes that were created before this date and time.
     *
     * @required false
     * @var string
     */
    public $created_before;

    /**
     * Notes that were created after this date and time.
     *
     * @required false
     * @var string
     */
    public $created_after;

    /**
     * Part of title.
     *
     * @required false
     * @var string
     */
    public $title;

    /**
     * Part of text.
     *
     * @required false
     * @var string
     */
    public $text;

    /**
     * Author of the note.
     *
     * @required false
     * @var string
     */
    public $author;

    /**
     * Id of the note
     *
     * @required false
     * @var string First Model "id" or empty string.
     */
    public $prev;

    /**
     * Id of the note
     *
     * @required false
     * @var string First Model "id" or empty string.
     */
    public $next;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [
                ['limit', 'note_sid', 'created_after', 'created_before', 'title', 'text', 'author'
                ], 'filter', 'filter' => 'trim',
            ],
            [['title', 'author'], 'string', 'min' => 1, 'max' => 255],
            ['note_sid', SIDValidator::class, 'prefix' => Note::UUID_PREFIX],
            [['prev', 'next'], 'string'],
            ['limit', 'default', 'value' => 20],
            ['limit', 'number', 'min' => 1, 'max' => 20],

        ];
    }
}
