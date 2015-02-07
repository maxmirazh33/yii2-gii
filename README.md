Gii
===
Extended gii for [maxmirazh33/yii2-app-skeleton](https://github.com/maxmirazh33/yii2-app-skeleton)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/maxmirazh33/yii2-gii/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/maxmirazh33/yii2-gii/?branch=master)
[![Code Climate](https://codeclimate.com/github/maxmirazh33/yii2-gii/badges/gpa.svg)](https://codeclimate.com/github/maxmirazh33/yii2-gii)
[![Dependency Status](https://www.versioneye.com/user/projects/54d1fb3b3ca0840b19000106/badge.svg?style=flat)](https://www.versioneye.com/user/projects/54d1fb3b3ca0840b19000106)
[![Build Status](https://scrutinizer-ci.com/g/maxmirazh33/yii2-gii/badges/build.png?b=master)](https://scrutinizer-ci.com/g/maxmirazh33/yii2-gii/build-status/master)

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
