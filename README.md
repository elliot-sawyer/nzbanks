# nzbanks
SilverStripe module for managing a dataset of active New Zealand banks

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

## copyright
&copy; 2018 Elliot Sawyer, CryptoPay Limited. All rights reserved. You may not use this code for any reason. 
