<?php

declare(strict_types=1);

namespace Forumify\Milhq\Exception;

class SoldierNotFoundException extends MilhqException
{
    public function __construct(
        ?string $message = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message ?? 'No soldier found for the current logged in user.', $code, $previous);
    }
}
