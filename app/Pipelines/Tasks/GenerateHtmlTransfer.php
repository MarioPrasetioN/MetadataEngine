<?php
namespace App\Pipelines\Tasks;

use Closure;
use Illuminate\Support\Facades\Storage;
use App\Models\MetadataLogging;

class GenerateHtmlTransfer
{
    public function handle($nowplay, Closure $next)
    {
        try {
            $html = '<table border="1">';
            $html .= '<tr><th>Artist</th><th>Title</th><th>Duration (ms)</th></tr>';
            $html .= '<tr>';
            $html .= '<td>' . ($nowplay->artist ?? '') . '</td>';
            $html .= '<td>' . ($nowplay->title ?? '') . '</td>';
            $html .= '<td>' . ($nowplay->duration ?? '') . '</td>';
            $html .= '</tr>';
            $html .= '</table>';

            try {
                Storage::disk('ftp_me_demo')->put('nowplaying.html', $html);

                MetadataLogging::create([
                    'metadata_name'    => 'GenerateHtmlTransfer',
                    'endpoint'         => 'ftp_me_demo:nowplaying.html',
                    'response_message' => 'Uploaded',
                    'response_code'    => 200,
                    'notes'            => 'HTML uploaded to FTP',
                ]);
            } catch (\Exception $e) {
                MetadataLogging::create([
                    'metadata_name'    => 'GenerateHtmlTransfer',
                    'endpoint'         => 'ftp_me_demo:nowplaying.html',
                    'response_message' => $e->getMessage(),
                    'response_code'    => 500,
                    'notes'            => 'FTP upload failed',
                ]);
            }
        } catch (\Exception $e) {
            MetadataLogging::create([
                'metadata_name'    => 'GenerateHtmlTransfer',
                'endpoint'         => 'ftp_me_demo:nowplaying.html',
                'response_message' => $e->getMessage(),
                'response_code'    => 500,
                'notes'            => 'GenerateHtmlTransfer failed',
            ]);
        }

        return $next($nowplay);
    }
}
?>