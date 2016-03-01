<?php

namespace App;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Solution extends Eloquent
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code', 'language',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'code', 'detail', 'detail_result', 'detail_time', 'detail_memory',
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function problem()
    {
        return $this->belongsTo('App\Problem');
    }

    public function getCodeAttribute($value)
    {
        return base64_decode($value);
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = base64_encode($value);
    }

}