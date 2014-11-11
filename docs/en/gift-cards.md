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

* The customer purchases one or more gift cards
	* The customer can choose between preselected options in the dropdown,
or enter an own amount
* After successful payment, the customer receives an invoice, and one or
many gift card vouchers.
* These voucers can now be entered when purchasing items in the store





## questions / issues right now

* there is an issue with the template when buying a gift card


## TODOS

* MAJOR: it should be possible to use the credit on a gift card for more than one purchase 
* Option to enter a message for the recipient
* Make some kind of gift card design?
* Test things on a real server that can send emails


## IDEAS

* Gift cards can either be used as one-off coupons, or added to your profile.
* Make `GiftVoucherProduct` a digital purchase, so bought gift voucher codes are shown under
"digital purchases"



