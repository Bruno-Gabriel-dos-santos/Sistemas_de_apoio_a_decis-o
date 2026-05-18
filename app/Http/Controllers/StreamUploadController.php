<?php

namespace App\Http\Controllers;

use App\Services\Streaming\StreamingConfigService;
use Illuminate\Http\Request;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

class StreamUploadController extends Controller
{
    public function __construct(private StreamingConfigService $streamingConfig)
    {
    }

    public function test(Request $request)
    {
        $config = $this->streamingConfig->build($request);

        return view('streaming.test', $config + [
            'user_name' => $request->user()->name ?? $request->user()->email ?? 'Usuário'
        ]);
    }
    
    public function listFiles(Request $request)
    {
        $files = [];
        $user = $request->user();
        $basePath = storage_path('app/streaming/upload/user_' . $user->id);
        
        if (is_dir($basePath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = [
                        'name' => str_replace($basePath . '/', '', $file->getPathname()),
                        'size' => $file->getSize(),
                        'size_gb' => round($file->getSize() / 1024 / 1024 / 1024, 2),
                        'modified' => date('Y-m-d H:i:s', $file->getMTime())
                    ];
                }
            }
        }
        
        return response()->json($files);
    }
}
