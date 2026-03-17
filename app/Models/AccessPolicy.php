<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class AccessPolicy extends Model
{
    use HasUuidPrimaryKey;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'document_id',
        'rule',
        'conditions_json',
    ];

    protected $casts = [
        'conditions_json' => 'array',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
