<?php
/*
  ****************************************************************************
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
*/

require_once dirname(__FILE__).'/dmapiclient.php';
require_once dirname(__FILE__)."/../../../includes/registrarfunctions.php";

function joker_widget_info($vars) {

    $config = getRegistrarConfigOptions('joker');

    $client = DMAPIClient::getInstance($config);

    $client->ExecuteAction("query-profile",Array());

    $content = '<p>';
    $content .= '<strong>Username:</strong> '.$config['Username'].'<br>';
    $content .= '<strong>Customer ID:</strong> '.$client->getValue('customer-id').'<br>';
    $content .= '<strong>Account-Balance:</strong> '.$client->getValue('balance').' USD ';
    $content .= '<a class="btn btn-info btn-xs" href="https://joker.com/goto/funding" target="_blank">Increase your account on Joker.com</a>';
    $content .= '</p>';

    return array( 'title' => 'Joker.com Reseller Account', 'content' => $content );

}

add_hook("AdminHomeWidgets",1,"joker_widget_info");

