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
class PageflowFactory
{
    /**
     * @var \Piece\Flow\Pageflow\PageflowRegistries
     */
    protected $pageflowRegistries;

    /**
     * @param \Piece\Flow\Pageflow\PageflowRegistries $pageflowRegistries
     */
    public function __construct(PageflowRegistries $pageflowRegistries)
    {
        $this->pageflowRegistries = $pageflowRegistries;
    }

    /**
     * @param  string                                 $id
     * @return \Piece\Flow\Pageflow\PageflowInterface
     */
    public function create($id)
    {
        $pageflowGenerator = new PageflowGenerator(new Pageflow($id), $this->pageflowRegistries);

        return $pageflowGenerator->generate();
    }
}
