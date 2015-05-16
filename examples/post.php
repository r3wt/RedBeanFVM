<?php

require 'redbean/rb.php';

require 'RedBeanFVM.php';

if(!empty($_POST)){
    
    R::configure(); //configure redbean
    
    $fvm = \RedBeanFVM\RedBeanFVM::getInstance();
    
    $bean = R::dispense('user');
    
    // post key + rule(s)
    $required = [
        'Name'=>'name',
        'Email'=>'email',
        'User_Name'=>['rmnl','az_lower'],
        'Password'=>'password_hash',
    ];
    
    $fvm->generate_model($bean,$required); //boom! now your bean is ready for storage.
    
    R::store($bean);
    
}