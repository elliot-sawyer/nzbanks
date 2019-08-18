# New Zealand Banks
SilverStripe module for managing a dataset of active New Zealand banks. This also maintains a DataObject of what represents a New Zealand bank account number, including validation of these numbers. It also contains utility functions for converting between dollars and cents.

## Installation
1. add the repository to your composer.json. This is a private repo, so composer will not find it on packagist
2. `composer require cryptopay/nzbanks`
3. Run `vendor/bin/sake dev/build flush=`
4. Populate the database with bank branches: `vendor/bin/sake dev/tasks/download-bank-register-information flush=`. Do this on the CLI because it will likely exceed PHP's default execution time.

## Configuration
Only the download URL can be changed. This is incredibly unlikely, but possible because PaymentsNZ has updated the URL more than once without notice.
```yml
CryptoPay\NZBanks\DownloadBankRegisterInformation:
  bank_register_source: https://payments.nz/path/to/register
```

## Usage
```php
    //convert NZ Dollars to cents
    NZBankAccount::dollars_to_cents(1.234); //1234

    //convert NZ cents into dollars
    NZBankAccount::cents_to_dollars(1234) //12.34

    //identity the bank that issued an account. This is based on the first 6 digits and returns a Bank dataobject
    Bank::identify('12-3141-0000456-001') // ASB Bank, Willis Street (Bank dataobject)

    //display the account in a "normalised" format described by the IRD
    NZBankAccount::prettify('1-2-3-4') //0001-0002-00000003-0004

    //display the account in a "normalised" format with a different delimiter
    NZBankAccount::prettify('1-2-3-4', ' ') //0001 0002 00000003 0004
    NZBankAccount::prettify('1-2-3-4', '.') //0001.0002.00000003.0004
```

Bank accounts are validated according to an IRD checksum format. More information on this validation can be found here: https://www.ird.govt.nz/-/media/Project/IR/PDF/2020RWTNRWTSpecificationDocumentv10.pdf (see page 15, "Bank account number validation")

## Copyright
&copy; 2019 Elliot Sawyer, CryptoPay Limited. All rights reserved. 

## Support
Like my work? Consider shouting me a coffee or a small donation if this module helped you solve a problem. I accept cryptocurrency at the following addresses:
* Bitcoin: 12gSxkqVNr9QMLQMMJdWemBaRRNPghmS3p
* Bitcoin Cash: 1QETPtssFRM981TGjVg74uUX8kShcA44ni
* Litecoin: LbyhaTESx3uQvwwd9So4sGSpi4tTJLKBdz
