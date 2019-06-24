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
*/

require dirname(__FILE__). '/../../../init.php';
require_once dirname(__FILE__).'/dmapiclient.php';
require_once dirname(__FILE__).'/helper.php';

use WHMCS\Domain\Domain;

class JokerCron {
    
    private static $_instance = null;
    
    public static function Execute() {
        $cron = self::getInstance();
        $cron->syncDomainRegistrations();
    }
    
    public static function getInstance() {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function syncDomainRegistrations() {
        foreach (Domain::where('registrar','joker')->where('status','Pending')->get() as $domain) {
            $domainObj = new WHMCS\Domains\Domain($domain->domain);
            $idn_domain = $domainObj->getDomain(true);
            $tld = $domainObj->getTopLevel();
            $Joker = DMAPIClient::getInstance(JokerHelper::loadSettings());
            $reqParams = array();
            $reqParams["rtype"] = "domain-register";
            $reqParams["objid"] = $idn_domain;
            $reqParams["showall"] = 1;
            $reqParams["pending"] = 1;
            $reqParams["limit"] = 1;
            $reqParams["cltrid"] = 'domreg-'.$domain->id;
            $Joker->ExecuteAction('result-list', $reqParams);

            if ($Joker->hasError()) {
                $this->output($idn_domain . ': ' . $Joker->getError());
                continue;
            } elseif ($Joker->getHeaderValue('Row-Count') > 0) {
                $resultList = $Joker->getResponseList();
                $status = $resultList[0][5];
                if ($status == "nack") {
                    $domain->status = "Cancelled";
                    $domain->save();
                    $this->output($idn_domain . ': ' . "Now cancelled");
                } else {
                    $reqParams = array();
                    $reqParams["pattern"] = $idn_domain;
                    $Joker->ExecuteAction('query-domain-list', $reqParams);

                    if ($Joker->hasError()) {
                        $this->output($idn_domain . ': ' . $Joker->getError());
                        continue;
                    }

                    $resultList = $Joker->getResponseList();

                    if (count($resultList) > 0) {
                        $domain->expirydate = JokerHelper::fixExpirationDate($resultList[0]['expiration_date'], $tld);
                        $domain->status = "Active";
                        $domain->save();
                        $this->output($idn_domain . ': ' . "Now active ({$domain->expirydate})");
                    } else {
                        $this->output($idn_domain . ': ' . "Still pending");
                    }
                }
            } else {
               $this->output($idn_domain . ': Domain/Order not found (cltrid:'.'domreg-'.$domain->id.')');
            }
        }
    }
    
    private function output($line) {
        print date('Y-M-d H:i:s').':'.$line . PHP_EOL;
    }

}

JokerCron::Execute();
