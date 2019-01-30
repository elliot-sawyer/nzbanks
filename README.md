# nzbanks
SilverStripe module for managing a dataset of active New Zealand banks. This also maintains a DataObject of what represents a New Zealand bank account number, including validation of these numbers. It also contains utility functions for converting between dollars and cents.

## installation
1. add the repository to your composer.json. This is a private repo, so composer will not find it on packagist
2. `composer require cryptopay/nzbanks`
3. Run `vendor/bin/sake dev/build flush=`
4. Populate the database with bank branches: `vendor/bin/sake dev/tasks/CryptoPay-NZBanks-DownloadBankRegisterInformation flush=`

## configuration
Only the download URL can be changed. This is incredibly unlikely, but possible because PaymentsNZ has updated the URL more than once without notice.
```yml
CryptoPay\NZBanks\DownloadBankRegisterInformation:
  bank_register_source: https://payments.nz/path/to/register
```

## usage
```php
    NZBankAccount::dollars_to_cents(1.234); //1234
    NZBankAccount::cents_to_dollars(1234) //12.34
    Bank::identify('12-3141-0000456-001') // ASB Bank, Willis Street (Bank dataobject)
    Bank::prettify('1-2-3-4') //0001-0002-00000003-0004
```

## copyright
&copy; 2018 Elliot Sawyer, CryptoPay Limited. All rights reserved. You may not use this code for any reason. 
