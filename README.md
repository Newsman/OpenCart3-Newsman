# OpenCart 3 - Newsman Newsletter Sync
[Newsman](https://www.newsmanapp.com) plugin for OpenCart 3. Sync your OpenCart customers / subscribers to Newsman list / segments.

This is the easiest way to connect your Shop with [Newsman](https://www.newsmanapp.com).
Generate an API KEY in your [Newsman](https://www.newsmanapp.com) account, install this plugin and you will be able to sync your shop customers and newsletter subscribers with Newsman list / segments.

# Installation

## Newsman Sync

Manual installation:
1.  Copy contents of the src folder and paste to your opencart 3 root directory
2.  Go to admin->Extensions->Extenstion->Choose the extension type->Modules-> and then install Newsman Newsletter Sync module
3.  After installation edit the Newsman Newsletter Sync module

## Newsman Remarketing

1. Extensions -> Installer -> Upload newsmanremarketing.ocmod.zip
2. Extensions -> Modifications -> Refresh
3. Extensions -> Extensions -> Analytics -> Newsman Remarketing

# Setup

## Newsman Sync

1. Fill in your Newsman API KEY and User ID and click connect
![](https://raw.githubusercontent.com/Newsman/OpenCart3-Newsman/master/assets/api-setup-screen-opencart3.png)

2. Choose List for your newsletter subscribers
For the lists to show up in this form, you need to set up your user id and api key.

For the automatic synchronization to work, you must setup a webcron to run this URL:
`{yoursiteurl}/index.php?route=extension/module/newsman&cron=true`

## Newsman Remarketing

1. Fill in your Newsman Remarketing ID and save
![](https://raw.githubusercontent.com/Newsman/OpenCart3-Newsman/master/assets/nr1.png)

After the plugin is installed, you will also have: feed products, events (product impressions, AddToCart, purchase) automatically implemented.
