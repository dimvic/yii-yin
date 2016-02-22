<?php

use WoohooLabs\Yin\JsonApi\JsonApi;
use WoohooLabs\Yin\JsonApi\Exception\ExceptionFactory;
use WoohooLabs\Yin\JsonApi\Request\Request;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;

use WoohooLabs\Yin\JsonApi\Exception\JsonApiExceptionInterface;

class ApiController extends CController
{
    /**
     * @var WoohooLabs\Yin\JsonApi\Request\Request
     */
    public $request;

    /**
     * @var WoohooLabs\Yin\JsonApi\JsonApi
     */
    public $jsonApi;

    public function init()
    {
        Yii::app()->setComponents([
            'errorHandler' => [
                'errorAction' => 'api/default/error',
            ],
        ]);

        // Initializing JsonApi
        $this->request = new Request(ServerRequestFactory::fromGlobals());
        $this->jsonApi = new JsonApi($this->request, new Response(), new ExceptionFactory());
    }

    public function beforeAction($action)
    {
        if (!isset($_GET['route'])) {
            ApiHelper::$responseErrors[] = [405];
            return false;
        }

        $route = explode('/', $_REQUEST['route']);
        foreach ($route as $k => $v) {
            $route[$k] = urldecode($v);
        }

        if (!isset(ApiHelper::getRoutes()[$route[0]])) {
            ApiHelper::$responseErrors[] = [405];
        }

        ApiHelper::$current_type = $route[0];
        ApiHelper::$current_id = isset($route[1]) ? $route[1] : 0;

        if (!empty($route[2])) {
            $r = !empty($route[3]) ? $route[3] : null;

            switch (true) {
                case in_array($route[2], ApiHelper::getExposedRelations(get_class(ApiHelper::getCurrentModel()))):
                    ApiHelper::$current_related = $route[2];
                    break;
                case $route[2] == 'relationships':
                    ApiHelper::$current_related = $r;
                    break;
                default:
                    ApiHelper::$responseErrors[] = [404];
            }
            if ($this->request->getMethod() != 'GET') {
                ApiHelper::$responseErrors[] = [403];
            }
        }

        return parent::beforeAction($action);
    }

    public function afterAction($action)
    {
        parent::afterAction($action);

        if (empty(ApiHelper::$responseErrors)) {
            try {
                $actions = ApiHelper::getCurrentMethods();
                $method = $this->request->getMethod();

                $helper = new ApiActionHelper();
                if (in_array($method, $actions)) {
                    call_user_func([$helper, $method], $this->jsonApi);
                } else {
                    ApiHelper::$responseErrors[] = [405];
                }
            } catch (JsonApiExceptionInterface $e) {
                $this->sendError($e);
            }
        }
        $this->respond();
    }

    public function actionError()
    {
        $exception = Yii::app()->errorHandler->exception;
        if ($exception && $exception instanceof CHttpException) {
            $code = $exception->statusCode;
            $detail = YII_DEBUG ? $exception->getMessage() : null;
        } else {
            $code = 500;
            $detail = YII_DEBUG && Yii::app()->errorHandler->error ? Yii::app()->errorHandler->error : null;
        }
        ApiHelper::$responseErrors[] = [$code, $detail];
        $this->sendErrors();
    }

    public function sendErrors()
    {
        $this->sendError(new ApiError());
    }

    /**
     * @param $e JsonApiExceptionInterface
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
        }
        if (!empty(ApiHelper::$responseErrors)) {
            $this->sendErrors();
        }
    }

    /**
     * @param Psr\Http\Message\ResponseInterface $response
     */
    public function emit($response)
    {
        $emitter = new \Zend\Diactoros\Response\SapiEmitter();
        $emitter->emit($response);
    }
}
