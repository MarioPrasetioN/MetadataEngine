<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetadataLogging extends Model
{
    protected $table = 'metadata_logging';
    protected $guarded = [];
    public $timestamps = false; // we only use created_at

    /**
     * Helper function to log a task or metadata response
     */
    public static function log($metadataName, $endpoint = null, $responseMessage = null, $responseCode = null, $notes = null)
    {
        self::create([
            'metadata_name'    => $metadataName,
            'endpoint'         => $endpoint,
            'response_message' => $responseMessage,
            'response_code'    => $responseCode,
            'notes'            => $notes,
        ]);
    }
}
