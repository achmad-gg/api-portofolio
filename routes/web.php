<?php

use Illuminate\Support\Facades\Route;

// use Illuminate\Support\Facades\Config;


use Illuminate\Support\Facades\Storage;

Route::get('/upload-test', function () {
    $localFile = storage_path('app/todo.jpg'); // pastikan file ini ada
    $content = file_get_contents($localFile);
    $path = 'project/test-via-route.jpg';

    $success = Storage::disk('s3')->put($path, $content);

    if (!$success) {
        return response()->json(['error' => 'Upload gagal'], 500);
    }

    $url = config('filesystems.disks.s3.url') . $path;

    return response()->json([
        'message' => 'Upload berhasil',
        'url' => $url,
    ]);
});

Route::get('/debug-storage', function () {
    return response()->json(
        config('filesystems.disks.s3') // ganti jadi 'supabase' jika nama disk kamu beda
    );
});


Route::get('/', function () {
    return view('welcome');
});

