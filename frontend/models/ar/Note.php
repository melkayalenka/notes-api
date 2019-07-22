<?php
namespace frontend\models\ar;

use frontend\exceptions\ServerErrorHttpException;
use yii\db\ActiveRecord;
use frontend\exceptions\NotFoundException;
use yii\db\Expression;
use yii\helpers\VarDumper;

/**
 * Class Note.
 *
 * @package frontend\models\ar
 *
 * @property integer $id
 * @property string $note_sid
 * @property string $title
 * @property string $text
 * @property string $author
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $deleted_at
 *
 */

class Note extends ActiveRecord
{
    const UUID_PREFIX = 'not';
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%note}}';
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return [
            'note_sid',
            'author',
            'title',
            'text',
            'created' => function () {
                return date('r', strtotime($this->created_at));
            },
            'updated' => function () {
                return date('r', strtotime($this->updated_at));
            },
        ];
    }
    /**
     * Make search query with given condition.
     * Check result.
     *
     * @param array $condition Search query condition
     *
     * @return Note
     * @throws NotFoundException
     */
    public static function loadModel($condition)
    {
        $note = static::findOne($condition);
        if ($note === null) {
            $msg = 'Cannot find Note. Given condition = ' . VarDumper::export($condition);
            \Yii::error($msg, __METHOD__);
            throw new NotFoundException();
        }
        return $note;
    }

    /**
     * Override Yii2 Default Scope
     *
     * @return \yii\db\ActiveQuery
     */
    public static function find()
    {
        return parent::find()->andWhere([self::tableName() . '.deleted_at' => null]);
    }

    /*
        * @param int $executionType
        * @return false|int|void
        * @throws NotFoundHttpException
        * @throws ServerErrorHttpException
     */
    public function delete()
    {
        $this->deleted_at = new Expression('NOW()');
        if (!$this->save()) {
            $msg = 'Cannot delete Note. System error: ' . VarDumper::export($this->getErrors());
            \Yii::error($msg, __METHOD__);
            throw new ServerErrorHttpException();
        }
    }
}