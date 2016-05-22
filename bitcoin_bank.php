<?php
/**
 * Bitcoin Bank plugin for Wordpress.
 * Original written to demonstrate the usage of Bitcoin Cheques.
 *
 * Copyright (C) 2016 Arild Hegvik and Bitcoin Cheque Foundation.
 *
 * GNU LESSER GENERAL PUBLIC LICENSE (GNU LGPLv3)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the 
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
Plugin Name: Bitcoin Bank
Plugin URI: http://www.bitcoincheque.org
Description: This Wordpress plugin is a Bitcoin Bank to demonstrate the usage of "Bitcoin Cheques".
Version: 0.0.1
Author: Bitcoin Cheque Foundation
Author URI: http://www.bitcoincheque.org
License: GNU LGPLv3
License URI: license.txt
Text Domain: bcf_bitcoinbank
*/

namespace BCF_BitcoinBank;

require_once('includes/user_handler.php');
require_once('includes/cheque_handler.php');
require_once('includes/html_table.php');


function SanitizeInputText($text)
{
    $text = str_replace('<', '&lt;', $text);
    $text = str_replace('>', '&gt;', $text);

    return $text;
}

function SanitizeInputInteger($text)
{
    $value = intval($text);
    return $value;
}

function ProcessAjaxValidateCheque()
{
    $cheque_handler = new ChequeHandlerClass();

    $cheque_json = SanitizeInputText($_REQUEST['cheque']);
    $cheque_json = str_replace ('\"', '"', $cheque_json);
    $cheque_json = str_replace ('\\', '', $cheque_json);
    $cheque_json = rawurldecode($cheque_json);

    //error_log('Validate cheque:' . $cheque_json);

    $cheque = json_decode($cheque_json, true);

    $result_msg = $cheque_handler->ValidateCheque($cheque, true);

    //error_log('cheque valid error:' . $result_msg);
    if($result_msg == 'OK')
    {
        $response_data = array(
            'serial_no' => $cheque['serial_no'],
            'status'    => 'VALID',
            'errors'    => $result_msg
        );
    }
    else
    {
        $response_data = array(
            'serial_no' => $cheque['serial_no'],
            'status'    => 'INVALID',
            'errors'    => $result_msg
        );
    }

    echo json_encode($response_data);
    die();
}

function ProcessAjaxRequestCheque()
{
    $amount_val = intval(SanitizeInputText($_REQUEST['amount']));
    $currency_str = SanitizeInputText($_REQUEST['currency']);
    $paylink_str = SanitizeInputText($_REQUEST['paylink']);
    $receiver_name = SanitizeInputText($_REQUEST['receiver_name']);
    $receiver_address = SanitizeInputText($_REQUEST['receiver_address']);
    $receiver_url = SanitizeInputText($_REQUEST['receiver_url']);
    $receiver_email = SanitizeInputText($_REQUEST['receiver_email']);
    $business_no = SanitizeInputText($_REQUEST['business_no']);
    $reg_country = SanitizeInputText($_REQUEST['reg_country']);
    $receiver_wallet = SanitizeInputText($_REQUEST['receiver_wallet']);
    $min_expire_sec = SanitizeInputText($_REQUEST['min_expire_sec']);
    $max_escrow_sec = SanitizeInputText($_REQUEST['max_escrow_sec']);
    $reference_str = SanitizeInputText($_REQUEST['ref']);
    $account_id_val = intval(SanitizeInputText($_REQUEST['account']));
    $account_password_str = SanitizeInputText($_REQUEST['passwd']);
    $description = SanitizeInputText($_REQUEST['description']);

    if($currency_str != 'BTC')
    {
        echo 'Unsupported curreny';
        die();
    }

    $issuer_account_id= new AccountIdTypeClass($account_id_val);
    $account_password = new PasswordTypeClass($account_password_str);
    $amount           = new ValueTypeClass($amount_val);
    $reference        = new TextTypeClass($reference_str);
    $receiver_name    = new NameTypeClass($receiver_name);
    $receiver_address = new TextTypeClass($receiver_address);
    $receiver_url     = new TextTypeClass($receiver_url);
    $receiver_email   = new TextTypeClass($receiver_email);
    $business_no      = new TextTypeClass($business_no);
    $reg_country      = new TextTypeClass($reg_country);
    $receiver_wallet  = new TextTypeClass($receiver_wallet);
    $description      = new TextTypeClass($description);

    $expire_seconds = 300;
    $escrow_seconds = 600;

    $cheque_handler = new ChequeHandlerClass();

    $cheque = $cheque_handler->IssueCheque(
        $issuer_account_id,
        $account_password,
        $amount,
        $expire_seconds,
        $escrow_seconds,
        $reference,
        $receiver_name,
        $receiver_address,
        $receiver_url,
        $receiver_email,
        $business_no,
        $reg_country,
        $receiver_wallet,
        $description
    );

    if(!is_null($cheque))
    {
        //error_log('Issued   cheque:' . $cheque_json);
        //error_log($cheque_json);
        echo $cheque->GetJson();
    }
    else
    {
        //error_log('Issue cheque failed');
        error_log('Issue cheque failed');
    }

    die();
}
function MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency)
{
    $html = '<select name="select_account">';
    foreach ( $account_data_list as $account_data )
    {
        $listed_account_id      = $account_data->GetAccountId();
        $account_id_str         = $listed_account_id->GetString();
        $account_id_formatedstr = $listed_account_id->GetFormatedString();
        $account_name           = $account_data->GetAccountName();
        $account_name_str       = $account_name->GetString();
        $balance = $user_handler->GetUsersAccountBalance($listed_account_id);
        $account_balance_str    = GetFormattedCurrency($balance->GetInt(), $currency, true );

        $select = '';
        if($account_selected != null)
        {
            if ( $listed_account_id->GetInt() == $account_selected->GetInt() )
            {
                $select = 'selected="1" ';
            }
        }

        $html .= '<option '.$select.'value="' . $account_id_str . '">' . $account_id_formatedstr . ' / ' . $account_name_str . ' / ' . $account_balance_str . '</option>';
    }
    $html .= '</select>';
    return $html;
}

function MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected, $currency)
{

    $html = '<form name="bcf_withdraw_form">';
    $html .= '<table style="border-style:none;" width="100%"><tr>';
    $html .= '<td style="border-style:none;" width="30%">My accounts:</td>';

    $html .= '<td style="border-style:none;">';

    $html .= MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency);

    $html .= '</td>';

    $html .= '<td style="border-style:none;"><a href="transactions"><input type="submit" value="Select"/></a></td>';
    $html .= '</tr></table>';
    $html .= '</form>';

    return $html;
}

function ListUserTransactions($atts)
{
    if (is_user_logged_in()) {
        $currency = 'uBTC';

        if ( ! empty( $_REQUEST['select_account'] ) ) {
            $show_account_str = SanitizeInputText( $_REQUEST['select_account'] );
            $account_selected = new AccountIdTypeClass(0);
            $account_selected->SetDataFromString($show_account_str);
        }
        else
        {
            $account_selected = null;
        }

        $user_handler = new UserHandlerClass();
        $account_data_list = $user_handler->GetAccountInfoListCurrentUser();



        if ( $account_selected == null ) {
            $account_data = $account_data_list[0];
            $account_selected = $account_data->GetAccountId();
        }
        $transaction_records_list = $user_handler->GetTransactionListForCurrentUser($account_selected);

        $html_select_account_form = MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected, $currency);

        $html_table = new HtmlTableClass();
        $html_table->AddLineItem('Trans.Id');
        $html_table->AddLineItem('Date/time');
        $html_table->AddLineItem('Type');
        $html_table->AddLineItem('Add');
        $html_table->AddLineItem('Withdraw');
        $html_table->AddLineItem('Balance');
        $html_table->RowFeed();

        foreach (array_reverse($transaction_records_list) as $transaction)
        {
            $id = $transaction->GetTransactionId();
            $datetime = $transaction->GetDateTime()->GetString();
            $type = $transaction->GetTransactionType()->GetString();
            $amount = $transaction->GetTransactionAmount()->GetInt();
            $balance = $transaction->GetTransactionBalance()->GetInt();

            $id_str = $id->GetString();
            $amount_str = GetFormattedCurrency($amount, $currency, false);
            $balance_str = GetFormattedCurrency($balance, $currency, true);

            $html_table->AddLineItem($id_str);
            $html_table->AddLineItem($datetime);
            $html_table->AddLineItem($type);
            if ($amount > 0)
            {
                $html_table->AddLineItem($amount_str);
                $html_table->AddLineItem('');
                $html_table->AddLineItem($balance_str);
            } 
            else if ($amount < 0)
            {
                $html_table->AddLineItem('');
                $html_table->AddLineItem($amount_str);
                $html_table->AddLineItem($balance_str);
            }
            else
            {
                $html_table->AddLineItem('');
                $html_table->AddLineItem('');
                $html_table->AddLineItem($balance_str);
            }
            $html_table->RowFeed();
        }

        $output = $html_select_account_form . $html_table->GetHtmlTable();
    }
    else
    {
        $output = 'You must sign in to see your transactions.<br>';
    }

    return $output;
}

function Withdraw()
{
    if(is_user_logged_in())
    {
        $currency = 'uBTC';

        if ( ! empty( $_REQUEST['select_account'] ) ) {
            $show_account_str = SanitizeInputText( $_REQUEST['select_account'] );
            $account_selected = new AccountIdTypeClass(0);
            $account_selected->SetDataFromString($show_account_str);
        }
        else
        {
            $account_selected = null;
        }

        $user_handler = new UserHandlerClass();

        $withdraw_output = '';
        if(!empty($_REQUEST['select_account'])) {
            $from_account_id_str = SanitizeInputText( $_REQUEST['select_account'] );
            $to_account_id_str = intval(SanitizeInputText($_REQUEST['depost_account']));
            $amount_str = intval(SanitizeInputText($_REQUEST['amount']));

            $from_account_id = new AccountIdTypeClass(intval($from_account_id_str));
            $to_account_id = new AccountIdTypeClass(intval($to_account_id_str));
            $amount = new ValueTypeClass(intval($amount_str));

            $transaction_id = $user_handler->MakeTransactionToAccount($from_account_id, $to_account_id, $amount);
            if (!is_null($transaction_id))
            {
                $withdraw_output .= 'Transfered OK.<br>';
                $withdraw_output .= GetFormattedCurrency($amount->GetInt(), $currency, true) . ' sent from account '. $from_account_id->GetFormatedString() . ' to ' . $to_account_id->GetFormatedString() . '<br>';
                $my_transaction_id = $transaction_id->GetInt() - 1; // Making a internal transfer creats two transactions records in database. My record was the previous one.
                $withdraw_output .= '(Transaction ID = ' . $my_transaction_id . ')<br>';
            }
            else
            {
                $withdraw_output .= 'Transfer ERROR!<br>';
                $withdraw_output .= 'Unable to send '. GetFormattedCurrency($amount->GetInt(), $currency, true) . ' from account '. $from_account_id->GetFormatedString() . ' to ' . $to_account_id->GetFormatedString() . '<br>';
                $withdraw_output .= '<br>';
            }
        }
        else
        {
            $withdraw_output .= '<br>';
            $withdraw_output .= '<br>';
            $withdraw_output .= '<br>';
        }

        $account_data_list = $user_handler->GetAccountInfoListCurrentUser();

        $output = '<form name="bcf_withdraw_form">';
        $output .= '<table style="border-style:none;" width="100%"><tr>';
        $output .= '<td style="border-style:none;" width="30%">From my account:</td>';

        $output .= '<td style="border-style:none;">';

        $output .= MakeHtmlSelectOptions($user_handler, $account_data_list, $account_selected, $currency);

        $output .= '</td>';

        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">To account:</td><td style="border-style:none;"><input type="text" value="" id="bcf_bitcoinbank_deposit_account" name="depost_account" /></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;">Amount:</td><td style="border-style:none;"><input type="text" id="bcf_bitcoinbank_withdraw_amount" name="amount" /></td>';
        $output .= '</tr><tr>';
        $output .= '<td style="border-style:none;"></td><td style="border-style:none;"><a href="withdraw"><input type="submit" value="Withdraw"/></a></td>';
        $output .= '</tr></table>';
        $output .= '</form>';

        $output .= '<br>';

    }
    else
    {
        $output = 'You must log in to make withdrawal.<br>';
    }


    return $output . $withdraw_output;
}

function ListUserCheques()
{
    if (is_user_logged_in())
    {
        $currency = 'uBTC';

        if ( ! empty( $_REQUEST['cheque'] ) )
        {
            $cheque_id_val = SanitizeInputInteger($_REQUEST['cheque']);
            $cheque_id = new ChequeIdTypeClass($cheque_id_val);

            $user_handler = new UserHandlerClass();
            $cheque = $user_handler->GetCheque($cheque_id);

            $html_table = new HtmlTableClass();

            $html_table->AddLineItem('Cheque No.:');
            $html_table->AddLineItem($cheque->GetChequeId()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Value:');
            $html_table->AddLineItem($cheque->GetValue()->GetFormattedCurrencyString($currency, true));
            $html_table->RowFeed();

            $html_table->AddLineItem('Cheque status:');
            $html_table->AddLineItem($cheque->GetChequeState()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Issuer Name:');
            $html_table->AddLineItem($cheque->GetIssuerName()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Issuer Address');
            $html_table->AddLineItem($cheque->GetIssuerAddress()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Issue date/time:');
            $html_table->AddLineItem($cheque->GetIssueDateTime()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Expire date/time:');
            $html_table->AddLineItem($cheque->GetExpireDateTime()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Escrow date/time:');
            $html_table->AddLineItem($cheque->GetEscrowDateTime()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Fixed fee:');
            $html_table->AddLineItem($cheque->GetFixedFee()->GetFormattedCurrencyString($currency, true));
            $html_table->RowFeed();

            $html_table->AddLineItem('Collection fee');
            $html_table->AddLineItem($cheque->GetCollectionFee()->GetFormattedCurrencyString($currency, true));
            $html_table->RowFeed();

            $html_table->AddLineItem('Will be stamped:');
            $html_table->AddLineItem($cheque->GetStamp()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Collection url:');
            $html_table->AddLineItem($cheque->GetCollectUrl()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s name:');
            $html_table->AddLineItem($cheque->GetReceiverName()->GetString());
            $html_table->RowFeed();

            $address = $cheque->GetReceiverAddress()->GetString();
            $address = str_replace("\r\n",'<br>' , $address);
            $html_table->AddLineItem('Receiver\'s address:');
            $html_table->AddLineItem($address);
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s web site:');
            $html_table->AddLineItem($cheque->GetReceiverUrl()->GetString(), $cheque->GetReceiverUrl()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s e-mail:');
            $html_table->AddLineItem($cheque->GetReceiverEmail()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s business no.:');
            $html_table->AddLineItem($cheque->GetReceiverBusinessNo()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s registration country:');
            $html_table->AddLineItem($cheque->GetReceiverRegCountry()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s Wallet:');
            $html_table->AddLineItem($cheque->GetReceiverWallet()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Receiver\'s reference:');
            $html_table->AddLineItem($cheque->GetReceiverReference()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Attached user\'s reference:');
            $html_table->AddLineItem($cheque->GetUserReference()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Attached user\'s name:');
            $html_table->AddLineItem($cheque->GetUserName()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Attached user\'s address:');
            $html_table->AddLineItem($cheque->GetUserAddress()->GetString());
            $html_table->RowFeed();

            $html_table->AddLineItem('Secret token:');
            $html_table->AddLineItem($cheque->GetNounce()->GetString());
            $html_table->RowFeed();

            $html_table2 = new HtmlTableClass();
            $html_table2->AddLineItem('Account No.:');
            $html_table2->AddLineItem($cheque->GetOwnerAccountId()->GetString());
            $html_table2->RowFeed();

            $html_table2->AddLineItem('Description:');
            $html_table2->AddLineItem($cheque->GetDescription()->GetString());
            $html_table2->RowFeed();

            $output = '<h3>Details for Cheque No. ' . strval($cheque_id_val) . '</h3>';
            $output .= '<b>Public data:</b><br>This information is included in cheque and sent to cheque receiver.';
            $output .= $html_table->GetHtmlTable();
            $output .= '<b>Private data:</b><br>Information not included in the cheque.';
            $output .= $html_table2->GetHtmlTable();
            $output .= '<br><h3>Developer\'s details</h3>';
            $output .= '<b>Bitcoin Cheque in JSON format:</b></b><br>' . $cheque->GetJson();
        }
        else
        {
            if( ! empty($_REQUEST['select_account']))
            {
                $show_account_str = SanitizeInputText($_REQUEST['select_account']);
                $account_selected = new _AccountIdTypeClass(0);
                $account_selected->SetDataFromString($show_account_str);
            }
            else
            {
                $account_selected = null;
            }

            $user_handler      = new UserHandlerClass();
            $account_data_list = $user_handler->GetAccountInfoListCurrentUser();


            if($account_selected == null)
            {
                $account_data     = $account_data_list[0];
                $account_selected = $account_data->GetAccountId();
            }
            $cheque_list = $user_handler->GetChequeListCurrentUser($account_selected);

            $html_select_account_form = MakeHtmlFormSelectAccount($user_handler, $account_data_list, $account_selected, $currency);

            $html_table = new HtmlTableClass();
            $html_table->AddLineItem('Cheque No.');
            $html_table->AddLineItem('Issue<br>Date/Time');
            $html_table->AddLineItem('Expire<br>Date/Time');
            $html_table->AddLineItem('Escrow<br>Date/Time');
            $html_table->AddLineItem('State');
            $html_table->AddLineItem('Amount');
            $html_table->RowFeed();

            foreach(array_reverse($cheque_list) as $cheque)
            {
                $cheque_id       = $cheque->GetChequeId();
                $issue_datetime  = $cheque->GetIssueDateTime();
                $expire_datetime = $cheque->GetExpireDateTime();
                $escrow_datetime = $cheque->GetEscrowDateTime();
                $state           = $cheque->GetChequeState();
                $amount          = $cheque->GetValue();
                $details_link    = site_url() . '/index.php/cheques?cheque=' . $cheque_id->GetString();

                $html_table->AddLineItem($cheque_id->GetString(), $details_link);
                $timestamp_str = str_replace(' ', '<br>', $issue_datetime->GetString());
                $html_table->AddLineItem($timestamp_str);
                $timestamp_str = str_replace(' ', '<br>', $expire_datetime->GetString());
                $html_table->AddLineItem($timestamp_str);
                $timestamp_str = str_replace(' ', '<br>', $escrow_datetime->GetString());
                $html_table->AddLineItem($timestamp_str);
                $html_table->AddLineItem($state->GetString());
                $html_table->AddLineItem(GetFormattedCurrency($amount->GetInt(), $currency, true));

                $html_table->RowFeed();
            }

            $output = $html_select_account_form;
            $output .= 'Cheques draw from account ' . $account_selected->GetString() . ':<br>';
            $output .= $html_table->GetHtmlTable();
        }
    }
    else
    {
        $output = 'You must sign in to see your cheques.<br>';
    }

    return $output;
}

function TestCheque()
{
    if (is_user_logged_in())
    {
        $cheque_handler = new ChequeHandlerClass();

        $account_id       = new AccountIdTypeClass(3);
        $account_password = new PasswordTypeClass("abc123");
        $amount           = new ValueTypeClass(7);

        $output      = "Issue cheque...";
        $reference   = new TextTypeClass('12fd4d');
        $expire_seconds = 60;
        $escrow_seconds = 3600;
        
        $cheque = $cheque_handler->IssueCheque( $account_id, $account_password, $amount, $expire_seconds, $escrow_seconds, $reference );

        if(is_null($cheque))
        {
            $output .= 'Failed' . '<br>';
        }
        else
        {
            $output .= 'OK' . '<br><br>Cheque:<br>';
            $output .= $cheque->GetJson() . '<br>';
        }
    }

    return $output;
}

function ActivatePlugin()
{
    /* To let it be recreated */
    delete_option( BCF_BITCOINBANK_ADMIN_USER_ID );
    delete_option( BCF_BITCOINBANK_CHEQUE_ESCROW_ACCOUNT_ID );

    DB_CreateOrUpdateDatabaseTables();
    
    $user_handler = new UserHandlerClass();
    $user_handler->CreateAdminBankUser();
}

function DeactivatePlugin()
{
}


/* Add AJAX handlers */
// Note! Need to have both actions below, "nopriv" and other! Otherwise wordpress will not return json object.

// Can be tested i browser: /wp-admin/admin-ajax.php?action=bcf_bitcoinbank_process_ajax_request_cheque&account=3&amount=762&passwd=abc123&ref=47
add_action('wp_ajax_nopriv_bcf_bitcoinbank_process_ajax_request_cheque', 'BCF_BitcoinBank\ProcessAjaxRequestCheque');
add_action('wp_ajax_bcf_bitcoinbank_process_ajax_request_cheque', 'BCF_BitcoinBank\ProcessAjaxRequestCheque');

add_action('wp_ajax_nopriv_bcf_bitcoinbank_process_ajax_validate_cheque', 'BCF_BitcoinBank\ProcessAjaxValidateCheque');
add_action('wp_ajax_bcf_bitcoinbank_process_ajax_validate_cheque', 'BCF_BitcoinBank\ProcessAjaxValidateCheque');

/* Add shortcodes */
add_shortcode('bcf_bitcoinbank_list_user_transactions', 'BCF_BitcoinBank\ListUserTransactions');
add_shortcode('bcf_bitcoinbank_withdraw', 'BCF_BitcoinBank\Withdraw');
add_shortcode('bcf_bitcoinbank_list_user_cheques', 'BCF_BitcoinBank\ListUserCheques');
add_shortcode('bcf_bitcoinbank_test_cheque', 'BCF_BitcoinBank\TestCheque');

register_activation_hook(__FILE__, 'BCF_BitcoinBank\ActivatePlugin');
register_deactivation_hook(__FILE__, 'BCF_BitcoinBank\DeactivatePlugin');
