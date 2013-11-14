<?php
/*
 * Copyright (c) 2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
use Stagehand\FSM\StateMachine\StateMachine;

/**
 * The event handler to handle all events raised on the specified Pageflow object.
 *
 * @since Class available since Release 0.1.0
 */
class EventHandler
{
    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $actionID;

    /**
     * @var \Piece\Flow\Pageflow\PageflowInterface
     */
    protected $pageflow;

    /**
     * Wraps a action up with an EventHandler object.
     *
     * @param string                                 $actionID
     * @param \Piece\Flow\Pageflow\PageflowInterface $pageflow
     */
    public function __construct($actionID, PageflowInterface $pageflow)
    {
        $this->actionID = $actionID;
        $this->pageflow = $pageflow;
    }

    /**
     * Invokes the action with the event context.
     *
     * @param  \Stagehand\FSM\Event\EventInterface      $event
     * @param  mixed                                    $payload
     * @param  \Stagehand\FSM\StateMachine\StateMachine $fsm
     * @return string
     */
    public function invokeAction(EventInterface $event, $payload, StateMachine $fsm)
    {
        return $this->pageflow->getActionInvoker()->invoke($this->actionID, new EventContext($event, $payload, $this->pageflow));
    }

    /**
     * Invokes the action with the event context and triggers an event returned
     * from the action.
     *
     * @param  \Stagehand\FSM\Event\EventInterface         $event
     * @param  mixed                                       $payload
     * @param  \Stagehand\FSM\StateMachine\StateMachine    $fsm
     * @throws \Piece\Flow\Pageflow\EventNotFoundException
     */
    public function invokeActionAndTriggerEvent(EventInterface $event, $payload, StateMachine $fsm)
    {
        $eventID = $this->invokeAction($event, $payload, $fsm);
        if (!is_null($eventID)) {
            if (is_null($fsm->getCurrentState()->getEvent($eventID))) {
                throw new EventNotFoundException(sprintf(
                    'The event [ %s ] returned from the action [ %s ] is not found on the current state [ %s ].',
                    $eventID,
                    $this->actionID,
                    $fsm->getCurrentState()->getStateID()
                ));
            }

            $fsm->queueEvent($eventID);
        }
    }
}
