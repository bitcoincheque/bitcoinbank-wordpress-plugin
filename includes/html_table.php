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
    private $Style;

    public function __construct($text, $link, $style)
    {
        $this->Text = $text;
        $this->Link = $link;
        $this->Style = $style;
    }

    public function GetText()
    {
        return $this->Text;
    }

    public function GetLink()
    {
        return $this->Link;
    }

    public function GetStyle()
    {
        return $this->Style;
    }
}

class HtmlTableClass
{
    private $table_rows = array();
    private $current_line = array();

    public function AddLineItem($text, $link='', $style='')
    {
        $cell_item = new TableCellClass($text, $link, $style);
        $this->current_line[] = $cell_item;
    }

    public function RowFeed($row_link='')
    {
        $row = new TableRowClass($this->current_line);
        $this->table_rows[] = $row;
        $this->current_line = array();
    }
    
    public function GetHtmlTable($style = '')
    {
        $html = '<table';
        if($style != '')
        {
            $html .= ' ' . $style;
        }
        $html .= '>';

        foreach($this->table_rows as $row)
        {
            $html .= '<tr>';
            foreach($row->GetCellItems() as $cell_item)
            {
                $item_text = $cell_item->GetText();
                $item_link = $cell_item->GetLink();
                $cell_style = $cell_item->GetStyle();

                $html .= '<td';
                if($cell_style != '')
                {
                    $html .= ' ' . $cell_style;
                }
                $html .= '>';

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