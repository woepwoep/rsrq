function WhenFieldChanges(fieldname, val) {
  // alert("The input value has changed. De nieuwe waarde van " + fieldname + " is: " + val*0.19);
  document.getElementById(fieldname).value = (val*0.19).toFixed(2);
  if (val>0) {
  	document.getElementById(fieldname).setAttribute("readonly","1");
	} else {
		document.getElementById(fieldname).removeAttribute("readonly");
	}
}