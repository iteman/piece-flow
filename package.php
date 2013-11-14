<?php
/*
 * Copyright (c) 2006-2008 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

require_once 'PEAR/PackageFileManager2.php';
require_once 'PEAR.php';

PEAR::staticPushErrorHandling(PEAR_ERROR_CALLBACK, create_function('$error', 'var_dump($error); exit();'));

$releaseVersion = '1.16.0';
$releaseStability = 'stable';
$apiVersion = '1.7.0';
$apiStability = 'stable';
$notes = 'A new release of Piece_Flow is now available.

What\'s New in Piece_Flow 1.16.0

 * Enhanced interfaces: getActiveFlowExecutionTicket() has been added to the Piece_Flow_Continuation_Service class. It can be used to get the flow execution ticket for the active flow execution. And also isViewState() has been added to the Piece_Flow class. It can be used to get whether the current state of a flow execution is a view state or not.
 * Improved error handling: The behavior of internal error handling has been changed so as to handle only own and "exception" level errors.
 * A defect fix: A defect that the outer frame of an already removed flow execution to be created by garbage collection has been fixed.

See the following release notes for details.

Enhancements
============

- Added getActiveFlowExecutionTicket() to get the flow execution ticket for the active flow execution. (Piece_Flow_Continuation_FlowExecution, Piece_Flow_Continuation_Service)
- Added isViewState() to get whether the current state of a flow execution is a view state or not. (Piece_Flow)
- Changed the behavior of internal error handling so as to handle only own and "exception" level errors.
- Replaced all uses of PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN with PIECE_FLOW_ERROR_FLOW_ID_NOT_GIVEN.
- Changed the behavior of internal error handling that an exception from Stagehand_FSM is always wrapped with Piece_Flow_Error::push().

Defect Fixes
============

- Fixed a defect that the outer frame of an already removed flow execution to be created by garbage collection. (Piece_Flow_Continuation_FlowExecution)';

$package = new PEAR_PackageFileManager2();
$package->setOptions(array('filelistgenerator' => 'file',
                           'changelogoldtonew' => false,
                           'simpleoutput'      => true,
                           'baseinstalldir'    => '/',
                           'packagefile'       => 'package.xml',
                           'packagedirectory'  => '.',
                           'ignore'            => array('package.php'))
                     );

$package->setPackage('Piece_Flow');
$package->setPackageType('php');
$package->setSummary('A web flow engine and continuation server');
$package->setDescription('Piece_Flow is a web flow engine and continuation server.

Piece_Flow provides a stateful programming model for developers, and high security for applications.');
$package->setChannel('pear.piece-framework.com');
$package->setLicense('New BSD License', 'http://www.opensource.org/licenses/bsd-license.php');
$package->setAPIVersion($apiVersion);
$package->setAPIStability($apiStability);
$package->setReleaseVersion($releaseVersion);
$package->setReleaseStability($releaseStability);
$package->setNotes($notes);
$package->setPhpDep('4.3.0');
$package->setPearinstallerDep('1.4.3');
$package->addPackageDepWithChannel('required', 'Stagehand_FSM', 'pear.piece-framework.com', '1.10.0');
$package->addPackageDepWithChannel('required', 'Cache_Lite', 'pear.php.net', '1.7.0');
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.4.3');
$package->addMaintainer('lead', 'iteman', 'KUBO Atsuhiro', 'kubo@iteman.jp');
$package->addGlobalReplacement('package-info', '@package_version@', 'version');
$package->generateContents();

if (array_key_exists(1, $_SERVER['argv']) && $_SERVER['argv'][1] == 'make') {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}

exit();
