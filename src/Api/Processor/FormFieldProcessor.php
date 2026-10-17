<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Milhq\Entity\FormField;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<FormField, FormField>
 */
class FormFieldProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<FormField, FormField> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): FormField
    {
        $data->generateKeyIfEmpty();
        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
