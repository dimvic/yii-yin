<?php

namespace dimvic\YiiYin;

class ApiErrorHandler extends \CErrorHandler
{
    public function getError()
    {
        /** @var ApiController $controller */
        $controller = \Yii::app()->controller;
        ApiHelper::$responseErrors[] = parent::getError();
        $controller->sendErrors();
    }
}
