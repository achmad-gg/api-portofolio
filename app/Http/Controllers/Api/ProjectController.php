<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('categories')->get();
        return new ProjectResource(true, 'List Data Project', $projects);
    }

    public function store(Request $request)
    {
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

        // Upload ke Cloudinary
        $upload = Cloudinary::upload($request->file('image')->getRealPath(), [
            'folder' => 'portfolio-projects',
        ]);

        $imageUrl = $upload->getSecurePath();
        $publicId = $upload->getPublicId();

        // Simpan ke database
        $project = Project::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'bidang' => $validated['bidang'],
            'github_link' => $validated['github_link'] ?? null,
            'demo_link' => $validated['demo_link'] ?? null,
            'image' => $imageUrl,
            'image_public_id' => $publicId, // opsional untuk delete nanti
        ]);

        $project->categories()->sync($validated['category_id']);

        return response()->json([
            'message' => 'Project berhasil disimpan',
            'image_url' => $imageUrl,
            'data' => new ProjectResource(true, 'Project Baru', $project),
        ]);
    }

    public function show($id)
    {
        $project = Project::with('categories')->findOrFail($id);

        return response()->json([
            'message' => 'Detail Project',
            'image_url' => $project->image,
            'data' => new ProjectResource(true, 'Detail Project', $project),
        ]);
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

        // Jika ada gambar baru
        if ($request->hasFile('image')) {
            // Hapus gambar lama dari Cloudinary (jika ada public_id)
            if ($project->image_public_id) {
                Cloudinary::destroy($project->image_public_id);
            }

            $newUpload = Cloudinary::upload($request->file('image')->getRealPath(), [
                'folder' => 'portfolio-projects',
            ]);

            $project->image = $newUpload->getSecurePath();
            $project->image_public_id = $newUpload->getPublicId();
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
            'image_url' => $project->image,
            'data' => new ProjectResource(true, 'Project Diperbarui', $project),
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

        // Hapus gambar dari Cloudinary
        if ($project->image_public_id) {
            Cloudinary::destroy($project->image_public_id);
        }

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project berhasil dihapus',
        ]);
    }
}
