$(document).ready(function() {
	
	$('#footer a').on('click',function(e) {
		
		var gd = $(this).data('id');
		
		$('.app-page').hide();
		
		$(gd).show();
	});
	
});