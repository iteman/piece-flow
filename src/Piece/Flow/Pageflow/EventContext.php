<?php
/*
 * Copyright (c) 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\Pageflow;

use Stagehand\FSM\Event\EventInterface;

/**
 * @since Class available since Release 2.0.0
 */
class EventContext
{
    /**
     * @var \Stagehand\FSM\Event\EventInterface
     */
    protected $event;

    /**
     * @var mixed
     */
    protected $payload;

    /**
     * @var \Piece\Flow\Pageflow\PageflowInterface
     */
    protected $pageflow;

    /**
     * @param \Stagehand\FSM\Event\EventInterface    $event
     * @param mixed                                  $payload
     * @param \Piece\Flow\Pageflow\PageflowInterface $pageflow
     */
    public function __construct(EventInterface $event, $payload, PageflowInterface $pageflow)
    {
        $this->event = $event;
        $this->payload = $payload;
        $this->pageflow = $pageflow;
    }

    /**
     * @return \Stagehand\FSM\Event\EventInterface
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @return \Piece\Flow\Pageflow\PageflowInterface
     */
    public function getPageflow()
    {
        return $this->pageflow;
    }
}
