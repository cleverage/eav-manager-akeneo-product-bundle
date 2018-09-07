<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Debug;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Allows the injection of the stopwatch for profiling purposes
 */
interface StopwatchInjectableInterface
{
    /**
     * @param Stopwatch $stopwatch
     */
    public function setStopwatch(Stopwatch $stopwatch = null);
}
