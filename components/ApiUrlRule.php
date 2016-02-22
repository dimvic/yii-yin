<?php

class ApiUrlRule extends CBaseUrlRule
{
    public function createUrl($manager, $route, $params, $ampersand)
    {
        $url = false;
        if (preg_match('#(/?api)$#', $route)) {
            $model = !empty($params['model']) ? $params['model'] : null;
            if ($model) {
                /**
                 * @var CActiveRecord $model
                 */
                $url = '/api/' . ApiHelper::getResourceType(get_class($model)) . '/' . $model->id;
            }
        }
        return $url;
    }

    public function parseUrl($manager, $request, $pathInfo, $rawPathInfo)
    {
        return false;
    }
}
