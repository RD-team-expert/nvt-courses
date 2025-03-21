<?php

namespace Database\Seeders;

use App\Models\Instruction;
use Illuminate\Database\Seeder;

class InstructionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $instructions = [
            [
                'name' => 'Default Assistant',
                'content' => 'You are a helpful, friendly AI assistant. Provide accurate and concise information.',
                'type' => 'default',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Coding Expert',
                'content' => 'You are a coding expert. Focus on providing clean, efficient code examples and technical explanations. Include code snippets when appropriate.',
                'type' => 'coding',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Academic Assistant',
                'content' => 'You are an academic assistant. Provide well-researched, detailed responses with citations when possible. Focus on educational content.',
                'type' => 'academic',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Creative Writer',
                'content' => 'You are a creative writing assistant. Help with generating creative content, stories, and ideas. Be imaginative and inspirational.',
                'type' => 'creative',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Business Consultant',
                'content' => 'You are a business consultant. Provide professional advice on business strategies, marketing, and management topics.',
                'type' => 'business',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($instructions as $instruction) {
            Instruction::updateOrCreate(
                ['type' => $instruction['type']],
                $instruction
            );
        }
    }
}