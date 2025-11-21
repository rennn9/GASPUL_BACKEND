<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorSetting extends Model
{
    use HasFactory;

    protected $table = 'monitor_settings';

    protected $fillable = [
        'video_url',
        'running_text',
    ];
}
