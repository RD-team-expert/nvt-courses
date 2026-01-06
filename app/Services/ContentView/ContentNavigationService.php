<?php

namespace App\Services\ContentView;

use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\CourseModule;

class ContentNavigationService
{
    /**
     * Get navigation data (previous and next content)
     * Returns array with 'previous' and 'next' keys
     */
    public function getNavigation(ModuleContent $content, int $userId): array
    {
        $previous = $this->getPreviousContent($content);
        $next = $this->getNextContent($content);



        return [
            'previous' => $previous,
            'next' => $next,
        ];
    }

    /**
     * Get the module for content, safely handling lazy loading
     */
    private function getContentModule(ModuleContent $content): CourseModule
    {
        return $content->relationLoaded('module') ? $content->module : $content->load('module')->module;
    }

    /**
     * Get previous content in the module
     * Returns null if this is the first item
     */
    public function getPreviousContent(ModuleContent $content): ?array
    {
        $module = $this->getContentModule($content);
        
        $previousInModule = $module->contents()
            ->where('order_number', '<', $content->order_number)
            ->orderBy('order_number', 'desc')
            ->first();

        if ($previousInModule) {
            return [
                'id' => $previousInModule->id,
                'title' => $previousInModule->title,
                'content_type' => $previousInModule->content_type,
                'order_number' => $previousInModule->order_number,
            ];
        }

        return null;
    }

    /**
     * Get next content in the module
     * Returns null if this is the last item
     */
    public function getNextContent(ModuleContent $content): ?array
    {
        $module = $this->getContentModule($content);
        
        $nextInModule = $module->contents()
            ->where('order_number', '>', $content->order_number)
            ->orderBy('order_number', 'asc')
            ->first();

        if ($nextInModule) {
            return [
                'id' => $nextInModule->id,
                'title' => $nextInModule->title,
                'content_type' => $nextInModule->content_type,
                'order_number' => $nextInModule->order_number,
                'is_unlocked' => true, // Simplified - can add logic later
            ];
        }

        return null;
    }

    /**
     * Check if content is unlocked for user
     * (For future implementation of sequential unlocking)
     */
    public function isContentUnlocked(ModuleContent $content, int $userId): bool
    {
        // For now, all content is unlocked
        // You can add logic here later to enforce sequential completion

        // Example: Check if previous content is completed
        // $previous = $this->getPreviousContent($content);
        // if ($previous) {
        //     $previousProgress = UserContentProgress::where('user_id', $userId)
        //         ->where('content_id', $previous['id'])
        //         ->first();
        //     return $previousProgress && $previousProgress->is_completed;
        // }

        return true;
    }

    /**
     * Check if user can access next content
     * (Useful for enforcing sequential learning)
     */
    public function canAccessNextContent(ModuleContent $currentContent, int $userId): bool
    {
        // Check if current content is completed
        $currentProgress = UserContentProgress::where('user_id', $userId)
            ->where('content_id', $currentContent->id)
            ->first();

        // For now, allow access regardless of completion
        // You can enforce completion here if needed
        return true;

        // Strict mode (uncomment to enforce):
        // return $currentProgress && $currentProgress->is_completed;
    }

    /**
     * Get all content in current module with navigation context
     */
    public function getModuleContentList(ModuleContent $currentContent, int $userId): array
    {
        $module = $this->getContentModule($currentContent);
        
        $allContent = $module->contents()
            ->orderBy('order_number')
            ->get();

        return $allContent->map(function($content) use ($currentContent, $userId) {
            $progress = UserContentProgress::where('user_id', $userId)
                ->where('content_id', $content->id)
                ->first();

            return [
                'id' => $content->id,
                'title' => $content->title,
                'content_type' => $content->content_type,
                'order_number' => $content->order_number,
                'is_current' => $content->id === $currentContent->id,
                'is_completed' => $progress ? $progress->is_completed : false,
                'completion_percentage' => $progress ? $progress->completion_percentage : 0,
                'is_unlocked' => $this->isContentUnlocked($content, $userId),
            ];
        })->toArray();
    }

    /**
     * Get completion status for navigation items
     */
    public function getNavigationWithProgress(ModuleContent $content, int $userId): array
    {
        $navigation = $this->getNavigation($content, $userId);

        // Add completion status to previous
        if ($navigation['previous']) {
            $prevProgress = UserContentProgress::where('user_id', $userId)
                ->where('content_id', $navigation['previous']['id'])
                ->first();

            $navigation['previous']['is_completed'] = $prevProgress ? $prevProgress->is_completed : false;
            $navigation['previous']['completion_percentage'] = $prevProgress ? $prevProgress->completion_percentage : 0;
        }

        // Add unlock status to next
        if ($navigation['next']) {
            $navigation['next']['is_unlocked'] = $this->isContentUnlocked(
                ModuleContent::find($navigation['next']['id']),
                $userId
            );
        }

        return $navigation;
    }
}
