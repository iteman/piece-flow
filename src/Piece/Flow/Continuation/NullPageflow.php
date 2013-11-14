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

namespace Piece\Flow\Continuation;

use Piece\Flow\Pageflow\ActionInvokerInterface;
use Piece\Flow\Pageflow\PageflowInterface;

/**
 * @since Class available since Release 2.0.0
 */
class NullPageflow implements PageflowInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getID()
    {
        return $this->id;
    }

    public function getAttributes()
    {
        return null;
    }

    public function getCurrentState()
    {
        return null;
    }

    public function getPreviousState()
    {
        return null;
    }

    public function getCurrentView()
    {
        return null;
    }

    public function isInFinalState()
    {
        return false;
    }

    public function setActionInvoker(ActionInvokerInterface $actionInvoker)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getActionInvoker()
    {
        return null;
    }

    public function setPayload($payload)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getLastTransitionEvent()
    {
        return null;
    }
}
