<?php

class YiiyinModule extends \CWebModule
{
    public $resources = [];
    public $route = 'api';

    public $controllerMap = [
        'default'=> [
            'class'=>'dimvic\\YiiYin\\ApiController',
        ],
    ];
}
