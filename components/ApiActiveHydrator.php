<?php

use WoohooLabs\Yin\JsonApi\Exception\ExceptionFactoryInterface;
use WoohooLabs\Yin\JsonApi\Hydrator\AbstractHydrator;
use WoohooLabs\Yin\JsonApi\Request\RequestInterface;
use WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToManyRelationship;
use WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToOneRelationship;

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
            throw new CHttpException(403);
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
     * @param CActiveRecord $domainObject
     * @param string $id
     * @return mixed|null
     */
    protected function setId($domainObject, $id)
    {
        // we do not allow changing a model's ID
        return $domainObject;
    }

    /**
     * @param CActiveRecord $domainObject
     * @return callable[]
     */
    protected function getAttributeHydrator($domainObject)
    {
        $ret = [];
        foreach ($domainObject->attributes as $k=>$v) {
            if ($k!=$domainObject->primaryKey()) {
                $ret[$k] = function(CActiveRecord $domainObject, $value, $data, $attribute)  { $domainObject->{$attribute} = $value; };
            }
        }
        return $ret;
    }

    /**
     * @param CActiveRecord $domainObject
     * @return callable[]
     */
    protected function getRelationshipHydrator($domainObject)
    {
        if (get_class($domainObject)!=ApiHelper::getCurrentResource()) {
            return [];
        }

        $relations = ApiHelper::getCurrentModelRelations();
        $ret = [];
        foreach (ApiHelper::getExposedRelations(get_class($domainObject)) as $relation) {
            switch (true) {
                case $relations[$relation][0]==CActiveRecord::HAS_MANY:
                    if (isset($relations[$relation]['through'])) {
                        $ret[$relation] = function(CActiveRecord $domainObject, $relationship, $data, $relationshipName) { ApiActiveRelationHydratorHelper::hydrateHasManyThroughRelationship($domainObject, $relationship, $data, $relationshipName); };
                    } else {
                        $ret[$relation] = function(CActiveRecord $domainObject, $relationship, $data, $relationshipName) { ApiActiveRelationHydratorHelper::hydrateHasManyRelationship($domainObject, $relationship, $data, $relationshipName); };
                    }
                    break;
                case $relations[$relation][0]==CActiveRecord::HAS_ONE:
                    $ret[$relation] = function(CActiveRecord $domainObject, $relationship, $data, $relationshipName) { ApiActiveRelationHydratorHelper::hydrateHasOneRelationship($domainObject, $relationship, $data, $relationshipName); };
                    break;
                case $relations[$relation][0]==CActiveRecord::BELONGS_TO:
                    $ret[$relation] = function(CActiveRecord $domainObject, $relationship, $data, $relationshipName) { ApiActiveRelationHydratorHelper::hydrateBelongsToRelationship($domainObject, $relationship, $data, $relationshipName); };
                    break;
            }
        }
        return $ret;
    }
}
