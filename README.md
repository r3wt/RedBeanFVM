# RedBeanFVM

RedbeanFVM makes Filtering, Validating , and Generating RedBean Models easy.

**Features:**
  - 16 built in filter and validation functions.
  - simple api allows users to define custom named filters.
  - chainable filters
  - fully automates the process of creating a redbean Model - simply pass in a the bean and a list of required rules Multi-deminsional Array: `Array(datakey => rules)`, 
  a list of optional rules, and the data source, which defaults to the `$_POST` superglobal. RedBeanFVM searches your data source for the key, performs the filtering rule(s) 
  and stores this into the model. pretty flippin sweet huh?
  - automatically converts datakeys to snake case as required by RedBean.
  - Singleton Interface with dynamic methods. No need to juggle references across business logic or pass it down through functions. create custom filters anywhere, and they will persist via static references.
  simply retrieve a reference to the library at anytime like so `$fvm = \RedBeanFVM\RedBeanFVM::getInstance();`
    
  
### Installation


**Install via Composer:**

1. install with composer:
    ```sh
    composer require redbean-fvm/redbean-fvm
    ```
    
2. add code to project:
    ```php
    require 'vendor/autoload.php';
    $fvm = \RedBeanFVM\RedBeanFVM::getInstance();
    ```

**Download and Manually Install:**

1. download/clone the package:
    ```sh
    git clone https://github.com/r3wt/RedBeanFVM.git
    ```

2. add this snipped of code:
    ```php
    require 'RedBeanFVM/RedBeanFVM.php';
    \RedBeanFVM\RedBeanFVM::registerAutoloader(); // for future use
    $fvm = \RedBeanFVM\RedBeanFVM::getInstance();
    ```

### Examples

1. basic usage:
    ```php
    $bean = R::dispense('user'); // the redbean model

    $required = [
        'Name'=>'name', // post key + rule(s)
        'Email'=>'email',
        'User_Name'=>['rmnl','az_lower'],
        'Password'=>'password_hash',
    ];

    $fvm->generate_model($bean,$required); //the magic

    R::store($bean);
    ```

2. optional parameters:
    ```php
    $bean = R::dispense('user'); // the redbean model

    $required = [
        'Name'=>'name', // post key + rule(s)
        'Email'=>'email',
        'User_Name'=>['rmnl','az_lower'],
        'Password'=>'password_hash',
    ];
    //here we are adding the optional array. these fields are optional, so we raise no exception for missing values.
    $optional = [
        'username'=>'min' //min is the minimum validation/filter
    ];

    $fvm->generate_model($bean,$required,$optional); //the magic

    R::store($bean);
    ```

3. custom data source

    ```php
    $bean = R::dispense('user'); // the redbean model

    $required = [
        'Name'=>'name', // post key + rule(s)
        'Email'=>'email',
        'User_Name'=>['rmnl','az_lower'],
        'Password'=>'password_hash',
    ];
    $optional = [
        'username'=>'min' //min is the minimum validation/filter
    ];

    // here we add a custom data source

    $data = array_merge($_POST,$_GET);

    $fvm->generate_model($bean,$required,$optional,$data); //the magic

    R::store($bean);
    ```

4. manual usage of the methods. 
    ```php
    $unsafeData = 'alfjasldfajsl1000afdasjlkl';
    //we can use RedBeanFVM manually too.   
    $bean->someProperty = $fvm->cast_int($unsafeData);
    ```

5. chainable methods with chain()
    ```php
    $input = $_POST['username'];

    $rules = ['rmnl','az','name'];

    $bean->user_name = $fvm->chain($rules,$input);
    ```

6. custom filters(named closures)
    ```php
    $fvm = \RedBeanFVM\RedBeanFVM::getInstance();
        
    $bean = R::dispense('automobile');

    $required = [
        'make'=>'min',
        'model'=>'min',
        'year'=>'car_year', //this is custom filter
        'vin-number'=>'car_vin_number',// so is this
    ];

    //now we must create the custom filters ...

    //create a custom filter to validate the `year` of the automobile
    $fvm->custom_filter('car_year',function($input){
        if(!preg_match('/^[0-9]{4}$/',$input)){
            throw new \exception('Invalid Year entered');
        }
        return $input;
    });

    //create a custom filter to validate the vin number of the automobile.
    $fvm->custom_filter('car_vin_number',function($input){
        // vin numbers are 17 in length alphanumeric
        if(!preg_match('/^[0-9A-Za-z]{17}$/',$input)){
            throw new \exception('Invalid VIN entered.');
        }
        return strtoupper($input);//we dont really care if they typed lower case. we can fix it for them.
    });

    //now we can use our custom filters for year and vin.
    $required = [
        'make'=>'min',
        'model'=>'min',
        'year'=>'car_year', //this is custom filter
        'vin-number'=>'car_vin_number',// so is this
    ];

    $fvm->generate_model($bean,$required);
    ```

7. advanced custom filters.
 - Some functions like `name` accept optional second parameters. 
 - by design, FVM only accepts 1 argument, the $input to be filtered. 
 - we can work around this like so:
    ```php
    $min_length = 10;
    $max_length = 55;

    $fvm->custom_filter('name_custom',function($input) use($fvm,$min_length,$max_length){
        return $fvm->name($input,$min_length,$max_length);
    });
    ```
    
8. calling custom filter directly on $fvm is possible.

    ```php

    $fvm->custom_filter('foo',function($input){
        return 'foo';
    });

    $input = 'abcdefg';

    $bean->foo = $fvm->foo($input);
    ```
	
9. checkboxes

	```php
	
	$checkboxes = [
		'remember_me'=>1,//the expected value of the input field.
		'email_subscribe'=>1,
	];
	
	$fvm->checkbox($bean,$checkboxes,$_POST);
	```
	
10. loading a custom filter class.
	
	```php
	//latest version of RedbeanFVM introduces the ability to load a custom class of filters. this way you 
	//can write code for your filters in a normal class instead of relying on `RedbeanFVM::custom_filter()`
	//at present time, it is only possible to load a single class, in future support for passing an array of classes is possible,
	//although personally i believe it to be cleaner to use a single class.
	
	//note: namespaces must be escaped as shown
	
	\RedBeanFVM\RedBeanFVM::configure([
		'user_filters'=>'\\App\\Util\\CustomFilters'
	]);

	```
	
11. required and optional can be called directly.

	```
	$required = [...rules];
	$optional = [...rules;
	
	$fvm->required($bean,$required,$_POST);
	$fvm->optional($bean,$optional,$_POST);
	
	```

12. file uploads (coming soonish)

13. all config options and their default values.

	```php
	\RedbeanFVM\RedbeanFVM::configure([
        'password'=>[
            'cost'=>12, //cost of password_hash
            'algo'=>PASSWORD_DEFAULT //password algo. see PHP Manual entry for password_hash() for more info.
        ],
        'locale'=>'\\RedBeanFVM\\Locale\\US', //default locale is this.
        'user_filters'=>'',//load a custom class of filters. filter methods must be public.
		'optional_defaults'=>false //require that optional parameters are provided a default. see example 13 for more info.
    ]);
	```

14. optional defaults explained.

	```php
	//say you want a field to be optional, but it needs a default value if not specified right?
	//FVM will expect that you provide an array of rules, with the last rule being the default value like so
	$optional = [
		'about_me'=>['paragraph','no info provided'],
		'phone_number'=>['us_phone','not provided']
	];
	//as you can see its pretty convenient to type and should be simple to get the hang of. 
	//if users don't like this, please open an issue and we can think of an alternative way to handle it.
	//this option is disabled by default.
	```

### Requirements:

RedBean, obviously `http://www.redbeanphp.com/`

### Development

Want to contribute? Great! Pull Requests welcomed!

### Todo's

 - Write Tests
 - Language Packs (Locale's)
 - Expand Features. 


