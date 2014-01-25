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

/**
 * @since Class available since Release 2.0.0
 */
interface ContinuationContextInterface
{
    /**
     * @return string
     */
    public function getEventID();

    /**
     * @return string
     */
    public function getPageflowID();

    /**
     * @return string
     */
    public function getPageflowInstanceID();
}