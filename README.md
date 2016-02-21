# Drop-in json:api web service module for Yii 1.1

Yii 1.1 module, drop in and configure the module to automagically expose resources (models) using a [json:api](http://jsonapi.org) 1.0 compatible web service.

Even though developed with CActiveRecord in mind, you should be able to use any CModel out of the box.

Thanks [yii-yin](https://github.com/woohoolabs/yin), this project is practically a hack on top of it's provided example.

Thanks [Máté Kocsis](https://github.com/kocsismate) for the quick replies and merging of the pull requests.

## Supported functions

* GET /{resource}/{id}
* GET /{resource}/{id}/relationships/{relationship}
* PATCH /{resource}/{id}
* POST /{resource}

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

## Example project
See example project [ here ](https://github.com/dimvic/yii-yin-example).

## Attention
Please note that the controller functions in an ugly way and adding business logic to it will be near impossible, all business logic should be implemented using CActiveRecord events.

## TODO
* Review error codes & messages
* Eager loading for included relationships
* Fix `PATCH {"relationship": {"data":null}}`
* ~~DELETE requests~~
* GET paginated `/{resource}``
* GET `?include`, `?filter`
* Controller filter to validate requests (see [yin-middlewares](https://github.com/woohoolabs/yin-middlewares))
* UUID generator for exposed models (using a behavior)
* Configuration parameters to allow use of component classes other than the included ones
