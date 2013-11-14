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

use Stagehand\FSM\Event\TransitionEventInterface;
use Stagehand\FSM\StateMachine\StateMachine;
use Stagehand\FSM\StateMachine\StateMachineEvent;
use Stagehand\FSM\StateMachine\StateMachineEvents;
use Stagehand\FSM\State\StateInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

use Piece\Flow\Pageflow\State\ViewStateInterface;

/**
 * A web flow engine for handling page flows of web applications.
 *
 * Piece_Flow provides a web flow engine based on Finite State Machine (FSM).
 * Piece_Flow can handle two different states. The view state is a state which
 * is associated with a view string. The action state is a simple state, which
 * has no association with all views. If the engine once started,
 * the application will be put under control of it.
 *
 * @link  http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link  http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link  http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link  http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
 * @since Class available since Release 0.1.0
 */
class Pageflow extends StateMachine implements PageflowInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    protected $attributes;

    /**
     * @var \Piece\Flow\Pageflow\ActionInvokerInterface
     * @since Property available since Release 2.0.0
     */
    protected $actionInvoker;

    /**
     * @var \Stagehand\FSM\Event\TransitionEventInterface
     * @since Property available since Release 2.0.0
     */
    protected $lastTransitionEvent;

    /**
     * @param string $id
     * @since Method available since Release 2.0.0
     */
    public function __construct($id)
    {
        parent::__construct($id);

        $this->attributes = new ParameterBag();
    }

    /**
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), array('attributes'));
    }

    /**
     * {@inheritDoc}
     *
     * @since Method available since Release 2.0.0
     */
    public function setActionInvoker(ActionInvokerInterface $actionInvoker)
    {
        $this->actionInvoker = $actionInvoker;
    }

    /**
     * {@inheritDoc}
     *
     * @since Method available since Release 2.0.0
     */
    public function getActionInvoker()
    {
        return $this->actionInvoker;
    }

    /**
     * {@inheritDoc}
     *
     * @since Method available since Release 2.0.0
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher = null)
    {
        $self = $this;
        $eventDispatcher->addListener(
            StateMachineEvents::EVENT_PROCESS,
            function (StateMachineEvent $event) use ($self) {
                if ($event->getStateMachine() === $self) {
                    if ($event->getEvent() instanceof TransitionEventInterface) {
                        $self->lastTransitionEvent = $event->getEvent();
                    } else {
                        $self->lastTransitionEvent = null;
                    }
                }
            }
        );

        parent::setEventDispatcher($eventDispatcher);
    }

    public function getCurrentView()
    {
        if (is_null($this->getCurrentState())) return null;

        $state = $this->isInFinalState() ? $this->getPreviousState() : $this->getCurrentState();
        if ($state instanceof ViewStateInterface) {
            return $state->getView();
        } else {
            throw new IncompleteTransitionException(sprintf('An invalid transition detected. The state [ %s ] does not have a view. Maybe the state [ %s ] is an action state. Check the definition for [ %s ].', $state->getStateID(), $state->getStateID(), $this->getID()));
        }
    }

    public function getID()
    {
        return $this->getStateMachineID();
    }

    /**
     * Triggers an event.
     *
     * @param  string                                             $eventID
     * @return \Stagehand\FSM\State
     * @throws \Piece\Flow\Pageflow\PageflowNotActivatedException
     */
    public function triggerEvent($eventID)
    {
        if (is_null($this->getCurrentState())) {
            throw new PageflowNotActivatedException('The page flow must be activated to trigger any event.');
        }

        parent::triggerEvent($eventID);

        if ($this->getCurrentState()->isEndState()) {
            $this->triggerEvent(PageflowInterface::EVENT_END);
        }

        return $this->getCurrentState();
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function isInFinalState()
    {
        $currentState = $this->getCurrentState();
        if (is_null($currentState)) return false;
        return $currentState->getStateID() == StateInterface::STATE_FINAL;
    }

    /**
     * {@inheritDoc}
     *
     * @since Method available since Release 2.0.0
     */
    public function getLastTransitionEvent()
    {
        return $this->lastTransitionEvent;
    }
}
