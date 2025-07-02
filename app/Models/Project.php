<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'content',
        'image',
        'bidang',
        'github_link',
        'demo_link',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

     protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
