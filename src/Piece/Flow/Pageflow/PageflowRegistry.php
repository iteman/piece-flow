<?php
/*
 * Copyright (c) 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
class PageflowRegistry
{
    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @var string
     */
    protected $extension;

    /**
     * @param string $baseDir
     * @param string $extension
     */
    public function __construct($baseDir, $extension)
    {
        $this->baseDir = $baseDir;
        $this->extension = $extension;
    }

    /**
     * @param  string $id
     * @return string
     */
    public function getFileName($id)
    {
        return $this->baseDir . '/' . $id . $this->extension;
    }
}
