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

namespace Piece\Flow\PageFlow;

/**
 * @package    Piece_Flow
 * @copyright  2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
class PageFlowRepository
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
     * @var \Piece\Flow\PageFlow\PageFlowFactory
     */
    protected $pageFlowFactory;

    /**
     * @var \Piece\Flow\PageFlow\PageFlowRegistries
     */
    protected $pageFlowRegistries;

    /**
     * @var \Piece\Flow\PageFlow\PageFlowInterface[]
     */
    protected $pageFlows = array();

    /**
     * @param \Piece\Flow\PageFlow\PageFlowRegistries $pageFlowRegistries
     * @param string                                  $cacheDir
     * @param boolean                                 $clearCacheOnDestruction
     */
    public function __construct(PageFlowRegistries $pageFlowRegistries, $cacheDir, $clearCacheOnDestruction = false)
    {
        $this->pageFlowRegistries = $pageFlowRegistries;
        $this->pageFlowFactory = new PageFlowFactory($this->pageFlowRegistries);
        $this->cacheDir = $cacheDir;
        $this->clearCacheOnDestruction = $clearCacheOnDestruction;
    }

    /**
     * @param  string                                     $id
     * @throws \Piece\Flow\PageFlow\FileNotFoundException
     */
    public function add($id)
    {
        if (array_key_exists($id, $this->pageFlows)) return;

        $definitionFile = $this->pageFlowRegistries->getFileName($id);
        if (is_null($definitionFile)) {
            throw new FileNotFoundException(sprintf('The page flow definition file for the page flow ID "%s" is not found.', $id));
        }

        $pageFlowCache = new PageFlowCache($definitionFile, $this->cacheDir, $this->clearCacheOnDestruction);
        if (!$pageFlowCache->isFresh()) {
            $pageFlowCache->write($this->pageFlowFactory->create($id));
        }

        $this->pageFlows[$id] = $pageFlowCache;
    }

    /**
     * @param  string                                 $id
     * @return \Piece\Flow\PageFlow\PageFlowInterface
     */
    public function findByID($id)
    {
        if (array_key_exists($id, $this->pageFlows)) {
            return $this->pageFlows[$id]->read();
        } else {
            return null;
        }
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
