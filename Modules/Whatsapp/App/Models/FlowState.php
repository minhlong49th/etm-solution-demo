<?php

namespace Modules\Whatsapp\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Whatsapp\Database\factories\FlowStateFactory;

class FlowState extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['user_phone', 'flows', 'messages'];

    protected static function newFactory(): FlowStateFactory
    {
        //return FlowStateFactory::new();
    }
}
