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
        if ($exception instanceof PostTooLargeException) {
            return redirect()->back()->withErrors([
                'cover_image' => 'The uploaded data is too large. Increase PHP upload_max_filesize/post_max_size or upload a smaller file.'
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

        return parent::render($request, $exception);
    }
}
