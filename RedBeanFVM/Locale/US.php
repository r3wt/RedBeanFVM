<?php

namespace RedBeanFVM\Locale;

class US
{
    function __construct(){}    
    
    public function us_phone($input)
    {
        $input = preg_replace( '/[^0-9]/','', trim($input) );
        if(!preg_match('/^([0-9]){10}$/', $input)){
            throw new \exception($input);
        }
        return $input;
    }
    
    public function us_state_abbr($input)
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
        return $input;
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