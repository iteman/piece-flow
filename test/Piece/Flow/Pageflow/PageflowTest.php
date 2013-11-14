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

namespace Piece\Flow\Pageflow;

use Stagehand\FSM\State\StateInterface;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
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
