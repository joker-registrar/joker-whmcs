<?php
/*
  ****************************************************************************
  *                                                                          *
  * The MIT License (MIT)                                                    *
  * Copyright (c) 2019 Joker.com                                             *
  * Permission is hereby granted, free of charge, to any person obtaining a  *
  * copy of this software and associated documentation files                 *
  * (the "Software"), to deal in the Software without restriction, including *
  * without limitation the rights to use, copy, modify, merge, publish,      *
  * distribute, sublicense, and/or sell copies of the Software, and to       *
  * permit persons to whom the Software is furnished to do so, subject to    *
  * the following conditions:                                                *
  *                                                                          *
  * The above copyright notice and this permission notice shall be included  *
  * in all copies or substantial portions of the Software.                   *
  *                                                                          *
  * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS  *
  * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF               *
  * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.   *
  * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY     *
  * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,     *
  * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE        *
  * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.                   *
  *                                                                          *
  ****************************************************************************
  *                                                                          *
  * To install, create a folder named joker in modules/registrar             *
  * of your whmcs installation root directory and copy                       *
  * joker.php, jokerclient.php, eppcode.tpl, hooks.php logo.gif into it.     *
  * Then in WHMCS admin menu, go to registrar module settings and select     *
  * Joker, and configure.                                                    *
  ****************************************************************************
*/

require_once dirname(__FILE__).'/dmapiclient.php';
require_once dirname(__FILE__).'/helper.php';
require_once dirname(__FILE__).'/translations.php';

use WHMCS\Domains\DomainLookup\ResultsList;
use WHMCS\Domains\DomainLookup\SearchResult;

function joker_getConfigArray() {
$configarray = array(
 "Description" => array("Type" => "System", "Value"=>"Don't have an Joker Account yet? Get one at: <a href=\"http://joker.com/\" target=\"_blank\">http://joker.com/</a>"),
 "ApiKey" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your Joker API key here", ),
 "Username" => array( "Type" => "text", "Size" => "20", "Description" => "If you don't use API key, enter your Joker Reseller Account Username here", ),
 "Password" => array( "Type" => "password", "Size" => "20", "Description" => "If you don't use API key, enter your Joker Reseller Account Password here", ),
 "TestMode" => array( "Type" => "yesno", "Description" => "Tick this box to use the Joker OT&E system: <a href=\"http://www.ote.joker.com/\" target=\"_blank\">http://www.ote.joker.com/</a>"),
 "SyncNextDueDate" => array( "Type" => "yesno", "Description" => "Tick this box to also sync the next due date with the expiry date", ),
 "DefaultNameservers" => array( "Type" => "yesno", "Description" => "Tick this box to use the default Joker nameservers for new domain registrations", ),
 "TryRestoreFromRGP" => array( "Type" => "yesno", "Description" => "Tick this box to try a restore from redemption grace period, if renewal is not possible. (Be aware of additional costs and configure the fees in WHMCS accordingly!)", "Default" => false ),
 "CronJob" => array( "Type" => "yesno", "Description" => "Tick this box to use this module with its own cron job (see Readme before activation!)", "Default" => false ),
);

return $configarray;

}

function joker_GetNameservers($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('query-whois', $reqParams);

    $values = array();
    $nameservers = $Joker->getValue('domain.nservers.nserver.handle');
    for ($i = 1; $i <= 12; $i++) {
        $values["ns".$i] = isset($nameservers[$i-1]) ? $nameservers[$i-1] : '';
    }

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
    }

    return $values;

}

function joker_SaveNameservers($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $nameserverList = array();
    for ($i=1;$i<=5;$i++) {
        if (isset($params["ns".$i]) && !empty($params["ns".$i])) {
            $nameserverList[] = $params["ns".$i];
        }
    }

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
    $reqParams["ns-list"] = implode(":", $nameserverList );

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('domain-modify', $reqParams);

    $values = array();
    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
    }

    return $values;

}

function joker_GetRegistrarLock($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["pattern"] = $idn_domain;
    $reqParams["showstatus"] = 1;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('query-domain-list', $reqParams);

    $resultList = $Joker->getResponseList();

    if (count($resultList) > 0) {
        $status = explode(",",$resultList[0]['domain_status']);
        if (in_array('lock', $status)) {
            $lockstatus="locked";
        } else {
            $lockstatus="unlocked";
        }
        return $lockstatus;
    }

}

function joker_SaveRegistrarLock($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    if ($params["lockenabled"]=="locked") {
        $command = "domain-lock";
    } else {
        $command = "domain-unlock";
    }

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction($command, $reqParams);

    $values = array();
    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
    }
    return $values;
}

function joker_GetEmailForwarding($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("dns-zone-get", $reqParams);
    
    $values = array();

    if (!$Joker->hasError()) {
        $dnsrecords = $Joker->getResponseList();
        $counter = 1;
        foreach($dnsrecords as $record) {
            if (count($record) > 1 && $record[1] == "MAILFW") {
                $values[$counter++] = array(
                    "prefix" => $record[0],
                    "forwardto" => $record[3]
                );
            }
        }
    } else {
        $values["error"] = $Joker->getError();
    }

    return $values;
}

function joker_SaveEmailForwarding($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("dns-zone-get", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
        return $values;
    }

    $olddnsrecords = $Joker->getResponseList();

    $dnsrecords = array();

    foreach($olddnsrecords as $key => $record) {
        if ((count($record) > 2 && ($record[1] == "MAILFW" || ($record[1] == "NS" && $record[0] == "@") ))  ) {
            continue;
        }
        //Fix TTL in old records
        if (count($record) >= 4 && empty($record[4])) {
            $record[4] = 86400;
        }
        $dnsrecords[] = implode(" ", $record);
    }

    foreach (array_keys($params["prefix"]) AS $key) {
        if ($params["forwardto"][$key]) {
            $dnsrecords[] = $params["prefix"][$key] . " MAILFW 0 " . $params["forwardto"][$key] . " 86400 0 0 1";
        }
    }

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
    $reqParams["zone"] = implode("\n", $dnsrecords);
    $Joker->ExecuteAction("dns-zone-put", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
    }

    return $values;

}

function joker_GetDNS($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("dns-zone-get", $reqParams);

    $hostRecords = array();

    if (!$Joker->hasError()) {
        $dnsrecords = $Joker->getResponseList();
        foreach($dnsrecords as $record) {
            if (count($record) > 2 && ($record[1] !== "NS" || $record[0] !== "@")) {
                if ($record[1] == "TXT") {
                    $hostRecords[] = array(
                        "hostname" => $record[0],
                        "type" => $record[1],
                        "priority" => $record[2],
                        "address" => substr(implode(" ", array_slice($record,3,-1)),2,-2)
                    );
                } elseif ($record[1] !== "MAILFW" ) {
                    $hostRecords[] = array(
                        "hostname" => $record[0],
                        "type" => $record[1],
                        "address" => $record[3],
                        "priority" => $record[2],
                    );
                }
            }
        }
    }

    return $hostRecords;

}

function joker_SaveDNS($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("dns-zone-get", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
        return $values;
    }

    $olddnsrecords = $Joker->getResponseList();

    $dnsrecords = array();

    foreach($olddnsrecords as $key => $record) {
        if ((count($record) > 2 && $record[1] == "MAILFW") || substr($record[0],0,7) == '$dyndns') {
            $dnsrecords[] = implode(" ", $record);
        }
    }

    foreach ($params["dnsrecords"] AS $key=>$record) {
        if ($record && $record["address"] && $record["type"]!='MXE') {
            $dnsrecords[] = (empty($record["hostname"])?'@':$record["hostname"])." {$record["type"]} ".($record["type"]=="MX"?$record["priority"]:0)." ".($record["type"]=="TXT"?'"':'').$record["address"].($record["type"]=="TXT"?'"':'')." 86400 0 0";
        }
    }

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
    $reqParams["zone"] = implode("\n", $dnsrecords);
    $Joker->ExecuteAction("dns-zone-put", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
    }

    return $values;

}

function joker_RegisterDomain($params) {

    $owner_result = joker_CreateOwnerContact($params);

    if (isset($owner_result['error']) && $owner_result['error']) {
        return $owner_result;
    }
    $admin_result = $owner_result;

    // Don't use admin contact for now, to speed up registration
    //$admin_result = joker_CreateAdminContact($params);
    //if (isset($admin_result['error']) && $admin_result['error']) {
    //    return $admin_result;
    //}

    $params = injectDomainObjectIfNecessary($params);

    $reqParams = Array();

    $idn_domain = $params['domainObj']->getDomain(true);

    //#################################################################################################################
    //# IDN fix for Swedish language only. Otherwise language will be guessed by Joker depending on registrant country#
    if ($params['domainObj']->isIdn()) {
        $reqParams["language"] = "";
        if ($params['language'] == 'swedish') {
            if (($params["tld"] == "co") || ($params["tld"] == "biz") || ($params["tld"] == "tel")){
                $reqParams["language"] = "se";
            } elseif (($params["tld"] == "com") || ($params["tld"] == "net") || ($params["tld"] == "li") || ($params["tld"] == "fr") || ($params["tld"] == "ch") || ($params["tld"] == "sg") || ($params["tld"] == "com.sg") || ($params["tld"] == "tv") || ($params["tld"] == "co.uk")){
                $reqParams["language"] = "swe";
            } else {
                $reqParams["language"] = "sv";
            }
        }
    }
    //# END IDN FIX
    //#################################################################################################################

    $reqParams["domain"] = $idn_domain;
    $reqParams["period"] = $params["regperiod"]*12;
    $reqParams["status"] = "production";
    $reqParams["owner-c"] = $owner_result['handle'];
    if (is_array($admin_result)) {
        $reqParams["admin-c"] = $admin_result['handle'];
        $reqParams["tech-c"] = $admin_result['handle'];
        $reqParams["billing-c"] = $admin_result['handle'];
    }
    $reqParams["cltrid"] = 'domreg-'.$params['domainid'];


    if ($params["DefaultNameservers"]) {
        $reqParams["ns-list"] = "a.ns.joker.com:b.ns.joker.com:c.ns.joker.com";
    } else {
        $nslist = array();
        for ($i=1;$i<=5;$i++) {
            if (isset($params["ns$i"]) && !empty($params["ns$i"])) {
                $nslist[] = $params["ns$i"];
            }
        }
        $reqParams["ns-list"] = implode(':', $nslist);
    }

    if (isset($params["idprotection"]) && $params["idprotection"]) {
        $reqParams["privacy"] = "pro";
    }

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("domain-register", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
    }
    return $values;

}

function joker_TransferDomain($params) {

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("query-profile",Array());
    if ($Joker->getValue('balance')<=0) {
        $values['error'] = 'Account balance is too low.';
        return $values;
    }

    $owner_result = joker_CreateOwnerContact($params);

    if (isset($owner_result['error']) && $owner_result['error']) {
        return $owner_result;
    }

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
    $reqParams["transfer-auth-id"] = $params["transfersecret"];
    $reqParams["owner-c"] = $owner_result['handle'];
    $reqParams["admin-c"] = $owner_result['handle'];
    $reqParams["tech-c"] = $owner_result['handle'];
    $reqParams["billing-c"] = $owner_result['handle'];
    $reqParams["autorenew"] = '0';

    $nslist = array();
    for ($i=1;$i<=5;$i++) {
        if (isset($params["ns$i"]) && !empty($params["ns$i"])) {
            $nslist[] = $params["ns$i"];
        }
    }
    if (count($nslist)>0) {
        $reqParams["ns-list"] = implode(':', $nslist);
    }

    if (isset($params["idprotection"]) && $params["idprotection"]) {
        $reqParams["privacy"] = "pro";
    }

    $Joker->ExecuteAction("domain-transfer-in-reseller", $reqParams);

    $values["error"] = $Joker->getError();

    return $values;

}

function joker_RenewDomain($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["pattern"] = $idn_domain;
    //$reqParams["showstatus"] = 1;

    $Joker = DMAPIClient::getInstance($params);
    
    $Joker->ExecuteAction("query-profile",Array());
    if ($Joker->getValue('balance')<=0) {
        $values['error'] = 'Account balance is too low.';
        return $values;
    }
    
    $Joker->ExecuteAction('query-domain-list', $reqParams);

    if ($Joker->hasError()) {
        $values['error'] = $Joker->getError();
        return $values;
    }

    $resultList = $Joker->getResponseList();
    
    if (count($resultList) > 0) {
        //$status = explode(",",$resultList[0]['domain_status']);
        //$expirationdate = $resultList[0]['expiration_date'];
        $restore = false;
    } else {
        // TODO: Check if domain is in redemption
        // isset($params['isInRedemptionGracePeriod']) && $params['isInRedemptionGracePeriod']
        $restore = true;
    }

    if (!$restore) {
        $reqParams = Array();
        $reqParams["domain"] = $idn_domain;
        $reqParams["period"] = $params["regperiod"]*12;
        if ($params["idprotection"]) {
            $reqParams["privacy" ] = "keep";
        }
        $Joker->ExecuteAction("domain-renew", $reqParams);
    } elseif ($params['TryRestoreFromRGP']) {
        // TODO: Add Privacy. What about additional domain years?
        $reqParams = Array();
        $reqParams["domain"] = $idn_domain;
        $Joker->ExecuteAction("domain-redeem", $reqParams);
    } else {
        $values['error'] = 'Domain could not be renewed. Redemption grace period?';
    }

    if ($Joker->hasError()) {
        $values['error'] = $Joker->getError();
    }

    return $values;

}

function joker_CreateOwnerContact($params) {
    $params = injectDomainObjectIfNecessary($params);
    $params = joker_CleanupContactDetails($params);

    $errorMsgs = array();


    $reqParams = Array();
    $reqParams["tld"] = $params["tld"];
    //$reqParams["fax"] = "";
    $reqParams["phone"] = $params["fullphonenumber"];
    $reqParams["country"] = $params["country"];
    $reqParams["postal-code"] = $params["postcode"];
    $reqParams["state"] = $params["state"];
    $reqParams["city"] = $params["city"];
    $reqParams["email"] = $params["email"];
    $reqParams["address-1"] = $params["address1"];
    $reqParams["address-2"] = $params["address2"];
    $reqParams["name"] = $params["firstname"].' '.$params["lastname"];
    $reqParams["organization"] = $params["companyname"];

    if ($params['domainObj']->getLastTLDSegment() == 'fi') {
        unset($reqParams["name"]);
        $reqParams["fname"] = $params["firstname"];
        $reqParams["lname"] = $params["lastname"];

        $reqParams["x-ficora-type"] = strtolower($params["additionalfields"]['x-ficora-type']);
        $reqParams["x-ficora-is-finnish"] = $params["additionalfields"]['x-ficora-is-finnish'];

        if ($reqParams["x-ficora-type"] == 'privateperson') {
            if ($reqParams["x-ficora-is-finnish"] == 'yes') {
                $reqParams["x-ficora-identity"] = $params["additionalfields"]["x-ficora-registernumber"];
            } else {
                $reqParams["x-ficora-birthdate"] = $params["additionalfields"]["x-ficora-registernumber"];
                if (!preg_match("/^\d{4}-\d{2}-\d{2}$/",$reqParams["x-ficora-birthdate"]) ) {
                    $date = date_parse($reqParams["x-ficora-birthdate"]);
                    if ($date['day'] !== false && $date['month'] !== false && $date['year'] !== false) {
                        $reqParams["x-ficora-birthdate"] = sprintf("%04d-%02d-%02d", $date['year'], $date['month'], $date['day']);
                    }
                }
            }
        } else {
            $reqParams["x-ficora-registernumber"] = $params["additionalfields"]["x-ficora-registernumber"];
        }
    } elseif ($params['domainObj']->getLastTLDSegment() == 'us') {

        $nexus = $params["additionalfields"]['Nexus Category'];
        $countrycode = $params["additionalfields"]['Nexus Country'];
        $purpose = $params["additionalfields"]['Application Purpose'];

        if ($purpose=="Business use for profit") {
            $purpose="P1";
        } elseif ($purpose=="Non-profit business") {
            $purpose="P2";
        } elseif ($purpose=="Club") {
            $purpose="P2";
        } elseif ($purpose=="Association") {
            $purpose="P2";
        } elseif ($purpose=="Religious Organization") {
            $purpose="P2";
        } elseif ($purpose=="Personal Use") {
            $purpose="P3";
        } elseif ($purpose=="Educational purposes") {
            $purpose="P4";
        } elseif ($purpose=="Government purposes") {
            $purpose="P5";
        }

        switch ($nexus){
            case 'C11':
            case 'C12':
            case 'C21':
                $reqParams["nexus-category"] = $nexus;
                break;
            case 'C31':
            case 'C32':
                $reqParams["nexus-category"] = $nexus;
                $reqParams["nexus-category-country"] = $countrycode;
                break;
        }
        $reqParams["app-purpose"] = $purpose;

    } elseif ($params['domainObj']->getLastTLDSegment() == 'uk') {

        if ($params["additionalfields"]['Legal Type']=="UK Limited Company") {
            $uklegaltype="LTD";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Public Limited Company") {
            $uklegaltype="PLC";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Partnership") {
            $uklegaltype="PTNR";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Limited Liability Partnership") {
            $uklegaltype="LLP";
        } elseif ($params["additionalfields"]['Legal Type']=="Sole Trader") {
            $uklegaltype="STRA";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Registered Charity") {
            $uklegaltype="RCHAR";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Industrial/Provident Registered Company") {
            $uklegaltype="IP";
        } elseif ($params["additionalfields"]['Legal Type']=="UK School") {
            $uklegaltype="SCH";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Government Body") {
            $uklegaltype="GOV";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Corporation by Royal Charter") {
            $uklegaltype="CRC";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Statutory Body") {
            $uklegaltype="STAT";
        } elseif ($params["additionalfields"]['Legal Type']=="Non-UK Individual") {
            $uklegaltype="FIND";
        } elseif ($params["additionalfields"]['Legal Type']=="Foreign Organization") {
            $uklegaltype="CORP";
        } elseif ($params["additionalfields"]['Legal Type']=="Other foreign organizations") {
            $uklegaltype="FOTHER";
        } else {
            $uklegaltype="IND";
        }
        $reqParams["account-type"] = $uklegaltype;
        $reqParams["company-number"] = $params["additionalfields"]['Company ID Number'];

    } elseif ($params['domainObj']->getLastTLDSegment() == 'eu') {
        $reqParams["lang"] = "EN";
    }

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("v2/contact/create", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = "Registrant: ".$Joker->getError();
        return $values;
    }

    $handle = $Joker->getValue('handle');
    $values['handle'] = $handle;
    return $values;

}

function joker_CreateAdminContact($params) {
    $params = injectDomainObjectIfNecessary($params);
    $params = joker_CleanupContactDetails($params);

    $errorMsgs = array();

    $reqParams = Array();
    $reqParams["tld"] = $params["tld"];
    //$reqParams["fax"] = "";
    $reqParams["phone"] = $params["adminfullphonenumber"];
    $reqParams["country"] = $params["admincountry"];
    $reqParams["postal-code"] = $params["adminpostcode"];
    $reqParams["state"] = $params["adminstate"];
    $reqParams["city"] = $params["admincity"];
    $reqParams["email"] = $params["adminemail"];
    $reqParams["address-1"] = $params["adminaddress1"];
    $reqParams["address-2"] = $params["adminaddress2"];
    $reqParams["name"] = $params["adminfirstname"].' '.$params["adminlastname"];
    $reqParams["organization"] = $params["admincompanyname"];

    if ($params['domainObj']->getLastTLDSegment() == 'eu') {
        $reqParams["lang"] = "EN";
    }

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("v2/contact/create", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = "Admin: ".$Joker->getError();
        return $values;
    }

    $handle = $Joker->getValue('handle');
    $values['handle'] = $handle;
    return $values;
}

function joker_GetContactDetails($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);
    
    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
    $reqParams["internal"] = 1;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('query-whois', $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
        return $values;
    }

    $names = explode(" ", $Joker->getValue("domain.name"));
    $lastname = array_pop($names);
    $firstname = implode(" ", $names);
    $values["Registrant"]["First Name"] = $firstname;
    $values["Registrant"]["Last Name"] = $lastname;
    $values["Registrant"]["Organisation Name"] = $Joker->getValue("domain.organization");
    $values["Registrant"]["Job Title"] = "";
    $values["Registrant"]["Email"] = $Joker->getValue("domain.email");
    $values["Registrant"]["Address 1"] = $Joker->getValue("domain.address-1");
    $values["Registrant"]["Address 2"] = $Joker->getValue("domain.address-2");
    $values["Registrant"]["City"] = $Joker->getValue("domain.city");
    $values["Registrant"]["State"] = $Joker->getValue("domain.state");
    $values["Registrant"]["Postcode"] = $Joker->getValue("domain.postal-code");
    $values["Registrant"]["Country"] = $Joker->getValue("domain.country");
    $values["Registrant"]["Phone"] = $Joker->getValue("domain.phone");
    $values["Registrant"]["Fax"] = $Joker->getValue("domain.fax");

    // Don't allow to change admin, tech and billing contact for now
    /*
    $contacts = array(
        "Admin" => $Joker->getValue("domain.admin-c"),
        "Tech" => $Joker->getValue("domain.tech-c"),
        "Billing" => $Joker->getValue("domain.billing-c")
    );

    foreach($contacts as $type => $handle) {
        $reqParams = Array();
        $reqParams["contact"] = $handle;
        $Joker->ExecuteAction('query-whois', $reqParams);

        if ($Joker->hasError()) {
            //$values["error"] = $Joker->getError();
            continue;
        }

        $names = explode(" ", $Joker->getValue("contact.name"));
        $lastname = array_pop($names);
        $firstname = implode(" ", $names);
        $values[$type]["First Name"] = $firstname;
        $values[$type]["Last Name"] = $lastname;
        $values[$type]["Organisation Name"] = $Joker->getValue("contact.organization");
        $values[$type]["Job Title"] = "";
        $values[$type]["Email"] = $Joker->getValue("contact.email");
        $values[$type]["Address 1"] = $Joker->getValue("contact.address-1");
        $values[$type]["Address 2"] = $Joker->getValue("contact.address-2");
        $values[$type]["City"] = $Joker->getValue("contact.city");
        $values[$type]["State"] = $Joker->getValue("contact.state");
        $values[$type]["Postcode"] = $Joker->getValue("contact.postal-code");
        $values[$type]["Country"] = $Joker->getValue("contact.country");
        $values[$type]["Phone"] = $Joker->getValue("contact.phone");
        $values[$type]["Fax"] = $Joker->getValue("contact.fax");
    }
    */

    return $values;
}

function joker_GetRegistrantContactEmailAddress(array $params)
{
    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
    $reqParams["internal"] = 1;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('query-whois', $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
        return $values;
    }

    $values['registrantEmail'] = $Joker->getValue('domain.email');
    return $values;

}

function joker_SaveContactDetails($params) {
    $params = injectDomainObjectIfNecessary($params);
    $params = joker_CleanupContactDetails($params);
    
    $errorMsgs = array();
    
    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
    $reqParams["fax"] = $params["contactdetails"]["Registrant"]["Fax"];
    $reqParams["phone"] = $params["contactdetails"]["Registrant"]["Phone"];
    $reqParams["country"] = $params["contactdetails"]["Registrant"]["Country"];
    $reqParams["postal-code"] = $params["contactdetails"]["Registrant"]["Postcode"];
    $reqParams["state"] = $params["contactdetails"]["Registrant"]["State"];
    $reqParams["city"] = $params["contactdetails"]["Registrant"]["City"];
    $reqParams["email"] = $params["contactdetails"]["Registrant"]["Email"];
    $reqParams["address-1"] = $params["contactdetails"]["Registrant"]["Address 1"];
    $reqParams["address-2"] = $params["contactdetails"]["Registrant"]["Address 2"];
    //$reqParams["title"] = $params["contactdetails"]["Registrant"]["Job Title"];
    $reqParams["name"] = $params["contactdetails"]["Registrant"]["First Name"].' '.$params["contactdetails"]["Registrant"]["Last Name"];
    $reqParams["organization"] = $params["contactdetails"]["Registrant"]["Organisation Name"];

    if ($params['original']['domainObj']->getLastTLDSegment() == 'us') {

        $nexus = $params["additionalfields"]['Nexus Category'];
        $countrycode = $params["additionalfields"]['Nexus Country'];
        $purpose = $params["additionalfields"]['Application Purpose'];

        if ($purpose=="Business use for profit") {
            $purpose="P1";
        } elseif ($purpose=="Non-profit business") {
            $purpose="P2";
        } elseif ($purpose=="Club") {
            $purpose="P2";
        } elseif ($purpose=="Association") {
            $purpose="P2";
        } elseif ($purpose=="Religious Organization") {
            $purpose="P2";
        } elseif ($purpose=="Personal Use") {
            $purpose="P3";
        } elseif ($purpose=="Educational purposes") {
            $purpose="P4";
        } elseif ($purpose=="Government purposes") {
            $purpose="P5";
        }

        switch ($nexus){
            case 'C11':
            case 'C12':
            case 'C21':
                $reqParams["nexus-category"] = $nexus;
                break;
            case 'C31':
            case 'C32':
                $reqParams["nexus-category"] = $nexus;
                $reqParams["nexus-category-country"] = $countrycode;
                break;
        }
        $reqParams["app-purpose"] = $purpose;

    } elseif ($params['original']['domainObj']->getLastTLDSegment() == 'uk') {

        if ($params["additionalfields"]['Legal Type']=="UK Limited Company") {
            $uklegaltype="LTD";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Public Limited Company") {
            $uklegaltype="PLC";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Partnership") {
            $uklegaltype="PTNR";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Limited Liability Partnership") {
            $uklegaltype="LLP";
        } elseif ($params["additionalfields"]['Legal Type']=="Sole Trader") {
            $uklegaltype="STRA";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Registered Charity") {
            $uklegaltype="RCHAR";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Industrial/Provident Registered Company") {
            $uklegaltype="IP";
        } elseif ($params["additionalfields"]['Legal Type']=="UK School") {
            $uklegaltype="SCH";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Government Body") {
            $uklegaltype="GOV";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Corporation by Royal Charter") {
            $uklegaltype="CRC";
        } elseif ($params["additionalfields"]['Legal Type']=="UK Statutory Body") {
            $uklegaltype="STAT";
        } elseif ($params["additionalfields"]['Legal Type']=="Non-UK Individual") {
            $uklegaltype="FIND";
        } elseif ($params["additionalfields"]['Legal Type']=="Foreign Organization") {
            $uklegaltype="CORP";
        } elseif ($params["additionalfields"]['Legal Type']=="Other foreign organizations") {
            $uklegaltype="FOTHER";
        } else {
            $uklegaltype="IND";
        }
        $reqParams["account-type"] = $uklegaltype;
        $reqParams["company-number"] = $params["additionalfields"]['Company ID Number'];

    } elseif ($params['original']['domainObj']->getLastTLDSegment() == 'eu') {
        $reqParams["lang"] = "EN";
    }

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("domain-owner-change", $reqParams);

    if ($Joker->hasError()) {
        $errorMsgs[] = "Registrant: ".$Joker->getError();
    }

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
    $reqParams["internal"] = 1;
    $Joker->ExecuteAction('query-whois', $reqParams);

    if ($Joker->hasError()) {
        $errorMsgs[] = "Domain Info: ".$Joker->getError();
    } else {

        $contacts = array(
            "Admin" => $Joker->getValue("domain.admin-c"),
            "Tech" => $Joker->getValue("domain.tech-c"),
            "Billing" => $Joker->getValue("domain.billing-c")
        );

        foreach($contacts as $type => $handle) {
            if (isset($params["contactdetails"][$type])) {
                $phonenumber = $params["contactdetails"][$type]["Phone"];
                $country = $params["contactdetails"][$type]["Country"];
                $phoneprefix = $countrycallingcodes[$country];
                if ((substr($phonenumber,0,1)!="+") && ($phoneprefix)) {
                    $params["contactdetails"][$type]["Phone"] = "+".$phoneprefix.".".$phonenumber;
                }
                $reqParams = Array();
                $reqParams["handle"] = $handle;
                $reqParams["fax"] = $params["contactdetails"][$type]["Fax"];
                $reqParams["phone"] = $params["contactdetails"][$type]["Phone"];
                $reqParams["country"] = $params["contactdetails"][$type]["Country"];
                $reqParams["postal-code"] = $params["contactdetails"][$type]["Postcode"];
                $reqParams["state"] = $params["contactdetails"][$type]["State"];
                $reqParams["city"] = $params["contactdetails"][$type]["City"];
                $reqParams["email"] = $params["contactdetails"][$type]["Email"];
                $reqParams["address-1"] = $params["contactdetails"][$type]["Address 1"];
                $reqParams["address-2"] = $params["contactdetails"][$type]["Address 2"];
                //$reqParams["title"] = $params["contactdetails"][$type]["Job Title"];
                $reqParams["name"] = $params["contactdetails"][$type]["First Name"].' '.$params["contactdetails"][$type]["Last Name"];
                $reqParams["organization"] = $params["contactdetails"][$type]["Organisation Name"];
                if ($params['original']['domainObj']->getLastTLDSegment() == 'eu') {
                    $reqParams["lang"] = "EN";
                }
                $Joker->ExecuteAction('contact-modify', $reqParams);
                if ($Joker->hasError()) {
                    $errorMsgs[] = "$type: ".$Joker->getError();
                }
            }
        }
    }

    $values["error"] = implode(', ', $errorMsgs);

    return $values;

}

/*
function joker_GetEPPCode($params) {

    // Need to integrate flag in DMAPI send the AuthCode to the registrant's email, instead of showing it in the DMAPI results

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;
 
    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("domain-transfer-get-auth-id", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
    }

    return $values;

}
*/

function joker_FetchEPPCodeClient($params) {

    $values = array(
        'templatefile' => 'eppcode',
        'vars' => joker_FetchEPPCode($params),
        'breadcrumb' => array( 'clientarea.php?action=domaindetails&domainid='.$params['domainid'].'&modop=custom&a=FetchEPPCode' => 'EPP Code' )
    );
    
    return $values;
}

function joker_FetchEPPCode($params) {

    $values = array();

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $reqParams = Array();
    $reqParams["domain"] = $idn_domain;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction("domain-transfer-get-auth-id", $reqParams);

    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
        return $values;
    }

    $reqParams = Array();
    $reqParams["rtype"] = "domain-transfer-get-auth-id";
    $reqParams["objid"] = $idn_domain;
    $reqParams["showall"] = 1;
    $reqParams["pending"] = 1;
    $reqParams["limit"] = 1;
    
    $Joker->ExecuteAction('result-list', $reqParams);

    $procid = false;
    if ($Joker->hasError()) {
        $values["error"] = $Joker->getError();
    } elseif ($Joker->getHeaderValue('Row-Count') > 0) {
        $resultList = $Joker->getResponseList();
        $procid = $resultList[0][2];
    } else {
        $values['error'] = "EPP Code request not found. Please try again.";
    }

    if ($procid) {
        $timeout = 30; //seconds

        $authid = false; $error = false;

        $start_time = time();
        while (!$error && !$authid && ($start_time + $timeout) >= time()) {
            $reqParams = Array();
            $reqParams["Proc-ID"] = $procid;
            $rawMsg = $Joker->ExecuteAction("result-retrieve", $reqParams);
            if ($Joker->hasError()) {
                $values["error"] = "EPP-Code: ".$Joker->getError();
                $error = true;
            }

            if ($Joker->getValue("Completion-Status") == "ack") {
                $matches = array();
                if (preg_match('/^The Authorization ID is: "([^"]+)"/m', $rawMsg,$matches)) {
                    $authid = $matches[1];
                    $values["eppcode"] = $authid;
                    $values["message"] = "The Epp-Code is: ".$authid;
                } else {
                    $error = true;
                    $values["error"] = "EPP-Code: not found";
                }
            }
            if ($Joker->getValue("Completion-Status") == 'nack') {
                $values["error"] = "EPP-Code: retrieval failed";
                $error = true;
            }
            if (!$error && !$authid) {
                usleep(500);
            }
        }
    }

    if (!$error && !$authid) {
        $values["error"] = "EPP Code: retrieval timeout";
    }
    return $values;
}


function joker_RegisterNameserver($params) {

    $params = injectDomainObjectIfNecessary($params);

    $reqParams = Array();
    $reqParams["host"] = $params["nameserver"];
    $reqParams["ip"] = $params["ipaddress"];

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('ns-create', $reqParams);

    if ($Joker->hasError()) {
        $error = $Joker->getError();
    }

    $values["error"] = $error;

    return $values;

}

function joker_ModifyNameserver($params) {

    $params = injectDomainObjectIfNecessary($params);

    $reqParams = Array();
    $reqParams["host"] = $params["nameserver"];
    //$reqParams["old_ip"] = $params["currentipaddress"];
    $reqParams["ip"] = $params["newipaddress"];

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('ns-modify', $reqParams);

    if ($Joker->hasError()) {
        $error = $Joker->getError();
    }

    $values["error"] = $error;

    return $values;

}

function joker_DeleteNameserver($params) {

    $params = injectDomainObjectIfNecessary($params);

    $reqParams = Array();
    $reqParams["host"] = $params["nameserver"];

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('ns-delete', $reqParams);

    if ($Joker->hasError()) {
        $error = $Joker->getError();
    }
    $values["error"] = $error;

    return $values;

}

function joker_ManageDNSSEC($params,$fields) {
    $successful = false;
    $error = false;
    $configured = false;
    $record_added = false;
    $records = array();

    $params = injectDomainObjectIfNecessary($params);
    $idn_domain = $params['original']['domainObj']->getDomain(true);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' || isset($_POST['refresh'])) {
        // Load DNSSEC data currently not possible
        $Joker = DMAPIClient::getInstance($params);
        $Joker->ExecuteAction('whois', array('domain'=>$idn_domain,'disclaimer'=>0),'get');
        if (!$Joker->hasError()) {
            $configured = ($Joker->getValue("DNSSEC") == "signedDelegation");
        } else {
            print $Joker->getError();
        }
    } elseif (isset($_POST['removeRecord'])) {
        $records = $_POST['records'];
        unset($records[$_POST['removeRecord']]);
    } elseif (isset($_POST['addRecord'])) {
        $records = $_POST['records'];
        if (count($records)>=6) {
            $error = 'You cannot add more than 6 records';
        } else {
            $newRecord = array();
            foreach($fields as $field) {
                $newRecord[$field] = isset($_POST[$field])?$_POST[$field]:'';
            }
            $records[] = $newRecord;
            $record_added = true;
        }
    } elseif (isset($_POST['save'])||isset($_POST['deactivate'])) {
        $records = isset($_POST['records'])?$_POST['records']:array();
        $reqParams = Array();
        $reqParams["domain"] = $idn_domain;
        $reqParams["dnssec"] = 0;
        if (!empty($records) && is_array($records)) {
            $reqParams["dnssec"] = 1;
            $set = 0;
            foreach($records as $record) {
                if (++$set > 6) { break; }
                if (isset($record['pubkey'])) {
                    $record['pubkey'] = str_replace(array(" ","\n","\r","\t"),"",$record['pubkey']);
                }
                if (isset($record['digest'])) {
                    $record['digest'] = str_replace(array(" ","\n","\r","\t"),"",$record['digest']);
                }
                $reqParams["ds-$set"] = implode(':',array_replace(array_flip($fields), $record));
            }
        }
        $Joker = DMAPIClient::getInstance($params);
        $Joker->ExecuteAction('domain-modify', $reqParams);
    
        if ($Joker->hasError()) {
            $error = $Joker->getError();
        } else {
            $successful = true;
        }
    }

    $values = array(
        'successful' => $successful,
        'configured' => $configured,
        'recordslist' => $records,
        'record_added' => $record_added
    );
    if ($error) {
        $values['error'] = $error;
    }
    
    return $values;
}

function joker_ManageDNSSEC_DS($params) {
    global $_LANG;
    $vars = joker_ManageDNSSEC(
            $params,
            array("keyTag","alg","digestType","digest")
    );
    $values = array(
        'templatefile' => "dnssec_ds",
        'vars' => $vars,
        'breadcrumb' => array( 'clientarea.php?action=domaindetails&domainid='.$params['domainid'].'&modop=custom&a=ManageDNSSEC_DS' => $_LANG['dnssec']['ds_page_title'] )
    );
    return $values;
}

function joker_ManageDNSSEC_KD($params) {
    global $_LANG;
    
    $vars = joker_ManageDNSSEC(
        $params,
        array("protocol","alg","flags","pubkey")
    );

    $values = array(
        'templatefile' => 'dnssec_kd',
        'vars' => $vars,
        'breadcrumb' => array( 'clientarea.php?action=domaindetails&domainid='.$params['domainid'].'&modop=custom&a=ManageDNSSEC_KD' => $_LANG['dnssec']['kd_page_title'] )
    );

    return $values;
}

function joker_IDProtectToggle($params)
{
    $values = array();
    $params = injectDomainObjectIfNecessary($params);
    $idn_domain = $params['original']['domainObj']->getDomain(true);

    // id protection parameter
    $protectEnable = (bool) $params['protectenable'];
    $buyPrivacy = false;
    $currentPrivacyStatus = false;
    
    $reqParams = Array();
    $reqParams["pattern"] = $idn_domain;
    $reqParams["showprivacy"] = 1;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('query-domain-list', $reqParams);

    if ($Joker->hasError()) {
        $values['error'] = $Joker->getError();
        return $values;
    }

    $resultList = $Joker->getResponseList();
    if (count($resultList) > 0) {
        $buyPrivacy = ($resultList[0]['privacy-origin'] === 'off');
        $currentPrivacyStatus = ($resultList[0]['privacy-status'] !== 'off');
    } else {
        $values['error'] = "Domain not found";
        return $values;
    }
    
    if ($buyPrivacy) {
        $reqParams = Array();
        $reqParams["domain"] = $idn_domain;
        $reqParams["pname"] = "privacy";
        $reqParams["privacy"] = "pro";
        $Joker->ExecuteAction('domain-privacy-order', $reqParams);
        if ($Joker->hasError()) {
            $values['error'] = $Joker->getError();
        }
    } elseif ($currentPrivacyStatus!=$protectEnable) {
        $reqParams = Array();
        $reqParams["domain"] = $idn_domain;
        $reqParams["pname"] = "privacy";
        $reqParams["pvalue"] = $protectEnable?"pro":"off";
        $Joker->ExecuteAction('domain-set-property', $reqParams);
        if ($Joker->hasError()) {
            $values['error'] = $Joker->getError();
        }
    }
    return $values;

}

/**
 * Check Domain Availability.
 *
 * Determine if a domain or group of domains are available for
 * registration or transfer.
 *
 * @param array $params common module parameters
 * @see https://developers.whmcs.com/domain-registrars/module-parameters/
 *
 * @see \WHMCS\Domains\DomainLookup\SearchResult
 * @see \WHMCS\Domains\DomainLookup\ResultsList
 *
 * @throws Exception Upon domain availability check failure.
 *
 * @return \WHMCS\Domains\DomainLookup\ResultsList An ArrayObject based collection of \WHMCS\Domains\DomainLookup\SearchResult results
 */


function joker_CheckAvailability($params)
{
    // availability check parameters
    $searchTerm = $params['searchTerm'];
    $punyCodeSearchTerm = $params['punyCodeSearchTerm'];
    $tldsToInclude = $params['tldsToInclude'];
    $isIdnDomain = (bool) $params['isIdnDomain'];
    $premiumEnabled = (bool) $params['premiumEnabled'];

    $error = "";
    
    $results = new ResultsList();

    foreach($tldsToInclude as $tld) {
        $Joker = DMAPIClient::getInstance($params);
        $domain = $searchTerm.$tld;
        $reqParams = Array("domain" => $domain);
        $Joker->ExecuteAction('domaincheck', $reqParams);
        if ($Joker->hasError()) {
            $error = $Joker->getError();
            continue;
        }
        $searchResult = new SearchResult($searchTerm, $tld);
        $status_row = $Joker->getValue('domain-status');
        $status_arr = explode(':',$status_row,2);
        $status_text = $status_arr[0];
        $reason = count($status_arr)>1?trim($status_arr[1]):'';
        switch($status_text) {
            case 'free':
            case 'available':
                $status = SearchResult::STATUS_NOT_REGISTERED;
                break;
            default:
            case 'unavailable':
                $status=SearchResult::STATUS_REGISTERED;
                break;
        }
        $searchResult->setStatus($status);
        // Return premium information if applicable
        /*
        if ($domain['isPremiumName']) {
            $searchResult->setPremiumDomain(true);
            $searchResult->setPremiumCostPricing(
                array(
                    'register' => $domain['premiumRegistrationPrice'],
                    'renew' => $domain['premiumRenewPrice'],
                    'CurrencyCode' => 'USD',
                )
            );
        }
        */
        $results->append($searchResult);
    }

    return $results;
}

function joker_SyncManual($params) {
$params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);
    $tld = $params['original']['domainObj']->getTopLevel();


    $reqParams = Array();
    $reqParams["pattern"] = $idn_domain;
    //$reqParams["showstatus"] = 1;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('query-domain-list', $reqParams);

    if ($Joker->hasError()) {
        $values['error'] = $Joker->getError();
    }

    $resultList = $Joker->getResponseList();

    if (count($resultList) > 0) {
        //$status = explode(",",$resultList[0]['domain_status']);
        $values['expirydate'] = JokerHelper::fixExpirationDate($resultList[0]['expiration_date'], $tld);
        if ($params['SyncNextDueDate']) {
            $values['nextduedate'] = $values['expirydate'];
        }
        $expDate = new DateTime($values['expirydate'],new DateTimeZone('UTC'));
        $now = new DateTime(null,new DateTimeZone('UTC'));
        if ($expDate > $now) {
            $values['status'] = "Active";
        } else {
            $values['status'] = "Expired";
        }
    } else {
            $reqParams = Array();
            $reqParams["rtype"] = "domain-r*";
            $reqParams["objid"] = $idn_domain;
            $reqParams["showall"] = 1;
            $reqParams["pending"] = 1;
            $reqParams["limit"] = 1;
            $reqParams["period"] = 1;

            $Joker = DMAPIClient::getInstance($params);
            $Joker->ExecuteAction('result-list', $reqParams);

            if ($Joker->hasError()) {
                $values['error'] = $Joker->getError();
            } elseif ($Joker->getHeaderValue('Row-Count') > 0) {
                $resultList = $Joker->getResponseList();
                $status = $resultList[0][5];
                if ($status == "nack") {
                    $values['status'] = "Cancelled";
                }
            } else {
                $values['error'] = "Domain/Order not found";
            }
    }
    if (!isset($values['error'])) {
        $values['domainid'] = $params['domainid'];
        localAPI('updateclientdomain', $values, $params['AdminUser']);
        $values['message'] = '(Warning) You must refresh page to see the changes';
    }
    return $values;
}

function joker_Sync($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['domainObj']->getDomain(true);
    $tld = $params['original']['domainObj']->getTopLevel();

    $reqParams = Array();
    $reqParams["pattern"] = $idn_domain;
    //$reqParams["showstatus"] = 1;
    

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('query-domain-list', $reqParams);
    
    if ($Joker->hasError()) {
        $values['error'] = $Joker->getError();
    }


    $resultList = $Joker->getResponseList();

    if (count($resultList) > 0) {
        //$status = explode(",",$resultList[0]['domain_status']);
        $values['expirydate'] = JokerHelper::fixExpirationDate($resultList[0]['expiration_date'], $tld);
        if ($params['SyncNextDueDate']) {
            $values['nextduedate'] = $values['expirydate'];
        }
        $expDate = new DateTime($values['expirydate'],new DateTimeZone('UTC'));
        $now = new DateTime(null,new DateTimeZone('UTC'));
        if ($expDate > $now) {
            $values['active'] = true;
        } else {
            $values['expired'] = true;
        }
    } else {
        $reqParams = Array();
        $reqParams["rtype"] = "domain-r*";
        $reqParams["objid"] = $idn_domain;
        $reqParams["showall"] = 1;
        $reqParams["pending"] = 1;
        $reqParams["limit"] = 1;
        $reqParams["period"] = 1;

        $Joker = DMAPIClient::getInstance($params);
        $Joker->ExecuteAction('result-list', $reqParams);

        if ($Joker->hasError()) {
            $values['error'] = $Joker->getError();
        } elseif ($Joker->getHeaderValue('Row-Count') > 0) {
            $resultList = $Joker->getResponseList();
            $status = $resultList[0][5];
            if ($status == "nack") {
                $values['cancelled'] = true;
            }
        } else {
            $values['error'] = "Domain/Order not found";
        }
    }
    return $values;

}


function joker_TransferSync($params){

    $params = injectDomainObjectIfNecessary($params);

    $values = array();

    $idn_domain = $params['domainObj']->getDomain(true);
    $tld = $params['original']['domainObj']->getTopLevel();

    
    $reqParams = Array();
    $reqParams["rtype"] = "domain-transfer-in-reseller";
    $reqParams["objid"] = $idn_domain;
    $reqParams["showall"] = 1;
    $reqParams["pending"] = 1;
    $reqParams["limit"] = 1;

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('result-list', $reqParams);

    if ($Joker->hasError()) {
        $values['error'] = $Joker->getError();
    } elseif ($Joker->getHeaderValue('Row-Count') > 0) {
        $resultList = $Joker->getResponseList();
        $status = $resultList[0][5];
        switch($status) {
            case "pending":
                    $values['pendingtransfer'] = true;
                    $values['reason'] = "";
                break;
            case "ack":
                $values['completed'] = true;
                $reqParams = Array();
                $reqParams["pattern"] = $idn_domain;
                $Joker->ExecuteAction('query-domain-list', $reqParams);
                if (!$Joker->hasError()) {
                    $resultList = $Joker->getResponseList();
                    if (count($resultList) > 0) {
                        $values['expirydate'] = JokerHelper::fixExpirationDate($resultList[0]['expiration_date'], $tld);
                    }
                }
                break;
            case "nack":
                $values['failed'] = true;
                $values['reason'] = "";
                break;
        }

    } else {
        $values['error'] = "Domain not found";
    }

    return $values;

}

function joker_CleanupContactDetails($params) {

    $contacttypes = array("Registrant", "Admin", "Tech", "Billing");
    foreach ($contacttypes as $ctype) {
        if (isset($params["contactdetails"][$ctype]["Country"])) {
            $country = $params["contactdetails"][$ctype]["Country"];
            if ($country == 'CA') {
                $params["contactdetails"][$ctype]["Postcode"] = preg_replace('/\s/', '', $params["contactdetails"][$ctype]["Postcode"]);
            }
        }
    }

    if (isset($params["contactdetails"]["Registrant"]["Phone"])) {
        // import $countrycallingcodes from WHMCS includes
        if (file_exists(ROOTDIR.'/includes/countriescallingcodes.php')) {
            require_once (ROOTDIR."/includes/countriescallingcodes.php");

            $country = $params["contactdetails"]["Registrant"]["Country"];
            $phoneprefix = $countrycallingcodes[$country];
            $phonenumber = $params["contactdetails"]["Registrant"]["Phone"];
            if ((substr($phonenumber,0,1)!="+") && ($phoneprefix)) {
                $params["contactdetails"]["Registrant"]["Phone"] = "+".$phoneprefix.".".ltrim($phonenumber,'0');
            }
        }
    }

    if (isset($params["country"]) && $params["country"] == 'NL') {
        $modifyKeys = array('fullstate', 'state', 'statecode', 'adminfullstate', 'adminstate');
        foreach ($modifyKeys as $key) {
            $params[$key] = str_replace('-', '', $params[$key]);
        }
    }

    if (isset($params["country"]) && $params["country"] == 'CA') {
        $params["postcode"] = preg_replace('/\s/', '', $params["postcode"]);
    }

    if (isset($params["admincountry"]) && $params["admincountry"] == 'CA') {
        $params["adminpostcode"] = preg_replace('/\s/', '', $params["adminpostcode"]);
    }

    return $params;
}

function joker_ClientAreaCustomButtonArray($params) {
    
    global $_LANG;
    
    $params = injectDomainObjectIfNecessary($params);
    $tld = $params['original']['domainObj']->getTopLevel();

    $buttonarray = array();
    $buttonarray["EPP Code"] = "FetchEPPCodeClient";

    $Joker = DMAPIClient::getInstance($params);
    $Joker->ExecuteAction('inquire-tld', array('tld' => $tld), 'get');
    
    if (!$Joker->hasError()) {
        $dnssec = $Joker->getValue('dnssec');
        if (strpos($dnssec,'keydata')!==false) {
            $buttonarray[$_LANG['dnssec']['kd_page_title']] = "ManageDNSSEC_KD";
        } elseif (strpos($dnssec,'dsdata')!==false) {
            $buttonarray[$_LANG['dnssec']['ds_page_title']] = "ManageDNSSEC_DS";
        }
    }
    return $buttonarray;
}

function joker_AdminCustomButtonArray($params) {

    $buttonarray = array();
    $buttonarray["EPP Code"] = "FetchEPPCode";
    $buttonarray["Sync"] = "SyncManual";
    return $buttonarray;
}
