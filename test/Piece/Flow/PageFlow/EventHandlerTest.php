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

use Stagehand\FSM\StateMachine\StateMachine;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class EventHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @since Method available since Release 2.0.0
     */
    public function invokesTheAction()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke($this->anything(), $this->anything())->thenReturn('foo');
        $pageFlow = \Phake::mock('Piece\Flow\PageFlow\PageFlowInterface');
        \Phake::when($pageFlow)->getActionInvoker()->thenReturn($actionInvoker);
        $event = \Phake::mock('Stagehand\FSM\Event\EventInterface');
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageFlow);
        $nextEvent = $eventHandler->invokeAction($event, $payload, new StateMachine());

        $this->assertThat($nextEvent, $this->equalTo('foo'));
        \Phake::verify($actionInvoker)->invoke($this->equalTo('my_controller:onRegister'), \Phake::capture($eventContext)); /* @var $eventContext \Piece\Flow\PageFlow\EventContext */
        $this->assertThat($eventContext->getEvent(), $this->identicalTo($event));
        $this->assertThat($eventContext->getPageFlow(), $this->identicalTo($pageFlow));
        $this->assertThat($eventContext->getPayload(), $this->identicalTo($payload));
    }

    /**
     * @test
     * @since Method available since Release 2.0.0
     */
    public function invokesTheActionAndTriggersTheNextEvent()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke($this->anything(), $this->anything())->thenReturn('foo');
        $event = \Phake::mock('Stagehand\FSM\Event\EventInterface');
        $state = \Phake::mock('Stagehand\FSM\State\StateInterface');
        \Phake::when($state)->getEvent($this->anything())->thenReturn($event);
        $fsm = \Phake::mock('Stagehand\FSM\StateMachine\StateMachine');
        \Phake::when($fsm)->getCurrentState()->thenReturn($state);
        $pageFlow = \Phake::mock('Piece\Flow\PageFlow\PageFlowInterface');
        \Phake::when($pageFlow)->getActionInvoker()->thenReturn($actionInvoker);
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageFlow);
        $eventHandler->invokeActionAndTriggerEvent($event, $payload, $fsm);

        \Phake::verify($actionInvoker)->invoke($this->equalTo('my_controller:onRegister'), \Phake::capture($eventContext)); /* @var $eventContext \Piece\Flow\PageFlow\EventContext */
        $this->assertThat($eventContext->getEvent(), $this->identicalTo($event));
        $this->assertThat($eventContext->getPageFlow(), $this->identicalTo($pageFlow));
        $this->assertThat($eventContext->getPayload(), $this->identicalTo($payload));

        \Phake::verify($fsm)->getCurrentState();
        \Phake::verify($state)->getEvent($this->equalTo('foo'));
        \Phake::verify($fsm)->queueEvent('foo');
    }

    /**
     * @test
     * @expectedException \Piece\Flow\PageFlow\EventNotFoundException
     * @since Method available since Release 2.0.0
     */
    public function raisesAnExceptionWhenTheNextEventIsNotFound()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke($this->anything(), $this->anything())->thenReturn('NonExistingEventID');
        $event = \Phake::mock('Stagehand\FSM\Event\EventInterface');
        $state = \Phake::mock('Stagehand\FSM\State\StateInterface');
        $fsm = \Phake::mock('Stagehand\FSM\StateMachine\StateMachine');
        \Phake::when($fsm)->getCurrentState()->thenReturn($state);
        $pageFlow = \Phake::mock('Piece\Flow\PageFlow\PageFlowInterface');
        \Phake::when($pageFlow)->getActionInvoker()->thenReturn($actionInvoker);
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageFlow);
        $eventHandler->invokeActionAndTriggerEvent($event, $payload, $fsm);
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
