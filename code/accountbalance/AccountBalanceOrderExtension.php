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

				//Adding coupon remains to balance if a coupon modifier is present
				if ($className == 'OrderCouponModifier') {
					$coupon = $modifier->Coupon();
					
					//This should only happen if this is a gift voucher
					if ($coupon && $coupon->GiftVoucher()) {
						$orderAmount = $modifier->Amount;
						$couponAmount = $coupon->Amount;
						
						if ($couponAmount > $orderAmount) {
							$m = $this->owner->Member();
							$amount = $couponAmount - $orderAmount;
							$m->addCouponRemainderToBalance($amount, $coupon, $this->owner);
						}
					}
				}
				
				
			}
		}
		
	}
}