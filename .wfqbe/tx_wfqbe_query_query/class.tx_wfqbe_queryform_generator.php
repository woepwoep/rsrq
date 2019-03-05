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

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_api_array2xml.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_api_xml2array.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . 'mod2/class.tx_wfqbe_belib.php');

require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('adodb') . 'adodb/adodb.inc.php');

class tx_wfqbe_queryform_generator
{
    var $extKey = 'wfqbe';    // The extension key.
    //array globale che contiene le coppie chiave valore (es : orderby->prova.nome)degli elementi utilizzati per costruire il form
    var $wfqbe;
    //array globale che contiene le coppie chiave valore degli elementi utilizzati per costruire la text area per inserimento a mano
    var $rawwfqbe;
    //array globale che contiene le coppie chiave valore degli elementi ausiliari utilizzati per memorizzare le stringe xml
    //delle query temporane
    var $pass;
    //contiene tutti i possibili operatori che si possono usare nella clausola where
    var $operatoriWhere = array(">", ">=", "<", "<=", "=", "!=", "LIKE", "ILIKE", "IS", "IS NOT", "IN", "NOT IN", "BETWEEN", "NOT BETWEEN");
    //contiene tutti i possibili tipi di join che si possono usare nella clausola from
    var $operatoriJoin = array("NATURAL JOIN", "JOIN", "LEFT OUTER JOIN", "RIGHT OUTER JOIN", "FULL OUTER JOIN");
    //contiene tutti i possibili operatori che si possono usare nella clausola from e piu' precisamente nel costrutto ON
    var $operatoriOn = array("=");
    //contiene tutte le possibili funzioni che si possono usare nella clausola having
    var $aggregationFunctions = array("COUNT", "MAX", "MIN", "AVG", "SUM");
    //contiene tutti i possibili operatori che si possono usare nella clausola having
    var $havingOperator = array(">", ">=", "<", "<=", "=", "!=");
    //variabili usate nella funzione showOn() e contengono rispettivamente i nomi dei campi contenuti nell'ultima tabella(i-esima) selezionata
    //e in tutte le altre (0...i-1)
    var $rightOn = "";
    var $leftOn = "";
    var $operator = array("/", "*", "+", "-");
    //contiene tutti i possibili operatori possono usare utilizzare le operazioni insiemistiche
    var $setOperator = array("UNION", "EXCEPT", "INTERSECT");
    //contiene l'html  temporaneo della query creata tramite il form.Il contenuto viene inserito come valore dll'elemento pass[hiddenqbe]
    //utilizzato nella modalita' RAW QUERY
    var $hiddenqbe;
    //contiene l'html  temporaneo della query creata tramite la text area.Il contenuto viene inserito come valore dll'elemento pass[hiddenraw]
    //utilizzato nelle modalita' QBE
    var $hiddenraw;
    // contiene la modalita' selezionata(QBE o RAW QUERY)
    var $wfqbefunction;

    //var $setoperator;

    function init()
    {
        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_array2xml");
        $API2 = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_xml2array");

        $this->wfqbefunction = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wfqbefunction');
        if ($this->wfqbefunction == "RAWQUERY" && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('rawwfqbe') != "") {//caso in cui resto nella modalita' RAWQUERY
            $this->rawwfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('rawwfqbe');
            $this->pass = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pass');
            $this->hiddenqbe = $this->pass['hiddenqbe'];

        } elseif ($this->wfqbefunction == "QBE" && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wfqbe') != "") {//caso in cui resto nella modalita' QBE
            $this->wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wfqbe');
            $this->pass = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pass');
            $this->hiddenraw = $this->pass['hiddenraw'];

        } elseif ($this->wfqbefunction == "RAWQUERY" && \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('rawwfqbe') == "") {//caso in cui passo da QBE a RAWQUERY
            $this->wfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('wfqbe');
            $this->pass = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pass');

            if ($this->wfqbe != "")
                $this->hiddenqbe = $API->array2xml($this->wfqbe);
            else //se provengo dal qbe in modalita' impossibile memorizzo in hiddenqbe il contenuto del campo pass[hiddenqbe]
                $this->hiddenqbe = $this->pass['hiddenqbe'];

            //$this->rawwfqbe=$API2->xml2array("<tempwfqbe>".$this->pass['hiddenraw']."</tempwfqbe>");
            $this->rawwfqbe = $API2->xml2array($this->pass['hiddenraw']);
            $this->rawwfqbe = $this->rawwfqbe;

        } else {//caso in cui passo da RAWQUERY a QBE
            $this->rawwfqbe = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('rawwfqbe');
            $this->pass = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pass');

            if ($this->rawwfqbe != "")
                $this->hiddenraw = $API->array2xml($this->rawwfqbe);
            else
                $this->hiddenraw = "";
            if ($this->pass['hiddenqbe'] != "")
                $this->wfqbe = $API2->xml2array($this->pass['hiddenqbe']);
            //$this->wfqbe=$this->wfqbe['tempwfqbe'];
        }

        /*se "provengo" dal plugin(e percio' l'array wfqbe e' vuoto) allora richiamo la funzione parseQuery che ha il compito di riempire l'array
		 wfqbe e di conseguenza creare il form.*/
        if ($this->wfqbe == "" && $this->rawwfqbe == "") {
            $this->parseQuery();
        } else {
            $var = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
            $this->rawwfqbe['orgId'] = intval($var['uid']);
            $this->rawwfqbe['orgPid'] = intval($var['pid']);
        }
    }

    /**
     *
     * Utilizza la funzione xml2array() definita nella classe tx_wfqbe_api_xml2array che converte un file xml in un array
     *
     * @return    [void]
     */

    function parseQuery()
    {

        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_xml2array");

        $var = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
        //estraggo la query salvata dal database (modalita' xml) e la converto in array
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('query', 'tx_wfqbe_query', 'tx_wfqbe_query.uid=' . intval($var['uid']) . ' AND tx_wfqbe_query.deleted!=1', '', '', '');
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        $saveXml = $API->xml2array($row["query"]);
        $saveXml = $saveXml['contentwfqbe'];

        $saveXml['rawwfqbe']['orgId'] = intval($var['uid']);
        $saveXml['rawwfqbe']['orgPid'] = intval($var['pid']);

        //siccome per rispettare la sintassi xml aggiungo dei tag contenitori quando salvo(vedi funzione saveQuery) adesso estraggo solo
        //la parte utile per costruire il form o la text area.
        $this->wfqbe = $saveXml['wfqbe'];
        //se wfqbe e' vuoto(ho salvato un file xml del tipo ...<wfqbe></wfqbe>)setto il primo campo select(riferito alla prima tabella) a vuoto
        if ($this->wfqbe == "")
            $this->wfqbe[0]['table'][0] = "";
        $this->rawwfqbe = $saveXml['rawwfqbe'];
        $this->wfqbefunction = $saveXml['function'];
    }

    /**
     * Crea una stringa che contiene l'xml della query creata.
     * Questa funzione viene richiamata nel file index.php quando si salva oppure si salva e si chiede il file.
     * Utilizza la funzione array2xml() definita nella classe tx_wfqbe_api_array2xml che converte un array in un file(stringa) xml.
     *
     * @return    [string]    $xml: stringa che contiene la query in formato xml
     */
    function saveQuery()
    {
        $API = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_array2xml");
        $API2 = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_api_xml2array");
        $xml = "";
        //Aggiungo all'inizio e alla fine dei tag contenitori per rispettare le regole XML.
        //Se compongo la query tramite la textArea(e quindi senza usare il form) racchiudo l'html tra i tag <rawquery> e </rawquery>.Se
        //invece costruisco la query servendomi del form allora racchiudo l'html tra <wfqbe> e </wfqbe>.
        //In ogni caso salvo la modalita' (QBE o RAW QBE) tra i tag <function> e </function> e percio' , sempre per rispettare la sintassi xml,
        //racchiudo tutto tra i tag <contentwfqbe> e </contentwfqbe>.

        if ($this->rawwfqbe['rawquery'] != "") {
            $saveArray['contentwfqbe']['rawwfqbe']['rawquery'] = $this->rawwfqbe['rawquery'];
            $saveArray['contentwfqbe']['function'] = $this->wfqbefunction;
            //$xml = "<contentwfqbe><rawwfqbe>".$API->array2xml($this->rawwfqbe)."</rawwfqbe><function>".$this->wfqbefunction."</function></contentwfqbe>";
            $xml = $API->array2xml($saveArray);
        } else {
            //t3lib_utility_Debug::debug($this->pass['hiddenraw']);
            $this->rawwfqbe = $API2->xml2array($this->pass['hiddenraw']);
            //t3lib_utility_Debug::debug($this->rawwfqbe);
            if ($this->rawwfqbe['rawquery'] != "") {
                $saveArray['contentwfqbe']['rawwfqbe']['rawquery'] = $this->rawwfqbe['rawquery'];
                $saveArray['contentwfqbe']['function'] = "RAWQUERY";
                $saveArray['contentwfqbe']['invalidwfqbe'] = 1;
                $xml = $API->array2xml($saveArray);
                //$xml = "<contentwfqbe><rawwfqbe>".$API->array2xml($this->rawwfqbe)."<invalidwfqbe>1</invalidwfqbe></rawwfqbe><function>RAWQUERY</function></contentwfqbe>";
            } else {
                asort($this->wfqbe);
                $saveArray['contentwfqbe']['wfqbe'] = $this->wfqbe;
                $saveArray['contentwfqbe']['function'] = $this->wfqbefunction;
                $xml = $API->array2xml($saveArray);
                //$xml = "<contentwfqbe><wfqbe>".$API->array2xml($this->wfqbe)."</wfqbe><function>".$this->wfqbefunction."</function></contentwfqbe>";
            }
        }
        return $xml;
    }

    /**
     * Crea il form per la creazione della query
     *
     * @param    [type]        $h: puntatore alla connessione al database
     *
     * @return    [string]    $content: stringa che contiene l'html del form
     */

    function showForm($h)
    {
        $content = '<table style="font-size: 0.9em" class="table table-striped">';
        $content .= '<tr class="db_list_normal"><td>' . $this->showMenu() . '</td></tr>';

        //visualizzo il form classico la prima volta(quando non ho salvato niente) oppure quando e' stata selezionata la modalita' QBE e
        //la text area di inserimento a mano della query e' vuota(e cioe' quando rawwfqbe[invalidqbe] e' vuoto )
        if (($this->wfqbefunction == "QBE" || $this->wfqbefunction == "") && ($this->rawwfqbe['invalidwfqbe'] == 0 || $this->rawwfqbe['invalidwfqbe'] == "")) {

            for ($numForm = 0; $numForm < sizeof($this->wfqbe['setoperator']) + 1; $numForm++) {

                $this->leftOn = "";
                $this->rightOn = "";
                if ($numForm % 2 == 0)
                    $backgroundColor = 'db_list_normal';
                else
                    $backgroundColor = 'db_list_normal';
                //$tabelle e' un array che contiene le tabelle presenti nel database selezionato
                $tabelle = $h->MetaTables(false, true);

                $content .= '<tr class="' . $backgroundColor . '" ><td>';
                for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['table']) + 1; $i++) {
                    $content .= $this->showSelectTable($h, $i, $this->wfqbe[$numForm]['table'][$i], $this->wfqbe[$numForm]['table'][$i + 1] == "", $tabelle, $numForm);
                    if ($this->wfqbe[$numForm]['table'][$i] == "")
                        break;
                }
                $content .= '</td></tr>';

                //se non e' stata selezionata nessuna tabella non presento gli elementi
                if ($this->wfqbe[$numForm]['table'][0] != "") {

                    $content .= '<tr class="' . $backgroundColor . '" ><td>';
                    $content .= $this->showDistinctAll($numForm);
                    $content .= '</td></tr>';

                    $content .= '<tr class="' . $backgroundColor . '" ><td>';
                    $content .= $this->showFields($h, $numForm);
                    $content .= '</td></tr>';

                    $content .= '<tr class="' . $backgroundColor . '" ><td>';
                    $content .= $this->showWhere($h, $numForm);
                    $content .= '</td></tr>';

                    $content .= '<tr class="' . $backgroundColor . '" id="groupby"><td>';
                    for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['groupby']) + 1; $i++) {//richiamo la funzione showGroupBy tante volte quanto e' grande l'array
                        $content .= $this->showGroupBy($h, $i, $this->wfqbe[$numForm]['groupby'][$i + 1] == "", $numForm);    //wfqbe['groupby'] piu' una volta perche' si possono specificare molti
                        if ($this->wfqbe[$numForm]['groupby'][$i] == "")                //attributi di raggruppamento e percio' quando l'utente ne seleziona uno
                            break;                                        //si deve dare la possibilita' di selezionarne un'altro.
                    }

                    $content .= '<br /><br /><strong>Custom group by:</strong> <input type="text" name="wfqbe[' . $numForm . '][groupby][custom]" value="' . $this->wfqbe[$numForm]['groupby']['custom'] . '" size="80" />';
                    $content .= '</td></tr>';

                    if ($this->wfqbe[$numForm]['groupby'][0] != "") {
                        $content .= '<tr class="' . $backgroundColor . '" id="having"><td>';
                        $content .= $this->showHaving($h, $numForm);
                        $content .= '</td></tr>';
                    }

                    $content .= '<tr class="' . $backgroundColor . '" id="orderby"><td>';
                    for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['orderby']) + 1; $i++) {
                        $content .= $this->showOrderBy($h, $i, $this->wfqbe[$numForm]['orderby'][$i + 1] == "", $numForm);
                        if ($this->wfqbe[$numForm]['orderby'][$i] == "")
                            break;
                    }
                    $content .= '</td></tr>';

                    $content .= '<tr class="' . $backgroundColor . '" id="setOparations"><td>';
                    $content .= $this->showSetOperations($numForm);
                    $content .= '</td></tr>';

                    $content .= "<input type='hidden' value='" . str_replace("'", '"', $this->hiddenraw) . "'  name='pass[hiddenraw]'/>";

                }

            }
            $content .= '</table>';

            $content .= '<div>';
            $content .= $this->showQuery($i);
            $content .= '</div>';
        }
        //se e' stata selezionata la modalita' QBE ma e' stato inserito qualche cosa nelll'area inserimento query a mano(rawwfqbe[invalidqbe] e'
        //uguale a 1) allora visualizzo un ,assaggio di errore
        elseif ($this->wfqbefunction == "QBE" && $this->rawwfqbe['invalidwfqbe'] == 1) {

            $content .= '<tr class="db_list_normal"><td>';
            $content .= '<strong>Not available.</strong><br/><br/>You are using the RAW method. If you want to create a query with QBE functions, you have to reset the RAWQUERY function with the reset button.<br/>';
            $content .= "<input type='hidden' value='" . str_replace("'", '"', $this->hiddenraw) . "'  name='pass[hiddenraw]'/>";
            //salvo anche il contenuto del campo pass[hiddenqbe] che cotiene l'html del form creato fino a questo momento. Se non lo faccio
            //quando ritorno nella modalita' RAW QUERY perdo l'array wfqbe e non potrei piu' riscostruire la query  fatta fino a quel
            //momento tramite  il form
            $content .= "<input type='hidden' value='" . $this->pass['hiddenqbe'] . "'  name='pass[hiddenqbe]'/>";
            $content .= '</td></tr></table>';

        } //Qunado e' stata selezionata la modalita' RAW QUERY visualizzo l'area di inserimento quary a mano.
        else {
            $content .= '<tr class="db_list_normal"><td>';
            $content .= $this->showInsertQuery();
            $content .= '</td></tr></table>';
        }

        return $content;
    }

    /**
     * Crea il menu' per la selezione della modalita'
     *
     * @return    [string]    $content: stringa che contiene l'html del form
     */

    function showMenu()
    {
        $content .= '<select onChange="updateForm();"  name="wfqbefunction" title="function" >';

        if ($this->wfqbefunction == "QBE" || $this->wfqbefunction == "") {
            $content .= '<option  value="QBE" selected="true">QBE</option>';
            $content .= '<option  value="RAWQUERY" >RAW QUERY</option>';
        } else {
            $content .= '<option  value="QBE" >QBE</option>';
            $content .= '<option  value="RAWQUERY" selected="true">RAW QUERY</option>';
        }

        $content .= '</select>';

        return $content;

    }

    /**
     * Crea un elemento "select" dove gli elementi "option" contengono le tabelle del database selezionato.
     *
     * @param    [type]        $h: puntatore alla connessione
     * @param    [intero]    $numSelect: numero identificativo degli elementi select(posizione corrente dell'array wfqbe). Es: 0,1,2,....,n
     * @param    [type]        $selectedTable:posizione corrente dell'array wfqbw
     * @param    [type]        $nextEmpty:verifica se il successivo elemento dell'array wfqbe['table'] e' vuoto
     * @param    [type]        $tabelle:
     *
     * @return    [string]    $content: stringa che contiene html degli elementi select per la selezione delle tabelle
     */

    function showSelectTable($h, $numSelect, $selectedTable, $nextEmpty, $tabelle, $numForm)
    {
        //visualizzo "table" solo la prima volta
        if ($numSelect == 0)
            $content .= '<h4>Table : </h4>';

        //inserisco 4 spazi prima di ogni elemento ad eccezione del primo
        if ($numSelect != 0)
            $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";


        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][table][' . $numSelect . ']" title="table[' . $numSelect . ']" >';
        //se il successivo e' vuoto inserisci una opzione vuota per permettere di eliminare una tabella. Se invece la successiva non e'
        //vuota non permetto di annullare(e quindi non metto la opzione vuota) ma solo di modificare
        if ($nextEmpty)
            $content .= '<option  value=""></option>';
        for ($i = 0; $i < sizeof($tabelle); $i++) {
            if ($selectedTable == $tabelle[$i])
                $content .= '<option  value="' . $tabelle[$i] . '" selected="true">' . $tabelle[$i] . '</option>';
            else
                $content .= '<option  value="' . $tabelle[$i] . '">' . $tabelle[$i] . '</option>';
        }
        $content .= '</select>';

        //inserisco 4 spazi dopo di ogni elemento
        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

        // se e' stata selezionata una tabella tabella allora visualizzo un campo input che mi permette di rinominarla
        if ($this->wfqbe[$numForm]['table'][$numSelect] != "") {
            $content .= 'AS&nbsp;&nbsp;&nbsp;&nbsp;';
            $content .= '<input type="text" value="' . $this->wfqbe[$numForm]['renametable'][$numSelect] . '" name="wfqbe[' . $numForm . '][renametable][' . $numSelect . ']"/>';
        }

        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

        //vado a capo 2 volte dopo ogni tabella ad eccezione della prima
        if ($numSelect >= 1) {
            $content .= '<br/>';
            $content .= '<br/>';
        }

        //se non e' l'elemento che visualizza la prima tabella selezionata (wfqbe['table'][0]) e l'elemento corrente che visaulizza la tabella
        //selezionata e' diverso da vuoto allora richiamo la funzione che visualizza il costrutto ON.
        //Inoltre controllo se la clausola join e' diversa da vuota.Infatti se e' vuota vuol dire che ho deciso di effettuare un prodotto
        //cartesiano e percio' l'elemento ON non deve essere visualizzato.
        //Inoltre, non richiamo la funzione se e' stato selezionato il NATURAL JOIN perche' questo tipo di join crea automaticamente una
        //condizione implicita di equijoin per ogniuna coppia di attributi con lo stesso nome.
        if ($this->wfqbe[$numForm]['table'][$numSelect] != "" && $numSelect != 0 && $this->wfqbe[$numForm]['join'][$numSelect - 1] != "" && $this->wfqbe[$numForm]['join'][$numSelect - 1] != "NATURAL JOIN")
            $content .= $this->showON($numSelect, $h, $numForm);

        if ($this->wfqbe[$numForm]['table'][0] != "") {
            //visualizzo l'elemento che contiene le clausole JOIN solo quando seleziono la prima tabella oppure quando il primo join e' stato selezionato
            //Questo perche' quando non seleziono il primo JOIN vuol dire che voglio fare un prodotto cartesiano e percio' non visualizzo
            //i restanti elementi di join
            if ($this->wfqbe[$numForm]['join'][0] != "" || $numSelect == 0) {
                //se l'elemento che contiene la seconda tabella non e' vuoto visualizzo l'elemento che contiene i vari tipi di join
                if ($this->wfqbe[$numForm]['table'][$numSelect] != "")
                    $content .= $this->showJoin($numSelect, $numForm);
            }

        }

        return $content;
    }

    /**
     * Visualizza gli elementi che contengono le clausole di join o prodotto cartesiano
     *
     * @param    [type]         $j:indice elemento corrente
     * @return    [string]     $content:
     */

    function showJoin($j, $numForm)
    {

        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][join][' . $j . ']" title="join[' . $j . ']" >';
        $content .= '<option  value="" ></option>';
        //se dopo aver creato dei join annidati elimino il primo allora setto l'array a vuoto cosi' non mi compare il costrutto ON perche'
        //assumo che si voglia creare un prodotto cartesiano. Non vengono presi in considerazione gli altri join perche' se il primo e' settato
        //non permetto che gli altri siano vuoti
        if ($this->wfqbe[$numForm]['join'][0] == "")
            $this->wfqbe[$numForm]['join'] = "";
        //questo if serve per settare l'elemnto JOIN corrente con il valore JOIN(deciso di default) nel caso in cui si sta facendo dei join annidati e si seleziona
        //una tabella senza selezionare un operatore di join.Questo viene fatto perche' non si permette di costruire una clausola FROM
        //con prodotti cartesiani e join insieme.
        if ($this->wfqbe[$numForm]['join'][$j - 1] != "" && $this->wfqbe[$numForm]['join'][$j] == "" && $this->wfqbe[$numForm]['table'][$j + 1] != "") {
            $content .= '<option  value="JOIN" selected="true">JOIN</option>';
            //setto questo valore perche' altrimenti non mi verrebbe visualizzato l'elemento ON associato.Questo perche' quando richiamo
            //la funzione che costruisce il costrutto ON controllo che il join associato sia diverso da vuoto e se non faccio questo
            //assegnamento l'elemento JOIN e' uguale a vuoto e percio' l'elemento ON non viene visualizzato.
            $this->wfqbe[$numForm]['join'][$j] = "JOIN";
        }
        for ($i = 0; $i < sizeof($this->operatoriJoin); $i++) {
            if ($this->operatoriJoin[$i] == $this->wfqbe[$numForm]['join'][$j])
                $content .= '<option value="' . $this->operatoriJoin[$i] . '" selected="true">' . $this->operatoriJoin[$i] . '</option>';
            else
                $content .= '<option value="' . $this->operatoriJoin[$i] . '">' . $this->operatoriJoin[$i] . '</option>';
        }
        $content .= '</select>';

        return $content;
    }

    /**
     * Visualizza gli elementi che contengono i campi delle tabelle selezionate da utilizzare nell'elemento ON
     *
     * @param    [type]         $i:indice elemento corrente
     * @param    [type]         $h:
     * @param    [type]         $numForm:
     * @return    [string]     $content:
     */

    function showON($i, $h, $numForm)
    {

        $content .= '<div style="margin-left: 8em;">';

        $content .= '<strong>ON : </strong>';
        $content .= "&nbsp;&nbsp;";

        //primo elemento del cosrtutto on.Contiene i campi di tutte le tabelle selezionate ad eccezione dell'ultima i cui campi sono
        //contenuti nel secondo costrutto on.
        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][on1][' . $i . ']" title="left ON[' . $i . ']" >';
        $content .= '<option  value="" ></option>';
        //se leftOn e rightOn sono vuoti vuol dire che sta costruendo il primo elemnto On (il primo join) e percio' devo ricavare i campi della
        //prima tabella selezionata attraverso una connessione al database
        if ($this->leftOn == "" && $this->rightOn == "") {
            $this->leftOn = $h->MetaColumnNames($this->wfqbe[$numForm]['table'][0]);
            if ($this->wfqbe[$numForm]['renametable'][0] != "")
                $tableName = $this->wfqbe[$numForm]['renametable'][0];
            else
                $tableName = $this->wfqbe[$numForm]['table'][0];
            //ogni campo ricavato lo concateno al nome della tabella
            foreach ($this->leftOn AS $key => $value)
                $this->leftOn[$key] = $tableName . "." . $this->leftOn[$key];
        }
        //se leftOn e rightOn non sono vuoti vuol dire che sta costruendo un elemento ON successivo al primo(i-esimo) e percio' faccio un ciclo
        //for sull'array associativo leftOn(costruito nell' if precedente per la prima tabella e incrementato con i campi delle successive
        //tabelle selezionate durante la costuzione del secondo elemento del costrutto ON) e se il campo e'  stato selezionato inserisco
        //l'attributo selected="true"
        foreach ($this->leftOn AS $key => $value) {
            if ($value == $this->wfqbe[$numForm]['on1'][$i])
                $content .= '<option  value="' . $value . '" selected="true">' . $value . '</option>';
            else
                $content .= '<option  value="' . $value . '">' . $value . '</option>';
        }
        $content .= '</select>';

        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

        //elemento che contiene gli operatori dell'on
        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][operatorion][' . $i . ']" title="on operator[' . $i . ']" >';
        $content .= '<option  value="" ></option>';
        for ($p = 0; $p < sizeof($this->operatoriOn); $p++) {
            if ($this->operatoriOn[$p] == $this->wfqbe[$numForm]['operatorion'][$i])
                $content .= '<option value="' . $this->operatoriOn[$p] . '" selected="true">' . $this->operatoriOn[$p] . '</option>';
            else
                $content .= '<option value="' . $this->operatoriOn[$p] . '">' . $this->operatoriOn[$p] . '</option>';
        }
        $content .= '</select>';

        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

        //secondo elemento del cosrtutto on.Contiene i campi dell'ultima tabella selezionata.
        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][on2][' . $i . ']" title="right ON[' . $i . ']" >';
        $content .= '<option  value="" ></option>';
        //il contenuto del secondo elemento del costrutto on deve essere sempre calcolato con una connessione al databasa perche' deve
        //contenere solamente i campi dell'ultima tabella selezionata
        $this->rightOn = $h->MetaColumnNames($this->wfqbe[$numForm]['table'][$i]);
        if ($this->wfqbe[$numForm]['renametable'][$i] != "")
            $tableName = $this->wfqbe[$numForm]['renametable'][$i];
        else
            $tableName = $this->wfqbe[$numForm]['table'][$i];
        foreach ($this->rightOn AS $key => $value) {
            if ($tableName . "." . $value == $this->wfqbe[$numForm]['on2'][$i])
                $content .= '<option  value="' . $tableName . "." . $value . '" selected="true">' . $tableName . "." . $value . '</option>';
            else
                $content .= '<option  value="' . $tableName . "." . $value . '">' . $tableName . "." . $value . '</option>';
            //dopo aver inserito i campi dell'ultima tabella selezionata nell'elemento select inserisco ogni campo nell'array leftOn che
            //deve contenere tutti i campi delle tabelle selezionate ad eccezione dell'ultima
            array_push($this->leftOn, $tableName . "." . $value);
        }
        $content .= '</select>';

        $content .= '</div>';

        $content .= '<br/>';

        return $content;
    }

    /**
     * Visualizza  l'opzione distinct/all
     *
     * @return    [string]    $content: stringa che contiene l'html della text area che contiene tutti i campi della tabella.
     */

    function showDistinctAll($numForm)
    {

        $content .= '<h4>Distinct/All : </h4>';
        $content .= '<select   onChange="updateForm();" name="wfqbe[' . $numForm . '][distinctall]" title="distinct all" >';
        $content .= '<option  value="" ></option>';
        if ($this->wfqbe[$numForm]['distinctall'] == "") {
            $content .= '<option  value="distinct" >DISTINCT</option>';
            $content .= '<option  value="all" >ALL</option>';
        } else {
            if ($this->wfqbe[$numForm]['distinctall'] == 'distinct') {
                $content .= '<option  value="distinct" selected="true">DISTINCT</option>';
                $content .= '<option  value="all">ALL</option>';
            } else {
                $content .= '<option  value="distinct">DISTINCT</option>';
                $content .= '<option  value="all"  selected="true">ALL</option>';
            }
        }
        $content .= '</select>';


        return $content;

    }

    /**
     * Visualizza  le colonne delle tabelle selezionate
     *
     * @param    [type]         $h: puntatore alla connessine
     * @return    [string]    $content: stringa che contiene l'html della text area che contiene tutti i campi della tabella.
     */

    function showFields($h, $numForm)
    {
        //variabile utilizzata per rappresentare l'indice degli elementi fild e selectedfields
        $index = 1;

        $content .= '<h4>Selected fields :</h4>';
        $content .= '<input type="text" size=145 value="' . htmlspecialchars($this->wfqbe[$numForm]['selectedfields']) . '" id="selectedfields' . $numForm . $index . '" name="wfqbe[' . $numForm . '][selectedfields]" title="selected fields"/>';

        $content .= '<br/><br/>';

        $content .= '<strong>Values for the increase : &nbsp;</strong>';
        $content .= '<input type="text" onChange="insertChangeValueAndOperator(\'changevalue' . $numForm . $index . '\',\'selectedfields' . $numForm . $index . '\');updateForm()" size=5  id="changevalue' . $numForm . $index . '" name="wfqbe[' . $numForm . '][changevalue]" title="change value"/>';

        $content .= '<em>&nbsp;&nbsp;&nbsp;&nbsp; Insert number and aritmetic oparator(without spaces). Example : 4*,3+,2-,3/,.....</em>';
        $content .= '<br/><br/>';

        //elemento che contiene le funzioni di aggregazione
        $content .= '<strong>Aggregation function : &nbsp;&nbsp;&nbsp;&nbsp;</strong>';
        $content .= '<select onChange="insertAggregationFunction(\'aggregationfunction' . $numForm . $index . '\',\'selectedfields' . $numForm . $index . '\');updateForm()" id="aggregationfunction' . $numForm . $index . '" name="wfqbe[' . $numForm . '][aggregationfunction]" title="aggregationfunction" >';
        $content .= '<option  value=""></option>';
        for ($i = 0; $i < sizeof($this->aggregationFunctions); $i++)
            $content .= '<option value="' . $this->aggregationFunctions[$i] . '">' . $this->aggregationFunctions[$i] . '</option>';

        $content .= '</select>';

        $content .= '<br/><br/>';
        $content .= '<table style="font-size:1em"><tr valign="top"><td><strong>Fields : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong></td>';
        $content .= '<td>';
        $content .= '<select   onChange="insertField(\'field' . $numForm . $index . '\',\'selectedfields' . $numForm . $index . '\');updateForm()" id="field' . $numForm . $index . '" name="wfqbe[' . $numForm . '][field]" title="fields" multiple="multiple" size="10" >';
        $content .= '<option value="*">*</option>';
        for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['table']); $i++) {
            //se l'i-esima posizione dell'array e' vuota mi fermo altrimenti andrei a estrarre le colonne di una tabella inesistente
            if ($this->wfqbe[$numForm]['table'][$i] == "")
                break;
            if ($this->wfqbe[$numForm]['renametable'][$i] != "")
                $tableName = $this->wfqbe[$numForm]['renametable'][$i];
            else
                $tableName = $this->wfqbe[$numForm]['table'][$i];
            $column = $h->MetaColumnNames($this->wfqbe[$numForm]['table'][$i]);
            foreach ($column AS $key => $value)
                $content .= '<option  value="' . $tableName . "." . $value . '" >' . $tableName . "." . $value . '</option>';
        }
        $content .= '</select>';
        $content .= '</td></tr></table>';
        $content .= '<br/><br/>';

        //elemento di input che permette di rinominare i campi
        $content .= '<strong>AS : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</strong>';
        $content .= '<input type="text" onChange="insertRenamedFields(\'rename' . $numForm . $index . '\',\'selectedfields' . $numForm . $index . '\');updateForm()" size=20  id="rename' . $numForm . $index . '" name="wfqbe[' . $numForm . '][rename]" title="rename fields"/>';
        $content .= '&nbsp;&nbsp;&nbsp;&nbsp;Insert column name (if you want to rename it).';
        return $content;

    }

    /**
     * Crea la clausola where
     *
     * @param    [type]        $h:
     * @return    [string]    $content: stringa che contiene l'html della clausola where.
     */

    function showWhere($h, $numForm)
    {
        //contatore che contiene il numero delle parentesi aperte
        $parentesiAperte = 0;
        $content .= '<h4>Where : </h4>';
        $numTab = 0;
        for ($j = 0; $j < sizeof($this->wfqbe[$numForm]['ao']) + 1; $j++) {

            //$numTab serve per definire di quanto deve essere spostato verso sx l'elemento div che costruisco e si basa sul numero di parentesi
            //aperte.Lo calcolo prima e dopo la costruzione della select che contiene le parentesi da aprire perche' nella costruzione di questo elemento
            //modifico il contatore ($parentesiAperte) che conteggia il numero delle parentesi aperte fino a questo momento.
            //Se non lo facessi il contatore verrebbe aumentato ma la variabile $numTab non verrebbe aggiornata.
            $numTab = $parentesiAperte * 3;

            $content .= '<div style="margin-left:' . $numTab . 'em;">';
            //elemento select che contiene le parentesi da aprire
            $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][parentesiopen][' . $j . ']" title="parentesi open[' . $j . ']" >';
            $content .= '<option  value="" ></option>';
            if ($this->wfqbe[$numForm]['parentesiopen'][$j] == 'open') {
                $content .= '<option  value="open" selected="true">(</option>';
                $parentesiAperte++;
            } else
                $content .= '<option  value="open">(</option>';
            $content .= '</select>';
            $content .= '</div>';

            $numTab = $parentesiAperte * 3;

            $content .= '<div style="margin-left:' . $numTab . 'em;">';
            //elemento select che contiene tutti i campi delle tabelle selezionate
            $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][where][' . $j . ']" title="where[' . $j . ']" >';
            $content .= '<option  value="" ></option>';
            for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['table']); $i++) {
                //se l'i-esima posizione dell'array e' vuota mi fermo altrimenti andrei a estrarre le colonne di una tabella inesistente
                if ($this->wfqbe[$numForm]['table'][$i] == "")
                    break;
                if ($this->wfqbe[$numForm]['renametable'][$i] != "")
                    $tableName = $this->wfqbe[$numForm]['renametable'][$i];
                else
                    $tableName = $this->wfqbe[$numForm]['table'][$i];
                $column = $h->MetaColumnNames($this->wfqbe[$numForm]['table'][$i]);
                foreach ($column AS $key => $value) {
                    if ($tableName . "." . $value == $this->wfqbe[$numForm]['where'][$j])
                        $content .= '<option  value="' . $tableName . "." . $value . '" selected="true">' . $tableName . "." . $value . '</option>';
                    else
                        $content .= '<option  value="' . $tableName . "." . $value . '">' . $tableName . "." . $value . '</option>';
                }
            }
            $content .= '</select>';

            $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

            //elemento select che contiene gli operatori contenuti nell'array operatoriWhere
            $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][op][' . $j . ']" title="where operator[' . $j . ']" >';
            $content .= '<option  value="" ></option>';
            for ($i = 0; $i < sizeof($this->operatoriWhere); $i++) {
                if ($this->operatoriWhere[$i] == $this->wfqbe[$numForm]['op'][$j])
                    $content .= '<option value="' . $this->operatoriWhere[$i] . '" selected="true">' . $this->operatoriWhere[$i] . '</option>';
                else
                    $content .= '<option value="' . $this->operatoriWhere[$i] . '">' . $this->operatoriWhere[$i] . '</option>';
            }
            $content .= '</select>';

            $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

            //se seleziono l'operatore between visualizzo due campi di inserimento testo
            if ($this->wfqbe[$numForm]['op'][$j] == "BETWEEN" || $this->wfqbe[$numForm]['op'][$j] == "NOT BETWEEN") {
                $content .= '<input type="text"  value="' . $this->wfqbe[$numForm]['insertbetween1'][$j] . '" name="wfqbe[' . $numForm . '][insertbetween1][' . $j . ']" title="insertbetween1[' . $j . ']"/>';
                $content .= "&nbsp;&nbsp;";
                $content .= " AND ";
                $content .= "&nbsp;&nbsp;";
                $content .= '<input type="text"  value="' . $this->wfqbe[$numForm]['insertbetween2'][$j] . '" name="wfqbe[' . $numForm . '][insertbetween2][' . $j . ']" title="insert between2[' . $j . ']"/>';
            } else {
                //se seleziono l'operatore IN visualizzo n campi input
                if ($this->wfqbe[$numForm]['op'][$j] == "IN" || $this->wfqbe[$numForm]['op'][$j] == "NOT IN") {
                    for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['insertin']) + 1; $i++) {
                        if (($i % 4 == 0) && ($i != 0)) {
                            $content .= '<br/><br/>';
                            $content .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                        }
                        $content .= "&nbsp;&nbsp;&nbsp;";
                        $content .= '<input type="text"  value="' . $this->wfqbe[$numForm]['insertin'][$i] . '" name="wfqbe[' . $numForm . '][insertin][' . $i . ']" title="insert in[' . $i . ']"/>';
                        if ($this->wfqbe[$numForm]['insertin'][$i] == "")
                            break;
                    }
                } else {
                    $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";
                    //elemento input nel quale si puo' inserire un valore
                    $content .= '<input type="text" id="ins' . $numForm . $j . '" value="' . $this->wfqbe[$numForm]['insert'][$j] . '" name="wfqbe[' . $numForm . '][insert][' . $j . ']" title="insert[' . $i . ']"/>';
                    $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";
                    //elemento select attraverso il quale posso selezionare un campo da inserire nel campo input e contiene tutti i campi
                    //delle tabelle selezionate
                    $content .= '<select onChange="insertwhere(\'insf' . $numForm . $j . '\',\'ins' . $numForm . $j . '\');updateForm()" id="insf' . $numForm . $j . '" name="wfqbe[' . $numForm . '][insertfield][' . $j . ']" title="insert field[' . $j . ']" >';
                    $content .= '<option  value="" ></option>';
                    for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['table']); $i++) {
                        //se l'i-esima posizione dell'array e' vuota mi fermo altrimenti andrei a estrarre le colonne di una tabella inesistente
                        if ($this->wfqbe[$numForm]['table'][$i] == "")
                            break;
                        if ($this->wfqbe[$numForm]['renametable'][$i] != "")
                            $tableName = $this->wfqbe[$numForm]['renametable'][$i];
                        else
                            $tableName = $this->wfqbe[$numForm]['table'][$i];
                        $column = $h->MetaColumnNames($this->wfqbe[$numForm]['table'][$i]);
                        foreach ($column AS $key => $value) {
                            if ($tableName . "." . $value == $this->wfqbe[$numForm]['insertfield'][$j])
                                $content .= '<option  value="' . $tableName . "." . $value . '" selected="true">' . $tableName . "." . $value . '</option>';
                            else
                                $content .= '<option  value="' . $tableName . "." . $value . '">' . $tableName . "." . $value . '</option>';
                        }
                    }
                    $content .= '</select>';
                }
            }

            $content .= '</div>';

            //elemento select che contiene le parentesi da chiudere
            //non lo visualizzo la prima volta
            $parOpen = "";
            //se la select per apertura parentesi non e' settata oppure non ci sono parentesi aperte non faccio vedere la select per
            // chiusura parentesi
            $numTabClose = ($parentesiAperte - strlen($this->wfqbe[$numForm]['parentesiclose'][$j])) * 3;
            if ($parentesiAperte || $this->wfqbe[$numForm]['parentesiopen'][$j] != "") {
                $content .= '<div style="margin-left:' . $numTabClose . 'em;">';
                $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][parentesiclose][' . $j . ']" title="parentesi close[' . $j . ']" >';
                $content .= '<option  value="" ></option>';
                for ($q = 0; $q < $parentesiAperte; $q++) {
                    $parOpen .= ")";
                    if ($this->wfqbe[$numForm]['parentesiclose'][$j] == $parOpen) {
                        $content .= '<option  value="' . $parOpen . '" selected="true">' . $parOpen . '</option>';
                        $numParChiuse = strlen($parOpen);
                        //decremento il contatore che conta le parentesi aperte tante volte quante parentesi sono state chiuse nella
                        //select che permette di chiudere le parentesi numero i
                        for ($w = 0; $w < $numParChiuse; $w++)
                            $parentesiAperte--;
                    } else
                        $content .= '<option  value="' . $parOpen . '">' . $parOpen . '</option>';
                }
                $content .= '</select>';
                $content .= '</div>';
            }

            $content .= '<br/>';
            $numTab = $parentesiAperte * 3;

            $content .= '<div style="margin-left:' . $numTab . 'em;">';
            //elemento select che contiene le congiunzioni logiche
            $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][ao][' . $j . ']" title="and or[' . $j . ']" >';
            $content .= '<option  value="" ></option>';
            if ($this->wfqbe[$numForm]['ao'][$j] == "") {
                $content .= '<option  value="AND" >AND</option>';
                $content .= '<option  value="OR" >OR</option>';
            } else {
                if ($this->wfqbe[$numForm]['ao'][$j] == "AND") {
                    $content .= '<option  value="AND" selected="true">AND</option>';
                    $content .= '<option  value="OR" >OR</option>';
                } else {
                    $content .= '<option  value="AND">AND</option>';
                    $content .= '<option  value="OR" selected="true">OR</option>';
                }
            }
            $content .= '</select>';
            $content .= '</div>';
            $content .= '<br/>';

            if ($this->wfqbe[$numForm]['ao'][$j] == "")
                break;


        }


        return $content;
    }


    /**
     * Crea l'elemento groupBy che contiene tutti i campi delle tabelle selezionate
     *
     * @param    [type]        $h: puntatore alla connessine
     * @param    [intero]    $num : indice identificativo dell'elemento corrente che viene costruito
     * @param    [type]        $nextEmpty:verifica se il successivo elemento dell'array wfqbe['groupby'] e' vuoto
     * @return    [string]    $content: stringa che contiene l'html dell'elemento select che rappresenta la clausola groupBy
     */

    function showGroupBy($h, $num, $nextEmpty, $numForm)
    {
        //vado a capo 2 volte prima di creare ogni elemento group by(ad eccezione del primo)
        if ($num != 0)
            $content .= '<br/><br/>';
        if ($num == 0)
            $content .= '<h4>GroupBy : </h4>';
        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][groupby][' . $num . ']" title="group by[' . $num . ']" >';
        if ($nextEmpty)
            $content .= '<option  value=""></option>';
        for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['table']); $i++) {
            if ($this->wfqbe[$numForm]['table'][$i] != "") {
                $column = $h->MetaColumnNames($this->wfqbe[$numForm]['table'][$i]);
                if ($this->wfqbe[$numForm]['renametable'][$i] != "")
                    $tableName = $this->wfqbe[$numForm]['renametable'][$i];
                else
                    $tableName = $this->wfqbe[$numForm]['table'][$i];
                foreach ($column AS $key => $value) {
                    if ($tableName . "." . $value == $this->wfqbe[$numForm]['groupby'][$num])
                        $content .= '<option  value="' . $tableName . "." . $value . '" selected="true">' . $tableName . "." . $value . '</option>';
                    else
                        $content .= '<option  value="' . $tableName . "." . $value . '">' . $tableName . "." . $value . '</option>';
                }
            }
        }
        $content .= '</select>';

        return $content;
    }

    /**
     * Crea l'elemento having
     *
     * @return    [string]    $content: contiente l'html dell'elemento having
     */

    function showHaving($h, $numForm)
    {
        $content .= '<h4>Having : </h4>';

        //elemento che contiene tutti le possibili funzioni aggregate che si possono utilizzare nella clausola having
        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][having]" title="having" >';
        $content .= '<option  value=""></option>';
        for ($i = 0; $i < sizeof($this->aggregationFunctions); $i++) {
            if ($this->aggregationFunctions[$i] == $this->wfqbe[$numForm]['having'])
                $content .= '<option value="' . $this->aggregationFunctions[$i] . '" selected="true">' . $this->aggregationFunctions[$i] . '</option>';
            else
                $content .= '<option value="' . $this->aggregationFunctions[$i] . '">' . $this->aggregationFunctions[$i] . '</option>';
        }
        $content .= '</select>';

        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

        //elemento che contiene tutti i campi(e l'operatore *) che si possono usare nell'elemento having
        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][havingfield]" title="having field" >';
        $content .= '<option  value=""></option>';
        if ($this->wfqbe[$numForm]['havingfield'] == "*")
            $content .= '<option value="*" selected="true">*</option>';
        else
            $content .= '<option value="*">*</option>';

        for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['table']); $i++) {
            //se l'i-esima posizione dell'array e' vuota mi fermo altrimenti andrei a estrarre le colonne di una tabella inesistente
            if ($this->wfqbe[$numForm]['table'][$i] == "")
                break;
            $column = $h->MetaColumnNames($this->wfqbe[$numForm]['table'][$i]);
            if ($this->wfqbe[$numForm]['renametable'][$i] != "")
                $tableName = $this->wfqbe[$numForm]['renametable'][$i];
            else
                $tableName = $this->wfqbe[$numForm]['table'][$i];
            foreach ($column AS $key => $value) {
                if ($tableName . "." . $value == $this->wfqbe[$numForm]['havingfield'])
                    $content .= '<option  value="' . $tableName . "." . $value . '" selected="true">' . $tableName . "." . $value . '</option>';
                else
                    $content .= '<option  value="' . $tableName . "." . $value . '">' . $tableName . "." . $value . '</option>';

            }
        }

        $content .= '</select>';

        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

        //elemento che contiene tutti i possibili operatori  che si possono utilizzare nella clausola having
        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][havingoperator]" title="having operator" >';
        $content .= '<option  value=""></option>';
        for ($i = 0; $i < sizeof($this->havingOperator); $i++) {
            if ($this->havingOperator[$i] == $this->wfqbe[$numForm]['havingoperator'])
                $content .= '<option value="' . $this->havingOperator[$i] . '" selected="true">' . $this->havingOperator[$i] . '</option>';
            else
                $content .= '<option value="' . $this->havingOperator[$i] . '">' . $this->havingOperator[$i] . '</option>';
        }
        $content .= '</select>';

        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

        //elemento
        $content .= '<input type="text"  value="' . $this->wfqbe[$numForm]['inserthaving'] . '" name="wfqbe[' . $numForm . '][inserthaving]" title="insert having"/>';

        return $content;

    }

    /**
     * Crea l'elemento orderBy che contiene tutti i campi delle tabelle selezionate
     *
     * @param    [type]        $h: puntatore a una nuova connessine
     * @param    [type]        $num : indice identificativo dell'elemento corrente che viene costruito
     * @param    [type]        $nextEmpty:verifica se il successivo elemento dell'array wfqbe['orderby'] e' vuoto
     * @return    [string]    $content: stringa che contiene l'html dell'elemento select che rappresenta la clausola groupBy
     */

    function showOrderBy($h, $num, $nextEmpty, $numForm)
    {
        //vado a capo 2 volte prima di creare ogni elemento order by(ad eccezione del primo)
        if ($num != 0)
            $content .= '<br/><br/>';
        if ($num == 0)
            $content .= '<h4>OrderBy : </h4>';
        $content .= '<select onChange="updateForm();"  name="wfqbe[' . $numForm . '][orderby][' . $num . ']" title="order by[' . $num . ']" >';
        if ($nextEmpty)
            $content .= '<option  value=""></option>';
        for ($i = 0; $i < sizeof($this->wfqbe[$numForm]['table']); $i++) {
            if ($this->wfqbe[$numForm]['table'][$i] != "") {
                $column = $h->MetaColumnNames($this->wfqbe[$numForm]['table'][$i]);
                if ($this->wfqbe[$numForm]['renametable'][$i] != "")
                    $tableName = $this->wfqbe[$numForm]['renametable'][$i];
                else
                    $tableName = $this->wfqbe[$numForm]['table'][$i];
                foreach ($column AS $key => $value) {
                    if ($tableName . "." . $value == $this->wfqbe[$numForm]['orderby'][$num])
                        $content .= '<option  value="' . $tableName . "." . $value . '" selected="true">' . $tableName . "." . $value . '</option>';
                    else
                        $content .= '<option  value="' . $tableName . "." . $value . '">' . $tableName . "." . $value . '</option>';
                }
            }
        }

        $content .= '</select>';
        //spazi inserito per distanziare le radiobutton
        $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";


        if ($this->wfqbe[$numForm]['orderby'][$num] != "") {
            $content .= 'Ascending ';
            if ($this->wfqbe[$numForm]['ad'][$num] == "")
                $this->wfqbe[$numForm]['ad'][$num] = "ASC";
            if ($this->wfqbe[$numForm]['ad'][$num] == "ASC")
                $content .= '<input onChange="updateForm();" type="radio" name="wfqbe[' . $numForm . '][ad][' . $num . ']" value="ASC" checked="checked"/>';
            else
                $content .= '<input onChange="updateForm();" type="radio" name="wfqbe[' . $numForm . '][ad][' . $num . ']" value="ASC" />';

            $content .= "&nbsp;&nbsp;&nbsp;&nbsp;";

            $content .= 'Descending ';
            if ($this->wfqbe[$numForm]['ad'][$num] == "DESC")
                $content .= '<input onChange="updateForm();" type="radio" name="wfqbe[' . $numForm . '][ad][' . $num . ']" value="DESC" checked="checked"/>';
            else
                $content .= '<input onChange="updateForm();" type="radio" name="wfqbe[' . $numForm . '][ad][' . $num . ']" value="DESC"/>';
        }

        return $content;
    }

    /**
     * Visualizza l'elemento select che permette di fare operazioni insiamistiche
     *
     * @param    [type]        $num : indice identificativo dell'elemento corrente che viene costruito
     * @return    [string]    $content: contiente l'html del'elemento select che permette di fare operazioni insiamistiche
     */

    function showSetOperations($numForm)
    {
        $content .= '<h4>Set operator :</h4>';
        $content .= '<select onChange="updateForm();"  name="wfqbe[setoperator][' . $numForm . ']" title="set operator" >';
        $content .= '<option  value=""></option>';
        for ($i = 0; $i < sizeof($this->setOperator); $i++) {
            if ($this->setOperator[$i] == $this->wfqbe['setoperator'][$numForm])
                $content .= '<option value="' . $this->setOperator[$i] . '" selected="true">' . $this->setOperator[$i] . '</option>';
            else
                $content .= '<option value="' . $this->setOperator[$i] . '">' . $this->setOperator[$i] . '</option>';
        }

        $content .= '</select>';


        return $content;

    }

    /**
     * Crea una textarea per permettere l'inserimento della query senza utilizzare il form
     *
     * @return    [string]    $content: contiente l'html della textarea che permette di visualizzare in tempo reale la query creata
     */

    function showInsertQuery()
    {
        $index = 1;

        $content .= '<h4>Insert query :</h4>';
        $content .= '<textarea onChange="invalidWfqbe(\'invalidwfqbe' . $index . '\');updateForm()" name="rawwfqbe[rawquery]" id="rawquery' . $index . '" rows="25" cols="160" title="insert query">';
        if ($this->rawwfqbe['rawquery'] != "")
            $content .= $this->rawwfqbe['rawquery'];
        $content .= '</textarea>';

        $content .= "<input type='hidden' value='" . $this->rawwfqbe['invalidwfqbe'] . "' id='invalidwfqbe" . $index . "' name='rawwfqbe[invalidwfqbe]'/>";
        //se provengo dal plu-in  hiddenqbe e vuoto e allora lo inizializzo a mano
        if ($this->hiddenqbe == "") {
            //$this->hiddenqbe="<table><numericValue_0></numericValue_0></table>";
            $this->hiddenqbe = serialize(array("table" => array(0 => "")));
        }
        $content .= "<input type='hidden' value='" . $this->hiddenqbe . "'  name='pass[hiddenqbe]'/>";

        $content .= '<br/><br/><br/>';
        $content .= '<input type="button" onClick="cancelContentTextArea(\'rawquery' . $index . '\',\'invalidwfqbe' . $index . '\');updateForm()" value="RESET" name="rawwfqbe[button]"/>';
        $content .= "<br/><br/><br/>";
        $content .= "<em>Before save click outside the text area</em>";
        return $content;

    }

    /**
     * Crea la query
     *
     * @param    [type]
     * @return    [string]    $query: stringa che contiene la query creata tramite il wizard
     */

    function createQuery($wfqbe = NULL, $rawwfqbe = NULL, $piVars = NULL, $query_uid = NULL, $pObj = NULL)
    {

        if ($wfqbe != NULL)
            $this->wfqbe = $wfqbe;

        //se la query e' stata inserita manualmente in $query metto il contenuto della text area e mi fermo
        if ($rawwfqbe != NULL) {
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['preProcessRawQuery'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['preProcessRawQuery'] as $_classRef) {
                    $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                    $rawwfqbe = $_procObj->process_raw_query($rawwfqbe, $piVars, $query_uid, $this);
                }
            }
            $query = $rawwfqbe['rawquery'];
            return $query;
        }
        $query = "";

        // Hook that can be used to pre-process a query structure before creating the SQL
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['preProcessQueryStructure'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['wfqbe']['preProcessQueryStructure'] as $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $remove = $_procObj->process_query_structure($this->wfqbe, $piVars, $query_uid, $this);
            }

            if (sizeof($remove) > 0) {
                foreach ($remove as $key) {
                    //unset($this->wfqbe[0]['parentesiopen'][$key]);
                    unset($this->wfqbe[0]['where'][$key]);
                    unset($this->wfqbe[0]['op'][$key]);
                    unset($this->wfqbe[0]['insertfield'][$key]);
                    unset($this->wfqbe[0]['ao'][$key - 1]);
                    //unset($this->wfqbe[0]['parentesiclose'][$key]);
                    if ($key == 0)
                        unset($this->wfqbe[0]['ao'][0]);
                }
            }
        }

        for ($numForm = 0; $numForm < sizeof($this->wfqbe['setoperator']) + 1; $numForm++) {

            if ($numForm != 0 && $this->wfqbe['setoperator'][$numForm - 1] == "")
                continue;

            //sezione select
            $query .= "SELECT";
            $query .= " ";
            //se e' stata selezionata la clausola distinct la aggiungo ma se e' stata selezionata la clausola all non la agguingo perche' per lo
            //standard sql select a = select all a
            if ($this->wfqbe[$numForm]['distinctall'] == 'distinct') {
                $query .= "DISTINCT";
                $query .= " ";
            }

            $query .= $this->wfqbe[$numForm]['selectedfields'];

            $query .= " ";

            //sezione from
            if ($this->wfqbe[$numForm]['table'] != "")
                $query .= "FROM";
            $query .= " ";

            //$from e' una variabile che utilizzo per contenere temporaneamente il codice SQL della sezione from
            $from = "";
            if ($this->wfqbe[$numForm]['join'] != "") {
                for ($j = 0; $j < sizeof($this->wfqbe[$numForm]['join']); $j++) {
                    if ($this->wfqbe[$numForm]['join'][$j] == "")
                        break;
                    //per ogni join creato devo inserire una parentesi aperta dopo la parola chiave from. La inserisco in questo punto perche'
                    //quando entro nel for esterno sono sicuro che ho creato un join e quindi inserisco una parentesi aperta
                    $from = "(" . $from;
                    if ($j == 0) {
                        $from .= " ";
                        $from .= $this->wfqbe[$numForm]['table'][$j];
                        $from .= " ";
                        if ($this->wfqbe[$numForm]['renametable'][$j] != "") {
                            $from .= "AS ";
                            $from .= $this->wfqbe[$numForm]['renametable'][$j];
                            $from .= " ";
                        }
                        $from .= $this->wfqbe[$numForm]['join'][$j];
                        $from .= " ";
                        $from .= $this->wfqbe[$numForm]['table'][$j + 1];
                        if ($this->wfqbe[$numForm]['renametable'][$j + 1] != "") {
                            $from .= " AS ";
                            $from .= $this->wfqbe[$numForm]['renametable'][$j + 1];
                            $from .= " ";
                        }
                        if ($this->wfqbe[$numForm]['join'][$j] != "NATURAL JOIN") {
                            $from .= " ON ";
                            $from .= $this->wfqbe[$numForm]['on1'][$j + 1];
                            $from .= " ";
                            $from .= $this->wfqbe[$numForm]['operatorion'][$j + 1];
                            $from .= " ";
                            $from .= $this->wfqbe[$numForm]['on2'][$j + 1];
                        }
                        $from .= " ) ";
                    } else {
                        $from .= " ";
                        $from .= $this->wfqbe[$numForm]['join'][$j];
                        $from .= " ";
                        $from .= $this->wfqbe[$numForm]['table'][$j + 1];
                        $from .= " ";
                        if ($this->wfqbe[$numForm]['renametable'][$j + 1] != "") {
                            $from .= "AS ";
                            $from .= $this->wfqbe[$numForm]['renametable'][$j + 1];
                        }
                        if ($this->wfqbe[$numForm]['join'][$j] != "NATURAL JOIN") {
                            $from .= " ON ";
                            $from .= $this->wfqbe[$numForm]['on1'][$j + 1];
                            $from .= " ";
                            $from .= $this->wfqbe[$numForm]['operatorion'][$j + 1];
                            $from .= " ";
                            $from .= $this->wfqbe[$numForm]['on2'][$j + 1];
                        }
                        $from .= " ) ";
                    }
                }
            } else {
                for ($j = 0; $j < sizeof($this->wfqbe[$numForm]['table']); $j++) {
                    if ($this->wfqbe[$numForm]['table'][$j] == "")
                        break;
                    $from .= $this->wfqbe[$numForm]['table'][$j];
                    if ($this->wfqbe[$numForm]['renametable'][$j] != "") {
                        $from .= " AS";
                        $from .= " ";
                        $from .= $this->wfqbe[$numForm]['renametable'][$j] . ",";
                    } else
                        $from .= ",";
                }
                //elimino dalla stringa selezionata l'ultima virgola
                $from = substr($from, 0, -1);
                $from .= " ";
            }
            //quando ho finito di creare la clausola FROM accodo il codice SQL della clausola alla variabile $query che in questo punto contiene
            //la clausola SELECT e poi conterra il codice SQL di tutte le clausole spacificate
            $query .= $from;


            //sezione where
            $where_setted = true;
            if (is_array($this->wfqbe[$numForm]['where'])) {
                foreach ($this->wfqbe[$numForm]['parentesiopen'] as $i => $value) {
                    // ##   modified for HOOK "preProcessQuery" to delete surrounding stuff
                    if (sizeof($remove) > 0) {
                        $value_i_marked_as_deleted = false;
                        foreach ($remove as $val) {
                            if ($val == $i) {
                                $value_i_marked_as_deleted = true;
                                break;
                            }
                        }
                        if ($value_i_marked_as_deleted) {
                            continue;
                        }
                    }
                    // ##    end of modification
                    if ($where_setted && $this->wfqbe[$numForm]['where'][$i] != "") {
                        $query .= "WHERE ";
                        $where_setted = false;
                    }

                    if ($this->wfqbe[$numForm]['ao'][$i - 1] != "") {
                        $query .= " ";
                        //inserimento operatore logico
                        $query .= $this->wfqbe[$numForm]['ao'][$i - 1];
                        $query .= " ";
                    }

                    //inserisco le parentesi aperte
                    if ($this->wfqbe[$numForm]['parentesiopen'][$i] != "" && $this->wfqbe[$numForm]['parentesiopen'][$i] == 'open')
                        $query .= "(";
                    $query .= " ";
                    $query .= $this->wfqbe[$numForm]['where'][$i];
                    $query .= " ";
                    $query .= $this->wfqbe[$numForm]['op'][$i];
                    $query .= " ";
                    //se l'operatore selezionato e' between allora inserisco i due valori e l'AND
                    if ($this->wfqbe[$numForm]['op'][$i] == "BETWEEN" || $this->wfqbe[$numForm]['op'][$i] == "NOT BETWEEN") {
                        if (is_numeric($this->wfqbe[$numForm]['insertbetween1'][$i]) && $this->wfqbe[$numForm]['insertbetween1'][$i] != "")
                            $query .= $this->wfqbe[$numForm]['insertbetween1'][$i];
                        else
                            $query .= "'" . $this->wfqbe[$numForm]['insertbetween1'][$i] . "'";
                        $query .= " ";
                        $query .= "AND";
                        $query .= " ";
                        if (is_numeric($this->wfqbe[$numForm]['insertbetween2'][$i]) && $this->wfqbe[$numForm]['insertbetween2'][$i] != "")
                            $query .= $this->wfqbe[$numForm]['insertbetween2'][$i];
                        else
                            $query .= "'" . $this->wfqbe[$numForm]['insertbetween2'][$i] . "'";
                        //altrimenti
                    } else {
                        //se l'operatore selezionato e' in inserisco i valori
                        if ($this->wfqbe[$numForm]['op'][$i] == "IN" || $this->wfqbe[$numForm]['op'][$i] == "NOT IN") {
                            $query .= "(";
                            $query .= " ";
                            for ($p = 0; $p < sizeof($this->wfqbe[$numForm]['insertin']); $p++) {
                                if ($this->wfqbe[$numForm]['insertin'][$p] == "")
                                    break;
                                if (is_numeric($this->wfqbe[$numForm]['insertin'][$p]))
                                    $query .= $this->wfqbe[$numForm]['insertin'][$p];
                                else
                                    $query .= "" . $this->wfqbe[$numForm]['insertin'][$p] . "";
                                $query .= ",";
                            }
                            $query = substr($query, 0, -1);
                            $query .= " ";
                            $query .= ")";
                        } //altrimenti procedo normalmente
                        else {
                            //se il valore inserito e' un numero , null oppure e' stato selezionato un campo di una tabella non lo racchiudo tra apici altrimenti si
                            if ((is_numeric($this->wfqbe[$numForm]['insert'][$i]) && $this->wfqbe[$numForm]['insert'][$i] != "") || $this->wfqbe[$numForm]['insert'][$i] == 'null' || $this->wfqbe[$numForm]['insert'][$i] == $this->wfqbe[$numForm]['insertfield'][$i])
                                $query .= $this->wfqbe[$numForm]['insert'][$i];
                            else
                                //if($this->wfqbe['insert'][$i]!="")
                                $query .= "" . $this->wfqbe[$numForm]['insert'][$i] . "";
                        }
                    }
                    $query .= " ";
                    //inserisco parentesi chiuse
                    if ($this->wfqbe[$numForm]['parentesiclose'][$i] != "") {
                        if (strpos($query, '(    ') !== false)
                            $query = str_replace('(    ', '', $query);
                        else
                            $query .= $this->wfqbe[$numForm]['parentesiclose'][$i];
                    }
                }
            }
            $query .= " ";

            //sezione group by
            if ($this->wfqbe[$numForm]['groupby'][0] != "")
                $query .= "GROUP BY ";
            for ($j = 0; $j < sizeof($this->wfqbe[$numForm]['groupby']); $j++) {
                if ($this->wfqbe[$numForm]['groupby'][$j] == "")
                    break;
                $query .= $this->wfqbe[$numForm]['groupby'][$j] . ",";
            }

            $query = substr($query, 0, -1);

            if ($this->wfqbe[$numForm]['groupby']['custom'] != '')
                $query .= $this->wfqbe[$numForm]['groupby']['custom'];

            $query .= " ";

            //sezione having
            if ($this->wfqbe[$numForm]['having'][0] != "") {
                $query .= "HAVING ";
                $query .= $this->wfqbe[$numForm]['having'];
                $query .= "(";
                $query .= $this->wfqbe[$numForm]['havingfield'];
                $query .= ")";
                $query .= " ";
                $query .= $this->wfqbe[$numForm]['havingoperator'];
                $query .= " ";
                $query .= $this->wfqbe[$numForm]['inserthaving'];
                $query .= " ";
            }

            $allowedOrderByFields = '';
            if ($pObj->conf['customQuery.'][$query_uid . '.']['allowedOrderByFields'] != '') {
                $allowedOrderByFields = $pObj->conf['customQuery.'][$query_uid . '.']['allowedOrderByFields'];
            }

            if ($query_uid == '' && empty($allowedOrderByFields)) {
                $BELIB = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wfqbe_belib');
                $config = $BELIB->retrievePageConfig($this->rawwfqbe['orgPid']);
                $allowedOrderByFields = $config['customQuery.'][$this->rawwfqbe['orgId'] . '.']['allowedOrderByFields'];
            }

            if ($this->wfqbe['setoperator'][$numForm] == '') {
                //sezione order by

                if (is_array($piVars['orderby']) && $piVars['orderby'][$query_uid]['field'] != '' && \TYPO3\CMS\Core\Utility\GeneralUtility::inList($allowedOrderByFields, $piVars['orderby'][$query_uid]['field']) && \TYPO3\CMS\Core\Utility\GeneralUtility::inList('ASC,DESC', $piVars['orderby'][$query_uid]['mode'])) {
                    $query .= "ORDER BY " . addslashes($piVars['orderby'][$query_uid]['field']) . " " . addslashes($piVars['orderby'][$query_uid]['mode']) . " ";
                } else {
                    if ($this->wfqbe[$numForm]['orderby'][0] != "") {
                        $query .= "ORDER BY ";
                        for ($j = 0; $j < sizeof($this->wfqbe[$numForm]['orderby']); $j++) {
                            if ($this->wfqbe[$numForm]['orderby'][$j] == "")
                                break;
                            if ($j != 0)
                                $query .= ",";
                            if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($allowedOrderByFields, $this->wfqbe[$numForm]['orderby'][$j])) {
                                $query .= addslashes($this->wfqbe[$numForm]['orderby'][$j]);
                                if ($this->wfqbe[$numForm]['ad'][$j] != "" && \TYPO3\CMS\Core\Utility\GeneralUtility::inList('ASC,DESC', $this->wfqbe[$numForm]['ad'][$j])) {
                                    $query .= " ";
                                    $query .= addslashes($this->wfqbe[$numForm]['ad'][$j]);
                                    $query .= " ";
                                }
                            }
                        }
                    }
                }
            }

            $query .= $this->wfqbe['setoperator'][$numForm];
            $query .= " ";
        }

        return $query;

    }

    /**
     * Visualizza la query creata in tempo reale
     *
     * @return    [string]    $content: contiente l'html della textarea che permette di visualizzare in tempo reale la query creata
     */

    function showQuery()
    {

        $content .= '<strong>Generated query:</strong><br/><br/>';
        $content .= $this->createQuery();

        return $content;

    }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/tx_wfqbe_query_query/class.tx_wfqbe_queryform_generator.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/tx_wfqbe_query_query/class.tx_wfqbe_queryform_generator.php']);
}
