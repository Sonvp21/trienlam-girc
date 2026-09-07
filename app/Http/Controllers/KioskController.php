<?php

namespace App\Http\Controllers;

use App\Services\KioskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KioskController extends Controller
{
    public function __construct(protected KioskService $kiosk)
    {
    }

    /** GET /api/projects */
    public function projects()
    {
        return response()->json($this->kiosk->loadProjects());
    }

    /** POST /api/stt */
    public function stt(Request $request)
    {
        $request->validate([
            'audio' => 'required|file',
        ]);

        $file = $request->file('audio');
        $data = file_get_contents($file->getRealPath());

        if (!$data) {
            return response()->json(['detail' => 'File âm thanh rỗng'], 400);
        }

        try {
            $result = $this->kiosk->transcribe($data, $file->getClientOriginalName(), $request->query('provider'));
            Log::info("[STT {$result['provider']}] nghe được: \"{$result['text']}\"");
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['detail' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['detail' => $e->getMessage()], 502);
        }
    }

    /** POST /api/match */
    public function match(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
        ]);

        try {
            $result = $this->kiosk->matchProject($request->input('text'), $request->query('provider'));
            $name = $result['project']['name'] ?? 'không khớp dự án nào';
            Log::info("[MATCH " . ($result['provider'] ?? '') . "] \"{$result['heard']}\" -> {$name}");
            return response()->json($result);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['detail' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return response()->json(['detail' => $e->getMessage()], 502);
        }
    }
}
