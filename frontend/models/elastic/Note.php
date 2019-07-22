<?php

namespace frontend\models\elastic;

use yii\elasticsearch\ActiveRecord;

class NoteElastic extends ActiveRecord
{
    public function attributes()
    {
        // path mapping for '_id' is setup to field 'id'
        return ['id', 'text'];
    }

}