<?php

namespace dimvic\YiiYin;

use WoohooLabs\Yin\JsonApi\JsonApi;

class ApiActionHelper
{
    public function GET()
    {
        $domainObject = ApiActiveRepository::getById(ApiHelper::$resource, ApiHelper::$id);

        if (!$domainObject) {
            ApiHelper::$responseErrors[] = [404];
            return;
        }

        if (ApiHelper::$relationship) {
            $document = new ApiActiveDocument(new ApiActiveResourceTransformer);

            // Responding with "200 Ok" status code along with the requested relationship document
            ApiHelper::$response = ['ok', $document, $domainObject, ApiHelper::$relationship];
        } else {
            $document = new ApiActiveDocument(new ApiActiveResourceTransformer);

            // Responding with "200 Ok" status code along with the domainObject document
            ApiHelper::$response = ['ok', $document, $domainObject];
        }
    }

    /**
     * @param \WoohooLabs\Yin\JsonApi\JsonApi $jsonApi
     */
    public function POST(JsonApi $jsonApi)
    {
        $domainObject = $jsonApi->hydrate(new ApiActiveHydrator(ApiHelper::$resource), new ApiHelper::$resource);
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
    public function PATCH(JsonApi $jsonApi)
    {
        $domainObject = ApiActiveRepository::getById(ApiHelper::$resource, ApiHelper::$id);

        if (!$domainObject) {
            ApiHelper::$responseErrors[] = [404];
            return;
        }

        $domainObject = $jsonApi->hydrate(new ApiActiveHydrator(ApiHelper::$resource), $domainObject);
        ApiActiveRepository::save($domainObject);

        if (empty(ApiHelper::$responseErrors)) {
            $document = new ApiActiveDocument(new ApiActiveResourceTransformer);

            // Responding with "200 Ok" status code along with the domain object document
            ApiHelper::$response = ['ok', $document, $domainObject];
        }
    }

    public function DELETE()
    {
        $domainObject = ApiActiveRepository::getById(ApiHelper::$resource, ApiHelper::$id);

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
