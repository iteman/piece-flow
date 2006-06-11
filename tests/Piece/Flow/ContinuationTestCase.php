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
 * @see        Piece_Flow_Continuation
 * @since      File available since Release 1.0.0
 */

require_once 'Piece/Flow/Continuation.php';

require_once 'Cache/Lite/File.php';

// {{{ Piece_Flow_ContinuationTestCase

/**
 * TestCase for Piece_Flow_Continuation
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow_Continuation
 * @since      Class available since Release 1.0.0
 */
class Piece_Flow_ContinuationTestCase extends PHPUnit_TestCase
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
    var $_flowName;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        Piece_Flow_Action_Factory::setActionPath(dirname(__FILE__) . '/../..');
        $this->_flowName = 'Counter';
    }

    function tearDown()
    {
        $GLOBALS['PIECE_FLOW_Action_Instances'] = array();
        $GLOBALS['PIECE_FLOW_Action_Path'] = null;
        $this->_flowName = null;
        $this->_flowExecutionTicket = null;
        $cache = &new Cache_Lite_File(array('cacheDir' => dirname(__FILE__) . '/',
                                            'masterFile' => '',
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        $stack = &Piece_Flow_Error::getErrorStack();
        $stack->getErrors(true);
        PEAR_ErrorStack::staticPopCallback();
    }

    function testAddingFlowInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('foo', '/path/to/foo.xml');

        $this->assertTrue($continuation->hasFlow('foo'));
        $this->assertFalse($continuation->hasFlow('bar'));
    }

    function testFailureToAddFlowForSecondTimeInSingleFlowMode()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('foo', '/path/to/foo.xml');

        $this->assertTrue($continuation->hasFlow('foo'));

        $continuation->addFlow('bar', '/path/to/bar.xml');

        $this->assertTrue(PEAR_ErrorStack::staticHasErrors());

        $stack = &Piece_Flow_Error::getErrorStack();

        $this->assertTrue($stack->hasErrors());

        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_EXISTS, $error['code']);

        PEAR_ErrorStack::staticPopCallback();
    }

    function testSettingFlowInMultipleFlowExecutionMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('foo', '/path/to/foo.xml');
        $continuation->addFlow('bar', '/path/to/bar.xml');

        $this->assertTrue($continuation->hasFlow('foo'));
        $this->assertTrue($continuation->hasFlow('bar'));
        $this->assertFalse($continuation->hasFlow('baz'));
    }

    function testFirstTimeInvocationInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');

        $flowExecutionTicket = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(0, $continuation->getAttribute('counter'));
    }

    function testSecondTimeInvocationInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function getEventName()
    {
        return 'increase';
    }

    function testInvocationInMultipleFlowExecutionModeByNonExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $this->_flowExecutionTicket = $continuation->invoke(new stdClass());
        $flowExecutionTicket = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $this->_flowExecutionTicket);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($this->_flowExecutionTicket, $flowExecutionTicket);
    }

    function testMultipleInvocationInMultipleFlowExecutionModeByNonExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->addFlow('SecondCounter', dirname(__FILE__) . '/SecondCounter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        /*
         * Starting a new 'Counter'.
         */
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        /*
         * Starting a new 'SecondCounter'.
         */
        $this->_flowName = 'SecondCounter';
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket2);
        $this->assertEquals('SecondCounter', $continuation->getView());
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        /*
         * Continuing the first 'Counter'.
         */
        $this->_flowExecutionTicket = $flowExecutionTicket1;
        $this->_flowName = 'Counter';
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertEquals(1, $continuation->getAttribute('counter'));

        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket3);

        /*
         * Continuing the first 'SecondCounter'.
         */
        $this->_flowExecutionTicket = $flowExecutionTicket2;
        $this->_flowName = 'SecondCounter';
        $flowExecutionTicket4 = $continuation->invoke(new stdClass());

        $this->assertEquals('SecondCounter', $continuation->getView());
        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket2, $flowExecutionTicket4);

        /*
         * Starting a new 'SecondCounter'.
         */
        $this->_flowExecutionTicket = null;
        $secondCounter->counter = null;
        $this->_flowName = 'SecondCounter';
        $flowExecutionTicket5 = $continuation->invoke(new stdClass());

        $this->assertEquals('SecondCounter', $continuation->getView());
        $this->assertEquals(0, $continuation->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket2 != $flowExecutionTicket5);
    }

    function testSuccessOfContinuationByInvalidFlowNameInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $continuation->invoke(new stdClass());
        $this->_flowName = 'InvalidFlowName';
        $continuation->invoke(new stdClass());

        $this->assertFalse(PEAR_ErrorStack::staticHasErrors());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');
        $this->assertEquals(1, $continuation->getAttribute('counter'));
    }

    function testFailureOfContinuationByInvalidFlowNameInMultipleFlowExecutionMode()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $continuation->invoke(new stdClass());
        $this->_flowName = 'InvalidFlowName';
        $continuation->invoke(new stdClass());

        $this->assertTrue(PEAR_ErrorStack::staticHasErrors());

        $stack = &Piece_Flow_Error::getErrorStack();
        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        PEAR_ErrorStack::staticPopCallback();
    }

    function testFailureToInvokeByNonExistingFlowConfiguration()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('NonExistingFile', dirname(__FILE__) . '/NonExistingFile.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $this->_flowName = 'NonExistingFile';
        $continuation->invoke(new stdClass());

        $this->assertTrue(PEAR_ErrorStack::staticHasErrors());

        $stack = &Piece_Flow_Error::getErrorStack();
        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        PEAR_ErrorStack::staticPopCallback();
    }

    function getFlowExecutionTicket()
    {
        return $this->_flowExecutionTicket;
    }

    function getFlowName()
    {
        return $this->_flowName;
    }

    function testInvocationInMultipleFlowExecutionModeByExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->addFlow('SecondCounter', dirname(__FILE__) . '/SecondCounter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        $this->_flowName = 'SecondCounter';
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        $this->_flowName = 'Counter';
        $this->_flowExecutionTicket = null;
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(1, $continuation->getAttribute('counter'));

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);
        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
    }

    function testInvocationInSingleFlowModeByExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $this->_flowExecutionTicket = null;
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testSettingAttribute()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $this->_flowExecutionTicket = $continuation->invoke(new stdClass());
        $continuation->setAttribute('foo', 'bar');
        $continuation->invoke(new stdClass());
        $continuation->setAttribute('bar', 'baz');

        $this->assertTrue($continuation->hasAttribute('foo'));
        $this->assertEquals('bar', $continuation->getAttribute('foo'));
        $this->assertTrue($continuation->hasAttribute('bar'));
        $this->assertEquals('baz', $continuation->getAttribute('bar'));
    }

    function testFailureToSetAttributeBeforeStartingFlow()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $continuation->setAttribute('foo', 'bar');

        $this->assertTrue(PEAR_ErrorStack::staticHasErrors());

        $stack = &Piece_Flow_Error::getErrorStack();
        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testFailureToGetAttributeBeforeStartingFlow()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $continuation->getAttribute('foo');

        $this->assertTrue(PEAR_ErrorStack::staticHasErrors());

        $stack = &Piece_Flow_Error::getErrorStack();
        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
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
