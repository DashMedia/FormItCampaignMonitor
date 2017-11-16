<?php
/**
 * @name cmTransactionalSend
 * @description add subscriber hook
 *
 *
 * Variables
 * ---------
 * @var $modx modX
 * @var $hook formIt hook object
 *
 * @package formitcampaignmonitor
 */
// Your core_path will change depending on whether your code is running on your development environment
// or on a production environment (deployed via a Transport Package).  Make sure you follow the pattern
// outlined here. See https://github.com/craftsmancoding/repoman/wiki/Conventions for more info
$core_path = $modx->getOption('formitcampaignmonitor.core_path', null, MODX_CORE_PATH.'components/formitcampaignmonitor/');
include_once $core_path .'/vendor/autoload.php';

$values = $hook->getValues();

// formFields is also set by formalicious
$formFields = $modx->getOption('formFields', $formit->config, false);

$api_key = $modx->getOption('formitcampaignmonitor.api_key');
$default_smart_email_id = $modx->getOption('formitcampaignmonitor.default_smart_email_id');
$smart_email_id = $modx->getOption('cmSmartEmailId', $hook->formit->config, $default_smart_email_id);

$emailTo = $modx->getOption('emailTo', $hook->formit->config, null, true);
$subject = $modx->getOption('emailSubject', $hook->formit->config, null, true);
$emailToName = $modx->getOption('emailToName', $hook->formit->config, $emailTo, true);
$replyTo = $modx->getOption('emailReplyTo', $hook->formit->config, null);
$replyToName = $modx->getOption('emailReplyToName', $hook->formit->config, null);


if ($formFields) {
    $formFields = explode(',', $formFields);
    foreach($formFields as $k => $v) {
        $formFields[$k] = trim($v);
    }
}

// Build the data array
$dataArray = array();
if($formFields){
    foreach($formFields as $field) {
        $dataArray[$field] = (!isset($values[$field])) ? '' : $values[$field];
    }
}else{
    $dataArray = $values;
}
//Change the fieldnames
if($fieldNames){
    $newDataArray = array();
    $fieldLabels = array();
    $formFieldNames = explode(',', $fieldNames);
    foreach($formFieldNames as $formFieldName){
        list($name, $label) = explode('==', $formFieldName);
        $fieldLabels[trim($name)] = trim($label);
    }
    foreach ($dataArray as $key => $value) {
        if($fieldLabels[$key]){
            $newDataArray[$fieldLabels[$key]] = $value;
        }else{
            $newDataArray[$key] = $value;
        }
    }
    $dataArray = $newDataArray;
}

$data = array(
  'email_subject' => $subject,
  'generated_content' => ''
);
$attachments = array();
foreach ($dataArray as $field => $value) {
  $field = ucfirst(str_replace('_',' ',$field));
  if(is_array($value)){
    if(isset($value['tmp_name'])){
      // file
      switch ($value['error']) {
            case UPLOAD_ERR_OK:
                $message = 'See attached - ' . $value['name'];
                $attachmentEncoded = base64_encode(file_get_contents($value['tmp_name']));

                if(empty($attachmentEncoded)){
                  $message = "ERROR: File uploads must be on the final step of a form";
                } else {
                  $attachments[] = array(
                    'Name' => $value['name'],
                    'Type' =>$value['type'],
                    'Content' => $attachmentEncoded
                  );
                }
                break;
            case UPLOAD_ERR_INI_SIZE:
                $message = "ERROR: The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "ERROR: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "ERROR: The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "ERROR: Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "ERROR: Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "ERROR: File upload stopped by extension";
                break;

            default:
                $message = "Unknown upload error";
                break;
        }
      $data['generated_content'] .= "<h3>{$field}</h3>{$message}";
      $data[$field] = $message;
    } else {
      // radios / checkboxes
      if(count($value) > 0){
        $data['generated_content'] .= "<h3>{$field}</h3>";
        $data[$field] = array();
        foreach ($value as $index => $option) {
          $data['generated_content'] .= "{$option}<br />";
          $data[$field][] = $option;
        }
        $data[$field] = implode(', ',$data[$field]);
      }
    }
  } else {
    if(!empty($value)){
      $data['generated_content'] .= "<h3>{$field}</h3>{$value}<br />";
      $data[$field] = $value;
    }
  }
}

# Authenticate with your API key
$default_smart_email_id = $modx->getOption('formitcampaignmonitor.default_smart_email_id');

$auth = array('api_key' => $api_key);

# The unique identifier for this smart email
// $smart_email_id = $modx->getOption('cmSmartEmailId', $hook->formit->config, $default_smart_email_id);
// $smart_email_id = '8fad4dcb-5308-46b5-bdd1-4f988b9c2d01';

# Create a new mailer
$wrap = new CS_REST_Transactional_SmartEmail($smart_email_id, $auth);

// try to find the enquiries email address so we can set the reply to field
$lcaseFields = array_change_key_case($dataArray, CASE_LOWER);

// deine our message
$message = array(
    "To" => $emailToName . '<' . $emailTo . '>',
    "Data" => $data
);

if(!empty($attachments)){
  $message['Attachments'] = $attachments;
}

if(!empty($replyTo)){
  $message['Data']['replyToEmail'] = $replyTo;
} elseif (!empty($dataArray['Email'])) {
  $message['Data']['replyToEmail'] = $message['Data']['Email'];
}
if(!empty($replyToName)){
  $message['Data']['replyToName'] = $replyToName;  
}

# Send the message and save the response
$result = $wrap->send($message);

if(!is_array($result->response)){
  $modx->setPlaceholder('fi.validation_error_message', 'Sorry, there was a problem sending your enquiry: '.$result->response->Message);
  return false;
}
if($result->response[0]->Status != 'Accepted'){
  $modx->setPlaceholder('fi.validation_error_message', 'Sorry, there was a problem sending your enquiry: '.$result->response);
  return false;
}

return true;