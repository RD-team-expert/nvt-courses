<?php

namespace App\Http\Controllers;

use App\Services\VpsSubtitleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubtitleCallbackController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Subtitle callback received', [
            'video_id' => $request->input('video_id'),
            'status'   => $request->input('status'),
            'has_vtt'  => !empty($request->input('vtt_content')),
        ]);

        $data = $request->validate([
            'video_id'    => 'required',
            'status'      => 'required|in:completed,failed',
            'vtt_content' => 'nullable|string',
            'filename'    => 'nullable|string',
            'error'       => 'nullable|string',
        ]);

        $success = app(VpsSubtitleService::class)->handleCallback($data);

        return response()->json([
            'received' => true,
            'success'  => $success,
        ]);
    }
}