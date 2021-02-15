<?php

namespace idfortysix\vatcodechecker;

use Exception;

/**
 * Check if VAT code is valid
 */
class VatCodeChecker {

	/**
	 * is VAT exempt applicable for the following country
	 */
	public function is_applicable($country)
	{
		return ($country->id == 'CH' || $country->eu == 1 || $country->id == "NO");
	}
    
    /**
	 * Checks if PVM is valid
	 * $country objektas arba stdClass, kur butinos properties id ir eu
	 */
    public function check_VAT($country, $vat_number, $w_info = false)
    {
        // Checks if user is from Switzerland
        if ($country->id == 'CH')
        {
            $checker = new CHVatChecker;
        }
        //checks if user is from Norway
        elseif ($country->id == 'NO')
        {
            $checker = new NOVatChecker;
        }
        // is from EU country
        elseif ($country->eu == 1)
        {
            $checker = new EUVatChecker;
        }
        else
        {
            return null;
        }

        // Greece VIES naudoja kitoki country code
        if($country->id == "GR")
        {
            $country->id = 'EL';
        }
        
        try 
        {
            $result = $checker->check_VAT($country->id, $vat_number, $w_info);
        } 
        catch (Exception $ex) 
        {
            $result = null;
        }

        return $result;
    }
}
