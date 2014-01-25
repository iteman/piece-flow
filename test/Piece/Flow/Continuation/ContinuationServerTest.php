<?php
/*
 * Copyright (c) 2006-2008, 2012-2014 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
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
 * @since Class available since Release 1.14.0
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
        $continuationServer = $this->createContinuationServer(array('Counter'));
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
        $continuationServer = $this->createContinuationServer(array());
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
        $continuationServer = $this->createContinuationServer(array());
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
        $continuationServer = $this->createContinuationServer(array());
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
        $continuationServer = $this->createContinuationServer(array());
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
        $continuationServer = $this->createContinuationServer($exclusive ? array('Counter') : array());
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
        $continuationServer = $this->createContinuationServer(array(), new GarbageCollector($expirationTime, $clock));
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
        $continuationServer = $this->createContinuationServer(array());
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
        return new PageflowInstanceRepository();
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @return \Piece\Flow\Pageflow\PageflowRepository
     */
    protected function createPageflowRepository()
    {
        return new PageflowRepository(new PageflowRegistries(array(new PageflowRegistry($this->cacheDirectory, '.yaml'))), $this->cacheDirectory, true);
    }

    /**
     * @param  array                                                       $exclusivePageflows
     * @param  \Piece\Flow\Continuation\GarbageCollection\GarbageCollector $garbageCollector
     * @return \Piece\Flow\Continuation\ContinuationServer
     * @since Method available since Release 2.0.0
     */
    protected function createContinuationServer(array $exclusivePageflows, GarbageCollector $garbageCollector = null)
    {
        $pageflowRepository = new PageflowRepository(new PageflowRegistries(array(new PageflowRegistry($this->cacheDirectory, '.yaml'))), $this->cacheDirectory, true);
        $pageflowRepository->add('Counter');
        $pageflowRepository->add('SecondCounter');
        $pageflowRepository->add('CheckLastEvent');

        return new ContinuationServer(
            new PageflowInstanceRepository(),
            $pageflowRepository,
            $exclusivePageflows,
            $garbageCollector
        );
    }
}
