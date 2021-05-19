jQuery(document).ready(function() {
   var currentValue = $('.datum').val();
   var currentDate = new Date();
  
   jQuery('.datum').datepicker({
	  'language': 'nl',
      'format': 'd-m-yyyy',
      'autoclose': true,  
      'changeMonth': true,
      'changeYear': true,
      'minDate': '01-01-2014',
	  'todayBtn': 'linked',
	  'todayHighlight': true,
      'showAnim': 'slideDown' });
	  
	jQuery('.sqldate').datepicker({
	  'language': 'nl',
      'format': 'yyyy-m-d',
      'autoclose': true,  
      'changeMonth': true,
      'changeYear': true,
      'minDate': '01-01-2014',
	  'todayBtn': 'linked',
	  'todayHighlight': true,
      'showAnim': 'slideDown' });
			
	
  //console.log('Huidige datum is: ' + currentDate);
  //console.log('Datum in veld is: ' + currentValue);
  if (!currentValue) {
     //Set date to current date  
    jQuery('.datum, .sqldate').datepicker();
  }
      
   jQuery('.tijd').timepicker({
      'timeFormat': 'H:i',
      'step': 15,
      'scrollDefault': 'now',
      'show2400': true });
	  
	$("#searchInput").on("keyup", function() {
		var value = $(this).val().toLowerCase();
		$("#theTable tr").filter(function() {
		  $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
		});
	});
	
	$("p").on("click", function(){
    $(this).hide();
	});
	
	var total = 0;
    $( ".Bedrag" ).each( function(){
	total += parseFloat($(this).val()) || 0;
	});
    //alert(total);

	console.log(total);
   
});
