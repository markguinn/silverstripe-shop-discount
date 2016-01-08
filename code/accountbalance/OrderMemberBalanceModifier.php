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
		//		$order = $this->Order();
		$subtotal = $incoming; //$order->SubTotal();

		$discount = $balance;

		//ensure discount never goes above Amount
		if($discount > $subtotal){
			$discount = $subtotal;
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
		if ($m && $balance = $m->getAccountBalanceAmount()) {
			return $balance;
		} else {
			return 0;
		}
	}

}
