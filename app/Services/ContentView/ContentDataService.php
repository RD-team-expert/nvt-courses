<?php

namespace App\Services\ContentView;

use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ContentDataService
{
    /**
     * Prepare complete content data for Inertia response
     */
    public function prepareContentData(ModuleContent $content): array
    {
        return [
            'id' => $content->id,
            'title' => $content->title,
            'description' => $content->description,
            'content_type' => $content->content_type,
            'order_number' => $content->order_number,
            'is_required' => $content->is_required,
            'duration' => $content->duration,
            'pdf_page_count' => $content->pdf_page_count,

            // ✅ FIXED: Complete module data
            'module' => [
                'id' => $content->module->id,
                'title' => $content->module->title,  // ✅ Added title
                'name' => $content->module->name,
                'course_id' => $content->module->course_online_id,

                // ✅ FIXED: Add complete course data
                'course' => [
                    'id' => $content->module->courseOnline->id ?? 0,
                    'title' => $content->module->courseOnline->title ?? 'Unknown Course',
                    'name' => $content->module->courseOnline->name ?? 'Unknown Course',
                ],
            ],
        ];
    }

    /**
     * Prepare video data with streaming information
     */
    public function prepareVideoData(?Video $video, ?array $streamingData): ?array
{
    if (!$video) {
        return null;
    }

    return [
        'id' => $video->id,
        'name' => $video->name,
        'description' => $video->description,
        'duration' => $video->duration,
        'thumbnail_url' => $video->thumbnail_url,
        'streaming_url' => $streamingData['streaming_url'] ?? null,  // ✅ This is correct
        'key_id' => $streamingData['key_id'] ?? null,
        'key_name' => $streamingData['key_name'] ?? null,
    ];
}


    /**
     * Prepare PDF data
     */
    public function preparePdfData(ModuleContent $content): ?array
{
    if ($content->content_type !== 'pdf') {
        return null;
    }

    // ✅ Generate proper URL for file_path
    $fileUrl = null;
    if ($content->file_path) {
        $fileUrl = Storage::url($content->file_path);
    }
  Log::info('Unauthorized video access attempt', [
                'google_drive_url' => $content->google_drive_pdf_url,
            ]);
    return [
        'file_url' => $fileUrl,  // ✅ Changed from file_path to file_url with proper URL
        'google_drive_url' => $content->google_drive_pdf_url,
        'page_count' => $content->pdf_page_count,
        'has_page_count' => !is_null($content->pdf_page_count),
    ];
}


    /**
     * Prepare progress data
     */
    public function prepareProgressData(?UserContentProgress $progress): array
    {
        if (!$progress) {
            return [
                'exists' => false,
                'completion_percentage' => 0,
                'playback_position' => 0,
                'is_completed' => false,
                'watch_time' => 0,
            ];
        }

        return [
            'exists' => true,
            'id' => $progress->id,
            'completion_percentage' => $progress->completion_percentage,
            'playback_position' => $progress->playback_position ?? 0,
            'is_completed' => $progress->is_completed,
            'watch_time' => $progress->watch_time,
            'last_accessed_at' => $progress->last_accessed_at?->toIso8601String(),
            'completed_at' => $progress->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Prepare navigation data
     */
    public function prepareNavigationData(array $navigation): array
    {
        return [
            'previous' => $navigation['previous'] ? [
                'id' => $navigation['previous']['id'],
                'title' => $navigation['previous']['title'],
                'content_type' => $navigation['previous']['content_type'],
                'is_completed' => $navigation['previous']['is_completed'] ?? false,
            ] : null,
            'next' => $navigation['next'] ? [
                'id' => $navigation['next']['id'],
                'title' => $navigation['next']['title'],
                'content_type' => $navigation['next']['content_type'],
                'is_unlocked' => $navigation['next']['is_unlocked'] ?? true,
            ] : null,
        ];
    }

    /**
     * Build complete Inertia response
     */
   public function buildInertiaResponse(
    ModuleContent $content,
    ?Video $video,
    ?array $streamingData,
    UserContentProgress $progress,
    array $navigation
): array {
    $contentData = $this->prepareContentData($content);
    $progressData = $this->prepareProgressData($progress);
    $navigationData = $navigation;

    $response = [
        'content' => $contentData,
        'progress' => $progressData,
        'navigation' => $navigationData,

        // ✅ ADD THIS: Send course data separately for easier access
        'course' => [
            'id' => $content->module->courseOnline->id ?? 0,
            'name' => $content->module->courseOnline->name ?? 'Unknown Course',
            'title' => $content->module->courseOnline->title ?? 'Unknown Course',
        ],

        // ✅ ADD THIS: Send module data separately
        'module' => [
            'id' => $content->module->id ?? 0,
            'name' => $content->module->name ?? 'Unknown Module',
            'title' => $content->module->title ?? 'Unknown Module',
        ],
    ];

    // Add video data if it's a video content
    if ($content->content_type === 'video' && $video) {
        $response['video'] = $this->prepareVideoData($video, $streamingData);
    }
    Log::info('Content type:', ['type' => $content->content_type]);

    // Add PDF data if it's a PDF content
    if ($content->content_type === 'pdf') {
        $response['pdf'] = $this->preparePdfData($content);
    }
    Log::info('Final response keys:', ['keys' => array_keys($response)]);

    return $response;
}



    /**
     * Prepare error response
     */
    public function buildErrorResponse(string $message, int $code = 403): array
    {
        return [
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ];
    }

    /**
     * Prepare module content list for sidebar/navigation
     */
    public function prepareModuleContentList(array $contentList): array
    {
        return array_map(function($content) {
            return [
                'id' => $content['id'],
                'title' => $content['title'],
                'content_type' => $content['content_type'],
                'order_number' => $content['order_number'],
                'is_current' => $content['is_current'] ?? false,
                'is_completed' => $content['is_completed'] ?? false,
                'completion_percentage' => $content['completion_percentage'] ?? 0,
                'is_unlocked' => $content['is_unlocked'] ?? true,
            ];
        }, $contentList);
    }

    /**
     * Prepare course assignment data
     */
    public function prepareCourseAssignmentData($assignment): array
    {
        if (!$assignment) {
            return [];
        }

        return [
            'id' => $assignment->id,
            'status' => $assignment->status,
            'progress_percentage' => $assignment->progress_percentage,
            'assigned_at' => $assignment->assigned_at?->toIso8601String(),
            'started_at' => $assignment->started_at?->toIso8601String(),
            'completed_at' => $assignment->completed_at?->toIso8601String(),
            'deadline' => $assignment->deadline?->toIso8601String(),
            'is_overdue' => $assignment->is_overdue ?? false,
        ];
    }
}
