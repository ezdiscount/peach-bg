<?php

namespace service\config;

class QuotaRate extends Config
{
    protected $name = 'quota_rate';
    const BUYER = 'buyer_rate';
    const FATHER = 'father_rate';
    const GRANDPA = 'grandpa_rate';
    const CHAIN_SUPER = 'chain_super_rate';
    const CHAIN_PARTNER = 'chain_partner_rate';
    const SUPER = 'super_rate';
}
