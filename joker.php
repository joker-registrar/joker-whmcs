<?php
/*
  ****************************************************************************
  *                                                                          *
  * Joker.com WHMCS Registrar Module                                         *
  * Version 1.0.0                                                            *
  *                                                                          *
  *                                                                          *
  * The MIT License (MIT)                                                    *
  * Copyright (c) 2016 Joker.com                                             *
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
  * To install, create a folder named joker under                            *
  * modules/registrar under your whmcs root directory and place              *
  * joker.php, eppcode.tpl logo.gif into it.                                 *
  * Then in WHMCS admin menu, go to registrar module settings and select     *
  * Joker, and configure.                                                    *
  ****************************************************************************
*/


function joker_getConfigArray() {
$configarray = array(
 "Description" => array("Type" => "System", "Value"=>"Don't have an Joker Account yet? Get one at: <a href=\"http://joker.com/\" target=\"_blank\">http://joker.com/</a>"),
 "Username" => array( "Type" => "text", "Size" => "20", "Description" => "Enter your Joker Reseller Account Username here", ),
 "Password" => array( "Type" => "password", "Size" => "20", "Description" => "Enter your Joker Reseller Account Password here", ),
 "TestMode" => array( "Type" => "yesno", "Description" => "Tick this box to use the Joker OT&E system: <a href=\"http://www.ote.joker.com/\" target=\"_blank\">http://www.ote.joker.com/</a>"),
 "DefaultNameservers" => array( "Type" => "yesno", "Description" => "Tick this box to use the default Joker nameservers for new domain registrations", ),
);

return $configarray;

}

function joker_GetNameservers($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->NewRequest();
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->DoTransaction('query-whois', $params);

    $values = array();
    $nameservers = $Joker->getValue('domain.nservers.nserver.handle');
    for ($i = 1; $i <= 12; $i++) {
        $values["ns".$i] = isset($nameservers[$i-1]) ? $nameservers[$i-1] : '';
    }

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
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

    $Joker = new CJokerInterface;
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->AddParam( "ns-list", implode(":", $nameserverList ));
    $Joker->DoTransaction('domain-modify', $params);

    $values = array();
    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
    }

    return $values;

}

function joker_GetRegistrarLock($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "pattern", $idn_domain );
    $Joker->AddParam( "showstatus", 1 );
    $Joker->DoTransaction('query-domain-list',$params);

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

    $Joker = new CJokerInterface;
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->DoTransaction($command, $params);

    $values = array();
    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
    }
    return $values;
}

function joker_GetEmailForwarding($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->DoTransaction("dns-zone-get",$params);
    
    $values = array();

    if (!$Joker->getValue("Err1")) {
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
        $values["error"] = $Joker->getValue("Err1");
    }

    return $values;
}

function joker_SaveEmailForwarding($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->DoTransaction("dns-zone-get",$params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
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
            $dnsrecords[] = $params["prefix"][$key] . " MAILFW 0 " . $params["forwardto"][$key] . " 86400 0 0 0";
        }
    }

    $Joker->NewRequest();
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->AddParam( "zone", implode("\n", $dnsrecords) );
    $Joker->DoTransaction("dns-zone-put", $params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
    }

    return $values;

}

function joker_GetDNS($params) {

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->DoTransaction("dns-zone-get",$params);

    $hostRecords = array();

    if (!$Joker->getValue("Err1")) {
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

    $Joker = new CJokerInterface;
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->DoTransaction("dns-zone-get",$params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
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

    $Joker->NewRequest();
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->AddParam( "zone", implode("\n", $dnsrecords) );
    $Joker->DoTransaction("dns-zone-put", $params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
    }

    return $values;

}

function joker_RegisterDomain($params) {

    $owner_result = joker_CreateOwnerContact($params);

    if (isset($owner_result['error']) && $owner_result['error']) {
        return $owner_result;
    }
    $admin_result = joker_CreateAdminContact($params);
    if (isset($admin_result['error']) && $admin_result['error']) {
        return $admin_result;
    }

    $params = injectDomainObjectIfNecessary($params);

    $Joker = new CJokerInterface;
    $Joker->NewRequest();

    $idn_domain = $params['domainObj']->getDomain(true);

    //#################################################################################################################
    //# IDN fix for Swedish language only. Otherwise language will be guessed by Joker depending on registrant country#
    if ($params['domainObj']->isIdn() && $params['language'] == 'swedish') {
        if (($params["tld"] == "co") || ($params["tld"] == "biz") || ($params["tld"] == "tel")){
            $Joker->AddParam("language", "se");
        } elseif (($params["tld"] == "com") || ($params["tld"] == "net") || ($params["tld"] == "li") || ($params["tld"] == "fr") || ($params["tld"] == "ch") || ($params["tld"] == "sg") || ($params["tld"] == "com.sg") || ($params["tld"] == "tv") || ($params["tld"] == "co.uk")){
            $Joker->AddParam("language", "swe");
        } else {
            $Joker->AddParam("language", "sv");
        }
    }
    //# END IDN FIX
    //#################################################################################################################

    $Joker->AddParam("domain", $idn_domain );
    $Joker->AddParam("period", $params["regperiod"]*12 );
    $Joker->AddParam("status", "production" );
    $Joker->AddParam("owner-c", $owner_result['handle']);
    $Joker->AddParam("admin-c", $admin_result['handle']);
    $Joker->AddParam("tech-c", $admin_result['handle']);
    $Joker->AddParam("billing-c", $admin_result['handle']);


    if ($params["DefaultNameservers"]) {
        $Joker->AddParam( "ns-list", "a.ns.joker.com:b.ns.joker.com:c.ns.joker.com");
    } else {
        $nslist = array();
        for ($i=1;$i<=5;$i++) {
            if (isset($params["ns$i"]) && !empty($params["ns$i"])) {
                $nslist[] = $params["ns$i"];
            }
        }
        $Joker->AddParam( "ns-list", implode(':',$nslist) );
    }

    if (isset($params["idprotection"]) && $params["idprotection"]) {
        $Joker->AddParam( "privacy", "pro" );
    }

    $Joker->DoTransaction("domain-register",$params);

    $values["error"] = $Joker->getValue("Err1");

    return $values;

}

function joker_TransferDomain($params) {

    $owner_result = joker_CreateOwnerContact($params);

    if (isset($owner_result['error']) && $owner_result['error']) {
        return $owner_result;
    }

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->NewRequest();
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->AddParam( "transfer-auth-id", $params["transfersecret"] );
    $Joker->AddParam( "owner-c", $owner_result['handle']);
    $Joker->AddParam( "admin-c", $owner_result['handle']);
    $Joker->AddParam( "tech-c", $owner_result['handle']);
    $Joker->AddParam( "billing-c", $owner_result['handle']);


    $nslist = array();
    for ($i=1;$i<=5;$i++) {
        if (isset($params["ns$i"]) && !empty($params["ns$i"])) {
            $nslist[] = $params["ns$i"];
        }
    }
    if (count($nslist)>0) {
        $Joker->AddParam( "ns-list", implode(':',$nslist) );
    }


    if (isset($params["idprotection"]) && $params["idprotection"]) {
        $Joker->AddParam( "privacy", "pro" );
    }
    $Joker->DoTransaction("domain-transfer-in-reseller", $params);

    $values["error"] = $Joker->getValue("Err1");

    return $values;

}

function joker_RenewDomain($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "pattern", $idn_domain );
    //$Joker->AddParam( "showstatus", 1 );
    $Joker->DoTransaction('query-domain-list',$params);

    if ($Joker->getValue("Err1")) {
        $values['error'] = $Joker->getValue("Err1");
        return $values;
    }

    $resultList = $Joker->getResponseList();

    if (count($resultList) > 0) {
        //$status = explode(",",$resultList[0]['domain_status']);
        //$expirationdate = $resultList[0]['expiration_date'];
        // TODO: Check if domain is in redemption
        $restore = false;
    } else {
        $restore = true;
    }

    if (!$restore) {
        $Joker->NewRequest();
        $Joker->AddParam( "domain", $idn_domain );
        $Joker->AddParam( "period", $params["regperiod"]*12 );
        if ($params["idprotection"]) {
            $Joker->AddParam( "privacy" , "keep");
        }
        $Joker->DoTransaction("domain-renew",$params);
    } else {
        // TODO: Add Privacy. What about additional domain years?
        $Joker->NewRequest();
        $Joker->AddParam( "domain", $idn_domain );
        $Joker->DoTransaction("domain-redeem",$params);
    }

    if ($Joker->getValue("Err1")) {
        $values['error'] = $Joker->getValue("Err1");
    }

    return $values;

}

function joker_CreateOwnerContact($params) {
    $params = injectDomainObjectIfNecessary($params);
    $params = joker_NormalizeContactDetails($params);

    $errorMsgs = array();

    $Joker = new CJokerInterface;

    $Joker->NewRequest();
    $Joker->AddParam( "tld", $params["tld"] );
    //$Joker->AddParam( "fax", "" );
    $Joker->AddParam( "phone", $params["fullphonenumber"] );
    $Joker->AddParam( "country", $params["country"] );
    $Joker->AddParam( "postal-code", $params["postcode"] );
    $Joker->AddParam( "state", $params["state"] );
    $Joker->AddParam( "city", $params["city"] );
    $Joker->AddParam( "email", $params["email"] );
    $Joker->AddParam( "address-1", $params["address1"] );
    $Joker->AddParam( "address-2", $params["address2"] );
    $Joker->AddParam( "name", $params["firstname"].' '.$params["lastname"] );
    $Joker->AddParam( "organization", $params["companyname"] );

    if ($params['domainObj']->getLastTLDSegment() == 'us') {

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
                $Joker->AddParam( "nexus-category", $nexus );
                break;
            case 'C31':
            case 'C32':
                $Joker->AddParam( "nexus-category", $nexus );
                $Joker->AddParam( "nexus-category-country", $countrycode);
                break;
        }
        $Joker->AddParam( "app-purpose", $purpose );

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
        $Joker->AddParam( "account-type", $uklegaltype );
        $Joker->AddParam( "company-number", $params["additionalfields"]['Company ID Number'] );

    } elseif ($params['domainObj']->getLastTLDSegment() == 'eu') {
        $Joker->AddParam( "lang", "EN" );
    }
    $Joker->DoTransaction("contact-create", $params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = "Registrant: ".$Joker->getValue("Err1");
        return $values;
    }

    $procid = $Joker->getHeaderValue("Proc-ID");

    $timeout = 30; //seconds

    $handle = false; $error = false;

    $start_time = time();
    while (!$error && !$handle && ($start_time + $timeout) >= time()) {
        $Joker->NewRequest();
        $Joker->AddParam("Proc-ID", $procid);
        $Joker->DoTransaction("result-retrieve", $params);

        if ($Joker->getValue("Err1")) {
            $values["error"] = "Registrant: ".$Joker->getValue("Err1");
            $error = true;
        }

        if ($Joker->getValue("Completion-Status") == "ack") {
            $handle = $Joker->getValue("Object-Name");
        }
        if ($Joker->getValue("Completion-Status") == 'nack') {
            $values["error"] = "Registrant: Contact creation failed";
            $error = true;
        }
        if (!$error && !$handle) {
            usleep(500);
        }
    }

    if (!$error && !$handle) {
        $values["error"] = "Registrant: Contact creation timeout";
    }

    $values['handle'] = $handle;
    $values['time_spent'] = (time() - $start_time);

    return $values;

}

function joker_CreateAdminContact($params) {
    $params = injectDomainObjectIfNecessary($params);
    $params = joker_NormalizeContactDetails($params);

    $errorMsgs = array();

    $Joker = new CJokerInterface;

    $Joker->NewRequest();
    $Joker->AddParam( "tld", $params["tld"] );
    //$Joker->AddParam( "fax", "" );
    $Joker->AddParam( "phone", $params["adminfullphonenumber"] );
    $Joker->AddParam( "country", $params["admincountry"] );
    $Joker->AddParam( "postal-code", $params["adminpostcode"] );
    $Joker->AddParam( "state", $params["adminstate"] );
    $Joker->AddParam( "city", $params["admincity"] );
    $Joker->AddParam( "email", $params["adminemail"] );
    $Joker->AddParam( "address-1", $params["adminaddress1"] );
    $Joker->AddParam( "address-2", $params["adminaddress2"] );
    $Joker->AddParam( "name", $params["adminfirstname"].' '.$params["adminlastname"] );
    $Joker->AddParam( "organization", $params["admincompanyname"] );

    if ($params['domainObj']->getLastTLDSegment() == 'eu') {
        $Joker->AddParam( "lang", "EN" );
    }

    $Joker->DoTransaction("contact-create", $params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = "Admin: ".$Joker->getValue("Err1");
        return $values;
    }

    $procid = $Joker->getHeaderValue("Proc-ID");

    $timeout = 30; //seconds

    $handle = false; $error = false;

    $start_time = time();
    while (!$error && !$handle && ($start_time + $timeout) >= time()) {
        $Joker->NewRequest();
        $Joker->AddParam("Proc-ID", $procid);
        $Joker->DoTransaction("result-retrieve", $params);

        if ($Joker->getValue("Err1")) {
            $values["error"] = "Admin: ".$Joker->getValue("Err1");
            $error = true;
        }

        if ($Joker->getValue("Completion-Status") == "ack") {
            $handle = $Joker->getValue("Object-Name");
        }
        if ($Joker->getValue("Completion-Status") == 'nack') {
            $values["error"] = "Admin: Contact creation failed";
            $error = true;
        }
        if (!$error && !$handle) {
            usleep(500);
        }
    }

    if (!$error && !$handle) {
        $values["error"] = "Admin: Contact creation timeout";
    }

    $values['handle'] = $handle;
    $values['time_spent'] = (time() - $start_time);

    return $values;
}

function joker_GetContactDetails($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);
    
    $Joker = new CJokerInterface;
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->AddParam( "internal", 1 );
    $Joker->DoTransaction('query-whois', $params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
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

    $contacts = array(
        "Admin" => $Joker->getValue("domain.admin-c"),
        "Tech" => $Joker->getValue("domain.tech-c"),
        "Billing" => $Joker->getValue("domain.billing-c")
    );

    foreach($contacts as $type => $handle) {
        $Joker->NewRequest();
        $Joker->AddParam( "contact", $handle );
        $Joker->DoTransaction('query-whois', $params);

        if ($Joker->getValue("Err1")) {
            //$values["error"] = $Joker->getValue("Err1");
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

    return $values;
}

/**
 * Obtain the registrant contact email address and return it to be used for the
 * domain reminders.
 *
 * @param array $params
 *
 * @return array
 */
function joker_GetRegistrantContactEmailAddress(array $params)
{
    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->AddParam( "internal", 1 );
    $Joker->DoTransaction('query-whois', $params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
        return $values;
    }

    $values['registrantEmail'] = $Joker->getValue('domain.email');
    return $values;

}

function joker_SaveContactDetails($params) {
    $params = injectDomainObjectIfNecessary($params);
    $params = joker_NormalizeContactDetails($params);
    
    $errorMsgs = array();

    require (ROOTDIR."/includes/countriescallingcodes.php");

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;

    $Joker->NewRequest();
    $Joker->AddParam( "domain", $idn_domain );

    $phonenumber = $params["contactdetails"]["Registrant"]["Phone"];
    $country = $params["contactdetails"]["Registrant"]["Country"];
    $phoneprefix = $countrycallingcodes[$country];
    if ((substr($phonenumber,0,1)!="+") && ($phoneprefix)) {
        $params["contactdetails"]["Registrant"]["Phone"] = "+".$phoneprefix.".".$phonenumber;
    }

    $Joker->AddParam( "fax", $params["contactdetails"]["Registrant"]["Fax"] );
    $Joker->AddParam( "phone", $params["contactdetails"]["Registrant"]["Phone"] );
    $Joker->AddParam( "country", $params["contactdetails"]["Registrant"]["Country"] );
    $Joker->AddParam( "postal-code", $params["contactdetails"]["Registrant"]["Postcode"] );
    $Joker->AddParam( "state", $params["contactdetails"]["Registrant"]["State"] );
    $Joker->AddParam( "city", $params["contactdetails"]["Registrant"]["City"] );
    $Joker->AddParam( "email", $params["contactdetails"]["Registrant"]["Email"] );
    $Joker->AddParam( "address-1", $params["contactdetails"]["Registrant"]["Address 1"] );
    $Joker->AddParam( "address-2", $params["contactdetails"]["Registrant"]["Address 2"] );
    //$Joker->AddParam( "title", $params["contactdetails"]["Registrant"]["Job Title"] );
    $Joker->AddParam( "name", $params["contactdetails"]["Registrant"]["First Name"].' '.$params["contactdetails"]["Registrant"]["Last Name"] );
    $Joker->AddParam( "organization", $params["contactdetails"]["Registrant"]["Organisation Name"] );

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
                $Joker->AddParam( "nexus-category", $nexus );
                break;
            case 'C31':
            case 'C32':
                $Joker->AddParam( "nexus-category", $nexus );
                $Joker->AddParam( "nexus-category-country", $countrycode);
                break;
        }
        $Joker->AddParam( "app-purpose", $purpose );

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
        $Joker->AddParam( "account-type", $uklegaltype );
        $Joker->AddParam( "company-number", $params["additionalfields"]['Company ID Number'] );

    } elseif ($params['original']['domainObj']->getLastTLDSegment() == 'eu') {
        $Joker->AddParam( "lang", "EN" );
    }
    $Joker->DoTransaction("domain-owner-change", $params);

    if ($Joker->getValue("Err1")) {
        $errorMsgs[] = "Registrant: ".$Joker->getValue("Err1");
    }

    $Joker->NewRequest();
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->AddParam( "internal", 1 );
    $Joker->DoTransaction('query-whois', $params);

    if ($Joker->getValue("Err1")) {
        $errorMsgs[] = "Domain Info: ".$Joker->getValue("Err1");
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
                $Joker->NewRequest();
                $Joker->AddParam( "handle", $handle );
                $Joker->AddParam( "fax", $params["contactdetails"][$type]["Fax"] );
                $Joker->AddParam( "phone", $params["contactdetails"][$type]["Phone"] );
                $Joker->AddParam( "country", $params["contactdetails"][$type]["Country"] );
                $Joker->AddParam( "postal-code", $params["contactdetails"][$type]["Postcode"] );
                $Joker->AddParam( "state", $params["contactdetails"][$type]["State"] );
                $Joker->AddParam( "city", $params["contactdetails"][$type]["City"] );
                $Joker->AddParam( "email", $params["contactdetails"][$type]["Email"] );
                $Joker->AddParam( "address-1", $params["contactdetails"][$type]["Address 1"] );
                $Joker->AddParam( "address-2", $params["contactdetails"][$type]["Address 2"] );
                //$Joker->AddParam( "title", $params["contactdetails"][$type]["Job Title"] );
                $Joker->AddParam( "name", $params["contactdetails"][$type]["First Name"].' '.$params["contactdetails"][$type]["Last Name"] );
                $Joker->AddParam( "organization", $params["contactdetails"][$type]["Organisation Name"] );
                if ($params['original']['domainObj']->getLastTLDSegment() == 'eu') {
                    $Joker->AddParam( "lang", "EN" );
                }
                $Joker->DoTransaction('contact-modify', $params);
                if ($Joker->getValue("Err1")) {
                    $errorMsgs[] = "$type: ".$Joker->getValue("Err1");
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

    $Joker = new CJokerInterface;
    $Joker->NewRequest();
    $Joker->AddParam( "domain", $idn_domain );
    $Joker->DoTransaction("domain-transfer-get-auth-id", $params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
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

    $Joker = new CJokerInterface;

    $Joker->AddParam( "domain", $idn_domain );
    $Joker->DoTransaction("domain-transfer-get-auth-id", $params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
        return $values;
    }

    $Joker->NewRequest();
    $Joker->AddParam( "rtype", "domain-transfer-get-auth-id" );
    $Joker->AddParam( "objid", $idn_domain );
    $Joker->AddParam( "showall", 1 );
    $Joker->AddParam( "limit", 1 );
    $Joker->DoTransaction('result-list',$params);

    $procid = false;
    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
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
            $Joker->NewRequest();
            $Joker->AddParam("Proc-ID", $procid);
            $rawMsg = $Joker->DoTransaction("result-retrieve", $params);
            if ($Joker->getValue("Err1")) {
                $values["error"] = "EPP-Code: ".$Joker->getValue("Err1");
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

    $Joker = new CJokerInterface;
    $Joker->NewRequest();
    $Joker->AddParam( "host", $params["nameserver"] );
    $Joker->AddParam( "ip", $params["ipaddress"] );
    $Joker->DoTransaction('ns-create', $params);

    if ($Joker->getValue("Err1")) {
        $error = $Joker->getValue("Err1");
    }

    $values["error"] = $error;

    return $values;

}

function joker_ModifyNameserver($params) {

    $params = injectDomainObjectIfNecessary($params);

    $Joker = new CJokerInterface;
    $Joker->NewRequest();
    $Joker->AddParam( "host", $params["nameserver"] );
    //$Joker->AddParam( "OldIP", $params["currentipaddress"] );
    $Joker->AddParam( "ip", $params["newipaddress"] );
    $Joker->DoTransaction('ns-modify',$params);

    if ($Joker->getValue("Err1")) {
        $error = $Joker->getValue("Err1");
    }

    $values["error"] = $error;

    return $values;

}

function joker_DeleteNameserver($params) {

    $params = injectDomainObjectIfNecessary($params);

    $Joker = new CJokerInterface;
    $Joker->NewRequest();
    $Joker->AddParam( "host", $params["nameserver"] );
    $Joker->DoTransaction('ns-delete', $params);

    if ($Joker->getValue("Err1")) {
        $error = $Joker->getValue("Err1");
    }

    $values["error"] = $error;

    return $values;

}

function joker_SyncManual($params) {
    $values = array();

    $params = injectDomainObjectIfNecessary($params);

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "pattern", $idn_domain );
    //$Joker->AddParam( "showstatus", 1 );
    $Joker->DoTransaction('query-domain-list',$params);

    if ($Joker->getValue("Err1")) {
        $values["error"] = $Joker->getValue("Err1");
        return $values;
    }

    $resultList = $Joker->getResponseList();

    if (count($resultList) > 0) {
        //$status = explode(",",$resultList[0]['domain_status']);
        print $resultList[0]['expiration_date'];
        $time_grace = intval($GLOBALS['CONFIG']['OrderDaysGrace']) * 86400;
        $sync_data = array(
            'domainid' => $params['domainid'],
            'expirydate' => $resultList[0]['expiration_date'],
            'nextduedate' => date('Ymd', strtotime($resultList[0]['expiration_date']) - $time_grace),
        );
        $expDate = new DateTime($values['expirydate'],new DateTimeZone('UTC'));
        $now = new DateTime(null,new DateTimeZone('UTC'));
        if ($expDate > $now) {
            $sync_data['status'] = 'active';
        }
        $result = localAPI('updateclientdomain', $sync_data, $params['AdminUser']);
    } else {
        $values['error'] = "Domain not found";
    }
    if (!isset($values['error'])) {
        $values['message'] = '(Warning) You must refresh page to see the changes';
    }
    return $values;
}

function joker_Sync($params) {

    $params = injectDomainObjectIfNecessary($params);
    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "pattern", $idn_domain );
    //$Joker->AddParam( "showstatus", 1 );
    $Joker->DoTransaction('query-domain-list',$params);

    if ($Joker->getValue("Err1")) {
        $values['error'] = $Joker->getValue("Err1");
    }

    $resultList = $Joker->getResponseList();

    if (count($resultList) > 0) {
        //$status = explode(",",$resultList[0]['domain_status']);
        $values['expirydate'] = $resultList[0]['expiration_date'];
        $expDate = new DateTime($values['expirydate'],new DateTimeZone('UTC'));
        $now = new DateTime(null,new DateTimeZone('UTC'));
        if ($expDate > $now) {
            $values['status'] = "Active";
        }
    } else {
        $values['error'] = "Domain not found";
    }
    return $values;

}


function joker_TransferSync($params){

    $params = injectDomainObjectIfNecessary($params);

    $values = array();

    $idn_domain = $params['original']['domainObj']->getDomain(true);

    $Joker = new CJokerInterface;
    $Joker->AddParam( "rtype", "domain-transfer-in-reseller" );
    $Joker->AddParam( "objid", $idn_domain );
    $Joker->AddParam( "showall", 1 );
    $Joker->AddParam( "limit", 1 );
    $Joker->DoTransaction('result-list',$params);

    if ($Joker->getValue("Err1")) {
        $values['error'] = $Joker->getValue("Err1");
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

/**
 * With the new ICCAN rules requiring validation of addresses and other contact data,
 * joker has added a number of validation filters on the contact details. Sometimes they
 * make sense, other times we need to normalize the data we are sending to them to ensure
 * they accept it.
 *
 * This function is called each place we get user inputed contact details to send to joker.
 *
 * Currently it filters the Canadian postal codes, which are normally stored as ANA NAN but
 * joker expects to be ANANAN.
 *
 * @param  array $params the full set of parameters we are going to pull from to send to joker
 * @return array $params the same set of parameters, normalized for joker's filtering
 */
function joker_NormalizeContactDetails($params) {

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

    $contacttypes = array("Registrant", "Admin", "Tech");
    for ($i=0;$i<=2;$i++) {
        if (isset($params["contactdetails"][$contacttypes[$i]]["Country"])) {
            $country = $params["contactdetails"][$contacttypes[$i]]["Country"];
            if ($country == 'CA') {
                $params["contactdetails"][$contacttypes[$i]]["Postcode"] = preg_replace('/\s/', '', $params["contactdetails"][$contacttypes[$i]]["Postcode"]);
            }
        }
    }

    return $params;
}

function joker_ClientAreaCustomButtonArray($params) {

    $buttonarray = array();
    $buttonarray["EPP Code"] = "FetchEPPCodeClient";
    return $buttonarray;
}

function joker_AdminCustomButtonArray($params) {

    $buttonarray = array();
    $buttonarray["EPP Code"] = "FetchEPPCode";
    $buttonarray["Sync"] = "SyncManual";
    return $buttonarray;
}

class CJokerInterface {
    private $PostString;
    private $RawData;
    private $Values;
    private $List;
    private $Header;
    private $Command;
    private $Session;

    public function __construct() {
        $this->Session = false;
        $this->NewRequest();
    }

    public function NewRequest() {
        $this->Command = "";
        $this->PostString = "";
        $this->RawData = "";
        $this->Header = array();
        $this->Values = array();
        $this->List = array();
    }

    public function getValue($key) {
        return isset($this->Values[$key])?$this->Values[$key]:false;
    }

    public function getResponseList() {
        return $this->List;
    }

    public function getHeaderValue($key) {
        return isset($this->Header[$key])?$this->Header[$key]:false;
    }

    private function AddError( $error ) {
        $this->Values[ "ErrCount" ] = "1";
        $this->Values[ "Err1" ] = $error;
    }

    private function ParseResponse( $buffer ) {
        if (!$buffer || !is_string($buffer)) {
            $errorMsg = "Cannot parse empty response from server - ";
            $errorMsg .= "Please try again later";
            $this->AddError($errorMsg);
            return false;
        }
        $responseParts = explode("\n\n", $buffer,2);

        $this->Header = $this->parseKeyValueList($responseParts[0]);
        $rawBody = "";
        if (count($responseParts) > 1 ) {
            $rawBody = $responseParts[1];
        }
        if (!isset($this->Header["Status-Code"]) || $this->Header["Status-Code"] !=0 ) {
            $this->AddError(
                "DMAPI request '" . $this->Command . "' failed: ". $this->Header["Status-Code"] 
                    . (isset($this->Header["Status-Text"])?" ".$this->Header["Status-Text"]:'')
                    . (isset($this->Header["Error"])?" ".(is_array($this->Header["Error"])?implode(";",$this->Header["Error"]):$this->Header["Error"]):'')
            );
            return false;
        }
        if (isset($this->Header["Auth-Sid"])) {
            $this->Session = $this->Header["Auth-Sid"];
        }

        $Body = $this->parseKeyValueList($rawBody);
        if (substr($this->Command,-4) == 'list' || $this->Command == 'dns-zone-get' ) {
            $this->List = $this->ParseResponseList($rawBody);
        } else {
            $this->List = array();
        }
        $this->Values = array_merge($this->Values,$Body);
        return true;
    }
    
    private function parseKeyValueList($data) {
        $result = array();
        $lines = explode("\n", $data);
        foreach( $lines as $line) {
            $keyvalue = explode(' ', $line, 2);
            $key = rtrim($keyvalue[0],': ');
            $value = (count($keyvalue) == 2) ?  $keyvalue[1] : "";
            if (isset($result[$key])) {
                if (is_array($result[$key])) {
                    $result[$key][] = $value;
                } else {
                    $result[$key] = array( $result[$key], $value);
                }
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function parseResponseList($data) {
        $result = array();
        $separator = " ";
        if (isset($this->Header['Separator']) && $this->Header['Separator'] == 'TAB') {
            $separator = "\t";
        }
        $columnTitles = Array();
        if (isset($this->Header['Columns']) ) {
            $columnTitles = explode(",",$this->Header['Columns']);
        }
        $lines = explode("\n", $data);
        foreach( $lines as $line) {
            if (empty($line)) continue;
            $values = explode($separator, $line);
            if (count($columnTitles) > 0) {
                $columns = array();
                foreach($values as $key => $value) {
                    $columns[$columnTitles[$key]] = $value;
                }
                $result[] = $columns;
            } else {
                $result[] = $values;
            }
        }
        return $result;
    }

    public function AddParam($Name, $Value) {
        $this->PostString = $this->PostString . $Name . "=" . urlencode( $Value ) . "&";
    }

    public function DoTransaction($command, $params, $processResponse = true) {
        if ($this->Session === false) {
            $loginResult = $this->Login($params);
            if ($this->Session === false) {
                return $loginResult;
            }
        }
        return $this->SendCommand($command, $params, $processResponse);
    }
    
    private function Login($params) {
        $cachedPostString = $this->PostString;
        $this->PostString = "";
        $this->AddParam( "username", $params["Username"] );
        $this->AddParam( "password", $params["Password"] );
        $result = $this->SendCommand("login", $params);
        $this->PostString = $cachedPostString;
        return $result;
    }


    private function SendCommand($command, $params, $processResponse = true) {
        $this->Command = $command;
        $this->Values = Array();
        if ($params['TestMode']) {
            $host = 'dmapi.ote.joker.com';
        } else {
            $host = 'dmapi.joker.com';
        }
        $whmcsVersion = \App::getVersion();
        $this->AddParam('Engine', 'WHMCS' . $whmcsVersion->getMajor() . '.' . $whmcsVersion->getMinor());

        if (!$this->Session === false) {
            $this->AddParam('auth-sid', $this->Session);
        }
        $ch = curl_init();
        $url = "https://".$host."/request/".$command;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->PostString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $response = curl_exec($ch);
        $this->RawData = '';

        if (curl_error($ch)) {
            $responseMsgToPropagate = "CURL Error: ".curl_errno($ch)." - ".curl_error($ch);
            $this->AddError($responseMsgToPropagate);
        } elseif (!$response) {
            $responseMsgToPropagate = 'Empty data response from server - Please try again later';
        } else {
            $this->RawData = $responseMsgToPropagate = $response;
        }
        curl_close ($ch);

        if ($processResponse && $response) {
            $this->ParseResponse($response);
        }
        if (function_exists("logModuleCall")) {
            logModuleCall(
                'joker',
                $command,
                $this->PostString,
                $responseMsgToPropagate,
                '',
                array($params["Username"], $params["Password"])
            );
        }
        return $this->RawData;
    }

}
