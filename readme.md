#MyCloudFulfillment Integration for Commerce on MODX CMS
Development by Murray Wood at Digital Penguin.

**Thanks to Inside Creative for sponsoring the development of this module.

##Requirements
Commerce_MyCloudFulfillment requires at least MODX 2.6.5 and PHP 7.1 or higher. Commerce by modmore should be at least version 1.1.4. You also need to have an MyCloudFulfillment account which provides an api key and a secret key.

##Installation
Install via the MODX package manager. The package name is Commerce_MyCloudFulfillment.

##Introduction
This Commerce module introduces a custom shipping method called "MyCloudFulfillment Shipping Method".
When a customer pays for an order using this shipping method, the order details are sent to MyCloudFulfillment to start the delivery process.

On the Commerce order detail screen there is an order field showing if the order has been sent.
There are also details including the mycloud id and tracking url on each order shipment.

This module also includes a webhook that can be set up so when a shipment is being readied or delivered, 
MyCloudFulfillment can update the order status in Commerce automatically.

##Setup
- Enable the module: In the MODX manager, open Commerce then go to `Configuration` -> `Modules`.
Find Commerce_MyCloudFulfillment in the list and enable it for test and live modes.
  

- Module setup: On the same modal, there is a checkbox `Enable test account details`. If you have 
managed to get test api credentials, check the box, save and enter them in the new fields. Otherwise don't check the box to use the live/production credentials.
  `Note: due to difficulty getting test keys for this API this checkbox is the only way to use them. If you don't check it, the Commerce test mode will attempt to use the live endpoints.`
  

- Create your shipping methods: On the Commerce configuration page, click on the `Shipping Methods` tab. 
Create as many shipping methods as you like but to use this module you need to select `MyCloud Fulfillment Shipping Method` from the drop down box.
  This custom shipping method type has an extra field at the bottom called `MyCloud Fulfillment Shipping ID#`.
  Get this number from your MyCloudFulfillment account.
  

- Set up statuses: The following statuses will be sent from the API `PACKED`, `INPROGRESS`, `SHIPPED`, `DELIVERED`
You should set up statuses with the same names in that order. The webhook will then process the status changes when it's notified.
  

- Set up webhook. The webhook is available at `https://example.com/assets/components/commerce_mycloudfulfillment/notify.php`
You will need to contact MyCloudFulfillment to have it set.
  
