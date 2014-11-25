<?php
/**
 * Order Member Balance Modifier
 *
 */
class OrderMemberBalanceModifier extends OrderModifier {
	
	public static $defaults = array(
		"Type" => "Deductable"
	);

	public static $singular_name = "Balance";

	public static $plural_name = "Balances";
	
	/**
	 * @see OrderModifier::required()
	 */
	function required(){
		return $this->valid();
	}
	
	/**
	 * Should this modifier be in the cart ?
	 * Only if the member does have a balance
	 */
	function valid(){
		
		$m = Member::currentUser();
		if ($m && $amount = $m->getAccountBalanceAmount()) {
			return true;
		}
		return false;
	}
	
	public function canRemove() {
		return false;
	}
	
	/**
	 * @see OrderModifier::value()
	 */
	function value($incoming){

		$balance = $this->accountBalanceAmount();
		$order = $this->Order();
		$subtotal = $order->SubTotal();


		//Taking account for other modifiers
		//We don't want to subtract anything that has
		//already been subtracted
		//
		//For now we only check for OrderCouponModifier, but this could
		//be made more generic
		
		$alreadyDiscounted = 0;
		$modifiers = $order->Modifiers();
		foreach($modifiers as $modifier) {
			$className = $modifier->ClassName;
			if ($className == 'OrderCouponModifier') {
				$modifierAmount = $modifier->Amount;
				$alreadyDiscounted = $alreadyDiscounted + $modifierAmount;
			}
		}

		$availableDiscount = $balance + $alreadyDiscounted;
		
		//ensure discount never goes above Amount
		if($availableDiscount > $subtotal){
			$discount = $subtotal - $alreadyDiscounted;
		}
		
		$this->Amount = $discount;

		return $this->Amount;

	}
	
	/**
	 * @see OrderModifier::TableTitle()
	 */
	public function TableTitle(){
		$balance = "$" . $this->accountBalanceAmount();
		return "From account balance (total {$balance})";
	}
	
	
	/**
	* Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)
	*/
	public function IsDeductable() {
		return true;
	}
	
	
	private function accountBalanceAmount() {
		$m = Member::currentUser();
		$balance = $m->getAccountBalanceAmount();
	
		return $balance;
	}

}