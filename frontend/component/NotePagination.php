<?php

namespace frontend\component;

use common\components\helpers\NoteHelper;
use yii\data\Pagination;
use frontend\exceptions\ServerErrorHttpException;
use yii\helpers\VarDumper;
use yii\web\Request;
use yii;
use yii\helpers\ArrayHelper;

/**
 * Pagination represents information relevant to pagination of data items.
 *
 * @property integer $limit The limit of the data. This may be used to set the LIMIT value for a SQL statement
 * for fetching the current page of data. Note that if the page size is infinite, a value -1 will be returned.
 * @property integer $pageSize The number of items per page. If it is less than 1, it means the page size is
 * infinite, and thus a single page contains all items.
 * @property bool more_data_exist Indicate exist more models than limit
 * @property string pag_direction  Analyze "prev" and "next" params. Depend on params $this->prev and $this->next
 * @property string prev First Model "id" or empty string.
 * @property string next Last Model "id" or empty string.
 * @property string pagination_key Model key we use for create pagination.
 *
 */
class NotePagination extends Pagination
{
    /**
     * Model key we use for create pagination.
     * Have to be Unique and Sortable.
     *
     * @var string
     */
    public $pagination_key;

    /**
     * Key for sort data from local db in final output
     *
     * @var string
     */
    public $order_by;

    /**
     * Flag to define whether we save data to local db or not
     *
     * @var bool
     */
    public $save = null;

    /**
     * Pagination's direction.
     * Analyze "prev" and "next" params. Depend on params $this->prev and $this->next
     * <code>
     *   ...
     *   if ($prev !== '') {
     *        $this->pag_direction = self::LINK_PREV;
     *    } elseif ($next !== '') {
     *        $this->pag_direction = self::LINK_NEXT;
     *    } elseif ($prev == '' && $next == '') {
     *        $this->pag_direction = self::LINK_FIRST;
     *    }
     *   ...
     * </code>
     *
     * @var string
     */
    public $pag_direction = '';

    /**
     * Indicate exist more models than limit
     * Depend on the result of the test.
     * Should we create next or prev url.
     *
     * <code>
     *   //Check:Can we get MORE models. Should we create link "NEXT" or "PREV"
     *   $limit = $pagination->getLimit() + 1;
     *   $query->limit($limit);
     * </code>
     *
     * @var bool
     */
    public $more_data_exist = null;

    /**
     * Unique Model's key. Use for go to previous page.
     * Can be empty for pag_direction == FIRST or pag_direction == NEXT.
     *
     * @var string
     */
    public $prev;

    /**
     * Unique Model's key. Use for go to next page.
     * Can be empty for pag_direction == FIRST or pag_direction == PREV.
     * @var string
     */
    public $next;

    /**
     * @inheritdoc
     * @throws ServerErrorHttpException
     */
    public function init()
    {
        parent::init();
        $this->paginationDirectionGenerate();
    }

    /**
     * Analyze "prev" and "next" params.
     * Pagination Direction depend on params $this->prev and $this->next
     *
     * @throws ServerErrorHttpException
     */
    private function paginationDirectionGenerate()
    {
        $prev = (string)$this->prev;
        $next = (string)$this->next;

        if ($prev !== '') {
            $this->pag_direction = self::LINK_PREV;
        } elseif ($next !== '') {
            $this->pag_direction = self::LINK_NEXT;
        } elseif ($prev == '' && $next == '') {
            $this->pag_direction = self::LINK_FIRST;
        } else {
            $msg = 'Incorrect Pagination Direction. Given data:.';
            $msg = $msg . ' prev = ' . VarDumper::export($this->prev) . ' next = ' . VarDumper::export($this->next);
            \Yii::error($msg, __METHOD__);
            throw new ServerErrorHttpException();
        }
    }

    /**
     * Creates the URL suitable for pagination with the specified page number.
     * This method is mainly called by pagers when creating URLs used to perform pagination.
     *
     * @param integer $pageSize the number of items on each page. If not set, the value of [[pageSize]] will be used.
     * @param boolean $absolute whether to create an absolute URL. Defaults to `false`.
     *
     * @return string the created URL
     * @see params
     * @see forcePageParam
     */
    public function createApiUrl($pageSize = null, $absolute = false)
    {
        $pageSize = (int)$pageSize;
        if (($params = $this->params) === null) {
            $request = Yii::$app->getRequest();
            $params = $request instanceof Request ? $request->getQueryParams() : [];
        }

        if ($pageSize <= 0) {
            $pageSize = $this->getPageSize();
        }
        if ($pageSize != $this->defaultPageSize) {
            $params[$this->pageSizeParam] = $pageSize;
        } else {
            unset($params[$this->pageSizeParam]);
        }

        $params[0] = $this->route === null ? Yii::$app->controller->getRoute() : $this->route;
        $urlManager = $this->urlManager === null ? Yii::$app->urlManager : $this->urlManager;
        $params = $this->rewriteUrl($params);
        if ($absolute) {
            return $urlManager->createAbsoluteUrl($params, NoteHelper::yiiparam('api')['scheme']);
        } else {
            return $urlManager->createUrl($params);
        }
    }

    /**
     * Rewrite url params

     * @param array $params Array of Generated Links
     * @return array Without unnecessary params.
     */
    public function rewriteUrl($params)
    {
        if (isset($params['per-page'])) {
            unset($params['per-page']);
        }
        return $params;
    }

    /**
     * Returns a whole set of links for navigating to the next and previous pages.
     *
     * @param boolean $absolute whether the generated URLs should be absolute.
     *
     * @return array the links for navigational purpose.
     * The array keys specify the purpose of the links,
     * and the array values are the corresponding URLs.
     * @throws ServerErrorHttpException
     */
    public function getLinks($absolute = false)
    {
        $links = [];
        $link_next = $this->getParam(self::LINK_NEXT);
        $link_prev = $this->getParam(self::LINK_PREV);

        switch ($this->pag_direction) {
            case self::LINK_FIRST:
                $links[self::LINK_PREV] = null;

                if ($this->more_data_exist) {
                    unset($this->params[self::LINK_PREV]);
                    $links[self::LINK_NEXT] = $this->createApiUrl(null, $absolute);
                } else {
                    $links[self::LINK_NEXT] = null;
                }

                break;
            case self::LINK_NEXT:
                unset($this->params[self::LINK_NEXT]);
                $links[self::LINK_PREV] = $this->createApiUrl(null, $absolute);

                if ($this->more_data_exist) {
                    unset($this->params[self::LINK_PREV]);
                    $this->params = ArrayHelper::merge([self::LINK_NEXT => $link_next], $this->params);
                    $links[self::LINK_NEXT] = $this->createApiUrl(null, $absolute);
                } else {
                    $links[self::LINK_NEXT] = null;
                }

                break;
            case self::LINK_PREV:
                unset($this->params[self::LINK_PREV]);
                $links[self::LINK_NEXT] = $this->createApiUrl(null, $absolute);

                if ($this->more_data_exist) {
                    unset($this->params[self::LINK_NEXT]);
                    $this->params = ArrayHelper::merge([self::LINK_PREV => $link_prev], $this->params);
                    $links[self::LINK_PREV] = $this->createApiUrl(null, $absolute);
                } else {
                    $links[self::LINK_PREV] = null;
                }

                break;
            default:
                $msg = 'Failed generate Links. Pagination directory =' . VarDumper::export($this->pag_direction);
                \Yii::error($msg, __METHOD__);
                throw new ServerErrorHttpException();
        }
        return $links;
    }

    /**
     * Return query param if exist.
     *
     * @param $key string Query param key.
     * @return mixed Value of Query param.
     * @throws ServerErrorHttpException
     */
    private function getParam($key)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        }
        \Yii::error('Cannot get param. Given key == ' . VarDumper::export($key), __METHOD__);
        throw new ServerErrorHttpException();
    }

}