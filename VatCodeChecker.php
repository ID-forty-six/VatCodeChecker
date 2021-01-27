<?php

namespace idfortysix\vatcodechecker;


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
            $checker = resolve(CHVatChecker::class);
        }
        //checks if user is from Norway
        elseif ($country->id == 'NO')
        {
            $checker = resolve(NOVatChecker::class);
        }
        // is from EU country
        elseif ($country->eu == 1)
        {
            $checker = resolve(EUVatChecker::class);
        }
        else
        {
            return null;
        }
        
        return $checker->check_VAT($country->id, $vat_number, $w_info);
    }
}
