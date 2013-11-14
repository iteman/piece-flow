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

use Stagehand\FSM\StateMachine\StateMachine;

/**
 * @since Class available since Release 0.1.0
 */
class EventHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @since Method available since Release 2.0.0
     */
    public function invokesTheAction()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke($this->anything(), $this->anything())->thenReturn('foo');
        $pageflow = \Phake::mock('Piece\Flow\Pageflow\PageflowInterface');
        \Phake::when($pageflow)->getActionInvoker()->thenReturn($actionInvoker);
        $event = \Phake::mock('Stagehand\FSM\Event\EventInterface');
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageflow);
        $nextEvent = $eventHandler->invokeAction($event, $payload, new StateMachine());

        $this->assertThat($nextEvent, $this->equalTo('foo'));
        \Phake::verify($actionInvoker)->invoke($this->equalTo('my_controller:onRegister'), \Phake::capture($eventContext)); /* @var $eventContext \Piece\Flow\Pageflow\EventContext */
        $this->assertThat($eventContext->getEvent(), $this->identicalTo($event));
        $this->assertThat($eventContext->getPageflow(), $this->identicalTo($pageflow));
        $this->assertThat($eventContext->getPayload(), $this->identicalTo($payload));
    }

    /**
     * @test
     * @since Method available since Release 2.0.0
     */
    public function invokesTheActionAndTriggersTheNextEvent()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke($this->anything(), $this->anything())->thenReturn('foo');
        $event = \Phake::mock('Stagehand\FSM\Event\EventInterface');
        $state = \Phake::mock('Stagehand\FSM\State\StateInterface');
        \Phake::when($state)->getEvent($this->anything())->thenReturn($event);
        $fsm = \Phake::mock('Stagehand\FSM\StateMachine\StateMachine');
        \Phake::when($fsm)->getCurrentState()->thenReturn($state);
        $pageflow = \Phake::mock('Piece\Flow\Pageflow\PageflowInterface');
        \Phake::when($pageflow)->getActionInvoker()->thenReturn($actionInvoker);
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageflow);
        $eventHandler->invokeActionAndTriggerEvent($event, $payload, $fsm);

        \Phake::verify($actionInvoker)->invoke($this->equalTo('my_controller:onRegister'), \Phake::capture($eventContext)); /* @var $eventContext \Piece\Flow\Pageflow\EventContext */
        $this->assertThat($eventContext->getEvent(), $this->identicalTo($event));
        $this->assertThat($eventContext->getPageflow(), $this->identicalTo($pageflow));
        $this->assertThat($eventContext->getPayload(), $this->identicalTo($payload));

        \Phake::verify($fsm)->getCurrentState();
        \Phake::verify($state)->getEvent($this->equalTo('foo'));
        \Phake::verify($fsm)->queueEvent('foo');
    }

    /**
     * @test
     * @expectedException \Piece\Flow\Pageflow\EventNotFoundException
     * @since Method available since Release 2.0.0
     */
    public function raisesAnExceptionWhenTheNextEventIsNotFound()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke($this->anything(), $this->anything())->thenReturn('NonExistingEventID');
        $event = \Phake::mock('Stagehand\FSM\Event\EventInterface');
        $state = \Phake::mock('Stagehand\FSM\State\StateInterface');
        $fsm = \Phake::mock('Stagehand\FSM\StateMachine\StateMachine');
        \Phake::when($fsm)->getCurrentState()->thenReturn($state);
        $pageflow = \Phake::mock('Piece\Flow\Pageflow\PageflowInterface');
        \Phake::when($pageflow)->getActionInvoker()->thenReturn($actionInvoker);
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageflow);
        $eventHandler->invokeActionAndTriggerEvent($event, $payload, $fsm);
    }
}
