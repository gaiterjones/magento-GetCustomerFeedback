## Magento Discount Coupon Code for Facebook Likes
***

### Synopsis
The code in the module is called at checkout caching customer information and products ordered. The module then uses the Magento scheduling system to execute code that regularly checks the cached files to create a customised customer feedback email containing direct links to the review pages of the products the customer has ordered.

When configurable parameters, such as order status, or elapsed time since the order was placed are met the email is sent to the customer and the order status updated.

The customer feedback email allows you to directly follow up every customer order and offer incentives to the customer to complete product reviews and ratings. An example email is shown below.

![image](http://blog.gaiterjones.com/wp-content/uploads/2011/11/getcustomerfeedbackexampleemail1-620x571.jpg)

### Version
***
	@version		0.6.0
	@since			05 2013
	@author			gaiterjones
	@documentation	[blog.gaiterjones.com](http://blog.gaiterjones.com)
	@twitter		twitter.com/gaiterjones
	
### Requirements

* PHP5.x/MYSQL

* Magento 1.3+

### Installation

Enable Magento scheduling system

To install this extension:

1. Unzip the module and copy the contents of the modules app folder to the app folder of your Magento store installation.

2. Ensure that the module /cache folder has write permission set to allow the WWW user group write access. The module caches customer and product information in this folder.

3. Refresh the Magento Store cache

4. Logout and login back into the admin backend, goto System – Configuration and locate the configuration settings under My Extensions – Get Customer Feedback.
 

## License

The MIT License (MIT)
Copyright (c) 2013 Peter Jones

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.