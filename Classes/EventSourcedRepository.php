<?php
namespace Flowpack\EventStore;

/*
 * This file is part of the Flowpack.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Flowpack\Cqrs\Domain\AggregateRootInterface;
use Flowpack\Cqrs\Domain\Exception\AggregateRootNotFoundException;
use Flowpack\Cqrs\Domain\RepositoryInterface;
use Flowpack\Cqrs\Event\EventBusInterface;
use Flowpack\Cqrs\Event\EventInterface;
use Flowpack\EventStore\Exception\EventStreamNotFoundException;
use TYPO3\Flow\Annotations as Flow;

/**
 * EventSerializer
 */
abstract class EventSourcedRepository implements RepositoryInterface
{
    /**
     * @var EventStoreInterface
     * @Flow\Inject
     */
    protected $eventStore;

    /**
     * @var EventBusInterface
     * @Flow\Inject
     */
    protected $eventBus;

    /**
     * @param string $identifier
     * @return AggregateRootInterface
     * @throws AggregateRootNotFoundException
     */
    public function findByIdentifier($identifier): AggregateRootInterface
    {
        try {
            /** @var EventStream $eventStream */
            $eventStream = $this->eventStore->get($identifier);
        } catch (EventStreamNotFoundException $e) {
            throw new AggregateRootNotFoundException(sprintf(
                "AggregateRoot with id '%s' not found", $identifier
            ), 1471077948);
        }

        $reflection = new \ReflectionClass($eventStream->getAggregateName());

        /** @var AggregateRootInterface $aggregateRoot */
        $aggregateRoot = $reflection->newInstanceWithoutConstructor();
        $aggregateRoot->reconstituteFromEventStream($eventStream);

        return $aggregateRoot;
    }

    /**
     * @param  AggregateRootInterface $aggregate
     * @return void
     */
    public function save(AggregateRootInterface $aggregate)
    {
        try {
            $stream = $this->eventStore
                ->get($aggregate->getAggregateIdentifier());
        } catch (EventStreamNotFoundException $e) {
            $stream = new EventStream(
                $aggregate->getAggregateIdentifier(),
                get_class($aggregate),
                [],
                1
            );
        } finally {
            $uncommitedEvents = $aggregate->pullUncommittedEvents();
            $stream->addEvents($uncommitedEvents);
        }

        $this->eventStore->commit($stream);

        /** @var EventInterface $event */
        foreach ($uncommitedEvents as $event) {
            $this->eventBus->handle($event);
        }
    }
}
