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
     * @param CActiveRecord $domainObject
     * @return string
     */
    public function getType($domainObject)
    {
        return ApiHelper::getResourceType(get_class($domainObject));
    }

    /**
     * @param CActiveRecord $domainObject
     * @return string
     */
    public function getId($domainObject)
    {
        return $domainObject->id;
    }

    /**
     * @param CActiveRecord $domainObject
     * @return array
     */
    public function getMeta($domainObject)
    {
        return [];
    }

    /**
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
        return Yii::app()->createUrl('/api', ['model' => $domainObject]);
    }

    /**
     * @param CActiveRecord $domainObject
     * @return callable[]
     */
    public function getAttributes($domainObject)
    {
        $ret = [];
        foreach ($domainObject->attributes as $k => $v) {
            if ($k != $domainObject->primaryKey()) {
                $ret[$k] = function (CActiveRecord $domainObject, $request, $attribute) {
                    return $domainObject->{$attribute};
                };
            }
        }
        return $ret;
    }

    /**
     * @param CActiveRecord $domainObject
     * @return array
     */
    public function getDefaultIncludedRelationships($domainObject)
    {
        return ApiHelper::getDefaultRelations(get_class($domainObject));
    }

    /**
     * @param CActiveRecord $domainObject
     * @return callable[]
     */
    public function getRelationships($domainObject)
    {
        if (get_class($domainObject) != ApiHelper::getCurrentResource()) {
            return [];
        }

        !$this->transformer && $this->transformer = new self;

        $ret = [];
        foreach ($this->getDefaultIncludedRelationships($domainObject) as $relation) {
            $ret[$relation] = function (CActiveRecord $domainObject, $request, $relationName) {
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
                    ->setData($domainObject->{$relationName}, $this->transformer);
            };
        }
        return $ret;
    }
}
