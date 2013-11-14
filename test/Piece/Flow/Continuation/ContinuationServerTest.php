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
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow\Continuation;

use Stagehand\FSM\Event\EventInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

use Piece\Flow\Continuation\GarbageCollection\GarbageCollector;
use Piece\Flow\Pageflow\EventContext;
use Piece\Flow\Pageflow\PageflowRegistries;
use Piece\Flow\Pageflow\PageflowRegistry;
use Piece\Flow\Pageflow\PageflowRepository;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class ContinuationServerTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheDirectory;

    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $pageflowInstanceID;

    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $pageflowID;

    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $eventID;

    /**
     * @var \Piece\Flow\Continuation\ContinuationContextProvider
     * @since Property available since Release 2.0.0
     */
    protected $continuationContextProvider;

    public function getPageflowInstanceID()
    {
        return $this->pageflowInstanceID;
    }

    public function getPageflowID()
    {
        return $this->pageflowID;
    }

    public function getEventID()
    {
        return $this->eventID;
    }

    protected function setUp()
    {
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $this->continuationContextProvider = \Phake::mock('Piece\Flow\Continuation\ContinuationContextProvider');
        $self = $this;
        \Phake::when($this->continuationContextProvider)->getEventID()->thenGetReturnByLambda(function () use ($self) { return $self->getEventID(); });
        \Phake::when($this->continuationContextProvider)->getPageflowID()->thenGetReturnByLambda(function () use ($self) { return $self->getPageflowID(); });
        \Phake::when($this->continuationContextProvider)->getPageflowInstanceID()->thenGetReturnByLambda(function () use ($self) { return $self->getPageflowInstanceID(); });
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function startsPageflowInstancesForAnExclusivePageflow()
    {
        $pageflowInstanceRepository = $this->createPageflowInstanceRepository();
        $pageflowInstanceRepository->addPageflow('Counter', true);
        $continuationServer = new ContinuationServer($pageflowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);
        $continuationServer->setEventDispatcher(new EventDispatcher());

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance1 = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance2 = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageflowInstance2->getID(), $this->logicalNot($this->equalTo($pageflowInstance1->getID())));
        $this->assertThat($continuationServer->getPageflowInstanceRepository()->findByID($pageflowInstance1->getID()), $this->isNull());
        $this->assertThat($continuationServer->getPageflowInstanceRepository()->findByID($pageflowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function startsPageflowInstancesForANonExclusivePageflow()
    {
        $pageflowInstanceRepository = $this->createPageflowInstanceRepository();
        $pageflowInstanceRepository->addPageflow('Counter', false);
        $continuationServer = new ContinuationServer($pageflowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);
        $continuationServer->setEventDispatcher(new EventDispatcher());

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance1 = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance2 = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageflowInstance2->getID(), $this->logicalNot($this->equalTo($pageflowInstance1->getID())));
        $this->assertThat($continuationServer->getPageflowInstanceRepository()->findByID($pageflowInstance1->getID()), $this->logicalNot($this->isNull()));
        $this->assertThat($continuationServer->getPageflowInstanceRepository()->findByID($pageflowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function startsPageflowInstancesForMultiplePageflows()
    {
        $pageflowInstanceRepository = $this->createPageflowInstanceRepository();
        $pageflowInstanceRepository->addPageflow('Counter', false);
        $pageflowInstanceRepository->addPageflow('SecondCounter', false);
        $continuationServer = new ContinuationServer($pageflowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);
        $continuationServer->setEventDispatcher(new EventDispatcher());

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance1 = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->pageflowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance2 = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageflowInstance2->getID(), $this->logicalNot($this->equalTo($pageflowInstance1->getID())));
        $this->assertThat($continuationServer->getPageflowInstanceRepository()->findByID($pageflowInstance1->getID()), $this->logicalNot($this->isNull()));
        $this->assertThat($continuationServer->getPageflowInstanceRepository()->findByID($pageflowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function continuesAPageflowInstance()
    {
        $pageflowInstanceRepository = $this->createPageflowInstanceRepository();
        $pageflowInstanceRepository->addPageflow('Counter', false);
        $continuationServer = new ContinuationServer($pageflowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);
        $continuationServer->setEventDispatcher(new EventDispatcher());

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance1 = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = $pageflowInstance1->getID();
        $continuationServer->activate(new \stdClass());
        $pageflowInstance2 = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageflowInstance2->getID(), ($this->equalTo($pageflowInstance1->getID())));
        $this->assertThat($pageflowInstance2->getAttributes()->get('counter'), $this->equalTo(2));
    }

    /**
     * @test
     * @expectedException \Piece\Flow\Continuation\UnexpectedPageflowIDException
     */
    public function raisesAnExceptionWhenAnUnexpectedPageflowIdIsSpecifiedForTheSecondTimeOrLater()
    {
        $pageflowInstanceRepository = $this->createPageflowInstanceRepository();
        $pageflowInstanceRepository->addPageflow('Counter', false);
        $pageflowInstanceRepository->addPageflow('SecondCounter', false);
        $continuationServer = new ContinuationServer($pageflowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);
        $continuationServer->setEventDispatcher(new EventDispatcher());

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->pageflowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageflowInstanceID = $pageflowInstance->getID();
        $continuationServer->activate(new \stdClass());
    }

    /**
     * @param boolean $exclusive
     * @since Method available since Release 2.0.0
     *
     * @test
     * @dataProvider providePageflowExclusiveness
     */
    public function findsThePageflowInstanceByAPageflowId($exclusive)
    {
        $pageflowInstanceRepository = $this->createPageflowInstanceRepository();
        $pageflowInstanceRepository->addPageflow('Counter', $exclusive);
        $continuationServer = new ContinuationServer($pageflowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);
        $continuationServer->setEventDispatcher(new EventDispatcher());

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $continuationServer->shutdown();

        $this->assertThat($continuationServer->getPageflowInstanceRepository()->findByPageflowID('Counter'), $exclusive ? $this->identicalTo($continuationServer->getPageflowInstance()) : $this->isNull());
    }

    /**
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function providePageflowExclusiveness()
    {
        return array(array(true), array(false));
    }

    /**
     * @param integer $expirationTime
     * @param string  $firstTime
     * @param string  $secondTime
     * @param boolean $shouldRaiseException
     * @since Method available since Release 2.0.0
     *
     * @test
     * @dataProvider provideTimesForExpiration
     */
    public function raisesAnExceptionWhenThePageflowInstanceHasExpired($expirationTime, $firstTime, $secondTime, $shouldRaiseException)
    {
        $clock = \Phake::mock('Piece\Flow\Continuation\GarbageCollection\Clock');
        \Phake::when($clock)->now()
            ->thenReturn(new \DateTime($firstTime))
            ->thenReturn(new \DateTime($secondTime));
        $pageflowInstanceRepository = $this->createPageflowInstanceRepository();
        $pageflowInstanceRepository->addPageflow('Counter', false);
        $continuationServer = new ContinuationServer($pageflowInstanceRepository, new GarbageCollector($expirationTime, $clock));
        $continuationServer->setActionInvoker(\Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface'));
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);
        $continuationServer->setEventDispatcher(new EventDispatcher());

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->pageflowID = 'Counter';
        $this->eventID = null;
        $this->pageflowInstanceID = $pageflowInstance->getID();

        if ($shouldRaiseException) {
            try {
                $continuationServer->activate(new \stdClass());
                $continuationServer->shutdown();
                $this->fail('An expected exception has not been raised.');
            } catch (PageflowInstanceExpiredException $e) {
            }
        } else {
            $continuationServer->activate(new \stdClass());
            $continuationServer->shutdown();
        }
    }

    /**
     * @return array
     */
    public function provideTimesForExpiration()
    {
        return array(
            array(1440, '2012-08-09 15:43:00', '2012-08-09 16:07:00', false),
            array(1440, '2012-08-09 15:43:00', '2012-08-09 16:07:01', true),
        );
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function validatesTheLastReceivedEvent()
    {
        $pageflowInstanceRepository = $this->createPageflowInstanceRepository();
        $pageflowInstanceRepository->addPageflow('CheckLastEvent', false);
        $continuationServer = new ContinuationServer($pageflowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);
        $continuationServer->setEventDispatcher(new EventDispatcher());

        $this->pageflowID = 'CheckLastEvent';
        $this->eventID = 'nonExistingEvent';
        $this->pageflowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageflowInstance = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageflowInstance->getLastTransitionEvent(), $this->logicalNot($this->isNull()));
        $this->assertThat($pageflowInstance->getLastTransitionEvent()->getEventID(), $this->equalTo(EventInterface::EVENT_START));

        $this->pageflowID = 'CheckLastEvent';
        $this->eventID = 'DisplayEditConfirmFromDisplayEdit';
        $this->pageflowInstanceID = $pageflowInstance->getID();
        $continuationServer->activate(new \stdClass());
        $pageflowInstance = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageflowInstance->getLastTransitionEvent(), $this->logicalNot($this->isNull()));
        $this->assertThat($pageflowInstance->getLastTransitionEvent()->getEventID(), $this->equalTo('DisplayEditConfirmFromDisplayEdit'));

        $this->pageflowID = 'CheckLastEvent';
        $this->eventID = 'nonExistingEvent';
        $this->pageflowInstanceID = $pageflowInstance->getID();
        $continuationServer->activate(new \stdClass());
        $pageflowInstance = $continuationServer->getPageflowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageflowInstance->getLastTransitionEvent(), $this->isNull());
    }

    /**
     * @return \Piece\Flow\Pageflow\ActionInvokerInterface
     */
    protected function createCounterActionInvoker()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\Pageflow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke('setup', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) {
            $eventContext->getPageflow()->getAttributes()->set('counter', 0);
        });
        \Phake::when($actionInvoker)->invoke('increase', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) {
            $eventContext->getPageflow()->getAttributes()->set('counter', $eventContext->getPageflow()->getAttributes()->get('counter') + 1);
        });

        return $actionInvoker;
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @return \Piece\Flow\Continuation\PageflowInstanceRepository
     */
    protected function createPageflowInstanceRepository()
    {
        return new PageflowInstanceRepository(new PageflowRepository(new PageflowRegistries(array(new PageflowRegistry($this->cacheDirectory, '.yaml'))), $this->cacheDirectory, true));
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
