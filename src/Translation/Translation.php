<?php


namespace Tabaoman\Translation;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

trait Translation
{
    public static function bootTranslation()
    {
        static::updating(function($model) {
            if (isset($model->translations) && is_array($model->translations)) {
                $translations = [];
                foreach ($model->attributes as $key => $val) {
                    if (array_key_exists($key, $model->translations)) {
                        array_push($translations, $key);
                        $model->setTranslation($key, $val);
                    }
                }
                foreach ($translations as $t)
                    unset($model->attributes[$t]);
            }
        });
    }

    /***
     * Only for model use
     * @return mixed
     * @throws \Exception
     */
    public function trans()
    {
        if (class_exists(config('translation.model')))
            return $this->hasMany(config('translation.model'), 'entity_id', $this->getKeyName());
    }

    /***
     * Get all language contents grouped by language codes
     * @return array
     * @throws \Exception
     */
    public function langs ()
    {
        if (class_exists(config('translation.model')))
            $langs = config('translation.model')::
                select(['lang_code', 'text_code', 'content'])
                ->where('entity_id', $this->getKey())
                ->get()->toArray();
        else
            $langs = DB::table(config('translation.table'))
                ->select(['lang_code', 'text_code', 'content'])
                ->where('entity_id', $this->getKey())
                ->where('text_code', $code)
                ->get()->toArray();

        $texts = [];
        for ($i = 0; $i < count($langs); $i++) {
            $lang = $langs[$i]['lang_code'];
            $code = $langs[$i]['text_code'];
            if (!isset($texts[$lang])) $texts[$lang] = [];
            foreach ($this->getTranKey($code, $lang) as $prop)
                $texts[$lang][$prop] = $langs[$i]['content'];
        }
        return $texts;
    }

    /***
     * Override the method from HasAttributes
     * To fill the attribute(s) with translations
     * @param array $attributes
     * @param bool $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if (isset($this->translations) && is_array($this->translations)) {
            foreach ($this->translations as $key => $code) {
                $this->attributes[$key] = $this->getTranslation($key, App::getLocale());
            }
        }

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    /***
     * Get the value for specified key and locale (optional)
     * @param $key
     * @param string|null $locale
     * @return mixed|string
     */
    public function getTranslation($key, string $locale = null)
    {
        if (!isset($this->translations) ||
            !is_array($this->translations) ||
            !array_key_exists($key, $this->translations)) return $this->getAttribute($key);
        $code = $this->translations[$key];
        if (is_array($code) && array_key_exists('code', $code)) {
            if (array_key_exists('locale', $code))
                $locale = $code['locale'];
            $code = $code['code'];
        }
        if (!is_string($code)) {
            return $this->getAttribute($key);
        }
        if (is_null($locale)) $locale = App::getLocale();
        if (class_exists(config('translation.model')))
            $lang = config('translation.model')::where('lang_code', $locale)
                ->where('entity_id', $this->getKey())
                ->where('text_code', $code)->first();
        else
            $lang = DB::table(config('translation.table'))
                ->where('lang_code', $locale)
                ->where('entity_id', $this->getKey())
                ->where('text_code', $code)->first();
        if (is_null($lang)) return '';
        return $lang->content;
    }

    /***
     * Set the value to the specified key with locale (optioanl)
     * @param $key
     * @param $value
     * @param string|null $locale
     * @return mixed
     */
    public function setTranslation($key, $value, string $locale = null)
    {
        if (is_null($this->getKey()) ||
            !isset($this->translations) ||
            !is_array($this->translations) ||
            !array_key_exists($key, $this->translations)) return $this->setAttribute($key, $value);
        $code = $this->translations[$key];
        if (is_array($code) && array_key_exists('code', $code)) {
            if (array_key_exists('locale', $code))
                $locale = $code['locale'];
            $code = $code['code'];
        }
        if (!is_string($code)) {
            return  $this->setAttribute($key, $value);
        }
        if (is_null($locale)) $locale = config('app.locale');
        if (is_null($value)) $value = '';
        if (class_exists(config('translation.model')))
            config('translation.model')::updateOrCreate(
                ['entity_id' => $this->getKey(), 'lang_code' => $locale, 'text_code' => $code],
                ['content' => $value]);
        else
            DB::table(config('translation.table'))->updateOrInsert(
                ['entity_id' => $this->getKey(), 'lang_code' => $locale, 'text_code' => $code],
                ['content' => $value]);
        $this->setAttribute($key, $value);
    }

    public function __get($key)
    {
        return $this->getTranslation($key);
    }

    public function __set($key, $value)
    {
        return $this->setTranslation($key, $value);
    }

    protected function getTranKey($code, string $locale)
    {
        $props = [];
        $keys = array_keys($this->translations);
        for ($i = 0; $i < count($keys); $i++) {
            $key = $keys[$i];
            $tran = $this->translations[$key];
            if (is_string($tran) && $code == $tran) {
                array_push($props, $key);
            } elseif (is_array($tran)) {
                if (isset($tran['code']) && $tran['code'] == $code) {
                    if (!isset($tran['locale']))
                        array_push($props, $key);
                    elseif ($tran['locale'] == $locale)
                        array_push($props, $key);
                }
            }
        }
        return $props;
    }

}
