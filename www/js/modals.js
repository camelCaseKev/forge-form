$(document).ready(function() {

	$('.input').blur(function() {
		var $this = $(this);
		if ($this.val())
			$this.addClass('used');
		else
			$this.removeClass('used');
	});
	
});


$('#theModals').on('click','#tab1' , function(){
	$('#tab1').addClass('login-shadow');
	$('#tab2').removeClass('signup-shadow');
	$('#myModal div.modal-dialog').removeClass('cool-spacing', {duration: 500});
});

$('#theModals').on('click','#tab2' , function(){
	$('#tab2').addClass('signup-shadow');
	$('#tab1').removeClass('login-shadow');
	$('#myModal div.modal-dialog').addClass('cool-spacing',{duration:500});
});
