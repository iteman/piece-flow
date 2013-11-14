<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.0.0
 */

namespace Piece\Flow\Continuation;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Piece\Flow\Pageflow\ActionInvokerInterface;
use Piece\Flow\Pageflow\PageflowInterface;

/**
 * @package    Piece_Flow
 * @copyright  2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
class PageflowInstance implements PageflowInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var \Piece\Flow\Pageflow\PageflowInterface
     */
    protected $pageflow;

    /**
     * @param string                                 $id
     * @param \Piece\Flow\Pageflow\PageflowInterface $pageflow
     */
    public function __construct($id, PageflowInterface $pageflow)
    {
        $this->id = $id;
        $this->pageflow = $pageflow;
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
    public function getPageflowID()
    {
        return $this->pageflow->getID();
    }

    public function removePageflow()
    {
        $this->pageflow = new NullPageflow($this->pageflow->getID());
    }

    /**
     * Activate this page flow instance with the specified event.
     *
     * @param string $eventID
     */
    public function activate($eventID)
    {
        if (is_null($this->pageflow->getCurrentState())) {
            $this->pageflow->start();
        } else {
            $this->pageflow->triggerEvent($eventID);
        }
    }

    public function getAttributes()
    {
        return $this->pageflow->getAttributes();
    }

    public function getCurrentState()
    {
        return $this->pageflow->getCurrentState();
    }

    public function getPreviousState()
    {
        return $this->pageflow->getPreviousState();
    }

    public function getCurrentView()
    {
        return $this->pageflow->getCurrentView();
    }

    public function isInFinalState()
    {
        return $this->pageflow->isInFinalState();
    }

    public function setActionInvoker(ActionInvokerInterface $actionInvoker)
    {
        $this->pageflow->setActionInvoker($actionInvoker);
    }

    /**
     * {@inheritDoc}
     */
    public function getActionInvoker()
    {
        return $this->pageflow->getActionInvoker();
    }

    public function setPayload($payload)
    {
        $this->pageflow->setPayload($payload);
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @since Method available since Release 2.0.0
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->pageflow->setEventDispatcher($eventDispatcher);
    }

    /**
     * {@inheritDoc}
     *
     * @since Method available since Release 2.0.0
     */
    public function getLastTransitionEvent()
    {
        return $this->pageflow->getLastTransitionEvent();
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
