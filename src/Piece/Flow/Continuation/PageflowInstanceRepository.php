<?php
/*
 * Copyright (c) 2007-2008, 2012-2014 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * The repository for the page flow instances.
 *
 * @since Class available since Release 1.14.0
 */
class PageflowInstanceRepository
{
    protected $pageflowInstances = array();
    protected $exclusivePageflowInstances = array();

    /**
     * Removes a page flow instance.
     *
     * @param \Piece\Flow\Continuation\PageflowInstance $pageflowInstance
     */
    public function remove(PageflowInstance $pageflowInstance)
    {
        if ($pageflowInstance->isExclusive()) {
            unset($this->exclusivePageflowInstances[ $pageflowInstance->getPageflowID() ]);
        }

        unset($this->pageflowInstances[ $pageflowInstance->getID() ]);
    }

    /**
     * Adds a page flow instance to this repository.
     *
     * @param \Piece\Flow\Continuation\PageflowInstance $pageflowInstance
     */
    public function add(PageflowInstance $pageflowInstance)
    {
        $this->pageflowInstances[ $pageflowInstance->getID() ] = $pageflowInstance;

        if ($pageflowInstance->isExclusive()) {
            $this->exclusivePageflowInstances[ $pageflowInstance->getPageflowID() ] = $pageflowInstance->getID();
        }
    }

    /**
     * Returns whether or not the specified page flow has one or more exclusive
     * instances.
     *
     * @param  string  $pageflowID
     * @return boolean
     */
    protected function checkPageflowHasExclusiveInstance($pageflowID)
    {
        return array_key_exists($pageflowID, $this->exclusivePageflowInstances);
    }

    /**
     * @param  string                                    $id
     * @return \Piece\Flow\Continuation\PageflowInstance
     * @since Method available since Release 2.0.0
     */
    public function findByID($id)
    {
        if (array_key_exists($id, $this->pageflowInstances)) {
            return $this->pageflowInstances[$id];
        } else {
            return null;
        }
    }

    /**
     * @param  string                                    $pageflowID
     * @return \Piece\Flow\Continuation\PageflowInstance
     * @since Method available since Release 2.0.0
     */
    public function findByPageflowID($pageflowID)
    {
        if ($this->checkPageflowHasExclusiveInstance($pageflowID)) {
            return $this->findByID($this->exclusivePageflowInstances[$pageflowID]);
        } else {
            return null;
        }
    }

    /**
     * @return \Piece\Flow\Pageflow\PageflowRepository
     * @since Method available since Release 2.0.0
     */
    public function getPageflowRepository()
    {
        return $this->pageflowRepository;
    }

    /**
     * Checks whether or not the page flow of the specified page flow instance
     * is exclusive.
     *
     * @param  \Piece\Flow\Continuation\PageflowInstance $pageflowInstance
     * @return boolean
     * @since Method available since Release 2.0.0
     */
    public function checkPageflowIsExclusive(PageflowInstance $pageflowInstance)
    {
        return in_array($pageflowInstance->getPageflowID(), $this->exclusivePageflows);
    }
}
