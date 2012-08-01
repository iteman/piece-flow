<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.0.0
 */

namespace Piece\Flow\Continuation;

use Piece\Flow\PageFlow\ActionInvoker;
use Piece\Flow\PageFlow\IPageFlow;
use Piece\Flow\PageFlow\PageFlow;

/**
 * @package    Piece_Flow
 * @copyright  2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
class PageFlowInstance implements IPageFlow
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var \Piece\Flow\PageFlow\PageFlow
     */
    protected $pageFlow;

    /**
     * @var boolean
     */
    protected $active = false;

    /**
     * @param string $id
     * @param \Piece\Flow\PageFlow\PageFlow $pageFlow
     */
    public function __construct($id, PageFlow $pageFlow)
    {
        $this->id = $id;
        $this->pageFlow = $pageFlow;
    }

    /**
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPageFlowID()
    {
        return $this->pageFlow->getID();
    }

    public function activate()
    {
        $this->active = true;
    }

    public function inactivate()
    {
        $this->active = false;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    public function removePageFlow()
    {
        $this->pageFlow = new NullPageFlow($this->pageFlow->getID());
    }

    public function setAttribute($name, $value)
    {
        $this->pageFlow->setAttribute($name, $value);
    }

    public function getAttribute($name)
    {
        return $this->pageFlow->getAttribute($name);
    }

    public function hasAttribute($name)
    {
        return $this->pageFlow->hasAttribute($name);
    }

    public function checkLastEvent()
    {
        return $this->pageFlow->checkLastEvent();
    }

    public function getCurrentStateName()
    {
        return $this->pageFlow->getCurrentStateName();
    }

    public function getView()
    {
        return $this->pageFlow->getView();
    }

    public function isFinalState()
    {
        return $this->pageFlow->isFinalState();
    }

    public function setActionInvoker(ActionInvoker $actionInvoker)
    {
        $this->pageFlow->setActionInvoker($actionInvoker);
    }

    public function setPayload($payload)
    {
        $this->pageFlow->setPayload($payload);
    }

    public function triggerEvent($eventName, $transitionToHistoryMarker = false)
    {
        return $this->pageFlow->triggerEvent($eventName, $transitionToHistoryMarker);
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