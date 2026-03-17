<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasUuidPrimaryKey;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'fonds_id',
        'title',
        'reference_code',
        'summary',
        'type',
        'visibility',
        'start_date',
        'end_date',
        'language',
        'page_count',
        'published_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'published_at' => 'datetime',
    ];

    public function fonds()
    {
        return $this->belongsTo(FondsArchive::class, 'fonds_id');
    }

    public function files()
    {
        return $this->hasMany(DocumentFile::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function accessPolicies()
    {
        return $this->hasMany(AccessPolicy::class);
    }
}
