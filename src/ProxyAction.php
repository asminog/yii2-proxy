<?php

namespace asminog\proxy;

use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\Request;
use yii\web\Response;

class ProxyAction extends Action
{
    public string|null $accessToken = null;
    private Request $request;
    private Response $response;


    /**
     * @return Response
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws InvalidConfigException
     */
    public function run(): Response
    {
        $url = $this->request->headers->get('X-Proxy-Url');
        if ($this->accessToken) {
            $token = $this->request->headers->get('X-Access-Token');
            if ($token !== $this->accessToken) {
                throw new ForbiddenHttpException('Access denied');
            }
        }
        if (!$url) {
            throw new BadRequestHttpException('X-Proxy-Url header is required');
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
        $request = $client->createRequest()
            ->setMethod($this->request->method)
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
        $this->request = Instance::ensure($controller->request, Request::class);
        $this->response = Instance::ensure($controller->response, Response::class);
        parent::__construct($actionId, $controller, $config);
    }
}