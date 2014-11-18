# Gift Cards

_by Anselm Christophersen (<anselm@adaircreative.com>), November 2014_




## Administration

* Gift cards are added via CMS (the code needs to be set up so it's possible to 
add products `GiftVoucherProduct` products.
* From the CMS a gift card resembles a normal product - the main difference is
that under pricing there's the option for the customer to choose the price.
	* If this option has been chosen, the customer will be presented with a dropdown
of price options. The dropdown will also contain an option for the customer to choose
a price.



## Purchase use cases

### Purchasing a Gift Card

* The customer purchases one or more gift cards
	* The customer can choose between preselected options in the dropdown,
	or enter an own amount
* After successful payment, the customer receives an invoice, and one or
many gift card vouchers
	* Gift card vouchers are also visible under "Account > Past Orders"
* These vouchers can now be entered when purchasing items in the store


### Using a Gift Card

* The customer fills up the shopping cart with one or several items
* The customer enters the coupon code in the coupon code field, and
the amount of the gift card amount will be deducted from the order total
	* The customer is notified that if the order is less than the gift card amount,
	the remainder will be added to his/her balance on checkout
* The customer can now check out like usual
* Once the order has been processed, the gift card cannot be reused, but the 
remainder is added to the customer's account


## Installation

Add these to your `yml` config:

	Member:
	  extensions:
	   - MemberBalanceExtension
	Order:
	  extensions:
		- AccountBalanceOrderExtension


Add this to your `_config.php`:

	Order::set_modifiers(array(
		"OrderCouponModifier",
		"OrderMemberBalanceModifier",
	));




## Todos/Issues/Edge cases

* Option to enter a message for the recipient
	* Either print or email the voucher to someone else
* Make some kind of gift card design?
* It seems emails are not being sent out when the order total is 0
* Adding a gift card/coupon while having a positive balance, gives errors
in the calculation

## Possible improvements

* consider naming - member balance / account balance / ?







