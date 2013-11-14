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

namespace Piece\Flow\Continuation;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Piece\Flow\Continuation\GarbageCollection\GarbageCollector;
use Piece\Flow\Pageflow\ActionInvokerInterface;

/**
 * The continuation server.
 *
 * @since Class available since Release 1.14.0
 */
class ContinuationServer
{
    /**
     * @var \Piece\Flow\Continuation\GarbageCollection\GarbageCollector
     */
    protected $garbageCollector;

    /**
     * @var \Piece\Flow\Continuation\PageflowInstanceRepository
     */
    protected $pageflowInstanceRepository;

    /**
     * @var \Piece\Flow\Pageflow\ActionInvokerInterface
     * @since Property available since Release 2.0.0
     */
    protected $actionInvoker;

    /**
     * @var \Piece\Flow\Continuation\ContinuationContextProvider
     * @since Property available since Release 2.0.0
     */
    protected $continuationContextProvider;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     * @since Property available since Release 2.0.0
     */
    protected $eventDispatcher;

    /**
     * @var \Piece\Flow\Continuation\PageflowInstance
     * @since Property available since Release 2.0.0
     */
    protected $pageflowInstance;

    private static $activeInstances = array();
    private static $shutdownRegistered = false;

    /**
     * @param \Piece\Flow\Continuation\PageflowInstanceRepository         $pageflowInstanceRepository
     * @param \Piece\Flow\Continuation\GarbageCollection\GarbageCollector $garbageCollector
     */
    public function __construct(PageflowInstanceRepository $pageflowInstanceRepository, GarbageCollector $garbageCollector = null)
    {
        $this->pageflowInstanceRepository = $pageflowInstanceRepository;
        $this->garbageCollector = $garbageCollector;
    }

    /**
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function __sleep()
    {
        return array(
            'garbageCollector',
            'pageflowInstanceRepository',
        );
    }

    /**
     * Activates a page flow instance.
     *
     * @param mixed $payload
     */
    public function activate($payload)
    {
        if (!is_null($this->garbageCollector)) {
            $this->garbageCollector->mark();
        }

        $this->pageflowInstance = $this->createPageflowInstance($payload);
        $this->pageflowInstance->activate($this->continuationContextProvider->getEventID());

        if (!is_null($this->garbageCollector) && !$this->pageflowInstanceRepository->checkPageflowIsExclusive($this->pageflowInstance)) {
            $this->garbageCollector->update($this->pageflowInstance->getID());
        }

        self::$activeInstances[] = $this;
        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function(array(__CLASS__, 'shutdown'));
        }
    }

    /**
     * Shutdown the continuation server for next events.
     */
    public static function shutdown()
    {
        for ($i = 0, $count = count(self::$activeInstances); $i < $count; ++$i) {
            $instance = self::$activeInstances[$i];
            if (!($instance instanceof self)) {
                unset(self::$activeInstances[$i]);
                continue;
            }
            $instance->clear();
        }
    }

    /**
     * Clears some properties for the next use.
     */
    public function clear()
    {
        if (!is_null($this->pageflowInstance)) {
            if ($this->pageflowInstance->isInFinalState()) {
                $this->pageflowInstanceRepository->remove($this->pageflowInstance);
            }
        }

        if (!is_null($this->garbageCollector)) {
            $pageflowInstanceRepository = $this->pageflowInstanceRepository;
            $this->garbageCollector->sweep(function ($pageflowInstanceID) use ($pageflowInstanceRepository) {
                $pageflowInstance = $pageflowInstanceRepository->findByID($pageflowInstanceID);
                if (!is_null($pageflowInstance)) {
                    $pageflowInstance->removePageflow();
                }
            });
        }
    }

    /**
     * Sets the action invoker.
     *
     * @param \Piece\Flow\Pageflow\ActionInvokerInterface $actionInvoker
     * @since Method available since Release 2.0.0
     */
    public function setActionInvoker(ActionInvokerInterface $actionInvoker)
    {
        $this->actionInvoker = $actionInvoker;
    }

    /**
     * @param \Piece\Flow\Continuation\ContinuationContextProvider $continuationContextProvider
     * @since Method available since Release 2.0.0
     */
    public function setContinuationContextProvider(ContinuationContextProvider $continuationContextProvider)
    {
        $this->continuationContextProvider = $continuationContextProvider;
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @since Method available since Release 2.0.0
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return \Piece\Flow\Continuation\PageflowInstance
     * @since Method available since Release 2.0.0
     */
    public function getPageflowInstance()
    {
        return $this->pageflowInstance;
    }

    /**
     * @return \Piece\Flow\Continuation\PageflowInstanceRepository
     * @since Method available since Release 2.0.0
     */
    public function getPageflowInstanceRepository()
    {
        return $this->pageflowInstanceRepository;
    }

    /**
     * Generates the ID for a page flow instance.
     *
     * @throws \Piece\Flow\Continuation\SecurityException
     */
    protected function generatePageflowInstanceID()
    {
        $bytes = openssl_random_pseudo_bytes(24, $cryptographicallyStrong);
        if ($bytes === false) {
            throw new SecurityException('Generating a pseudo-random string of bytes is failed.');
        }
        if ($cryptographicallyStrong === false) {
            throw new SecurityException('Any cryptographically strong algorithm is not used to generate the pseudo-random string of bytes.');
        }

        $pageflowInstanceID = base64_encode($bytes);
        if ($pageflowInstanceID === false) {
            throw new SecurityException('Encoding the pseudo-random string of bytes with Base64 is failed.');
        }

        return $pageflowInstanceID;
    }

    /**
     * Creates a page flow instance.
     *
     * @param  mixed                                                     $payload
     * @return \Piece\Flow\Continuation\PageflowInstance
     * @throws \Piece\Flow\Continuation\PageflowInstanceExpiredException
     * @throws \Piece\Flow\Continuation\PageflowIDRequiredException
     * @throws \Piece\Flow\Continuation\PageflowNotFoundException
     * @throws \Piece\Flow\Continuation\UnexpectedPageflowIDException
     */
    protected function createPageflowInstance($payload)
    {
        $pageflowID = $this->continuationContextProvider->getPageflowID();
        if (empty($pageflowID)) {
            throw new PageflowIDRequiredException('A page flow ID must be specified.');
        }

        $pageflowInstance = $this->pageflowInstanceRepository->findByID($this->continuationContextProvider->getPageflowInstanceID());
        if (is_null($pageflowInstance)) {
            $pageflow = $this->pageflowInstanceRepository->getPageflowRepository()->findByID($pageflowID);
            if (is_null($pageflow)) {
                throw new PageflowNotFoundException(sprintf('The page flow for ID [ %s ] is not found in the repository.', $pageflowID));
            }

            while (true) {
                $pageflowInstanceID = $this->generatePageflowInstanceID();
                $pageflowInstance = $this->pageflowInstanceRepository->findByID($pageflowInstanceID);
                if (is_null($pageflowInstance)) {
                    $pageflowInstance = new PageflowInstance($pageflowInstanceID, $pageflow);
                    $this->pageflowInstanceRepository->add($pageflowInstance);
                    break;
                }
            }
        } else {
            if ($pageflowID != $pageflowInstance->getPageflowID()) {
                throw new UnexpectedPageflowIDException(sprintf('The specified page flow ID [ %s ] is different from the expected page flow ID [ %s ].', $pageflowID, $pageflowInstance->getPageflowID()));
            }

            if (!is_null($this->garbageCollector)) {
                if ($this->garbageCollector->shouldSweep($pageflowInstance->getID())) {
                    $this->pageflowInstanceRepository->remove($pageflowInstance);
                    throw new PageflowInstanceExpiredException('The page flow instance has been expired.');
                }
            }
        }

        $pageflowInstance->setActionInvoker($this->actionInvoker);
        $pageflowInstance->setPayload($payload);
        $pageflowInstance->setEventDispatcher($this->eventDispatcher);

        return $pageflowInstance;
    }
}
