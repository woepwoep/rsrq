<?php

/*
 *  Copyright notice
 *
 *  (c) 2006-2017 WEBFORMAT
 *
 *  All rights reserved
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


class tx_wfqbe_api_xml2data_structure
{
    var $extKey = 'tx_wfqbe_api';    // The extension key.

    var $cObj;
    var $conf;

    var $parser;
    var $node_stack = array();


    /**
     * Main function
     */
    function main($conf, $cObj)
    {
        $this->conf = $conf;
        $this->cObj = $cObj;
        //$this->logged = $GLOBALS["TSFE"]->fe_user->user;
        return;
    }


    /**
     * If a string is passed in, parse it right away.
     */
    function xml2array($xmlstring = "")
    {
        if ($xmlstring) return ($this->parse($xmlstring));
        return (true);
    }

    /**
     * Parse a text string containing valid XML into a multidimensional array
     * located at rootnode.
     */
    function parse($xmlstring = "")
    {
        // set up a new XML parser to do all the work for us
        $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_element_handler($this->parser, "startElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        // Build a Root node and initialize the node_stack...
        $this->node_stack = array();
        $this->startElement(null, "root", array());

        // parse the data and free the parser...
        xml_parse($this->parser, $xmlstring);
        xml_parser_free($this->parser);

        // recover the root node from the node stack
        $rnode = array_pop($this->node_stack);

        // return the root node...
        return ($rnode);
    }

    /**
     * Start a new Element. This means we push the new element onto the stack
     * and reset it's properties.
     */
    function startElement($parser, $name, $attrs)
    {
        // create a new node...
        $node = array();
        $node["_NAME"] = $name;
        foreach ($attrs as $key => $value) {
            $node[$key] = $value;
        }

        $node["_DATA"] = "";
        $node["_ELEMENTS"] = array();

        // add the new node to the end of the node stack
        array_push($this->node_stack, $node);
//echo("SE: ".$name."<br>");
    }

    /**
     * End an element. This is done by popping the last element from the
     * stack and adding it to the previous element on the stack.
     */
    function endElement($parser, $name)
    {
        // pop this element off the node stack
        $node = array_pop($this->node_stack);
        $node["_DATA"] = trim($node["_DATA"]);

        // and add it an an element of the last node in the stack...
        $lastnode = count($this->node_stack);
        array_push($this->node_stack[$lastnode - 1]["_ELEMENTS"], $node);
//echo("EE: ".$name."<br>");
    }

    /**
     * Collect the data onto the end of the current chars.
     */
    function characterData($parser, $data)
    {
        // add this data to the last node in the stack...
        $lastnode = count($this->node_stack);
        $this->node_stack[$lastnode - 1]["_DATA"] .= $data;
//echo($data."<br>");
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_api_xml2data_structure.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/lib/class.tx_wfqbe_api_xml2data_structure.php']);
}


