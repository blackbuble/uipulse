<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\Design;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DesignController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'figma_url' => 'nullable|url',
            'figma_file_key' => 'nullable|string',
            'figma_node_id' => 'nullable|string',
            'metadata' => 'nullable|array',
            'image_data' => 'nullable|string', // Base64 image from Figma
        ]);

        $design = $project->designs()->create($validated);

        return response()->json([
            'message' => 'Design received successfully',
            'design' => $design,
        ], 201);
    }
}
