=== GWS Mauritius SEM Share Price Sync ===
Contributors: davidcommarmond
Author URI: https://www.linkedin.com/in/jcommarmond/
Plugin URI: https://www.gws-technologies.com/
Tags: semdex
Requires at least: 6.0.0
Tested up to: 6.4.1
Requires PHP: 8.0
Stable tag: 1.0.3
License: GPLv2

# Intro
Sync your SEM Share Price in Wordpress

# Requirements
- You will 1st need to install [ACF Pro](https://www.advancedcustomfields.com/pro/) Plugin.
- You will also need to get the XML Feed URL from https://www.stockexchangeofmauritius.com/ in the following format >> https://www.stockexchangeofmauritius.com/shareprice/XXX/XXX.xml

# Setup
- Once you've done the above requirements, activate the plugin
- Go to the "GWS SEM Sync" options page and paste the SEM URL there

# Usage
- In your theme, you can retrieve the share price thus
- Latest shareprice >> GWS_SEM_Share_Price_Sync::get_sharepprice_trend();
- Shareprice for a specific date >> GWS_SEM_Share_Price_Sync::get_sharepprice_trend("yyyy-mm-dd");

# Contribution
- You can contribute by going to https://github.com/GWS-Technologies-LTD/mcb-juice-woocommerce-gateway

# Feature Request
- You may send your feature requests by e-mail on support@gws-technologies.com

# Custom Development
If you need plugin customization, ERP integration, or custom WooCommerce or WordPress Development, get in touch with us at https://www.gws-technologies.com/