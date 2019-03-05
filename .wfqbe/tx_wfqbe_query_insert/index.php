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

$MCONF['name'] = 'xMOD_tx_wfqbe_tx_wfqbe_query_insertwiz';
$GLOBALS['LANG']->includeLLFile('EXT:wfqbe/tx_wfqbe_query_insert/locallang.xml');

$GLOBALS['LANG']->includeLLFile('EXT:wfqbe/tx_wfqbe_query_insert/locallang.xml');
//includo il file form_generator.php. La prima parte dell'argomento mi da il path della estensione
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "tx_wfqbe_query_insert/class.tx_wfqbe_insertform_generator.php");
require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('wfqbe') . "lib/class.tx_wfqbe_connect.php");
// ....(But no access check here...)
// DEFAULT initialization of a module [END]

class tx_wfqbe_query_insertwiz extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{
    var $P;
    var $qbe;

    /**
     * Adds items to the ->MOD_MENU array. Used for the function menu selector.
     *
     * @return    [type]        ...
     */
    function menuConfig()
    {
        global $LANG;
        /* $this->MOD_MENU = Array (
            'function' => Array (
                '1' => $LANG->getLL('function1'),
                '2' => $LANG->getLL('function2'),
                '3' => $LANG->getLL('function3'),
            )
        ); */
        parent::menuConfig();
    }

    /**
     * Initializes the backend module by setting internal variables, initializing the menu.
     *
     * @return void
     * @see init()
     */
    public function initModule($moduleConfiguration)
    {
        $this->MCONF = $moduleConfiguration;
        parent::init();
    }


    /**
     * Main function of the module. Write the content to
     *
     * @return    [type]        ...
     */
    function main()
    {
        global $BE_USER, $LANG, $BACK_PATH, $TCA_DESCR, $TCA, $CLIENT, $TYPO3_CONF_VARS;
        $this->P = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');
        $this->id = $this->P['pid'];
        $this->qbe = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_insertform_generator");
        $this->qbe->init();

        // Draw the header.
        $this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
        $this->doc->backPath = $BACK_PATH;
        $this->doc->form = '<form action="" method="POST" style="width:113%;" id="insInsert">';
        //$this->doc->styleSheetFile =$BACK_PATH.'typo3/stylesheet.css';
        // JavaScript
//        $this->doc->loadJavascriptLib('contrib/prototype/prototype.js');
//        $this->doc->loadJavascriptLib('js/common.js');

        $this->doc->getPageRenderer()->loadExtJS();

        $this->doc->JScode = '
                        <script language="javascript" type="text/javascript">
                            script_ended = 0;
                            function jumpToUrl(URL)	{
                                document.location = URL;
                            }
                            
                            function insertwhere(i,j){
                                document.getElementById(j).value=document.getElementById(i).value;								
                            }
                            
                            function insertField(i,j){
                                str = document.getElementById(j).value;
                                if(str.length>0){
                                    if(str.substring(str.length-1)=="(" && document.getElementById(i).value=="*")
                                        document.getElementById(j).value+=document.getElementById(i).value+")";
                                    else if(str=="*" || document.getElementById(i).value=="*")
                                            document.getElementById(j).value=document.getElementById(i).value;
                                    else if(str.substring(str.length-1)=="(" )
                                            document.getElementById(j).value+=document.getElementById(i).value+")";
                                        else if(str.substring(str.length-1)=="*" || str.substring(str.length-1)=="+" || str.substring(str.length-1)=="-" || str.substring(str.length-1)=="/")
                                                document.getElementById(j).value+=document.getElementById(i).value;
                                            else
                                                document.getElementById(j).value+=", "+document.getElementById(i).value;
                                        
                                }else
                                    document.getElementById(j).value+=document.getElementById(i).value;
                            }
                            
                            function insertAggregationFunction(i,j){
                                str = document.getElementById(j).value;
                                if(str.length>0){
                                    if(str.substring(str.length-1)=="*" || str.substring(str.length-1)=="+" || str.substring(str.length-1)=="-" || str.substring(str.length-1)=="/")
                                            document.getElementById(j).value+=document.getElementById(i).value+"(";
                                    else if(str=="*" || document.getElementById(i).value=="*")
                                        
                                        document.getElementById(j).value=document.getElementById(i).value+"(";
                                    else
                                        document.getElementById(j).value+=", "+document.getElementById(i).value+"(";
                                }else
                                    document.getElementById(j).value+=document.getElementById(i).value+"(";
                            }						
                                                
                            function insertRenamedFields(i,j){								
                                    document.getElementById(j).value+=" AS "+document.getElementById(i).value;
                            }
                            
                            function insertChangeValueAndOperator(i,j){
                                    str = document.getElementById(j).value;
                                    if(str.length>0){
                                        
                                         if(str=="*" || document.getElementById(i).value=="*")
                                            document.getElementById(j).value=document.getElementById(i).value;
                                        else
                                            document.getElementById(j).value+=", "+document.getElementById(i).value;
                                    }else
                                        document.getElementById(j).value+=document.getElementById(i).value;
                            }
                            
                            function invalidWfqbe(i){
                                document.getElementById(i).value=1;
                            }
                            
                            function cancelContentTextArea(i,j){
                                document.getElementById(i).innerHTML="";
                                document.getElementById(j).value=0;
                            }
                            
                            function updateForm() {
                                TYPO3.jQuery("#insInsert").submit(function( event ) { 
                                    event.preventDefault();
                                    var actionurl = event.currentTarget.action;
                            
                                    TYPO3.jQuery.ajax({
                                            url: actionurl,
                                            type: \'post\',
                                            dataType: \'html\',
                                            data: TYPO3.jQuery("#insInsert").serialize()                                            
                                    }).done(function(data) {
                                                TYPO3.jQuery("#tx_wfqbe_insertform").html(""+data);
                                            });
                                            
                                });
                                TYPO3.jQuery("#insInsert").submit();
                            }
                        </script>
                    ';


        $this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $access = is_array($this->pageinfo) ? 1 : 0;
        if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id)) {
            if ($BE_USER->user['admin'] && !$this->id) {
                $this->pageinfo = array('title' => '[root-level]', 'uid' => 0, 'pid' => 0);
            }

            //$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br>'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.\TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_pre($this->pageinfo['_thePath'],50);
            $headerSection = "";
            $this->content .= "<div id=\"tx_wfqbe_insertform\">";
            $this->content .= $this->doc->startPage($LANG->getLL('title'));
            $this->content .= $this->doc->header($LANG->getLL('title'));
            $this->content .= $this->doc->spacer(5);
            $this->content .= $this->doc->section('', $this->doc->funcMenu($headerSection, \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])));
            $this->content .= $this->doc->divider(5);

            // Chiamata alla funzione che costruisce il contenuto del wizard
            $this->moduleContent();


            // ShortCut
            if ($BE_USER->mayMakeShortcut()) {
                $this->content .= $this->doc->spacer(20) . $this->doc->section('', $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
            }
        }
        $this->content .= $this->doc->spacer(10) . '</div>';
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    function printContent()
    {

        $this->content .= $this->doc->endPage();
        echo $this->content;
    }

    /**
     * [Describe function...]
     *
     * @return    [type]        ...
     */
    function moduleContent()
    {

        // Icons inside wizards
        $iconFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        $icon_saveandclosedok = $iconFactory->getIcon(
            'actions-document-save-close',
            \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);
        $icon_savedok = $iconFactory->getIcon(
            'actions-document-save',
            \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);
        $icon_closedok = $iconFactory->getIcon(
            'actions-document-close',
            \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);
        $icon_refresh_n = $iconFactory->getIcon(
            'actions-refresh',
            \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);

        $content = '';

        $var = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('P');//P � l'array che contiene tutte le info passate dal plugin al wizard
        //t3lib_utility_Debug::debug($this->P);
        $this->P = $var;
        //t3lib_utility_Debug::debug($var);
        $where = 'tx_wfqbe_query.uid=' . $var['uid'] . ' AND tx_wfqbe_query.deleted!=1 AND ';
        $CONN = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("tx_wfqbe_connect");
        $connection_obj = $CONN->connect($where);

        if (!$connection_obj) {
            $content .= '<div id="wfqbe_form">';
            $content .= "Connection failed. Please check your credentials and the dbname.";
            $content .= '</div>';
            $this->content .= $this->doc->section('', $content, 0, 1);
        } else {

            // Settaggio tag apertura form. Questo form � quello principale che contiene i vari tipi di campi per
            //creare la query. La variabile $rUri contiene il path della pagina del wizard e viene utilizzata cona valore
            // dell'attributo action del form
            list($rUri) = explode('#', \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI'));

            $content .= '<div id="c-saveButtonPanel">';
            $content .= '<button type="submit" class="c-inputButton" name="savedok" value="1" title="Save document">' . $icon_savedok->render() . '</button>';
            $content .= '<button type="submit" class="c-inputButton" name="saveandclosedok" value="1" title="Save and close document">' . $icon_saveandclosedok->render() . '</button>';
            $content .= '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(unescape(\'' . rawurlencode($this->P['returnUrl']) . '\')); return false;') . '">' . $icon_closedok->render() . '</a>';
            $content .= '<button type="submit" class="c-inputButton" name="_refresh" value="1" title="Refresh document">' . $icon_refresh_n->render() . '</button>';
            $content .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_table_wiz_buttons', $GLOBALS['BACK_PATH'], '');
            $content .= '</div>';

            //$content.='<form action="'.htmlspecialchars($rUri).'" method="post" name="insInsert">';
            //inserisco tutto il form all'interno di un elemento div
            $content .= '<div id="wfqbe_form">';
            $content .= $this->qbe->showForm($connection_obj['conn'], htmlspecialchars($rUri));
            $content .= '</div>';
            //costruzione del form, posizionato in fondo alla pagina, per il salvataggio, chiusure e salvataggio-chiusura del documento.
            $content .= '<div id="c-saveButtonPanel">';
            $content .= '<button type="submit" class="c-inputButton" name="savedok" value="1" title="Save document">' . $icon_savedok->render() . '</button>';
            $content .= '<button type="submit" class="c-inputButton" name="saveandclosedok" value="1" title="Save and close document">' . $icon_saveandclosedok->render() . '</button>';
            $content .= '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(unescape(\'' . rawurlencode($this->P['returnUrl']) . '\')); return false;') . '">' . $icon_closedok->render() . '</a>';
            $content .= '<button type="submit" class="c-inputButton" name="_refresh" value="1" title="Refresh document">' . $icon_refresh_n->render() . '</button>';
            $content .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_table_wiz_buttons', $GLOBALS['BACK_PATH'], '');
            $content .= '</div>';
            $content .= '</form>';
            $content .= '<hr>';

            // Se i pulsanti di salvataggio e salvataggio/uscita son premuti allora
            if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('savedok') || \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('saveandclosedok')) {

                // creo una istanza della classe importata(viene salvata nella variabile tca)
                $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
                $tce->stripslashes_values = 0;

                // Creo un array di nome data e inserisco la query che verr� salvata nella tabella table(query),nella
                //tupla con valore uid uguale a uid e nel campo query.
                //table,uid e field sono parametri passati dal plugin al wizard tramite la variabile P
                $data = array();
                //$data[$this->P['table']][$this->P['uid']][$this->P['field']]=$this->qbe->createQuery();
                $data[$this->P['table']][$this->P['uid']][$this->P['field']] = $this->qbe->saveModule();

                // Richiamo due funzioni che permettono di fare l'aggiornamenti del campo della tupla nella tabella
                //definita nelle due righe sopra.
                //Queste due funzioni sono definite nel file tcemain.php
                $tce->start($data, array());
                $tce->process_datamap();

                // Se � stato premuto il tasto salvataggio/uscita ,oltre a fare tutto il lavoro che si f� per il pulsante
                //salvataggio, bisogna tornare nella pagina di inserimento nuovo record
                if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('saveandclosedok')) {
                    header('Location: ' . \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->P['returnUrl']));
                    exit;
                }
            }

            //$content.=$this->qbe->showQuery();

            $this->content .= $this->doc->section('', $content, 0, 1);
        }
    }
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/tx_wfqbe_query_insert/index.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wfqbe/tx_wfqbe_query_insert/index.php']);
}


// Make instance:
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_wfqbe_query_insertwiz');
$SOBE->initModule($MCONF);


$SOBE->main();
$SOBE->printContent();
