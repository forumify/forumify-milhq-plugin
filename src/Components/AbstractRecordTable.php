<?php

declare(strict_types=1);

namespace Forumify\Milhq\Components;

use DateTimeInterface;
use Doctrine\ORM\QueryBuilder;
use Forumify\Core\Component\Table\AbstractDoctrineTable;
use Forumify\Milhq\Entity\Document;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Record\RecordInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Twig\Environment;

abstract class AbstractRecordTable extends AbstractDoctrineTable
{
    #[LiveProp]
    public Soldier $user;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp]
    public array $sort = ['createdAt' => self::SORT_DESC];

    protected TranslatorInterface $translator;
    protected Environment $twig;
    protected array $searchFields = ['text'];

    protected function buildTable(): void
    {
        $this
            ->addDateColumn()
            ->addColumn('text', [
                'class' => 'text-left text-small',
                'field' => 'text',
                'searchable' => false,
            ])
            ->addDocumentColumn();
    }

    protected function addDateColumn(): static
    {
        $this->addColumn('createdAt', [
            'class' => 'w-25 text-small',
            'field' => 'createdAt',
            'label' => 'Date',
            'renderer' => fn (DateTimeInterface $date) => $this->translator->trans('date', ['date' => $date]),
            'searchable' => false,
        ]);

        return $this;
    }

    protected function addDocumentColumn(bool $recordTextModal = false, ?string $itemKey = null): static
    {
        $propAccess = PropertyAccess::createPropertyAccessor();

        $this->addColumn('document', [
            'field' => 'document',
            'label' => '',
            'renderer' => fn (?Document $document, RecordInterface $record) => $this->twig->render('@ForumifyMilhqPlugin/frontend/components/record_table/documents.html.twig', [
                'document' => $document,
                'item' => $itemKey !== null
                    ? $propAccess->getValue($record, $itemKey)
                    : null,
                'record' => $record,
                'showRecordText' => $recordTextModal,
            ]),
            'searchable' => false,
            'sortable' => false,
        ]);

        return $this;
    }

    protected function getQuery(array $search): QueryBuilder
    {

        $qb = parent::getQuery($search)
            ->andWhere('e.user = :user')
            ->setParameter('user', $this->user)
        ;

        foreach ($this->searchFields as $searchField) {
            $parts = explode('.', $searchField);
            if (count($parts) < 2) {
                continue;
            }

            $join = reset($parts);
            $qb->join("e.$join", $join);
        }

        return $this->addSearchToQuery($qb);
    }

    protected function addSearchToQuery(QueryBuilder $qb): QueryBuilder
    {
        $query = trim($this->query);
        if (empty($query)) {
            return $qb;
        }

        $exprs = array_map(
            fn (string $field) => (!str_contains($field, '.') ? 'e.' : '') . "$field LIKE :search",
            $this->searchFields
        );

        return $qb
            ->andWhere($qb->expr()->orX(...$exprs))
            ->setParameter('search', "%$query%")
        ;
    }

    #[Required]
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    #[Required]
    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
    }
}
