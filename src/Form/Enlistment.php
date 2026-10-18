<?php

declare(strict_types=1);

namespace Forumify\Milhq\Form;

class Enlistment
{
    public string $firstName = '';
    public string $lastName = '';
    /** @var array<string, mixed> */
    public array $additionalFormData = [];
}
