<?php

declare(strict_types=1);

namespace App\Http;

final class ResponseEmitter
{
    public function emit(Response $response): void
    {
        http_response_code($response->statusCode());

        foreach ($response->headers() as $name => $value) {
            header($name . ': ' . $value, true);
        }

        echo $response->body();
    }
}
