<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Symfony\Component\Mailer\Exception\TransportException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle file upload errors
        if ($exception instanceof PostTooLargeException) {
            return redirect()->back()->withErrors([
                'cover_image' => 'The uploaded data is too large. Increase PHP upload_max_filesize/post_max_size or upload a smaller file.',
            ]);
        }

        // Catch mail/network failures (no internet, SMTP unreachable, DNS failure, etc.)
        if ($exception instanceof TransportException
            || ($exception instanceof \RuntimeException && str_contains($exception->getMessage(), 'Failed to authenticate'))
            || ($exception->getPrevious() instanceof TransportException)
        ) {
            return redirect()->back()
                ->with('error', 'Unable to send email. Please check your internet connection and try again.')
                ->withInput();
        }

        // Handle connection timeouts and network errors
        if ($this->isConnectionError($exception)) {
            return response()->view('errors.network-error', [
                'message' => 'Network connection error. Please check your internet connection and try again.',
            ], 503);
        }

        // Handle AI service unavailability
        if ($this->isAIServiceError($exception)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI service is temporarily unavailable. Please try again later.',
                    'error' => 'ai_service_unavailable',
                ], 503);
            }
            return redirect()->back()
                ->with('warning', 'AI features are temporarily unavailable. Basic functionality continues to work.')
                ->withInput();
        }

        // Handle API rate limiting from external services
        if ($this->isRateLimitError($exception)) {
            return response()->view('errors.rate-limited', [
                'message' => 'Service rate limit exceeded. Please try again in a few moments.',
            ], 429);
        }

        // Handle database connection errors
        if ($this->isDatabaseError($exception)) {
            return response()->view('errors.database-error', [
                'message' => 'Database connection error. Please try again later.',
            ], 503);
        }

        return parent::render($request, $exception);
    }

    /**
     * Check if the exception is a connection error
     */
    private function isConnectionError(Throwable $exception): bool
    {
        $message = $exception->getMessage();
        $previous = $exception->getPrevious();

        // Check for timeout, DNS, or connection refused errors
        if (str_contains($message, 'timeout')
            || str_contains($message, 'Connection refused')
            || str_contains($message, 'Network is unreachable')
            || str_contains($message, 'No route to host')
            || str_contains($message, 'getaddrinfo failed')
            || $exception instanceof \Illuminate\Http\Client\ConnectionException
        ) {
            return true;
        }

        // Check previous exception
        if ($previous instanceof \Exception) {
            return $this->isConnectionError($previous);
        }

        return false;
    }

    /**
     * Check if the exception is an AI service error
     */
    private function isAIServiceError(Throwable $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'AI Services are currently unavailable')
            || str_contains($message, 'Gemini request failed')
            || str_contains($message, 'Ollama is not responding')
            || str_contains($message, 'All AI providers failed')
            || str_contains($exception::class, 'AIService');
    }

    /**
     * Check if the exception is a rate limit error
     */
    private function isRateLimitError(Throwable $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, '429')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'Too Many Requests');
    }

    /**
     * Check if the exception is a database error
     */
    private function isDatabaseError(Throwable $exception): bool
    {
        return $exception instanceof \Illuminate\Database\QueryException
            || $exception instanceof \PDOException
            || str_contains($exception->getMessage(), 'SQLSTATE');
    }
    }
}
