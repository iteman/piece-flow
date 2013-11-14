<?php
/*
 * Copyright (c) 2007-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\Pageflow;

use Stagehand\FSM\Event\DoEvent;
use Stagehand\FSM\Event\EntryEvent;
use Stagehand\FSM\Event\EventInterface;
use Stagehand\FSM\Event\ExitEvent;
use Stagehand\FSM\StateMachine\StateMachineBuilder;
use Stagehand\FSM\State\StateInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

use Piece\Flow\Pageflow\State\ActionState;
use Piece\Flow\Pageflow\State\ViewState;

/**
 * @since Class available since Release 1.14.0
 */
class PageflowGenerator
{
    /**
     * @var \Piece\Flow\Pageflow\PageflowInterface
     * @since Property available since Release 2.0.0
     */
    protected $pageflow;

    /**
     * @var \Piece\Flow\Pageflow\PageflowRegistries
     * @since Property available since Release 2.0.0
     */
    protected $pageflowRegistries;

    /**
     * @var \Stagehand\FSM\StateMachine\StateMachineBuilder
     * @since Property available since Release 2.0.0
     */
    protected $stateMachineBuilder;

    /**
     * @param \Piece\Flow\Pageflow\PageflowInterface  $pageflow
     * @param \Piece\Flow\Pageflow\PageflowRegistries $pageflowRegistries
     */
    public function __construct(PageflowInterface $pageflow, PageflowRegistries $pageflowRegistries)
    {
        $this->pageflow = $pageflow;
        $this->pageflowRegistries = $pageflowRegistries;
        $this->stateMachineBuilder = new StateMachineBuilder($this->pageflow);
    }

    /**
     * Configures a Pageflow object from the specified definition.
     *
     * @return \Piece\Flow\Pageflow\PageflowInterface
     * @throws \Piece\Flow\Pageflow\ProtectedStateException
     */
    public function generate()
    {
        $definition = $this->readDefinition();
        if (in_array($definition['firstState'], array(StateInterface::STATE_INITIAL, StateInterface::STATE_FINAL))) {
            throw new ProtectedStateException("The state [ {$definition['firstState']} ] cannot be used in flow definitions.");
        }

        foreach ($definition['viewState'] as $state) {
            if (in_array($state['name'], array(StateInterface::STATE_INITIAL, StateInterface::STATE_FINAL))) {
                throw new ProtectedStateException("The state [ {$state['name']} ] cannot be used in flow definitions.");
            }

            $this->addState(new ViewState($state['name']));
        }
        foreach ($definition['actionState'] as $state) {
            if (in_array($state['name'], array(StateInterface::STATE_INITIAL, StateInterface::STATE_FINAL))) {
                throw new ProtectedStateException("The state [ {$state['name']} ] cannot be used in flow definitions.");
            }

            $this->addState(new ActionState($state['name']));
        }
        if (!empty($definition['lastState'])) {
            if (in_array($definition['lastState']['name'], array(StateInterface::STATE_INITIAL, StateInterface::STATE_FINAL))) {
                throw new ProtectedStateException("The state [ {$definition['lastState']['name']} ] cannot be used in flow definitions.");
            }

            $this->addState(new ViewState($definition['lastState']['name']));
        }

        if (empty($definition['initial'])) {
            $this->stateMachineBuilder->setStartState($definition['firstState']);
        } else {
            $this->stateMachineBuilder->setStartState($definition['firstState'], $this->wrapAction($definition['initial']));
        }

        if (!empty($definition['lastState'])) {
            if (empty($definition['final'])) {
                $this->stateMachineBuilder->setEndState($definition['lastState']['name'], PageflowInterface::EVENT_END);
            } else {
                $this->stateMachineBuilder->setEndState($definition['lastState']['name'], PageflowInterface::EVENT_END, $this->wrapAction($definition['final']));
            }
            $this->configureViewState($definition['lastState']);
            $this->stateMachineBuilder->getStateMachine()->getState($definition['lastState']['name'])->setView($definition['lastState']['view']);
        }

        $this->configureViewStates($definition['viewState']);
        $this->configureActionStates($definition['actionState']);

        return $this->pageflow;
    }

    /**
     * Configures view states.
     *
     * @param  array                                        $states
     * @throws \Piece\Flow\Pageflow\ProtectedStateException
     */
    protected function configureViewStates(array $states)
    {
        foreach ($states as $state) {
            $this->configureViewState($state);
        }
    }

    /**
     * Configures action states.
     *
     * @param  array                                        $states
     * @throws \Piece\Flow\Pageflow\ProtectedStateException
     */
    protected function configureActionStates(array $states)
    {
        foreach ($states as $state) {
            $this->configureState($state);
        }
    }

    /**
     * Configures a state.
     *
     * @param  array                                        $state
     * @throws \Piece\Flow\Pageflow\ProtectedEventException
     */
    protected function configureState(array $state)
    {
        for ($i = 0, $count = count(@$state['transition']); $i < $count; ++$i) {
            if (in_array($state['transition'][$i]['event'], array(EventInterface::EVENT_ENTRY, EventInterface::EVENT_EXIT, EventInterface::EVENT_START, EventInterface::EVENT_DO))) {
                throw new ProtectedEventException("The event [ {$state['transition'][$i]['event']} ] cannot be used in flow definitions.");
            }

            $this->stateMachineBuilder->addTransition($state['name'],
                                       $state['transition'][$i]['event'],
                                       $state['transition'][$i]['nextState'],
                                       $this->wrapEventTriggerAction(@$state['transition'][$i]['action']),
                                       $this->wrapAction(@$state['transition'][$i]['guard'])
                                       );
        }

        if (!empty($state['entry'])) {
            $this->stateMachineBuilder->setEntryAction($state['name'],
                                        $this->wrapAction(@$state['entry'])
                                        );
        }

        if (!empty($state['exit'])) {
            $this->stateMachineBuilder->setExitAction($state['name'],
                                       $this->wrapAction(@$state['exit'])
                                       );
        }

        if (!empty($state['activity'])) {
            $this->stateMachineBuilder->setActivity($state['name'],
                                     $this->wrapEventTriggerAction(@$state['activity'])
                                     );
        }
    }

    /**
     * Wraps a simple action up with an Action object and returns
     * a callback. The simple action means that the action is entry action or
     * exit action or guard.
     *
     * @param  array $action
     * @return array
     */
    protected function wrapAction(array $action = null)
    {
        if (is_null($action)) {
            return $action;
        }

        if (is_null($action['class'])) {
            $actionID = $action['method'];
        } else {
            $actionID = $action['class'] . ':' . $action['method'];
        }

        return array(new EventHandler($actionID, $this->pageflow), 'invokeAction');
    }

    /**
     * Configures a view state.
     *
     * @param array $state
     */
    protected function configureViewState(array $state)
    {
        $this->stateMachineBuilder->getStateMachine()->getState($state['name'])->setView($state['view']);
        $this->configureState($state);
    }

    /**
     * Wraps an event trigger action up with an Action object and
     * returns a callback. The event trigger action means that the action is
     * transition action or activity.
     *
     * @param  array $action
     * @return array
     */
    protected function wrapEventTriggerAction(array $action = null)
    {
        if (is_null($action)) {
            return $action;
        }

        if (is_null($action['class'])) {
            $actionID = $action['method'];
        } else {
            $actionID = $action['class'] . ':' . $action['method'];
        }

        return array(new EventHandler($actionID, $this->pageflow), 'invokeActionAndTriggerEvent');
    }

    /**
     * @return array
     * @since Method available since Release 2.0.0
     */
    protected function readDefinition()
    {
        $processor = new Processor();

        return $processor->processConfiguration(
            new Definition17Configuration(),
            array('definition17' => Yaml::parse($this->pageflowRegistries->getFileName($this->pageflow->getID())))
        );
    }

    /**
     * @param \Stagehand\FSM\State\StateInterface $state
     * @since Method available since Release 2.0.0
     */
    protected function addState(StateInterface $state)
    {
        $state->setEntryEvent(new EntryEvent());
        $state->setExitEvent(new ExitEvent());
        $state->setDoEvent(new DoEvent());
        $this->stateMachineBuilder->getStateMachine()->addState($state);
    }
}
