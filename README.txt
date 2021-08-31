== Development ==

Manual testing can be done with the pre-configured WordPress instance.

Just launch containers with Docker Compose :

```sh
docker-compose up
```

Once configuration is complete you connect to the site on [localhost:8080](http://localhost:8080)
or to the [admin dashboard](http://localhost:8080/wp-admin/) with the username
`wordpress` and the password `password`.

PHPMyAdmin is available at [localhost:8081](http://localhost:8081) and you can
connect with the username `root` and the password `password`.

If you want to clean up after testing or you want to reset the site you can wipe
the environment with the following command.

```sh
docker-compose down -v
```

To run WP-CLI commands launch an interactive terminal with the "configure" container:

```sh
docker-compose run configure bash
```

=== Configuration ===

**Enable taxes:**
On WP admin: 
- go on WooCommerce => Settings 
- General tab: and check the "Enable tax rates and calculations" option.  
- Tax tab: click "Standard rates" and add a tax 20%, uncheck "Shipping"

**Add shipping method:**
On WP admin: 
- go on WooCommerce => Settings
- Shipping tab: 
- disable "Free shipping" 
- add a new "Flat rate" method, edit name, tax status and cost

**Allow create account:**
On WP admin: 
- go on WooCommerce => Settings
- Account & Privacy tab: enable all options of "Account creation" and "Guest checkout"

**Set INFast API Keys:**
On WP admin: 
- go on WooCommerce => INFast : set ClientId and ClientSecret


