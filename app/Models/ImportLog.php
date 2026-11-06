<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = ['source', 'kind', 'source_id', 'source_url', 'title', 'target_type', 'target_id', 'status', 'notes'];
}
