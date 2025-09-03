#!/bin/bash

echo -e "\e[32mRunning tests...\e[39m\n"

# Run PHPUnit tests inside the PHP container
docker compose exec api vendor/bin/phpunit --configuration /var/www/html/phpunit.xml

if [ $? -eq 0 ]; then
    echo -e "\n\e[32mTests passed successfully!\e[39m"
    exit 0
else
    echo -e "\n\e[31mTests failed. Please fix the issues above.\e[39m"
    exit 1
fi