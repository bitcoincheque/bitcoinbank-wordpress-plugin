<?php
/**
 * Generic html table library written in php.
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

Class TableRowClass
{
    private $CellItems;
    private $RowLink;

    public function __construct($cell_items)
    {
        $this->CellItems = $cell_items;
    }

    public function GetCellItems()
    {
        return $this->CellItems;
    }

    public function GetRowLink()
    {
        return $this->RowLink;
    }
}

Class TableCellClass
{
    private $Text;
    private $Link;

    public function __construct($text, $link)
    {
        $this->Text = $text;
        $this->Link = $link;
    }

    public function GetText()
    {
        return $this->Text;
    }

    public function GetLink()
    {
        return $this->Link;
    }
}

class HtmlTableClass
{
    private $table_rows = array();
    private $current_line = array();

    public function AddLineItem($text, $link='')
    {
        $cell_item = new TableCellClass($text, $link);
        $this->current_line[] = $cell_item;
    }

    public function RowFeed($row_link='')
    {
        $row = new TableRowClass($this->current_line);
        $this->table_rows[] = $row;
        $this->current_line = array();
    }
    
    public function GetHtmlTable()
    {
        $html = '<table>';

        foreach($this->table_rows as $row)
        {
            $html .= '<tr>';
            foreach($row->GetCellItems() as $cell_item)
            {
                $item_text = $cell_item->GetText();
                $item_link = $cell_item->GetLink();

                $html .= '<td>';

                if($item_link != '')
                {
                    $html .= '<a href="' . $item_link . '">';
                }

                $html .= $item_text;

                if($item_link != '')
                {
                    $html .= '</a>';
                }

                $html .= '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</table>';
        
        return $html;
    }
    
}