<?php

namespace dimvic\YiiYin;

use WoohooLabs\Yin\JsonApi\Exception\ExceptionFactory;
use WoohooLabs\Yin\JsonApi\Exception\JsonApiExceptionInterface;
use WoohooLabs\Yin\JsonApi\JsonApi;
use WoohooLabs\Yin\JsonApi\Request\Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;
use function array_shift;
use function file_get_contents;
use function json_decode;

class ApiController extends \CController
{
    /**
     * @var \WoohooLabs\Yin\JsonApi\Request\Request
     */
    public $request;

    /**
     * @var \WoohooLabs\Yin\JsonApi\JsonApi
     */
    public $jsonApi;

    public function init()
    {
        \Yii::app()->setComponents([
            'errorHandler' => [
                'class' => 'dimvic\\YiiYin\\ApiErrorHandler',
            ],
        ]);

        // Initializing JsonApi
        $this->request = new Request(ServerRequestFactory::fromGlobals());
        if (empty($this->request->getResource())) {
            $this->request = new Request(ServerRequestFactory::fromGlobals($_SERVER, ['null' => null], json_decode(file_get_contents('php://input'), true)));
        }
        $this->jsonApi = new JsonApi($this->request, new Response(), new ExceptionFactory());

        parent::init();
    }

    public function actionIndex()
    {
        $route = explode('/', \Yii::app()->request->pathInfo);
        array_shift($route);//first item is always the module's route

        $type = !empty($route[0]) ? $route[0] : null;
        $id = !empty($route[1]) ? $route[1] : null;
        $relationships = !empty($route[2]) ? $route[2] : null;
        $relationship = !empty($route[3]) ? $route[3] : null;

        if (!$type) {
            ApiHelper::$responseErrors[] = [405];
        } elseif (!ApiHelper::getTypeResource($type)) {
            ApiHelper::$responseErrors[] = [404];
        } else {
            //disallow POST to /book/1
            if ($id && $this->request->getMethod() == 'POST') {
                ApiHelper::$responseErrors[] = [403];
            }

            if ($relationships) {
                $relationship = $relationships == 'relationships' ? $relationship : $relationships;

                if (empty(ApiHelper::getExposedRelationships($type)[$relationship])) {
                    ApiHelper::$responseErrors[] = [404];
                }

                //GET is the only supported method for relationships
                if (empty(ApiHelper::$responseErrors) && $this->request->getMethod() != 'GET') {
                    ApiHelper::$responseErrors[] = [403];
                }
            }

            if (empty(ApiHelper::$responseErrors)) {
                try {
                    ApiHelper::init($type, $id, $relationship);

                    $actions = ApiHelper::getMethods($type);
                    $method = $this->request->getMethod();

                    $helper = new ApiActionHelper;
                    if (in_array($method, $actions)) {
                        call_user_func([$helper, $method], $this->jsonApi);
                    } else {
                        ApiHelper::$responseErrors[] = [405];
                    }
                } catch (JsonApiExceptionInterface $e) {
                    $this->sendError($e);
                }
            }
        }

        $this->respond();
    }

    public function actionError()
    {
        $exception = \Yii::app()->errorHandler->exception;
        if ($exception && $exception instanceof \CHttpException) {
            $code = $exception->statusCode;
            $detail = YII_DEBUG ? $exception->getMessage() : null;
        } else {
            $code = 500;
            $detail = YII_DEBUG && \Yii::app()->errorHandler->error ? \Yii::app()->errorHandler->error : null;
        }
        ApiHelper::$responseErrors[] = [$code, $detail];
        $this->respond();
    }

    public function sendErrors()
    {
        $this->sendError(new ApiError());
    }

    /**
     * @param $e      JsonApiExceptionInterface
     * @param $detail string
     */
    public function sendError($e, $detail = null)
    {
        $response = $detail ? new Response($detail) : new Response;
        $this->emit($e->getErrorDocument()->getResponse($response));
    }

    public function respond()
    {
        if (empty(ApiHelper::$responseErrors)) {
            $response = ApiHelper::$response;
            switch (true) {
                case count($response) == 3:
                    $this->emit($this->jsonApi->respond()->{$response[0]}($response[1], $response[2]));
                    break;
                case count($response) == 4:
                    $this->emit($this->jsonApi->respondWithRelationship($response[3])->{$response[0]}(
                        $response[1],
                        $response[2]
                    ));
                    break;
                case count($response) == 1:
                    $this->emit($this->jsonApi->respond()->{$response[0]}());
                    break;
                default:
                    ApiHelper::$responseErrors[] = [500];
            }
        } elseif (!empty(ApiHelper::$responseErrors)) {
            $this->sendErrors();
        }
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function emit($response)
    {
        $emitter = new SapiEmitter();
        $emitter->emit($response);
    }
}
