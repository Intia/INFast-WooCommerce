== Development ==

Manual testing can be done with the pre-configured WordPress instance.

Edit the INFast API Url: INFAST_API_URL in infast-woocommece.php file  

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

=== update docker image
```sh
docker-compose down
docker-compose pull
docker-compose up
```

=== Configuration ===

**Enable taxes:**
On WP admin: 
- go on WooCommerce => Settings 
- General tab: and check the "Enable tax rates and calculations" option => SAVE 
- Tax tab: 
  - Click "Standard rates" and insert a new a tax 20%, uncheck "Shipping" => SAVE

**Add shipping method:**
On WP admin: 
- go on WooCommerce => Settings
- Shipping tab, select France
- disable "Free shipping" 
- add a new "Flat rate" method then edit the tax to modify it's name (Ex: Colissimo), tax status to "None", and cost (Ex: 9.90) => SAVE

**Allow create account:**
On WP admin: 
- go on WooCommerce => Settings
- Account & Privacy tab: enable all options of "Account creation" and "Guest checkout" => SAVE

**Set INFast API Keys:**
On WP admin: 
- go on WooCommerce => INFast : set ClientId and ClientSecret


=== Send new version to SVN ===
- Change version in infast-woocommerce.php
- Change stage version in README.txt
- Add a release description for this version

- Create a tag: svn copy trunk tags/1.2.3
- Push: svn ci -m "commit message"