<?php
/*
 * Copyright (c) 2013 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\Pageflow;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @since Class available since Release 2.0.0
 */
class PageflowRegistries extends ArrayCollection
{
    /**
     * @param  string $pageflowID
     * @return string
     */
    public function getFileName($pageflowID)
    {
        foreach ($this as $pageflowRegistry) {
            $definitionFile = $pageflowRegistry->getFileName($pageflowID);
            if (file_exists($definitionFile)) {
                return $definitionFile;
            }
        }

        return null;
    }
}
