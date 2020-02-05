# Soup Bot
This is the soup bot which is used to update the slack topic in the MEDIAGENIX workspace
## Intallation
1. Install php so you can run it on your computer. I'm using Xampp (https://www.apachefriends.org/index.html)
2. Install composer (https://getcomposer.org/)
3. Install the pdf parser with the command: 
```bash
composer update
```
4. Create a new php file called **secrets.php** in the src folder containing the next code
```php
<?php
    const TRANSLATION_KEY = 'xxx';
    const SLACK_KEY = 'xxx';
```
5. Generate a Yandex API key (https://translate.yandex.com/developers/keys) and put it in the **secrets.php** file
6. Generate a Slcak API key (https://api.slack.com/legacy/custom-integrations/legacy-tokens) and put it in the **secrets.php** file

4. Put the folder in the **xampp/htdocs** folder and browse to **http://localhost/src/**
