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
                foreach ($model->attributes as $key => $val) {
                    if (array_key_exists($key, $model->translations)) {
                        unset($model->attributes[$key]);
                        // Keep the model clean (not dirty)
                        unset($model->original[$key]);
                        $code = $model->translations[$key];
                        if (is_string($code))
                            $model->setTranslation($key, $code, $val);
                        if (is_array($code) &&
                            array_key_exists('code', $code) &&
                            array_key_exists('locale', $code))
                            $model->setTranslation($key, $code['code'], $val, $code['locale']);
                    }
                }
            }
        });
    }

    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if (isset($this->translations) && is_array($this->translations)) {
            foreach ($this->translations as $key => $code) {
                if (is_string($code))
                    $this->attributes[$key] = $this->getTranslation($this->getKey(), $code, App::getLocale());
                if (is_array($code) && isset($code['code']) && isset($code['locale']))
                    $this->attributes[$key] = $this->getTranslation($this->getKey(), $code['code'], $code['locale']);
            }
        }

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    public function getTranslation(string $key, string $code, string $locale = null)
    {
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

    public function setTranslation(string $key, string $code, ?string $value, string $locale = null)
    {
        if (is_null($locale)) $locale = config('app.locale');
        if (!isset($this->translations) || !is_array($this->translations)) return;
        if (is_null($this->getKey())) return;
        if (is_null($value)) $value = '';
        if (class_exists(config('translation.model')))
            config('translation.model')::updateOrCreate(
                ['entity_id' => $this->getKey(), 'lang_code' => $locale, 'text_code' => $code],
                ['content' => $value]);
        else
            DB::table(config('translation.table'))->updateOrInsert(
                ['entity_id' => $this->getKey(), 'lang_code' => $locale, 'text_code' => $code],
                ['content' => $value]);
        $this->attributes[$key] = $value;
    }

}
