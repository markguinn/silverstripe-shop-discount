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
		'Member' => 'Member'
	);
	
	
	private static $default_sort = 'Created DESC';

	private static $summary_fields = array(
		'Created' => 'Created',
		'Balance' => 'Balance',
		'Description' => 'Description',
	);

	/**
	 * Creating the balance history
	 * This should only be called from {@see MemberBalanceExtension::onAfterWrite}
	 * 
	 */
	public static function create_history($member, $amount, $description = null, $currency = 'USD') {
		$h = new AccountBalanceHistory();
		$h->MemberID = $member->ID;
		$h->Balance = DBField::create_field('Money', array(
			"Currency" => $currency,
			"Amount" => $amount
		));
		$h->Description = $description;
		$h->write();
	}
}