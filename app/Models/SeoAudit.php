<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoAudit extends Model
{
    protected $fillable = ['url', 'subject_type', 'subject_key', 'status_code', 'title', 'description', 'canonical', 'robots', 'h1_count', 'images_without_alt', 'words', 'links', 'issues', 'schema_ok', 'checked_at'];

    protected $casts = ['links' => 'array', 'issues' => 'array', 'schema_ok' => 'boolean', 'checked_at' => 'datetime'];
}
