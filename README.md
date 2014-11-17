Gii
===
Extended gii for maxmirazh33\yii2-app-skeleton

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist maxmirazh33/yii2-gii "*"
```

or add

```
"maxmirazh33/yii2-gii": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, add in your config:

```php
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'maxmirazh33\gii\Module';
```