<?php 
    include("../lib/ajaxForm.inc.php");
    

    
    $form = new ajaxForm("POST");
    
    $data = $form->getData();
    
    $form->setMetaData("Return", $data);

    
    $form->validateValues();
    
    echo $form->getResponse();

?>
