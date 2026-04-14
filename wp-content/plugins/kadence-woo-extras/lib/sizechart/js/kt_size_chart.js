jQuery(document).ready(function ($) {
var bootstrap_enabled = (typeof $().modal == 'function');
if ( bootstrap_enabled == false ) {
	$(document).on('click','.kt-size-btn[data-toggle="modal"]', function (e) {
	e.preventDefault();
	var $this   = $(this);
	var $target = $($this.attr('data-target'));
	if($target.hasClass('kt-modal-open')) {
		$target.removeClass('kt-modal-open');
	} else {
		$target.addClass('kt-modal-open');
	}
		
	});
	$(document).on('click','.kt-modal-open .close', function (e) {
	e.preventDefault();
	$(this).parents('.kt-modal-open').removeClass('kt-modal-open');			     
	});
	$(document).on('click','.kt-modal-open .modal-footer button', function (e) {
	e.preventDefault();
	$(this).parents('.kt-modal-open').removeClass('kt-modal-open');			     
	});
	}
});


