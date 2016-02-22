<?php

class ApiHelper
{
    public static $current_type;
    public static $current_id;
    public static $current_related;

    public static $response = [];
    public static $responseErrors = [];

    public static function getResources()
    {
        return Yii::app()->controller->module->resources;
    }

    private static $routes = [];

    public static function getRoutes()
    {
        if (empty(self::$routes)) {
            foreach (self::getResources() as $resource => $configuration) {
                self::$routes[$configuration['type']] = $resource;
            }
        }
        return self::$routes;
    }

    /**
     * @param string|CActiveRecord $domainObject
     * @param string $type
     * @return string
     */
    public static function getTypeRelation($domainObject, $type)
    {
        $class = is_string($domainObject) ? $domainObject : get_class($domainObject);
        return array_search($type, self::getExposedRelations($class));
    }

    public static function getRelationType($domainObject, $relation)
    {
        return self::getExposedRelations(get_class($domainObject))[$relation];
    }

    public static function getTypeResource($type)
    {
        return self::getRoutes()[$type];
    }

    public static function getCurrentId()
    {
        return self::$current_id;
    }

    public static function getResourceType($resource)
    {
        return array_search($resource, self::getRoutes());
    }

    public static function getRequestedRelated()
    {
        return self::$current_related;
    }

    private static $currentResource;

    /**
     * @return string
     */
    public static function getCurrentResource()
    {
        !self::$currentResource && self::$currentResource = self::getRoutes()[self::$current_type];
        return self::$currentResource;
    }

    private static $currentModel;

    /**
     * @return CActiveRecord
     */
    public static function getCurrentModel()
    {
        if (!self::$currentModel) {
            $class = self::getCurrentResource();
            self::$currentModel = new $class;
        }
        return self::$currentModel;
    }

    /**
     * @return array
     */
    public static function getCurrentModelRelations()
    {
        return self::getCurrentModel()->relations();
    }

    private static $currentConfig;

    public static function getCurrentConfig()
    {
        !self::$currentConfig && self::$currentConfig = self::getResources()[self::getCurrentResource()];
        return self::$currentConfig;
    }

    private static $currentMethods;

    public static function getCurrentMethods()
    {
        !self::$currentMethods && self::$currentMethods = self::getCurrentConfig()['methods'];
        return self::$currentMethods;
    }

    /**
     * @param string $class
     * @return array
     */
    public static function getExposedRelations($class)
    {
        return isset(self::getResources()[$class]) && isset(self::getResources()[$class]['exposedRelations'])
            ? array_keys(self::getResources()[$class]['exposedRelations'])
            : [];
    }

    /**
     * @param string $class
     * @return array
     */
    public static function getDefaultRelations($class)
    {
        return isset(self::getResources()[$class]) && isset(self::getResources()[$class]['defaultRelations'])
            ? array_keys(self::getResources()[$class]['defaultRelations'])
            : [];
    }
}
