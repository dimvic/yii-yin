<?php

namespace dimvic\YiiYin;

use WoohooLabs\Yin\JsonApi\Exception\ExceptionFactoryInterface;
use WoohooLabs\Yin\JsonApi\Hydrator\AbstractHydrator;
use WoohooLabs\Yin\JsonApi\Request\RequestInterface;

class ApiActiveHydrator extends AbstractHydrator
{
    public $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return string|array
     */
    protected function getAcceptedType()
    {
        return ApiHelper::getResourceType($this->resource);
    }

    /**
     * @param string $clientGeneratedId
     * @param \WoohooLabs\Yin\JsonApi\Request\RequestInterface $request
     * @param \WoohooLabs\Yin\JsonApi\Exception\ExceptionFactoryInterface $exceptionFactory
     * @throws \WoohooLabs\Yin\JsonApi\Exception\ClientGeneratedIdNotSupported
     * @throws \WoohooLabs\Yin\JsonApi\Exception\ClientGeneratedIdAlreadyExists
     * @throws \Exception
     */
    protected function validateClientGeneratedId(
        $clientGeneratedId,
        RequestInterface $request,
        ExceptionFactoryInterface $exceptionFactory
    ) {
        if ($clientGeneratedId !== null) {
            throw new \CHttpException(403);
        }
    }

    /**
     * @return null
     */
    protected function generateId()
    {
        return null;//CActiveRecord will generate an id on insert
    }

    /**
     * @param object $domainObject
     * @param string $id
     * @return mixed|null
     */
    protected function setId($domainObject, $id)
    {
        // we do not allow changing a model's ID
        return $domainObject;
    }

    /**
     * @param object $domainObject
     * @return callable[]
     */
    protected function getAttributeHydrator($domainObject)
    {
        $ret = [];
        foreach ($domainObject->attributes as $k => $v) {
            if ($k != $domainObject->primaryKey()) {
                $ret[$k] = function ($domainObject, $value, $data, $attribute) {
                    $domainObject->{$attribute} = $value;
                };
            }
        }
        return $ret;
    }

    /**
     * @param object $domainObject
     * @return callable[]
     */
    protected function getRelationshipHydrator($domainObject)
    {
        if (get_class($domainObject) != ApiHelper::$resource) {
            return [];
        }

        $ret = [];
        $relationships = ApiHelper::getExposedRelationships(ApiHelper::getResourceType($domainObject));
        foreach ($relationships as $relationship => $resourceType) {
            $relationDescription = ApiHelper::getResourceRelations($domainObject)[$relationship];

            switch (true) {
                case $relationDescription[0] == \CActiveRecord::HAS_MANY:
                    if (isset($relationDescription['through'])) {
                        $ret[$relationship] = function ($domainObject, $relationship, $data, $relationshipName) {
                            ApiActiveRelationHydratorHelper::hydrateHasManyThroughRelationship(
                                $domainObject,
                                $relationship,
                                $data,
                                $relationshipName
                            );
                        };
                    } else {
                        $ret[$relationship] = function ($domainObject, $relationship, $data, $relationshipName) {
                            ApiActiveRelationHydratorHelper::hydrateHasManyRelationship(
                                $domainObject,
                                $relationship,
                                $data,
                                $relationshipName
                            );
                        };
                    }
                    break;
                case $relationDescription[0] == \CActiveRecord::HAS_ONE:
                    $ret[$relationship] = function ($domainObject, $relationship, $data, $relationshipName) {
                        ApiActiveRelationHydratorHelper::hydrateHasOneRelationship(
                            $domainObject,
                            $relationship,
                            $data,
                            $relationshipName
                        );
                    };
                    break;
                case $relationDescription[0] == \CActiveRecord::BELONGS_TO:
                    $ret[$relationship] = function ($domainObject, $relationship, $data, $relationshipName) {
                        ApiActiveRelationHydratorHelper::hydrateBelongsToRelationship(
                            $domainObject,
                            $relationship,
                            $data,
                            $relationshipName
                        );
                    };
                    break;
            }
        }
        return $ret;
    }
}
