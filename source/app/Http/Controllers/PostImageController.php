<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostImageController extends Controller
{
    /**
     * Quill 에디터 이미지 업로드.
     * 허용 형식: jpeg, png, gif, webp / 최대 5 MB
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:5120', 'mimes:jpeg,png,gif,webp'],
        ]);

        $file     = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs(
            'post-images/' . now()->format('Y/m'),
            $filename,
            'public'
        );

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }
}
