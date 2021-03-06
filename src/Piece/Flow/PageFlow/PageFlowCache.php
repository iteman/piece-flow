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

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;

/**
 * @package    Piece_Flow
 * @copyright  2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
class PageFlowCache
{
    /**
     * @var string
     */
    protected $cacheFile;

    /**
     * @var string
     */
    protected $clearCacheOnDestruction;

    /**
     * @var \Symfony\Component\Config\ConfigCache
     */
    protected $configCache;

    /**
     * @var string
     */
    protected $definitionFile;

    /**
     * @param string  $definitionFile
     * @param string  $cacheDir
     * @param boolean $clearCacheOnDestruction
     */
    public function __construct($definitionFile, $cacheDir, $clearCacheOnDestruction)
    {
        $this->definitionFile = $definitionFile;
        $this->cacheFile = $cacheDir . '/' . sha1($this->definitionFile) . '.cache';
        $this->configCache = new ConfigCache($this->cacheFile, true);
        $this->clearCacheOnDestruction = $clearCacheOnDestruction;
    }

    public function __destruct()
    {
        if ($this->clearCacheOnDestruction && file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
            unlink($this->cacheFile . '.meta');
        }
    }

    /**
     * @return boolean
     */
    public function isFresh()
    {
        return $this->configCache->isFresh();
    }

    /**
     * @return \Piece\Flow\PageFlow\PageFlowInterface
     */
    public function read()
    {
        return unserialize(serialize(unserialize(require $this->cacheFile)));
    }

    /**
     * @param \Piece\Flow\PageFlow\PageFlowInterface $pageFlow
     */
    public function write(PageFlowInterface $pageFlow)
    {
        $pageFlowClass = new \ReflectionObject($pageFlow);
        $this->configCache->write($this->createContents(addslashes(serialize($pageFlow))), array(
            new FileResource($this->definitionFile),
            new FileResource($pageFlowClass->getFileName()),
        ));
    }

    /**
     * @param  string $data
     * @return string
     */
    protected function createContents($data)
    {
        return '<?php return "' . $data . '";' . PHP_EOL;
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
