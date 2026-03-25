<?php

declare(strict_types=1);

namespace Phenix\Routing\Middlewares;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Phenix\App;
use Phenix\Http\Constants\HttpStatus;
use Phenix\Routing\UrlGenerator;

class ValidateSignature implements Middleware
{
    protected UrlGenerator $urlGenerator;

    public function __construct()
    {
        $this->urlGenerator = App::make(UrlGenerator::class);
    }

    public function handleRequest(Request $request, RequestHandler $next): Response
    {
        if (! $this->urlGenerator->hasValidSignature($request)) {
            return $this->invalidSignatureResponse($request);
        }

        return $next->handleRequest($request);
    }

    protected function invalidSignatureResponse(Request $request): Response
    {
        $isExpired = $request->getQueryParameter('signature') !== null
            && ! $this->urlGenerator->signatureHasNotExpired($request);

        $message = $isExpired
            ? 'Signature has expired.'
            : 'Invalid signature.';

        return new Response(
            status: HttpStatus::FORBIDDEN->value,
            headers: [
                'content-type' => 'application/json',
            ],
            body: json_encode([
                'error' => 'Forbidden',
                'message' => $message,
            ])
        );
    }
}
