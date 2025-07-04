<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;

class SupabaseStorage
{
    protected $url;
    protected $key;
    protected $bucket;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
        $this->bucket = config('services.supabase.bucket');
    }

    public function upload(UploadedFile $file, $path)
    {
        $filename = $path . '/' . uniqid() . '_' . $file->getClientOriginalName();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => $file->getMimeType(),
        ])->put("{$this->url}/storage/v1/object/$this->bucket/$filename", file_get_contents($file));

        if ($response->successful()) {
            return [
                'path' => $filename,
                'url' => "{$this->url}/storage/v1/object/public/$this->bucket/$filename",
            ];
        }

        throw new \Exception("Upload gagal: " . $response->body());
    }

    public function delete($filename)
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
        ])->delete("{$this->url}/storage/v1/object/$this->bucket/$filename");
    }
}
