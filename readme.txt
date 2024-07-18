-------------------------------------------------
 Joker.com registrar module for WHMCS
-------------------------------------------------
Version 1.3.4
Tested with WHMCS 8.1.3


Features:

The following registrar core functionality is provided:
* register domains
* initiate transfers
* perform renewals
* allow viewing and changing of nameservers
* allow viewing and changing of WHOIS information of domains
* usable with any TLD Joker.com offers
* TLD & pricing sync

Additionally, also these features are provided:
* create/edit email forwards
* create/edit dns records for joker-ns
* fetch auth id
* sync of expiration date
* order whois privacy protection with domain registration, transfer and renewal
* domain availability check
* dnssec configuration


Installation:
Please put the content of this archive into the folder modules/registrars/joker/
of your WHMCS installation.
In case you want to deal with .FI domains, add the contents of the file 
"additionalfields.txt" to the WHMCS file in "resources/domains/additionalfields.php". 

The configuration of the Joker.com registrar module is done by using the WHMCS
admin section. For this, please login as "admin" into your WHMCS installation, and
navigate to

Setup -> Products/Services -> Domain Registrars

Pick "Joker.com" form the list, "Activate" the plugin, and enter your Joker.com
Reseller's credentials - the same you are using for DMAPI.
You also may choose to use the "TestMode" for dry run, or to use the free
Joker.com nameservers as a default for new domains.
Once activated, you may always change these settings by clicking on "Configure".
Don't forget to "Save Changes" after doing so.


Usage:
You will find most options for domain in section "Clients -> Domain registration"
within the domain details.
A special command there is "Sync" - this will trigger a manual sync of a domain's
status and expiration date with Joker.com, in case it was modified outside of
WHMCS. This is usually also done automatically by the 'WHMCS domainsync cron'.

Advanced settings:
If you enable the "CronJob" option of this module, make sure that the
file 'modules/registrars/joker/cron.php' is executed regularly. We suggest to
run it every 5 minutes. If the cron.php is not executed and the "CronJob" option
is enabled, domain registrations will not complete in WHMCS!!!

Example crontab entry:
*/5 * * * * php -q /path/to/whmcs/modules/registrars/joker/cron.php


Please send us your feedback: reseller-support@joker.com
Enjoy!

Your Team of Joker.com
