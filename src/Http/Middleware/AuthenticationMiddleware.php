<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\SystemUser\SystemUserRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Security\AuthenticationContext;
use App\Security\AuthenticatedUser;
use App\Security\SessionManager;

final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly SystemUserRepositoryInterface $users,
        private readonly AuthenticationContext $context,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $this->context->set(null);
        $userId = $this->session->userId();

        if ($userId !== null) {
            $record = $userId > 0 ? $this->users->findSafeById($userId) : null;

            if ($record === null || $record['status'] !== 'active') {
                $this->session->clearAuthentication();
                $this->context->set(null);

                if ($request->path() !== '/login') {
                    return Response::redirect('/login');
                }
            } else {
                $user = AuthenticatedUser::fromRow($record);
                $this->context->set($user);
                $request = $request->withAuthenticatedUser($user);
            }
        }

        return $next->handle($request);
    }
}
