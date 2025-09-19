<?php
// database/seeders/OrganizationalDataSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\UserLevel;
use App\Models\NotificationTemplate;

class OrganizationalDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create User Levels
        $levels = [
            ['code' => 'L1', 'name' => 'Employee', 'hierarchy_level' => 1, 'description' => 'Front-line employees with no direct reports', 'can_manage_levels' => []],
            ['code' => 'L2', 'name' => 'Direct Manager', 'hierarchy_level' => 2, 'description' => 'Team managers with direct reports', 'can_manage_levels' => ['L1']],
            ['code' => 'L3', 'name' => 'Senior Manager', 'hierarchy_level' => 3, 'description' => 'Department heads and senior managers', 'can_manage_levels' => ['L1', 'L2']],
            ['code' => 'L4', 'name' => 'Director', 'hierarchy_level' => 4, 'description' => 'Executive level with multiple department oversight', 'can_manage_levels' => ['L1', 'L2', 'L3']]
        ];

        foreach ($levels as $level) {
            UserLevel::create($level);
        }

        // Create Departments (based on your organization)
        $departments = [
            // Main departments
            ['name' => 'Real Estate', 'description' => 'Main real estate operations department', 'parent_id' => null, 'department_code' => 'RE'],
            ['name' => 'Finance', 'description' => 'Main finance department', 'parent_id' => null, 'department_code' => 'FIN'],
            ['name' => 'Logistics', 'description' => 'Main logistics operations', 'parent_id' => null, 'department_code' => 'LOG'],
            ['name' => 'Marketing', 'description' => 'Marketing and advertising department', 'parent_id' => null, 'department_code' => 'MKT'],
            ['name' => 'R&D', 'description' => 'Research and development department', 'parent_id' => null, 'department_code' => 'RND'],
            ['name' => 'Pizza', 'description' => 'Pizza operations department', 'parent_id' => null, 'department_code' => 'PIZZA'],
        ];

        $createdDepartments = [];
        foreach ($departments as $dept) {
            $createdDepartments[$dept['department_code']] = Department::create($dept);
        }

        // Create sub-departments
        $subDepartments = [
            // Real Estate sub-departments
            ['name' => 'Builders Department', 'description' => 'Construction and building operations', 'parent_id' => $createdDepartments['RE']->id, 'department_code' => 'BUILD'],
            ['name' => 'Management Department', 'description' => 'Real estate management operations', 'parent_id' => $createdDepartments['RE']->id, 'department_code' => 'MGMT'],
            ['name' => '3D Design', 'description' => 'Architectural and design services', 'parent_id' => $createdDepartments['RE']->id, 'department_code' => '3D'],

            // Finance sub-departments
            ['name' => 'Pizza Finance', 'description' => 'Pizza operations finance', 'parent_id' => $createdDepartments['FIN']->id, 'department_code' => 'PFIN'],
            ['name' => 'Real Estate Finance', 'description' => 'Real estate finance operations', 'parent_id' => $createdDepartments['FIN']->id, 'department_code' => 'REFIN'],

            // Logistics sub-departments
            ['name' => 'Dispatch Teams', 'description' => 'Delivery dispatch operations', 'parent_id' => $createdDepartments['LOG']->id, 'department_code' => 'DISP'],
            ['name' => 'HR Team', 'description' => 'Logistics HR management', 'parent_id' => $createdDepartments['LOG']->id, 'department_code' => 'LOGHR'],
            ['name' => 'Hiring Team', 'description' => 'Logistics recruitment', 'parent_id' => $createdDepartments['LOG']->id, 'department_code' => 'HIRE'],
        ];

        foreach ($subDepartments as $subDept) {
            Department::create($subDept);
        }

        // Create basic notification templates
        $templates = [
            [
                'name' => 'Course Assignment - Direct Manager',
                'type' => 'course_assignment',
                'department_id' => null,
                'manager_role_type' => 'direct_manager',
                'subject' => 'New Course Assignment: {{course_name}}',
                'body' => 'Hello {{manager_name}}, your team member {{employee_name}} has been assigned the course "{{course_name}}". Please ensure they complete it by {{due_date}}.',
                'variables' => ['course_name', 'employee_name', 'manager_name', 'due_date'],
                'created_by' => 1 // Assuming admin user ID is 1
            ],
            [
                'name' => 'Evaluation Due - General',
                'type' => 'evaluation_due',
                'department_id' => null,
                'manager_role_type' => null,
                'subject' => 'Evaluation Due: {{evaluation_title}}',
                'body' => 'Hello {{user_name}}, you have a pending evaluation "{{evaluation_title}}" that needs to be completed by {{due_date}}.',
                'variables' => ['evaluation_title', 'user_name', 'due_date'],
                'created_by' => 1
            ]
        ];


    }
}
