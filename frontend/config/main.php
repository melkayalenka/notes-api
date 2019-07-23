<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'request' => [
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'as isContentTypeJson' => [
                'class' => 'common\behaviors\CheckContentTypeAppJson'
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['incoming_requests'],
                    'logFile' => '@app/runtime/logs/incoming_requests.log',
                    'maxFileSize' => 1024 * 10,
                    'maxLogFiles' => 5,
                    'logVars' => [],
                ],
            ],
        ],
        'errorHandler' => [
            'class' => 'common\errorhandler\ApiErrorHandler',
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST notes' => 'note/create-note',
                'GET notes' => 'note/view-notes',
                'GET notes/<noteSID>' => 'note/view-note',
                'PUT notes/<noteSID>' => 'note/update-note',
                'DELETE notes/<noteSID>' => 'note/delete-note',
            ],
        ],

    ],
    'params' => $params,
];
