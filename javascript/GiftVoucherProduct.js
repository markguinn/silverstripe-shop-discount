(function($) {
	$(function () {
		
		//Amount Dropdown
		$(document).on('change', '#GiftCardAmountDropdown', function() {
			//per default make sure that unit price is hidden
			$('#UnitPrice').hide();

			var $this = $(this);
			var val = $this.find('select').val();
			
			if (val.length) {
				
				//Adding the $ sign
				val = '$' + val;

				//Setting the unit price from the dropdown

				var input = $this.parent().find('input[name=UnitPrice]');
				//console.log(input);
				input.val(val);
				
			} else {
				//Enter amount has been chosen
				$('#UnitPrice').show();
			}
		});
		
		//Delivery method
		$(document).on('change', 'div#Delivery input', function() {
			var val = $(this).val();
			if (val == 'PrintAtHome') {
				$('div#RecipientEmail').hide();
			} else {
				$('div#RecipientEmail').show();
			}
		});
		
	});
})(jQuery);