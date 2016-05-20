<?php
/**
 * Bank user data class library.
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

define ('USER_DATA_CLASS_NAME', __NAMESPACE__ . '\UserDataClass');

class UserDataClass extends DataBaseClass
{
    /* Database table name: */
    const DB_TABLE_NAME = 'bcf_bank_users';

    /* List of table field names: */
    const DB_FIELD_USER_ID = 'user_id';
    const DB_FIELD_WP_USER_ID = 'wp_user';
    const DB_FIELD_NAME = 'name';

    /* Metadata describing database fields and data properties: */
    protected $MetaData = array
    (
        self::DB_FIELD_USER_ID => array(
            'class_type'    => 'UserIdTypeClass',
            'db_field_name' => self::DB_FIELD_USER_ID,
            'db_primary_key'=> true,
            'default_value' => 0
        ),
        self::DB_FIELD_WP_USER_ID => array(
            'class_type'    => 'WpUserIdTypeClass',
            'db_field_name' => self::DB_FIELD_WP_USER_ID,
            'db_primary_key'=> false,
            'default_value' => 0
        ),
        self::DB_FIELD_NAME => array(
            'class_type'    => 'TextTypeClass',
            'db_field_name' => self::DB_FIELD_NAME,
            'db_primary_key'=> false,
            'default_value' => ''
        )
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function SetBankUserId($bank_user_id)
    {
        $result = false;

        if(SanitizeBankUserId($bank_user_id))
        {
            $result = $this->SetDataObject(self::DB_FIELD_USER_ID, $bank_user_id);
        }

        return $result;
    }
    
    public function GetBankUserId()
    {
        return $this->GetDataObjects(self::DB_FIELD_USER_ID);
    }
    
    public function SetWpUserId($wp_user_id)
    {
        $result = false;

        if(SanitizeWpUserId($wp_user_id))
        {
            $result = $this->SetDataObject(self::DB_FIELD_WP_USER_ID, $wp_user_id);
        }

        return $result;
    }
    
    public function GetWpUserId()
    {
        return $this->GetDataObjects(self::DB_FIELD_WP_USER_ID);
    }

    public function SetName($name)
    {
        $result = false;

        if(SanitizeText($name))
        {
            $result = $this->SetDataObject(self::DB_FIELD_NAME, $name);
        }

        return $result;
    }
    
    public function GetName()
    {
        return $this->GetDataObjects(self::DB_FIELD_NAME);
    }
}

function SanitizeBankUserData($bank_user)
{
    if(gettype($bank_user) == 'object')
    {
        if(get_class($bank_user) == 'UserDataClass' )
        {
            return $bank_user->SanitizeData();
        }
    }
    return false;
}
