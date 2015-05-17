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
        ]
    ]; 
    
    private static $locale; //in the future, language specific packs will reside under this variable.
    private static $custom_filters = []; //allow users to register custom filters. 
    
    /**
     * Protected Ctor
     */
    protected function __construct(){}
    
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
     * configure multiple configuration settings
     * @params array
     */
    public static function configureAll($c)
    {
        if(!is_array($c)){
            throw new \exception('RedBeanFVM :: configureAll() expects an array! `'.gettype($c).'` given.';
        }
        foreach($c as $k => $v){
            self::configure($k,$v);
        }
    }

    /**
     * Configures a single property in the config array.
     * @param string $k the key in the config array
     * @param mixed $v the new value to set.
     * @return void
     */
    public static function configure($k,$v)
    {
        if(isset(self::$config[$k])){
            self::$config[$k] = $v;
        }else{
            throw new \exception('RedBeanFVM :: configure() `'.$k.'` is not a valid configuration option.');
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
    private function snake_case($property)
    {
        return strtolower(trim(preg_replace("/(_)\\1+/", "$1",preg_replace('/([^a-zA-Z_])/','_',$test)),'_'));
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
     * creates a named callable and adds it to $custom_filters array. useful for creating custom filters.
     * @param string $name the name to assign to the callable.
     * @param closure $callable the callback function.
     */
    public function custom_filter($name,$callable)
    {
        if(empty($name)){
            throw new \exception('RedbeanFVM :: custom_filter() An Invalid Name was declared.');
        }
        if(!is_callable($callable)){
            throw new \exception('RedbeanFVM :: custom_filter() Method `'.$name.'` isn\'t a valid callable!');
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
    public function az($str)
    {
        return preg_replace( '/[^a-zA-Z]/','', trim($str) );
    }
    
    // self explanatory
    public function az_upper($str)
    {
        return strtoupper($this->az($str));
    }
    
    // self explanatory
    public function az_lower($str)
    {
        return strtolower($this->az($str));
    }
    
    //remove all but typically allowed charachters in a business entity name, Eg: #1 Plumbing-Contractors & Associates, Ltd.
    public function business_name($name)
    {
        return preg_replace( '/[^A-Za-z\,\.\-\&\# ]+/','', trim($name) );
    }
    
    //cast to int
    public function cast_int($val)
    {
        return ((int) $val);
    }
    
    //validate an email
    public function email($email)
    {
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
            throw new \exception('invalid email address');
        }
        return $email;
    }
    
    //the minimalist filter
    public function min($in)
    {
        return stripslashes(strip_tags(trim($in)));
    }
    
    //remove all non word charachters with safeguard. 
    //unfortunately this method is not chainable, however you could work around it by defining a custom filter with a use statement:
    /*
        $min = 5;
        $max = 55;
        $fvm = RedBeanFVM::getInstance();
        $custom_filter = function($input) use($min,$max,$filter){ return $filter->name($input,$min,$max); }
    */
    public function name($name, $min = 2,$max = 30)
    {
        $name = preg_replace( '/[^ \w]+/','', trim($name) );
        if(!preg_match('/^[a-zA-Z ]{'.$min.','.$max.'}$/', $name)){
            throw new \exception('Please Enter an alphabetic name between 2 and 30 charachters. Spaces are allowed.');
        }
        return $name;
    }
    
    //normalize the date format from html5 date inputs. Probably needs work in the future.
    public function normalize_date($str)
    {
        if(strpos($str,'-') !== false){
            $seperator = '-';
        }
        else if(strpos($str,'/') !== false){
            $seperator = '/';
        }
        else if(strpos($str,',') !== false){
            $seperator = ',';
        }else{
            throw new \exception('Invalid separator used. Use - OR / OR , to separate the date.');
        }
        if(substr_count($str,$seperator) !== 2){
            throw new \exception('Malformed Date given in form.');
        }
        $t = explode($seperator,$str);
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
        $state = strtoupper($state);
        $states = array_keys(self::$states);
        if(!in_array($state,$states)){
            throw new \exception('That isnt a real state.');
        }
        return $state;
    }
    
    public function us_state_full($state)
    {
        $state = strtoupper($state);
        $states = array_values(self::$states);
        if(!in_array($state,$states)){
            throw new \exception('That isnt a real state.');
        }
        return $state;
    }
    
    public function us_zipcode($str)
    {
        if (!preg_match( '/^\d{5}([\-]?\d{4})?$/i',$str)){
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