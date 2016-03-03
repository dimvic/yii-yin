<?php

namespace dimvic\YiiYin;

class ApiUrlRule extends \CBaseUrlRule
{
    public function createUrl($manager, $route, $params, $ampersand)
    {
        $route = ApiHelper::getRoute();
        $url = false;
        if (preg_match('#(/?' . preg_quote($route) . ')$#', $route)) {
            $model = !empty($params['model']) ? $params['model'] : null;
            if ($model && is_object($model)) {
                $url = "/{$route}/" . ApiHelper::getResourceType(get_class($model)) . '/' . $model->id;
            }
        }
        return $url;
    }

    public function parseUrl($manager, $request, $pathInfo, $rawPathInfo)
    {
        $ret = preg_match('#^/?' . preg_quote($route = ApiHelper::getRoute()) . '#', $rawPathInfo)
            ? (preg_match('#^/?' . preg_quote($route = ApiHelper::getRoute()) . '/default/error#', $rawPathInfo)
                ? 'yiiyin/default/error'
                : 'yiiyin/default/index'
            )
            : false;
        return $ret;
    }
}
