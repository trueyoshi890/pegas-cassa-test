<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadFileRequest;
use App\Jobs\FillBinJob;
use App\Models\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class FillBinController extends Controller
{
    public function upload(UploadFileRequest $request): JsonResponse
    {
        $user = $request->user();

        $randomName = Str::random(32) . '.' . $request->file('file')->getClientOriginalExtension();
        $path = $request->file('file')->storeAs('uploads', $randomName);

        $file = File::query()->create([
            'path' => $path,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        FillBinJob::dispatch($file);

        return response()->json([
            'message' => 'Файл загружен и поставлен в очередь на обработку',
            'file_id' => $file->path,
        ]);
    }
}
