Client Side

The $.fn.ajaxForm class is an extension of the jquery object and can be called upon any html form element. AjaxForm does not require any special modifications to be made to the html form markup but does require that the corresponding “action” script for the form respond with a properly formatted JSON reply.

Ex. (basic implementation)

1.<script type='text/javascript'> 
2.        $(document).ready(function(){ 
3.                var options = { 
4.                        onFieldInvalid:{ 
5.                                fieldname:function(){} 
6.                        }, 
7.                        onFieldValid:{}, 
8.                        onSuccess:function(){}, 
9.                        onFail:function(){} 
10.                }  
11.                $(“form#someForm”).ajaxForm(options); 
12.        }); 
13.</script> 
The jquery class allows us to extend it's functionality through the use of the options object. Objects with the onField* naming convention allow us to add callbacks to certain inputs by name. The onSuccess and onFail callbacks are called based on the servers response to the script as opposed the http status of the request. Currently this extension is declared in scripts/script.js file but will eventually be moved somewhere into scripts/libs/ajaxForm.js

 

JSON response
Ex. (response object)

1.{ 
2.        "errors":[ 
3.                {"field":"email","message":"this is not a valid email address"},         
4.                {"field":"password","message":"Password field cannot be left blank"} 
5.        ], 
6.        "message":"Some of the entries below could not be validated. Please review the highlighted fields and correct the values.", 
7.        "status":"ERROR" 
8.} 
In the example above you can see that the errors array is a collection of objects containing information regarding the field that has given invalid data and information about the error itself. The the message string is the error that the user will be seeing as their notification. The status string lets the client script know that there was an error processing the request, which is not to be confused with a http status.

In the event that form needs to be redirected the JSON object returned will contain an additional object named redirect. The redirect object will contain a url string and a delay. The url string will tell the script where to send the page to, and the delay indicates how many milliseconds to wait before sending the client to that location.

When a form has been properly processed the server side script should set a status of “OK” in order for the client script to execute any custom success callbacks.

 

Styling
The ajaxForm js class will apply classes to it's message box and input fields depending on the servers response. This allows us to style the message box and the error reporting on the inputs without having to change our code. If you would like to change the styling for the input fields that have generated an error, you will be editing the .errorHighlight css class. The message box at the head of the page will always have a base class of .info but will have the following classes appended to it .error, .message, .warning, and .success. Please keep css neatly structured in either a external stylesheet in the styles direcory or within style tags on the generated page.

 

Server Side
In order to facilitate the use of the ajaxForm client side script there is a ajaxForm class written in php that lives in libs/ajaxForm.inc.php and should also be included in the common.inc.php as well. This class should contain all the required methods to validate inputs easily and to create a properly formatted response for the client side script to receive.

Ex. (Instanciation and basic usage)

1.<?php 
2.                $form = new ajaxForm(“POST”); 
3.                $form->setValidation(“firstName”, “fistname is not valid”, function($v){return (strlen($v)>3);}); 
4.                $form->validateValues(“Your name is not longer than three characters”); 
5.                 
6.                header(“Content-Type: application/json”);         
7.                echo $form->getResponse(); 
8.        ?> 
The example above should check a forms input firstName and check if it's string value is greater than 3 characters long.

 

 

 

 

Methods contained

 

        __construct([mixed $arg])

 The constructor allows us to use data in different ways. We can use the strings “POST, GET, ALL/REQUEST” to use data from those form methods, or we can feed it an array containing the data that we would like to set. This is basically a smart implementation of the useData* methods inside of the class. The default will fetch data from the request array and equivalent to using useDataAll.

 

 Void useDataGet(void)

 void useDataPost(void)

 void useDataAll(void)

 void useDataCustom(array $arg)

 These methods are used to set what kind of data to use within the class. This will overwrite any existing data being currently used by the instance when called. If you would like to merge or modify data you must use the mergeData* or setData methods.

 

 

 Void mergeDataGet(void)

 void mergeDataPost(void)

 void mergeDataAll(void)

 void mergeDataCustom(array $arg)

 These methods are similar to the useData* with the exception that this will merge new data into the old data instead of completely overwriting the array we are currently using.

 

 Void setData(string $index, mixed $value)

 This method will set a given value within our data to a new value.

 

 Mixed getData(string $index)

 Getter for the data that is being used by our class

 

 void clearData(void)

 This method will unset any data that is being used for verification

 

 void setMetaData(string $index, mixed $value)

 This method will return extra information to the calling script. This will be returned within the response as an object when there is data contained within it. If there is no data contained it will not be set in the response. This feature was implemented with the intent of being used within the client side scripts callback functions, but has turned out to be more useful as a debugging feature.

 

 void mergeMetaData(array $value)

 Merges an array into the meta data array

 void setValidation(string $index, [string $message, function [bool $callback($value)]])

 This method sets a certain index to be validated where the $index is usually the fieldname passed though $_POST or $_GET.  $message is an optional value that will be returned as the reason that the value did not pass validation. $callback is an anonymous function (or closure) that will be passed the value to be validated, execute a validation method, and should return true or false. If the $callback is left blank it will auto assume that the validation method should check for emptiness only.

 

 Void setRequired(string $index, [string $message])

 This is an alias of set validation.

 

 Void setRequiredMulti(string $index[[, string $index][, string $index][, string $index]])

 takes an unspecified number of string indices as arguments and uses the setRequired method on each of them. Unlike setRequired or setValidation this method does not allow you to set a custom error message.

 

 void validateValues([string $customError])

 This is where all the validation magic happens. This method must be explicitly called in order to trigger the validation methods set with setRequired* or setValidation. If an input does not pass validation then the overall status of the form will be set to “ERROR” and the custom error message will be set using the setStatus method internally.

 

 Void setStatus(string $status = “ERROR” | “OK”,[string $errorMessage])

 This method will allow you to set the status of the form arbitrarily. Currently this only supports two statuses which are “ERROR” and “OK”.

 

 String getStatus(void)

 This method returns what the current status of the response is.

 

 Bool isStatus(string $status)

 This method takes an unspecified number of statuses will return whether the current status is equivalent to the any of the statuses provided. When given more than one parameter this functions as an OR comparison operator.

 

 Void setError(string $index, [string $message])

 Arbitrarily set an error on a given field. This method is used by validateValues to set an error on a field but can be used publicly to set errors based on externally factored criteria if needed.

 

 Int errorCount(void)

 This method will return a integer count of the errors that have been logged in the error object.

 

 void clearErrors(void)

 this method will clear all objects within the error array.

 

 Void setRedirect(string $url, [int $delay = 0])

 This method will set the redirect object within the response object. $url is the location that the client side script will redirect to after the given $delay in milliseconds. This redirect is not the same as creating a header() redirect but it will achieve the same effect through a different mechanism.

 Void clearRedirect(void)

 This method will unset the redirect object and the requesting script will no longer redirect the page upon receiving the response.

 

 mixed getResponse([string $type = “JSON|ARRAY|QUERY”])

 This method will assemble all the needed parts of the response object and set the basic structure it will then return the object as the specified type. The default return type will be a JSON encoded string but you may request the response as an array or a serialized query string. Please note that the JSON format is expected to be used by being echoed to the screen when being used in conjunction with the ajaxForm jquery class. When using ajaxForm on a non ajax form it may still be useful to use the response as a query so that the php redirect may pass data along after the #(hash) following a !(bang) in the url parameter, there is already a custom jquery function by the name of $.fn.hashAlerts that will respond to this format as well.

 
