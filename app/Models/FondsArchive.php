<?php

namespace App\Models;

use App\Models\Concerns\HasUuidPrimaryKey;
use Illuminate\Database\Eloquent\Model;

class FondsArchive extends Model
{
    use HasUuidPrimaryKey;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'fonds_archives';

    protected $fillable = [
        'code',
        'name',
        'description',
        'period_label',
        'unesco',
        'estimated_documents_count',
    ];

    protected $casts = [
        'unesco' => 'bool',
    ];

    public function documents()
    {
        return $this->hasMany(Document::class, 'fonds_id');
    }
}
