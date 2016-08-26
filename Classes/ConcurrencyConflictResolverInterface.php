<?php
namespace Ttree\EventStore;

/*
 * This file is part of the Ttree.Cqrs package.
 *
 * (c) Hand crafted with love in each details by medialib.tv
 */

use Ttree\Cqrs\Event\EventInterface;
use TYPO3\Flow\Annotations as Flow;

/**
 * ConcurrencyConflictResolverInterface
 */
interface ConcurrencyConflictResolverInterface
{
    /**
     * Check if an event conflict with a previous event
     *
     * @param string $eventType
     * @param array $previousEventTypes
     * @return boolean
     */
    public function conflictWith(string $eventType, array $previousEventTypes): bool;

    /**
     * Register conflicting events
     *
     * The value of $conflictsWith is an associative array, the keys are the event type and
     * the value is the exception message.
     *
     * @param string $eventType
     * @param array $conflictsWith
     * @return void
     */
    public static function registerConflictWith(string $eventType, array $conflictsWith);
}
