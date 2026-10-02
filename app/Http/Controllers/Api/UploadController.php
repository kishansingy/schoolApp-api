<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * POST /tables/{appTable}/upload
     * Stores file under public/uploads/{table_name}/{filename}
     * Returns the public URL path.
     */
    public function store(Request $request, AppTable $appTable)
    {
        $request->validate([
            'file' => 'required|file|image|max:5120', // 5 MB max
        ]);

        $folder = 'uploads/' . $appTable->name;
        $path   = $request->file('file')->store($folder, 'public');

        return response()->json([
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * DELETE /upload
     * Removes a previously uploaded file by its storage path.
     */
    public function destroy(Request $request)
    {
        $path = $request->input('path');
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        return response()->noContent();
    }
}
