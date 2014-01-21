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

use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;

/**
 * @since Class available since Release 2.0.0
 */
class PageflowCache
{
    /**
     * @var string
     */
    protected $cacheFile;

    /**
     * @var string
     */
    protected $debug;

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
     * @param boolean $debug
     */
    public function __construct($definitionFile, $cacheDir, $debug)
    {
        $this->definitionFile = $definitionFile;
        $this->cacheFile = $cacheDir . '/' . sha1($this->definitionFile) . '.cache';
        $this->configCache = new ConfigCache($this->cacheFile, $debug);
        $this->debug = $debug;
    }

    public function __destruct()
    {
        if ($this->debug && file_exists($this->cacheFile)) {
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
     * @return \Piece\Flow\Pageflow\PageflowInterface
     */
    public function read()
    {
        return unserialize(serialize(unserialize(require $this->cacheFile)));
    }

    /**
     * @param \Piece\Flow\Pageflow\PageflowInterface $pageflow
     */
    public function write(PageflowInterface $pageflow)
    {
        $pageflowClass = new \ReflectionObject($pageflow);
        $this->configCache->write($this->createContents(addslashes(serialize($pageflow))), array(
            new FileResource($this->definitionFile),
            new FileResource($pageflowClass->getFileName()),
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
