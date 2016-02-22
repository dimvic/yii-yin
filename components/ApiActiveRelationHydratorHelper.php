<?php

use WoohooLabs\Yin\JsonApi\Schema\ResourceIdentifier;

class ApiActiveRelationHydratorHelper {
    /**
     * @param CActiveRecord $domainObject
     * @param WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToOneRelationship $relationship
     * @param array $data
     * @param string $relationshipName
     */
    public static function hydrateBelongsToRelationship($domainObject, $relationship, $data, $relationshipName)
    {
        $relationName = $relationshipName;//ApiHelper::getTypeRelation($domainObject, $relationshipName);
        $relationConfiguration = $domainObject->relations()[$relationName];

        $relationClass = $relationConfiguration[1];
        $relationModel = new $relationClass;

        $resourceIdentifier = $relationship->getResourceIdentifier();

        /**
         * @var CActiveRecord $relationModel
         */
        $relatedModel = $relationModel->findByPk($resourceIdentifier->getId());

        /**
         * @var CActiveRecord $relatedModel
         */
        if ($relatedModel) {
            $domainObject->{$relationConfiguration[2]} = $relatedModel->id;
        } else {
            $title = "A resource of type '{$resourceIdentifier->getType()}' with ID '{$resourceIdentifier->getId()}' was not found";
            ApiHelper::$responseErrors[] = [404, null, $title, "{$title} when trying to add it as a relationship to a resource of type '".ApiHelper::getCurrentResource()."'.", self::generateMeta($resourceIdentifier, $relationshipName)];
        }
    }

    /**
     * @param CActiveRecord $domainObject
     * @param WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToOneRelationship $relationship
     * @param array $data
     * @param string $relationshipName
     */
    public static function hydrateHasOneRelationship($domainObject, $relationship, $data, $relationshipName)
    {
        self::unacceptable($relationship->getResourceIdentifier(), $relationshipName);
    }

    /**
     * @param CActiveRecord $domainObject
     * @param WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToManyRelationship $relationship
     * @param array $data
     * @param string $relationshipName
     */
    public static function hydrateHasManyThroughRelationship($domainObject, $relationship, $data, $relationshipName)
    {
        $relationConfiguration = $domainObject->relations()[$relationshipName];
        $throughConfiguration = $domainObject->relations()[$relationConfiguration['through']];

        $throughClass = $throughConfiguration[1];

        $resourceThroughKey = $throughConfiguration[2];
        $relationThroughKey = is_array($relationConfiguration[2]) ? array_keys($relationConfiguration[2])[0] : $relationConfiguration[2];

        $existing = [];
        foreach ($domainObject->{$relationshipName} as $item) {
            $existing[$item->id] = $item;
        }

        /** @var \WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToManyRelationship $relationship */
        foreach ($relationship->getResourceIdentifiers() as $resourceIdentifier) {
            $id = $resourceIdentifier->getId();

            if (!isset($existing[$id])) {
                $junction = new $throughClass;
//                $junction->{$resourceThroughKey} = $domainObject->id;
                $junction->{$relationThroughKey} = $id;
                ApiActiveRepository::$saveQueue[] = [
                    'model' => $junction,
                    'resourceIdentifier' => $resourceIdentifier,
                    'fill' => ['id' => $resourceThroughKey],
                ];
            } else {
                unset($existing[$id]);
            }
        }

        ApiActiveRepository::$deleteQueue = array_merge(ApiActiveRepository::$deleteQueue, $existing);
    }

    /**
     * @param CActiveRecord $domainObject
     * @param WoohooLabs\Yin\JsonApi\Hydrator\Relationship\ToManyRelationship $relationship
     * @param array $data
     * @param string $relationshipName
     */
    public static function hydrateHasManyRelationship($domainObject, $relationship, $data, $relationshipName)
    {
        foreach ($relationship->getResourceIdentifiers() as $resourceIdentifier) {
            self::unacceptable($resourceIdentifier, $relationshipName);
        }
    }

    /**
     * @param ResourceIdentifier|array $resourceIdentifier
     * @param string $relationshipName
     */
    public static function unacceptable($resourceIdentifier, $relationshipName)
    {
        ApiHelper::$responseErrors[] = [
            406,
            null,
            "Use route '/{$resourceIdentifier->getType()}' to create related resources of this type",
            null,
            self::generateMeta($resourceIdentifier, $relationshipName),
        ];
    }

    /**
     * @var ResourceIdentifier|array $resourceIdentifier
     * @param string $relationshipName
     * @return array
     */
    public static function generateMeta($resourceIdentifier, $relationshipName)
    {
        return [
            $relationshipName=>[
                'type'=>$resourceIdentifier->getType(),
                'id'=>$resourceIdentifier->getid(),
                'meta'=>$resourceIdentifier->getMeta()
            ],
        ];
    }
}
