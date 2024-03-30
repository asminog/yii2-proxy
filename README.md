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

## Usage

```php
use asminog\proxy\ProxyAction;

class SiteController extends Controller
{
    public function actions()
    {
        return [
            'proxy' => [
                'class' => ProxyAction::class,
            ],
        ];
    }
}
```



