<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use App\Models\NowPlay;
use Illuminate\Support\Facades\Storage;
use App\Pipelines\Tasks\HttpCall;
use App\Pipelines\Tasks\SelectFromDb;
use App\Pipelines\Tasks\GenerateXml;
use App\Pipelines\Tasks\UploadToFtp;
use App\Pipelines\Tasks\MoveFile;
use App\Pipelines\Tasks\CallEndpoints;
use App\Models\PlaylistData;
use Carbon\Carbon;


    class HandoverController extends Controller
    {
        public function nowplay(Request $request)
        {
            $payload = json_decode($request->query('data'), true);

            if (!$payload || !is_array($payload)) {
                return response()->json(['error' => 'Invalid data'], 400);
            }

            foreach ($payload as $index => $item) {
                $validator = \Validator::make($item, [
                    'playout_id'         => 'required|string|max:50',
                    'artist'             => 'nullable|string|max:100',
                    'title'              => 'nullable|string|max:100',
                    'duration'           => 'required|integer',
                    'start_time'         => 'required|date_format:d.m.Y H:i:s.u',
                    'planned_start_time' => 'nullable|date_format:d.m.Y H:i:s.u'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'error' => 'Validation failed',
                        'details' => $validator->errors(),
                        'item_index' => $index
                    ], 422);
                }


                /**
                 * Check if playout id is not setup correctly with the naming convention
                 * localCode-playout-networkCode-...
                 */
                $pattern = '/^[A-Z0-9]+-[A-Z0-9]+-[A-Z0-9]+/i';

                if (!preg_match($pattern, $item['playout_id'])) {
                    return response()->json([
                        'error' => 'Invalid playout_id format (first 3 parts must match localCode - playout - networkCode)',
                        'playout_id' => $item['playout_id'],
                        'item_index' => $index
                    ], 422);
                }

                // extract metadata from playout_id ---
                // Example: RC1-B1-RCD-C
                $parts = explode('-', $item['playout_id']);
                $local_code = $parts[0] ?? null;
                $network_code = $parts[2] ?? null;
                $playout_type = $parts[3] ?? null; // C in your examples

                // store each data into db for reporting purposes
                $nowplay = NowPlay::create(array_merge($item, [
                    'local_code'   => $local_code,
                    'network_code' => $network_code,
                    'playout_type' => $playout_type,
                ]));

                /** Request Number: ....... 
                 * Simple Flow: AWE + RCE
                */
                if ($network_code === 'AWE' && $local_code === 'RCE') {
                    // Simple HTTP call inline
                    //Define user-defined webhook URL
                    $url = 'https://webhook.site/59c912c3-74c3-4da1-96d4-227e0683a710';
                    try {
                        $response = \Http::post($url, [
                            'title'      => $nowplay->title,
                            'artist'     => $nowplay->artist,
                        ]);
                        // log to MetadataLogging
                        \App\Models\MetadataLogging::log(
                            'SimpleHttpCall',
                            $url,
                            $response->body(),
                            $response->status(),
                            'Executed inline simple HTTP call'
                        );
                    } catch (\Exception $e) {
                        \App\Models\MetadataLogging::log(
                            'SimpleHttpCall',
                            $url,
                            $e->getMessage(),
                            'error',
                            'Failed inline HTTP call'
                        );
                    }
                    break;
                } 
                
                /** Request Number: ....... 
                 * mid Flow: RCD + RC1
                */
                $mockData = true; //Set to false to use real DB data
                if ($network_code === 'RCD' && $local_code === 'RC1') {
                    if($mockData){
                        $this->handleMidFlowMock($nowplay);

                    } else {
                        $this->handleMidFlow($nowplay);
                    }

                    //
                } 

                /** Request Number: ....... 
                 * Simple Flow: RCD + RC2
                 * This flow will run through several tasks using Laravel Pipeline feature
                */
                if ($network_code === 'RCD' && $local_code === 'RC2') {
                    
                    //Define user-defined webhook URL
                    $url = 'https://webhook.site/59c912c3-74c3-4da1-96d4-227e0683a710';

                    app(Pipeline::class)
                        ->send(['url' => $url, 'nowplay' => $nowplay]) // pass single payload array
                        ->through([
                            \App\Pipelines\Tasks\SimpleHttpCall::class,
                            \App\Pipelines\Tasks\GenerateHtmlTransfer::class,
                        ])
                        ->thenReturn();

                }

            }

            return response()->json(['status' => 'Data Processed']);
        }
    
    private function handleMidFlow($nowplay)
    {
        $windowSize = 5;

        // Previous 5
        $previous = PlaylistData::where('network_code', $nowplay->network_code)
            ->where('local_code', $nowplay->local_code)
            ->where('created_at', '<', $nowplay->created_at)
            ->orderBy('created_at', 'desc')
            ->limit($windowSize)
            ->get();

        // Next 5
        $next = PlaylistData::where('network_code', $nowplay->network_code)
            ->where('local_code', $nowplay->local_code)
            ->where('created_at', '>', $nowplay->created_at)
            ->orderBy('created_at', 'asc')
            ->limit($windowSize)
            ->get();

        // Combine with current
        $playlistWindow = $previous->reverse()->concat([$nowplay])->concat($next);

        // Generate HTML
        $html = '<table border="1">';
        $html .= '<tr><th>Artist</th><th>Title</th><th>Duration (ms)</th></tr>';

        foreach ($playlistWindow as $row) {
            $html .= '<tr>';
            $html .= '<td>' . ($row->artist ?? $row['artist']) . '</td>';
            $html .= '<td>' . ($row->title ?? $row['title']) . '</td>';
            $html .= '<td>' . ($row->duration ?? $row['duration']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        // Upload to FTP (make sure FTP disk is configured)
        Storage::disk('ftp')->put('nowplaying.html', $html);
    }

    /**
     * Mid Flow Mock: get 5 before + 1 now + 5 after from a mock JSON file
     * Generates HTML table with start_time and uploads to FTP
     */
    private function handleMidFlowMock($nowplay)
    {
        $windowSize = 5;

        // Load mock data
        $mockFile = storage_path('app/mock_playlist.json');
        if (!file_exists($mockFile)) {
            \Log::error("Mock playlist file not found: $mockFile");
            return;
        }

        $mockData = json_decode(file_get_contents($mockFile), true);
        if (!$mockData || !is_array($mockData)) {
            \Log::error("Invalid mock playlist JSON format");
            return;
        }
        
        // Pick a random index for "now playing" in the mock array to select previous/next around it
        $minIndex = $windowSize;
        $maxIndex = count($mockData) - $windowSize - 1;
        $mockNowIndex = ($maxIndex > $minIndex) ? rand($minIndex, $maxIndex) : 0;

        // Get 5 previous from mock
        $previous = array_slice($mockData, $mockNowIndex - $windowSize, $windowSize);

        // The "now" song comes from the request data ($nowplay)
        $now = [
            [
                'artist'   => $nowplay->artist ?? $nowplay['artist'] ?? '',
                'title'    => $nowplay->title ?? $nowplay['title'] ?? '',
                'duration' => $nowplay->duration ?? $nowplay['duration'] ?? 180000,
            ]
        ];

        // Get 5 next from mock
        $next = array_slice($mockData, $mockNowIndex + 1, $windowSize);

        // Combine all
        $playlistWindow = array_merge($previous, $now, $next);

        // Base start time for the "now playing" song
        $nowTime = Carbon::now();


        // Assign start times
        $currentTime = $nowTime->copy();
        foreach (array_reverse($previous) as &$row) {
            $currentTime->subMilliseconds($row['duration'] ?? 0);
            $row['start_time'] = $currentTime->format('Y-m-d H:i:s.v');
        }
        unset($row);

        $now[0]['start_time'] = $nowTime->format('Y-m-d H:i:s.v');

        $currentTime = $nowTime->copy();
        foreach ($next as &$row) {
            $currentTime->addMilliseconds($row['duration'] ?? 0);
            $row['start_time'] = $currentTime->format('Y-m-d H:i:s.v');
        }
        unset($row);

        // Merge all again with start times
        $playlistWindow = array_merge($previous, $now, $next);

        // Generate HTML table
        $html = '<table border="1" cellpadding="5" cellspacing="0">';
        $html .= '<tr><th>Artist</th><th>Title</th><th>Duration (ms)</th><th>Start Time</th></tr>';

        foreach ($playlistWindow as $row) {
            $html .= '<tr>';
            $html .= '<td>' . ($row['artist'] ?? '') . '</td>';
            $html .= '<td>' . ($row['title'] ?? '') . '</td>';
            $html .= '<td>' . ($row['duration'] ?? '') . '</td>';
            $html .= '<td>' . ($row['start_time'] ?? '') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        // Upload to FTP
        try {
            //
            # https://sftpcloud.io/ use this for mock FTP server
            Storage::disk('ftp_me_demo')->put('nowplaying.html', $html);
            \Log::info("Mid flow mock HTML uploaded to FTP successfully with start times");
        } catch (\Exception $e) {
            \Log::error("Failed to upload mid flow mock HTML: " . $e->getMessage());
        }
    }
}
