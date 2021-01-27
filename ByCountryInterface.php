<?php

namespace idfortysix\vatcodechecker;

interface ByCountryInterface
{
    public function check_VAT(string $country_id, string $vat_number, bool $w_info);
}
