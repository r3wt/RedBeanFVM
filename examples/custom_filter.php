<?php

//example illustrates how to implement a custom validation function, such as one that validates a hash

require 'redbean/rb.php';

require 'RedBeanFVM.php';

if(!empty($_POST)){
    
    R::configure(); //configure redbean
    
    $fvm = \RedBeanFVM\RedBeanFVM::getInstance();
    
    $bean = R::dispense('automobile');
    
    
    $required = [
        'make'=>'min',
        'model'=>'min',
        'year'=>'car_year', //this is custom filter
        'vin-number'=>'car_vin_number',// so is this
    ];
    
    //now we must create the custom filters ...
    
    $fvm->custom_filter('car_year',function($input){
        if(!preg_match('/^[0-9]{4}$/',$input)){
            throw new \exception('Invalid Year entered');
        }
        return $input;
    });
    
    $fvm->custom_filter('car_vin_number',function($input){
        // vin numbers are 17 in length alphanumeric
        if(!preg_match('/^[0-9A-Za-z]{17}$/',$input)){
            throw new \exception('Invalid VIN entered.');
        }
        return strtoupper($input);//we dont really care if they typed lower case. we can fix it for them.
    });
    
    $fvm->generate_model($bean,$required); //boom! now your bean is ready for storage.
    
    R::store($bean);
    
}