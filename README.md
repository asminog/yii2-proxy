<p align="center">
    <h1 align="center">HTTP Proxy Extension for Yii 2</h1>
    <br>
</p>

This is a simple proxy for Yii2 framework.
This extension provides the HTTP proxy action for the [Yii framework 2.0](https://www.yiiframework.com).

For license information check the [LICENSE](LICENSE)-file.

[![Build Status](https://github.com/asminog/yii2-proxy/workflows/analyze/badge.svg)](https://github.com/asminog/yii2-proxy/actions/workflows/analyze.yml)
[![Build Status](https://github.com/asminog/yii2-proxy/workflows/phpmd/badge.svg)](https://github.com/asminog/yii2-proxy/actions/workflows/phpmd.yml)

![GitHub repo file count](https://img.shields.io/github/directory-file-count/asminog/yii2-proxy)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/asminog/yii2-proxy)


## Installation

```bash
composer require asminog/yii2-proxy
```

## Usage on domain.com

```php
use asminog\proxy\ProxyAction;

class SiteController extends Controller
{
    public function actions()
    {
        return [
            'proxy' => [
                'class' => ProxyAction::class,
                // 'accessToken' => 'your-access-token', // - set access token for secure requests
                // 'throw404Exception' => true, // - show 404 error if access token is not valid or request url is not valid
                // 'proxyHeaders' => ['User-Agent', 'Content-Type'], // - set headers for proxy request
                'proxyHeaders' => ['Authorization', 'Content-Type'], // - set headers for chatgpt proxy request
                // 'proxyCookies' => ['cookie1', 'cookie2'], // - set cookies for proxy request
            ],
        ];
    }
}
```

## Example request through proxy on domain.com

```php

        $this->client = new Client([
            'transport' => CurlTransport::class,
            'baseUrl' => 'https://domain.com/site/proxy', // - set url to your proxy action
            'requestConfig' => [
                'format' => Client::FORMAT_JSON,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                    'X-Proxy-Url' => 'https://api.openai.com/v1/chat/completions', // - set url to your api
//                    'X-Access-Token' => 'your-access-token' // - set access token for secure requests
                ],
            ],
        ]);

        $response = $this->client->post('', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Hello, how are you?',
                ],
            ],
        ]);

        if ($response->isOk) {
            $data = $response->data;
            // - do something with response data
        } else {
            // - handle error
        }
        $this->client->close();
```
