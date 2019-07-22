<?php

namespace frontend\component;

use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use yii\db\QueryInterface;
use yii\base\InvalidConfigException;
use frontend\component\NotePagination;
use frontend\exceptions\ServerErrorHttpException;
use yii\helpers\VarDumper;
use yii\helpers\ArrayHelper;

class NoteActiveDataProvider extends ActiveDataProvider
{
    /**
     * @inheritdoc
     * 1. Prepare Query for pagination
     * 2. Query data(fetchData)
     * 3. Clear data if necessary.
     *
     * @return $nodels
     * @throws ServerErrorHttpException
     * @throws InvalidConfigException
     */
    protected function prepareModels()
    {
        if (!$this->query instanceof QueryInterface && $this->query !== null) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }

        if ($this->query === null) {
            $models = [];
        } else {
            $query = $this->processBeforeFetchedModels();
            $models = $query->all($this->db);
        }
        $models = $this->processAfterFetchedModels($models);

        /** @var \frontend\component\NotePagination $pagination */
        $pagination = $this->getPagination();
        $pagination->params = $this->prepareRequestParams($models);

        return $models;
    }

    /**
     * Prepare Query for pagination
     *
     * Depend on "Pagination key" and "Pagination Direction" prepare query request.
     *
     * @param $models array Result of fetch Models for pagination.
     * @return $query
     * @throws ServerErrorHttpException
     */
    public function processBeforeFetchedModels()
    {
        /** @var \frontend\component\NotePagination $pagination */
        $pagination = $this->getPagination();

        if ($this->query === null) {
            return null;
        }

        $query = clone $this->query;

        $limit = $pagination->getLimit() + 1; //Check:Can we get MORE models. Should we create link "NEXT" or "PREV"
        $query->limit($limit);

        $modelClass = $query->modelClass;
        if ($pagination->order_by !== null) {
            $sort = $pagination->order_by;
        } else {
            $sort = $pagination->pagination_key;
        }
        $key = $pagination->pagination_key;

        switch ($pagination->pag_direction) {
            case NotePagination::LINK_FIRST:
                $query->addOrderBy([$key => SORT_DESC]);
                break;
            case NotePagination::LINK_PREV:
                $query->andWhere(['>', $key, $pagination->prev])->addOrderBy([$sort => SORT_ASC]);
                break;
            case NotePagination::LINK_NEXT:
                $query->andWhere(['<', $key, $pagination->next])->addOrderBy([$sort => SORT_DESC]);
                break;
            default:
                $msg = "Failed prepare query for pagination.";
                \Yii::error($msg, __METHOD__);
                throw new ServerErrorHttpException();
        }
        return $query;
    }

    /**
     * Check exist more Models than param "limit". Init param $pagination->more_data_exist.
     * Extract First or Last array of model depend on $pagination->more_data_exist and $pagination->pag_direction.
     *
     * Attention:
     * For $pagination->pag_direction we have to do array_reverse($models)
     *
     * @param $models array Result of fetch Models for pagination.
     * @return array of Models.
     * @throws ServerErrorHttpException
     */
    public function processAfterFetchedModels($models)
    {
        /** @var \frontend\component\NotePagination $pagination */
        $pagination = $this->getPagination();
        $this->checkExistMoreModels($models);

        switch ($pagination->pag_direction) {
            case NotePagination::LINK_FIRST:
                if ($pagination->more_data_exist) {
                    array_pop($models);
                }
                break;
            case NotePagination::LINK_PREV:
                $models = array_reverse($models);
                if ($pagination->more_data_exist) {
                    array_shift($models);
                }
                break;
            case NotePagination::LINK_NEXT:
                if ($pagination->more_data_exist) {
                    array_pop($models);
                }
                break;
            default:
                $msg = "Failed pagination (processAfterFetchedModels). Given data = ";
                $msg = $msg . "Pag_direction = " . VarDumper::export($pagination->pag_direction);
                $msg = $msg . "Models count = " . VarDumper::export(count($models));
                \Yii::error($msg, __METHOD__);
                throw new ServerErrorHttpException();
        }
        return $models;
    }

    /**
     * Check exist more Models than param "limit". Init param $pagination->more_data_exist.
     * @param $models array of Models that was fetched for pagination.
     */
    protected function checkExistMoreModels($models)
    {
        /** @var \frontend\component\NotePagination $pagination */
        $pagination = $this->getPagination();
        $limit = $pagination->getLimit() + 1; //Check:Can we get MORE models. Should we create link "NEXT" or "PREV"

        if (count($models) == $limit) {
            $pagination->more_data_exist = true;
        } else {
            $pagination->more_data_exist = false;
        }
    }

    /**
     * Extract last and first key from fetched models
     * and return array of request QueryParams.
     * @param ActiveRecord[] $models
     * @return array of request QueryParams.
     */
    public function prepareRequestParams($models)
    {
        /** @var @var \yii\web\Request  $request */
        $request = \Yii::$app->getRequest();
        $params = $request->getQueryParams();

        /** @var \frontend\component\NotePagination $pagination */
        $pagination = $this->getPagination();
        $key = $pagination->pagination_key;

        if (empty($models)) {
            $first_model = '';
            $last_model = '';
        } else {
            $first_model = $models[0];
            $last_model = end($models);
        }

        $params = ArrayHelper::merge(
            $params,
            [
                'prev' => $first_model !== '' ? (string)$first_model->$key : '',
                'next' => $last_model !== '' ? (string)$last_model->$key : ''
            ]
        );
        return $params;
    }

    /**
     * {@inheritdoc}
     * @throws InvalidConfigException
     */
    protected function prepareTotalCount()
    {
        if (!$this->query instanceof QueryInterface) {
            throw new InvalidConfigException('The "query" property must be an instance of a class that implements the QueryInterface e.g. yii\db\Query or its subclasses.');
        }
        $query = clone $this->query;
        return (int) $query->limit(-1)->offset(-1)->orderBy([])->count('*', $this->db);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareKeys($models)
    {
        $keys = [];
        if ($this->key !== null) {
            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }
            return $keys;
        } elseif ($this->query instanceof ActiveQueryInterface) {
            /* @var $class \yii\db\ActiveRecordInterface */
            $class = $this->query->modelClass;
            $pks = $class::primaryKey();
            if (count($pks) === 1) {
                $pk = $pks[0];
                foreach ($models as $model) {
                    $keys[] = $model[$pk];
                }
            } else {
                foreach ($models as $model) {
                    $kk = [];
                    foreach ($pks as $pk) {
                        $kk[$pk] = $model[$pk];
                    }
                    $keys[] = $kk;
                }
            }
            return $keys;
        }
        return array_keys($models);
    }
}
