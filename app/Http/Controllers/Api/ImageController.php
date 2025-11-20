<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Contracts\Filesystem\FileNotFoundException; // Untuk penanganan error

class ImageController extends Controller
{
    /**
     * Mengambil file dari storage dan mengembalikannya sebagai response.
     * Ini memaksa file untuk melewati middleware CORS.
     */
    public function show($path)
    {
        try {
            // Cek apakah file ada di storage 'public'
            if (!Storage::disk('public')->exists($path)) {
                abort(404, 'File not found');
            }

            // Ambil file
            $file = Storage::disk('public')->get($path);
            
            // Ambil tipe MIME file (misal: 'image/jpeg')
            $type = Storage::disk('public')->mimeType($path);

            // Buat response dengan isi file dan header Content-Type yang benar
            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;

        } catch (FileNotFoundException $e) {
            abort(404, 'File not found');
        } catch (\Exception $e) {
            // Log error untuk debugging
            \Log::error('Error loading image: ' . $e->getMessage());
            abort(500, 'Could not retrieve file');
        }
    }
}