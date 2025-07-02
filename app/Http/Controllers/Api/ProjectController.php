<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('categories')->get();
        return new ProjectResource(true, 'List Data Project', $projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info($request->all());

        $validated = $request->validate([
            'title' => 'required',
            'content' => 'required',
            'bidang' => 'required|in:frontend,backend,fullstack',
            'github_link' => 'nullable|url',
            'demo_link' => 'nullable|url',
            'category_id' => 'required|array',
            'category_id.*' => 'exists:categories,id',
            'image' => 'required|image|max:2048',
        ]);

        $file = $request->file('image');

        $path = $file->storeAs('project', $file->hashName(), 'supabase');

        $project = Project::create([
            'image' => $path,
            'title' => $validated['title'],
            'content' => $validated['content'],
            'bidang' => $validated['bidang'],
            'github_link' => $validated['github_link'] ?? null,
            'demo_link' => $validated['demo_link'] ?? null,
        ]);

        $project->categories()->sync($validated['category_id']);

        return response()->json([
            'message' => 'Project berhasil disimpan',
            'image_path' => $path,
            // kamu juga bisa generate public URL kalau bucket public
            // 'image_url' => Storage::disk('supabase')->url($path),
        ]);
    }



    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $project = Project::with('categories')->findOrFail($id);
        return new ProjectResource(true, 'Detail Project', $project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required',
            'content' => 'required',
            'bidang' => 'required|in:frontend,backend,fullstack',
            'github_link' => 'nullable|url',
            'demo_link' => 'nullable|url',
            'category_id' => 'required|array',
            'category_id.*' => 'exists:categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image->storeAs('project', $image->hashName(), 'public');
            $project->image = $image->hashName();
        }

        $project->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'bidang' => $validated['bidang'],
            'github_link' => $validated['github_link'] ?? null,
            'demo_link' => $validated['demo_link'] ?? null,
            // 'image' di-set di atas
        ]);

        $project->categories()->sync($validated['category_id']);

        return response()->json(['message' => 'Project berhasil diperbarui']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project tidak ditemukan',
            ], 404);
        }

        if ($project->image && Storage::disk('public')->exists('project/' . $project->image)) {
            Storage::disk('public')->delete('project/' . $project->image);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil dihapus',
        ]);
    }
}
