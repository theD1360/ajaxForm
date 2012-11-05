<?php 

/*********************************************************************************


	Author: Diego O. Alejos
	Date: May 05, 2012
	Description: 
		This class is used to create a properly formatted response that 
		will interact with the ajaxFrom jQuery plugin. This class provides
		basic validation methods and can be used with or without ajaxForm.
		The hashAlerts jQuery plugin is also designed around responses 
		provided by this class. 
	Contributers:
		
	
	Change Log:
		05/15/2012: Added response format options to getResponse.
		05/16/2012: Comment header added.
		
		
	Example Usage:
	
		$form = new ajaxForm(“POST”);
		$form->setValidation(“firstName”, “fistname is not valid”, function($v){return (strlen($v)>3);});
	    $form->setRedirect("succsess.html", 1000);

        // Run all the validations
		$form->validateValues(“Your name must be longer than three characters”);
	
		header(“Content-Type: application/json”);	
		echo $form->getResponse();
		


**********************************************************************************/
error_reporting(E_ALL);
ini_set("display_errors", "On");

	class ajaxForm{

		private $data,
		        $errors,
		        $status,
		        $redirect,
		        $message,
		        $response,
		        $validate, 
		        $metadata,
			    $filter_map;
		
		function __construct(){
			$args = func_get_args();
			$argc = func_num_args();
			
			//instanciate the errors and redirect arrays
			$this->errors = array();
			$this->redirect = array();
			$this->validate = array();

			//instanciate the data array that is used to carry all form data
			$this->data = array();
			//instanciate metadata
			$this->metadata = array();

			//instanciate the message and status
			$this->setMessage("Processing your data please wait...");
			$this->setStatus("OK");
			
			$this->filter_map = array(
		        "bool"=>FILTER_VALIDATE_BOOLEAN,
		        "email"=>FILTER_VALIDATE_EMAIL,
		        "float"=>FILTER_VALIDATE_FLOAT,
		        "int"=>FILTER_VALIDATE_INT,
		        "ip"=>FILTER_VALIDATE_IP,
		        "url"=>FILTER_VALIDATE_URL,
		        "bool"=>FILTER_VALIDATE_BOOLEAN
		    );
			
			# if no arguments given use all data from $_REQUEST
			if( $argc == 0 ){
				$this->useDataAll();
			}else{
			# if arguments were passed to the function check if it's an array or string
			# if the argument passed is an array merge it using the custom function
			# if the argument is a string check if post, get, or request. If no match default to request
				while($arg = array_shift($args)){
					if(!is_array($arg)){
						switch(strtoupper($arg)){
							case "POST":
								$this->useDataPost();
							break;
							case "GET":
								$this->useDataGet();
							break;
							case "REQUEST":
								default:
								$this->useDataAll();
							
						}
					}else{
						$this->useDataCustom($arg);
					}
				}								
			}	
			
			# This will implicitly retrieve data attribute validation
			$this->setValidationFromDataAttr();

		}
		
		# The following functions will load data into the object 	
		public function useDataPost(){
			$this->data = $_POST;			
		}

		public function useDataGet(){
			$this->data = $_GET;			
		}

		public function useDataAll(){
			$this->data = $_REQUEST;			
		}
		# This is useful if I want to add session data to the object
		public function useDataCustom($array){
			$this->data = $array;
		}

		public function mergeData($data){
			//alias function form merging data
			// accepts an array or strings "POST", "GET", "ALL", "REQUEST"
			if(is_array($data)){
				return $this->mergeDataCustom($data);
			}			
			switch(strtolower($data)){
				case "post":
					return $this->mergeDataPost();
				break;
				case "get":
					return $this->mergeDataGet();
				break;
				case "all":
				case "request":
				default:
					return $this->mergeDataAll();
			}

		}

		# The following functions will merge data into the 	

		public function mergeDataPost(){
			$this->data = array_merge($this->data,$_POST);			
		}

		public function mergeDataGet(){
			$this->data = array_merge($this->data,$_GET);
		}

		public function mergeDataAll(){
			$this->data = array_merge($this->data,$_REQUEST);
		}
		# This is useful if I want to add session data to the object
		public function mergeDataCustom($array){
			
			$this->data = array_merge($this->data, $array);
			//var_dump($this->data);
		}

		# Validation and modification methods

		# Sets multiple required fields all at once but will doesn't allow for specific errors
		public function setRequiredMulti(){
			$args = func_get_args();
			$argc = func_num_args();
			if(empty($argc))
				return false;
			
			foreach($args as $arg){
				if(is_string($arg))
					$this->setValidation($arg);
			}

		}
		# Partial alias for setValidation no callback included
		public function setRequired($field, $raisin = ""){
			$this->setValidation($field, $raisin);
		}


		# Partial alias for setValidation callback is a regex 
		public function setRegex($field, $regex, $raisin = ""){
		    
			$this->setValidation($field, $raisin, function($v, $r){ return preg_match($r, $v); }, array($regex));
		
		}

		# Partial alias for setValidation callback is a comparison 
		public function setComparison($field, $operator, $value , $raisin = ""){
		        

			$this->setValidation($field, $raisin, function($expr1, $operator, $expr2){ 

		        switch(strtolower($operator)) { 
                  case '==': 
                     return $expr1 == $expr2; 
                  case '>=': 
                     return $expr1 >= $expr2; 
                  case '<=': 
                     return $expr1 <= $expr2; 
                  case '!=': 
                     return $expr1 != $expr2; 
                  case '&&': 
                  case 'and': 
                     return $expr1 && $expr2; 
                  case '||': 
                  case 'or': 
                     return $expr1 || $expr2; 
                  default: 
                     throw new Exception("Invalid operator '$operator'"); 
               } 

			 }, array($operator, $value));
		
		}

		# Partial alias for setValidation callback is a filters 
		public function setFilter($field, $filter, $raisin = ""){


            $filter = $this->filter_map[strtolower($filter)];
            
		    if(empty($filter))
		        throw new Exception("Invalid filter was set for setFilter Method valid filters are (".implode(" | ", array_keys($filter_map)).")");
		    
			$this->setValidation($field, $raisin, function($v, $f){ return filter_var($v, $f);}, array($filter));
		
		}

		# Another futurama reference. (I really do know how to spell)
		public function setValidation($field, $raisin = "", $callback = "", $map = array()){
			$randKey = uniqid();
			$this->validate[$randKey]['field'] = $field;			
			$this->validate[$randKey]['reason'] = (!empty($raisin))?$raisin:"$field could not be properly validated";
			$this->validate[$randKey]['callback'] = (is_callable($callback))?$callback:function($v){return !empty($v);};
			$this->validate[$randKey]['map'] = $map;
			return $randKey;
		}

        private function setValidationFromDataAttr(){
            foreach($this->data as $field=>$value){
                # Check for data attr verification properties
                if(strstr($field, "ajaxForm-verify-")){
                    $field = trim(strtolower(str_replace("ajaxForm-verify-", "", $field)));
                    
                        
                    $message = "";
                    # check if we have any custom messages for this validation
                    if(($pos = strpos($field, "ajaxForm-message-")))
                        $message = str_replace("ajaxForm-message-", "", $field);
                
                    if($value == "required"){
                    
                        $this->setRequired($field, $message);
                    
                    }else if(!empty($this->filter_map[$value])){
                    
                        $this->setFilter($field, $value, $message);
                    
                    }else if(0 == strpos($value, "/")){
                    
                         $this->setRegex($field, $value, $message);
                    
                    }else{
                        # Try and to a match comparison                    
                        $this->setComparison($field, "==" , $value , $message);

                    }

                }

            }
        }

		# Iterate over the requred fields and check if they are populated in the data array
		# Accept an alternative error message to display here
		public function validateValues($errorMessage=null, $successMessage = null){

            $this->validate($errorMessage=null, $successMessage = null);
		    
		}

        # Changing validateValues to validate 
        public function validate($errorMessage=null, $successMessage = null){

			$errorMessage = (!empty($errorMessage))?$errorMessage:"Some of the entries below could not be validated. Please review the highlighted fields and correct the values.";
			$successMessage = (!empty($successMessage))?$successMessage: "Form successfully validated...";

			foreach($this->validate as $v){
			    $params = $v['map'];
			    array_unshift(  $params , $this->data[$v['field']] );
				if( !call_user_func_array( $v['callback'], $params ) )
					$this->setError( $v['field'] , $v['reason'] );
			}
			# if there are errors set the message
			if($this->errorCount())
				$this->setMessage($errorMessage);
		    else
		        $this->setMessage($successMessage);
        
        }


		# simple clearing functions *not sure if they will be used yet*
		public function clearData(){
			unset($this->data);
			$this->data = array();
		}

		public function clearErrors(){
			unset($this->errors);
			$this->errors = array();
		}

		public function clearRedirect(){
			unset($this->redirect);
			$this->redirect = array();
		}

		# Modify data object array function
		public function setData($dataKey, $newValue){
			$this->data[$dataKey] = $newValue;
		}

		# meta data is whatever data that needs to be handed back to the script in whatever format 
		public function setMetaData($dataKey, $newValue = null){
			if($newValue != null)
				$this->metadata[$dataKey] = $newValue;
			if(is_array($dataKey))
				$this->mergeMetaData($dataKey);
			if(empty($dataKey))
				return false;
		}
		public function mergeMetaData($obj){
			$this->metadata = array_merge($this->metadata, $obj);
		}

		public function getData($dataKey = NULL){
			if($dataKey!=NULL)
				return $this->data[$dataKey];
			else
				return $this->data;
		}


		# futurama joke in here
		public function setError($offender, $raisins){
			$this->setStatus("ERROR");
			$this->errors[] = array("field"=>$offender, "message"=>$raisins);
		}

		public function setStatus($status = "OK", $message=NULL){
			$status = strtoupper($status);
			if($status!="OK"){
				$status = "ERROR";
			}

			if($message!=NULL){
				$this->setMessage($message);
			}
			
			return ($this->status = $status);
		}

		public function getStatus(){
			return $this->status;
		}

		// This is status takes as many arguments as needed to comare against
		// This is for when status will have more types
		public function isStatus(){
			$status = func_get_args();
			return in_array($this->status, $status);
		}

		# sets the redirect
		public function setRedirect($location, $delay=0){
			$this->redirect =  array("url"=>$location, "delay"=>$delay);
		}
		
		public function getRedirect(){
			return $this->redirect;
		}	
		
		public function setMessage($message){
			$this->message = $message;
		}


		public function errorCount(){
			return count($this->errors);
		}

		private function prepareResponse(){
			if($this->errorCount()>0 || $this->status != "OK"){
				$this->setStatus("ERROR");
				$this->response['errors'] = $this->errors;
			}

			if(!empty($this->redirect)){
				$this->response['redirect'] =$this->redirect;
			}

			if(!empty($this->metadata)){
				$this->response['metadata'] =$this->metadata;
			}


			$this->response['message'] = $this->message;
			$this->response['status'] = $this->status;
		}

		public function getResponse($type = "JSON"){
			$this->prepareResponse();

			switch(strtolower($type)){
				case "query":
					$tmp = http_build_query($this->response);
				break;
				case "array":
					$tmp = $this->response;
				break;
				case "json":
					default:
					$tmp = json_encode($this->response);
				break;
			}
			
			return $tmp;
			
		}

	}


?>
