<?php
/*
 * Copyright (c) 2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\Continuation;

use Piece\Flow\Pageflow\PageflowRepository;

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
     * @var \Piece\Flow\Pageflow\PageflowRepository
     * @since Property available since Release 2.0.0
     */
    protected $pageflowRepository;

    /**
     * @var array
     * @since Property available since Release 2.0.0
     */
    protected $exclusivePageflows = array();

    /**
     * @param \Piece\Flow\Pageflow\PageflowRepository $pageflowRepository
     * @since Method available since Release 2.0.0
     */
    public function __construct(PageflowRepository $pageflowRepository)
    {
        $this->pageflowRepository = $pageflowRepository;
    }

    /**
     * Adds a page flow into the page flow repository.
     *
     * @param string  $pageflowID
     * @param boolean $exclusive
     */
    public function addPageflow($pageflowID, $exclusive)
    {
        $this->pageflowRepository->add($pageflowID);
        if ($exclusive) {
            $this->exclusivePageflows[] = $pageflowID;
        }
    }

    /**
     * Removes a page flow instance.
     *
     * @param \Piece\Flow\Continuation\PageflowInstance $pageflowInstance
     */
    public function remove(PageflowInstance $pageflowInstance)
    {
        if ($this->checkPageflowHasExclusiveInstance($pageflowInstance->getPageflowID())) {
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
        $exclusivePageflowInstance = $this->findByPageflowID($pageflowInstance->getPageflowID());
        if (!is_null($exclusivePageflowInstance)) {
            $this->remove($exclusivePageflowInstance);
        }

        $this->pageflowInstances[ $pageflowInstance->getID() ] = $pageflowInstance;
        if ($this->checkPageflowIsExclusive($pageflowInstance)) {
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
