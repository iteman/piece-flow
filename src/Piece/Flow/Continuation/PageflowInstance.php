<?php
/*
 * Copyright (c) 2012-2014 KUBO Atsuhiro <kubo@iteman.jp>,
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

use Piece\Flow\Pageflow\ActionInvokerInterface;
use Piece\Flow\Pageflow\PageflowInterface;

/**
 * @since Class available since Release 2.0.0
 */
class PageflowInstance implements PageflowInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var \Piece\Flow\Pageflow\PageflowInterface
     */
    protected $pageflow;

    /**
     * @var boolean
     * @since Property available since Release 2.0.0
     */
    protected $exclusive;

    /**
     * @param string                                 $id
     * @param \Piece\Flow\Pageflow\PageflowInterface $pageflow
     * @param boolean                                $exclusive
     */
    public function __construct($id, PageflowInterface $pageflow, $exclusive)
    {
        $this->id = $id;
        $this->pageflow = $pageflow;
        $this->exclusive = $exclusive;
    }

    /**
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPageflowID()
    {
        return $this->pageflow->getID();
    }

    public function removePageflow()
    {
        $this->pageflow = new NullPageflow($this->pageflow->getID());
    }

    /**
     * Activate this page flow instance with the specified event.
     *
     * @param string $eventID
     */
    public function activate($eventID)
    {
        if (is_null($this->pageflow->getCurrentState())) {
            $this->pageflow->start();
        } else {
            $this->pageflow->triggerEvent($eventID);
        }
    }

    public function getAttributes()
    {
        return $this->pageflow->getAttributes();
    }

    public function getCurrentState()
    {
        return $this->pageflow->getCurrentState();
    }

    public function getPreviousState()
    {
        return $this->pageflow->getPreviousState();
    }

    public function getCurrentView()
    {
        return $this->pageflow->getCurrentView();
    }

    public function isInFinalState()
    {
        return $this->pageflow->isInFinalState();
    }

    public function setActionInvoker(ActionInvokerInterface $actionInvoker)
    {
        $this->pageflow->setActionInvoker($actionInvoker);
    }

    /**
     * {@inheritDoc}
     */
    public function getActionInvoker()
    {
        return $this->pageflow->getActionInvoker();
    }

    public function setPayload($payload)
    {
        $this->pageflow->setPayload($payload);
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @since Method available since Release 2.0.0
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->pageflow->setEventDispatcher($eventDispatcher);
    }

    /**
     * {@inheritDoc}
     *
     * @since Method available since Release 2.0.0
     */
    public function getLastTransitionEvent()
    {
        return $this->pageflow->getLastTransitionEvent();
    }

    /**
     * @return boolean
     * @since Method available since Release 2.0.0
     */
    public function isExclusive()
    {
        return $this->exclusive;
    }
}
