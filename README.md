# Invoicing Script
A free stand-alone script to handle invoicing and payment via PayPal smart checkout. Requires a free PayPal account and API key. Use this as a base to develop whatever is desired.

## It is most basic
* The UI is not aesthectically dramatic, but can be formatted as desired
* The form fields has no labels, just placeholder attribute
* Data is saved as .json files
* Uses a simple login for administration. Can be developed for greater security if needed. The default password is `admin_config` and is stored in the file named **admin**. This file can be viewed via a browser so it should be protected. I use .htaccess but each server OS that's not Apache will have its own method to restrict file view.
* Form fields are **not sanitized**. Add security processes as needed
* If the MailJet SMTP tool will be used, the file named **vendor.zip** must be extracted to enable the dependent libraries. Requires a free account with mailjet.com. There is the standard `mail()` method available, however big bad gmail tends to return if DKIM and SPF does not match their requirements. Mailjet and other SMTP providers will deliver.
* Function names are very common keywords and may cause conflict if loaded in WordPress or other packages. Either wrap within a class or make the names unique

### paths
* config - invoicing/?do_config=1
* create invoice - invoicing/?do_invoice=1
* edit an invoice - invoicing/?do_invoice=1&edit=invoice-3504.json
* view invoice - invoicing/?client=invoice-3504

### Front Invoice View
![invoice](https://github.com/user-attachments/assets/51245806-a081-4c7e-b54f-8dba72a94242)

### Pay Via PayPal Smart Checkout Without Leaving Page
![pay-in-page](https://github.com/user-attachments/assets/2e288783-a637-4ccc-92b4-122b75b5e976)

### If there is no invoice, can serve as a pay resource
![funding](https://github.com/user-attachments/assets/80da9fc4-4cbb-4263-9b73-518cbac37595)

### Invoice Creation
![create-invoice](https://github.com/user-attachments/assets/3968cf7e-30b6-4226-928c-6ca1f729f4e2)

![new-invoice](https://github.com/user-attachments/assets/66c260ed-ad14-4a3c-b170-bc3614b29e15)

### Configuration
![configs](https://github.com/user-attachments/assets/447b5c36-b29b-4a5c-93c7-3297eb115c9f)
