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

error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'Phake.php';

\Phake::setClient(\Phake::CLIENT_PHPUNIT);
