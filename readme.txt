=== PlaneUpload - Files for WooCommerce Orders ===
Contributors: yagular
Tags: woocommerce, order files, files, uploader
Requires at least: 6.0
Tested up to: 6.0
Requires PHP: 7.2
Stable tag: 1.0.4
License: GPLv2

PlaneUpload allows your customers to upload files to your WooCommerce orders. Files are stored in your cloud account (Google Drive, AWS S3, FTP or Dropbox).

== Description ==

PlaneUpload allows your customers to upload files to your WooCommerce orders.
The uploader is present in the product view, cart view, order (customer) and order (admin).
In the product and cart view, the files are in temporary (attachment) mode. After the customer places the order,
the files are saved in your cloud.


Files are uploaded into your cloud account that may be:

- Google Drive
- AWS S3
- Dropbox
- (S)FTP Server


Instead of storing the files in your Wordpress server, they are in your cloud, what are the advantages of doing so?

- It is impossible to hack your website uploading some malicious files, as they are stored in different place
- You will never run out of free disk space in your wordpress installation (by uploading those files)


Key features:

- advanced ajax uploader (drag & drop, multiple file upload, paste from clipboard, supports different browsers and devices)
- automatic backups to another cloud (your files are uploaded into the two different clouds simultaneously)
- automatic compression that save space in your cloud
- automatic file encryption
- customization using CSS
- file comments (your customer and you can add comments to any file)
- logs (you can see what happend in the uploader, for example if the file was removed)
- thumbnails for file formats like .pdf, .pps, .doc, .html, .psd, .cdr, .mp4 and over 100+ more

== Installation ==

* PHP 7.2 or greater is recommended
* Your API key is encrypted in your database with openssl (aes-128-cbc).
If you have not openssl configured on your system, or 'aes-128-cbc' is not supported, the plugin will not encrypt your API key. But it's highly recommended that you configure openssl to enable the encryption of the API key.
In the file planeupload-client.php - you can change the encryption method ($encryptionMethod)
or disable this feature (not recommended) with $apiKeyEncryptionEnabled

1. Upload the PlaneUpload plugin to your WooCommerce, activate it and go to PlaneUpload Settings on the main menu.
2. Register your account on [planeupload.com](https://app.planeupload.com/), connect your first cloud and add an API key [here](https://app.planeupload.com/api)
3. Copy the API key and paste it in your WooCommerce PlaneUpload Settings, click on "Save API Key"
4. You are all set. Now on your product view, cart view and orders there should be PlaneUpload widget installed.
If they are not, please contact the support, as there may be some differences in your WooCommerce theme.

The next step is customizing the button so it fits your store. On the PlaneUpload settings page click on "Upload button settings"
It will open the Prototype button - all other buttons on your website are copies of this specific prototype.
So if you change any settings here, it will reflect on others. You can change the looks here by changing the color manually, or setting up your custom CSS code.





