<?php
/**
 * Applies a discount to current order, if applicable, when entered at checkout.
 * @package shop-discounts
 */
class OrderCoupon extends DataObject {

	static $db = array(
		"Title" => "Varchar(255)", //store the promotion name, or whatever you like
		"Code" => "Varchar(25)",
		"Type" => "Enum('Percent,Amount','Percent')",
		"Amount" => "Currency",
		"Percent" => "Percentage",
		"Active" => "Boolean",

		"ForItems" => "Boolean",
		"ForShipping" => "Boolean",
		
		//Item / order validity criteria
		//"Cumulative" => "Boolean",
		"MinOrderValue" => "Currency",
		"UseLimit" => "Int",
		"MemberUseLimit" => "Int",
		"StartDate" => "Datetime",
		"EndDate" => "Datetime"
	);
	
	static $has_one = array(
		"GiftVoucher" => "GiftVoucher_OrderItem", //used to link to gift voucher purchase
		"Group" => "Group"
	);
	
	static $many_many = array(
		"Products" => "Product", //for restricting to product(s)
		"Categories" => "ProductCategory",
		"Zones" => "Zone"
	);

	static $searchable_fields = array(
		"Code"
	);

	static $defaults = array(
		"Type" => "Percent",
		"Active" => true,
		"UseLimit" => 0,
		"MemberUseLimit" => 0,
		//"Cumulative" => 1,
		"ForItems" => 1
	);
	
	static $field_labels = array(
		"DiscountNice" => "Discount",
		"UseLimit" => "Maximum number of uses",
		"MinOrderValue" => "Minimum subtotal of order"
	);

	static $summary_fields = array(
		"Code",
		"Title",
		"DiscountNice",
		"StartDate",
		"EndDate",
		"UseCount",
		"OrderReference"
		
	);

	static $singular_name = "Discount";
	function i18n_singular_name() { return _t("OrderCoupon.COUPON", "Coupon");}
	static $plural_name = "Discounts";
	function i18n_plural_name() { return _t("OrderCoupon.COUPONS", "Coupons");}

	static $default_sort = "EndDate DESC, StartDate DESC";
	static $generated_code_length = 10;

	public static function get_by_code($code) {
		return self::get()
			->filter('Code:nocase', $code)
			->first();
	}

	/**
	 * Getter for related order
	 * This only applies to Gift cards
	 */
	public function getOrder() {
		if ($v = $this->GiftVoucher()) {
			return $v->Order();
		}
	}
	/**
	 * Getter for related order reference
	 * This only applies to Gift cards
	 */
	public function getOrderReference() {
		if ($o = $this->getOrder()) {
			return $o->Reference;
		}
	}
	

	/**
	 * Generates a unique code.
	 * @todo depending on the length, it may be possible that all the possible
	 *       codes have been generated.
	 * @return string the new code
	 */
	public static function generate_code($length = null, $prefix = "") {
		$length = ($length) ? $length : self::config()->generated_code_length;
		$code = null;
		$generator = Injector::inst()->create('RandomGenerator');
		do {
			$code = $prefix.strtoupper(substr($generator->randomToken(), 0, $length));
		} while (
			self::get()->filter("Code:nocase", $code)->exists()
		);
		return $code;
	}
	
	protected $message = null, $messagetype = null;
	
	function getCMSFields($params = null){
		$fields = new FieldList(array(
			$tabset = new TabSet("Root",
				$maintab = new Tab("Main",
					new TextField("Title"),
					new TextField("Code"),
					new CheckboxField("Active","Active (allow this coupon to be used)"),
//					new FieldGroup("This discount applies to:",
//						new CheckboxField("ForItems","Item values"),
//						new CheckboxField("ForShipping","Shipping cost")
//					),
					new HeaderField("Criteria","Order and Item Criteria",4),
					new LabelField("CriteriaDescription", "Configure the requirements an order must meet for this coupon to be used with it:"),
					new FieldGroup("Valid date range:",
						new CouponDatetimeField("StartDate","Start Date / Time"),
						new CouponDatetimeField("EndDate","End Date / Time (you should set the end time to 23:59:59, if you want to include the entire end day)")
					),
					new CurrencyField("MinOrderValue","Minimum order subtotal"),
					new NumericField("UseLimit","Limit number of uses (0 = unlimited)"),
					new CheckboxField("MemberUseLimit","Allow each account to use only once")
				)
			)
		));
		if($this->isInDB()){
			if($this->ForItems){
				$tabset->push(new Tab("Products",
					new LabelField("ProductsDescription", "Select specific products that this coupon can be used with"),
					$products = new GridField("Products", "Products", $this->Products(), new GridFieldConfig_RelationEditor())
				));
				$tabset->push(new Tab("Categories",
					new LabelField("CategoriesDescription", "Select specific product categories that this coupon can be used with"),
					$categories = new GridField("Categories", "Categories", $this->Categories(), new GridFieldConfig_RelationEditor())
				));
//				$products->setPermissions(array('show'));
//				$categories->setPermissions(array('show'));
			}
			
			$tabset->push(new Tab("Zones",
				$zones = new GridField("Zones", "Zones", $this->Zones(), new GridFieldConfig_RelationEditor())
			));
			
			$maintab->Fields()->push($grps = new DropdownField("GroupID", "Member Belongs to Group", DataObject::get('Group')->map('ID','Title')));
			$grps->setHasEmptyDefault(true);
			$grps->setEmptyString('-- Any Group --');
			
			if($this->Type == "Percent"){
				$fields->insertBefore($percent = new NumericField("Percent","Percentage discount"), "Active");
				$percent->setTitle("Percent discount (eg 0.05 = 5%, 0.5 = 50%, and 5 = 500%)");
			}elseif($this->Type == "Amount"){
				$fields->insertBefore($amount = new NumericField("Amount","Discount value"), "Active");
			}
		}else{
			$fields->insertBefore(  
				new OptionsetField("Type","Type of discount", 
					array(
						"Percent" => "Percentage of subtotal (eg 25%)",
						"Amount" => "Fixed amount (eg $25.00)"
					)
				),
				"Active"
			);
			$fields->insertAfter(
				new LiteralField("warning","<p class=\"message good\">More criteria options can be set after an intial save</p>"),
				"Criteria"
			);
		}
		$this->extend("updateCMSFields",$fields);
		return $fields;
	}

	// This was causing crashes on /dev/build. Moving to onBeforeWrite. MG 8.15.13
//	function populateDefaults() {
//		parent::populateDefaults();
//		$this->Code = self::generateCode();
//	}

	/**
	 * Autogenerate the code if needed
	 */
	protected function onBeforeWrite() {
		if (empty($this->Code)){
			$this->Code = self::generate_code();
		}
		parent::onBeforeWrite();
	}

	/**
	 * We have to tap in here to correct "50" to "0.5" for the percent
	 * field. This is a common user error and it's nice to just fix it
	 * for them.
	 *
	 * @param string $fieldName Name of the field
	 * @param mixed $value New field value
	 * @return DataObject $this
	 */
	public function setCastedField($fieldName, $value) {
		if ($fieldName == 'Percent' && $value > 1) $value /= 100.0;
		return parent::setCastedField($fieldName, $value);
	}

	/*
	 * Assign this coupon to a OrderCouponModifier on the given order
	 */
	function applyToOrder(Order $order){
		$modifier = $order->getModifier('OrderCouponModifier',true);
		if($modifier){
			$modifier->setCoupon($this);
			$modifier->write();
			$order->calculate(); //makes sure prices are up-to-date
			$order->write();
			$this->message(_t("OrderCoupon.APPLIED","Coupon applied."),"good");
			return true;
		}
		$this->error(_t("OrderCoupon.CANTAPPLY","Could not apply"));
		return false;
	}

	/**
	 * Check if this coupon can be used with a given order
	 * @param Order $order
	 * @return boolean
	 */
	function valid($order){
		if(empty($order)){
			$this->error(_t("OrderCoupon.NOORDER","Order has not been started."));
			return false;
		}
		if(!$this->Active){
			$this->error(_t("OrderCoupon.INACTIVE","This coupon is not active."));
			return false;
		}
		if($this->UseLimit > 0 && $this->getUseCount($order) >= $this->UseLimit) {
			$this->error(_t("OrderCoupon.LIMITREACHED","Limit of $this->UseLimit uses for this code has been reached."));
			return false;
		}
		if($this->MemberUseLimit > 0 && Member::currentUserID() && $this->getUseCount($order, Member::currentUserID()) >= $this->MemberUseLimit) {
			$this->error("You have already used this coupon code.");
			return false;
		}
		if($this->MinOrderValue > 0 && $order->SubTotal() < $this->MinOrderValue){
			$this->error(sprintf(_t("OrderCouponModifier.MINORDERVALUE","Your cart subtotal must be at least %s to use this coupon"),$this->dbObject("MinOrderValue")->Nice()));
			return false;
		}
		$startDate = strtotime($this->StartDate);
		$endDate = strtotime($this->EndDate);
		$now = time();
		if($endDate && $endDate < $now){
			$this->error(_t("OrderCoupon.EXPIRED","This coupon has already expired."));
			return false;
		}
		if($startDate && $startDate > $now){
			$this->error(_t("OrderCoupon.TOOEARLY","It is too early to use this coupon."));
			return false;
		}
		$group = $this->Group();
		$member = (Member::currentUser()) ? Member::currentUser() : $order->Member(); //get member
		if($group->exists() && (!$member || !$member->inGroup($group))){
			$this->error(_t("OrderCoupon.GROUPED","Only specific members can use this coupon."));
			return false;
		}
		$zones = $this->Zones();
		if($zones->exists()){
			$address = $order->getShippingAddress();
			if(!$address){
				$this->error(_t("OrderCouponModifier.NOTINZONE","This coupon can only be used for a specific shipping location."));
				return false;
			}
			$currentzones = Zone::get_zones_for_address($address);
			if(!$currentzones || !$currentzones->exists()){
				$this->error(_t("OrderCouponModifier.NOTINZONE","This coupon can only be used for a specific shipping location."));
				return false;
			}
			//check if any of currentzones is in zones
			$inzone = false;
			foreach($currentzones as $zone){
				if($zones->find('ID',$zone->ID)){
					$inzone = true;
					break;
				}
			}
			if(!$inzone){
				$this->error(_t("OrderCouponModifier.NOTINZONE","This coupon can only be used for a specific shipping location."));
				return false;
			}
		}
		$items = $order->Items();
		$incart = false; //note that this means an order without items will always be invalid
		foreach($items as $item){
			if($this->itemMatchesCriteria($item)){ //check at least one item in the cart meets the coupon's criteria
				$incart = true;
				break;
			}
		}
		if(!$incart){
			$this->error(_t("OrderCouponModifier.PRODUCTNOTINORDER","No items in the cart match the coupon criteria"));
			return false;
		}
		$valid = true;
		$this->extend("updateValidation",$order, $valid, $error);
		if(!$valid){
			$this->error($error);
		}
		return $valid;
	}
	
	/**
	 * Work out the discount for a given order.
	 * @param Order $order
	 * @param float $runningTotal [optional] (if called from OrderCouponModifier this allows them to pay shipping and tax as well)
	 * @return double - discount amount
	 */
	function orderDiscount(Order $order, $runningTotal = 0){
		$discount = 0;
		if ($this->GiftVoucherID && $runningTotal) {
			$discountvalue = $this->getDiscountValue($runningTotal);
			$discount += ($discountvalue > $runningTotal) ? $runningTotal : $discountvalue; //prevent discount being greater than what is possible
			return $discount;
		}

		if($this->ForItems){
			$items = $order->Items();
			$discountable = 0;
			$discountvalue = 0;
			$itemLevelDiscount = ($this->Percent > 0);
			foreach($items as $item){
				if($this->itemMatchesCriteria($item)){
					$discountable += ($itemTotal = $item->Total());
					if ($itemLevelDiscount) {
						$buyable = $item instanceof ProductVariation_OrderItem ? $item->ProductVariation(true) : $item->Product(true);
						// If we can find the wholesale price, use that as a minimum, otherwise assume it's 40%
						$minPrice = ($buyable && $buyable->exists() && $buyable->WholesalePrice > 0)
								? $buyable->WholesalePrice * $item->Quantity
								: $itemTotal * 0.60;
						$discountvalue += min($this->getDiscountValue($itemTotal), $itemTotal - $minPrice);
					}
				}
			}
			if($discountable){
				// if we're not using percentages, calculate the discount here
				if (!$itemLevelDiscount) $discountvalue = $this->getDiscountValue($discountable);
				// add to the total discount
				$discount += ($discountvalue > $discountable) ? $discountable : $discountvalue; //prevent discount being greater than what is possible
			}
		}
		if($this->ForShipping && class_exists('ShippingFrameworkModifier')){
			if($shipping = $order->getModifier("ShippingFrameworkModifier")){
				$discount += $this->getDiscountValue($shipping->Amount);
			}
		}
		//ensure discount never goes above Amount
		if($this->Type == "Amount" && $discount > $this->Amount){
			$discount = $this->Amount;
		}
		return $discount;
	}
	
	/**
	 * Check if order item meets criteria of this coupon
	 * @param OrderItem $item
	 * @return boolean
	 */
	function itemMatchesCriteria(OrderItem $item){
		$products = $this->Products();
		if($products->exists()){
			if(!$products->find('ID', $item->ProductID)){
				return false;
			}
		}
		$categories = $this->Categories()->getIDList();
		if(!empty($categories)){
			$itemproduct = $item->Product(true); //true forces the current version of product to be retrieved.
			if (!$itemproduct) return false;
			$productCats = $itemproduct->ProductCategories()->getIDList();
			$productCats[$itemproduct->ParentID] = $itemproduct->ParentID;
			$overlap = array_intersect_key($categories, $productCats);
			if (empty($overlap)) return false;
		}
		$match = true;
		$this->extend("updateItemCriteria",$item, $match);
		return $match;				
	}
	
	/**
	 * Works out the discount on a given value.
	 * @param float $subTotal
	 * @return calculated discount
	 */
	function getDiscountValue($value){
		$discount = 0;
		if($this->Amount) {
			$discount += abs($this->Amount);
		}
		if($this->Percent) {
			$discount += $value * $this->Percent;
		}
		return $discount;
	}
	
	function getDiscountNice(){
		if($this->Type == "Percent"){
			return $this->dbObject("Percent")->Nice();
		}
		return $this->dbObject("Amount")->Nice();
	}
	
	/**
	* How many times the coupon has been used
	* @param string $order - ignore this order when counting uses
	* @return int
	*/
	function getUseCount($order = null, $memberID = 0) {
//		$filter = "\"Order\".\"Paid\" IS NOT NULL";
//		if($order){
//			$filter .= " AND \"OrderAttribute\".\"OrderID\" != ".$order->ID;
//		}
//		$join = "INNER JOIN \"Order\" ON \"OrderAttribute\".\"OrderID\" = \"Order\".\"ID\"";
//		$query = new SQLQuery("COUNT(\"OrderCouponModifier\")");
//		$query = singleton("OrderCouponModifier")->buildSQL("","","",$join);
//		$query->where = array($filter);
//		$query->select("OrderCouponModifier.ID");
//		return $query->unlimitedRowCount("\"OrderCouponModifier\".\"ID\"");

		//$filter = "\"Order\".\"Paid\" IS NOT NULL";
		
		//Instead of checking on Order.Paid we're here checking on Order.Reference
		//As an order ordered on a gift card does not necessary need to have a paid date
		$filter = "\"Order\".\"Reference\" IS NOT NULL";
		
		//Additionally we're making sure that we're only counting order items
		//That belong to this coupon
		$filter .= " AND \"OrderCouponModifier\".\"CouponID\" =" . $this->ID;

		if ($order) {
			$filter .= " AND \"OrderAttribute\".\"OrderID\" != ".$order->ID;
		}

		if ($memberID) {
			$filter .= " AND \"Order\".\"MemberID\" = '{$memberID}'";
		}

		return OrderCouponModifier::get()
			->where($filter)
			->innerJoin('Order', '"OrderAttribute"."OrderID" = "Order"."ID"')
			->count();
	}
	
	/**
	* Forces codes to be alpha-numeric, without spaces, and uppercase
	*/
	function setCode($code){
		$code = preg_replace('/[^0-9a-zA-Z]+/', '', $code);
//		$code = trim(preg_replace('/\s+/', "", $code)); //gets rid of any white spaces
		$this->setField("Code", strtoupper($code));
	}
	
	function canDelete($member = null) {
		if($this->getUseCount()) {
			return false;
		}
		return true;
	}

	function canEdit($member = null) {
		//TODO: reintroduce this code, once table fields have been fixed to paginate in read-only state
		/*if($this->getUseCount() && !$this->Active) {
			return false;
		}*/
		return true;
	}
	
	protected function message($messsage, $type = "good"){
		$this->message = $messsage;
		$this->messagetype = $type;
	}
	
	protected function error($message){
		$this->message($message, "bad");
	}
	
	function getMessage(){
		return $this->message;
	}
	
	function getMessageType(){
		return $this->messagetype;
	}

	/**
	 * Getter for the print link for gift cards
	 * @return string
	 */
	public function getGiftCardPrintLink() {
		$voucher = $this->GiftVoucher();
		if ($voucher && $voucher->exists()) {
			
			$action = 'forceprint';
			
			if ($voucher->Delivery == 'Email') {
				$action = 'show';
			}
			
			return "/giftcardprint/$action/{$this->Code}";
		} 
		
	}

}
