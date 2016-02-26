# json:api module for Yii 1.1

[![Packagist package][ico-packagist]][link-packagist]
[![License][ico-license]](LICENSE.md)

Yii 1.1 module, drop in and configure to automagically expose resources (CActiveRecord models) through a [json:api](http://jsonapi.org) 1.0 compatible web service.

Thanks [yin](https://github.com/woohoolabs/yin) for being an amazing library and providing the example this module is heavily based on.

Thanks [Máté Kocsis](https://github.com/kocsismate) for the help and merging of the pull requests.

## Supported functions

* GET /{type}/{id}
* GET /{type}/{id}/relationships/{relationship}
* GET /{type}/{id}/{relationship}
* PATCH /{type}/{id}
* POST /{type}
* DELETE /{type}/{id}

## Usage
Simply configure the module and you have a fully functional HATEOAS web service for your models.

I like to believe configuration is self-explanatory, the only thing you should watch out for is that you need to configure a type (even with an active methods array) for each exposed relationship type:
```php
return [
    ...
   'modules' => [
        'yiiyin' => [
            'route' => 'api',//expose the module at /api
            'controllerMap' => [//only add this if you want all requests logged
                'default'=> [
                    'class'=>'dimvic\\YiiYin\\ApiLogController',//log using Yii::log($log, 'info', 'json:api')
                ],
            ],
            'resources' => [
                'Book' => [//exposed model
                    'type' => 'books',//exposed at api/books
                    'methods' => ['GET', 'POST', 'PATCH', 'DELETE'],//API methods supported for this model
                    'exposedRelationships' => [//all relations a client may access using the API
                        'book_i18ns' => 'book_i18ns',//relation name => API type (route)
                        'authors' => 'authors',
                        'publisher' => 'publishers',
                    ],
                    'defaultRelationships' => [//all relations a client may access using the API
                        'book_i18ns' => 'book_i18ns',//relation name => API type (route)
                        'authors' => 'authors',
                        'publisher' => 'publishers',
                    ],
                ],
                'BookI18n' => [
                    'type' => 'book_i18ns',
                    'methods' => ['GET', 'POST', 'PATCH'],
                ],
                'Author' => [
                    'type' => 'authors',
                    'methods' => ['GET', 'POST', 'PATCH'],
                ],
                'Publisher' => [
                    'type' => 'publishers',
                    'methods' => ['GET', 'POST', 'PATCH'],
                    'exposedRelationships' => ['representatives' => 'representatives'],
                    'defaultRelationships' => ['representatives' => 'representatives'],
                ],
                'Representative' => [
                    'type' => 'representatives',
                    'methods' => ['GET', 'POST', 'PATCH'],
                ],
            ],
        ],
        ...
    ],
    ...
    'components' => [
        'urlManager' => [
            'urlFormat' => 'path',
            'showScriptName' => false,
            'rules' => [
                ['class' => 'dimvic\\YiiYin\\ApiUrlRule'],
                ...
            ],
        ],
    ],
    ....
];
```

## Demo
Example project can be found [here](https://github.com/dimvic/yii-yin-example). Setup it up in less than a minute.

## TODO
* Fix `PATCH {"relationship": {"data":null}}`
* GET `/{resource}` paginated
* GET `?include` & eager loading for included relationships
* GET `?filter`
* Review error codes & messages
* Controller filter to validate requests (see [yin-middlewares](https://github.com/woohoolabs/yin-middlewares))
* UUID generator for exposed models (using a behavior)
* Allow use customized of customized repositories, transformers and hydrators

[ico-packagist]: https://img.shields.io/badge/packagist-dev-lightgrey.svg?style=flat-square
[ico-license]: https://img.shields.io/packagist/l/dimvic/yiiyin.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/dimvic/yiiyin
