<?php

declare(strict_types=1);

namespace Forumify\Milhq\Api\Processor;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Milhq\Entity\CourseClassStudent;
use Forumify\Milhq\Service\CourseClassService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<CourseClassStudent, CourseClassStudent|null>
 */
class CourseClassStudentProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<CourseClassStudent, CourseClassStudent> $persistProcessor
     * @param ProcessorInterface<CourseClassStudent, null> $removeProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private readonly ProcessorInterface $removeProcessor,
        private readonly CourseClassService $courseClassService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?CourseClassStudent
    {
        $class = $data->getClass();
        if ($operation instanceof DeleteOperationInterface) {
            $class->removeStudent($data);
            $student = $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        } else {
            $student = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($class->getResult()) {
            $this->courseClassService->updateResult($class);
        }

        return $student;
    }
}
