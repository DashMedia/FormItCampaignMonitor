<?php
/**
 * @name cmSubscribe
 * @description add subscriber hook
 *
 *
 * Variables
 * ---------
 * @var $modx modX
 * @var $hook formIt hook information
 *
 * @package formitcampaignmonitor
 */
// Your core_path will change depending on whether your code is running on your development environment
// or on a production environment (deployed via a Transport Package).  Make sure you follow the pattern
// outlined here. See https://github.com/craftsmancoding/repoman/wiki/Conventions for more info
$core_path = $modx->getOption('formitcampaignmonitor.core_path', null, MODX_CORE_PATH.'components/formitcampaignmonitor/');
include_once $core_path .'/vendor/autoload.php';

$values = $hook->getValues();

// formFields is set by formalicious
$formFields = $modx->getOption('formFields', $formit->config, false);
$cmSubscribeListId['Text'] = $modx->getOption('cmSubscribeListId', $hook->formit->config, '');

$api_key = $modx->getOption('formitcampaignmonitor.api_key');
$default_list_id = $modx->getOption('formitcampaignmonitor.default_list_id');
$list_id = $modx->getOption('cmSubscribeListId', $hook->formit->config, $default_list_id);

$cmSubscribeName = $modx->getOption('cmSubscribeName', $hook->formit->config, 'name');
$cmSubscribeEmail = $modx->getOption('cmSubscribeEmail', $hook->formit->config, 'email');
$cmSubscribeEmail = trim(htmlspecialchars($cmSubscribeEmail ), '?');
$cmSubscribeEmail = strtolower($cmSubscribeEmail );
$cmSubscribeEmail = preg_replace("/[\s]/", "_", $cmSubscribeEmail );

$cmSubscribeStore = array();
$cmSubscribeStore['Text'] = $modx->getOption('cmSubscribeStoreText', $hook->formit->config, '');
$cmSubscribeStore['Number'] = $modx->getOption('cmSubscribeStoreNumber', $hook->formit->config, '');
$cmSubscribeStore['Date'] = $modx->getOption('cmSubscribeStoreDate', $hook->formit->config, '');
$cmSubscribeStore['Country'] = $modx->getOption('cmSubscribeStoreCountry', $hook->formit->config, '');
$cmSubscribeStore['MultiSelectOne'] = $modx->getOption('cmSubscribeStoreMultiSelectOne', $hook->formit->config, '');
$cmSubscribeStore['MultiSelectMany'] = $modx->getOption('cmSubscribeStoreMultiSelectMany', $hook->formit->config, '');



$userName = $modx->getOption($cmSubscribeName, $values, null);
$userEmailAddress = $modx->getOption($cmSubscribeEmail, $values, null);

// check for email address
if(empty($userEmailAddress)){
  $modx->setPlaceholder('fi.validation_error_message', 'Missing value for email address');
  return false;
}

$cm_api = new CM_API($api_key, $list_id);

foreach($cmSubscribeStore as $fieldType => &$fields){
  $fields = array_map('trim', explode(',', $fields));

  foreach($fields as $fieldName){
    // sanitise field name
    $fieldKey = trim(htmlspecialchars($fieldName), '?');
    $fieldKey = strtolower($fieldKey);
    $fieldKey = preg_replace("/[\s]/", "_", $fieldKey);
    // get value
    $fieldValue = $modx->getOption($fieldKey, $values, null);
    if(!empty($fieldValue)){
      // add to storage for later persistance
      if(is_array($fieldValue)){
        foreach($fieldValue as $nestedValue){
          $cm_api->add_custom_field_value($fieldName, $nestedValue, $fieldType);             
        }
      } else {
        $cm_api->add_custom_field_value($fieldName, $fieldValue, $fieldType);        
      }
    }
  }
}

$result = $cm_api->subscribe($userName, $userEmailAddress);

if(!$result->was_successful()){
  $modx->setPlaceholder('fi.validation_error_message', 'Sorry, there was a problem adding you to our mailing list, please try again');
  return false;
}

return true;