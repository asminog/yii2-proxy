<?php

namespace asminog\proxy;

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
    /** @var string[] */
    public array $proxyHeaders = [];
    /** @var string[] */
    public array $proxyCookies = [];
    private Request $request;
    private Response $response;

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
        $request = $client->createRequest()
            ->setMethod($this->request->method)
            ->setUrl($url);
        $this->addData($request);
        $this->addHeaders($headers, $request);
        $this->addCookies($request);

        $response = $request->send();

        return $this->proxyResponse($response);
    }

    /**
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidConfigException
     */
    public function __construct(string $actionId, Controller $controller, array $config = [])
    {
        // Disable CSRF validation
        $controller->enableCsrfValidation = false;

        // Set request and response components
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

    /**
     * @param mixed $headers
     * @param \yii\httpclient\Request $request
     * @return void
     */
    public function addHeaders(mixed $headers, \yii\httpclient\Request $request): void
    {
        if ($this->proxyHeaders) {
            foreach ($this->proxyHeaders as $proxyHeader) {
                if (($value = $headers->get($proxyHeader)) !== null) {
                    $request->headers->add($proxyHeader, $value);
                }
            }
        }
    }

    /**
     * @param \yii\httpclient\Request $request
     * @return void
     */
    public function addCookies(\yii\httpclient\Request $request): void
    {
        if ($this->proxyCookies) {
            foreach ($this->proxyCookies as $proxyCookie) {
                if (($cookie = $this->request->cookies->get($proxyCookie)) !== null) {
                    $request->cookies->add($cookie);
                }
            }
        }
    }

    /**
     * @param \yii\httpclient\Request $request
     * @return void
     */
    public function addData(\yii\httpclient\Request $request): void
    {
        if ($this->request->isGet) {
            $request->setdata($this->request->get());
        } elseif ($this->request->post()) {
            $request->setData($this->request->post());
        } elseif ($this->request->rawBody) {
            $request->setContent($this->request->rawBody);
        }
    }

    /**
     * @param \yii\httpclient\Response $response
     * @return Response
     */
    public function proxyResponse(\yii\httpclient\Response $response): Response
    {
        $this->response->statusCode = (int)$response->statusCode;
        $this->response->format = Response::FORMAT_RAW;
        $this->response->headers->removeAll();
        $this->response->headers->fromArray($response->headers->toArray());
        $this->response->cookies->removeAll();
        $this->response->cookies->fromArray($response->cookies->toArray());

        $this->response->content = $response->content;

        return $this->response;
    }
}