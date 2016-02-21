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
     * Determines which resource type or types can be accepted by the hydrator.
     *
     * @return string|array
     */
    protected function getAcceptedType()
    {
        return ApiHelper::getResourceType($this->resource);
    }

    /**
     * Validates a client-generated ID.
     *
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
     * Produces a new ID for the domain objects.
     *
     * UUID-s are preferred according to the JSON API specification.
     *
     * @return null
     */
    protected function generateId()
    {
        return null;//CActiveRecord will generate an id on insert
    }

    /**
     * Sets the given ID for the domain object.
     *
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
     * Provides the attribute hydrators.
     *
     * @param CActiveRecord $domainObject
     * @return array
     */
    protected function getAttributeHydrator($domainObject)
    {
        $ret = [];
        foreach ($domainObject->attributes as $k=>$v) {
            if ($k!=$domainObject->primaryKey()) {
                $ret[$k] = function(CActiveRecord $domainObject, $value, $attribute)  { $domainObject->{$attribute} = $value; };
            }
        }
        return $ret;
    }

    /**
     * Provides the relationship hydrators.
     *
     * @param CActiveRecord $domainObject
     * @return array
     */
    protected function getRelationshipHydrator($domainObject)
    {
        if (get_class($domainObject)!=ApiHelper::getCurrentResource()) {
            return [];
        }

        $relations = ApiHelper::getCurrentModelRelations();
        $ret = [];
        foreach (ApiHelper::getExposedRelations(get_class($domainObject)) as $relation=>$relatedType) {
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

    /*
     * HydratorTrait
     */

    /**
     * @param mixed $domainObject
     * @param array $data
     * @return mixed
     */
    protected function hydrateAttributes($domainObject, array $data)
    {
        if (empty($data["attributes"])) {
            return $domainObject;
        }

        $attributeHydrator = $this->getAttributeHydrator($domainObject);
        foreach ($attributeHydrator as $attribute => $hydrator) {
            if (isset($data["attributes"][$attribute]) === false) {
                continue;
            }

            $result = $hydrator($domainObject, $data["attributes"][$attribute], $attribute, $data);
            if ($result) {
                $domainObject = $result;
            }
        }

        return $domainObject;
    }

    /*
     * HydratorTrait
     */

    /**
     * @param string $relationshipName
     * @param \Closure $hydrator
     * @param mixed $domainObject
     * @param ToOneRelationship|ToManyRelationship $relationshipObject
     * @param array $data
     * @param \WoohooLabs\Yin\JsonApi\Exception\ExceptionFactoryInterface $exceptionFactory
     * @return mixed
     * @throws \WoohooLabs\Yin\JsonApi\Exception\RelationshipTypeInappropriate
     * @throws \Exception
     */
    protected function getRelationshipHydratorResult(
        $relationshipName,
        \Closure $hydrator,
        $domainObject,
        $relationshipObject,
        array $data,
        ExceptionFactoryInterface $exceptionFactory
    ) {
        // Checking if the current and expected relationship types match
        $relationshipType = $this->getRelationshipType($relationshipObject);
        $expectedRelationshipType = $this->getRelationshipType($this->getArgumentTypeHintFromClosure($hydrator));
        if ($expectedRelationshipType !== null && $relationshipType !== $expectedRelationshipType) {
            throw $exceptionFactory->createRelationshipTypeInappropriateException(
                $relationshipName,
                $relationshipType,
                $expectedRelationshipType
            );
        }

        // Returning if the hydrator returns the hydrated domain object
        $value = $hydrator($domainObject, $relationshipObject, $data, $relationshipName);
        if ($value) {
            return $value;
        }

        // Returning the domain object which was mutated but not returned by the hydrator
        return $domainObject;
    }
}
