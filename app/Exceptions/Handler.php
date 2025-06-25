<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom reporting logic can be added here if needed
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle API requests - always return JSON for API routes
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions and return JSON responses
     */
    private function handleApiException(Request $request, Throwable $exception): JsonResponse
    {
        // Handle ModelNotFoundException (when using findOrFail, etc.)
        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found',
                'error' => 'The requested resource could not be found.'
            ], 404);
        }

        // Handle NotFoundHttpException (404 errors)
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Route not found',
                'error' => 'The requested endpoint could not be found.'
            ], 404);
        }

        // Handle ValidationException
        if ($exception instanceof ValidationException) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $exception->errors()
            ], 422);
        }

        // Handle HTTP exceptions
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: 'An error occurred';

            return response()->json([
                'message' => $message,
                'error' => $message
            ], $statusCode);
        }

        // Handle general exceptions
        $statusCode = 500;
        $message = 'Internal server error';

        // In debug mode, show the actual error message
        if (config('app.debug')) {
            $message = $exception->getMessage();
        }

        return response()->json([
            'message' => $message,
            'error' => $message
        ], $statusCode);
    }
}
