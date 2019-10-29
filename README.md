oscommerce-Twispay_Payments
=========================

The official [Twispay Payment Gateway][twispay] extension for OScommerce.

At the time of purchase, after checkout confirmation, the customer will be redirected to the secure Twispay Payment Gateway.

All payments will be processed in a secure PCI DSS compliant environment so you don't have to think about any such compliance requirements in your web shop.

Install
=======

TODO 1. Download the Twispay payment module from OScommerce Marketplace, where you can find [The Official Twispay Payment Gateway Extension][marketplace]
2. Unzip the archive files and upload the content of folder "uploads" in the corresponding files on the server.
3. Sign in to your OScommerce admin.
4. Click **Modules** tab and **Payment**.
5. Click **Install Module**.
6. Find **Credit card secure payment | Twispay** click on it and then click **Install Module**.
7. Click **Modules** tab and **Payment**.
8. Find **Credit card secure payment | Twispay** click on it and then click **Edit**.
9. Select **True** under **Enable Twispay**.
10. Select **False** under **Test Mode**. _(Unless you are testing)_
11. Enter your **Live Site ID**. _(Twispay ID for live site)_
12. Enter your **Live Site Key**. _(Twispay private KEY for live site)_
13. Enter your **Custom redirect page**. _(Optional, if you have a custom thank you page)_
14. Select **Payment Zone**. _(Optional, if any valid payment zone is defined)_
15. Enter your **Contact email**. _(Optional, The email address for technical support)_
16. Enter **Transactions on page**. _(The number of results on a transaction list page)_
17. Enter the **Sort Order**. _(The order that the payment option will appear on the checkout payment tab in accordance with the other payment methods. Lowest is displayed first.)_
18. Select **___ Order Status**. _(Optional, if you want to assign other order statuses to Twispay predefined ones)_
19. Save your changes.

Changelog
=========

= 1.0.1 =
* Updated the way requests are sent to the Twispay server.
* Updated the server response handling to process all the possible server response statuses.
* Added support for refunds.
* Added filter by status an sorting support for transactions table.

= 1.0.0 =
* Initial Plugin version

<!-- Other Notes
===========

A functional description of the extension can be found on the [wiki page][doc] -->

TODO
[twispay]: http://twispay.com/
[marketplace]: https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=31761&filter_member=twispay
[github]: https://github.com/MichaelRotaru/OpenCart3.0
