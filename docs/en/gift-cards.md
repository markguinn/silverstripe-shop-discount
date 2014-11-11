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
many gift card vouchers.
	* Gift card vouchers are also visible under "Account > Past Orders"
* These vouchers can now be entered when purchasing items in the store


### Using a Gift Card

* The customer fills up the shopping cart with one or several items
	* The cart sub total must be at least the same as the gift cart or more **(we want to change this)**
* Once done shopping, the customer enters the coupon code in the coupon code field, and
the amount of the gift card amount will be deducted from the order total
* The customer can now check out like usual
* Once the order has been processed, the gift card cannot be reused




## TODOS

* MAJOR: it should be possible to use the credit on a gift card for more than one purchase
	* see below
* Option to enter a message for the recipient
* Make some kind of gift card design?
* It seems emails are not being sent out when the order total is 0



## Ideas for reusing gift cards for more than one purchase

* Create a "redeem gift card form"
	* In order to redeem a gift card the customer needs to be logged in
	* We could still give the customer the option to just use the gift card in it's entirety
	as a coupon
	* The customer would now have a balance tied to his/her account, which will be
	used each time he/she makes a purchase - until the balance is used up





