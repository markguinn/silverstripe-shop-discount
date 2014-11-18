<?php
/**
 * Member Balance Extension
 * Allowing logged in members Members to have positive balance
 * that will be deducted from purchases before any other
 * means of payment is charged.
 * 
 */
class MemberBalanceExtension extends DataExtension {

	private static $db = array(
		'AccountBalance' => 'Money', //this should always be 0 or positive - we don't work with credit (for the moment at least)
		'AccountBalanceChangeDescription' => 'Text' //this is hidden, and used for the history
	);
	
	private static $has_many = array(
		'AccountBalanceHistories' => 'AccountBalanceHistory'
	);

	/**
	 * By default balance history is written automatically
	 * This is needed for when the balance is updated from within the CMS
	 * 
	 * @var bool
	 */
	public static $enable_on_after_write = true;
	

	/**
	 * Getter for the balance amount
	 * @return int
	 */
	public function getAccountBalanceAmount() {
		return $this->owner->AccountBalance->getAmount();
	}

	/**
	 * Setter for the balance amount
	 * @param int $amount
	 */
	public function setAccountBalanceCustom($amount, $currency = 'USD') {
		$this->owner->AccountBalance = DBField::create_field('Money', array(
			"Currency" => $currency,
			"Amount" => $amount
		));
	}
	

	public function updateCMSFields(FieldList $fields) {

		//$f = TextField::create('AccountBalance');
		
		//Balance editing
		$bf = new MoneyField('AccountBalance');
		$bf->setAllowedCurrencies(array('USD'));
		
		
		$cm = Member::currentUser();
		$bdf = new HiddenField(
			'AccountBalanceChangeDescription'
		);
		$bdf->setValue("Changed by {$cm->getName()} via the CMS");
		
		//Balance history
		$fields->removeByName('AccountBalanceHistories');
		$hf = new GridField('AccountBalanceHistories', 'History', $this->owner->AccountBalanceHistories());
		
		
		
		$fields->addFieldsToTab('Root.Balance', array(
				$bf,
				$bdf,
				HeaderField::create('BalanceHistory', 'Balance History'),
				$hf
			)
		);
 
	}


	/**
	 * Writing the balance history on after write
	 */
	public function onAfterWrite() {
		parent::onAfterWrite();
		
		//Will only be executed by default
		//As this is only meant to happen when a balance is edited via the CMS
		if (!self::$enable_on_after_write) return;
		
		
		$balanceAmount = $this->owner->AccountBalance
			->getAmount();
		$balanceChangeDesc = $this->owner->AccountBalanceChangeDescription;
		
		
		$lastHistoryItem = $this->owner->AccountBalanceHistories()
			->first();

		
		if ($lastHistoryItem && $lastHistoryItem->exists()) {
			
			//Create balance history if the balance has changed
			if ($lastHistoryItem->Balance->getAmount() != $balanceAmount) {
				AccountBalanceHistory::create_history(
					$this->owner,
					$balanceAmount,
					$balanceChangeDesc
				);
			}
			
			
		} else {
			//create the first balance history, if current balance is not 0
			if (!$balanceAmount == 0) {
				AccountBalanceHistory::create_history(
					$this->owner,
					$balanceAmount,
					$balanceChangeDesc
				);
			}
		}

	}

	/**
	 * Add the remainder of a coupon to the Member's balance
	 * This should happen once the order has been finalized and paid for
	 * 
	 * @param OrderCoupon $coupon
	 * @param Int         $amount
	 * @param null        $order
	 */
	public function addCouponRemainderToBalance($amount, $coupon, $order = null) {
		$o = $this->owner;

		$description = "Added remainder from gift card {$coupon->Code}";
		if ($order) {
			$description .= " on order #{$order->Reference}";
		}
		
		$this->modifyBalance($amount, $description, $order, $coupon);
	}

	/**
	 * Subtract from the Member's balance
	 * This should happen once the order has been finalized
	 * 
	 * @param Int  $amount
	 * @param null $order
	 */
	public function subtractBalance($amount, $order = null) {
		$o = $this->owner;

		//make amount negative
		$negativeAmount = $amount * -1;
		
		$description = "Subtracted $amount";
		if ($order) {
			$description .= " for order #{$order->Reference}";
		}
		
		$this->modifyBalance($negativeAmount, $description, $order);
	}


	/**
	 * Modifies the balance - either adds or subtracts
	 * 
	 * @param int              $amount
	 * @param string           $description
	 * @param null|Order       $order
	 * @param null|OrderCoupon $coupon
	 */
	private function modifyBalance($amount, $description, $order = null, $coupon = null) {
		$o = $this->owner;

		//make sure that history is not written on after write
		//we want to write it ourselves
		self::$enable_on_after_write = false;

		$currentBalance = $o->getAccountBalanceAmount();
		$newBalance = $currentBalance + $amount;

		$o->setAccountBalanceCustom($newBalance);
		$o->write();

		//Creating history
		$history = AccountBalanceHistory::create_history(
			$o,
			$newBalance,
			$description
		);
		
		//Adding coupon/order relations to history
		if ($coupon) {
			$history->CouponID = $coupon->ID;
		}
		if ($order) {
			$history->OrderID = $order->ID;
		}
		$history->write();

	}
	

}