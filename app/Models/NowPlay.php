<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NowPlay extends Model
{
    protected $table = 'playout_now_play';
    protected $guarded = []; // allow mass assignment for all fields
}
