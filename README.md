# OpenCart 3 - Newsman Newsletter Sync

NewsMAN Plugin for OpenCart 3 facilitates seamless synchronization of your OpenCart customers and subscribers with [Newsman](https://www.newsmanapp.com) lists and segments. Simplify the connection between your shop and NewsMAN by generating an API KEY in your NewsMAN account and installing this plugin. This process allows you to effortlessly sync customer and newsletter subscriber data.

![image](https://raw.githubusercontent.com/Newsman/OpenCart2.3-Newsman/master/assets/newsmanBr.jpg)

# Installation

## Older versions of Newsman extension before 3.x.x or without a version.
1. In Admin -> Extensions -> Extensions -> Installer delete "newsmanremarketing.ocmod.zip" and "Oc 3.x Fix OCMOD.ocmod.zip", depending on which is installed.
2. If Newsman plugin wasn't uploaded from admin than delete the files of the extension:
```
admin/controller/extension/analytics/newsmanremarketing.php
admin/controller/extension/module/newsman.php
admin/language/en-gb/extension/analytics/newsmanremarketing.php
admin/language/en-gb/extension/module/newsman.php
admin/view/stylesheet/newsman.css
admin/view/template/extension/analytics/newsmanremarketing.twig
admin/view/template/extension/module/newsman.twig
catalog/controller/extension/analytics/newsmanremarketing.php
catalog/controller/extension/module/newsman.php
catalog/model/extension/module/newsman.php
catalog/view/theme/default/template/extension/module/newsman.twig
system/library/Newsman/Client/Exception.php
system/library/Newsman/Client.php
```

Note: developers can use carefully the script https://github.com/Newsman/OpenCart3-Newsman/blob/master/tools/delete-old-v1.0.0-extension.sh
3. If somone created an archive with the old version of extension than use the "Uninstall" button in Admin -> Extensions -> Extensions -> Installer. 
4. Continue bellow with the installation of the new version of extension 3.x.x.

## Manual installation (download archive and upload):
1. Download the latest **newsman.ocmod.zip** archive from [releases](https://github.com/Newsman/OpenCart3-Newsman/releases) (Git tags 3.x.x-autoload, link in the right sidebar here on GitHub). The archive newsman.ocmod.zip contains the plugin and has the generated `system/library/newsman/vendor/autoload.php` which is required in non-composer Opencart 3 installations.
2. Navigate to Admin -> Extensions -> Extension -> Choose extension type -> Modules -> click the "Install" button for NewsMAN module.
3. Navigate to Admin -> Extensions -> Extensions -> Choose extension type -> Analytics -> NewsMAN Remarketing -> click the "Install" button for NewsMAN Remarketing module.
4. After installation, click the "Edit" button for the "NewsMAN" module from Admin -> Extensions -> Extension -> Choose extension type -> Modules.
5. At this step you will need to click on the "Login with NewsMAN" button and follow the 3 steps to complete the configuration:
* Authenticate in newsman.app.
* Allow access to your NewsMAN account in your store.
* Configure the email list and save the settings.
6. If there are any errors, repeat the configuration using "Login with NewsMAN". Also you can check OpenCart logs for more information in `storage/logs/newsman_*.log``.
   You can increase the log level from extension configuration in Admin -> Newsman -> Settings -> Developer Settings -> Log level.
7. Admin -> Extensions -> Extensions -> Installer, upload "Oc 3.x Fix OCMOD.ocmod.zip"
8. Admin -> Extensions -> Modifications -> Refresh

## Additional steps:
1. Look in Admin > Newsman > * configurations for preferred changes.
2. Verify the storefront for Newsman remarketing JavaScript code.
3. You can also use the debugger in newsman.app > Integrations > NewsMAN Remarketing > "Check installation" button.
   The debugger is similar to Google GTM debugger and shows if the events are tracked correctly by NewsMAN remarketing.

## Manual installation (create archive from source):
1. Download from Github repository > top right corner "Code" > Download ZIP. Unarchive the downloaded file.
2. Go to downloaded directory `src/system/library/newsman/`. Run `composer install --no-dev` or `composer dump-autoload -o` to add the dependencies of this extension.
   Clean up `src/system/library/newsman/` from any unnecessary files and folders. You can compare the contents of the `src/system/library/newsman/` folder with the contents of latest archive in [releases](https://github.com/Newsman/OpenCart3-Newsman/releases , see "Manual installation (download archive and upload)".
3. Create an archive named newsman.ocmod.zip with a folder "upload" and add in it only the contents of the "src/*" folder.
4. Please do the steps from above "Manual installation (download archive and upload)" from step 2 including to the end.

## Description

The NewsMAN Plugin for OpenCart 3 empowers you to streamline email and SMS marketing efforts, offering features like subscription forms, contact list management, newsletters, email campaigns, SMS functionalities, smart automations, and detailed analytics. Access these capabilities through the NewsMAN platform for enhanced marketing efficiency.

Key Features:

Subscription Forms & Pop-ups:
Create visually engaging forms and pop-ups for capturing leads.
Sync forms across platforms for a consistent user experience.
Connect forms to automated workflows for enhanced user engagement.

Contact Lists & Segments:
Automate import and synchronization of contact lists.
Utilize advanced segmentation techniques for targeted marketing.

Marketing Campaigns (Email and SMS):
Conduct mass campaigns to keep subscribers engaged.
Personalize campaigns and resend to unopened emails for increased reach.

Marketing Automation (Email & SMS):
Automate personalized product suggestions and follow-up emails.
Address cart abandonment and gather post-purchase feedback.

Ecommerce Remarketing:
Reconnect with subscribers through targeted offers.
Personalize interactions based on user behavior for increased engagement.

SMTP Transactional Emails
Ensure prompt and reliable delivery of transactional messages through SMTP.

Extended Email and SMS Statistics:
Gain comprehensive insights into campaign performance for data-driven decision-making.
