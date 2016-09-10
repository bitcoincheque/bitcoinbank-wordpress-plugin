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

require_once('includes/banking_app_interface.php');
require_once('includes/payment_interface.php');
require_once('includes/user_interface.php');


function SanitizeInputText($text)
{
    if (preg_match('/^[A-Za-z0-9 .,;:_~\/\-!@#\$%\^&\*\(\)]+$/', $text))
    {
        return $text;
    }
    else
    {
        return null;
    }
}

function SanitizeInputInteger($text)
{
    if (preg_match('/^[1-9][0-9]{0,15}$/', $text))
    {
        $value = intval($text);
    }
    else
    {
        $value = null;
    }

    return $value;
}

function SafeReadGetString($key)
{
    if(!empty($_GET[$key]))
    {
        return SanitizeInputText($_REQUEST[$key]);
    }
    else
    {
        return null;
    }
}

function SafeReadGetInt($key)
{
    if(!empty($_GET[$key]))
    {
        return SanitizeInputInteger($_REQUEST[$key]);
    }
    else
    {
        return null;
    }
}

function SafeReadPostString($key)
{
    if(!empty($_POST[$key]))
    {
        return SanitizeInputText($_POST[$key]);
    }
    else
    {
        return null;
    }
}

function SafeReadPostInt($key)
{
    if(!empty($_POST[$key]))
    {
        return SanitizeInputInteger($_POST[$key]);
    }
    else
    {
        return null;
    }
}


function BankingAppInterface_RequestCheque()
{
    $username                       = SafeReadPostString('username');
    $password                       = SafeReadPostString('password');
    $input_data['account']          = SafeReadPostInt('account');
    $input_data['payment_request']  = SafeReadPostString('payment_request');

    $bankingapp = new BankingAppInterface($username, $password);
    $response_data = $bankingapp->RequestCheque($input_data);

    echo json_encode($response_data);
    die();
}

function BankingAppInterface_DrawCheque()
{
    $username                       = SafeReadPostString('username');
    $password                       = SafeReadPostString('password');
    $input_data['account']          = SafeReadPostInt('account');
    $input_data['amount']           = SafeReadPostInt('amount');
    $input_data['currency']         = SafeReadPostString('currency');
    $input_data['receivers_name']   = SafeReadPostString('receivers_name');
    $input_data['bank_send_to']     = SafeReadPostString('bank_send_to');
    $input_data['lock']             = SafeReadPostString('lock');
    $input_data['memo']             = SafeReadPostString('memo');
    $input_data['cc_me']            = SafeReadPostString('cc_me');

    $bankingapp = new BankingAppInterface($username, $password);
    $response_data = $bankingapp->DrawCheque($input_data);

    echo json_encode($response_data);
    die();
}

function BankingAppInterface_GetAccountList()
{
    $username                       = SafeReadPostString('username');
    $password                       = SafeReadPostString('password');

    $bankingapp = new BankingAppInterface($username, $password);
    $response_data = $bankingapp->GetAccountList();

    echo json_encode($response_data);
    die();
}

function BankingAppInterface_GetAccountDetails()
{
    $username                       = SafeReadPostString('username');
    $password                       = SafeReadPostString('password');
    $input_data['account']          = SafeReadPostInt('account');

    $bankingapp = new BankingAppInterface($username, $password);
    $response_data = $bankingapp->GetAccountInfo($input_data);

    echo json_encode($response_data);
    die();
}

function BankingAppInterface_GetTransactionList()
{
    $username                       = SafeReadPostString('username');
    $password                       = SafeReadPostString('password');
    $input_data['account']          = SafeReadPostInt('account');

    $bankingapp = new BankingAppInterface($username, $password);
    $response_data = $bankingapp->GetTransactionList($input_data);

    echo json_encode($response_data);
    die();
}


function PaymentInterface_ValidateCheque()
{
    $input_data['cheque_no']        = SafeReadPostInt('cheque_no');
    $input_data['access_code']      = SafeReadPostString('access_code');
    $input_data['hash']             = SafeReadPostString('hash');

    $payment_interface = new PaymentInterface();
    $response_data = $payment_interface->ValidateCheque($input_data);

    echo json_encode($response_data);
    die();
}

function PaymentInterface_CreateChequePng()
{
    $input_data['cheque_no']        = SafeReadGetInt('cheque_no');
    $input_data['access_code']      = SafeReadGetString('access_code');
    $input_data['hash']             = SafeReadGetString('hash');

    $payment_interface = new PaymentInterface();
    $payment_interface->CreateChequePng($input_data);
    die();
}


function UserInterface_DisplayTransactionList()
{
    $input_data['select_account']   = SafeReadGetInt('select_account');

    $user_interface = new UserInterface();
    $output = $user_interface->Display_TransactionList($input_data);
    return $output;
}


function UserInterface_Withdraw()
{
    $input_data['account']          = SafeReadGetInt('account');
    $input_data['depost_account']   = SafeReadGetInt('depost_account');
    $input_data['amount']           = SafeReadGetInt('amount');

    $user_interface = new UserInterface();
    $output = $user_interface->Withdraw($input_data);
    return $output;
}

function UserInterface_DisplayChequeDetails()
{
    $input_data['cheque_no']        = SafeReadGetInt('cheque_no');
    $input_data['access_code']      = SafeReadGetString('access_code');
    $input_data['hash']             = SafeReadGetString('hash');

    $user_interface = new UserInterface();
    $output = $user_interface->DisplayChequeDetails($input_data);
    return $output;
}

function UserInterface_DisplayChequeList()
{
    $input_data['select_account']   = SafeReadGetInt('select_account');

    $user_interface = new UserInterface();
    $output = $user_interface->DisplayChequeList($input_data);
    return $output;
}

function UserInterface_ClaimCheque()
{
    $input_data['cheque_no']        = SafeReadGetInt('cheque_no');
    $input_data['access_code']      = SafeReadGetString('access_code');
    $input_data['bitcoin_address']  = SafeReadGetString('bitcoin_address');
    $input_data['confirm']          = SafeReadGetString('confirm');

    $user_interface = new UserInterface();
    $output = $user_interface->ClaimCheque($input_data);
    return $output;
}

function UserInterface_DrawCheque()
{
    $input_data['state']            = SafeReadGetInt('state');
    $input_data['select_account']   = SafeReadGetInt('select_account');
    $input_data['amount']           = SafeReadGetInt('amount');
    $input_data['cheque_no']        = SafeReadGetInt('cheque_no');
    $input_data['expired_days']     = SafeReadGetInt('expired_days');
    $input_data['receiver_name']    = SafeReadGetString('receiver_name');
    $input_data['receiver_email']   = SafeReadGetString('receiver_email');
    $input_data['access_code']      = SafeReadGetString('access_code');
    $input_data['send_email']       = SafeReadGetString('send_email');

    $user_interface = new UserInterface();
    $output = $user_interface->DrawCheque($input_data);
    return $output;
}

function UserInterface_DisplayProfile()
{
    $input_data['full_name']        = SafeReadGetString('full_name');
    $input_data['country']          = SafeReadGetString('country');

    $user_interface = new UserInterface();
    $output = $user_interface->DisplayProfile($input_data);
    return $output;
}

function UserInterface_DisplayPaymentForm()
{
    $input_data['amount']           = SafeReadGetString('amount');
    $input_data['select_account']   = SafeReadGetInt('select_account');
    $input_data['request']          = SafeReadGetString('request');
    $input_data['receiver_name']    = SafeReadGetString('receiver_name');
    $input_data['lock']             = SafeReadGetString('lock');
    $input_data['currency']         = SafeReadGetString('currency');
    $input_data['receiver_reference'] = SafeReadGetString('receiver_reference');
    $input_data['paylink']          = SafeReadGetString('paylink');
    $input_data['payment_request']  = SafeReadGetString('payment_request');

    $user_interface = new UserInterface();
    $output = $user_interface->DisplayPaymentForm($input_data);
    return $output;
}


function add_meta_data()
{
    $banking_app_url = site_url() . '/wp-admin/admin-ajax.php';

    echo '<link rel="BankingApp" href="' . $banking_app_url . '">' . PHP_EOL;
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


/* Banking App interface */
add_action('wp_ajax_nopriv_request_cheque', 'BCF_BitcoinBank\BankingAppInterface_RequestCheque');
add_action('wp_ajax_request_cheque', 'BCF_BitcoinBank\BankingAppInterface_RequestCheque');

add_action('wp_ajax_nopriv_draw_cheque', 'BCF_BitcoinBank\BankingAppInterface_DrawCheque');
add_action('wp_ajax_draw_cheque', 'BCF_BitcoinBank\BankingAppInterface_DrawCheque');

add_action('wp_ajax_nopriv_get_account_list', 'BCF_BitcoinBank\BankingAppInterface_GetAccountList');
add_action('wp_ajax_get_account_list', 'BCF_BitcoinBank\BankingAppInterface_GetAccountList');

add_action('wp_ajax_nopriv_get_account_details', 'BCF_BitcoinBank\BankingAppInterface_GetAccountDetails');
add_action('wp_ajax_get_account_details', 'BCF_BitcoinBank\BankingAppInterface_GetAccountDetails');

add_action('wp_ajax_nopriv_get_transactions', 'BCF_BitcoinBank\BankingAppInterface_GetTransactionList');
add_action('wp_ajax_get_transactions', 'BCF_BitcoinBank\BankingAppInterface_GetTransactionList');


/* Payment interface */
add_action('wp_ajax_nopriv_validate_payment_cheque', 'BCF_BitcoinBank\PaymentInterface_ValidateCheque');
add_action('wp_ajax_validate_payment_cheque', 'BCF_BitcoinBank\PaymentInterface_ValidateCheque');

add_action('wp_ajax_nopriv_bcf_bitcoinbank_get_cheque_png', 'BCF_BitcoinBank\PaymentInterface_CreateChequePng');
add_action('wp_ajax_bcf_bitcoinbank_get_cheque_png', 'BCF_BitcoinBank\PaymentInterface_CreateChequePng');


/* Shortcodes for user interface */
add_shortcode('bcf_bitcoinbank_list_user_transactions', 'BCF_BitcoinBank\UserInterface_DisplayTransactionList');
add_shortcode('bcf_bitcoinbank_withdraw', 'BCF_BitcoinBank\UserInterface_Withdraw');
add_shortcode('bcf_bitcoinbank_list_user_cheques', 'BCF_BitcoinBank\UserInterface_DisplayChequeList');
add_shortcode('bcf_bitcoinbank_cheque_details', 'BCF_BitcoinBank\UserInterface_DisplayChequeDetails');
add_shortcode('bcf_bitcoinbank_draw_cheque', 'BCF_BitcoinBank\UserInterface_DrawCheque');
add_shortcode('bcf_bitcoinbank_claim_cheque', 'BCF_BitcoinBank\UserInterface_ClaimCheque');
add_shortcode('bcf_bitcoinbank_profile', 'BCF_BitcoinBank\UserInterface_DisplayProfile');
add_shortcode('bcf_bitcoinbank_payment', 'BCF_BitcoinBank\UserInterface_DisplayPaymentForm');


/* Hooks */
add_action('wp_head', 'BCF_BitcoinBank\add_meta_data');

register_activation_hook(__FILE__, 'BCF_BitcoinBank\ActivatePlugin');
register_deactivation_hook(__FILE__, 'BCF_BitcoinBank\DeactivatePlugin');
