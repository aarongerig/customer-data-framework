<?php
/**
 * Created by PhpStorm.
 * User: mmoser
 * Date: 30.11.2016
 * Time: 09:56
 */

namespace CustomerManagementFrameworkBundle\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/rules")
 */
class RulesController extends \Pimcore\Bundle\AdminBundle\Controller\AdminController
{

    /**
     * get saved action trigger rules
     *
     * @param Request $request
     * @Route("/list")
     */
    public function listAction(Request $request)
    {
        $rules = new \CustomerManagementFrameworkBundle\Model\ActionTrigger\Rule\Listing();
        $rules->setOrderKey('name');
        $rules->setOrder('ASC');

        $json = array();
        foreach ($rules->load() as $rule) {

            if ($rule->getActive()) {
                $icon = 'plugin_cmf_icon_rule_enabled';
                $title = 'active';
            } else {
                $icon = 'plugin_cmf_icon_rule_disabled';
                $title = 'inactive';
            }

            $json[] = array(
                'iconCls' => $icon,
                'id' => $rule->getId(),
                'text' => $rule->getName(),
                'leaf' => true,
                'qtipCfg' => array(
                    'title' => $title,
                    'text' => $rule->getDescription(),
                ),
            );
        }

        return $this->json($json);
    }

    /**
     * get rule config as json
     *
     * @param Request $request
     * @Route("/get")
     */
    public function getAction(Request $request)
    {
        $rule = \CustomerManagementFrameworkBundle\Model\ActionTrigger\Rule::getById((int)$request->get('id'));
        if ($rule) {
            // create json config
            $json = array(
                'id' => $rule->getId(),
                'name' => $rule->getName(),
                'description' => $rule->getDescription(),
                'active' => $rule->getActive(),
                'trigger' => [],
                'condition' => [],
                'actions' => [],
            );


            foreach ($rule->getTrigger() as $trigger) {
                $json['trigger'][] = $trigger->toArray();
            }


            foreach ($rule->getAction() as $action) {

                if (class_exists($action->getImplementationClass())) {
                    $actionData = call_user_func([$action->getImplementationClass(), 'getDataForEditmode'], $action);
                } else {
                    throw new \Exception(sprintf("class '%s' does not exist", $action->getImplementationClass()));
                }

                $json['actions'][] = $actionData;
            }

            foreach ($rule->getCondition() as $condition) {
                if (class_exists($condition->getImplementationClass())) {
                    $conditionData = call_user_func(
                        [$condition->getImplementationClass(), 'getDataForEditmode'],
                        $condition
                    );
                } else {
                    throw new \Exception(sprintf("class '%s' does not exist", $condition->getImplementationClass()));
                }

                $json['condition'][] = $conditionData;
            }

            return $this->json($json);
        }

        return $this->json(['error' => true, 'msg' => 'rule not found']);
    }

    /**
     * save rule config
     *
     * @param Request $request
     * @Route("/save")
     */
    public function saveAction(Request $request)
    {
        // send json response
        $return = array(
            'success' => false,
            'message' => '',
        );

        // save rule config
        try {
            $rule = \CustomerManagementFrameworkBundle\Model\ActionTrigger\Rule::getById((int)$request->get('id'));
            $data = json_decode($request->get('data'));

            // apply basic settings
            $rule->setName($data->settings->name);
            $rule->setDescription($data->settings->description);
            $rule->setActive((bool)$data->settings->active);


            // save trigger
            $arrTrigger = array();
            foreach ($data->trigger as $setting) {
                $setting = json_decode(json_encode($setting), true);
                $trigger = new \CustomerManagementFrameworkBundle\Model\ActionTrigger\TriggerDefinition($setting);
                $arrTrigger[] = $trigger;
            }
            $rule->setTrigger($arrTrigger);


            // create a tree from the flat structure
            $arrCondition = [];
            foreach ($data->conditions as $setting) {
                if (class_exists($setting->implementationClass)) {
                    $condition = call_user_func(
                        [$setting->implementationClass, 'createConditionDefinitionFromEditmode'],
                        $setting
                    );
                } else {
                    throw new \Exception(sprintf("class '%s' does not exist", $setting->implementationClass));
                }
                $arrCondition[] = $condition;
            }


            $rule->setCondition($arrCondition);


            // save action
            $arrActions = array();
            foreach ($data->actions as $setting) {

                if (class_exists($setting->implementationClass)) {
                    $action = call_user_func(
                        [$setting->implementationClass, 'createActionDefinitionFromEditmode'],
                        $setting
                    );
                } else {
                    throw new \Exception(sprintf("class '%s' does not exist", $setting->implementationClass));
                }

                $arrActions[] = $action;
            }

            $rule->setAction($arrActions);


            // save rule
            $rule->save();

            // finish
            $return['success'] = true;
            $return['id'] = $rule->getId();
        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
        }

        // send response
        return $this->json($return);
    }

    /**
     * add new rule
     *
     * @param Request $request
     * @Route("/add")
     */
    public function addAction(Request $request)
    {
        // send json response
        $return = array(
            'success' => false,
            'message' => '',
        );

        // save rule
        try {
            $rule = new \CustomerManagementFrameworkBundle\Model\ActionTrigger\Rule();
            $rule->setName($request->get('name'));
            if ($rule->save()) {

                $return['success'] = true;
                $return['id'] = $rule->getId();
            }

        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
            $return['success'] = false;
        }

        // send response
        return $this->json($return);
    }

    /**
     * delete exiting rule
     *
     * @param Request $request
     * @Route("/delete")
     */
    public function deleteAction(Request $request)
    {
        // send json response
        $return = array(
            'success' => false,
            'message' => '',
        );

        // delete rule
        try {
            $rule = \CustomerManagementFrameworkBundle\Model\ActionTrigger\Rule::getById((int)$request->get('id'));
            $rule->delete();
            $return['success'] = true;
        } catch (\Exception $e) {
            $return['message'] = $e->getMessage();
        }

        // send response
        return $this->json($return);
    }
}