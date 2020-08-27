<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\FunctionalTestingFramework\Test\Util;

use Magento\FunctionalTestingFramework\Test\Objects\ActionObject;
use Magento\FunctionalTestingFramework\Test\Objects\TestHookObject;

/**
 * Class TestHookObjectExtractor
 */
class TestHookObjectExtractor extends BaseObjectExtractor
{
    /**
     * Action Object Extractor object
     *
     * @var ActionObjectExtractor
     */
    private $actionObjectExtractor;

    /**
     * TestHookObjectExtractor constructor
     */
    public function __construct()
    {
        $this->actionObjectExtractor = new ActionObjectExtractor();
    }

    /**
     * This method trims all irrelevant tags to extract hook information including before and after tags
     * and their relevant actions. The result is an array of TestHookObjects.
     *
     * @param string $parentName
     * @param string $hookType
     * @param array  $testHook
     * @return TestHookObject
     * @throws \Exception
     */
    public function extractHook($parentName, $hookType, $testHook)
    {
        $hookActions = $this->stripDescriptorTags(
            $testHook,
            self::NODE_NAME
        );

        $actions = $this->actionObjectExtractor->extractActions($hookActions);

        if ($hookType === 'after' && getenv('ROLLBACK') === 'PER_TEST') {
            $actions = array_merge(
                $actions,
                $this->getRollbackActions(TestObjectExtractor::TEST_AFTER_HOOK)
            );
        }

        $hook = new TestHookObject(
            $hookType,
            $parentName,
            $actions
        );

        return $hook;
    }

    /**
     * Creates the default failed hook object with a single saveScreenshot action.
     * And a pause action when ENABLE_PAUSE is set to true, and etc
     *
     * @param string $parentName
     * @return TestHookObject
     */
    public function createDefaultFailedHook($parentName)
    {
        $defaultSteps['saveScreenshot'] = new ActionObject("saveScreenshot", "saveScreenshot", []);
        if (getenv('ENABLE_PAUSE') === 'true') {
            $defaultSteps['pauseWhenFailed'] = new ActionObject(
                'pauseWhenFailed',
                'pause',
                [ActionObject::PAUSE_ACTION_INTERNAL_ATTRIBUTE => true]
            );
        }
        if (getenv('ROLLBACK') === 'ON_FAILURE') {
            $defaultSteps = array_merge(
                $defaultSteps,
                $this->getRollbackActions(TestObjectExtractor::TEST_FAILED_HOOK)
            );
        }

        $hook = new TestHookObject(
            TestObjectExtractor::TEST_FAILED_HOOK,
            $parentName,
            $defaultSteps
        );

        return $hook;
    }

    /**
     * Creates the default after hook object
     *
     * @param string $parentName
     * @return TestHookObject|null
     */
    public function createDefaultAfterHook($parentName)
    {
        $hook = null;

        if (getenv('ROLLBACK') === 'PER_TEST') {
            $actions = $this->getRollbackActions(TestObjectExtractor::TEST_AFTER_HOOK);

            $hook = new TestHookObject(
                TestObjectExtractor::TEST_AFTER_HOOK,
                $parentName,
                $actions
            );
        }

        return $hook;
    }

    /**
     * Rollback Actions
     *
     * @param string $hookType
     * @return ActionObject[]
     */
    private function getRollbackActions($hookType)
    {
        $actions['rollbackMedia' . ucfirst($hookType)] = new ActionObject(
            'rollbackMedia' . ucfirst($hookType),
            'mediaRollBack',
            []
        );
        $actions['rollbackDB' . ucfirst($hookType)] = new ActionObject(
            'rollbackDB' . ucfirst($hookType),
            'dbRollBack',
            []
        );

        return $actions;
    }
}
