<?php

declare(strict_types = 1);

namespace FiveLab\Component\Diagnostic\Runner;

/**
 * The list of available vents.
 */
final class RunnerEvents
{
    /**
     * Emit this event before run check.
     *
     * @see \FiveLab\Component\Diagnostic\Runner\Event\BeforeRunCheckEvent
     */
    public const RUN_CHECK_BEFORE = 'check.run.before';

    /**
     * Emit this event after complete run check.
     *
     * @see \FiveLab\Component\Diagnostic\Runner\Event\CompleteRunCheckEvent
     */
    public const RUN_CHECK_COMPLETE = 'check.run.complete';
}
