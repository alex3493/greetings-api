<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\EventListener;

use App\Modules\Shared\Domain\Exception\DomainException;
use App\Modules\Shared\Domain\Exception\FormValidationException;
use App\Modules\Shared\Domain\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Only act if we have an API request (should always be the case).
        if ('application/json' === $request->headers->get('Accept')) {
            if ($exception instanceof ValidationException) {
                // Validation exception.
                $this->logger->debug('ExceptionListener :: Validation exception', ['exception' => $exception]);
                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->getErrors(),
                    'code' => $exception->getCode(),
                    // For validation exception traces are not needed for development.
                    // 'traces' => $exception->getTrace(),
                ]);
            } elseif ($exception instanceof FormValidationException) {
                // Validation exception.
                $this->logger->debug('ExceptionListener :: Form validation exception', ['exception' => $exception]);
                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'errors' => $exception->getErrors(),
                    'code' => $exception->getCode(),
                    // For validation exception traces are not needed for development.
                    // 'traces' => $exception->getTrace(),
                ]);
            } elseif ($exception instanceof DomainException) {
                // Other Domain exceptions.
                $this->logger->debug('ExceptionListener :: Generic domain exception', ['exception' => $exception]);
                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    // For domain exception traces are not needed for development.
                    // 'traces' => $exception->getTrace(),
                ]);
            } else {
                // All other exceptions.
                // Customize your response object to display the exception details.
                $this->logger->debug('ExceptionListener :: Misc exception', ['exception' => $exception]);
                $response = new JsonResponse([
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'traces' => $exception->getTrace(),
                ]);
            }

            // HttpExceptionInterface is a special type of exception that
            // holds status code and header details.
            if ($exception instanceof HttpExceptionInterface) {
                $response->setStatusCode($exception->getStatusCode());
                $response->headers->replace($exception->getHeaders());
            } else {
                $response->setStatusCode($exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Send the modified response object to the event.
            $event->setResponse($response);
        } else {
            $this->logger->debug('ExceptionListener :: Server error', [
                'exception' => $exception,
                'Content-Type header' => $request->headers->get('Content-Type'),
                'Accept header' => $request->headers->get('Accept'),
            ]);
        }
    }
}
