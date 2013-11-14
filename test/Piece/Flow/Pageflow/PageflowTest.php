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

use Stagehand\FSM\State\StateInterface;

/**
 * @since Class available since Release 0.1.0
 */
class PageflowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Piece\Flow\Pageflow\PageflowFactory
     * @since Property available since Release 2.0.0
     */
    protected $pageflowFactory;

    protected function setUp()
    {
        $this->pageflowFactory = new PageflowFactory(new PageflowRegistries(array(new PageflowRegistry(__DIR__ . '/' . basename(__FILE__, '.php'), '.flow'))));
    }

    /**
     * @test
     */
    public function getsTheViewOfTheCurrentState()
    {
        $pageflow = $this->pageflowFactory->create('Registration');
        $pageflow->setActionInvoker(\Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface'));

        $this->assertThat($pageflow->getCurrentView(), $this->isNull());

        $pageflow->start();

        $this->assertThat($pageflow->getCurrentView(), $this->equalTo('Input'));
    }

    /**
     * @test
     */
    public function triggersAnEvent()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke('onValidation', $this->anything())->thenReturn('valid');
        \Phake::when($actionInvoker)->invoke('onRegistration', $this->anything())->thenReturn('done');
        $pageflow = $this->pageflowFactory->create('Registration');
        $pageflow->setActionInvoker($actionInvoker);

        $this->assertThat($pageflow->isInFinalState(), $this->isFalse());

        $pageflow->start();
        $pageflow->triggerEvent('next');
        $pageflow->triggerEvent('next');

        $this->assertThat($pageflow->getCurrentState()->getStateID(), $this->equalTo(StateInterface::STATE_FINAL));
        $this->assertThat($pageflow->getPreviousState()->getStateID(), $this->equalTo('Finish'));
        $this->assertThat($pageflow->isInFinalState(), $this->isTrue());
        \Phake::verify($actionInvoker)->invoke('onValidation', $this->anything());
        \Phake::verify($actionInvoker)->invoke('onRegistration', $this->anything());
    }

    /**
     * @expectedException \Piece\Flow\Pageflow\PageflowNotActivatedException
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function raisesAnExceptionWhenAnEventIsTriggeredIfThePageflowIsNotActive()
    {
        $pageflow = \Phake::partialMock('Piece\Flow\Pageflow\Pageflow', 'foo');
        $pageflow->triggerEvent('bar');
    }

    /**
     * @test
     */
    public function accessesTheAttributes()
    {
        $pageflow = $this->pageflowFactory->create('Registration');
        $pageflow->setActionInvoker(\Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface'));
        $pageflow->start();
        $pageflow->getAttributes()->set('foo', 'bar');

        $this->assertThat($pageflow->getAttributes()->has('foo'), $this->isTrue());
        $this->assertThat($pageflow->getAttributes()->get('foo'), $this->equalTo('bar'));
    }

    /**
     * @expectedException \Piece\Flow\Pageflow\ProtectedEventException
     * @since Method available since Release 1.2.0
     *
     * @test
     */
    public function raisesAnExceptionWhenThePageflowDefinitionHasAProtectedEvent()
    {
        $this->pageflowFactory->create('ProtectedEvent', \Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface'));
    }

    /**
     * @expectedException \Piece\Flow\Pageflow\ProtectedStateException
     * @since Method available since Release 1.2.0
     *
     * @test
     */
    public function raisesAnExceptionWhenThePageflowDefinitionHasAProtectedState()
    {
        $this->pageflowFactory->create('ProtectedState', \Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface'));
    }
}
