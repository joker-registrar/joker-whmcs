<h3 style="margin-bottom:25px;">{$LANG.dnssec.kd_page_title}</h3>

{if $successful}
    <div class="alert alert-block alert-success">
        <p>{$LANG.changessavedsuccessfully}</p>
    </div>
{/if}

{if $record_added}
    <div class="alert alert-block alert-warning">
        <p>{$LANG.dnssec.record_added}</p>
    </div>
{/if}

{if $error}
    <div class="alert alert-block alert-danger">
        {$error}
    </div>
{/if}
<h4>{$LANG.dnssec.kd_records_title}</h4>
<form class="form-horizontal" role="form" method="post" action="/clientarea.php">
<input type="hidden" name="action" value="domaindetails" />
<input type="hidden" name="id" value="{$domainid}" />
<input type="hidden" name="modop" value="custom" />
<input type="hidden" name="a" value="ManageDNSSEC_KD" />
{if !empty($recordslist)}
    {foreach $recordslist as $key => $item}
            <div class="form-group">
                <label for="flags_{$key}" class="col-xs-4 control-label">Flags</label>
                <div class="col-xs-6 col-sm-5">
                    <input id="flags_{$key}" name="records[{$key}][flags]" type="number" required="required" min="256" max="257" data-supported="True" data-required="True" value="{$item.flags}" class="form-control" />
                </div>
            </div>
            <div class="form-group">
                <label for="protocol_{$key}" class="col-xs-4 control-label">Protocol</label>
                <div class="col-xs-6 col-sm-5">
                    <select id="protocol_{$key}" name="records[{$key}][protocol]" data-supported="True" data-required="True" class="form-control">
                        <option value="3"{($item.protocol==3)?" selected":""}>3 - DNSSEC</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="alg_{$key}" class="col-xs-4 control-label">Algorithm</label>
                <div class="col-xs-6 col-sm-5">
                    <select id="alg_{$key}" name="records[{$key}][alg]" data-supported="True" data-required="True" class="form-control">
                        <option value="1"{($item.alg==1)?" selected":""}>1-RSAMD5</option>
                        <option value="2"{($item.alg==2)?" selected":""}>2-DH</option>
                        <option value="3"{($item.alg==3)?" selected":""}>3-DSA</option>
                        <option value="4"{($item.alg==4)?" selected":""}>4-ECC</option>
                        <option value="5"{($item.alg==5)?" selected":""}>5-RSASHA1</option>
                        <option value="6"{($item.alg==6)?" selected":""}>6-DSA-NSEC3-SHA1</option>
                        <option value="7"{($item.alg==7)?" selected":""}>7-RSASHA1-NSEC3-SHA1</option>
                        <option value="8"{($item.alg==8)?" selected":""}>8-RSASHA256</option>
                        <option value="10"{($item.alg==10)?" selected":""}>10-RSASHA512</option>
                        <option value="12"{($item.alg==12)?" selected":""}>12-ECC-GOST</option>
                        <option value="13"{($item.alg==13)?" selected":""}>13-ECDSAP256SHA256</option>
                        <option value="14"{($item.alg==14)?" selected":""}>14-ECDSAP384SHA384</option>
                        <option value="15"{($item.alg==15)?" selected":""}>15-ED25519</option>
                        <option value="16"{($item.alg==16)?" selected":""}>16-ED448</option>
                        <option value="252"{($item.alg==252)?" selected":""}>252-INDIRECT</option>
                        <option value="253"{($item.alg==253)?" selected":""}>253-PRIVATEDNS</option>
                        <option value="254"{($item.alg==254)?" selected":""}>254-PRIVATEOID</option>
                    </select>
                </div>
            </div>    

            <div class="form-group">
                <label for="pubkey_{$key}" class="col-xs-4 control-label">Public Key</label>
                <div class="col-xs-6 col-sm-5">
                    <textarea id="pubkey_{$key}" name="records[{$key}][pubkey]" rows="2" data-supported="True" data-required="True" class="form-control" required="required">{$item.pubkey}</textarea>
                </div>
            </div>

            <p class="text-center">
                <button type="submit" name="removeRecord" value="{$key}" class="btn btn-danger">{$LANG.dnssec.remove}</button>
            </p>

        <br />
    {/foreach}
{else}
    <div class="text-center alert alert-block {($configured)?"alert-success":"alert-info"}"><strong>{($configured)?$LANG.dnssec.configured:$LANG.dnssec.not_configured}</strong><br/><small>{($configured)?$LANG.dnssec.configured_not_fetched:""}</small></div>
{/if}
<div class="text-center">
    <button type="submit" name="refresh" value="1" class="btn btn-primary pull-left">{$LANG.dnssec.refresh}</button><button type="submit" name="deactivate" value="1" class="btn btn-danger" {($configured)?"":"disabled"}>{$LANG.dnssec.deactivate}</button><button type="submit" name="save" value="1" class="btn btn-success pull-right" {(!empty($recordslist))?"":"disabled"}>{$LANG.dnssec.save}</button>
</div>
    </form>


{if $recordslist|@count lt 6}
    
    <hr>

    <h4>{$LANG.dnssec.kd_add_title}</h4>
    <form class="form-horizontal" role="form" method="post" action="/clientarea.php">
    <input type="hidden" name="action" value="domaindetails" />
    <input type="hidden" name="id" value="{$domainid}" />
    <input type="hidden" name="modop" value="custom" />
    <input type="hidden" name="a" value="ManageDNSSEC_KD" />
    {foreach $recordslist as $key => $item}
        <input type="hidden" name="records[{$key}][alg]"value="{$item.alg}" />
        <input type="hidden" name="records[{$key}][flags]" value="{$item.flags}" />
        <input type="hidden" name="records[{$key}][protocol]" value="{$item.protocol}" />
        <input type="hidden" name="records[{$key}][pubkey]" value="{$item.pubkey}" />
    {/foreach}
    
    <div class="form-group">
        <label for="flags" class="col-xs-4 control-label">Flags</label>
        <div class="col-xs-6 col-sm-5">
            <input id="flags" name="flags" type="number" required="required" min="256" max="257" data-supported="True" data-required="True" data-previousvalue="" class="form-control" />
        </div>
    </div>

    <div class="form-group">
        <label for="protocol" class="col-xs-4 control-label">Protocol</label>
        <div class="col-xs-6 col-sm-5">
            <select id="protocol" name="protocol" data-supported="True" data-required="True" class="form-control">
                <option value="3">3 - DNSSEC</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="alg" class="col-xs-4 control-label">Algorithm</label>
        <div class="col-xs-6 col-sm-5">
            <select id="alg" name="alg" data-supported="True" data-required="True" data-previousvalue="" class="form-control">
                <option value="1">1-RSAMD5</option>
                <option value="2">2-DH</option>
                <option value="3">3-DSA</option>
                <option value="4">4-ECC</option>
                <option value="5">5-RSASHA1</option>
                <option value="6">6-DSA-NSEC3-SHA1</option>
                <option value="7">7-RSASHA1-NSEC3-SHA1</option>
                <option value="8">8-RSASHA256</option>
                <option value="10">10-RSASHA512</option>
                <option value="12">12-ECC-GOST</option>
                <option value="13">13-ECDSAP256SHA256</option>
                <option value="14">14-ECDSAP384SHA384</option>
                <option value="15">15-ED25519</option>
                <option value="16">16-ED448</option>
                <option value="252">252-INDIRECT</option>
                <option value="253">253-PRIVATEDNS</option>
                <option value="254">254-PRIVATEOID</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="pubkey" class="col-xs-4 control-label">Public Key</label>
        <div class="col-xs-6 col-sm-5">
            <textarea id="pubkey" name="pubkey" rows="2" data-supported="True" data-required="True" data-previousvalue="" class="form-control" required="required"></textarea>
        </div>
    </div>

    <p class="text-center">
        <button type="submit" name="addRecord" value="1" class="btn btn-primary">{$LANG.dnssec.add}</button>
    </p>
    </form>
{/if}