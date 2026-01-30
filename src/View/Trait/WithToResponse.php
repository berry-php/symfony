<?php declare(strict_types=1);

namespace Berry\Symfony\View\Trait;

use Symfony\Component\HttpFoundation\Response;

trait WithToResponse
{
    /**
     * Tranform into Symfony Response
     *
     * @param array<string, mixed> $headers
     */
    public function toResponse(int $status = 200, array $headers = []): Response
    {
        return new Response($this->toString(), $status, $headers);
    }
}
