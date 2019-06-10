<?php
/*-----------------------------------------------------------------
 * Lexicon keys for System Settings follows this format:
 * Name: setting_ + $key
 * Description: setting_ + $key + _desc
 -----------------------------------------------------------------*/
return array(

    array(
        'key'       =>     'formitcampaignmonitor.api_key',
        'value'         =>     '',
        'xtype'         =>     'textfield',
        'namespace' => 'formitcampaignmonitor',
        'area'      => 'formitcampaignmonitor:default'
    ),
    array(
      'key'       =>     'formitcampaignmonitor.default_list_id',
      'value'         =>     '',
      'xtype'         =>     'textfield',
      'namespace' => 'formitcampaignmonitor',
      'area'      => 'formitcampaignmonitor:default'
    ),
    array(
      'key'       =>     'formitcampaignmonitor.default_smart_email_id',
      'value'         =>     '',
      'xtype'         =>     'textfield',
      'namespace' => 'formitcampaignmonitor',
      'area'      => 'formitcampaignmonitor:default'
    ),
    array(
      'key'       =>     'formitcampaignmonitor.max_upload',
      'value'         =>     '5242880',
      'xtype'         =>     'textfield',
      'namespace' => 'formitcampaignmonitor',
      'area'      => 'formitcampaignmonitor:default'
  )
);
/*EOF*/
