<?php

namespace App\Http\Controllers\Version;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class VersionCheckAction extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'ios_version' => '1.0.0',
            'android_version' => '1.0.0',
            'force_update' => false,
            'update_message' => 'A new version is available with important features. Please update your application.'
        ]);
    }
}
