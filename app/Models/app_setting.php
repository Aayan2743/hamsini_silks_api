<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class app_setting extends Model
{
    protected $guarded = [

    ];

     public static function one()
    {
        return self::firstOrCreate(['id' => 1]);
    }
}