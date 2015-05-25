<?php

/**
 * RedBeanFVM - Automatically Filter Validate and Create RedBean Models programmatically.
 *
 * @author      Garrett R. Morris <stuckin@box@live.com>
 * @copyright   2015 Garrett R. Morris
 * @link        https://github.com/r3wt/RedBeanFVM
 * @license     http://www.opensource.org/licenses/mit-license.php
 * @version     0
 * @package     redbean-fvm
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace RedBeanFVM;

class RedBeanFVM
{
    private static $instance = null;
    
    private static $config = [
        'raise'=>true,//whether to raise exception or not for missing value in required array.
        'password'=>[
            'cost'=>12, //cost of password_hash
            'algo'=>PASSWORD_DEFAULT //password algo. see PHP Manual entry for password_hash() for more info.
        ],
		'locale'=>'US'
    ]; 
    
    private static $locale_filters = null; //load a locale filter set.
    private static $custom_filters = [];   //allow users to register custom filters. 
    
    /**
     * Protected Ctor
     */
    protected function __construct(){
		$c = self::$config;
		$locale = '\\RedBeanFVM\\Locale\\'.$c['locale'];
		self::$locale_filters = new $locale();
	}
	
	/**
     * configure multiple configuration settings
     * @param Array $c
     */
    public static function configure($c)
    {
        if(!is_array($c)){
            throw new \exception('RedBeanFVM :: configureAll() expects an array! `'.gettype($c).'` given.';
        }
        foreach($c as $k => $v){
            if(isset(self::$config[$k])){
				self::$config[$k] = $v;
			}else{
				throw new \exception('RedBeanFVM :: configure() `'.$k.'` is not a valid configuration option.');
			}
        }
		//if settings changed on an instantiated instance, we must reinstantiate.
		if(self::$instance !== null){ 
			self::destroy(); 
			self::getInstance();
		}
    }
	
	/**
     * Destroy the Singleton instance of RedBeanFVM
     * @return void
     */
	private static function destroyInstance()
	{
		self::$instance === null;
	}
    
    /**
     * Retrieve the Singleton instance of RedBeanFVM
     * @return RedBeanFVM
     */
    public static function getInstance()
    {
        return (is_null(self::$instance) ? self::$instance = new self : self::$instance);
    }
    
    /**
     * This magic method searches the list of user defined filters for a match. if none is found, an exception is raised.
     * @param callable $function
     * @param mixed $args
     * @return mixed 
     */
    public function __call($function,$args = false)
    {
        if($this->custom_filter_exists($function)){
            return $this->custom_filter_exec($function,$args);
        }
        throw new \exception('RedbeanFVM ::  Method `'.$function.'` doesn\'t exist!');
    }
    
    /**
     * Autoloader
     * @param $class
     * @return void
     */
    public static function autoload($class)
    {
        $file =  __DIR__ . str_replace('\\','/', preg_replace('/'. __NAMESPACE__ .'/','',$class,1)) . '.php';
        if(file_exists($file)){
            include $file;
        }
    }
    
    /**
     * Register the autoloader for people who arent using composer.
     * @return void
     */
    public static function registerAutoloader()
    {
        spl_autoload_register('\\RedBeanFVM\\RedBeanFVM::autoload');
    }
    
    /**
     * Convert a key to snake casing before setting it to the bean.
     * @param string $property the key.
     * @return string
     */
    private function snake_case($key)
    {
        return strtolower(trim(preg_replace("/(_)\\1+/", "$1",preg_replace('/([^a-zA-Z_])/','_',$key)),'_'));
    }
    
    /**
     * Generate a RedBean Model
     * @param  RedBean_SimpleModel &$bean An instance of RedBean_SimpleModel
     * @param  Array $required A list of required keys and their rules (key=>rule) OR (key=>[rule1,rule2, etc.])
     * @param  Array $optional A list of optional keys. Exceptions are not thrown for optional keys. default empty array.
     * @param  Array $source An array of data where to look for the keys. Default is post
     * @return void
     */
    public function generate_model( &$bean, $required, $optional = [], $source = $_POST)
    {
        foreach($required as $k => $v){
            if(!isset($source[$k])){
                throw new \exception('Missing form value: '.ucFirst($k));
            }
            if(is_array($v)){
                $bean->{ $this->snake_case($k) } = $this->chain($v,$source[$k]);
            }else{
                $bean->{ $this->snake_case($k) } = $this->{$v}($source[$k]);
            }
        }
        foreach($optional as $k => $v){
            if(isset($source[$k])){
                if(!empty($source[$k])){
                    if(is_array($v)){
                        $bean->{ $this->snake_case($k) } = $this->chain($v,$source[$k]);
                    }else{
                        $bean->{ $this->snake_case($k) } = $this->{$v}($source[$k]);
                    }
                }
            }
        }
    }
    
    /**
     * checks $custom_filters for the presence of the named callable
     * @param string $function the named callable
     * @return bool
     */
    private function custom_filter_exists($function)
    {
        return isset(self::$custom_filters[$function]);
    }
	
    /**
     * executes the custom filtering functions and returns the output
     * @param string $function the named callable
     * @param mixed $input the data to be filtered by the function.
     * @return mixed
     */
    private function custom_filter_exec($function,$input)
    {
        $method = self::$custom_filters[$function];
        return call_user_func_array($method,$input);
    }
	
	/**
     * checks $locale_filters for the presence of the named callable
     * @param string $function the named callable
     * @return bool
     */
    private function locale_filter_exists($function)
    {
        return method_exists(self::$locale_filters, $function);
    }
	
    /**
     * executes the custom filtering functions and returns the output
     * @param string $function the named callable
     * @param mixed $input the data to be filtered by the function.
     * @return mixed
     */
    private function locale_filter_exec($function,$input)
    {
        return self::$localefilters->{$function}($input);
    }
    
    /**
     * creates a named callable and adds it to $custom_filters array. useful for creating custom filters.
     * @param string $name the name to assign to the callable.
     * @param closure $callable the callback function.
     */
    public function custom_filter($name,$callable)
    {
        if(empty($name)){
            throw new \exception('RedBeanFVM :: custom_filter() An Invalid Name was declared.');
        }
        if(method_exists($this,$name)){
            throw new \exception('RedBeanFVM :: custom_filter() `'.$name.'()` is a built in method of RedBeanFVM and a custom filter of that name may not be declared.');
        }
        if(!is_callable($callable)){
            throw new \exception('RedBeanFVM :: custom_filter() Method `'.$name.'` isn\'t a valid callable!');
        }
        $info = new \ReflectionFunction($callable);
        if( $info->getNumberOfParameters() !== 1 || $info->getNumberOfRequiredParameters() !== 1 ){
            throw new \exception('RedbeanFVM :: custom_filter() Method`'.$name.'` declares an invalid number of arguments! only one argument is allowed!' );
        }
        self::$custom_filters[$name] = $callable;
    }
    
    /**
     * executes an array of filters on an input and returns the output.
     * @param Array $functions
     * @param mixed $input
     * @return string
     */
    public function chain($functions,$input)
    {
        foreach($functions as $callable){
            $input = $this->{$callable}($input);
        }
        return $input;
    }
    
    
    // only a-z
    public function az($input)
    {
        return preg_replace( '/[^a-zA-Z]/','', trim($input) );
    }
    
    // self explanatory
    public function az_upper($input)
    {
        return strtoupper($this->az($input));
    }
    
    // self explanatory
    public function az_lower($input)
    {
        return strtolower($this->az($input));
    }
    
    //remove all but typically allowed charachters in a business entity name, Eg: #1 Plumbing-Contractors & Associates, Ltd.
    public function business_name($input)
    {
        return preg_replace( '/[^A-Za-z\,\.\-\&\# ]+/','', trim($input) );
    }
    
    //cast to int
    public function cast_int($input)
    {
        return ((int) $input);
    }
    
    //validate an email
    public function email($input)
    {
        if(!filter_var($input,FILTER_VALIDATE_EMAIL)){
            throw new \exception('invalid email address');
        }
        return $input;
    }
    
    //the minimalist filter
    public function min($input)
    {
        return stripslashes(strip_tags(trim($input)));
    }
    
    //remove all non word charachters with safeguard. 
    public function name($input, $min = 2,$max = 30)
    {
        $input = preg_replace( '/[^ \w]+/','', trim($input) );
        if(!preg_match('/^[a-zA-Z ]{'.$min.','.$max.'}$/', $input)){
            throw new \exception('Please Enter an alphabetic name between 2 and 30 charachters. Spaces are allowed.');
        }
        return $input;
    }
    
    //normalize the date format from html5 date inputs. Probably needs work in the future.
    public function normalize_date($input)
    {
        if(strpos($input,'-') !== false){
            $seperator = '-';
        }
        else if(strpos($input,'/') !== false){
            $seperator = '/';
        }
        else if(strpos($input,',') !== false){
            $seperator = ',';
        }else{
            throw new \exception('Invalid separator used. Use - OR / OR , to separate the date.');
        }
        if(substr_count($input,$seperator) !== 2){
            throw new \exception('Malformed Date given in form.');
        }
        $t = explode($seperator,$input);
        if(count($t) !== 3){
            throw new \exception("Invalid date");
        }
        return implode('-',$t);
    }
    
    public function password_hash($input)
    {
        return password_hash($input, self::$config['password']['algo'], ['cost'=>self::$config['password']['cost']]);
    }
    
    //format a paragraph. good for textarea inputs.
    public function paragraph($input)
    {
        return str_replace(['\r\n','\n'],'<br/>',strip_tags($input));
    }
    
    //remove line feeds. 
    public function rmnl($input)
    {
        return preg_replace('/\s+/', ' ', trim($input));
    }
    
    /* BEGIN US SPECIFIC FUNCTIONS IN FUTURE THIS BLOCK SHOULD BE MOVED TO a Locales class. */
    
    public function us_phone($input)
    {
        $input = preg_replace( '/[^0-9]/','', trim($input) );
        if(!preg_match('/^([0-9]){10}$/', $input)){
            throw new \exception($input);
        }
        return $input;
    }
    
    public function us_state_abbr($state)
    {
        $input = strtoupper($input);
        $states = array_keys(self::$states);
        if(!in_array($input,$states)){
            throw new \exception('That isnt a real state.');
        }
        return $input;
    }
    
    public function us_state_full($input)
    {
        $input = strtoupper($input);
        $states = array_values(self::$states);
        if(!in_array($input,$states)){
            throw new \exception('That isnt a real state.');
        }
        return $input;
    }
    
    public function us_zipcode($input)
    {
        if (!preg_match( '/^\d{5}([\-]?\d{4})?$/i',$input)){
            throw new \exception('Invalid Zip Code Entered.');
        }
        return $str;
    }

    private static $states = [
        'AL'=>'ALABAMA',
        'AK'=>'ALASKA',
        'AZ'=>'ARIZONA',
        'AR'=>'ARKANSAS',
        'CA'=>'CALIFORNIA',
        'CO'=>'COLORADO',
        'CT'=>'CONNECTICUT',
        'DE'=>'DELAWARE',
        'DC'=>'DISTRICT OF COLUMBIA',
        'FL'=>'FLORIDA',
        'GA'=>'GEORGIA',
        'HI'=>'HAWAII',
        'ID'=>'IDAHO',
        'IL'=>'ILLINOIS',
        'IN'=>'INDIANA',
        'IA'=>'IOWA',
        'KS'=>'KANSAS',
        'KY'=>'KENTUCKY',
        'LA'=>'LOUISIANA',
        'ME'=>'MAINE',
        'MD'=>'MARYLAND',
        'MA'=>'MASSACHUSETTS',
        'MI'=>'MICHIGAN',
        'MN'=>'MINNESOTA',
        'MS'=>'MISSISSIPPI',
        'MO'=>'MISSOURI',
        'MT'=>'MONTANA',
        'NE'=>'NEBRASKA',
        'NV'=>'NEVADA',
        'NH'=>'NEW HAMPSHIRE',
        'NJ'=>'NEW JERSEY',
        'NM'=>'NEW MEXICO',
        'NY'=>'NEW YORK',
        'NC'=>'NORTH CAROLINA',
        'ND'=>'NORTH DAKOTA',
        'OH'=>'OHIO',
        'OK'=>'OKLAHOMA',
        'OR'=>'OREGON',
        'PA'=>'PENNSYLVANIA',
        'RI'=>'RHODE ISLAND',
        'SC'=>'SOUTH CAROLINA',
        'SD'=>'SOUTH DAKOTA',
        'TN'=>'TENNESSEE',
        'TX'=>'TEXAS',
        'UT'=>'UTAH',
        'VT'=>'VERMONT',
        'VA'=>'VIRGINIA',
        'WA'=>'WASHINGTON',
        'WV'=>'WEST VIRGINIA',
        'WI'=>'WISCONSIN',
        'WY'=>'WYOMING'
    ];
}