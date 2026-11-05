<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    public const TYPES = ['contact' => 'Contact', 'quote' => 'Quote request'];

    public const STATUSES = ['new' => 'নতুন', 'read' => 'পড়া হয়েছে', 'replied' => 'উত্তর দেওয়া হয়েছে', 'archived' => 'আর্কাইভ', 'spam' => 'Spam'];

    protected $fillable = [
        'type', 'status', 'name', 'email', 'phone', 'company', 'subject', 'service', 'budget', 'timeline',
        'message', 'data', 'page_url', 'ip', 'user_agent', 'admin_note', 'emailed',
    ];

    protected $casts = [
        'data' => 'array',
        'emailed' => 'boolean',
    ];
}
