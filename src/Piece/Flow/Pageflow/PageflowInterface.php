<?php
/*
 * Copyright (c) 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\Pageflow;

/**
 * @since Class available since Release 2.0.0
 */
interface PageflowInterface
{
    const EVENT_END = '__END__';

    /**
     * Gets the ID of this page flow.
     *
     * @return string
     */
    public function getID();

    /**
     * @return \Symfony\Component\HttpFoundation\ParameterBag
     */
    public function getAttributes();

    /**
     * Gets the current state.
     *
     * @return \Stagehand\FSM\State\StateInterface
     */
    public function getCurrentState();

    /**
     * Gets the previous state.
     *
     * @return \Stagehand\FSM\State\StateInterface
     */
    public function getPreviousState();

    /**
     * Gets the appropriate view string corresponding to the current state.
     *
     * @return string
     * @throws \Piece\Flow\Pageflow\IncompleteTransitionException
     */
    public function getCurrentView();

    /**
     * Checks whether the current state is the final state or not.
     *
     * @return boolean
     */
    public function isInFinalState();

    /**
     * @param \Piece\Flow\Pageflow\ActionInvokerInterface $actionInvoker
     */
    public function setActionInvoker(ActionInvokerInterface $actionInvoker);

    /**
     * @return \Piece\Flow\Pageflow\ActionInvokerInterface
     */
    public function getActionInvoker();

    /**
     * Sets a user defined payload.
     *
     * @param mixed $payload
     */
    public function setPayload($payload);

    /**
     * @return \Stagehand\FSM\Event\TransitionEventInterface
     */
    public function getLastTransitionEvent();
}
