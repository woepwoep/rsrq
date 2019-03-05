
/*
 * JS library used in the WFQBE Extension
 */

/**
 * Function used to show/hide the help text in the insert form
 * @param {Object} id
 */
function wfqbe_manage_help(id){
	helpTAG = document.getElementById(id);
	if (helpTAG.style.display=='block')
		helpTAG.style.display = 'none';
	else
		helpTAG.style.display = 'block';
	return false;
}




/*
 * This two lines are executed onLoad to hide all the help texts.
 */
if(document.getElementById && document.createElement){
	document.write('<style type="text/css">*.wfqbe_help{display:none}</style>');
}