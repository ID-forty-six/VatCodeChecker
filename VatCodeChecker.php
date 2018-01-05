<?php

namespace idfortysix\vatcodechecker;

use idfortysix\curlwrapper\Robot;
use idfortysix\curlwrapper\Parser;

/**
 * Check if VAT code is valid
 */

class VatCodeChecker {
    
    /*
	 * Checks if PVM is valid
	 */
    public function check_VAT($country, $vat_number)
    {
        // Checks if user is from Switzerland
        if ($country->id == 'CH')
        {
            return $this->check_CH_VAT($vat_number);
        }
        // if user is from EU countries
        elseif ($country->eu == 1)
        {
            return $this->check_EU_VAT($country->id, $vat_number);
        }
        else
        {
            return null;
        }
    }
    
    /*
	 * Validate Switzerland (CH) VAT
	 */
    private function check_CH_VAT($vat_number)
    {
        $vat_number = $this->format_CH_VAT($vat_number);
            
        // CH VAT checking system URL
        $url = "https://www.uid.admin.ch/Detail.aspx?uid_id=".$vat_number;
            
        // fill form data
        $get = [ 'uid_id' => $vat_number ];
            
        $robot = new Robot;
            
        // get source code
        try
        {
            $page = $robot->followLocation()->addPost($get)->curlPage($url);
        }
        catch(\Exception $ex)
        {
            return false;
        }
            
        // Page source paterns for VAT validation
        $pattern_active = <<<EOF
UID-Status</label><div class="col-sm-4">
<div style="padding:7px 12px">Aktiv</div>
EOF;
            
        // find patterns in source code
        $pattern_vat = $vat_number;
            
        $parser = new Parser($page);
            
        if ($parser->getStrings($pattern_active) && $parser->getStrings($pattern_vat)) 
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /*
	 * Validate EU VAT
	 */
    private function check_EU_VAT($country_id, $vat_number)
    {
        $vat_number = $this->format_VAT($vat_number);
        
        // EU VAT checking system URL
        $url = "http://ec.europa.eu/taxation_customs/vies/vatResponse.html";
            
        // fill form data
        $post = [
            'memberStateCode' => $country_id,
            'number' => $vat_number,
            'traderName' => '',
            'traderStreet' => '',
            'traderPostalCode' => '',
            'traderCity' => '',
            'requesterMemberStateCode' => '',
            'requesterNumber' => '',
            'action' => 'check',
            'check' => 'verify',
        ];
            
        $robot = new Robot;
            
        // Get source code
        try
        {
            $page = $robot->followLocation()->addPost($post)->curlPage($url);
        }
        catch(\Exception $ex)
        {
            return false;
        }
        
        // find patterns in source code
        $pattern = "<span class=\"validStyle\">Yes, valid VAT number</span>";
        
        $parser = new Parser($page);
        
        if ($parser->getStrings($pattern)) 
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    
    /*
	 * get only numbers from VAT code and delete all spaces
	 */
    private function format_VAT($vat_number) 
    {
        return preg_replace('/[\D\s]/iu', '', $vat_number);
    }
    
    
    // format Switzerland (CH) VAT code
    private function format_CH_VAT($vat_number) 
    {
        $vat_number = $this->format_VAT($vat_number);
        
        // create CHE-123.456.789 VAT format
        $vat_number = substr_replace(substr_replace($vat_number, ".", 3, 0), ".", 7, 0);
        return "CHE-".$vat_number;
    }
    

}