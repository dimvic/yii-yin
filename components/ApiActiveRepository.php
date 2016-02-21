<?php

class ApiActiveRepository
{
    /**
     * @param string $class
     * @param string $id
     * @return CActiveRecord|null
     */
    public static function getByPk($class, $id)
    {
        $model = new $class;
        /**
         * @var CActiveRecord $model
         */
        return $model->findByPk($id);
    }

    public static $saveQueue = [];
    public static $deleteQueue = [];

    /**
     * @param CActiveRecord $domainObject
     */
    public static function save($domainObject)
    {
        $domainObject->validate();
        foreach ($domainObject->errors as $error) {
            ApiHelper::$responseErrors[] = [400, 'VALIDATION_FAILED', "'".ApiHelper::getResourceType(get_class($domainObject))."' validation error", $error];
        }

        foreach (self::$saveQueue as $item) {
            /** @var CActiveRecord $model */
            $model = $item['model'];
            if (isset($item['fill']['id'])) {
                $model->$item['fill']['id'] = 1;
            }
            $model->validate();

            /** @var WoohooLabs\Yin\JsonApi\Schema\ResourceIdentifier $resourceIdentifier */
            $resourceIdentifier = $item['resourceIdentifier'];
            foreach ($model->errors as $attribute=>$errors) {
                foreach ($errors as $error) {
                    ApiHelper::$responseErrors[] = [400, 'VALIDATION_FAILED', "'".ApiHelper::getResourceType(get_class($domainObject))."' relationship '{$resourceIdentifier->getType()}' validation error", $error, ApiActiveRelationHydratorHelper::generateMeta($resourceIdentifier, 'asdads')];
                }
            }
        }

        if (empty(ApiHelper::$responseErrors)) {
            $domainObject->dbConnection->beginTransaction();
            $domainObject->save();

            foreach (self::$saveQueue as $item) {
                /** @var CActiveRecord $model */
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
}
