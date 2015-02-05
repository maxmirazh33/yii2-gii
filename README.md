Gii
===
Extended gii for maxmirazh33\yii2-app-skeleton

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Add

```
{
    "type": "vcs",
    "url": "https://github.com/maxmirazh33/yii2-gii"
}
```
to the repositories section of your `composer.json` file and add

```
"maxmirazh33/yii2-gii": "dev-master"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, add in your config:

```php
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'maxmirazh33\gii\Module';
```
