<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Milhq\Entity\CourseClass;
use Forumify\Milhq\Service\CourseClassService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<CourseClass, CourseClass>
 */
class CourseClassProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<CourseClass, CourseClass> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly CourseClassService $courseClassService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseClass
    {
        $previous = $context['previous_data'] ?? null;
        $alreadyProcessed = $previous instanceof CourseClass && $previous->getResult();
        if ($alreadyProcessed && !$data->getResult()) {
            throw new UnprocessableEntityHttpException('The results of this class have already been processed.');
        }

        $class = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        if ($alreadyProcessed) {
            $this->courseClassService->updateResult($class);
        } elseif ($class->getResult()) {
            $this->courseClassService->processResult($class);
        }

        return $class;
    }
}
