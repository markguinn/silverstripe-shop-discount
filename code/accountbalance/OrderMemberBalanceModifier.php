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
		return true;
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
	}
	
	public function canRemove() {
		return false;
	}
	
	/**
	 * @see OrderModifier::value()
	 */
	function value($incoming){

		$m = Member::currentUser();
		$balance = $m->getAccountBalanceAmount();
		
		//$order = $this->Order();

		$this->Amount = $balance;
		return $this->Amount;

	}
	
	/**
	 * @see OrderModifier::TableTitle()
	 */
	public function TableTitle(){
		return 'Account Balance';
	}
	
	
	/**
	* Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)
	*/
	public function IsDeductable() {
		return true;
	}
	
	///**
	// * Gets the order subtotal
	//* @return float
	//*/
	//protected function LiveSubTotalAmount() {
	//	if($this->OrderCoupon()) {
	//		$order = $this->Order();
	//		return $order->SubTotal();
	//	}
	//	return 0;
	//}
}