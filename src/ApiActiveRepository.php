<?php

namespace dimvic\YiiYin;

class ApiActiveRepository
{
    public static $saveQueue = [];
    public static $deleteQueue = [];

    /**
     * @param string $class
     * @param int|string $id
     * @return \CActiveRecord|null
     */
    public static function getById($class, $id)
    {
        $model = new $class;
        /**
         * @var \CActiveRecord $model
         */
        return $model->findByPk($id);
    }

    /**
     * @param object|\CActiveRecord $domainObject
     * @return int|string
     */
    public static function getId($domainObject)
    {
        return $domainObject->id;
    }

    /**
     * @param object|\CActiveRecord $domainObject
     */
    public static function save($domainObject)
    {
        $domainObject->validate();
        foreach ($domainObject->errors as $error) {
            ApiHelper::$responseErrors[] = [
                400,
                'VALIDATION_FAILED',
                "'" . ApiHelper::getObjectResource($domainObject) . "' validation error",
                $error,
            ];
        }

        foreach (self::$saveQueue as $item) {
            /** @var \CActiveRecord $model */
            $model = $item['model'];
            if (isset($item['fill']['id'])) {
                $model->$item['fill']['id'] = 1;
            }
            $model->validate();

            /** @var \WoohooLabs\Yin\JsonApi\Schema\ResourceIdentifier $resourceIdentifier */
            $resourceIdentifier = $item['resourceIdentifier'];
            foreach ($model->errors as $attribute => $errors) {
                foreach ($errors as $error) {
                    ApiHelper::$responseErrors[] = [
                        400,
                        'VALIDATION_FAILED',
                        "'" . ApiHelper::getObjectResource(get_class($domainObject))
                            . "' relationship '{$resourceIdentifier->getType()}' validation error",
                        $error,
                        ApiActiveRelationHydratorHelper::generateMeta(
                            $resourceIdentifier,
                            $resourceIdentifier->getType()
                        )
                    ];
                }
            }
        }

        if (empty(ApiHelper::$responseErrors)) {
            $domainObject->dbConnection->beginTransaction();
            $domainObject->save();

            foreach (self::$saveQueue as $item) {
                /** @var \CActiveRecord $model */
                $model = $item['model'];
                if (isset($item['fill']['id'])) {
                    $model->$item['fill']['id'] = $domainObject->id;
                }
                $model->save();
            }
            foreach (self::$deleteQueue as $model) {
                $model->delete();
            }
            $domainObject->dbConnection->currentTransaction->commit();

            $domainObject->refresh();
        }
    }

    /**
     * @param object|\CActiveRecord $domainObject
     * @return bool
     */
    public static function delete($domainObject)
    {
        $ok = true;
        $domainObject->dbConnection->beginTransaction();

        //first delete related models for relations using "through"
        foreach ($domainObject->relations() as $relationName => $relationConfiguration) {
            if ($relationConfiguration[0] == \CActiveRecord::HAS_MANY && isset($relationConfiguration['through'])) {
                foreach ($domainObject->{$relationName} as $relatedObject) {
                    /** @var \CActiveRecord $relatedObject */
                    $relatedObject->delete();
                }
            }
        }

        //then everything else
        foreach ($domainObject->relations() as $relationName => $relationConfiguration) {
            switch ($relationConfiguration[0]) {
                case \CActiveRecord::HAS_MANY:
                    foreach ($domainObject->{$relationName} as $relatedObject) {
                        /** @var \CActiveRecord $relatedObject */
                        $relatedObject->delete();
                    }
                    break;
                case \CActiveRecord::HAS_ONE:
                    /** @var \CActiveRecord $relatedObject */
                    $relatedObject = $domainObject->{$relationName};
                    if ($relatedObject) {
                        $ok = $ok && $relatedObject->delete();
                    }
                    break;
            }
        }

        //and the model itself
        $ok = $ok && $domainObject->delete();

        if ($ok) {
            $domainObject->dbConnection->currentTransaction->commit();
            return true;
        } else {
            $domainObject->dbConnection->currentTransaction->rollback();
            return false;
        }
    }
}
