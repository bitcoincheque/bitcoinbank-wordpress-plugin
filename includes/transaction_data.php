<?php
/**
 * Bank transaction data class library.
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

require_once('data_collection_base_class.php');
require_once('data_types.php');

define ('TRANSACTION_DATA_CLASS_NAME', __NAMESPACE__ . '\TransactionDataClass');

class TransactionDataClass extends DataBaseClass
{
    /* Database table name: */
    const DB_TABLE_NAME = 'bcf_bank_transaction';

    /* List of table field names: */
    const DB_FIELD_TRANSACTION_ID = 'transaction_id';
    const DB_FIELD_DATETIME = 'datetime';
    const DB_FIELD_ACCOUNT_ID = 'account_id';
    const DB_FIELD_TYPE = 'type';
    const DB_FIELD_AMOUNT = 'amount';
    const DB_FIELD_BALANCE = 'balance';

    /* Metadata describing database field and data properties: */
    protected $MetaData = array(
        self::DB_FIELD_TRANSACTION_ID => array(
            'class_type'    => 'TransactionIdTypeClass',
            'db_field_name' => self::DB_FIELD_TRANSACTION_ID,
            'db_primary_key'=> true,
            'default_value' => 0
        ),
        self::DB_FIELD_DATETIME => array(
            'class_type'    => 'DateTimeTypeClass',
            'db_field_name' => self::DB_FIELD_DATETIME,
            'db_primary_key'=> false,
            'default_value' => ''
        ),
        self::DB_FIELD_ACCOUNT_ID    => array(
            'class_type'    => 'AccountIdTypeClass',
            'db_field_name' => self::DB_FIELD_ACCOUNT_ID,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::DB_FIELD_TYPE => array(
            'class_type'    => 'TransactionDirTypeClass',
            'db_field_name' => self::DB_FIELD_TYPE,
            'db_primary_key'=> false,
            'default_value' => 'NA'
        ),
        self::DB_FIELD_AMOUNT => array(
            'class_type'    => 'ValueTypeClass',
            'db_field_name' => self::DB_FIELD_AMOUNT,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::DB_FIELD_BALANCE => array(
            'class_type'    => 'ValueTypeClass',
            'db_field_name' => self::DB_FIELD_BALANCE,
            'db_primary_key'=> false,
            'default_value' => 0
        )
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function SetTransactionId($transaction_id)
    {
        if(SanitizeAccountId($transaction_id))
        {
            return $this->SetDataObject(self::DB_FIELD_TRANSACTION_ID, $transaction_id);
        }
        return false;
    }

    public function GetTransactionId()
    {
        return $this->GetDataObjects(self::DB_FIELD_TRANSACTION_ID);
    }

    public function SetDateTime($datetime)
    {
        if(SanitizeDateTime($datetime))
        {
            return $this->SetDataObject(self::DB_FIELD_DATETIME, $datetime);
        }
        return false;
    }
    
    public function GetDateTime()
    {
        return $this->GetDataObjects(self::DB_FIELD_DATETIME);
    }

    public function SetAccountId($account_id)
    {
        if(SanitizeAccountId($account_id))
        {
            return $this->SetDataObject(self::DB_FIELD_ACCOUNT_ID, $account_id);
        }
        return false;
    }
    
    public function GetAccountId()
    {
        return $this->GetDataObjects(self::DB_FIELD_ACCOUNT_ID);
    }

    public function SetTransactionType($transaction_type)
    {
        if(SanitizeTransactionType($transaction_type))
        {
            return $this->SetDataObject(self::DB_FIELD_TYPE, $transaction_type);
        }
        return false;
    }
    
    public function GetTransactionType()
    {
        return $this->GetDataObjects(self::DB_FIELD_TYPE);
    }

    public function SetTransactionAmount($amount)
    {
        if(SanitizeAmount($amount))
        {
            return $this->SetDataObject(self::DB_FIELD_AMOUNT, $amount);
        }
        return false;
    }
    
    public function GetTransactionAmount()
    {
        return $this->GetDataObjects(self::DB_FIELD_AMOUNT);
    }

    public function SetTransactionBalance($balance)
    {
        if(SanitizeAmount($balance))
        {
            return $this->SetDataObject(self::DB_FIELD_BALANCE, $balance);
        }
        return false;
    }
    
    public function GetTransactionBalance()
    {
        return $this->GetDataObjects(self::DB_FIELD_BALANCE);
    }
}
