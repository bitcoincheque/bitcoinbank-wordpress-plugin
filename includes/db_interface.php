<?php
/**
 * Bitcoin Bank database interface for Wordpress.
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

require_once('user_data.php');
require_once('account_data.php');
require_once('transaction_data.php');
require_once('cheque_data.php');
require_once('data_types.php');

class BCF_BitcoinBank_DatabaseInterfaceClass
{
    const BCF_BITCOINBANK_DB_TABLE_TRANSACTIONS = 'bcf_bank_transaction';
    const BCF_BITCOINBANK_DB_TABLE_USERS =        'bcf_bank_users';
    const BCF_BITCOINBANK_DB_TABLE_ACCOUNTS =     'bcf_bank_accounts';
    const BCF_BITCOINBANK_DB_TABLE_CHEQUES =      'bcf_bank_cheques';

    protected function __construct()
    {
    }

    protected function DB_GetCurrentTimeStamp()
    {
        $now = current_time('timestamp', true);
        $now_str = date('Y-m-d H:i:s', $now);
        $datetime = new BCF_BitcoinBank_DateTimeTypeClass($now_str);
        return $datetime;
    }

    protected function DB_FormatedTimeStampStr($timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    protected function DB_GetRecordListByFieldValue($database_table, $field_name, $field_value)
    {
        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . $database_table;

        $sql = "SELECT * FROM " . $prefixed_table_name . " WHERE " . $field_name . "='" . $field_value . "'";

        $record_list = $wpdb->get_results($sql, ARRAY_A);

        if($wpdb->last_error)
        {
            $record_list = NULL;
        }

        return $record_list;
    }

    private function DB_LoadRecordsIntoDataCollection($record_list, $data_class)
    {
        $data_collection_list = array();

        foreach ( $record_list as $record )
        {
            $data_collection = new $data_class;
            if ( $data_collection->SetDataFromDbRecord( $record ) )
            {
                $data_collection_list[] = $data_collection;
            }
            else
            {
                $data_collection_list = array();
                break;
            }
        }
        return $data_collection_list;
    }

    protected function DB_GetTransaction($transaction_id)
    {
        $transaction = array();

        if(SanitizeTransactionId($transaction_id))
        {
            $field_value = $transaction_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( self::BCF_BITCOINBANK_DB_TABLE_TRANSACTIONS, 'transaction_id', $field_value );

            $transaction_list = $this->DB_LoadRecordsIntoDataCollection($record_list, 'BCF_Bank_TransactionDataClass');

            if(count( $transaction_list ) == 1)
            {
                $transaction = $transaction_list[0];
            }
        }

        return $transaction;
    }

    protected function DB_GetTransactionList($account_id)
    {
        $transaction_list = array();

        if(SanitizeAccountId($account_id))
        {
            $field_value = $account_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( self::BCF_BITCOINBANK_DB_TABLE_TRANSACTIONS, 'account_id', $field_value );

            $transaction_list = $this->DB_LoadRecordsIntoDataCollection($record_list, 'BCF_Bank_TransactionDataClass');
        }

        return $transaction_list;
    }

    protected function DB_GetChequeList($issuer_account_id)
    {
        $cheque_list = array();

        if(SanitizeAccountId($issuer_account_id))
        {
            $field_value = $issuer_account_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( self::BCF_BITCOINBANK_DB_TABLE_CHEQUES, 'issuer_account_id', $field_value );

            $cheque_list = $this->DB_LoadRecordsIntoDataCollection($record_list, 'BCF_Bank_ChequeDataClass');
        }
        return $cheque_list;
    }

    protected function DB_WriteRecord($data_collection)
    {
        $id_value = 0;
        global $wpdb;
        
        $prefixed_table_name = $wpdb->prefix . $data_collection->GetDbTableName();
        
        $new_data = $data_collection->GetDataArray();
        
        if(!empty($new_data))
        {
            $wpdb->insert( $prefixed_table_name, $new_data );

            if ( ! $wpdb->last_error )
            {
                $id_value = $wpdb->insert_id;
            }
        }
        
        return $id_value;            
    }

    protected function DB_UpdateRecord($data_collection)
    {
        $result = false;
        global $wpdb;

        $prefixed_table_name = $wpdb->prefix . $data_collection->GetDbTableName();

        $new_data = $data_collection->GetDataArray();

        $primary_id_key = $data_collection->GetPrimaryKeyArray();

        if(!empty($new_data) and !empty($primary_id_key))
        {
            $wpdb->update( $prefixed_table_name, $new_data, $primary_id_key);

            if (!$wpdb->last_error )
            {
                $result = true;
            }
        }

        return $result;
    }

    /*
    protected function DB_CreateBankUser($wp_user, $name)
    {
        $user_id = 0;

        if(SanitizeWpUserId($wp_user) and SanitizeText($name))
        {
            global $wpdb;
            $prefixed_table_name = $wpdb->prefix . self::BCF_BITCOINBANK_DB_TABLE_USERS;

            $newdata = array(
                'wp_user' => $wp_user->GetString(),
                'name'    => $name->GetString(),
            );

            $wpdb->insert( $prefixed_table_name, $newdata );

            if ( ! $wpdb->last_error )
            {
                $user_id = $wpdb->insert_id;
            }
        }

        return $user_id;
    }
    */
    
    protected function DB_GetBankUserData($bank_user_id)
    {
        $bank_user_data = null;

        if(SanitizeBankUserId($bank_user_id))
        {
            $field_value = $bank_user_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( BCF_BitcoinBankUserDataClass::DB_TABLE_NAME, BCF_BitcoinBankUserDataClass::DB_FIELD_USER_ID, $field_value );
            if ( count( $record_list ) == 1 )
            {
                $record         = $record_list[0];
                $bank_user_data = new BCF_BitcoinBankUserDataClass;
                $bank_user_data->SetDataFromDbRecord( $record );
            }
        }
        
        return $bank_user_data;
    }

    protected function DB_GetBankUserDataFromWpUser($wp_user_id)
    {
        $bank_user_data = null;

        if(SanitizeWpUserId($wp_user_id))
        {
            $field_value = $wp_user_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( BCF_BitcoinBankUserDataClass::DB_TABLE_NAME, BCF_BitcoinBankUserDataClass::DB_FIELD_WP_USER_ID, $field_value );
            if(!empty($record_list))
            {
                if ( count( $record_list ) == 1 )
                {
                    $record         = $record_list[0];
                    $bank_user_data = new BCF_BitcoinBankUserDataClass;
                    $bank_user_data->SetDataFromDbRecord( $record );
                }
            }
        }

        return $bank_user_data;
    }

    protected function DB_GetAccountData($account_id)
    {
        $account_data = NULL;

        if(SanitizeAccountId($account_id))
        {
            $field_value = $account_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( BCF_BitcoinAccountDataClass::DB_TABLE_NAME, BCF_BitcoinAccountDataClass::DB_FIELD_ACCOUNT_ID, $field_value );
            if ( count( $record_list ) == 1 )
            {
                $account_data = new BCF_BitcoinAccountDataClass;
                $account_data->SetDataFromDbRecord( $record_list[0] );
            }
        }

        return $account_data;
    }


    protected function DB_GetAccountDataList($bank_user_id)
    {
        $account_info_list = array();

        if(SanitizeBankUserId($bank_user_id))
        {
            $field_value = $bank_user_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( BCF_BitcoinAccountDataClass::DB_TABLE_NAME, BCF_BitcoinAccountDataClass::DB_FIELD_USER_ID, $field_value );

            $account_info_list = $this->DB_LoadRecordsIntoDataCollection($record_list, 'BCF_BitcoinAccountDataClass');
        }

        return $account_info_list;
    }

    protected function DB_GetChequeData($cheque_id)
    {
        $cheque_data = NULL;

        if(SanitizeChequeId($cheque_id))
        {
            $field_value = $cheque_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( BCF_Bank_ChequeDataClass::DB_TABLE_NAME, BCF_Bank_ChequeDataClass::DB_FIELD_CHEQUE_ID, $field_value );
            if ( count( $record_list ) == 1 )
            {
                $cheque_data = new BCF_Bank_ChequeDataClass();
                $cheque_data->SetDataFromDbRecord( $record_list[0] );
            }
        }

        return $cheque_data;
    }

    protected function DB_GetChequeDataListByState($cheque_state)
    {
        $cheque_data_list = array();

        if(SanitizeChequeState($cheque_state))
        {
            $field_value = $cheque_state->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue( BCF_Bank_ChequeDataClass::DB_TABLE_NAME, BCF_Bank_ChequeDataClass::DB_FIELD_STATE, $field_value );

            $cheque_data_list = $this->DB_LoadRecordsIntoDataCollection($record_list, 'BCF_Bank_ChequeDataClass');
        }

        return $cheque_data_list;
    }

    /*
    protected function DB_CreateBankAccount($user_id, $password)
    {
        $account_id = 0;

        if(SanitizeBankUserId($user_id) and SanitizePassword($password))
        {
            global $wpdb;
            $prefixed_table_name = $wpdb->prefix . BCF_BITCOINBANK_DB_TABLE_ACCOUNTS;

            $newdata = array(
                'user_id'  => $user_id->GetString(),
                'password' => $password->GetString(),
            );

            $wpdb->insert( $prefixed_table_name, $newdata );

            if ( ! $wpdb->last_error )
            {
                $account_id = $wpdb->insert_id;

                $timestamp = $this->DB_GetCurrentTimeStamp();
                $type      = 'INITIAL';
                $amount    = 0;
                $balance   = 0;

                $transaction_id = $this->DB_WriteTransaction( $account_id, $timestamp, $type, $amount, $balance );
                if ( $transaction_id == 0 )
                {
                    //debug_print('ERROR: Transaction write error.');

                    $account_id = 0;
                }
            }
        }

        return $account_id;
    }
    */
    /*
    protected function DB_WriteTransaction($account_id, $datetime, $transaction_type, $amount, $balance)
    {
        $transaction_id = null;

        if(SanitizeAccountId($account_id)
            and SanitizeDateTime($datetime)
            and SanitizeTransactionType($transaction_type)
            and SanitizeAmount($amount)
            and SanitizeAmount($balance))
        {
            global $wpdb;
            $prefixed_table_name = $wpdb->prefix . self::BCF_BITCOINBANK_DB_TABLE_TRANSACTIONS;

            $account_id_str = $account_id->GetString();
            $datetime_str   = $datetime->GetString();
            $type_str       = $transaction_type->GetString();
            $amount_str     = $amount->GetString();
            $balance_str    = $balance->GetString();

            $newdata = array(
                'account_id' => strval( $account_id_str ),
                'datetime'   => strval( $datetime_str ),
                'type'       => strval( $type_str ),
                'amount'     => strval( $amount_str ),
                'balance'    => strval( $balance_str ),
            );

            $wpdb->insert( $prefixed_table_name, $newdata );
            if ( ! $wpdb->last_error )
            {
                $transaction_id_val = $wpdb->insert_id;
                $transaction_id = new BCF_BitcoinBank_TransactionIdTypeClass($transaction_id_val);

                if ( $transaction_id_val == 0 )
                {
                    debug_print( 'ERROR: Transaction id 0 created.' );
                    die();
                }
            }
        }
        return $transaction_id;
    }
    */

    protected function DB_GetBalance($account_id)
    {
        $balance = null;

        if(SanitizeAccountId($account_id))
        {
            global $wpdb;

            $prefixed_table_name = $wpdb->prefix . self::BCF_BITCOINBANK_DB_TABLE_TRANSACTIONS;
            $account_id_str      = $account_id->GetString();

            $sql = "SELECT MAX(transaction_id) FROM " . $prefixed_table_name . " WHERE account_id=" . $account_id_str;
            $wpdb->query( $sql, ARRAY_A );

            if ( ! $wpdb->last_error )
            {
                $records = $wpdb->last_result;
                $row     = (array) $records[0];

                $last_transaction_id_int = $row['MAX(transaction_id)'];
                $last_transaction_id = new BCF_BitcoinBank_TransactionIdTypeClass(intval($last_transaction_id_int));

                if ($last_transaction_id->HasValidData())
                {
                    $transaction = $this->DB_GetTransaction($last_transaction_id);

                    if(!empty($transaction))
                    {
                        $balance = $transaction->GetTransactionBalance();
                    }
                }
            }
        }

        return $balance;
    }

    /*
    protected function DB_WriteChequeRecord($issuer_account_id, $issue_datetime, $expire_datetime, $escrow_datetime, $amount, $secret_token, $reference)
    {
        $cheque_id = 0;

        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . BCF_BITCOINBANK_DB_TABLE_CHEQUES;

        $amount_str  =strval($amount);
        $issue_datetime_str = $this->DB_FormatedTimeStampStr($issue_datetime);
        $expire_datetime_str = $this->DB_FormatedTimeStampStr($expire_datetime);
        $escrow_datetime_str = $this->DB_FormatedTimeStampStr($escrow_datetime);
        $issuer_account_id_str = strval($issuer_account_id);

        $newdata = array(
            'issue_datetime'    => strval($issue_datetime_str),
            'expire_datetime'   => strval($expire_datetime_str),
            'escrow_datetime'   => strval($escrow_datetime_str),
            'state'             => 'UNCLAIMED',
            'amount'            => $amount_str,
            'issuer_account_id' => $issuer_account_id_str,
            'secret_token'      => $secret_token,
            'receiver_reference'=> $reference,
        );

        $wpdb->insert($prefixed_table_name, $newdata);
        if (!$wpdb->last_error)
        {
            $cheque_id = $wpdb->insert_id;

            if ($cheque_id == 0)
            {
                debug_print('ERROR: Cheque id 0 created.');
                die();
            }
        }
        return $cheque_id;
    }
    */
    /*
    protected function DB_UpdateChequeRecord($cheque_id, $new_state)
    {
        $result = false;

        global $wpdb;
        $prefixed_table_name = $wpdb->prefix . BCF_BITCOINBANK_DB_TABLE_CHEQUES;
        $cheque_id_str  =strval($cheque_id);

        $newdata = array(
            'state' => $new_state
        );

        $where = array(
            'cheque_id' => $cheque_id_str
        );

        $wpdb->update($prefixed_table_name, $newdata, $where);

        if (!$wpdb->last_error)
        {
            $result = true;
        }
        return $result;
    }
    */
}

function DB_CreateOrUpdateDatabaseTable($class)
{
    require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $data_collector = new $class;
    $sql = $data_collector->GetCreateMysqlTableText();

    dbDelta($sql);
}

function DB_CreateOrUpdateDatabaseTables()
{

    DB_CreateOrUpdateDatabaseTable('BCF_BitcoinBankUserDataClass');
    DB_CreateOrUpdateDatabaseTable('BCF_BitcoinAccountDataClass');
    DB_CreateOrUpdateDatabaseTable('BCF_Bank_TransactionDataClass');
    DB_CreateOrUpdateDatabaseTable('BCF_Bank_ChequeDataClass');
}

