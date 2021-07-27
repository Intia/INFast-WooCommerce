#!/bin/bash -e

# Prevent configuration from being run multiple times
[ -f "config_done" ] && echo "WordPress already configured" && exit 0
touch config_done

echo "Waiting for database..."

# Wait for MySql to start
while ! mysqladmin ping -h"database" --silent; do
    sleep 1
done
echo "Database setup complete. Configuring WordPress..."

export WP_CLI_CACHE_DIR=/tmp

cd /var/www/html

# ---------------------------------------------------------------------------- #
# Configure WordPress installation                                             #
# ---------------------------------------------------------------------------- #

# Finish Wordpress installation
wp core install \
  --url="http://localhost:8080" \
  --title=Infast\ Shop \
  --admin_user=wordpress \
  --admin_password=password \
  --admin_email=dev@intia.fr \
  --skip-email

wp core update

# Install theme with store page
wp theme install storefront --activate

# Install and activate plugins
wp plugin install woocommerce --activate
wp plugin activate infast-woocommerce

wp plugin uninstall hello
wp plugin uninstall akismet

# ---------------------------------------------------------------------------- #
# Personalize WordPress                                                        #
# ---------------------------------------------------------------------------- #

wp option update page_on_front 5
wp option update show_on_front "page"

wp menu create "Main menu"
wp menu location assign main-menu primary
wp menu item add-post main-menu 5 --title="Shop"
wp menu item add-post main-menu 8 --title="Account"

wp widget delete $(wp widget list sidebar-1 --format=ids)

# ---------------------------------------------------------------------------- #
# Configure WooCommerce                                                        #
# ---------------------------------------------------------------------------- #

# Set business info
wp option update woocommerce_store_address "149 Rue Pierre Semard"
wp option update woocommerce_store_city "Brest"
wp option update woocommerce_default_country "FR"
wp option update woocommerce_store_postcode "29200"

# Set currency
wp option update woocommerce_currency "EUR"
wp option update woocommerce_currency_pos "right"
wp option update woocommerce_price_thousand_sep ""
wp option update woocommerce_price_decimal_sep ","

# Set shipping options
wp option update woocommerce_no_sales_tax 1
wp option update woocommerce_all_except_countries "{}" --format=json
wp option update woocommerce_specific_allowed_countries "{}" --format=json
wp option update woocommerce_specific_ship_to_countries "{}" --format=json
wp db query "INSERT INTO wp_woocommerce_shipping_zones VALUES (1, 'France', 0);"
wp db query "INSERT INTO wp_woocommerce_shipping_zone_locations VALUES (1, 1, 'FR', 'country');"
wp db query "INSERT INTO wp_woocommerce_shipping_zone_methods VALUES (1, 1, 'free_shipping', 1, 1);"
wp db query "DELETE FROM wp_postmeta WHERE meta_key = '_stock';"

wp option update woocommerce_demo_store_notice ""
wp option update woocommerce_default_homepage_layout "two_columns"

# Complete WooCommerce setup wizard
wp option update woocommerce_task_list_tracked_completed_tasks \
  '["store_details","products","payments","tax","shipping","appearance"]' \
  --autoload=yes \
  --format=json

wp option update woocommerce_task_list_appearance_complete 1
wp option update woocommerce_task_list_complete "yes"
wp option add woocommerce_task_list_welcome_modal_dismissed "yes" --autoload=yes

wp option add woocommerce_cod_settings \
  '{"enabled":"yes","title":"Cash on delivery","description":"Pay with cash upon delivery.","instructions":"Pay with cash upon delivery.","enable_for_methods":"","enable_for_virtual":"yes"}' \
  --autoload=yes \
  --format=json

wp option add woocommerce_onboarding_profile \
  '{"setup_client":false,"industry":[{"slug":"electronics-computers"}],"product_types":["physical"],"product_count":"1-10","selling_venues":"no","business_extensions":[],"theme":"storefront","completed":true}' \
  --autoload=yes \
  --format=json

# Create sample product
wp wc product create \
  --name="Widget" \
  --type=simple \
  --virtual=1 \
  --sku=001 \
  --regular_price=20 \
  --user=wordpress

# Clear setup notifications
wp db query "UPDATE wp_wc_admin_note_actions SET status = 'actioned' WHERE status != 'actioned';"
