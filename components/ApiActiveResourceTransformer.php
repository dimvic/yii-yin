<?php

use WoohooLabs\Yin\JsonApi\Schema\Link;
use WoohooLabs\Yin\JsonApi\Schema\Links;
use WoohooLabs\Yin\JsonApi\Schema\Relationship\ToManyRelationship;
use WoohooLabs\Yin\JsonApi\Schema\Relationship\ToOneRelationship;
use WoohooLabs\Yin\JsonApi\Transformer\AbstractResourceTransformer;

class ApiActiveResourceTransformer extends AbstractResourceTransformer
{
    public $transformer;

    /**
     * Provides information about the "type" section of the current resource.
     *
     * The method returns the type of the current resource.
     *
     * @param CActiveRecord $domainObject
     * @return string
     */
    public function getType($domainObject)
    {
        return ApiHelper::getResourceType(get_class($domainObject));
    }

    /**
     * Provides information about the "id" section of the current resource.
     *
     * The method returns the ID of the current resource which should be a UUID.
     *
     * @param CActiveRecord $domainObject
     * @return string
     */
    public function getId($domainObject)
    {
        return $domainObject->id;
    }

    /**
     * Provides information about the "meta" section of the current resource.
     *
     * The method returns an array of non-standard meta information about the resource. If
     * this array is empty, the section won't appear in the response.
     *
     * @param CActiveRecord $domainObject
     * @return array
     */
    public function getMeta($domainObject)
    {
        return [];
    }

    /**
     * Provides information about the "links" section of the current resource.
     *
     * The method returns a new Links schema object if you want to provide linkage
     * data about the resource or null if it should be omitted from the response.
     *
     * @param CActiveRecord $domainObject
     * @return \WoohooLabs\Yin\JsonApi\Schema\Links|null
     */
    public function getLinks($domainObject)
    {
        return Links::createWithoutBaseUri(
            [
                "self" => new Link($this->getSelfLinkHref($domainObject))
            ]
        );
    }

    /**
     * @param CActiveRecord $domainObject
     * @return string
     */
    public function getSelfLinkHref(CActiveRecord $domainObject)
    {
        return Yii::app()->createUrl('/api', ['model'=>$domainObject]);
    }

    /**
     * Provides information about the "attributes" section of the current resource.
     *
     * The method returns an array of attributes if you want the section to
     * appear in the response or null if it should be omitted. In the returned array,
     * the keys signify the attribute names, while the values are closures receiving the
     * domain object as an argument, and they should return the value of the corresponding
     * attribute.
     *
     * @param CActiveRecord $domainObject
     * @return array
     */
    public function getAttributes($domainObject)
    {
        $ret = [];
        foreach ($domainObject->attributes as $k=>$v) {
            if ($k!=$domainObject->primaryKey()) {
                $ret[$k] = function(CActiveRecord $domainObject, $request, $attribute) { return $domainObject->{$attribute}; };
            }
        }
        return $ret;
    }

    /**
     * Returns an array of relationship names which are included in the response by default.
     *
     * @param CActiveRecord $domainObject
     * @return array
     */
    public function getDefaultIncludedRelationships($domainObject)
    {
        return ApiHelper::getDefaultRelations(get_class($domainObject));
    }

    /**
     * Provides information about the "relationships" section of the current resource.
     *
     * The method returns an array where the keys signify the relationship names,
     * while the values are closures receiving the domain object as an argument,
     * and they should return a new relationship instance (to-one or to-many).
     *
     * @param CActiveRecord $domainObject
     * @return array
     */
    public function getRelationships($domainObject)
    {
        if (get_class($domainObject)!=ApiHelper::getCurrentResource()) {
            return [];
        }

        !$this->transformer && $this->transformer = new self;

        $ret = [];
        foreach ($this->getDefaultIncludedRelationships($domainObject) as $relation) {
            $ret[$relation] = function(CActiveRecord $domainObject, $request, $relationName) {
                $relationDescription = ApiHelper::getCurrentModelRelations()[$relationName];
                switch ($relationDescription[0]) {
                    case CActiveRecord::HAS_ONE:
                    case CActiveRecord::BELONGS_TO:
                        $relationship = ToOneRelationship::create();
                        break;
                    default:
                        $relationship = ToManyRelationship::create();
                }
                return $relationship
                    ->setLinks(
                        new Links(
                            $this->getSelfLinkHref($domainObject),
                            [
                                "self" => new Link("/relationships/{$relationName}")
                            ]
                        )
                    )
                    ->setData($domainObject->{$relationName}, $this->transformer)
                ;
            };
        }
        return $ret;
    }
}
