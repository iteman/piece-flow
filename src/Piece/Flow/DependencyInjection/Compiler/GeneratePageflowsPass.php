<?php
/*
 * Copyright (c) 2014 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

use Piece\Flow\Continuation\PageflowNotFoundException;

class GeneratePageflowsPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     *
     * @throws \Piece\Flow\Continuation\PageflowNotFoundException
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getParameter('piece_flow.base_dirs') as $baseDir) {
            $finder = Finder::create()
                ->files()
                ->name('*' . $container->getParameter('piece_flow.file_extension'))
                ->in($baseDir)
                ->depth('== 0');
            foreach ($finder as $file) {
                $container->get('piece_flow.pageflow_repository')->add($file->getBasename($container->getParameter('piece_flow.file_extension')));
            }
        }

        foreach ($container->getParameter('piece_flow.exclusive_pageflows') as $exclusivePageflow) {
            if (!$container->get('piece_flow.pageflow_repository')->findByID($exclusivePageflow)) {
                throw new PageflowNotFoundException(sprintf('The page flow for ID [ %s ] is not found in the repository.', $exclusivePageflow));
            }
        }
    }
}
