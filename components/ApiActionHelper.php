<?php

class ApiActionHelper
{
    public $resource;
    public $resource_id;
    public $related;

    public function __construct()
    {
        $this->resource = ApiHelper::getCurrentResource();
        $this->resource_id = ApiHelper::getCurrentId();
        $this->related = ApiHelper::getRequestedRelated();
    }

    public function GET()
    {
        $domainObject = ApiActiveRepository::getByPk($this->resource, $this->resource_id);

        if (!$domainObject) {
            ApiHelper::$responseErrors[] = [404];
            return;
        }

        if ($this->related) {
            $relationshipName = $this->related;

            if (empty($relationshipName)) {
                ApiHelper::$responseErrors[] = [400, 'Missing request parameter!'];
                return;
            }

            $relations = ApiHelper::getExposedRelations(get_class($domainObject));
            if (!in_array($relationshipName, $relations)) {
                ApiHelper::$responseErrors[] = [404, 'Relationship not present!'];
                return;
            }

            $document = new ApiActiveDocument(new ApiActiveResourceTransformer);

            // Responding with "200 Ok" status code along with the requested relationship document
            ApiHelper::$response = ['ok', $document, $domainObject, $relationshipName];
        } else {
            $document = new ApiActiveDocument(new ApiActiveResourceTransformer);

            // Responding with "200 Ok" status code along with the domainObject document
            ApiHelper::$response = ['ok', $document, $domainObject];
        }
    }

    /**
     * @param \WoohooLabs\Yin\JsonApi\JsonApi $jsonApi
     */
    public function POST(\WoohooLabs\Yin\JsonApi\JsonApi $jsonApi)
    {
        $domainObject = $jsonApi->hydrate(new ApiActiveHydrator($this->resource), new $this->resource);
        ApiActiveRepository::save($domainObject);

        if (empty(ApiHelper::$responseErrors)) {
            $document = new ApiActiveDocument(new ApiActiveResourceTransformer);

            // Responding with "201 Created" status code along with the domainObject document
            ApiHelper::$response = ['created', $document, $domainObject];
        }
    }

    /**
     * @param \WoohooLabs\Yin\JsonApi\JsonApi $jsonApi
     */
    public function PATCH(\WoohooLabs\Yin\JsonApi\JsonApi $jsonApi)
    {
        $domainObject = ApiActiveRepository::getByPk($this->resource, $this->resource_id);

        if (!$domainObject) {
            ApiHelper::$responseErrors[] = [404];
            return;
        }

        $domainObject = $jsonApi->hydrate(new ApiActiveHydrator($this->resource), $domainObject);
        ApiActiveRepository::save($domainObject);

        if (empty(ApiHelper::$responseErrors)) {
            $document = new ApiActiveDocument(new ApiActiveResourceTransformer);

            // Responding with "200 Ok" status code along with the domain object document
            ApiHelper::$response = ['ok', $document, $domainObject];
        }
    }

    public function DELETE()
    {
        $domainObject = ApiActiveRepository::getByPk($this->resource, $this->resource_id);

        if (!$domainObject) {
            ApiHelper::$responseErrors[] = [404];
            return;
        }

        if (empty(ApiHelper::$responseErrors)) {
            ApiActiveRepository::delete($domainObject);

            // Responding with "204 No Content" status code
            ApiHelper::$response = ['noContent'];
        }
    }
}
