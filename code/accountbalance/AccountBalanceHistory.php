<?php
/**
 * History for the Member Account Balance
 * Keeps track of what's been added/subtracted from a member's balance
 * 
 */
class AccountBalanceHistory extends DataObject {

	private static $db = array(
		'Balance' => 'Money',
		'Description' => 'Text'
	);
	
	private static $has_one = array(
		'Member' => 'Member',
		//These are only relevant when the balance
		//has been changed through a coupon/order
		'Coupon' => 'OrderCoupon',
		'Order' => 'Order'
	);
	
	private static $default_sort = 'Created DESC';

	private static $summary_fields = array(
		'Created' => 'Created',
		'Balance' => 'Balance',
		'Description' => 'Description',
		'Order.Reference' => 'Order'
	);

	/**
	 * Getter for the balance amount
	 * @return int
	 */
	public function getBalanceAmount() {
		return $this->Balance->getAmount();
	}

	/**
	 * Custom setter for the balance amount
	 * @param int $amount
	 */
	public function setBalanceCustom($amount, $currency = 'USD') {
		$this->Balance = DBField::create_field('Money', array(
			"Currency" => $currency,
			"Amount" => $amount
		));
	}
	
	
	/**
	 * Creating the balance history
	 * 
	 */
	public static function create_history($member, $amount, $description = null, $currency = 'USD') {
		$h = new AccountBalanceHistory();
		$h->MemberID = $member->ID;
		$h->setBalanceCustom($amount, $currency);
		$h->Description = $description;
		$h->write();
		
		return $h;
	}
}