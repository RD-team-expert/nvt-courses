<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instruction;
use App\Services\InstructionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstructionController extends Controller
{
    protected $instructionService;

    public function __construct(InstructionService $instructionService)
    {
        $this->instructionService = $instructionService;
    }

    /**
     * Display a listing of the instructions.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $instructions = Instruction::orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Admin/Instructions/Index', [
            'instructions' => $instructions,
        ]);
    }

    /**
     * Show the form for creating a new instruction.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Admin/Instructions/Create');
    }

    /**
     * Store a newly created instruction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|max:50',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $this->instructionService->create($validated);

        return redirect()->route('admin.instructions.index')
            ->with('success', 'Instruction created successfully!');
    }

    /**
     * Display the specified instruction.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $instruction = $this->instructionService->getById($id);

        return Inertia::render('Admin/Instructions/Show', [
            'instruction' => $instruction,
        ]);
    }

    /**
     * Show the form for editing the specified instruction.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $instruction = $this->instructionService->getById($id);

        return Inertia::render('Admin/Instructions/Edit', [
            'instruction' => $instruction,
        ]);
    }

    /**
     * Update the specified instruction in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|max:50',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $this->instructionService->update($id, $validated);

        return redirect()->route('admin.instructions.index')
            ->with('success', 'Instruction updated successfully!');
    }

    /**
     * Remove the specified instruction from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $this->instructionService->delete($id);

        return redirect()->route('admin.instructions.index')
            ->with('success', 'Instruction deleted successfully!');
    }
}