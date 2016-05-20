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

namespace BCF_BitcoinBank;

require_once('user_data.php');
require_once('account_data.php');
require_once('transaction_data.php');
require_once('cheque_data.php');
require_once('data_types.php');

class DatabaseInterfaceClass
{
    protected function __construct()
    {
    }

    protected function DB_GetCurrentTimeStamp()
    {
        $now = current_time('timestamp', true);
        $now_str = date('Y-m-d H:i:s', $now);
        $datetime = new DateTimeTypeClass($now_str);
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

            $record_list = $this->DB_GetRecordListByFieldValue(TransactionDataClass::DB_TABLE_NAME, TransactionDataClass::DB_FIELD_TRANSACTION_ID, $field_value);

            $transaction_list = $this->DB_LoadRecordsIntoDataCollection($record_list, TRANSACTION_DATA_CLASS_NAME);

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

            $record_list = $this->DB_GetRecordListByFieldValue(TransactionDataClass::DB_TABLE_NAME, TransactionDataClass::DB_FIELD_ACCOUNT_ID, $field_value);

            $transaction_list = $this->DB_LoadRecordsIntoDataCollection($record_list, TRANSACTION_DATA_CLASS_NAME);
        }

        return $transaction_list;
    }

    protected function DB_GetChequeList($issuer_account_id)
    {
        $cheque_list = array();

        if(SanitizeAccountId($issuer_account_id))
        {
            $field_value = $issuer_account_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue(ChequeDataClass::DB_TABLE_NAME, ChequeDataClass::DB_FIELD_USER_ACCOUNT_ID, $field_value);

            $cheque_list = $this->DB_LoadRecordsIntoDataCollection($record_list, CHEQUE_DATA_CLASS_NAME);
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

    protected function DB_GetBankUserData($bank_user_id)
    {
        $bank_user_data = null;

        if(SanitizeBankUserId($bank_user_id))
        {
            $field_value = $bank_user_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue(UserDataClass::DB_TABLE_NAME, UserDataClass::DB_FIELD_USER_ID, $field_value);
            if ( count( $record_list ) == 1 )
            {
                $record         = $record_list[0];
                $bank_user_data = new UserDataClass;
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

            $record_list = $this->DB_GetRecordListByFieldValue(UserDataClass::DB_TABLE_NAME, UserDataClass::DB_FIELD_WP_USER_ID, $field_value);
            if(!empty($record_list))
            {
                if ( count( $record_list ) == 1 )
                {
                    $record         = $record_list[0];
                    $bank_user_data = new UserDataClass;
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

            $record_list = $this->DB_GetRecordListByFieldValue(AccountDataClass::DB_TABLE_NAME, AccountDataClass::DB_FIELD_ACCOUNT_ID, $field_value);
            if ( count( $record_list ) == 1 )
            {
                $account_data = new AccountDataClass;
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

            $record_list = $this->DB_GetRecordListByFieldValue(AccountDataClass::DB_TABLE_NAME, AccountDataClass::DB_FIELD_USER_ID, $field_value);

            $account_info_list = $this->DB_LoadRecordsIntoDataCollection($record_list, ACCOUNT_DATA_CLASS_NAME);
        }

        return $account_info_list;
    }

    protected function DB_GetChequeData($cheque_id)
    {
        $cheque_data = NULL;

        if(SanitizeChequeId($cheque_id))
        {
            $field_value = $cheque_id->GetString();

            $record_list = $this->DB_GetRecordListByFieldValue(ChequeDataClass::DB_TABLE_NAME, ChequeDataClass::DB_FIELD_CHEQUE_ID, $field_value);
            if ( count( $record_list ) == 1 )
            {
                $cheque_data = new ChequeDataClass();
                $cheque_data->SetDataFromDbRecord($record_list[0]);
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

            $record_list = $this->DB_GetRecordListByFieldValue(ChequeDataClass::DB_TABLE_NAME, ChequeDataClass::DB_FIELD_STATE, $field_value);

            $cheque_data_list = $this->DB_LoadRecordsIntoDataCollection($record_list, CHEQUE_DATA_CLASS_NAME);
        }

        return $cheque_data_list;
    }

    protected function DB_GetBalance($account_id)
    {
        $balance = null;

        if(SanitizeAccountId($account_id))
        {
            global $wpdb;

            $prefixed_table_name = $wpdb->prefix . TransactionDataClass::DB_TABLE_NAME;
            $account_id_str      = $account_id->GetString();

            $sql = "SELECT MAX(transaction_id) FROM " . $prefixed_table_name . " WHERE account_id=" . $account_id_str;
            $wpdb->query( $sql, ARRAY_A );

            if ( ! $wpdb->last_error )
            {
                $records = $wpdb->last_result;
                $row     = (array) $records[0];

                $last_transaction_id_int = $row['MAX(' . TransactionDataClass::DB_FIELD_TRANSACTION_ID . ')'];
                $last_transaction_id = new TransactionIdTypeClass(intval($last_transaction_id_int));

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
    DB_CreateOrUpdateDatabaseTable('\\' . USER_DATA_CLASS_NAME);
    DB_CreateOrUpdateDatabaseTable('\\' . ACCOUNT_DATA_CLASS_NAME);
    DB_CreateOrUpdateDatabaseTable('\\' . TRANSACTION_DATA_CLASS_NAME);
    DB_CreateOrUpdateDatabaseTable('\\' . CHEQUE_DATA_CLASS_NAME);
}

