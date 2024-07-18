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

function loadLocale() {
    global $_LANG;
    $locale = isset($_LANG['locale'])?substr($_LANG['locale'], 0, 2):'en';
    $locale_dir = dirname(__FILE__).'/locale/';
    $locale_file = $locale_dir.$locale.'.php';
    if (!file_exists($locale_file)) {
        $locale_file = $locale_dir.'/en.php';
    }
    $REGLANG = array();
    include_once($locale_file);
    $_LANG = $_LANG + $REGLANG;
}
loadLocale();