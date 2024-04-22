<?php

namespace Modules\Whatsapp\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Whatsapp\Database\factories\SysConfigFactory;

class SysConfig extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['key', 'value', 'description'];

    protected static function newFactory(): SysConfigFactory
    {
        //return SysConfigFactory::new();
    }


}
