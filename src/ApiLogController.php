<?php

namespace dimvic\YiiYin;

class ApiLogController extends ApiController
{
    public function actionIndex()
    {
        /** @var \CWebUser $user */
        $user = \Yii::app()->user;
        $userId = (int)$user->id;
        $requestType = \Yii::app()->request->requestType;
        $pathInfo = \Yii::app()->request->pathInfo;
        $data = \Yii::app()->request->getRawBody();

        $log = <<<LOG
User: {$userId}
Request: {$requestType} {$pathInfo}
Body: {$data}
LOG;

        \Yii::log($log, 'info', 'json:api');

        parent::actionIndex();
    }
}
