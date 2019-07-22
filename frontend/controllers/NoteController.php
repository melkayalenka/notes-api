<?php
namespace frontend\controllers;

use frontend\component\NotePagination;
use frontend\exceptions\ServerErrorHttpException;
use frontend\exceptions\NotFoundException;
use \yii\db\StaleObjectException;
use \yii\base\InvalidParamException;
use yii\elasticsearch\Exception;
use frontend\exceptions\NoteException;
use frontend\models\elastic\NoteElastic;
use frontend\models\forms\FormNoteActionCreateNoteIn;
use frontend\models\forms\FormNoteActionCreateNoteOut;
use frontend\models\forms\FormNoteActionViewNotesIn;
use frontend\models\forms\FormNoteActionViewNoteIn;
use frontend\models\ar\Note;
use yii\helpers\Url;
use yii\helpers\VarDumper;
use common\components\helpers\NoteHelper;
use frontend\component\NoteActiveDataProvider;
use yii\web\Response;

class NoteController extends DefaultController
{
    /**
     * @inheritdoc
     */
    public $serializer = [
        'class' => 'frontend\component\NoteSerializer',
        'collectionEnvelope' => 'notes',
    ];

    /**
     * Make a note
     * <code>
     *     curl –X POST ‘http://site.com/notes’ \
     *     -H ‘Content-Type: application/json’ \
     *     -d ‘{ \
     *          “title”: ”title example”, \
     *          “text”: ”text example”, \
     *          “author”: ”author example” \
     *         }’ \
     * </code>
     *
     * @http_method post
     * @uri /notes
     * @return FormNoteActionCreateNoteOut $outForm
     * @throws ServerErrorHttpException
     */
    public function actionCreateNote()
    {
        /** @var FormNoteActionCreateNoteIn $formIn */
        $formIn = $this->validatedFormData;

        $transaction = Note::getDb()->beginTransaction();
        try {
            $note = new Note();
            $note->note_sid = NoteHelper::UUID(Note::UUID_PREFIX);
            $note->title = $formIn->title;
            $note->text = $formIn->text;
            $note->author = $formIn->author;
            $note->save();

            $noteElasctic = new NoteElastic();
            $noteElasctic->primaryKey = $note->id;
            $noteElasctic->attributes = ['text' => $note->text];
            $noteElasctic->save();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            $msg = 'Cannot save Note. Given data = ' . VarDumper::export($formIn->getAttributes()) . ' System error: ' . VarDumper::export($note->getErrors());
            \Yii::error($msg, __METHOD__);
            throw new ServerErrorHttpException();
        }
        /** @var FormNoteActionCreateNoteOut $outForm */
        $outForm = new FormNoteActionCreateNoteOut();
        $outForm->status_code = 201;
        $outForm->status_message = 'CREATED';
        $outForm->uri = Url::toRoute('/notes/' . $note->note_sid);

        \Yii::$app->response->statusCode = 201;
        \Yii::$app->response->format = Response::FORMAT_JSON;

        return $outForm;
    }
    /**
     * Get notes list.
     * <code>
     *   curl –X GET ‘http://site/notes’ \
     *  -H ‘Content-Type: application/json’ \
     * </code>
     *
     * Filtering parameters:
     * <code>
     *   curl –X GET ‘http://site/notes?author=authorname&title=first’ \
     * </code>
     *
     * List of filtering parameters:
     * text - part of the note body
     * note_sid - Note identifier.
     * created_after - Notes that were created after this date and time.
     * created_before - Notes that were created before this date and time.
     *
     * @http_method get
     * @uri /notes
     */
    public function actionViewNotes()
    {
        /** @var FormNoteActionViewNotesIn $formIn */
        $formIn = $this->validatedFormData;

        $query = Note::find();
        if ($formIn->text !== '') {
            $notesElastic = NoteElastic::find()->query(["match" => ["text" => $formIn->text]])->all();
            foreach ($notesElastic as $index) {
                $ids[] = $index->id;
            }
            $query->andFilterWhere(['IN', 'id', implode($ids)]);
        }
        else {
            $query->andFilterWhere(['<=', 'created_at', $formIn->created_before])
                ->andFilterWhere(['>=', 'created_at', $formIn->created_after])
                ->andFilterWhere(['note_sid' => $formIn->note_sid,])
                ->andFilterWhere(['like', 'author', $formIn->author])
                ->andFilterWhere(['like', 'title', $formIn->title]);
        }
        \Yii::$app->response->statusCode = 201;
        \Yii::$app->response->format = Response::FORMAT_JSON;

        return new NoteActiveDataProvider([
            'query'      => $query,
            'pagination' => [
                'pagination_key' => 'id',
                'pageSize' => $formIn->limit,
                'prev'     => $formIn->prev,
                'next'     => $formIn->next,
                'class'    => NotePagination::class,
            ],
        ]);
    }

    /**
     * Get note details.
     *
     * <code>
     *   curl –X GET ‘http://site/notes/{noteSID}’ \
     *   -H ‘Content-Type: application/json’ \
     * </code>
     *
     * @http_method get
     * @uri /note/{noteSID}
     * @throws NotFoundException
     */
    public function actionViewNote()
    {
        /** @var FormNoteActionViewNoteIn $formIn */
        $formIn = $this->validatedFormData;

        \Yii::$app->response->statusCode = 201;
        \Yii::$app->response->format = Response::FORMAT_JSON;

        return Note::loadModel(['note_sid'  => $formIn->noteSID]);
    }

    /**
     * Modify the note
     *
     * REST API example:
     * <code>
     *      curl –X PUT ‘http://site/notes/{noteSID}’ \
     *      -H ‘Content-Type: application/json’ \
     *      -d ‘{ \
     *           “title”: “new title” \
     *          }’ \
     * </code>
     *
     * @http_method put
     * @uri /notes/{noteSID}
     * @return array
     * @throws NoteException
     * @throws ServerErrorHttpException
     * @throws StaleObjectException
     * @throws InvalidParamException
     * @throws Exception
     *
     * @throws \yii\db\Exception
     */
    public function actionUpdateNote()
    {
        /** @var \frontend\models\forms\FormNoteActionUpdateNoteIn $formIn */
        $formIn = $this->validatedFormData;
        /** @var \frontend\models\ar\Note $note
         */
        $note = Note::findOne(['note_sid' => $formIn->noteSID]);
        if ($note === null) {
            $msg = 'Note not found. Input data = ' . VarDumper::export($formIn);
            \Yii::warning($msg, __METHOD__);
            throw new NotFoundException();
        }
        $transaction = Note::getDb()->beginTransaction();
        try {
            if ($formIn->title !== '') {
                $note->title = $formIn->title;
                $noteElastic = NoteElastic::get($note->id);
                $noteElastic->update(['text' => $formIn->title]);
            }
            if ($formIn->text !== '') {
                $note->text = $formIn->text;
            }
            if ($formIn->author !== '') {
                $note->author = $formIn->author;
            }
            $note->save();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            $msg = 'Cannot update Note. Given data = ' . VarDumper::export($formIn->getAttributes());
            $msg = $msg . ' System error: ' . VarDumper::export($note->getErrors());
            \Yii::error($msg, __METHOD__);
            throw new ServerErrorHttpException();
        }
        \Yii::$app->response->statusCode = 201;
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'status_code' => 200,
            'status_message' => 'OK',
            'uri' => Url::toRoute('notes/' . $note->sid),
        ];
    }

    /**
     * Delete note.
     *
     * REST API example:
     * <code>
     *   curl –X DELETE ‘https://site/notes/{noteSID}’ \
     * </code>
     *
     * @uri /notes/{noteSID}
     * @http_method delete
     * @return array Success result response.
     * @throws NoteException
     * @throws ServerErrorHttpException
     * @throws yii\db\Exception
     * @throws yii\base\Exception
     * @throws \Throwable
     */
    public function actionDeleteNote()
    {
        /** @var \frontend\models\forms\FormNoteActionDeleteNoteIn $formIn */
        $formIn = $this->validatedFormData;
        $note = Note::loadModel(['note_sid'  => $formIn->noteSID]);
        $transaction = Note::getDb()->beginTransaction();
        try {
            $note->delete();
            $noteElastic = NoteElastic::get($note->id);
            $noteElastic->delete();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            $msg = 'Cannot delete Note. Data = ' . VarDumper::export($formIn->getAttributes());
            $msg = $msg . ' System error: ' . VarDumper::export($e->getMessage());
            \Yii::error($msg, __METHOD__);
            throw new ServerErrorHttpException();
        }
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            "status_code" => 204,
            "status_message" => "NO_CONTENT",
        ];
    }

}
