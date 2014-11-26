<?php
class GiftCardPrintController extends Controller {

	private static $allowed_actions = array(
		'show',
		'forceprint'
	);
	
	
	private $forcePrint = false;
	
	public function show() {
		$params = $this->getURLParams();
		//Debug::dump($params);
		
		$code = $params['ID'];


		$coupon = OrderCoupon::get_by_code($code);


		if ($coupon && $coupon->exists()) {
			$voucher = $coupon->GiftVoucher();


			$email = new GiftCardPrintController_Email();
			$email->setTemplate("GiftVoucherEmail");
			$voucher->populateEmailTemplate($email, $coupon);
			
			return $this->customise(array(
				'Layout' => $email->getParsedBody(),
				'ForcePrint' => $this->forcePrint
			))->renderWith('GiftCardPrintController');
			
		} else {
			return $this->preventSniffing();
		}
	}
	
	public function forceprint() {
		$this->forcePrint = true;
		return $this->show();
	}


	/**
	 * If somebody tries to guess coupons, they'll have to wait,
	 * afterwards they'll get a 404
	 * This renders it practically impossible to guess coupons
	 */
	private function preventSniffing() {
		return 'wating...';
		return $this->httpError(404, 'Not found');
		
	}
}

/**
 * Helper class, allowing the print controller to access the email template
 */
class GiftCardPrintController_Email extends Email {

	public function getParsedBody() {
		$this->parseVariables();
		return $this->body;
	}
}