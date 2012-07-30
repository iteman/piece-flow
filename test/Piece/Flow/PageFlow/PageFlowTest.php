<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

namespace Piece\Flow\PageFlow;

use Stagehand\FSM\Event;
use Stagehand\FSM\FSMAlreadyShutdownException;
use Stagehand\FSM\State;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class PageFlowTest extends \PHPUnit_Framework_TestCase
{
    protected $source;
    protected $cacheDirectory;

    /**
     * @var \Piece\Flow\PageFlow\PageFlowFactory
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowFactory;

    protected function setUp()
    {
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $this->source = "{$this->cacheDirectory}/Registration.yaml";
        $this->pageFlowFactory = new PageFlowFactory();
    }

    public function testGettingView()
    {
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->start();

        $this->assertEquals('Form', $flow->getView());
    }

    public function testGettingPreviousStateName()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('isPermitted', $this->anything())->thenReturn(true);
        \Phake::when($actionInvoker)->invoke('validateInput', $this->anything())->thenReturn('succeed');
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker($actionInvoker);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals('processSubmitDisplayForm', $flow->getPreviousStateName());
    }

    public function testGettingCurrentStateName()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('isPermitted', $this->anything())->thenReturn(true);
        \Phake::when($actionInvoker)->invoke('validateInput', $this->anything())->thenReturn('succeed');
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker($actionInvoker);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals('ConfirmForm', $flow->getCurrentStateName());
    }

    public function testTriggeringEventAndInvokingTransitionAction()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('isPermitted', $this->anything())->thenReturn(true);
        \Phake::when($actionInvoker)->invoke('validateInput', $this->anything())->thenReturn('succeed');
        \Phake::when($actionInvoker)->invoke('validateConfirmation', $this->anything())->thenReturn('succeed');
        \Phake::when($actionInvoker)->invoke('register', $this->anything())->thenReturn('succeed');
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker($actionInvoker);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertThat($flow->getCurrentStateName(), $this->equalTo('ConfirmForm'));

        $flow->triggerEvent('submit');

        $this->assertThat($flow->getCurrentStateName(), $this->equalTo(State::STATE_FINAL));
    }

    public function testTriggeringRaiseErrorEvent()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('isPermitted', $this->anything())->thenReturn(true);
        \Phake::when($actionInvoker)->invoke('validateInput', $this->anything())->thenReturn('raiseError');
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker($actionInvoker);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertThat($flow->getCurrentStateName(), $this->equalTo('DisplayForm'));
        $this->assertThat($flow->getPreviousStateName(), $this->equalTo('processSubmitDisplayForm'));
    }

    public function testActivity()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker($actionInvoker);
        $flow->start();

        \Phake::verify($actionInvoker)->invoke('countDisplay', $this->anything());

        $flow->triggerEvent('foo');
        $flow->triggerEvent('bar');

        \Phake::verify($actionInvoker, \Phake::times(3))->invoke('countDisplay', $this->anything());
    }

    public function testExitAndEntryActions()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('validateInput', $this->anything())
            ->thenReturn('succeed');
        \Phake::when($actionInvoker)->invoke('isPermitted', $this->anything())
            ->thenReturn(true);
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker($actionInvoker);
        $flow->start();

        \Phake::verify($actionInvoker)->invoke('setupForm', $this->anything());

        $flow->triggerEvent('submit');

        \Phake::verify($actionInvoker)->invoke('teardownForm', $this->anything());
    }

    public function testSettingAttribute()
    {
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->start();
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue($flow->hasAttribute('foo'));
        $this->assertEquals('bar', $flow->getAttribute('foo'));
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     */
    public function testFailureToSetAttributeBeforeStartingFlow()
    {
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->setAttribute('foo', 'bar');
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     */
    public function testFailureToSetPayloadBeforeConfiguringFlow()
    {
        $flow = new PageFlow();
        $flow->setPayload(new \stdClass());
    }

    public function testOptionalElements()
    {
        $flow = $this->pageFlowFactory->create("{$this->cacheDirectory}/optional.yaml");
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertEquals('foo', $flow->getView());
    }

    public function testInitialAndFinalActionsWithYAML()
    {
        $this->assertInitialAndFinalActions('/initial.yaml');
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     */
    public function testFailureToGetViewBeforeStartingFlow()
    {
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->getView();
    }

    /**
     * @expectedException \Piece\Flow\PageFlow\InvalidTransitionException
     */
    public function testInvalidTransition()
    {
        $flow = $this->pageFlowFactory->create("{$this->cacheDirectory}/invalid.yaml");
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->setPayload(new \stdClass());
        $flow->start();
        $flow->triggerEvent('go');
        $flow->getView();
    }

    public function testCheckingWhetherCurrentStateIsFinalState()
    {
        $flow = $this->pageFlowFactory->create("{$this->cacheDirectory}/initial.yaml");
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertFalse($flow->isFinalState());

        $flow->triggerEvent('go');

        $this->assertTrue($flow->isFinalState());
    }

    public function testRemovingAttribute()
    {
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->start();
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue($flow->hasAttribute('foo'));

        $flow->removeAttribute('foo');

        $this->assertFalse($flow->hasAttribute('foo'));
    }

    public function testClearingAttributes()
    {
        $flow = $this->pageFlowFactory->create($this->source);
        $flow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $flow->start();
        $flow->setAttribute('foo', 'bar');
        $flow->setAttribute('bar', 'baz');

        $this->assertTrue($flow->hasAttribute('foo'));
        $this->assertTrue($flow->hasAttribute('bar'));

        $flow->clearAttributes();

        $this->assertFalse($flow->hasAttribute('foo'));
        $this->assertFalse($flow->hasAttribute('bar'));
    }

    /**
     * @since Method available since Release 1.2.0
     */
    public function testToPreventTriggeringProtectedEvents()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke($this->anything(), $this->anything())
            ->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) {
                if ($eventContext->getPageFlow()->hasAttribute('numberOfUpdate')) {
                    $numberOfUpdate = $eventContext->getPageFlow()->getAttribute('numberOfUpdate');
                } else {
                    $numberOfUpdate = 0;
                }

                ++$numberOfUpdate;
                $eventContext->getPageFlow()->setAttribute('numberOfUpdate', $numberOfUpdate);
            });

        $flow = $this->pageFlowFactory->create("{$this->cacheDirectory}/CDPlayer.yaml");
        $flow->setActionInvoker($actionInvoker);
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(1, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent('foo');

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(2, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(Event::EVENT_ENTRY);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(3, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(Event::EVENT_EXIT);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(4, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(Event::EVENT_START);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(5, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(Event::EVENT_END);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(6, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(Event::EVENT_DO);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(7, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent('play');

        $this->assertEquals('Playing', $flow->getCurrentStateName());
        $this->assertEquals(7, $flow->getAttribute('numberOfUpdate'));
    }

    /**
     * @expectedException \Piece\Flow\PageFlow\ProtectedEventException
     * @since Method available since Release 1.2.0
     */
    public function testProtectedEvents()
    {
        $this->pageFlowFactory->create("{$this->cacheDirectory}/ProtectedEvents.yaml", \Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
    }

    /**
     * @expectedException \Piece\Flow\PageFlow\ProtectedStateException
     * @since Method available since Release 1.2.0
     */
    public function testProtectedStates()
    {
        $this->pageFlowFactory->create("{$this->cacheDirectory}/ProtectedStates.yaml", \Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
    }

    /**
     * @since Method available since Release 1.3.0
     */
    public function testInvalidEventFromATransitionActionsOrActivities()
    {
        $actionInvoker1 = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker1)->invoke('register', $this->anything())->thenReturn('invalidEventFromRegister');
        $flow1 = $this->pageFlowFactory->create("{$this->cacheDirectory}/InvalidEventFromTransitionActionsOrActivities.yaml");
        $flow1->setActionInvoker($actionInvoker1);
        $flow1->setPayload(new \stdClass());
        $flow1->start();

        $this->assertEquals('DisplayForm', $flow1->getCurrentStateName());

        $flow1->triggerEvent('foo');

        $this->assertEquals('DisplayForm', $flow1->getCurrentStateName());

        try {
            $flow1->triggerEvent('register');
            $this->fail('An expected exception has not been raised.');
        } catch (EventNotFoundException $e) {
        }

        $this->assertEquals('ProcessRegister', $flow1->getCurrentStateName());

        $actionInvoker2 = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker2)->invoke('register', $this->anything())->thenReturn('goDisplayFinish');
        \Phake::when($actionInvoker2)->invoke('setupFinish', $this->anything())->thenReturn('invalidEventFromSetupFinish');
        $flow2 = $this->pageFlowFactory->create("{$this->cacheDirectory}/InvalidEventFromTransitionActionsOrActivities.yaml");
        $flow2->setActionInvoker($actionInvoker2);
        $flow2->setPayload(new \stdClass());
        $flow2->start();

        $this->assertEquals('DisplayForm', $flow2->getCurrentStateName());

        $flow2->triggerEvent('foo');

        $this->assertEquals('DisplayForm', $flow2->getCurrentStateName());

        try {
            $flow2->triggerEvent('register');
            $this->fail('An expected exception has not been raised.');
        } catch (EventNotFoundException $e) {
        }

        $this->assertEquals('DisplayFinish', $flow2->getCurrentStateName());
    }

    /**
     * @since Method available since Release 1.4.0
     */
    public function testProblemThatActivityIsInvokedTwiceUnexpectedly()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('validate', $this->anything())->thenReturn('goDisplayConfirmation');
        $flow = $this->pageFlowFactory->create("{$this->cacheDirectory}/ProblemThatActivityIsInvokedTwiceUnexpectedly.yaml");
        $flow->setActionInvoker($actionInvoker);
        $flow->setPayload(new \stdClass());
        $flow->start();

        \Phake::verify($actionInvoker)->invoke('setupForm', $this->anything());

        $flow->triggerEvent('confirmForm');

        \Phake::verify($actionInvoker)->invoke('validate', $this->anything());
        \Phake::verify($actionInvoker)->invoke('setupConfirmation', $this->anything());
    }

    protected function assertInitialAndFinalActions($source)
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        $flow = $this->pageFlowFactory->create("{$this->cacheDirectory}/$source");
        $flow->setActionInvoker($actionInvoker);
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertEquals('start', $flow->getView());
        \Phake::verify($actionInvoker)->invoke('initialize', $this->anything());
        \Phake::verify($actionInvoker, \Phake::times(0))->invoke('finalize', $this->anything());

        $flow->triggerEvent('go');

        $this->assertEquals('end', $flow->getView());
        \Phake::verify($actionInvoker)->invoke('finalize', $this->anything());

        try {
            $flow->triggerEvent('go');
            $this->fail('An expected exception has not been raised.');
        } catch (FSMAlreadyShutdownException $e) {
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