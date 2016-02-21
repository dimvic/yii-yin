# json:api module for Yii 1.1

Yii 1.1 module, drop in and configure to automagically expose resources (models) through a [json:api](http://jsonapi.org) 1.0 compatible web service.

Even though developed with CActiveRecord in mind, you should be able to use any CModel out of the box.

Thanks [yii-yin](https://github.com/woohoolabs/yin) for being an amazing library and providing the example this module is heavily based on.

Thanks [Máté Kocsis](https://github.com/kocsismate) for the help and merging of the pull requests.

## Supported functions

* GET /{resource}/{id}
* GET /{resource}/{id}/relationships/{relationship}
* GET /{resource}/{id}/{relationship}
* PATCH /{resource}/{id}
* POST /{resource}
* DELETE /{resource}/{id}

## Usage
Clone the repository in a directory inside `Yii::app()->modulePath` (default: `protected/modules`), for example `protected/modules/api`, configure the module and you have a fully functional web service!

I like to believe configuration is self-explanatory:
```php
return [
    ...
	'modules'=>[
        'api'=>[
            'resources' => [
                'Book' => [//exposed model
                    'type'=>'books',//exposed at api/books
                    'methods' => ['GET', 'POST', 'PATCH'],//API methods supported for this model
                    'exposedRelations' => [//all relations a client may access using the API
                        'book_i18ns'=>'book_i18ns',//relation name => API type (route)
                        'authors'=>'authors',
                        'publisher'=>'publishers',
                    ],
                    'defaultRelations' => [//relations included in response for GET api/book/1
                        'book_i18ns'=>'book_i18ns',//relation name => API type (route)
                        'authors'=>'authors',
                        'publisher'=>'publishers',
                    ],
                ],
                'BookI18n' => [
                    'type'=>'book_i18ns',
                    'methods' => ['GET', 'POST', 'PATCH'],
                ],
                'Author' => [
                    'type'=>'authors',
                    'methods' => ['GET', 'POST', 'PATCH'],
                ],
                'Publisher' => [
                    'type'=>'publishers',
                    'methods' => ['GET', 'POST', 'PATCH'],
                    'exposedRelations' => ['representatives'=>'representatives'],
                    'defaultRelations' => ['representatives'=>'representatives'],
                ],
                'Representative' => [
                    'type'=>'representatives',
                    'methods' => ['GET', 'POST', 'PATCH'],
                ],
            ],
        ],
        ...
	],
	...
];
```

## Demo: example project
Example project can be found [here](https://github.com/dimvic/yii-yin-example). Setup in less than a minute, uses sqlite for db, fully functional.

## Attention
Please note that the controller functions in an ugly way and adding business logic to it will be near impossible, all business logic should be implemented using CActiveRecord events.

## TODO
* Fix `PATCH {"relationship": {"data":null}}`
* GET `/{resource}` paginated
* GET `?include` & eager loading for included relationships
* GET `?filter`
* Review error codes & messages
* Controller filter to validate requests (see [yin-middlewares](https://github.com/woohoolabs/yin-middlewares))
* UUID generator for exposed models (using a behavior)
* Allow mapping of component classes to other than the included ones
