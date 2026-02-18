<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /** Routes to skip (e.g. login to avoid logging credentials). */
    protected array $skipRoutes = [
        'login.submit',
        'register.submit',
        'password.email',
        'password.update',
    ];

    /** Methods that change data and should be logged. */
    protected array $logMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldLog($request)) {
            return $response;
        }

        $this->writeLog($request);

        return $response;
    }

    protected function shouldLog(Request $request): bool
    {
        if (!in_array($request->method(), $this->logMethods, true)) {
            return false;
        }

        $route = $request->route();
        if ($route && in_array($route->getName(), $this->skipRoutes, true)) {
            return false;
        }

        return true;
    }

    protected function writeLog(Request $request): void
    {
        try {
            $action = $this->actionFromMethod($request->method());
            $routeName = $request->route()?->getName();
            $description = $routeName ?: $request->path();
            $description = $action . ': ' . $description;

            ActivityLog::log($action, $description, null, null, []);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function actionFromMethod(string $method): string
    {
        return match ($method) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'request',
        };
    }
}
