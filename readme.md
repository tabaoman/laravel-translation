# Guide

[![Total Downloads](https://img.shields.io/packagist/dt/tabaoman/laravel-translation.svg?label=Downloads&style=flat-square&cacheSeconds=600)](https://packagist.org/packages/tabaoman/laravel-translation) 
[![Latest Version](http://img.shields.io/packagist/v/tabaoman/laravel-translation.svg?label=Release&style=flat-square&cacheSeconds=600)](https://packagist.org/packages/tabaoman/laravel-translation) 

**A i18n solution with Laravel**

This is a Laravel package for i18n. You just need to create just **ONE** more table to manage **ALL** multi-language texts for your other Eloquent entities.

## Installation

```bash
composer require tabaoman/laravel-translation
```

## Basic Use

### **Change the new config file 'translation.php'**
```php
return [
    /*
     * The table that restores the multi-language texts
     * NOTES: Avoid to have any timestamp field.
     *        If you need to record the created/updated time, use 'model' as following.
     */
    'table' => 't_language_translate',
    
    /*
     * An example helper model.
     * It has a higher priority than 'table' if it is not commented out.
     */
    //'model' => \Tabaoman\Translation\Models\LanguageTranslate::class
];
```
### **Create the table**
**NOTE:** Do NOT create with timestamp fields ('create_time', 'update_time' here) if using 'table' in translation config.
~~~sql
CREATE TABLE `t_language_translate` (
    `id`            BIGINT(18)  NOT NULL AUTO_INCREMENT,
    `entity_id`     VARCHAR(32) NOT NULL COMMENT 'model id',
    `lang_code`     VARCHAR(18) NOT NULL COMMENT 'language code',
    `text_code`     VARCHAR(18) NOT NULL COMMENT 'text code',
    `content`       LONGTEXT    NOT NULL COMMENT 'text',
    `create_time`   DATETIME    NOT NULL COMMENT 'created time',
    `update_time`   DATETIME    NULL DEFAULT NULL COMMENT 'updated time',
    PRIMARY KEY (`id`),
    UNIQUE KEY unique_trans (`entity_id`, `lang_code`, `text_code`)
) COMMENT='i18n table' COLLATE='utf8mb4_general_ci' ENGINE=InnoDB;
~~~
### **Add trait and a new member variable '$translations' in model class**
```php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Tabaoman\Translation\Translation;
class Store extends Model
{
    use Translation;
    protected $casts = ['id' => 'string']; // Sometimes you need this cast
    
    protected $translations = [
        'name' => 'STORE_NAME' // attribute => text code
        // 'name' => ['code' => 'STORE_NAME'] // Or associate like this
    ];
    
    // ...
}
```

### **Getting translated attributes**

```php
$store = Store::first();
echo $store->name; // Get with default language setting (App::getLocale())

echo $store->getTranslation('name', 'en'); // Get its English name
App::setLocale('en');
echo $store->name; // Or get its English name after explicitly set app locale
```

### **Saving translated attributes**
```php
$store = Store::first();
echo $store->name; // 苹果商店

$store->setTranslation('name', 'Apple store', 'en'); // Directly save the English name
App::setLocale('en');
$store->name = 'Apple store'; // Or set its English name after explicitly set app locale
$store->save(); // Optional if there is no mutator for 'name'

$store = Store::first();
echo $store->name; // Apple store

App::setLocale('zh_CN');
echo $store->name; // 苹果商店

```

### **Flatten the i18n atrributes**
In model class
```php
    protected $translations = [
        'name' => 'STORE_NAME' // attribute => text code
        'name_english' => ['code' => 'STORE_NAME', 'locale' => 'en'],  // You are free to define your own language code.
    ];
```
```php
$store = Store::first();
echo $store->name;         // 苹果商店
echo $store->name_english; // Apple store
```

## Versions

| Package | Laravel | PHP |
| :--- | :--- | :--- |
| **v0.*** | `5.8.* / 6.*` | `>=7.2` |
