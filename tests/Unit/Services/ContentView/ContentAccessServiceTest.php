<?php

namespace Tests\Unit\Services\ContentView;

use Tests\TestCase;
use App\Services\ContentView\ContentAccessService;
use App\Models\User;
use App\Models\ModuleContent;
use App\Models\CourseOnlineAssignment;
use App\Models\CourseModule; // ✅ CHANGED from Module
use App\Models\CourseOnline;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContentAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ContentAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ContentAccessService();
    }

    public function test_user_with_assignment_can_access_content()
    {
        // Arrange: Create test data
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create([ // ✅ CHANGED
            'course_online_id' => $course->id,
        ]);
        $content = ModuleContent::factory()->create([
            'module_id' => $module->id,
        ]);
        
        $assignment = CourseOnlineAssignment::factory()->create([
            'user_id' => $user->id,
            'course_online_id' => $course->id,
            'status' => 'assigned',
        ]);

        // Act: Check access
        $result = $this->service->checkAccess($user, $content);

        // Assert: Should return the assignment
        $this->assertNotNull($result);
        $this->assertEquals($assignment->id, $result->id);
        $this->assertEquals($user->id, $result->user_id);
    }

    public function test_user_without_assignment_cannot_access_content()
    {
        // Arrange: Create user and content, but NO assignment
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create([ // ✅ CHANGED
            'course_online_id' => $course->id,
        ]);
        $content = ModuleContent::factory()->create([
            'module_id' => $module->id,
        ]);

        // Act: Check access (no assignment exists)
        $result = $this->service->checkAccess($user, $content);

        // Assert: Should return null (no access)
        $this->assertNull($result);
    }

    public function test_verify_access_or_fail_throws_403_when_no_assignment()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create([ // ✅ CHANGED
            'course_online_id' => $course->id,
        ]);
        $content = ModuleContent::factory()->create([
            'module_id' => $module->id,
        ]);

        // Act & Assert: Expect 403 exception
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Access denied to this content');

        $this->service->verifyAccessOrFail($user, $content);
    }

    public function test_verify_access_or_fail_returns_assignment_when_granted()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $module = CourseModule::factory()->create([ // ✅ CHANGED
            'course_online_id' => $course->id,
        ]);
        $content = ModuleContent::factory()->create([
            'module_id' => $module->id,
        ]);
        
        $assignment = CourseOnlineAssignment::factory()->create([
            'user_id' => $user->id,
            'course_online_id' => $course->id,
        ]);

        // Act
        $result = $this->service->verifyAccessOrFail($user, $content);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($assignment->id, $result->id);
    }

    public function test_get_user_assignment_returns_correct_assignment()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        $assignment = CourseOnlineAssignment::factory()->create([
            'user_id' => $user->id,
            'course_online_id' => $course->id,
        ]);

        // Act
        $result = $this->service->getUserAssignment($user, $course->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($assignment->id, $result->id);
    }

    public function test_has_any_assignments_returns_true_when_user_has_assignments()
    {
        // Arrange
        $user = User::factory()->create();
        $course = CourseOnline::factory()->create();
        CourseOnlineAssignment::factory()->create([
            'user_id' => $user->id,
            'course_online_id' => $course->id,
        ]);

        // Act
        $result = $this->service->hasAnyAssignments($user);

        // Assert
        $this->assertTrue($result);
    }

    public function test_has_any_assignments_returns_false_when_user_has_no_assignments()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->service->hasAnyAssignments($user);

        // Assert
        $this->assertFalse($result);
    }
}
