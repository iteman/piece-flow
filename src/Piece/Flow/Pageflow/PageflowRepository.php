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

namespace Piece\Flow\Pageflow;

/**
 * @since Class available since Release 2.0.0
 */
class PageflowRepository
{
    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $clearCacheOnDestruction = false;

    /**
     * @var \Piece\Flow\Pageflow\PageflowFactory
     */
    protected $pageflowFactory;

    /**
     * @var \Piece\Flow\Pageflow\PageflowRegistries
     */
    protected $pageflowRegistries;

    /**
     * @var \Piece\Flow\Pageflow\PageflowCache[]
     */
    protected $pageflowCaches = array();

    /**
     * @param \Piece\Flow\Pageflow\PageflowRegistries $pageflowRegistries
     * @param string                                  $cacheDir
     * @param boolean                                 $clearCacheOnDestruction
     */
    public function __construct(PageflowRegistries $pageflowRegistries, $cacheDir, $clearCacheOnDestruction = false)
    {
        $this->pageflowRegistries = $pageflowRegistries;
        $this->pageflowFactory = new PageflowFactory($this->pageflowRegistries);
        $this->cacheDir = $cacheDir;
        $this->clearCacheOnDestruction = $clearCacheOnDestruction;
    }

    /**
     * @param  string                                     $id
     * @throws \Piece\Flow\Pageflow\FileNotFoundException
     */
    public function add($id)
    {
        if (array_key_exists($id, $this->pageflowCaches)) return;

        $definitionFile = $this->pageflowRegistries->getFileName($id);
        if (is_null($definitionFile)) {
            throw new FileNotFoundException(sprintf('The page flow definition file for the page flow ID "%s" is not found.', $id));
        }

        $pageflowCache = new PageflowCache($definitionFile, $this->cacheDir, $this->clearCacheOnDestruction);
        if (!$pageflowCache->isFresh()) {
            $pageflowCache->write($this->pageflowFactory->create($id));
        }

        $this->pageflowCaches[$id] = $pageflowCache;
    }

    /**
     * @param  string                                 $id
     * @return \Piece\Flow\Pageflow\PageflowInterface
     */
    public function findByID($id)
    {
        if (array_key_exists($id, $this->pageflowCaches)) {
            return $this->pageflowCaches[$id]->read();
        } else {
            return null;
        }
    }
}
