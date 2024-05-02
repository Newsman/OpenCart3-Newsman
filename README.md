# OpenCart 3 - Newsman Newsletter Sync

NewsMAN Plugin for OpenCart 3 facilitates seamless synchronization of your OpenCart customers and subscribers with [Newsman](https://www.newsmanapp.com) lists and segments. Simplify the connection between your shop and NewsMAN by generating an API KEY in your NewsMAN account and installing this plugin. This process allows you to effortlessly sync customer and newsletter subscriber data.

![image](https://raw.githubusercontent.com/Newsman/OpenCart2.3-Newsman/master/assets/newsmanBr.jpg)

# Installation

## Newsman Sync

Manual installation:
1. Copy the contents of the src folder and paste them into your OpenCart 3 root directory.
2. Navigate to admin -> Extensions -> Extension -> Choose extension type -> Modules -> Install the NewsMAN Newsletter Sync module.
3. After installation, edit the NewsMAN Newsletter Sync module.

## Newsman Remarketing

1. Extensions -> Installer -> Upload NewsMANremarketing.ocmod.zip
2. Extensions -> Modifications -> Refresh
3. Extensions -> Extensions -> Analytics -> NewsMAN Remarketing

If the default OCMOD doesn't work, upload Oc 3.x Fix OCMOD.ocmod.zip at Extensions -> Installer.

# Setup

## Newsman Sync

The process is automated; log in with NewsMAN via OAuth, and the settings will be filled automatically based on your selection.

![image](https://raw.githubusercontent.com/Newsman/OpenCart2.3-Newsman/master/assets/oauth1.png)
![image](https://raw.githubusercontent.com/Newsman/OpenCart2.3-Newsman/master/assets/oauth2.png)

![](https://raw.githubusercontent.com/Newsman/OpenCart3-Newsman/master/assets/api-setup-screen-opencart3.png)

2.Choose a list for your newsletter subscribers by setting up your user ID and API key.

For automatic synchronization, set up a webcron to run the URL:  {yoursiteurl}/index.php?route=extension/module/NewsMAN&cron=true

## Newsman Remarketing

1. Fill in your Newsman Remarketing ID and save
![](https://raw.githubusercontent.com/Newsman/OpenCart3-Newsman/master/assets/nr1.png)

Upon installation, the plugin provides feed products and events (product impressions, AddToCart, purchase) automatically.

Description

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
