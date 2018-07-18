<?php
/*
  ****************************************************************************
  *                                                                          *
  * The MIT License (MIT)                                                    *
  * Copyright (c) 2018 Joker.com                                             *
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

require_once dirname(__FILE__).'/dmapiclient.php';

function joker_widget_info($vars) {

    require_once dirname(__FILE__)."/../../../includes/registrarfunctions.php";

    $config = getRegistrarConfigOptions('joker');

    $client = DMAPIClient::getInstance($config);

    $client->ExecuteAction("query-profile",Array());

    $content = '<div class="widget-content-padded"><p>';
    $content .= '<strong>Username:</strong> '.$client->getUsername().'<br>';
    $content .= '<strong>Customer ID:</strong> '.$client->getValue('customer-id').'<br>';
    $content .= '<strong>Account-Balance:</strong> '.$client->getValue('balance').' USD ';
    $content .= '<a class="btn btn-info btn-xs" href="https://joker.com/goto/funding" target="_blank">Increase your account on Joker.com</a>';
    $content .= '</p></div>';

    return array( 'title' => 'Joker.com Reseller Account', 'content' => $content );

}

add_hook("AdminHomeWidgets",1,"joker_widget_info");


function joker_validate_additional_domain_fields($params)
{
    $additionaldomainfields = null;
    if (file_exists(ROOTDIR.'/includes/additionaldomainfields.php')) {
        include(ROOTDIR.'/includes/additionaldomainfields.php');
    }

    $errors = array();
    foreach ($_SESSION['cart']['domains'] as $domain) {
        $tld = explode('.',$domain['domain'],2)[1];

        if (isset($additionaldomainfields)) {
            $fields = $additionaldomainfields["." . $tld];
        } else {
            $additflds = new WHMCS\Domains\AdditionalFields();
            $additflds->setTLD($tld);
            $fields = $additflds->getFields();
        }

        $additionalfields = array();
        $displaynames = array();

        foreach($fields as $key => $def) {
            $additionalfields[$def['Name']] = $domain['fields'][$key];
            $displaynames[$def['Name']] = isset($def['DisplayName'])?$def['DisplayName']:$def['Name'];
        }

        if (isset($additionalfields['x-ficora-registernumber']) && !empty($additionalfields['x-ficora-registernumber'])) {
            if ($additionalfields["x-ficora-type"] == 'privateperson') {
                if (strtolower($additionalfields["x-ficora-is-finnish"]) == 'yes') {
                    if (!preg_match("/^[0-9]{2}[0,1][0-9][0-9]{2}[-+A][0-9]{3}[0-9A-Z]$/", $additionalfields['x-ficora-registernumber']) ) {
                        $errors[] = $displaynames['x-ficora-registernumber'].": Please provide a valid identity number ({$domain['domain']})";
                    }
                } else {
                    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $additionalfields['x-ficora-registernumber']) ) {
                        $date = date_parse($additionalfields['x-ficora-registernumber']);
                        if ($date['day'] === false || $date['month'] === false || $date['year'] === false) {
                            $errors[] = $displaynames['x-ficora-registernumber'].": Please provide a valid birthdate ({$domain['domain']})";
                        }
                    }
                }
            } else {
                // check if valid registernumber
                //$errors[] = $displaynames['x-ficora-registernumber'].': No valid register number.';
            }
        }
    }
    return count($errors) ? $errors : '';
}

add_hook('ShoppingCartValidateDomainsConfig', 1, 'joker_validate_additional_domain_fields');


function joker_after_domain_registration($vars) {
    $config = getRegistrarConfigOptions('joker');
    if($vars["params"]["registrar"]=="joker" && $config["CronJob"] && $vars["params"]["status"] == "Active"){
        $values = array();
        $values['status'] = "Pending";
        $values['expirydate'] = '0000-00-00';
        $values['domainid'] = $vars['params']['domainid'];
        localAPI('updateclientdomain', $values, $vars['params']['AdminUser']);
    }
}

add_hook("AfterRegistrarRegistration",1,"joker_after_domain_registration");
