<?php
/**
 * Order Coupon Modifier
 * @package shop-discount
 */
class OrderCouponModifier extends OrderModifier {

	public static $has_one = array(
		"Coupon" => "OrderCoupon"
	);

	public static $defaults = array(
		"Type" => "Deductable"
	);

	public static $singular_name = "Coupon";
	function i18n_singular_name() { return _t("OrderCouponModifier.ORDERCOUPONREDUCTION", self::$singular_name);}

	public static $plural_name = "Coupons";
	function i18n_plural_name() { return _t("OrderCouponModifier.ORDERCOUPONREDUCTIONS", self::$plural_name);}
	
	/**
	 * @see OrderModifier::required()
	 */
	function required(){
		return false;
	}
	
	/**
	 * Validate cart against coupon
	 */
	function valid(){
		$order = $this->Order();
		if(!$order){
			return false;
		}
		$coupon = $this->Coupon();
		if(!$coupon){
			return false;
		}
		if(!$coupon->valid($order)){
			return false;
		}		
		return true;
	}
	
	public function canRemove() {
		return true;
	}
	
	/**
	 * @see OrderModifier::value()
	 */
	function value($incoming){
		
		if($coupon = $this->Coupon()){
			$this->Amount = $coupon->orderDiscount($this->Order(), $incoming);
		}
		return $this->Amount;
	}
	
	/**
	 * @see OrderModifier::TableTitle()
	 */
	public function TableTitle(){
		if($coupon = $this->Coupon()) {
			$title = sprintf(_t("OrderCouponModifier.COUPON", "Coupon: %s"),$coupon->Title);

			//Additional notification on that remainder will be added to account on checkout
			if ($coupon->GiftVoucher() && ($this->Order()->Status == 'Cart')) {
				$title .= " <br /><em>Total: {$coupon->Amount}</em> <br/> <em>Remainder will be added to your balance on checkout.</em>";
			}
			
			return $title;
		}
		return _t("OrderCouponModifier.NOCOUPONENTERED", "No Coupon Entered");
	}
	
	/**
	 * Helper function for setting the coupon for this modifier.
	 * @param OrderCoupon $discountCoupon
	 */
	public function setCoupon(OrderCoupon $discountCoupon) {
		$this->CouponID = $discountCoupon->ID;
		$this->write();
	}
	
	//TODO: remove functions below
	
	/**
	* form functions (e. g. showform and getform)
	*/
	static function show_form() {
		return true;
	}
	
	function getModifierForm($controller) {
		return self::get_form($controller);
	}
	static function get_form($controller) {
		return new CouponForm($controller,"CouponForm");
	}
	
	/**
	* Type Functions (IsChargeable, IsDeductable, IsNoChange, IsRemoved)
	*/
	public function IsDeductable() {
		return true;
	}
	
	/**
	 * Gets the order subtotal
	* @return float
	*/
	protected function LiveSubTotalAmount() {
		if($this->OrderCoupon()) {
			$order = $this->Order();
			return $order->SubTotal();
		}
		return 0;
	}
}
