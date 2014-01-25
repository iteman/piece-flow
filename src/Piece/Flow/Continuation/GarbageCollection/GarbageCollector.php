<?php
/*
 * Copyright (c) 2007, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\Continuation\GarbageCollection;

/**
 * The garbage collector for expired page flow instances.
 *
 * @since Class available since Release 1.11.0
 */
class GarbageCollector
{
    protected $expirationTime;

    /**
     * @var \Piece\Flow\Continuation\GarbageCollection\Clock
     * @since Property available since Release 2.0.0
     */
    protected $clock;

    /**
     * @var \Piece\Flow\Continuation\GarbageCollection\GarbageMarker[]
     */
    protected $garbageMarkers = array();

    /**
     * Sets the expiration time in seconds.
     *
     * @param integer                                          $expirationTime
     * @param \Piece\Flow\Continuation\GarbageCollection\Clock $clock
     */
    public function __construct($expirationTime, Clock $clock)
    {
        $this->expirationTime = $expirationTime;
        $this->clock = $clock;
    }

    /**
     * Updates the state of the specified page flow instance.
     *
     * @param string $pageflowInstanceID
     */
    public function update($pageflowInstanceID)
    {
        if (array_key_exists($pageflowInstanceID, $this->garbageMarkers)) {
            $this->garbageMarkers[$pageflowInstanceID]->updateModificationTimestamp($this->clock->now()->getTimestamp());
        } else {
            $this->garbageMarkers[$pageflowInstanceID] = new GarbageMarker($this->clock->now()->getTimestamp());
        }
    }

    /**
     * Returns whether or not the specified page flow instance is marked as
     * a target for sweeping.
     *
     * @param  string  $pageflowInstanceID
     * @return boolean
     */
    public function shouldSweep($pageflowInstanceID)
    {
        if (array_key_exists($pageflowInstanceID, $this->garbageMarkers)) {
            return $this->garbageMarkers[$pageflowInstanceID]->isEnabled();
        } else {
            return false;
        }
    }

    /**
     * Marks expired page flow instances as a target for sweeping.
     */
    public function mark()
    {
        reset($this->garbageMarkers);
        while (list($pageflowInstanceID, $marker) = each($this->garbageMarkers)) {
            if (!$marker->isSwept() && $marker->isExpired($this->clock->now()->getTimestamp(), $this->expirationTime)) {
                $marker->markAsEnabled();
            }
        }
    }

    /**
     * Sweeps all marked page flow instance with the specified callback.
     *
     * @param callback $callback
     */
    public function sweep($callback)
    {
        reset($this->garbageMarkers);
        while (list($pageflowInstanceID, $marker) = each($this->garbageMarkers)) {
            if (!$marker->isSwept() && $marker->isEnabled()) {
                call_user_func($callback, $pageflowInstanceID);
                $marker->markAsSwept();
            }
        }
    }
}
