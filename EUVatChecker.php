<?php

namespace idfortysix\vatcodechecker;

use idfortysix\curlwrapper\Robot;
use idfortysix\curlwrapper\Parser;
use PH7\Eu\Vat\Validator;
use PH7\Eu\Vat\Provider\Europa;

class EUVatChecker implements ByCountryInterface{

    /**
	 * Validate EU VAT via veis soap service
	 */
    public function check_VAT(string $country_id, string $vat_number, bool $w_info)
    {
		// kai kada PVM kodas turi raides, pvz Austrijoje
		// reikia papildomai repleisinti kai pirmos dvi raides - salies kodas
        $vat_number = preg_replace(['/\W/iu', "/^$country_id/iu"], '', strtoupper($vat_number) );
        $oVatValidator = new Validator(new Europa, $vat_number, $country_id);
        
        if ($oVatValidator->check())
        {
            if($w_info)
            {
                return [
                    'name'    => $oVatValidator->getName(),
                    'country' => $oVatValidator->getCountryCode(),
                    'vat'     => $oVatValidator->getVatNumber(),
                    'address' => $oVatValidator->getAddress(),
                ];
            }
            return true;
        }
        return false;
    }

    /**
	 * Validate EU VAT 
     * DEPRECATED - moved to SOAP service
	 */
    public function check_VAT_veis($country_id, $vat_number)
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
}
