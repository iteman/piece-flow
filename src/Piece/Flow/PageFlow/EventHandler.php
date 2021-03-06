<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

namespace Piece\Flow\PageFlow;

use Stagehand\FSM\Event\EventInterface;
use Stagehand\FSM\StateMachine\StateMachine;

/**
 * The event handler to handle all events raised on the specified PageFlow object.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class EventHandler
{
    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $actionID;

    /**
     * @var \Piece\Flow\PageFlow\PageFlowInterface
     */
    protected $pageFlow;

    /**
     * Wraps a action up with an EventHandler object.
     *
     * @param string                                 $actionID
     * @param \Piece\Flow\PageFlow\PageFlowInterface $pageFlow
     */
    public function __construct($actionID, PageFlowInterface $pageFlow)
    {
        $this->actionID = $actionID;
        $this->pageFlow = $pageFlow;
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
        return $this->pageFlow->getActionInvoker()->invoke($this->actionID, new EventContext($event, $payload, $this->pageFlow));
    }

    /**
     * Invokes the action with the event context and triggers an event returned
     * from the action.
     *
     * @param  \Stagehand\FSM\Event\EventInterface         $event
     * @param  mixed                                       $payload
     * @param  \Stagehand\FSM\StateMachine\StateMachine    $fsm
     * @throws \Piece\Flow\PageFlow\EventNotFoundException
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

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
