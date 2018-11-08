<?php
namespace CryptoPay\NZBanks;

use SilverStripe\Admin\ModelAdmin;


class BankAdmin extends ModelAdmin
{
    private static $managed_models = [
        'Bank'
    ];

    private static $menu_title = 'Banks';

    private static $url_segment = 'banks';
}
