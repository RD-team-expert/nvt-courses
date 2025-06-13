<?php

namespace App\Services;

use App\Models\Instruction;

class InstructionService
{
    /**
     * Get all active instructions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllActive()
    {
        return Instruction::where('is_active', true)->get();
    }

    /**
     * Get all instructions
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return Instruction::all();
    }

    /**
     * Get instruction by ID
     *
     * @param int $id
     * @return Instruction|null
     */
    public function getById($id)
    {
        return Instruction::find($id);
    }

    /**
     * Create a new instruction
     *
     * @param array $data
     * @return Instruction
     */
    public function create(array $data)
    {
        // If this is set as default, unset all other defaults
        if (isset($data['is_default']) && $data['is_default']) {
            Instruction::where('is_default', true)->update(['is_default' => false]);
        }

        return Instruction::create($data);
    }

    /**
     * Update an instruction
     *
     * @param int $id
     * @param array $data
     * @return Instruction
     */
    public function update($id, array $data)
    {
        $instruction = Instruction::findOrFail($id);

        // If this is set as default, unset all other defaults
        if (isset($data['is_default']) && $data['is_default']) {
            Instruction::where('is_default', true)->update(['is_default' => false]);
        }

        $instruction->update($data);
        return $instruction;
    }

    /**
     * Delete an instruction
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $instruction = Instruction::findOrFail($id);
        return $instruction->delete();
    }

    /**
     * Get instructions as key-value pairs for the Gemini service
     *
     * @return array
     */
    public function getInstructionsForGemini()
    {
        $instructions = $this->getAllActive();
        $result = [];

        foreach ($instructions as $instruction) {
            $result[$instruction->type] = $instruction->content;
        }

        return $result;
    }
}