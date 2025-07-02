<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('categories')->get();
        return new ProjectResource(true, 'List Data Project', $projects);
    }

    public function store(Request $request)
    {
        Log::info('Request received', $request->all());

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
        $filename = $file->hashName();
        $path = 'project/' . $filename;

        $success = Storage::disk('supabase')->put($path, file_get_contents($file));

        if (!$success) {
            Log::error('Gagal upload gambar ke Supabase', ['path' => $path]);
            return response()->json(['error' => 'Gagal upload gambar'], 500);
        }

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
            // 'image_url' => Storage::disk('supabase')->url($path), // opsional jika bucket public
        ]);
    }

    public function show($id)
    {
        $project = Project::with('categories')->findOrFail($id);
        return new ProjectResource(true, 'Detail Project', $project);
    }

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
            // Hapus file lama
            if ($project->image && Storage::disk('supabase')->exists($project->image)) {
                Storage::disk('supabase')->delete($project->image);
            }

            $newFile = $request->file('image');
            $filename = $newFile->hashName();
            $newPath = 'project/' . $filename;

            $upload = Storage::disk('supabase')->put($newPath, file_get_contents($newFile));

            if (!$upload) {
                Log::error('Gagal upload gambar baru ke Supabase', ['path' => $newPath]);
                return response()->json(['error' => 'Gagal upload gambar baru'], 500);
            }

            $project->image = $newPath;
        }

        $project->update([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'bidang' => $validated['bidang'],
            'github_link' => $validated['github_link'] ?? null,
            'demo_link' => $validated['demo_link'] ?? null,
        ]);

        $project->categories()->sync($validated['category_id']);

        return response()->json([
            'message' => 'Project berhasil diperbarui',
            'image_path' => $project->image,
        ]);
    }

    public function destroy($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project tidak ditemukan',
            ], 404);
        }

        if ($project->image && Storage::disk('supabase')->exists($project->image)) {
            Storage::disk('supabase')->delete($project->image);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil dihapus',
        ]);
    }
}
