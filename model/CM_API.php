<?php

class CM_API{
	private $name;
	private $email_address;
	private $api_key;
	private $list_id;
	private $subscription_option;
	private $custom_field_name;
	private $typesWithOptions;
	// private $custom_field_id;
	private $custom_field_obj;
	private $custom_fields;
	private $subscribers_connection;
	private $lists_connection;


	public function __construct($api_key, $list_id)
	{
		$this->$api_key = $api_key;
		$this->$list_id = $list_id;
		$this->typesWithOptions = array(
			'MultiSelectOne',
			'MultiSelectMany'
			);
		//create subscribers_connection
		$this->subscribers_connection = new CS_REST_Subscribers(
				$list_id, array('api_key'=>$api_key)
			);

		$this->lists_connection = new CS_REST_Lists(
				$list_id, array('api_key'=>$api_key)
			);

	}

	public function subscribe($name, $email_address)
	{
		$this->name = $name;
		$this->email_address = $email_address;
		// $this->subscription_option = $subscription_option;
		// $this->custom_field_name = $subscription_field;

		$options = array(
			'Name' => $this->name,
			'EmailAddress' => $this->email_address,
			'Resubscribe' => true
		);

		$user_details = $this->get_subscriber();

		$current_custom_field_values = null;
		if(!is_null($user_details))
		{
			// we have an existing user, grab their custom fields
			$current_custom_field_values = $user_details->CustomFields;
		}

		if($this->process_custom_fields($current_custom_field_values)){
			//returns true if there are custom fields associated with our subscriber
			$options['CustomFields'] = $this->custom_fields;
		}

		$api_result = $this->subscribers_connection->add($options);
		if(!$api_result->was_successful()){
			throw new Exception('Unable to add subscriber: '.$api_result->report_error);
    }
    return $api_result;
	}


	public function process_custom_fields(&$current_values = null)
	{
		$existing_custom_fields = $this->lists_connection->get_custom_fields()->response;

		if(is_null($current_values) && is_null($this->custom_fields)){
			//no custom field values to deal with
			return false;
		}
		foreach((array) $this->custom_fields as $key => &$value){
			// iterate through fields, making sure they exist, and that the value is a viable option.
			foreach ($existing_custom_fields as $fieldId => $field) {
				if($value['FieldName'] === $field->FieldName){
					$value['Key'] = $field->Key;
					if(isset($field->FieldOptions)){
						$value['Options'] = $field->FieldOptions;
						$value['FieldOptions'] = &$value['Options'];
						$value['DataType'] = $field->DataType;
					}
					break;
				}
			}
			if(!isset($value['Key'])){ //not an existing custom field, create it
				if(in_array($value['DataType'], $this->typesWithOptions)){//field type has pre-defined options
					$value['Options'] = array($value['Value']);
				}
				$api_result = $this->lists_connection->create_custom_field($value);
				if($api_result->was_successful()){
					$value['Key'] = $api_result->response;
				} else {
					throw new Exception("Error adding new custom field: ".$api_result->response);
				}
			}

			if(in_array($value['DataType'], $this->typesWithOptions) && !in_array($value['Value'],$value['Options'])){//availible options doesn't include our new value, add it

				$api_result = $this->lists_connection->update_field_options(
					$value['Key'],
					array($value['Value']),
					true //keep existing options
					);

				if(!$api_result->was_successful()){
					throw new Exception("Error adding new option to custom field: ".$api_result->response);
				}

				$value['Options'][] = $value['Value'];
			}
			// update the current values array with the new values
			if($value['DataType'] != CS_REST_CUSTOM_FIELD_TYPE_MULTI_SELECTMANY){ //check if field already has a value, and replace it
				$found = false;
				if(is_array($current_values)){
					foreach ($current_values as &$current_value) {
						if($current_value->Key == $value['FieldName']){
							$found = true;
							$current_value->Value = $value['Value'];
						}
					}
				}

				if(!$found){ //field doesn't currently have a value
					$current_values[] = (object) array(
						'Key'=>$value['FieldName'],
						'Value'=>$value['Value']
						);
				}
			} else { //multiselect, add the new value as an additional value
				$current_values[] = (object) array(
					'Key'=>$value['FieldName'],
					'Value'=>$value['Value']
					);
			}
		}
		$this->custom_fields = $current_values;
		return true;
	}
	/*
	*
	*	Valid $type input values
	*	CS_REST_CUSTOM_FIELD_TYPE_TEXT = Text
	* 	CS_REST_CUSTOM_FIELD_TYPE_NUMBER = Number
	*  	CS_REST_CUSTOM_FIELD_TYPE_MULTI_SELECTONE = MultiSelectOne
	*   CS_REST_CUSTOM_FIELD_TYPE_MULTI_SELECTMANY = MultiSelectMany
	*   CS_REST_CUSTOM_FIELD_TYPE_DATE = Date
	*   CS_REST_CUSTOM_FIELD_TYPE_COUNTRY = Country
	*
	*/
	public function add_custom_field_value($key, $value, $type = CS_REST_CUSTOM_FIELD_TYPE_TEXT, $add = false)
	{
		$this->custom_fields[] = array('FieldName' => $key, 'Value' => $value, 'DataType' => $type);
	}

	public function get_custom_fields()
	{
		return $this->custom_fields;
	}

	private function get_subscriber()
	{
		$result = $this->subscribers_connection->get($this->email_address);
		if($result->was_successful()){
			return $result->response;
		} else {
			return null;
		}
	}
}
