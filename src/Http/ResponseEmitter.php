<?php

declare(strict_types=1);

namespace App\Http;

final class ResponseEmitter
{
    public function emit(Response $response): void
    {
        http_response_code($response->statusCode());

        foreach ($response->headerValues() as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false);
            }
        }

        echo $response->body();
    }
}
