<?php

class ApiHelper {
    public static $current_type;
    public static $current_id;
    public static $current_related;

    public static $response = [];
    public static $responseErrors = [];

    public static function getResources()
    {
        return Yii::app()->controller->module->resources;
    }

    private static $_routes = [];
    public static function getRoutes()
    {
        if (empty(self::$_routes)) {
            foreach (self::getResources() as $resource=>$configuration) {
                self::$_routes[$configuration['type']] = $resource;
            }
        }
        return self::$_routes;
    }

    /**
     * @param CActiveRecord $domainObject
     * @param string $type
     * @return string
     */
    public static function getTypeRelation($domainObject, $type)
    {
        return array_search($type, self::getExposedRelations(get_class($domainObject)));
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

    private static $_currentResource;
    /**
     * @return string
     */
    public static function getCurrentResource()
    {
        !self::$_currentResource && self::$_currentResource = self::getRoutes()[self::$current_type];
        return self::$_currentResource;
    }

    private static $_currentModel;
    /**
     * @return CActiveRecord
     */
    public static function getCurrentModel()
    {
        if (!self::$_currentModel) {
            $class = self::getCurrentResource();
            self::$_currentModel = new $class;
        }
        return self::$_currentModel;
    }

    /**
     * @return array
     */
    public static function getCurrentModelRelations()
    {
        return self::getCurrentModel()->relations();
    }

    private static $_currentConfig;
    public static function getCurrentConfig()
    {
        !self::$_currentConfig && self::$_currentConfig = self::getResources()[self::getCurrentResource()];
        return self::$_currentConfig;
    }

    private static $_currentMethods;
    public static function getCurrentMethods()
    {
        !self::$_currentMethods && self::$_currentMethods = self::getCurrentConfig()['methods'];
        return self::$_currentMethods;
    }

    /**
     * @param string $class
     * @return array
     */
    public static function getExposedRelations($class)
    {
        return isset(self::getResources()[$class]) && isset(self::getResources()[$class]['exposedRelations']) ? array_keys(self::getResources()[$class]['exposedRelations']) : [];
    }

    /**
     * @param string $class
     * @return array
     */
    public static function getDefaultRelations($class)
    {
        return isset(self::getResources()[$class]) && isset(self::getResources()[$class]['defaultRelations']) ? array_keys(self::getResources()[$class]['defaultRelations']) : [];
    }
}
