<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006, KUBO Atsuhiro <iteman2002@yahoo.co.jp>
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
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://iteman.typepad.jp/piece/
 * @see        Stagehand_TestRunner_PHPUnitTestRunner::runAll()
 * @since      File available since Release 0.1.0
 */

require_once 'PEAR/PackageFileManager2.php';

$version = '0.5.0';
$notes = '';

$package = new PEAR_PackageFileManager2();
$result = $package->setOptions(array('filelistgenerator' => 'svn',
                                     'changelogoldtonew' => false,
                                     'simpleoutput'      => true,
                                     'baseinstalldir'    => '/',
                                     'packagefile'       => 'package2.xml',
                                     'packagedirectory'  => '.')
                               );

if (PEAR::isError($result)) {
    print $result->getMessage();
    exit();
}

$package->setPackage('Piece_Flow');
$package->setPackageType('php');
$package->setSummary('A web flow engine which to handle the page flow of a web application.');
$package->setDescription('Piece_Flow provides a web flow engine based on Finite State Machine (FSM).
Piece_Flow can handle two different states. The view state is a state associated with a view string. The action state is a simple state, which has no association with all views.
If the engine once started, the application will be put under control of it.');
$package->setChannel('pear.hatotech.org');
$package->setLicense('BSD License (revised)',
                     'http://www.opensource.org/licenses/bsd-license.php'
                     );
$package->setAPIVersion('0.4.0');
$package->setAPIStability('beta');
$package->setReleaseVersion($version);
$package->setReleaseStability('beta');
$package->setNotes($notes);
$package->setPhpDep('4.3.0');
$package->setPearinstallerDep('1.4.3');
$package->addMaintainer('lead', 'kubo', 'KUBO Atsuhiro', 'iteman2002@yahoo.co.jp');
$package->addMaintainer('developer', 'miya', 'MIYAI Fumihiko', 'fumichz@yahoo.co.jp');
$package->addIgnore(array('package.php', 'package.xml', 'package2.xml'));
$package->addGlobalReplacement('package-info', '@package_version@', 'version');
$package->generateContents();
$package1 = &$package->exportCompatiblePackageFile1();

if (array_key_exists(1, $_SERVER['argv'])
    && $_SERVER['argv'][1] == 'make'
    ) {
    $result = $package->writePackageFile();
    $result = $package1->writePackageFile();
} else {
    $result = $package->debugPackageFile();
    $result = $package1->debugPackageFile();
}

if (PEAR::isError($result)) {
    print $result->getMessage();
}

exit();

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
?>
