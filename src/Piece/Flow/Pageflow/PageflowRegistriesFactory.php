<?php
/*
 * Copyright (c) 2014 KUBO Atsuhiro <kubo@iteman.jp>,
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
class PageflowRegistriesFactory
{
    /**
     * @var array
     */
    protected $baseDirs;

    /**
     * @var string
     */
    protected $extension;

    /**
     * @param string $baseDirs
     * @param string $extension
     */
    public function __construct(array $baseDirs, $extension)
    {
        $this->baseDirs = $baseDirs;
        $this->extension = $extension;
    }

    /**
     * @return \Piece\Flow\Pageflow\PageflowRegistries
     */
    public function create()
    {
        $pageflowRegistries = array();
        foreach ($this->baseDirs as $baseDir) {
            $pageflowRegistries[] = new PageflowRegistry($baseDir, $this->extension);
        }

        return new PageflowRegistries($pageflowRegistries);
    }
}
