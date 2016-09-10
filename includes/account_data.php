<?php
/**
 * Bank Account data class library
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

define ('BCF_BITCOINBANK_ACCOUNT_DATA_CLASS_NAME', __NAMESPACE__ . '\AccountDataClass');

class AccountDataClass extends DataBaseClass
{
    /* Database table name: */
    const DB_TABLE_NAME = 'bcf_bank_accounts';

    /* List of table field names: */
    const DB_FIELD_ACCOUNT_ID = 'account_id';
    const DB_FIELD_USER_ID = 'user_id';
    const DB_FIELD_PASSWORD = 'password';
    const DB_FIELD_NAME = 'name';
    const DB_FIELD_CURRENCY = 'currency';

    /* Metadata describing database fields and data properties: */
    protected $MetaData = array(
        self::DB_FIELD_ACCOUNT_ID => array(
            'class_type'    => 'AccountIdTypeClass',
            'db_field_name' => self::DB_FIELD_ACCOUNT_ID,
            'db_primary_key'=> true,
            'default_value' => 0
        ),
        self::DB_FIELD_USER_ID => array(
            'class_type'    => 'UserIdTypeClass',
            'db_field_name' => self::DB_FIELD_USER_ID,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::DB_FIELD_PASSWORD => array(
            'class_type'    => 'PasswordTypeClass',
            'db_field_name' => self::DB_FIELD_PASSWORD,
            'db_primary_key'=> false,
            'default_value' => ''
        ),
        self::DB_FIELD_NAME => array(
            'class_type'    => 'NameTypeClass',
            'db_field_name' => self::DB_FIELD_NAME,
            'db_primary_key'=> false,
            'default_value' => 'Unnamed'
        ),
        self::DB_FIELD_CURRENCY => array(
            'class_type'    => 'CurrencyTypeClass',
            'db_field_name' => self::DB_FIELD_CURRENCY,
            'db_primary_key'=> false,
            'default_value' => 'TestBTC'
        )
    );


    public function __construct()
    {
        parent::__construct();
    }

    public function GetAccountId()
    {
        return $this->GetDataObjects(self::DB_FIELD_ACCOUNT_ID);
    }

    public function SetAccountId($account_id)
    {
        $result = false;

        if(SanitizeAccountId($account_id))
        {
            $result = $this->SetDataObject(self::DB_FIELD_ACCOUNT_ID, $account_id);
        }

        return $result;
    }
    
    
    public function GetOwnersUserId()
    {
        return $this->GetDataObjects(self::DB_FIELD_USER_ID);
    }
    
    public function SetAccountOwner($bank_user_id)
    {
        $result = false;
        
        if(SanitizeBankUserId($bank_user_id))
        {
            $result = $this->SetDataObject(self::DB_FIELD_USER_ID, $bank_user_id);
        }
        
        return $result;
    }
    
    public function SetPassword($password)
    {
        $result = false;

        if(SanitizePassword($password))
        {
            $result = $this->SetDataObject(self::DB_FIELD_PASSWORD, $password);
        }

        return $result;
    }
    
    public function CheckPassword($password)
    {
        $result = false;

        if(SanitizePassword($password))
        {
            $account_password = $this->GetDataObjects(self::DB_FIELD_PASSWORD);

            if($password == $account_password)
            {
                $result = true;
            }
        }

        return $result;
    }
    
    public function GetAccountName()
    {
        return $this->GetDataObjects(self::DB_FIELD_NAME);
    }

    public function SetCurrency($currency)
    {
        $result = false;

        if(SanitizeCurrency($currency))
        {
            $result = $this->SetDataObject(self::DB_FIELD_CURRENCY, $currency);
        }

        return $result;
    }

    public function GetCurrency()
    {
        return $this->GetDataObjects(self::DB_FIELD_CURRENCY);
    }

}