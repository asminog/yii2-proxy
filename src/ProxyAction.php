<?php

namespace asminog\proxy;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Request;
use yii\web\Response;

class ProxyAction extends Action
{
    public string|null $accessToken = null;

    public bool $throw404Exception = false;
    private Request $request;
    private Response $response;

    public function init(): void
    {
        parent::init();
        $this->controller->enableCsrfValidation = false;
    }


    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     * @throws NotFoundHttpException
     */
    public function run(): Response
    {
        $url = $this->request->headers->get('X-Proxy-Url');
        if (is_array($url)) {
            $url = reset($url);
        }
        if ($this->accessToken) {
            $token = $this->request->headers->get('X-Access-Token');
            if ($token !== $this->accessToken) {
                if ($this->throw404Exception) {
                    throw new NotFoundHttpException('Page not found');
                }
                throw new ForbiddenHttpException('Access token is invalid');
            }
        }
        if (!$url) {
            if ($this->throw404Exception) {
                throw new NotFoundHttpException('Page not found');
            }
            throw new BadRequestHttpException('Proxy URL is not set');
        }

        return $this->proxyRequest($url);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    protected function proxyRequest(string $url): Response
    {
        $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
        $headers = $this->request->headers;
        $headers->remove('X-Proxy-Url');
        $headers->remove('X-Access-Token');
        $request = $client->createRequest()
            ->setMethod($this->request->method)
            ->setOptions([
                'followLocation' => true,
                'maxRedirects' => 3,
            ])
            ->setUrl($url);
        if ($this->request->isGet) {
            $request->setdata($this->request->get());
        } elseif ($this->request->post()) {
            $request->setData($this->request->post());
        } elseif ($this->request->rawBody) {
            $request->setContent($this->request->rawBody);
        }
        $response = $request
            ->setHeaders($headers->toArray())
            ->setCookies($this->request->cookies->toArray())
            ->send();

        Yii::debug([
            'request' => [$request->url, $request->method, $request->headers->toArray(), $request->data, $request->content, $request->cookies->toArray()],
            'response' => [$response->statusCode, $response->headers->toArray(), $response->content, $response->cookies->toArray()],
        ]);

        $this->response->statusCode = (int)$response->statusCode;
        $this->response->format = Response::FORMAT_RAW;
        $this->response->headers->removeAll();
        $this->response->headers->fromArray($response->headers->toArray());
        $this->response->cookies->removeAll();
        $this->response->cookies->fromArray($response->cookies->toArray());

        $this->response->content = $response->content;

        return $this->response;
    }

    /**
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidConfigException
     */
    public function __construct(string $actionId, Controller $controller, array $config = [])
    {
        if (!($controller->request instanceof Request)) {
            throw new InvalidConfigException('Request component must be an instance of yii\web\Request');
        }
        $this->request = $controller->request;

        if (!($controller->response instanceof Response)) {
            throw new InvalidConfigException('Response component must be an instance of yii\web\Response');
        }
        $this->response = $controller->response;

        parent::__construct($actionId, $controller, $config);
    }
}