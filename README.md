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