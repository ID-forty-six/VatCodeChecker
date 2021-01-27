<?php

namespace idfortysix\vatcodechecker;

use idfortysix\curlwrapper\Robot;
use idfortysix\curlwrapper\Parser;

class CHVatChecker implements ByCountryInterface{

	/*
	 * Validate Switzerland (CH) VAT
	 */
    public function check_VAT(string $country_id, string $vat_number, bool $w_info)
    {
        $vat_number = $this->format_VAT($vat_number);
            
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
 
        $parser = new Parser($page);
       
        if ($parser->getStrings($this->getVatPattern()) && $parser->getStrings($vat_number)) 
        {
            if($w_info)
            {
                //get company name
				preg_match($this->getNamePattern(), $parser->getPage(), $name_matches);
				//get company street address
				preg_match($this->getStreetPattern(), $parser->getPage(), $street_matches);
				//get company city
				preg_match($this->getCityPattern(), $parser->getPage(), $city_matches);

                // format address
				$address = (isset($street_matches[2]) ? html_entity_decode($street_matches[2]) : '' ) 
				. ' ' 
				. (isset($city_matches[2]) ? html_entity_decode($city_matches[2]) : '');

                return [
                    'name'    => isset($name_matches[2]) ? html_entity_decode($name_matches[2]) : null,
                    'country' => $country_id,
					'vat'     => $vat_number,
					'address' => $address,
					'code' => $vat_number,
                ];
            }

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
    public function format_VAT($vat_number)
    {
        $vat_number = preg_replace('/\D/iu', '', $vat_number);
        
        // create CHE-123.456.789 VAT format
        $vat_number = substr_replace(substr_replace($vat_number, ".", 3, 0), ".", 7, 0);
        return "CHE-".$vat_number;
	}
    
    /**
	 * Vat pattern
	 */
	private function getVatPattern()
    {
        return <<<EOF
UID-Status</label><div class="col-sm-4">
<div style="padding:7px 12px">Aktiv</div>
EOF;
    }

    /**
	 * Name regex
	 */
    private function getNamePattern()
    {
        return '/Name<\/label><div class="col-sm-10">(s*)<div style="padding:7px 12px">([^<>]*?)<\/div>/';
    }

    /**
	 * Address regex for street
	 */
    private function getStreetPattern()
    {
        return '/Strasse \/ Nr\.<\/label><div class="col-sm-10">(s*)<div style="padding:7px 12px">([^<>]*?)<\/div>/';
	}
    
    /**
	 * Address regex for city
	 */
	private function getCityPattern()
    {
        return '/PLZ \/ Ort<\/label><div class="col-sm-10">(s*)<div style="padding:7px 12px">([^<>]*?)<\/div>/';
    }
}
