<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageTranslate extends Model
{
    protected $table = 't_language_translate';
    protected $primaryKey  = 'id';

    const CREATED_AT = 'create_time';
    const UPDATED_AT = 'update_time';

    protected $fillable = ['entity_id', 'lang_code', 'text_code', 'content'];
}
