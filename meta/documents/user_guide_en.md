<div class="alert alert-warning" role="alert">
   <strong><i>Note:</i></strong> The Novalnet plugin has been developed for use with the online store Ceres and only works with its structure or other template plugins. IO plugin is required.
</div>

# Novalnet payment plugin for plentymarkets

Novalnet payment plugin for plentymarkets simplifies your daily work by automating the entire payment process from checkout till collection. This plugin is designed to help you increase your sales by offering various international and local payment methods. The plugin which is perfectly adjusted to plentymarkets and the top-quality range of services of the payment provider Novalnet.

The various range of payment methods includes **Credit Card**, **Direct Debit SEPA**, **PayPal**, **Invoice**, **Online transfer**, **iDeal** and so on.

## Opening a Novalnet merchant account

You need to open an merchant account with Novalnet before you can set up the payment plugin in the plentymarket. You will then receive the credentials that is required to install and configure the payment method. Please contact Novalnet at [sales@novalnet.de](mailto:sales@novalnet.de) for getting your own merchant account.

## Configuring Novalnet in plentymarkets

To set up the merchant credentials, navigate to the path **Plugins -> Plugin overview -> Novalnet -> Configuration**

### Novalnet Global Configuration

- Fill in your Novalnet merchant account details to make the payment method appear in the online store.
- The fields **Merchant ID**, **Authentication code**, **Project ID**, **Tariff ID** and **Payment access key** are necessary and also marked mandatory.
- These values can be retrieved from the [Novalnet administration portal](https://admin.novalnet.de/).
- After filling those values in the respective fields, you must check the option **Enable payment method** to complete the Novalnet payment setup in your store.

##### Retrieving Novalnet merchant account details:

1. Login into your merchant account.
2. Navigate to the tab **PROJECTS**.
3. Select the corresponding product.
4. Under the **Shop Parameters**, you will find the details required.
5. On an important note, you might find mutiple **Tarif ID's** (if created more than one for your project). Get the Tarif ID which you wish to use it in the online store.

### Novalnet configuration along with it's descriptions

<table>
    <thead>
        <th>
            Setting
        </th>
        <th>
            Description
        </th>
    </thead>
    <tbody>
        <tr>
        <td class="th" align=CENTER colspan="2">General</td>
        </tr>
        <tr>
            <td>
                <b>Enable test mode</b>
            </td>
            <td>Payment will be processed in the test mode therefore amount for this transaction will not be charged</td>
        </tr>
        <tr>
            <td>
                <b>Merchant ID</b>
            </td>
            <td>A merchant identification number is provided by Novalnet after opening a merchant account at Novalnet. Please contact Novalnet at <a href="mailto:sales@novalnet.de">sales@novalnet.de</a> for getting your own merchant account.</td>
        </tr>
        <tr>
            <td>
                <b>Authentication code</b>
            </td>
            <td>Merchant authentication code is provided by Novalnet after opening a merchant account at Novalnet.</td>
        </tr>
        <tr>
            <td>
                <b>Project ID</b>
            </td>
            <td>Project identification number is an unique ID of merchant project. The merchant can create N number of projects through Novalnet merchant administration portal.</td>
        </tr>
        <tr>
            <td>
                <b>Tariff ID</b>
            </td>
            <td>Tariff identification number is an unique ID for each merchant project. The merchant can create N number of tariffs through Novalnet merchant administration.</td>
        </tr>
        <tr>
            <td>
                <b>Payment access key</b>
            </td>
            <td>This is the secure public key for encryption and decryption of transaction parameters. This is mandatory value for all online transfers, Credit Card-3D secure and wallet systems. </td>
        </tr>
        <tr>
            <td>
                <b>Referrer ID</b>
            </td>
            <td>
                Referrer ID of the person/company who recommended you Novalnet.
            </td>
        </tr>
        <tr>
        <td class="th" align=CENTER colspan="2"><b>Payment settings</b></td>
        </tr>
        <tr>
        <td class="th" align=CENTER colspan="2">Credit Card</td>
        </tr>
        <tr>
            <td>
                <b>Enable 3D secure</b>
            </td>
            <td>On activating 3D-Secure, the issuing bank prompts the buyer for a password. This helps in preventing a fraudulent payment. It can be used by the issuing bank as evidence that the buyer is indeed the card holder. This is intended to help decrease a risk of charge-back.</td>
        </tr>
        <tr>
            <td>
                <b>Set a limit for on-hold transaction</b> (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)
            </td>
            <td>In case the order amount exceeds mentioned limit, the transaction will be set on hold till your confirmation of transaction.</td>
        </tr>
        <tr>
        <td class="th" align=CENTER colspan="2">Direct Debit SEPA</td>
        </tr>
        <tr>
            <td>
                <b>Set a limit for on-hold transaction</b> (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)
            </td>
            <td>In case the order amount exceeds mentioned limit, the transaction will be set on hold till your confirmation of transaction.</td>
        </tr>
        <td class="th" align=CENTER colspan="2">Invoice / Prepayment</td>
        <tr>
            <td>
                <b>Payment due date (in days)</b>
            </td>
            <td>Number of days to transfer the payment amount to Novalnet (must be greater than 7 days). In case if the field is empty, 14 days will be set as due date by default.</td>
        </tr>
        <tr>
            <td>
                <b>Set a limit for on-hold transaction</b> (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)
            </td>
            <td>In case the order amount exceeds mentioned limit, the transaction will be set on hold till your confirmation of transaction.</td>
        </tr>
        <td class="th" align=CENTER colspan="2">Barzahlen</td>
        <tr>
            <td>
                <b>Slip expiry date (in days)</b>
            </td>
            <td>Enter the number of days to pay the amount at store near you. If the field is empty, 14 days will be set as due date by default.</td>
        </tr>
        <td class="th" align=CENTER colspan="2">Paypal</td>
        <tr>
            <td>
                <b>Set a limit for on-hold transaction</b> (in minimum unit of currency. E.g. enter 100 which is equal to 1.00)
            </td>
            <td>In case the order amount exceeds mentioned limit, the transaction will be set on hold till your confirmation of transaction.</td>
        </tr>
    </tbody>
</table>

## Displaying the payment transaction details on the order confirmation page

To display the payment transaction details on the order confirmation page, perform the steps as follows.

##### Displaying transaction details:

1. Go to **CMS » Container links**..
3. Go to the **Novalnet payment details** area.
4. Activate the container **Order confirmation: Additional payment information**.
5. **Save** the settings.<br />→ The payment transaction details will be displayed on the order confirmation page.

## Update of Vendor Script URL

Vendor script URL is required to keep the merchant’s database/system up-to-date and synchronized with Novalnet transaction status. It is mandatory to configure the Vendor Script URL in [Novalnet administration portal](https://admin.novalnet.de/).

Novalnet system (via asynchronous) will transmit the information on each transaction and its status to the merchant’s system.

To configure Vendor Script URL,

1. Login into your merchant account.
2. Navigate to the tab **PROJECTS**.
3. Select the corresponding product.
4. Under the tab **Project Overview**.
5. Set up the **Vendor script URL** fof your store. In general the vendor script URL will be like **YOUR SITE URL/payment/novalnet/callback**.

## Further reading

To know more information about the Novalnet and it's features, please contact at  [sales@novalnet.de](mailto:sales@novalnet.de)
