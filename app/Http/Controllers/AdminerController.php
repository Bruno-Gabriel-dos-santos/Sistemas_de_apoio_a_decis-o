<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sistema;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminerController extends Controller
{
    public function open($sistema)
    {
        $sistema = Sistema::findOrFail($sistema);
        // Prefer to serve Adminer via a protected proxy. Check if adminer exists in storage/adminer
        $storagePath = storage_path('adminer/adminer.php');
        if (!file_exists($storagePath)) {
            // fallback: check public for compatibility (existing installations)
            if (!file_exists(public_path('adminer.php'))) {
                return view('adminer.missing', ['sistema' => $sistema]);
            }
        }
        $config = [
            'server' => $sistema->db_host ?? '127.0.0.1',
            'username' => $sistema->db_username ?? '',
            'password' => $sistema->db_password ?? '',
            'db' => $sistema->db_name ?? '',
            'driver' => 'server',
        ];

        return view('adminer.form', compact('config', 'sistema'));
    }

    /**
     * Proxy endpoint that includes the Adminer script from storage and returns its output
     * while removing framing-restrictive headers so it can be embedded in an iframe.
     */
    public function proxy(Request $request, $sistema)
    {
        $sistema = Sistema::findOrFail($sistema);

        // Ensure adminer file exists in storage first, fallback to public
        $file = storage_path('adminer/adminer.php');
        if (!file_exists($file)) {
            $file = public_path('adminer.php');
            if (!file_exists($file)) {
                return response(view('adminer.missing', ['sistema' => $sistema]), 404);
            }
        }

        // Populate PHP superglobals so Adminer receives parameters correctly
        // Populate $_GET from query string
        $_GET = $request->query();
        // Populate $_POST with incoming auth[] if present (Adminer expects auth[] keys)
        $post = $request->post('auth', []);
        if (!empty($post) && is_array($post)) {
            $_POST['auth'] = $post;
        }
        // Populate $_REQUEST combining GET and POST
        $_REQUEST = array_merge($_GET, $_POST);

        // Ensure $_SERVER reflects the proxy request origin so Adminer builds links using the
        // same host (avoids Adminer emitting absolute URLs to 127.0.0.1 or other hostnames).
        $_SERVER['HTTP_HOST'] = $request->getHost();
        $_SERVER['SERVER_NAME'] = $request->getHost();
        $_SERVER['SERVER_PORT'] = $request->getPort();
        $_SERVER['REQUEST_URI'] = $request->getRequestUri();
        $_SERVER['HTTPS'] = $request->isSecure() ? 'on' : 'off';
        if ($request->headers->has('X-Forwarded-For')) {
            $_SERVER['REMOTE_ADDR'] = $request->headers->get('X-Forwarded-For');
        } else {
            $_SERVER['REMOTE_ADDR'] = $request->getClientIp();
        }

        // Log attempt (do NOT log passwords)
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? $request->getClientIp();
        Log::info('Adminer proxy attempt', [
            'sistema_id' => $sistema->id,
            'laravel_user_id' => Auth::id(),
            'db_username' => $post['username'] ?? null,
            'db' => $post['db'] ?? null,
            'ip' => $clientIp,
        ]);

        // Capture output of Adminer script
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            Log::error('Adminer include error', ['exception' => $e->getMessage(), 'sistema_id' => $sistema->id]);
            return response('Erro ao incluir Adminer: ' . $e->getMessage(), 500);
        }
        $content = ob_get_clean();

        // Detect common MySQL access denied message or other errors in Adminer output and log
        if (stripos($content, 'Access denied for user') !== false || stripos($content, 'access denied') !== false) {
            Log::warning('Adminer reported access denied', [
                'sistema_id' => $sistema->id,
                'db_username' => $post['username'] ?? null,
                'db' => $post['db'] ?? null,
                'ip' => $clientIp,
                'snippet' => substr($content, 0, 512),
            ]);
        } else {
            Log::info('Adminer proxy served content', ['sistema_id' => $sistema->id, 'db' => $post['db'] ?? null, 'ip' => $clientIp]);
        }

        // Collect headers set by the included script and filter out framing restrictions
        $sent = headers_list();
        $response = new Response($content);
        foreach ($sent as $h) {
            // Skip X-Frame-Options and CSP frame-ancestors which block embedding
            if (stripos($h, 'x-frame-options') === 0) continue;
            if (stripos($h, 'content-security-policy') === 0) continue;
            // Parse into name:value
            $parts = explode(':', $h, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                $response->headers->set($name, $value);
            }
        }

        // Explicitly allow framing from same origin (or remove restriction)
        $response->headers->remove('X-Frame-Options');
        // Optionally set a permissive frame-ancestors for this app's origin
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'");

        return $response;
    }
}
