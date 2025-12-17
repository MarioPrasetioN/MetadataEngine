<?php
namespace App\Pipelines\Tasks;

use Closure;
use Illuminate\Support\Facades\Http;
use App\Models\MetadataLogging;

class SimpleHttpCall
{
    public function handle($payload, Closure $next)
    {
        // payload is expected to be an array with keys 'url' and 'nowplay'
        $endpoint = is_array($payload) ? ($payload['url'] ?? null) : null;
        $nowplay = is_array($payload) ? ($payload['nowplay'] ?? null) : $payload;

        try {
            $response = Http::post($endpoint, [
                'playout_id' => $nowplay->playout_id ?? null,
                'artist'     => $nowplay->artist ?? null,
                'title'      => $nowplay->title ?? null,
            ]);

            MetadataLogging::create([
                'metadata_name'    => 'SimpleHttpCall',
                'endpoint'         => $endpoint,
                'response_message' => $response->body(),
                'response_code'    => $response->status(),
                'notes'            => 'Executed inline HTTP call',
            ]);
        } catch (\Exception $e) {
            MetadataLogging::create([
                'metadata_name'    => 'SimpleHttpCall',
                'endpoint'         => $endpoint,
                'response_message' => $e->getMessage(),
                'response_code'    => 500,
                'notes'            => 'Failed inline HTTP call',
            ]);
        }

        // forward the NowPlay object to the next pipe
        return $next($nowplay);
    }
}
