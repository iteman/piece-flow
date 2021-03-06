<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.0.0
 */

namespace Piece\Flow\PageFlow;

/**
 * @package    Piece_Flow
 * @copyright  2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
interface PageFlowInterface
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
     * @throws \Piece\Flow\PageFlow\IncompleteTransitionException
     */
    public function getCurrentView();

    /**
     * Checks whether the current state is the final state or not.
     *
     * @return boolean
     */
    public function isInFinalState();

    /**
     * @param \Piece\Flow\PageFlow\ActionInvokerInterface $actionInvoker
     */
    public function setActionInvoker(ActionInvokerInterface $actionInvoker);

    /**
     * @return \Piece\Flow\PageFlow\ActionInvokerInterface
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
