<?php

namespace dimvic\YiiYin;

use WoohooLabs\Yin\JsonApi\Schema\Link;
use WoohooLabs\Yin\JsonApi\Schema\Links;
use WoohooLabs\Yin\JsonApi\Schema\Relationship\ToManyRelationship;
use WoohooLabs\Yin\JsonApi\Schema\Relationship\ToOneRelationship;
use WoohooLabs\Yin\JsonApi\Transformer\AbstractResourceTransformer;

class ApiActiveResourceTransformer extends AbstractResourceTransformer
{
    public $transformer;

    /**
     * @param object $domainObject
     * @return string
     */
    public function getType($domainObject)
    {
        return ApiHelper::getResourceType($domainObject);
    }

    /**
     * @param object $domainObject
     * @return string
     */
    public function getId($domainObject)
    {
        return ApiActiveRepository::getId($domainObject);
    }

    /**
     * @param object $domainObject
     * @return array
     */
    public function getMeta($domainObject)
    {
        return [];
    }

    /**
     * @param object $domainObject
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
     * @param object $domainObject
     * @return string
     */
    public function getSelfLinkHref($domainObject)
    {
        return \Yii::app()->createUrl('/api', ['model' => $domainObject]);
    }

    /**
     * @param object $domainObject
     * @return callable[]
     */
    public function getAttributes($domainObject)
    {
        $ret = [];
        foreach ($domainObject->attributes as $k => $v) {
            if ($k != $domainObject->primaryKey()) {
                $ret[$k] = function ($domainObject, $request, $attribute) {
                    return $domainObject->{$attribute};
                };
            }
        }
        return $ret;
    }

    /**
     * @param object $domainObject
     * @return array
     */
    public function getDefaultIncludedRelationships($domainObject)
    {
        return ApiHelper::getDefaultRelationships(ApiHelper::getResourceType($domainObject));
    }

    /**
     * @param object $domainObject
     * @return callable[]
     */
    public function getRelationships($domainObject)
    {
        if (get_class($domainObject) != ApiHelper::$resource) {
            return [];
        }

        !$this->transformer && $this->transformer = new self;

        $ret = [];
        foreach ($this->getDefaultIncludedRelationships($domainObject) as $relationship => $resourceType) {
            $ret[$relationship] = function ($domainObject, $request, $relationship) {
                $relationDescription = ApiHelper::getResourceRelations($domainObject)[$relationship];
                switch ($relationDescription[0]) {
                    case \CActiveRecord::HAS_ONE:
                    case \CActiveRecord::BELONGS_TO:
                        $relationshipObject = ToOneRelationship::create();
                        break;
                    default:
                        $relationshipObject = ToManyRelationship::create();
                }
                return $relationshipObject
                    ->setLinks(
                        new Links(
                            $this->getSelfLinkHref($domainObject),
                            [
                                "self" => new Link("/relationships/{$relationship}")
                            ]
                        )
                    )
                    ->setData($domainObject->{$relationship}, $this->transformer);
            };
        }

        return $ret;
    }
}
