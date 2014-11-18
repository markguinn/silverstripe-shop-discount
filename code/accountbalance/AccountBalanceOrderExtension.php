<?php

/**
 * Account Balance Order Extension
 * 
 * Extends the order so account balance can be updated once the order has been placed
 * 
 */
class AccountBalanceOrderExtension extends DataExtension {


	function onPlaceOrder() {

		$modifiers = $this->owner->Modifiers();
		if($modifiers->exists()){
			foreach($modifiers as $modifier){
				$className = $modifier->ClassName;
				
				//Subtracting balance if a balance modifier is present
				if ($className == 'OrderMemberBalanceModifier') {
					$amount = $modifier->Amount;
					
					$m = $this->owner->Member();
					$m->subtractBalance($amount, $this->owner);
				}
			}
		}
		
	}
}