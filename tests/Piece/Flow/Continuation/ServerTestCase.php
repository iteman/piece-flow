<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 1.14.0
 */

require_once realpath(dirname(__FILE__) . '/../../../prepare.php');
require_once 'PHPUnit.php';
require_once 'Piece/Flow/Continuation/Server.php';
require_once 'Piece/Flow/Error.php';
require_once 'Cache/Lite/File.php';
require_once 'Piece/Flow/Action/Factory.php';

// {{{ Piece_Flow_Continuation_ServerTestCase

/**
 * TestCase for Piece_Flow_Continuation_Server
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class Piece_Flow_Continuation_ServerTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_flowExecutionTicket;
    var $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $GLOBALS['flowName'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = null;
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        Piece_Flow_Action_Factory::setActionDirectory($this->_cacheDirectory);
    }

    function tearDown()
    {
        Piece_Flow_Action_Factory::clearInstances();
        Piece_Flow_Action_Factory::setActionDirectory(null);
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $cache = &new Cache_Lite_File(array('cacheDir' => "{$this->_cacheDirectory}/",
                                            'masterFile' => '',
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        Piece_Flow_Error::clearErrors();
        Piece_Flow_Error::popCallback();
    }

    function getFlowExecutionTicket()
    {
        return $GLOBALS['flowExecutionTicket'];
    }

    function getFlowName()
    {
        return $GLOBALS['flowName'];
    }

    function getEventName()
    {
        return $GLOBALS['eventName'];
    }

    function testAddingFlowInSingleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('foo', '/path/to/foo.xml');

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));

        Piece_Flow_Error::popCallback();
    }

    function testFailureToAddFlowForSecondTimeInSingleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('foo', '/path/to/foo.xml');
        $server->addFlow('bar', '/path/to/bar.xml');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_EXISTS, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testSettingFlowInMultipleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('foo', '/path/to/foo.xml');
        $server->addFlow('bar', '/path/to/bar.xml');

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));

        Piece_Flow_Error::popCallback();
    }

    function testFirstTimeInvocationInSingleFlowMode()
    {
        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));

        $flowExecutionTicket = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(0, $service->getAttribute('counter'));
    }

    function testSecondTimeInvocationInSingleFlowMode()
    {
        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));

        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testMultipleInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $server->addFlow('SecondCounter', "{$this->_cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        /*
         * Starting a new 'Counter'.
         */
        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        /*
         * Starting a new 'SecondCounter'.
         */
        $GLOBALS['flowName'] = 'SecondCounter';
        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket2);
        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        /*
         * Continuing the first 'Counter'.
         */
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->shutdown();
        $flowExecutionTicket3 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket3);

        /*
         * Continuing the first 'SecondCounter'.
         */
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket2;
        $server->shutdown();
        $flowExecutionTicket4 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket2, $flowExecutionTicket4);

        /*
         * Starting a new 'SecondCounter'.
         */
        $GLOBALS['flowExecutionTicket'] = null;
        $secondCounter->counter = null;
        $GLOBALS['flowName'] = 'SecondCounter';
        $server->shutdown();
        $flowExecutionTicket5 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket2 != $flowExecutionTicket5);
    }

    function testSuccessOfContinuationByInvalidFlowNameInSingleFlowMode()
    {
        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowExecutionTicket'] = $server->invoke(new stdClass());
        $GLOBALS['flowName'] = 'InvalidFlowName';
        $server->shutdown();
        $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));
        $this->assertEquals(1, $service->getAttribute('counter'));
    }

    function testFailureOfContinuationByInvalidFlowNameInMultipleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $server->invoke(new stdClass());
        $GLOBALS['flowName'] = 'InvalidFlowName';
        $server->shutdown();
        $server->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testFailureToInvokeByNonExistingFlowConfiguration()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('NonExistingFile', "{$this->_cacheDirectory}/NonExistingFile.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowName'] = 'NonExistingFile';
        $server->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testInvocationInMultipleFlowModeAndFlowInExclusiveMode()
    {
        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $server->addFlow('SecondCounter', "{$this->_cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $GLOBALS['flowName'] = 'SecondCounter';
        $server->shutdown();
        $flowExecutionTicket3 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $GLOBALS['flowName'] = 'Counter';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
    }

    function testInvocationInSingleFlowModeAndFlowInExclusiveMode()
    {
        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testSettingAttribute()
    {
        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowExecutionTicket'] = $server->invoke(new stdClass());
        $service = &$server->createService();
        $service->setAttribute('foo', 'bar');
        $server->shutdown();
        $server->invoke(new stdClass());
        $service = &$server->createService();
        $service->setAttribute('bar', 'baz');
        $baz1 = &new stdClass();
        $service->setAttributeByRef('baz', $baz1);
        $server->shutdown();
        $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertTrue($service->hasAttribute('foo'));
        $this->assertEquals('bar', $service->getAttribute('foo'));
        $this->assertTrue($service->hasAttribute('bar'));
        $this->assertEquals('baz', $service->getAttribute('bar'));

        $baz1->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $baz1));
        $this->assertEquals('bar', $baz1->foo);

        $baz2 = &$service->getAttribute('baz');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($baz2)));

        $this->assertTrue(array_key_exists('foo', $baz2));
        $this->assertEquals('bar', $baz2->foo);
    }

    function testFailureToSetAttributeBeforeStartingContinuation()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $service = &$server->createService();
        $service->setAttribute('foo', 'bar');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testFailureToGetAttributeBeforeStartingContinuation()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $service = &$server->createService();
        $service->getAttribute('foo');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testStartingNewFlowExecutionAfterShuttingDownContinuationInNonExclusiveMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $GLOBALS['ShutdownCount'] = 0;

        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Shutdown', "{$this->_cacheDirectory}/Shutdown.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $GLOBALS['flowName'] = 'Shutdown';
        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $GLOBALS['eventName'] = 'go';
        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket.
         */
        $GLOBALS['flowName'] = null;
        $GLOBALS['eventName'] = 'go';
        $server->shutdown();
        $server->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN, $error['code']);

        unset($GLOBALS['ShutdownCount']);
        Piece_Flow_Error::popCallback();
    }

    function testStartingNewFlowExecutionAfterShuttingDownContinuationInExclusiveMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Shutdown', "{$this->_cacheDirectory}/Shutdown.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $GLOBALS['flowName'] = 'Shutdown';
        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $GLOBALS['eventName'] = 'go';
        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. And starting a new 'Shutdown'.
         */
        $server->shutdown();
        $flowExecutionTicket3 = $server->invoke(new stdClass());

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);

        unset($GLOBALS['ShutdownCount']);
    }

    function testStartingNewFlowExecutionAfterShuttingDownContinuationInSingleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $GLOBALS['ShutdownCount'] = 0;

        $server = &new Piece_Flow_Continuation_Server(true);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Shutdown', "{$this->_cacheDirectory}/Shutdown.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $GLOBALS['eventName'] = 'go';
        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. The continuation server never starts a new
         * 'Shutdown' again.
         */
        $server->shutdown();
        $server->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_SHUTDOWN, $error['code']);

        unset($GLOBALS['ShutdownCount']);
        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.7.0
     */
    function testShouldBeRequiredFlowExecutionTicketWheneverContinuingFlowExecution()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();
        $flowExecutionTicket2 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertTrue(Piece_Flow_Error::hasErrors('warning'));
        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_EXISTS, $error['code']);
        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.7.0
     */
    function testGettingFlowExecutionTicketByFlowName()
    {
        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $server->addFlow('SecondCounter', "{$this->_cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $flowExecutionTicket1 = $server->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $GLOBALS['flowExecutionTicket'] = null;
        $GLOBALS['flowName'] = 'SecondCounter';

        $flowExecutionTicket2 = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertFalse($flowExecutionTicket1 == $flowExecutionTicket2);
        $this->assertEquals($flowExecutionTicket1, $service->getFlowExecutionTicketByFlowName('Counter'));
        $this->assertNull($service->getFlowExecutionTicketByFlowName('SecondCounter'));
    }

    /**
     * @since Method available since Release 1.8.0
     */
    function testBindActionsWithFlowExecution()
    {
        Piece_Flow_Action_Factory::setActionDirectory($this->_cacheDirectory);
        $flowName = 'BindActionsWithFlowExecution';
        $GLOBALS['flowName'] = $flowName;
        $GLOBALS['eventName'] = 'goDisplayFinishFromDisplayCounter';
        $server = &new Piece_Flow_Continuation_Server();
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        // The first time invocation for the flow execution one.
        $flowExecutionTicket1 = $server->invoke(new stdClass(), true);

        $this->assertEquals('Counter', $server->getView());

        $server->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        // The first time invocation for the flow execution two.
        $flowExecutionTicket2 = $server->invoke(new stdClass(), true);

        $this->assertEquals('Counter', $server->getView());

        $server->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        // The second time invocation for the flow execution one.
        $server->invoke(new stdClass(), true);

        $this->assertEquals('Counter', $server->getView());

        $server->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        // The last invocation for the flow execution one.
        $server->invoke(new stdClass(), true);

        $this->assertEquals('Finish', $server->getView());

        $server->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket2;

        // The second time invocation for the flow execution two.
        $server->invoke(new stdClass(), true);

        $this->assertEquals('Counter', $server->getView());

        $server->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        // The last invocation for the flow execution two.
        $server->invoke(new stdClass(), true);

        $this->assertEquals('Finish', $server->getView());

        $server->shutdown();
        Piece_Flow_Action_Factory::clearInstances();
    }

    /**
     * @since Method available since Release 1.11.0
     */
    function testFlowExecutionExpiredExceptionShouldBeRaisedWhenFlowExecutionHasExpired()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $flowName = 'FlowExecutionExpired';
        $GLOBALS['flowName'] = $flowName;
        $server = &new Piece_Flow_Continuation_Server(false, true, 1);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $server->invoke(new stdClass());
        $server->shutdown();
        sleep(2);
        $server->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.11.0
     */
    function testFlowExecutionExpiredExceptionShouldNotBeRaisedWhenFlowExecutionHasNotExpired()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $flowName = 'FlowExecutionExpired';
        $GLOBALS['flowName'] = $flowName;
        $server = &new Piece_Flow_Continuation_Server(false, true, 2);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $server->invoke(new stdClass());
        $server->shutdown();
        sleep(1);
        $server->invoke(new stdClass());

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));

        sleep(1);
        $server->invoke(new stdClass());

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));

        sleep(1);
        $server->invoke(new stdClass());

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));

        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.11.0
     */
    function testNewFlowExecutionShouldBeAbleToStartWithSameRequestAfterFlowExecutionIsExpired()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $flowName = 'FlowExecutionExpired';
        $GLOBALS['flowName'] = $flowName;
        $server = &new Piece_Flow_Continuation_Server(false, true, 1);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $server->invoke(new stdClass());
        $server->shutdown();
        sleep(2);
        $server->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED, $error['code']);

        $newFlowExecutionTicket = $server->invoke(new stdClass());

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));
        $this->assertTrue($newFlowExecutionTicket != $GLOBALS['flowExecutionTicket']);

        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testCheckLastEventShouldReturnTrueIfContinuationHasJustStarted()
    {
        $flowName = 'CheckLastEvent';
        $GLOBALS['flowName'] = $flowName;
        $GLOBALS['eventName'] = 'foo';
        $server = &new Piece_Flow_Continuation_Server(false);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertTrue($service->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testCheckLastEventShouldReturnTrueWhenValidEventIsGivenByUser()
    {
        $flowName = 'CheckLastEvent';
        $GLOBALS['flowName'] = $flowName;
        $server = &new Piece_Flow_Continuation_Server(false);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $server->invoke(new stdClass());
        $server->shutdown();

        $GLOBALS['eventName'] = 'DisplayEditConfirmFromDisplayEdit';

        $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertTrue($service->checkLastEvent());

        $server->shutdown();

        $GLOBALS['eventName'] = 'DisplayEditFinishFromDisplayEditConfirm';

        $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertTrue($service->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testCheckLastEventShouldReturnFalseWhenInvalidEventIsGivenByUser()
    {
        $flowName = 'CheckLastEvent';
        $GLOBALS['flowName'] = $flowName;
        $server = &new Piece_Flow_Continuation_Server(false);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $server->invoke(new stdClass());
        $server->shutdown();

        $GLOBALS['eventName'] = 'foo';

        $server->invoke(new stdClass());
        $service = &$server->createService();

        $this->assertFalse($service->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testCheckLastEventShouldReturnTrueIfContinuationHasNotActivatedYet()
    {
        $flowName = 'CheckLastEvent';
        $GLOBALS['flowName'] = $flowName;
        $GLOBALS['eventName'] = 'foo';
        $server = &new Piece_Flow_Continuation_Server(false);
        $server->setCacheDirectory($this->_cacheDirectory);
        $server->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $service = &$server->createService();

        $this->assertTrue($service->checkLastEvent());
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    // }}}
}

// }}}

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
