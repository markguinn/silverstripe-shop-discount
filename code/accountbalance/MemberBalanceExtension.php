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


}