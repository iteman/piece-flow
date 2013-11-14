<?php
/*
 * Copyright (c) 2013 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\Pageflow\State;

use Stagehand\FSM\State\State;

/**
 * @since Class available since Release 2.0.0
 */
class ViewState extends State implements ViewStateInterface
{
    /**
     * @var string
     */
    protected $view;

    /**
     * @param string $view
     */
    public function setView($view)
    {
        $this->view = $view;
    }

    /**
     * {@inheritDoc}
     */
    public function getView()
    {
        return $this->view;
    }
}
