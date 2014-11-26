<?php
/**
 * Gift voucher products, when purchased will send out a voucher code to the customer via email.
 */
class GiftVoucherProduct extends Product{
	
	static $db = array(
		"VariableAmount" => "Boolean",
		"MinimumAmount" => "Currency"
	);

	private static $email_subject = 'Gift Card from %s';

	static $order_item = "GiftVoucher_OrderItem";
	
	public static $singular_name = "Gift Card";
	function i18n_singular_name() { return _t("GiftVoucherProduct.SINGULAR", $this->stat('singular_name')); }
	public static $plural_name = "Gift Cards";
	function i18n_plural_name() { return _t("GiftVoucherProduct.PLURAL", $this->stat('plural_name')); }
	
	function getCMSFields(){
		$fields = parent::getCMSFields();
		$fields->addFieldToTab("Root.Pricing", 
			new OptionsetField("VariableAmount","Price",array(
				0 => "Fixed",
				1 => "Allow customer to choose"	
			)),
			"BasePrice"
		);
		$fields->addFieldsToTab("Root.Pricing", array(
			$minimumamount = new TextField("MinimumAmount","Minimum Amount") //text field, because of CMS js validation issue
		));
		$fields->removeByName("CostPrice");
		$fields->removeByName("Variations");
		$fields->removeByName("Model");


		$fields->removeByName("Shipping");
		$fields->removeByName("Downloads");
		
		
		return $fields;
	}
	
	function canPurchase($member = null) {
		if(!self::$global_allow_purchase) return false;
		if(!$this->dbObject('AllowPurchase')->getValue()) return false;
		if(!$this->isPublished()) return false;
		return true;
	}
	
}

class GiftVoucherProduct_Controller extends Product_Controller{

	private static $allowed_actions = array(
		'Form',
		'AddProductForm'
	);
	
	
	function Form(){
		
		Requirements::javascript('shop_discount/javascript/GiftVoucherProduct.js');
		//hiding unit price on init - will only be shown whe "Enter amount" is chosen
		Requirements::customCSS('
			#UnitPrice {
				display:none;
			}
		');
		
		$form = parent::Form();
		if($this->VariableAmount){
			$form->setSaveableFields(array(
				"UnitPrice",
				"Message",
				"Delivery",
				"RecipientEmail",
			));
			
			
			$fields = $form->Fields(); 
			
			
			//Amount
			$fields->push(
				$giftDropdown = new DropdownField(
					"GiftCardAmountDropdown",
					"Amount",
					array(
						//'$0.00' => 'Please select amount',
						'10.00' => '$10',
						'25.00' => '$25',
						'50.00' => '$50',
						'75.00' => '$75',
						'100.00' => '$100',
						'150.00' => '$150',
						'200.00' => '$200',
						'' => 'Enter Amount'
						
					),
					$this->BasePrice
				)
			);
			$giftDropdown->setForm($form);
			//Debug::dump($this->BasePrice);
			//$giftDropdown->setEmptyString($this->BasePrice);

			$fields->push(
				$giftamount = new CurrencyField(
					"UnitPrice",
					"Enter Amount",
					$this->BasePrice
				)
			); //TODO: set minimum amount
			$giftamount->setForm($form);


			//Message
			$fields->push(
				$m = new TextareaField(
					'Message',
					'Enter a message for the recipient'
				)
			);


			//Delivery
			$fields->push(
				$d = new OptionsetField(
					'Delivery',
					'Delivery',
					//singleton('GiftVoucher_OrderItem')->dbObject('Delivery')->enumValues()
					array(
						'Email' => 'Email',
						'PrintAtHome' => 'Print at Home'
					)
				)
			);
			$d->setValue('Email');
			
			//Recipient email
			$fields->push(
				$r = new TextField(
					'RecipientEmail',
					'Recipient Email'
				)
			);
			
		}
		$form->setValidator($validator = new GiftVoucherFormValidator(array(
			"Quantity", "UnitPrice"	
		)));
		return $form;
	}
	
}

class GiftVoucherFormValidator extends RequiredFields{
	
	function php($data){
		$valid =  parent::php($data);
		if($valid){
			$controller = $this->form->Controller();			
			if($controller->VariableAmount){
				$giftvalue = $data['UnitPrice'];
				if($controller->MinimumAmount > 0 && $giftvalue < $controller->MinimumAmount){
					$this->validationError("UnitPrice", "Gift value must be at least ".$controller->MinimumAmount);
					return false;
				}
				if($giftvalue <= 0){
					$this->validationError("UnitPrice", "Gift value must be greater than 0");
					return false;
				}
			}
		}
		return $valid;
	}
	
}

class GiftVoucher_OrderItem extends Product_OrderItem{
	
	static $db = array(
		"GiftedTo" => "Varchar",
		"Delivery" => "Enum(array('Email', 'PrintAtHome', 'Email'))",
		"RecipientEmail" => "Varchar",
		"Message" => "Text"
	);
	
	static $has_many = array(
		"Coupons" => "OrderCoupon"
	);
	
	static $required_fields = array(
		"UnitPrice"	
	);
	
	/**
	 * Don't get unit price from product
	 */
	function UnitPrice() {
		if($this->Product()->VariableAmount){
			return $this->UnitPrice;
		}
		return parent::UnitPrice();
	}
	
	/**
	 * Create vouchers on order payment success event
	 */
	function onPlacement() {
	//function onPayment(){
		//parent::onPayment();
		parent::onPlacement();
		if($this->Coupons()->Count() < $this->Quantity){
			$remaining = $this->Quantity - $this->Coupons()->Count();
			for($i = 0; $i < $remaining; $i++){
				if($coupon = $this->createCoupon()){
					$this->sendVoucher($coupon);
				}
			}
		}
	}
	
	/**
	 * Create a new coupon, based on this orderitem
	 * @return OrderCoupon
	 */
	function createCoupon(){
		if(!$this->Product()){
			return false;
		}
		$coupon = new OrderCoupon(array(
			"Title" => $this->Product()->Title,
			"Type" => "Amount",
			"Amount" => $this->UnitPrice,
			"UseLimit" => 1,
			//we don't want any min value, as any unused amount will be added to the user's balance
			//"MinOrderValue" => $this->UnitPrice //safeguard that means coupons must be used entirely
		));
		$this->extend("updateCreateCupon",$coupon);
		$coupon->write();
		$this->Coupons()->add($coupon);
		return $coupon;
	}
	
	/*
	 * Send the voucher to the appropriate email
	 */
	function sendVoucher(OrderCoupon $coupon){
		
		//fallback settings
		$from = Email::getAdminEmail();
		$to = $this->Order()->getLatestEmail();
		$subject = "Gift Card";
		
		$m = $this->Order()->Member();


		$delivery = $this->Delivery;
		
		if ($delivery == 'Email') {
			$recipientEmail = $this->RecipientEmail;
			if (Email::validEmailAddress($recipientEmail)) {

				if ($m && $m->exists()) {
					$subject = sprintf(self::config()->get('email_subject'), $m->getName());
				}
				$from = $this->Order()->getLatestEmail();
				$to = $recipientEmail;
			}
		} else {
			//TODO actually no emails should be sent here,
			//and instead there should be an option to print a gift
			//card from the receipt
			//Until this has been developed, an email will be sent out
			$subject = 'Print this gift card for handing out';
		}
		
		
		$email = new Email($from, $to, $subject);
		$email->setTemplate("GiftVoucherEmail");
		$email->populateTemplate(array(
			'Coupon' => $coupon,
			'Message'=> $this->Message,
			'Sender' => $m,
			'Delivery' => $delivery
		));
		return $email->send();
	}
	
}
