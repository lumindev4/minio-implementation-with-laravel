<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MinioApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MinioController extends Controller
{
    public function __construct(private MinioApiService $minio) {}

    // POST /api/files
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240', // 10MB
        ]);
        if($validator->fails()){
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        return response()->json(
            $this->minio->upload($request->file('file')),
            201
        );
    }

    // GET /api/files
    public function index(Request $request)
    {
        // dd($request->get('prefix', ''), 3223);
        return response()->json(
            $this->minio->list($request->get('prefix', ''))
        );
    }

    // GET /api/files/show-by-name?name=...
    public function showByName(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'name' => 'required|string',
        ]);
        // dd($request->all(), 323);
        if($validator->fails()){
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = \App\Models\File::where('original_name', $request->name)->firstOrFail();

        return response()->json([
            'id'   => $file->id,
            'name' => $file->original_name,
            'path' => $file->path,
            'url'  => $this->minio->temporaryUrl($file->path),
        ]);
    }

    // GET /api/files/url
    public function url(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        return response()->json([
            'url' => $this->minio->temporaryUrl($request->path),
        ]);
    }

    // PUT /api/files
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
            'file' => 'required|file|max:10240', // 10MB
        ]);
        if($validator->fails()){
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }
        return response()->json(
            $this->minio->replace(
                $request->path,
                $request->file('file')
            )
        );
    }

    // DELETE /api/files
    public function destroy(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $this->minio->delete($request->path);

        return response()->json([
            'message' => 'File deleted',
        ]);
    }
}
