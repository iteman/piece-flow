<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow\Continuation;

use Piece\Flow\Pageflow\PageflowRepository;

/**
 * The repository for the page flow instances.
 *
 * @package    Piece_Flow
 * @copyright  2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
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
