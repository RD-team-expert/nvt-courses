<?php

namespace Tests\Unit\Services\ContentView;

use Tests\TestCase;
use App\Services\ContentView\ContentNavigationService;
use App\Models\User;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContentNavigationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContentNavigationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentNavigationService();
    }

    /**
     * Test 1: Get navigation returns both previous and next
     */
    public function test_get_navigation_returns_both_previous_and_next()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        
        $content1 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 1]);
        $content2 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 2]);
        $content3 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 3]);

        // Act
        $navigation = $this->service->getNavigation($content2, $user->id);

        // Assert
        $this->assertNotNull($navigation['previous']);
        $this->assertNotNull($navigation['next']);
        $this->assertEquals($content1->id, $navigation['previous']['id']);
        $this->assertEquals($content3->id, $navigation['next']['id']);
    }

    /**
     * Test 2: First content has no previous
     */
    public function test_first_content_has_no_previous()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        
        $content1 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 1]);
        $content2 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 2]);

        // Act
        $navigation = $this->service->getNavigation($content1, $user->id);

        // Assert
        $this->assertNull($navigation['previous']);
        $this->assertNotNull($navigation['next']);
        $this->assertEquals($content2->id, $navigation['next']['id']);
    }

    /**
     * Test 3: Last content has no next
     */
    public function test_last_content_has_no_next()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        
        $content1 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 1]);
        $content2 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 2]);

        // Act
        $navigation = $this->service->getNavigation($content2, $user->id);

        // Assert
        $this->assertNotNull($navigation['previous']);
        $this->assertNull($navigation['next']);
        $this->assertEquals($content1->id, $navigation['previous']['id']);
    }

    /**
     * Test 4: Content is unlocked by default
     */
    public function test_content_is_unlocked_by_default()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $content = ModuleContent::factory()->create(['module_id' => $module->id]);

        // Act
        $isUnlocked = $this->service->isContentUnlocked($content, $user->id);

        // Assert
        $this->assertTrue($isUnlocked);
    }

    /**
     * Test 5: Can access next content
     */
    public function test_can_access_next_content()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        $content = ModuleContent::factory()->create(['module_id' => $module->id]);

        // Act
        $canAccess = $this->service->canAccessNextContent($content, $user->id);

        // Assert
        $this->assertTrue($canAccess);
    }

    /**
     * Test 6: Get module content list
     */
    public function test_get_module_content_list()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create(['course_online_id' => $course->id]);
        
        $content1 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 1]);
        $content2 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 2]);
        $content3 = ModuleContent::factory()->create(['module_id' => $module->id, 'order_number' => 3]);

        // Act
        $contentList = $this->service->getModuleContentList($content2, $user->id);

        // Assert
        $this->assertCount(3, $contentList);
        $this->assertEquals($content1->id, $contentList[0]['id']);
        $this->assertEquals($content2->id, $contentList[1]['id']);
        $this->assertEquals($content3->id, $contentList[2]['id']);
        $this->assertTrue($contentList[1]['is_current']); // content2 is current
    }
}
