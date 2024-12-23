<?php

/*
 ****************************************************************************
 *                                                                          *
 * The MIT License (MIT)                                                    *
 * Copyright (c) 2021 Joker.com                                             *
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

use WHMCS\Database\Capsule;

class JokerHelper {

    private static $settings = Array();

    public static function fixExpirationDate($strDate, $tld) {
        if (strtolower($tld) == 'eu') {
            $strDate = (new DateTime($strDate, new DateTimeZone('UTC')))->modify('-1 days')->format('Y-m-d');
        }
        return $strDate;
    }

    public static function loadSettings() {
        if (empty(self::$settings)) {
            foreach (Capsule::table('tblregistrars')->where('registrar', 'joker')->get() as $domain) {
                $settings[$domain->setting] = decrypt($domain->value);
            }
            self::$settings = $settings;
        }
        return self::$settings;
    }

}
