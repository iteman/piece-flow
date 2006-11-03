<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2006 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-flow/
 * @since      File available since Release 1.0.0
 */

require_once 'Piece/Flow.php';
require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/Action/Factory.php';

// {{{ GLOBALS

$GLOBALS['PIECE_FLOW_Continuation_Active_Instances'] = array();
$GLOBALS['PIECE_FLOW_Continuation_Shutdown_Registered'] = false;

// }}}
// {{{ Piece_Flow_Continuation

/**
 * The continuation server for the Piece_Flow package.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2006 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-flow/
 * @since      Class available since Release 1.0.0
 */
class Piece_Flow_Continuation
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_flowDefinitions = array();
    var $_enableSingleFlowMode;
    var $_cacheDirectory;
    var $_flowExecutions = array();
    var $_flowExecutionTicketCallback;
    var $_flowNameCallback;
    var $_eventNameCallback;
    var $_exclusiveFlowExecutionTicketsByFlowName = array();
    var $_flowExecutionTicket;
    var $_isFirstTime;
    var $_flowName;
    var $_currentFlowExecutionTicket;
    var $_activated = false;
    var $_exclusiveFlowNamesByFlowExecutionTicket = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets whether the continuation server should be work in the single flow
     * mode.
     *
     * @param boolean $enableSingleFlowMode
     */
    function Piece_Flow_Continuation($enableSingleFlowMode = false)
    {
        $this->_enableSingleFlowMode = $enableSingleFlowMode;
    }

    // }}}
    // {{{ addFlow()

    /**
     * Adds a flow definition to the Piece_Flow_Continuation object.
     *
     * @param string  $name
     * @param string  $file
     * @param boolean $isExclusive
     * @throws PIECE_FLOW_ERROR_ALREADY_EXISTS
     */
     function addFlow($name, $file, $isExclusive = false)
     {
         if ($this->_enableSingleFlowMode && count($this->_flowDefinitions)) {
             Piece_Flow_Error::push(PIECE_FLOW_ERROR_ALREADY_EXISTS,
                                    'A flow definition already exists in the continuation object.'
                                    );
             return;
         }

         $this->_flowDefinitions[$name] = array('file' => $file,
                                                'isExclusive' => $isExclusive
                                                );
    }

    // }}}
    // {{{ invoke()

    /**
     * Invokes a flow and returns a flow execution ticket.
     *
     * @param mixed &$payload
     * @return string
     * @throws PIECE_FLOW_ERROR_NOT_GIVEN
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_INVALID_DRIVER
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     * @throws PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN
     */
    function invoke(&$payload)
    {
        $this->_prepare();
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        if (!$this->_isFirstTime) {
            $this->_continue($payload);
        } else {
            $this->_start($payload);
        }

        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        $GLOBALS['PIECE_FLOW_Continuation_Active_Instances'][] = &$this;
        if (!$GLOBALS['PIECE_FLOW_Continuation_Shutdown_Registered']) {
            $GLOBALS['PIECE_FLOW_Continuation_Shutdown_Registered'] = true;
            register_shutdown_function(array(__CLASS__, 'shutdown'));
        }

        return $this->_currentFlowExecutionTicket;
    }

    // }}}
    // {{{ getView()

    /**
     * Gets an appropriate view string which corresponding to the current
     * state.
     *
     * @return string
     * @throws PIECE_FLOW_ERROR_INVALID_TRANSITION
     */
    function getView()
    {
        if (!$this->_activated()) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.',
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();
            return;
        }

        return $this->_flowExecutions[$this->_currentFlowExecutionTicket]->getView();
    }

    // }}}
    // {{{ setEventNameCallback()

    /**
     * Sets a callback for getting an event name.
     *
     * @param callback $callback
     */
    function setEventNameCallback($callback)
    {
        $this->_eventNameCallback = $callback;
    }

    // }}}
    // {{{ setFlowExecutionTicketCallback()

    /**
     * Sets a callback for getting a flow execution ticket.
     *
     * @param callback $callback
     */
    function setFlowExecutionTicketCallback($callback)
    {
        $this->_flowExecutionTicketCallback = $callback;
    }

    // }}}
    // {{{ setFlowNameCallback()

    /**
     * Sets a callback for getting a flow name.
     *
     * @param callback $callback
     */
    function setFlowNameCallback($callback)
    {
        $this->_flowNameCallback = $callback;
    }

    // }}}
    // {{{ setCacheDirectory()

    /**
     * Sets a cache directory for the flow definitions.
     *
     * @param string $cacheDirectory
     */
    function setCacheDirectory($cacheDirectory)
    {
        $this->_cacheDirectory = $cacheDirectory;
    }

    // }}}
    // {{{ setAttribute()

    /**
     * Sets an attribute for the current flow.
     *
     * @param string $name
     * @param mixed  $value
     */
    function setAttribute($name, $value)
    {
        if (!$this->_activated()) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.',
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();
            return;
        }

        $this->_flowExecutions[$this->_currentFlowExecutionTicket]->setAttribute($name, $value);
    }

    // }}}
    // {{{ hasAttribute()

    /**
     * Returns whether this flow has an attribute with a given name.
     *
     * @param string $name
     * @return boolean
     */
    function hasAttribute($name)
    {
        if (!$this->_activated()) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.',
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();
            return;
        }

        return $this->_flowExecutions[$this->_currentFlowExecutionTicket]->hasAttribute($name);
    }

    // }}}
    // {{{ getAttribute()

    /**
     * Gets an attribute for the current flow.
     *
     * @param string $name
     * @return mixed
     */
    function getAttribute($name)
    {
        if (!$this->_activated()) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.',
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();
            return;
        }

        return $this->_flowExecutions[$this->_currentFlowExecutionTicket]->getAttribute($name);
    }

    // }}}
    // {{{ shutdown()

    /**
     * Shutdown the continuation server for next events.
     *
     * @static
     */
    function shutdown()
    {
        $count = count($GLOBALS['PIECE_FLOW_Continuation_Active_Instances']);
        for ($i = 0; $i < $count; ++$i) {
            $instance = &$GLOBALS['PIECE_FLOW_Continuation_Active_Instances'][$i];
            if (!is_a($instance, __CLASS__)) {
                unset($GLOBALS['PIECE_FLOW_Continuation_Active_Instances'][$i]);
                continue;
            }
            $instance->clear();
        }
    }

    // }}}
    // {{{ clear()

    /**
     * Clears some properties for next events.
     */
    function clear()
    {
        if (array_key_exists($this->_currentFlowExecutionTicket, $this->_flowExecutions)
            && !$this->_enableSingleFlowMode
            && $this->_flowExecutions[$this->_currentFlowExecutionTicket]->isFinalState()
            ) {
            unset($this->_flowExecutions[$this->_currentFlowExecutionTicket]);
            if (array_key_exists($this->_flowName, $this->_exclusiveFlowExecutionTicketsByFlowName)) {
                unset($this->_exclusiveFlowExecutionTicketsByFlowName[$this->_flowName]);
                unset($this->_exclusiveFlowNamesByFlowExecutionTicket[$this->_flowExecutionTicket]);
            }
        }

        $this->_flowExecutionTicket = null;
        $this->_isFirstTime = null;
        $this->_flowName = null;
        $this->_currentFlowExecutionTicket = null;
        $this->_activated = false;
    }

    // }}}
    // {{{ setActionDirectory()

    /**
     * Sets a action directory.
     *
     * @param string $actionDirectory
     * @static
     */
    function setActionDirectory($actionDirectory)
    {
        Piece_Flow_Action_Factory::setActionDirectory($actionDirectory);
    }

    // }}}
    // {{{ getCurrentFlowExecutionTicket()

    /**
     * Gets the current flow execution ticket for the current flow.
     *
     * @return string
     */
    function getCurrentFlowExecutionTicket()
    {
        if (!$this->_activated()) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.',
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();
            return;
        }

        return $this->_currentFlowExecutionTicket;
    }

    // }}}
    // {{{ setAttributeByRef()

    /**
     * Sets an attribute by reference for the current flow.
     *
     * @param string $name
     * @param mixed  &$value
     * @since Method available since Release 1.6.0
     */
    function setAttributeByRef($name, &$value)
    {
        if (!$this->_activated()) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.',
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();
            return;
        }

        $this->_flowExecutions[$this->_currentFlowExecutionTicket]->setAttributeByRef($name, $value);
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _generateFlowExecutionTicket()

    /**
     * Generates a flow execution ticket.
     */
    function _generateFlowExecutionTicket()
    {
        return sha1(uniqid(mt_rand(), true));
    }

    // }}}
    // {{{ _prepare()

    /**
     * Prepares a flow execution ticket, a flow name, and whether the
     * flow invocation is the first time or not.
     *
     * @throws PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN
     */
    function _prepare()
    {
        $this->_flowExecutionTicket = call_user_func($this->_flowExecutionTicketCallback);
        if ($this->_hasFlowExecutionTicket($this->_flowExecutionTicket)) {
            if (array_key_exists($this->_flowExecutionTicket, $this->_exclusiveFlowNamesByFlowExecutionTicket)) {
                $this->_flowName = $this->_exclusiveFlowNamesByFlowExecutionTicket[$this->_flowExecutionTicket];
            }

            $this->_isFirstTime = false;
        } else {
            if (!$this->_enableSingleFlowMode) {
                $this->_flowName = call_user_func($this->_flowNameCallback);
            } else {
                $flowNames = array_keys($this->_flowDefinitions);
                $this->_flowName = $flowNames[0];
            }

            if (is_null($this->_flowName) || !strlen($this->_flowName)) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN,
                                       'A flow name must be given in this case.'
                                       );
                return;
            }

            if (!array_key_exists($this->_flowName, $this->_exclusiveFlowExecutionTicketsByFlowName)) {
                $this->_isFirstTime = true;
            } else {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_ALREADY_EXISTS,
                                       "Another flow execution of the current flow [ {$this->_flowName} ] already exists in the flow executions. Please check the value of your execution ticket."
                                       );
                return;
            }
        }
    }

    // }}}
    // {{{ _continue()

    /**
     * Continues with the current continuation.
     *
     * @param mixed &$payload
     * @throws PIECE_FLOW_ERROR_CANNOT_INVOKE
     * @throws PIECE_FLOW_ERROR_ALREADY_SHUTDOWN
     */
    function _continue(&$payload)
    {
        $this->_currentFlowExecutionTicket = $this->_flowExecutionTicket;
        $this->_activated = true;
        $this->_flowExecutions[$this->_flowExecutionTicket]->setPayload($payload);
        $this->_flowExecutions[$this->_flowExecutionTicket]->triggerEvent(call_user_func($this->_eventNameCallback));
    }

    // }}}
    // {{{ _start()

    /**
     * Starts a new continuation with a flow.
     *
     * @param mixed &$payload
     * @return string
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_INVALID_DRIVER
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @throws PIECE_FLOW_ERROR_PROTECTED_EVENT
     * @throws PIECE_FLOW_ERROR_PROTECTED_STATE
     * @throws PIECE_FLOW_ERROR_CANNOT_INVOKE
     * @throws PIECE_FLOW_ERROR_ALREADY_SHUTDOWN
     */
    function _start(&$payload)
    {
        if (!array_key_exists($this->_flowName, $this->_flowDefinitions)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                   "The flow name [ {$this->_flowName} ] not found in the flow definitions."
                                   );
            return;
        }

        $flow = &new Piece_Flow();
        $flow->configure($this->_flowDefinitions[$this->_flowName]['file'],
                         null,
                         $this->_cacheDirectory
                         );
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        while (true) {
            $flowExecutionTicket = $this->_generateFlowExecutionTicket();
            if (!$this->_hasFlowExecutionTicket($flowExecutionTicket)) {
                $this->_flowExecutions[$flowExecutionTicket] = &$flow;
                break;
            }
        }

        $this->_currentFlowExecutionTicket = $flowExecutionTicket;
        $this->_activated = true;
        $flow->setPayload($payload);
        $flow->start();
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        if ($this->_enableSingleFlowMode || $this->_flowDefinitions[$this->_flowName]['isExclusive']
            ) {
            $this->_exclusiveFlowExecutionTicketsByFlowName[$this->_flowName] = $flowExecutionTicket;
            $this->_exclusiveFlowNamesByFlowExecutionTicket[$flowExecutionTicket] = $this->_flowName;
        }

        return $flowExecutionTicket;
    }

    // }}}
    // {{{ _activated()

    /**
     * Returns whether the current flow has activated or not.
     *
     * @return boolean
     */
    function _activated()
    {
        return $this->_activated;
    }

    // }}}
    // {{{ _hasFlowExecutionTicket()

    /**
     * Returns whether the current flow has the flow execution ticket or not.
     *
     * @param string $flowExecutionTicket
     * @return boolean
     */
    function _hasFlowExecutionTicket($flowExecutionTicket)
    {
        return array_key_exists($flowExecutionTicket, $this->_flowExecutions);
    }

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
