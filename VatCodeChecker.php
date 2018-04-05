<?php

namespace idfortysix\vatcodechecker;

use idfortysix\curlwrapper\Robot;
use idfortysix\curlwrapper\Parser;

/**
 * Check if VAT code is valid
 */

class VatCodeChecker {

	/*
	 * is VAT exempt applicable for the following country
	 */
	public function is_applicable($country)
	{
		return ($country->id == 'CH' || $country->eu == 1 || $country->id == "NO");
	}
    
    /*
	 * Checks if PVM is valid
	 * $country objektas arba stdClass, kur butinos properties id ir eu
	 */
    public function check_VAT($country, $vat_number)
    {
        // Checks if user is from Switzerland
        if ($country->id == 'CH')
        {
            return $this->check_CH_VAT($vat_number);
        }
        //checks if user is from Norway
        elseif ($country->id == 'NO')
        {
            return $this->check_NO_VAT($var_number);
        }
        // is from EU country
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
	 * Validate Norway (NO) VAT
	 */
    private function check_NO_VAT($vat_number)
    {
        $vat_number = $this->format_NO_VAT($vat_number);

        //NO VAT checking system URL
        $url = "http://w2.brreg.no/enhet/sok/detalj.jsp?orgnr=".$vat_number;

        $robot = new Robot;

        //get source code
        try
        {
            $page = $robot->followLocation()->curlPage($url);
        }
        catch(\Exeption $ex)

        {
            return false;
        }

        //Page source paterns for VAT validation

        $vat_number = $this->format_NO_VAT_withSpaces($vat_number);

        $patern_active = <<<EOF
<b>Organisasjonsnummer: </b>
</p>
</div>
<div class="col-sm-8">
<p>$vat_number</p>       
EOF;

        //find patterns is source code

        $parser = new Parser($page);

        if ($parser->getStrings($pattern_active)) 
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
		// kai kada PVM kodas turi raides, pvz Austrijoje
		// reikia papildomai repleisinti kai pirmos dvi raides - salies kodas
        $vat_number = preg_replace(['/\W/iu', "/^$country_id/iu"], '', strtoupper($vat_number) );
		
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
	 * format Switzerland (CH) VAT code
	 */
    private function format_CH_VAT($vat_number)
    {
        $vat_number = preg_replace('/\D/iu', '', $vat_number);
        
        // create CHE-123.456.789 VAT format
        $vat_number = substr_replace(substr_replace($vat_number, ".", 3, 0), ".", 7, 0);
        return "CHE-".$vat_number;
    }
    /*
	 * format Norway (NO) VAT code
	 */
    private function format_NO_VAT($vat_number)
    {
        $vat_number = preg_replace('/\D/iu', '', $vat_number);

        return $vat_number;
    }

    /*
	 * format Norway (NO) VAT code
	 */
    private function format_NO_VAT_withSpaces($vat_number)
    {
        $vat_number = substr_replace(substr_replace($vat_number, " ", 3, 0), " ", 7, 0);

        return $vat_number;
    }
}