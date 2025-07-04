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
        Log::info('Request received for project creation', $request->all());

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

        if (!$request->hasFile('image')) {
            Log::error('File image tidak ditemukan di request');
            return response()->json(['error' => 'File tidak ditemukan'], 400);
        }

        try {
            $file = $request->file('image');
            $path = 'project/' . $file->hashName();

            $uploadSuccess = Storage::disk('s3')->put($path, file_get_contents($file));

            if (!$uploadSuccess) {
                Log::error('Gagal upload gambar ke Supabase', ['path' => $path]);
                return response()->json(['error' => 'Gagal upload gambar'], 500);
            }

            $project = Project::create([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'bidang' => $validated['bidang'],
                'github_link' => $validated['github_link'] ?? null,
                'demo_link' => $validated['demo_link'] ?? null,
                'image' => $path,
            ]);

            $project->categories()->sync($validated['category_id']);

            $publicUrl = config('filesystems.disks.s3.url') . $path;

            return response()->json([
                'message' => 'Project berhasil disimpan',
                'image_path' => $path,
                'public_url' => $publicUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('Exception saat upload gambar', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal menyimpan project'], 500);
        }
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

        try {
            if ($request->hasFile('image')) {
                // Hapus gambar lama jika ada
                if ($project->image && Storage::disk('s3')->exists($project->image)) {
                    Storage::disk('s3')->delete($project->image);
                }

                $newFile = $request->file('image');
                $newPath = 'project/' . $newFile->hashName();

                $upload = Storage::disk('s3')->put($newPath, file_get_contents($newFile));

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

            $publicUrl = $project->image ? config('filesystems.disks.s3.url') . $project->image : null;

            return response()->json([
                'message' => 'Project berhasil diperbarui',
                'image_path' => $project->image,
                'public_url' => $publicUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal update project', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal update project'], 500);
        }
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

        try {
            if ($project->image && Storage::disk('s3')->exists($project->image)) {
                Storage::disk('s3')->delete($project->image);
            }

            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal hapus project', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus project',
            ], 500);
        }
    }
}
