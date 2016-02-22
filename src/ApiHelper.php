<?php

namespace dimvic\YiiYin;

class ApiHelper
{
    public static $config;

    public static $type;
    public static $id;
    public static $relationship;

    public static $methods;
    public static $relationships;

    public static $resource;
    public static $relations;

    public static function init($type, $id, $relationship)
    {
        self::$config = \Yii::app()->controller->module->resources;

        self::$type = $type;
        self::$id = $id;
        self::$relationship = $relationship;

        self::$methods = self::getMethods($type);
        self::$relationships = self::getExposedRelationships($type);

        self::$resource = self::getTypeResource($type);
        self::$relations = self::getResourceRelations(self::$resource);
    }

    /**
     * @todo document or documents
     *
     * @var array response to be emitted
     */
    public static $response = [];

    /**
     * @var array errors encountered, items can be instances of array or Exception
     */
    public static $responseErrors = [];

    public static function getConfig()
    {
        return \Yii::app()->modules['yiiyin'];
    }

    /**
     * yiiyin route
     *
     * @return string
     */
    public static function getRoute()
    {
        return !empty(self::getConfig()['route']) ? self::getConfig()['route'] : 'api';
    }

    /**
     * @param string|object $item
     * @return array
     */
    public static function getResourceConfig($item)
    {
        $class = is_string($item) ? $item : get_class($item);
        return !empty(self::getConfig()['resources'][$class]) ? self::getConfig()['resources'][$class] : [];
    }

    /**
     * yiiyin supported resources
     *
     * @return array
     */
    public static function getTypes()
    {
        return self::getConfig()['resources'];
    }

    /**
     * Get the yiiyin resource name for an object|class
     *
     * @param object|string $item
     * @return string|null
     */
    public static function getResourceType($item)
    {
        $class = is_string($item) ? $item : get_class($item);
        return !empty(self::getResourceConfig($class)['type']) ? self::getResourceConfig($class)['type'] : null;
    }

    /**
     * @param string $type
     * @return string
     */
    public static function getTypeResource($type)
    {
        $class = null;
        foreach (self::getConfig()['resources'] as $class => $config) {
            if ($config['type'] == $type) {
                break;
            }
        }
        return $class;
    }

    /**
     * @param string $type
     * @return array
     */
    public static function getMethods($type)
    {
        $class = self::getTypeResource($type);
        return !empty(self::getResourceConfig($class)['methods']) ? self::getResourceConfig($class)['methods'] : [];
    }

    /**
     * @param string|object $type
     * @return array
     */
    public static function getExposedRelationships($type)
    {
        $class = self::getTypeResource($type);
        return !empty(self::getResourceConfig($class)['exposedRelationships'])
            ? self::getResourceConfig($class)['exposedRelationships']
            : [];
    }

    /**
     * @param string $type
     * @return array
     */
    public static function getDefaultRelationships($type)
    {
        $class = self::getTypeResource($type);
        return !empty(self::getResourceConfig($class)['defaultRelationships'])
            ? self::getResourceConfig($class)['defaultRelationships']
            : [];
    }

    /**
     * @param string|object $item
     * @return array
     */
    public static function getResourceRelations($item)
    {
        $resource = is_string($item) ? new $item : $item;
        return $resource->relations();
    }

    /**
     * @param $domainObject
     * @param $relationship
     * @return string|null
     */
    public static function getResourceRelationshipRelationName($domainObject, $relationship)
    {
        return !empty(self::getExposedRelationships(self::getResourceType($domainObject))[$relationship])
            ? self::getExposedRelationships(self::getResourceType($domainObject))[$relationship]
            : null;
    }
}
