<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Throwable;

final class ProductSpecDefinitionController extends Controller
{
    public function show(Request $request, string $slug): JsonResponse
    {
        // Enforce staff access consistent with other controllers
        $this->requireStaffUser($request);

        $path = resource_path('product-specs/'.$slug.'.json');

        if (! is_file($path)) {
            return response()->json([
                'message' => 'Spec definition not found',
                'slug' => $slug,
            ], 404);
        }

        try {
            $content = File::get($path);
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            return response()->json([
                'message' => 'Failed to load spec definition',
                'error' => $throwable->getMessage(),
            ], 500);
        }

        return response()->json($data);
    }
}
