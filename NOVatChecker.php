<?php

namespace idfortysix\vatcodechecker;

use idfortysix\curlwrapper\Robot;
use idfortysix\curlwrapper\Parser;

class NOVatChecker implements ByCountryInterface{


    /**
	 * Checks NO VAT is valid
	 */
    public function check_VAT(string $country_id, string $vat_number, bool $w_info)
    {
        // format vat
        $vat_number = $this->format_VAT($vat_number);

        //NO VAT checking system URL
        $url = "https://w2.brreg.no/enhet/sok/detalj.jsp?orgnr=".$vat_number;

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
        $vat_number_spaced = $this->format_VAT_withSpaces($vat_number);

        //find patterns is source code
        $parser = new Parser($page);

        if ($parser->getStrings($this->getVatPattern($vat_number_spaced)))
        {
            if($w_info)
            {
                //get company name
                preg_match($this->getNamePattern(), $parser->getPage(), $name_matches);
                //get company address
                preg_match($this->getAddressPattern(), $parser->getPage(), $address_matches);

                // format address
                $address = (isset($address_matches[5]) ? html_entity_decode($address_matches[5]) : '' ) 
				. ' ' 
				. (isset($address_matches[6]) ? html_entity_decode($address_matches[6]) : '');

                return [
                    'name'    => isset($name_matches[5]) ? html_entity_decode($name_matches[5]) : null,
                    'country' => $country_id,
                    'vat'     => $vat_number,
                    'address' => $address,
                    'code'    => $vat_number
                ];
            }
            return true;
        }
        else
        {
            return false;
        }

    }
    
    /**
	 * Format VAT for NO
	 */
	private function format_VAT($vat_number)
    {
        $vat_number = preg_replace('/\D/iu', '', $vat_number);

        return $vat_number;
    }

    /**
	 * format Norway (NO) VAT code for regex match
	 */
    private function format_VAT_withSpaces($vat_number)
    {
        $vat_number = substr_replace(substr_replace($vat_number, " ", 3, 0), " ", 7, 0);

        return $vat_number;
    }


    /**
	 * VAT pattern
	 */
    private function getVatPattern(string $vat_number)
    {
        return <<<EOF
<b>Organisasjonsnummer: </b>
</p>
</div>
<div class="col-sm-8">
<p>$vat_number</p>   
EOF;
    }

    /**
	 * Name regex
	 */
    private function getNamePattern()
    {
        return '/<b>Navn\/foretaksnavn: <\/b>(s*)<\/p>(s*)<\/div>(s*)<div class="col-sm-8">(s*)<p>([^<>]*?)<\/p>/';
    }

    /**
	 * Address regex
	 */
    private function getAddressPattern()
    {
        return '/<b>Forretningsadresse: <\/b>(s*)<\/p>(s*)<\/div>(s*)<div class="col-sm-8" style="vertical-align:top">(s*)<p>([^<>]*?)<br>([^<>]*?)<\/p>/';
    }
}
